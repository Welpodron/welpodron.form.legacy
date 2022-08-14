<?

namespace Welpodron\Form\Controller;

use Bitrix\Main\Loader;

Loader::includeModule('welpodron.core');
Loader::includeModule('welpodron.form');

use Bitrix\Main\Web\Json;
use Bitrix\Main\Error;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Mail\Event;

use Welpodron\Core\Controller\Poster;

use Welpodron\Form\Model\SchemaTable;
use Welpodron\Form\Model\RequestTable;

class Requests extends Poster
{
    const DEFAULT_GOOGLE_URL = 'https://www.google.com/recaptcha/api/siteverify';
    const DEFAULT_MODULE_ID = 'welpodron.form';

    public function addAction($schemaid = '', $formid = '')
    {
        try {
            $postID = intval(!empty($schemaid) ? $schemaid : $this->postList['schemaid']);
            $token = $this->postList['token'];
            
            if (!$token) {
                if (CurrentUser::get()->isAdmin()) {
                    $this->addError(new Error('Произошла ошибка при попытке прокинуть на сервер запрос без токена капчи'));
                    return $token;
                } else {
                    $this->addError(new Error('Произошла ошибка, попробуйте повторить попытку позднее или свяжитесь с администратором сайта'));
                }

                return false;
            }

            $httpClient = new HttpClient();
            $response = Json::decode($httpClient->post(self::DEFAULT_GOOGLE_URL, ['secret' => Option::get(self::DEFAULT_MODULE_ID, 'GOOGLE_CAPTCHA_SECRET_KEY'), 'response' => $token], true));

            if (!$response['success']) {
                if (CurrentUser::get()->isAdmin()) {
                    $this->addError(new Error('Произошла ошибка при попытке обработать ответ от сервера капчи'));
                    return $response;
                } else {
                    $this->addError(new Error('Произошла ошибка, попробуйте повторить попытку позднее или свяжитесь с администратором сайта'));
                }

                return false;
            }

            $payload = SchemaTable::getList([
                'select' => ['PAYLOAD'],
                'filter' => ['=ID' => $postID],
                'limit' => 1
            ])->fetch()['PAYLOAD'];
    
            if (!$payload) {
                if (CurrentUser::get()->isAdmin()) {
                    $this->addError(new Error('Схема с идентификатором ' . $postID . ' не была найдена или ее поле PAYLOAD отсутствует'));
                    return $payload;
                } else {
                    $this->addError(new Error('Произошла ошибка, попробуйте повторить попытку позднее или свяжитесь с администратором сайта'));
                }

                return false;
            }

            $payload = Json::decode($payload);
            $required = is_array($payload['required']) ? $payload['required'] : [];

            foreach ($required as $mustBe) {
                if (!isset($this->postList[$mustBe]) || empty(trim($this->postList[$mustBe]))) {
                    $this->addError(new Error('Значение: ' . '' . ' поля: ' . $mustBe . ' не соответствует схеме', 'FIELD_VALIDATION_ERROR', ['field' => $mustBe, 'message' => 'Поле обязательно для заполнения']));
                    return false;
                }
            }

            $fields = is_array($payload['fields']) ? $payload['fields'] : [];

            foreach ($fields as $key => $props) {
                $postValue = trim($this->postList[$key]);
                $fieldType = $props['type'];
                $fieldLabel = trim($props['label']) ? trim($props['label']) : $key;
                $classPath = 'Welpodron\Form\Primitives\\' . ucfirst(strtolower(trim($fieldType)));
                // Try to create field with that type
                $object = new $classPath($postValue);

                foreach ($props['restrictions'] as $name => $args) {
                    if (!$object->validate(strval($name), $args)) {
                        $this->addError(new Error('Значение: ' . $postValue . ' поля: ' . $key . ' не соответствует схеме', 'FIELD_VALIDATION_ERROR', ['field' => $key, 'message' => 'Значение: ' . $postValue . ' поля: ' . $key . ' не соответствует схеме']));
                        return false;
                    }
                }
                
                $appendable[$fieldLabel] = $postValue;
            }

            $postFormId = !empty($formid) ? $formid : $this->postList['formid'];

            $request = Context::getCurrent()->getRequest();
            $server = Context::getCurrent()->getServer();
            $userAgent = $request->getUserAgent();
            $userId = CurrentUser::get()->getId();
            $userIp = $request->getRemoteAddress();
            $page = $server->get('HTTP_REFERER');
            $sessionId = bitrix_sessid();

            $arData = [
                'FORM_ID' => strval($postFormId),
                'SCHEMA_ID' => $postID,
                'USER_ID' => intval($userId),
                'SESSION_ID' => strval($sessionId),
                'IP' => strval($userIp),
                'PAGE' => strval($page),
                'USER_AGENT' => strval($userAgent),
                'PAYLOAD' => strval(Json::encode($appendable, JSON_UNESCAPED_UNICODE))
            ];

            if (Option::get(self::DEFAULT_MODULE_ID, 'USE_SAVE') == 'Y') {
                $result = RequestTable::add($arData);

                if (!$result->isSuccess()) {
                    if (CurrentUser::get()->isAdmin()) {
                        $this->addError(new Error('Произошла ошибка при попытке добавить новую запись в таблицу заявок'));
                    } else {
                        $this->addError(new Error('Произошла ошибка, попробуйте повторить попытку позднее или свяжитесь с администратором сайта'));
                    }
                    
                    return false;
                }

                return true;
            }

            if (Option::get(self::DEFAULT_MODULE_ID, 'USE_NOTIFY') == 'Y') {
                $result = Event::send([
                    'EVENT_NAME' => Option::get(self::DEFAULT_MODULE_ID, 'NOTIFY_TYPE'), 
                    'LID' => Context::getCurrent()->getSite(), 
                    'C_FIELDS' => array_merge($arData, ['EMAIL_TO' => Option::get(self::DEFAULT_MODULE_ID, 'NOTIFY_EMAIL')]), 
                ]);

                if (!$result->isSuccess()) {
                    if (CurrentUser::get()->isAdmin()) {
                        $this->addError(new Error('Произошла ошибка при попытке отправить письмо менеджеру сайта'));
                    } else {
                        $this->addError(new Error('Произошла ошибка, попробуйте повторить попытку позднее или свяжитесь с администратором сайта'));
                    }
                    
                    return false;
                }


                return true;
            }

            return true;
        } catch (\Throwable $th) {
            if (CurrentUser::get()->isAdmin()) {
                $this->addError(new Error($th->getMessage()));
            } else {
                $this->addError(new Error('Произошла ошибка, попробуйте повторить попытку позднее или свяжитесь с администратором сайта'));
            }

            return false;
        }

        return true;
    }
}

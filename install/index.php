<?

use Bitrix\Main\Application;
use Bitrix\Main\ModuleManager;
use Welpodron\Form\Model\RequestTable;
use Welpodron\Form\Model\SchemaTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Context;

class welpodron_form extends CModule
{
    const DEFAULT_EVENT_TYPE = 'WELPODRON_FORM_FEEDBACK';
    const DEFAULT_LID = 'ru';

    public function InstallEvents()
    {
        $dbEt = CEventType::GetByID(self::DEFAULT_EVENT_TYPE, self::DEFAULT_LID);
        $arEt = $dbEt->Fetch();

        if (!$arEt) {
            $et = new CEventType;
            $et->Add([
                'LID' => self::DEFAULT_LID,
                'EVENT_NAME' => self::DEFAULT_EVENT_TYPE,
                'NAME' => 'Отправка сообщения через форму обратной связи',
                'EVENT_TYPE' => 'email',
                'DESCRIPTION'  => '
				#FORM_ID# - ID Формы 
				#SCHEMA_ID# - ID Схемы
				#USER_ID# - ID Пользователя
				#SESSION_ID# - Сессия пользователя
				#IP# - IP Адрес пользователя
				#PAGE# - Страница отправки
				#USER_AGENT# - UserAgent
				#PAYLOAD# - Содержимое заявки
				#EMAIL_TO# - Email получателя письма
				'
            ]);
        }

        $dbMess = CEventMessage::GetList('id', 'desc', ['TYPE_ID' => self::DEFAULT_EVENT_TYPE]);
        $arMess = $dbMess->Fetch();

        if (!$arMess) {
            $mess = new CEventMessage;
            $mess->Add([
                'ACTIVE' => 'Y',
                'EVENT_NAME' => self::DEFAULT_EVENT_TYPE,
                'LID' => Context::getCurrent()->getSite(),
                'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
                'EMAIL_TO' => '#EMAIL_TO#',
                'SUBJECT' => '#SITE_NAME#: Сообщение из формы обратной связи',
                'BODY_TYPE' => 'html',
                'LANGUAGE_ID' => self::DEFAULT_LID,
                'MESSAGE' => '
                <!DOCTYPE html>
                <html lang="ru">
                <head>
                <meta charset="utf-8">
                <title>Новая заявка</title>
                </head>
                <body>
                <p>
                Вам было отправлено сообщение через форму обратной связи
                </p>
                <p>
                Содержимое заявки:
                </p>
                <p>
                #PAYLOAD#
                </p>
                <p>
                ID формы через которую была получена заявка: #FORM_ID#
                </p>
                <p>
                ID используемой схемы: #SCHEMA_ID#
                </p>
                <p>
                Отправлено пользователем: #USER_ID#
                </p>
                <p>
                Сессия пользователя: #SESSION_ID#
                </p>
                <p>
                IP адрес отправителя: #IP#
                </p>
                <p>
                Страница отправки: <a href="#PAGE#">#PAGE#</a>
                </p>
                <p>
                Используемый USER AGENT: #USER_AGENT#
                </p>
                <p>
                Письмо сформировано автоматически.
                </p>
                </body>
                </html>
                '
            ]);
        }
    }

    public function UnInstallEvents()
    {
        $dbMess = CEventMessage::GetList('id', 'desc', ['TYPE_ID' => self::DEFAULT_EVENT_TYPE]);
        $arMess = $dbMess->Fetch();

        if ($arMess['ID']) {
            $mess = new CEventMessage;
            $mess->Delete(intval($arMess['ID']));
        }

        $dbEt = CEventType::GetByID(self::DEFAULT_EVENT_TYPE, self::DEFAULT_LID);
        $arEt = $dbEt->Fetch();

        if ($arEt['ID']) {
            $et = new CEventType;
            $et->Delete(intval($arEt['ID']));
        }
    }

    public function DoInstall()
    {
        global $APPLICATION;

        ModuleManager::registerModule($this->MODULE_ID);

        $this->InstallDb();
        $this->InstallFiles();
        $this->InstallEvents();

        $APPLICATION->IncludeAdminFile('Установка модуля ' . $this->MODULE_ID, __DIR__ . '/step.php');
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        $this->UnInstallDb();
        $this->UnInstallFiles();
        $this->UnInstallEvents();

        ModuleManager::unRegisterModule($this->MODULE_ID);
        $APPLICATION->IncludeAdminFile('Деинсталляция модуля ' . $this->MODULE_ID, __DIR__ . '/unstep.php');
    }

    public function InstallDb()
    {
        Loader::includeModule($this->MODULE_ID);

        $connection = Application::getConnection();
        $entitySchema = SchemaTable::getEntity();
        $entityRequest = RequestTable::getEntity();

        if (!$connection->isTableExists($entitySchema->getDBTableName())) {
            $entitySchema->createDBTable();
        }

        if (!$connection->isTableExists($entityRequest->getDBTableName())) {
            $entityRequest->createDBTable();
        }
    }

    public function InstallFiles()
    {
        CopyDirFiles(__DIR__ . '/components/', Application::getDocumentRoot() . '/local/components', true, true);
        CopyDirFiles(__DIR__ . '/admin/', Application::getDocumentRoot() . '/bitrix/admin', true, true);
    }

    public function UnInstallDb()
    {
        Loader::includeModule($this->MODULE_ID);

        $connection = Application::getConnection();

        if ($connection->isTableExists(SchemaTable::getTableName())) {
            $connection->dropTable(SchemaTable::getTableName());
        }

        if ($connection->isTableExists(RequestTable::getTableName())) {
            $connection->dropTable(RequestTable::getTableName());
        }
    }

    public function UnInstallFiles()
    {
        DeleteDirFiles(__DIR__ . '/admin/', Application::getDocumentRoot() . '/bitrix/admin');
        DeleteDirFilesEx(Application::getDocumentRoot() . '/local/components/welpodron/form/');
    }

    public function __construct()
    {
        $this->MODULE_ID = 'welpodron.form';
        $this->MODULE_NAME = 'Модуль welpodron.form';
        $this->MODULE_DESCRIPTION = 'Модуль welpodron.form';
        $this->PARTNER_NAME = 'welpodron';
        $this->PARTNER_URI = 'https://github.com/Welpodron';

        $arModuleVersion = [];

        $path = str_replace('\\', '/', __FILE__);
        $path = substr($path, 0, strlen($path) - strlen('/index.php'));
        include $path . '/version.php';

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }
    }
}

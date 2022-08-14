<?

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Welpodron\Form\Model\SchemaTable;

use Welpodron\Core\Templates\Forms\Input;
use Welpodron\Core\Templates\Forms\Select;
// use Welpodron\Form\Primitives\Helper;

use Welpodron\Core\Templates\Ui\Element;
use Welpodron\Form\Primitives\Text;

use Welpodron\Core\Storage;

Loader::includeModule('welpodron.core');
Loader::includeModule('welpodron.form');

// TODO: REWORK!
require_once __DIR__ . '/ajax.php';

var_dump();

class WelpodronAdminSchemasEditComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        $this->arResult = $this->getResult();
        $this->includeComponentTemplate();
    }

    public function onPrepareComponentParams($arParams)
    {
        $arParams['ID'] = intval($arParams['ID']);
        return $arParams;
    }

    private function getResult()
    {
        $formId = 'form_'.md5(uniqid('', false));
        $formWrapperId = $formId.'_wrapper';
        $formErrorsId = $formId.'_errors';
        
        $appenderId = $formId.'_appender';

        Storage::getInstance()->setContext('welpodron_form_schemas_edit', [
            'form_id' => $formId,
            'form_wrapper' => $formWrapperId,
            'form_errors' =>  $formErrorsId,
            'form_appender' => $appenderId,
        ]);

        $schema = SchemaTable::getByPrimary([SchemaTable::getEntity()->getPrimary() => $this->arParams['ID']])->fetchAll();

        $arResult = [
            'WRAPPER' => 
            new Element(
                'div', 
                ['class' => 'welpodron_form_schemas_edit_grid', 'data-wrapper', 'id' => $formWrapperId]
            ), 
            'ERRORS' => 
            new Element(
                'div',
                ['class' => 'welpodron_form_schemas_edit_grid', 'data-form-errors', 'id' => $formErrorsId]
            ),
            'CONTROLS' => 
            new Element(
                'button',
                ['type' => 'button', 'class' => 'ui-btn ui-btn-primary ui-btn-icon-add', 'id' => $appenderId],
                'Добавить поле'
            )
        ];

        if (is_array($schema) && !empty($schema)) {
            try {
                $payload = Json::decode($schema[0]['PAYLOAD']);
                $required = is_array($payload['required']) ? $payload['required'] : [];
                $fields = is_array($payload['fields']) ? $payload['fields'] : [];

                foreach ($fields as $field => $props) {
                    $arResult['WRAPPER']->addContent(
                        WelpodronAdminSchemasEditController::getTemplate($field, $props, $required),
                    );
                }
            } catch (\Throwable $th) {
                $arResult['ERRORS']->addContent(
                    new Element('div', ['class' => 'ui-alert ui-alert-danger ui-alert-icon-danger'], [
                        new Element('span', ['class' => 'ui-alert-message'], $th->getMessage()),
                        new Element(
                            'span', [
                                'class' => 'ui-alert-close-btn',
                                'onclick' => 'this.parentNode.remove()'
                            ]
                        ),
                    ])
                );
            }
        }
        

        return $arResult;
    }
}

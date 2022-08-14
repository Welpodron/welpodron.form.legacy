<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;

Loader::includeModule('welpodron.core');
Loader::includeModule('welpodron.form');

use Welpodron\Core\Controller\Poster;
use Welpodron\Core\Controller\Actionfilter;
// use Welpodron\Form\Primitives\Helper;
use Bitrix\Main\Error;
use Welpodron\Form\Model\SchemaTable;
use Welpodron\Core\Templates\Forms\Input;
use Welpodron\Core\Templates\Forms\Select;
use Welpodron\Core\View\Renderer;
use Welpodron\Core\Templates\Ui\Element;
use Welpodron\Core\Storage;

class WelpodronAdminSchemasEditController extends Poster
{
    protected function getDefaultPreFilters()
    {
        $filters = parent::getDefaultPreFilters();
        $filters[] = new Actionfilter\Admin();
        return $filters;
    }

    public function getTemplateAction($template = '')
    {
        $selectedTemplate = !empty($template) ? $template : $this->postList['template'];

        $fieldset = static::getTemplate('', [], []);

        return $fieldset->render();
    }

    public function getTypeRestrictionsAction($type = '')
    {
        $selectedType = !empty($type) ? $type : $this->postList['type'];

        $restrictions = static::getTypeRestrictionsView($selectedType);

        $body = ' ';

        foreach ($restrictions as $restriction) {
            $body .= $restriction->render();
        }

        return $body;
    }

    public function updateSchemaAction($id = '', $payload = '')
    {
        $postID = intval(!empty($id) ? $id : $this->postList['id']);
        $postPayload = !empty($payload) ? $payload : $this->postList['payload'];

        $schema = SchemaTable::getByPrimary([SchemaTable::getEntity()->getPrimary() => $postID])->fetchAll();

        if (is_array($schema) && !empty($schema)) {
            // update current schema
            $result = SchemaTable::update($postID, [
                'PAYLOAD' => $postPayload
            ]);

            if (!$result->isSuccess()) {
                $errors = $result->getErrorMessages();

                foreach ($errors as $error) {
                    $this->addError(new Error($error));
                }
            
                return;
            }

            return true;
        }

        // try to add new schema if id is 0
        if ($postID !== 0) {
            $this->addError(new Error('Для добавления новой схемы необходимо обязательно использовать id = 0'));
          
            return;
        }

        $result = SchemaTable::add([
            'PAYLOAD' => $postPayload
        ]);

        if (!$result->isSuccess()) {
            $errors = $result->getErrorMessages();

            foreach ($errors as $error) {
                $this->addError(new Error($error));
            }
            
            return;
        }

        return true;
    }

    public static function getTypeRestrictionsView(string $type = '', array $restrictions = []): array
    {
        $view = [];

        try {
            $classPath = 'Welpodron\Form\Primitives\\' . ucfirst(strtolower(trim($type)));
            $view = $classPath::getRestrictionsView($restrictions);
        } catch (\Throwable $th) {
            // var_dump($th);
        }

        return $view;
    }

    public static function getTypesView(string $selected = ''): Select
    {
        // TODO: REWORK ALL THAT SECTION! WTF IS THIS SHIT
        $id = 'select_'.md5(uniqid('', false));

        return new Select([
            'TEMPLATE' => '/local/modules/welpodron.form/templates/admin/select.php',
            'LABEL' => 'Тип поля',
            'ATTRIBUTES' => [
                'required',
                'name' => 'type',
                'id' => $id,
            ],
            'ELEMENTS' => [
                // HERE USE GET SUPPORTED TYPES
                // TODO: use Reflection ????? if passed selected is instance of Validatable ???
                new Element('option', ['value' => ''], 'Не выбрано'),
                new Element('option', ['value' => 'Integer', (ucfirst(strtolower(trim($selected))) === 'Integer' ? 'selected' : null)], 'Целое число'),
                new Element('option', ['value' => 'Text', (ucfirst(strtolower(trim($selected))) === 'Text' ? 'selected' : null)], 'Строка'),
            ],
            'CONTENT' => Renderer::include(__DIR__ . '/script.php', [
                'ID' => $id,
            ], false)   
        ]);
    }

    public static function getTemplate(string $field, array $props, array $required): Element
    {
        return new Element('div', ['class' => 'welpodron_form_schemas_edit_grid welpodron_form_schemas_edit_p-4 welpodron_form_schemas_edit_rounded welpodron_form_schemas_edit_shadow', 'data-fieldset'], [
            new Element(
                'button',
                ['type' => 'button', 'class' => 'ui-btn ui-btn-danger', 'onclick' => 'this.parentNode.remove()'],
                'Удалить поле'
            ),
            new Input([
                'TEMPLATE' => '/local/modules/welpodron.form/templates/admin/input.php',
                'LABEL' => 'ID',
                'ATTRIBUTES' => [
                    'value' => $field,
                    'type' => 'text',
                    'name' => 'id',
                    'required',
                ]
            ]),
            new Input([
                'TEMPLATE' => '/local/modules/welpodron.form/templates/admin/input.php',
                'LABEL' => 'Название поля',
                'ATTRIBUTES' => [
                    'value' => strval($props['label']),
                    'type' => 'text',
                    'name' => 'label',
                ]
            ]),
            new Select([
                'TEMPLATE' => '/local/modules/welpodron.form/templates/admin/select.php',
                'LABEL' => 'Поле обязательно к заполнению',
                'ATTRIBUTES' => [
                    'required',
                    'name' => 'required',   
                ],
                'ELEMENTS' => [
                    new Element('option', ['value' => '0'], 'Поле не обязательно к заполнению'),
                    new Element('option', ['value' => '1', (in_array($field, $required) ? 'selected' : null)], 'Поле обязательно к заполнению'),
                ]   
            ]),
            static::getTypesView(strval($props['type'])),
            new Element(
                'div', 
                ['data-fieldset', 'data-name' => 'restrictions', 'class' => 'welpodron_form_schemas_edit_grid welpodron_form_schemas_edit_p-4 welpodron_form_schemas_edit_rounded welpodron_form_schemas_edit_shadow'], 
                static::getTypeRestrictionsView(
                    strval($props['type']), 
                    (is_array($props['restrictions']) ? $props['restrictions'] : [])
                )
            ) 
        ]);
    }
}

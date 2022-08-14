<?php
namespace Welpodron\Form\Primitives;
use Welpodron\Core\Templates\Forms\Fieldset;
use Welpodron\Core\Templates\Forms\Input;

use Welpodron\Core\Templates\Ui\Element;
use Welpodron\Core\Templates\Forms\Select;

class Text extends General
{
    public function __construct($value)
    {
        parent::__construct($value);
        
        $this->restrictions['have'] =  function(array $args) {
            if ($this->value === null) {
                return false;
            }

            $result = true;
            $config = array_change_key_case($args);

            $comparison = $config['comparison'];

            if ($comparison && !is_array($comparison)) {
                $options = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', trim(strval($comparison)));
                if ($options) {
                    $arOptionsFiltered = array_filter($options, function ($value) {
                        return $value !== null && $value !== '';
                    });
                    // TODO: Fix special chars like \\ and \. etc
                    $comparison = array_values($arOptionsFiltered);
                }
            }

            $mode = $config['mode'];
            $inverted = (bool)$config['inverted'];
            
            if (is_array($comparison)) {
                foreach ($comparison as $value) {
                    // strpos return true if was found so true !== false it is true
                    $flag = strpos($this->value, trim(strval($value))) !== false;

                    if ($mode === 'all') {
                        if (!$flag) {
                            $result = $inverted ? true : false;
                            break;
                        }
                    } else {
                        // string contains any substring of array of strings
                        if ($flag) {
                            // position was found
                            $result = $inverted ? false : true;
                            break;
                        }
                    }
                }
            } else {
                // TODO: check if it is okay
                $result = strpos($this->value, trim(strval($comparison))) !== false;
                $result = $inverted ? !$result : $result;
            }

            return $result;
        };

        $this->restrictions['regex'] = function(array $args) {
            return true;
        };
    }

    public static function getRestrictionsView(array $restrictions = []):array
    {
        return [
            new Element('div', ['data-fieldset', 'data-name' => 'have', 'class' => 'welpodron_form_schemas_edit_grid welpodron_form_schemas_edit_p-4'], [
                new Element('p', [], 'Строка содержит:'),
                new Input([
                    'TEMPLATE' => '/local/modules/welpodron.form/templates/admin/input.php',
                    'LABEL' => 'Значение',
                    'ATTRIBUTES' => [
                        'value' => is_array($restrictions['have']['comparison']) ? implode(',', $restrictions['have']['comparison']) : $restrictions['have']['comparison'],
                        'type' => 'text',
                        'name' => 'comparison',
                    ]
                ]),
                new Select([
                    'TEMPLATE' => '/local/modules/welpodron.form/templates/admin/select.php',
                    'LABEL' => 'Условие инвертировано',
                    'ATTRIBUTES' => [
                        'name' => 'inverted',   
                    ],
                    'ELEMENTS' => [
                        new Element('option', ['value' => '0'], 'Не инвертировано'),
                        new Element('option', ['value' => '1', ($restrictions['have']['inverted'] == '1' ? 'selected' : null)], 'Инвертировано'),
                    ]   
                ]),
                new Select([
                    'TEMPLATE' => '/local/modules/welpodron.form/templates/admin/select.php',
                    'LABEL' => 'Режим работы',
                    'ATTRIBUTES' => [
                        'name' => 'mode',   
                    ],
                    'ELEMENTS' => [
                        new Element('option', ['value' => 'all'], 'Все подстроки'),
                        new Element('option', ['value' => 'any', ($restrictions['have']['mode'] == 'any' ? 'selected' : null)], 'Любое найденное'),
                    ]   
                ]),
            ]),
            new Element('div', ['data-fieldset', 'data-name' => 'regex', 'class' => 'welpodron_form_schemas_edit_grid welpodron_form_schemas_edit_p-4'], [
                new Element('p', [], 'Строка соответствует регулярному выражению:'),
                new Input([
                    'TEMPLATE' => '/local/modules/welpodron.form/templates/admin/input.php',
                    'LABEL' => 'Значение',
                    'ATTRIBUTES' => [
                        'value' => $restrictions['regex']['comparison'],
                        'type' => 'text',
                        'name' => 'comparison',
                    ]
                ]),
                new Select([
                    'TEMPLATE' => '/local/modules/welpodron.form/templates/admin/select.php',
                    'LABEL' => 'Условие инвертировано',
                    'ATTRIBUTES' => [
                        'name' => 'inverted',   
                    ],
                    'ELEMENTS' => [
                        new Element('option', ['value' => '0'], 'Не инвертировано'),
                        new Element('option', ['value' => '1', ($restrictions['regex']['inverted'] == '1' ? 'selected' : null)], 'Инвертировано'),
                    ]   
                ]),
            ])
        ];
    }
}
<?
namespace Welpodron\Form;

use Welpodron\Form\Primitives\Primitive;
use Welpodron\Core\Templates\Forms\Select;
use Welpodron\Core\Templates\Forms\Option;
use Welpodron\Core\Templates\Forms\Fieldset;

class Helper
{
    public static function getTypeView(string $selected = '', $selectedRestrictions = [])
    {
        $result = new Fieldset([
            'TEMPLATE' => '/local/modules/welpodron.form/templates/admin/fieldset.php',
            'ATTRIBUTES' => [
                'data-fieldset' => 'true',
                'data-name' => 'restrictions'
            ],
        ]);

        // $types = static::getTypes(true);

        // $class = __NAMESPACE__ . '\\' . ucfirst(strtolower($selected));

        // if (empty($types) || !in_array($class, $types, true)) {
        //     return [];
        // }
        // return $class::getRestrictionsRepresentations($selectedRestrictions);
        return $result;
    }

    final public static function getTypes(bool $namespace = false):array
    {
        return Primitive::getChildren($namespace === true);
    }

    final public static function getTypesView(string $selected = ''):Select
    {
        $result = new Select([
            'TEMPLATE' => '/local/modules/welpodron.form/templates/admin/select.php',
            'LABEL' => 'Тип поля',
            'ATTRIBUTES' => [
                'required',
                'name' => 'type',   
            ],
            'ELEMENTS' => [
                new Option([
                    'TEMPLATE' => '/local/modules/welpodron.form/templates/admin/option.php',
                    'CONTENT' => 'Не выбрано',
                ]),
            ]   
        ]);

        foreach (static::getTypes(true) as $type) {
            $class = $type::getClass();

            $result->addElement(
                new Option([
                    'TEMPLATE' => '/local/modules/welpodron.form/templates/admin/option.php',
                    'CONTENT' => $type::getName(),
                    'ATTRIBUTES' => [
                        'value' => $class,
                        ($class === $selected ? 'selected' : null),
                    ]
                ]),
            );
        }

        return $result;
    }
}

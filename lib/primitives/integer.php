<?php
namespace Welpodron\Form\Primitives;

class Integer extends Primitive
{
    protected static $name = 'Целое число';

    public function __construct($value)
    {
        // throw exception if not int
        parent::__construct($value);

        $this->restrictions['max'] = function ($value) {
            return $this->value <= $value;
        };

        $this->restrictions['min'] = function ($value) {
            return $this->value >= $value;
        };
    }

    public static function getRestrictionsRepresentations($selectedRestrictions = [])
    {
        $supportedRestrictions = [
            new Input([
                'LABEL' => 'Максимально допустимое значение',
                'ATTRIBUTES' => [
                    'type' => 'number',
                    'name' => 'max',
                    'class' => 'ui-ctl-element',
                ]
            ]),
            new Input([
                'LABEL' => 'Минимально допустимое значение',
                'ATTRIBUTES' => [
                    'type' => 'number',
                    'name' => 'min',
                    'class' => 'ui-ctl-element',
                ]
            ])
        ];

        foreach ($supportedRestrictions as $restrictionRepresentation) {
            $name = $restrictionRepresentation->getAttributes()['name'];

            if ($selectedRestrictions[$name]) {
                $restrictionRepresentation->addAttribute('value', $selectedRestrictions[$name]);
            }
        }

        return $supportedRestrictions;
    }
}

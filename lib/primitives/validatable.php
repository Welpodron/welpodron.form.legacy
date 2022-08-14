<?php
namespace Welpodron\Form\Primitives;

abstract class Validatable
{
    protected $restrictions = [];

    final public function validate(string $restriction, $arguments)
    {
        $result = true;

        if ($this->restrictions[$restriction]) {
            $result = $this->restrictions[$restriction]($arguments);
        }

        if (!$result) {
            // throw validation error if false
        }

        return $result;
    }

    final public function getRestrictions()
    {
        return $this->restrictions;
    }

    public static function getRestrictionsView():array
    {
        return [];
    }
}




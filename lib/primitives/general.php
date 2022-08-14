<?
namespace Welpodron\Form\Primitives;

abstract class General extends Validatable
{
    protected static $name = '';
    protected $value = null;

    public function __construct($value)
    {
        $this->value = $value;
    }

    final public static function getName():string
    {
        return static::$name;
    }

    final public function getValue()
    {
        return $this->value;
    }
}

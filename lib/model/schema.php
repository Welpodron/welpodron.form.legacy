<?php
namespace Welpodron\Form\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\Web\Json;

class SchemaTable extends Entity\DataManager
{
    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getMap()
    {
        return [
            new Entity\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true
            ]),
            new Entity\TextField('PAYLOAD', [
                'title' => 'Схема',
                'validation' => function () {
                    return [
                        function ($value) {
                            try {
                                $schema = Json::decode($value);

                                // for each schema field 
                                // if schema field doesnt contains 
                                // type
                                // required 
                                // trhow exception
                                if (false) {
                                    # code...
                                }
                            } catch (\Throwable $th) {
                                return $th->getMessage();
                            }

                            return true;
                        }
                    ];
                }
            ])
        ];
    }

    public static function getTableName()
    {
        return 'welpodron_form_schema';
    }
}

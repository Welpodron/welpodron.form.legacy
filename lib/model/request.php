<?php
namespace Welpodron\Form\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\Type;

class RequestTable extends Entity\DataManager
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
            new Entity\StringField('FORM_ID', [
                'title' => 'Форма'
            ]),
            new Entity\IntegerField('SCHEMA_ID', [
                'title' => 'Схема'
            ]),
            new Entity\StringField('USER_ID', [
                'title' => 'Пользователь'
            ]),
            new Entity\StringField('SESSION_ID', [
                'title' => 'Сессия'
            ]),
            new Entity\StringField('IP'),
            new Entity\StringField('PAGE', [
                'title' => 'Страница'
            ]),
            new Entity\TextField('USER_AGENT', [
                'title' => 'User-Agent'
            ]),
            new Entity\DatetimeField('DATE_CREATED', [
                'title' => 'Дата получения',
                'default_value' => new Type\Datetime()
            ]),
            new Entity\TextField('PAYLOAD', [
                'title' => 'Содержание'
            ])
        ];
    }

    public static function getTableName()
    {
        return 'welpodron_form_request';
    }
}

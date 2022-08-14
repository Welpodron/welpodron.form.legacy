<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'PARAMETERS' => [
        'COMPONENT_ID' => [
            'NAME' => 'Id соглашения',
            'PARENT' => 'BASE',
            'TYPE' => 'STRING',
            'DEFAULT' => ''
        ],
        'AGREEMENT_ID' => [
            'NAME' => 'Id соглашения',
            'PARENT' => 'BASE',
            'TYPE' => 'STRING',
        ],
        'FIELDS' => [
            'NAME' => 'Поля соглашения',
            'PARENT' => 'BASE',
            'TYPE' => 'STRING',
        ],
        'CACHE_TIME' => ['DEFAULT' => 36000],
        'CACHE_GROUPS' => [
            'PARENT' => 'CACHE_SETTINGS',
            'NAME' => 'Учитывать права доступа',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y'
        ]
    ]
];

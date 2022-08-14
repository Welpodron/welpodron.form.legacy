<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;

$moduleId = 'welpodron.form'; //обязательно, иначе права доступа не работают!

Loader::includeModule($moduleId);

$request = Context::getCurrent()->getRequest();

#Описание опций

$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => 'Основные настройки',
        'OPTIONS' => [
            [
                'USE_SAVE', 
                'Сохранять заявки в базу данных',
                'Y',
                ['checkbox']
            ],
            [
                'USE_NOTIFY', 
                'Отправлять сообщение о заявке менеджеру сайта',
                'Y',
                ['checkbox']
            ],
            [
                'NOTIFY_TYPE', 
                'Тип почтового события',
                'WELPODRON_FORM_FEEDBACK',
                ['text', 40]
            ],  
            [
                'NOTIFY_EMAIL', 
                'Email менеджера сайта',
                Option::get('main', 'email_from'),
                ['text', 40]
            ],
        ]
    ],
    [
        'DIV' => 'edit2',
        'TAB' => 'Настройки капчи',
        'OPTIONS' => [
            [
                'GOOGLE_CAPTCHA_SECRET_KEY', 
                'Секретный ключ v3',
                '',
                ['text', 40]
            ],
            [
                'GOOGLE_CAPTCHA_PUBLIC_KEY', 
                'Публичный ключ v3',
                '',
                ['text', 40]
            ],
        ]
    ],
];
#Сохранение

if ($request->isPost() && $request['save'] && check_bitrix_sessid()) {
    foreach ($aTabs as $aTab) {
        __AdmSettingsSaveOptions($moduleId, $aTab['OPTIONS']);
    }

    LocalRedirect($APPLICATION->GetCurPage() . '?lang=' . LANGUAGE_ID . '&mid_menu=1&mid=' . urlencode($moduleId) .
        '&tabControl_active_tab=' . urlencode($request['tabControl_active_tab']));
}

#Визуальный вывод

$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>
<form method='post' name='welpodron_form_settings'>
    <? $tabControl->Begin(); ?>
    <?foreach ($aTabs as $aTab):?>
    <?
        $tabControl->BeginNextTab();
        __AdmSettingsDrawList($moduleId, $aTab['OPTIONS']);
    ?>
    <?endforeach;?>
    <?$tabControl->Buttons(['btnApply' => false, 'btnCancel' => false, 'btnSaveAndAdd' => false]); ?>
    <?=bitrix_sessid_post();?>
    <?$tabControl->End();?>
</form>

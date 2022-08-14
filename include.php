<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

CJSCore::RegisterExt('welpodron.form', [
    'js' => '/local/modules/welpodron.form/js/script.js',
    'rel' => ['welpodron.networker', 'welpodron.templater', 'welpodron.modal'],
    'skip_core' => true
]);

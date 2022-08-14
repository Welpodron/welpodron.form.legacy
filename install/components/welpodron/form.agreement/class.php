<?

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\UserConsent\Agreement;

class WelpodronFormAgreement extends CBitrixComponent
{
    public function executeComponent()
    {
        if ($this->startResultCache($this->arParams['CACHE_TIME'], $this->arParams['CACHE_GROUPS'])) {
            $this->arResult = $this->getText();

            if (!($this->arParams['ID'] > 0)) {
                $this->AbortResultCache();
            }

            $this->includeComponentTemplate();
        }

        return $this->arResult;
    }

    public function onPrepareComponentParams($arParams)
    {
        if ($arParams['CACHE_GROUPS'] === 'N') {
            $arParams['CACHE_GROUPS'] = false;
        } else {
            $arParams['CACHE_GROUPS'] = CurrentUser::get()->getUserGroups();
        }

        $arParams['CACHE_TIME'] = isset($arParams['CACHE_TIME']) ? $arParams['CACHE_TIME'] : 36000;
        $arParams['ID'] = intval($arParams['ID']);

        $arDefault = ['IP Адрес', 'Информация о браузере пользователя', 'Идентификатор сессии пользователя'];

        $options = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', trim(strval($arParams['FIELDS'])));
        if ($options) {
            $arOptionsFiltered = array_filter($options, function ($value) {
                return $value !== null && $value !== '';
            });
            $arParams['FIELDS'] = array_values($arOptionsFiltered);
        } else {
            $arParams['FIELDS'] = [];
        }

        $arParams['MODAL_ID'] = $arParams['MODAL_ID'] ? $arParams['MODAL_ID'] : 'modal_'.md5(uniqid('', false));

        $arParams['FIELDS'] = array_merge($arParams['FIELDS'], $arDefault);

        return $arParams;
    }

    protected function getText()
    {
        if ($this->arParams['ID'] > 0) {
            $agreement = new Agreement($this->arParams['ID']);

            if (!$agreement->isExist() || !$agreement->isActive()) {
                return;
            }

            $agreement->setReplace(['fields' => $this->arParams['FIELDS']]);

            return $agreement->getHtml();
        }
    }
}

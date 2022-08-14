<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php'; // первый общий пролог

use Bitrix\Main\Loader;

Loader::includeModule('welpodron.form');

use Welpodron\Form\Model\SchemaTable;

// NEW TOOLBAR
use Bitrix\Main\UI\Extension;
use Bitrix\UI\Buttons;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\Main\Context;

global $adminPage;
$adminPage->hideTitle();

$arActions = Context::getCurrent()->getRequest()->getQueryList()->toArray();

if ($arActions['op'] === 'delete' && $arActions['id']) {
    SchemaTable::delete($arActions['id']);
}

$gridId = SchemaTable::getTableName();
$filterId = $gridId . '_filter';
$toolbarId = $gridId . '_toolbar';

$primaryKey = SchemaTable::getEntity()->getPrimary();

$gridOptions = new Bitrix\Main\Grid\Options($gridId);

$sortOptions = $gridOptions->GetSorting(['sort' => [$primaryKey => 'DESC'], 'vars' => ['by' => 'by', 'order' => 'order']]);

$navParams = $gridOptions->GetNavParams();

$nav = new Bitrix\Main\UI\PageNavigation($gridId);

$nav->allowAllRecords(true)
    ->setPageSize($navParams['nPageSize'])
    ->initFromUri();

$uiColumns = [];
$uiFilter = [];

$tableFields = [];

foreach (SchemaTable::getEntity()->getFields() as $key => $value) {
    $tableFields[$key] = $value->getTitle();
    $uiColumns[] = ['id' => $key, 'name' => $value->getTitle(), 'sort' => $key, 'default' => true];
    $uiFilter[] = ['id' => $key, 'name' => $value->getTitle(), 'type' => $value->getDataType(), 'default' => true];
}

$filterOptions = new \Bitrix\Main\UI\Filter\Options($filterId);
$filterData = $filterOptions->getFilter([]);
$filter = [];

$filterAny = [];
$filterExact = [];

if ($filterData['FIND']) {
    $filterAny = ['LOGIC' => 'OR'];
    foreach ($tableFields as $k => $v) {
        $filterAny[$k] = $filterData['FIND'];
    }
}

foreach ($filterData as $k => $v) {
    if ($tableFields[$k]) {
        $filterExact[$k] = $v;
    }
}

if (empty($filterExact) && !empty($filterAny)) {
    $filter = $filterAny;
}

if (!empty($filterExact) && empty($filterAny)) {
    $filter = $filterExact;
}

if (!empty($filterExact) && !empty($filterAny)) {
    $filter = [
        'LOGIC' => 'OR',
        $filterExact,
        $filterAny
    ];
}

$db = SchemaTable::getList([
    'select' => ['*'],
    'filter' => $filter,
    'count_total' => true,
    'offset' => $nav->getOffset(),
    'limit' => $nav->getLimit(),
    'order' => $sortOptions['sort']
]);

$nav->setRecordCount($db->getCount());

foreach ($db->fetchAll() as $row) {
    $list[] = [
        'data' => $row,
        'actions' => [
            [
                'text' => 'Просмотр',
                'default' => true,
                'onclick' => 'BX.SidePanel.Instance.open(`'.str_replace('#id#', 0, BX_ROOT . '/admin/welpodron.form_schemas_edit.php?ID='.$row[$primaryKey] .'&lang=' . LANGUAGE_ID).'`, {cacheable: false,})'
            ], [
                'text' => 'Удалить',
                'default' => true,
                'onclick' => 'if(confirm("Вы точно хотите удалить схему?")){document.location.href="?op=delete&id=' . $row['ID'] . '"}'
            ]
        ]
    ];
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

Extension::load(['sidepanel']);

$APPLICATION->IncludeComponent('bitrix:ui.toolbar', 'admin', []);

Toolbar::addFilter([
    'GRID_ID' => $gridId,
    'FILTER_ID' => $filterId,
    'FILTER' =>$uiFilter,
    'DISABLE_SEARCH' => true, //Данный параметр отключет возможность использовать фильтр + поиск (нельзя вводить значения и тд)
    'ENABLE_LABEL' => true,
]);

$addButton = new Buttons\Button([
    'color' => Buttons\Color::PRIMARY,
    'icon' => Buttons\Icon::ADD,
    'click' => new Buttons\JsCode(
    	'BX.SidePanel.Instance.open(`'.str_replace('#id#', 0, BX_ROOT . '/admin/welpodron.form_schemas_edit.php?ID=0&lang=' . LANGUAGE_ID).'`, {
            cacheable: false,
        })'
    ),
    'text' => 'Добавить схему'
]);

Toolbar::addButton($addButton);

$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
    'GRID_ID' => $gridId,
    'COLUMNS' => $uiColumns,
    'ROWS' => $list,
    'SHOW_ROW_CHECKBOXES' => false,
    'NAV_OBJECT' => $nav,
    'AJAX_MODE' => 'Y',
    'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
    'PAGE_SIZES' => [
        ['NAME' => '5', 'VALUE' => '5'],
        ['NAME' => '10', 'VALUE' => '10'],
        ['NAME' => '20', 'VALUE' => '20'],
        ['NAME' => '50', 'VALUE' => '50'],
        ['NAME' => '100', 'VALUE' => '100']
    ],
    'AJAX_OPTION_JUMP' => 'N',
    'SHOW_CHECK_ALL_CHECKBOXES' => false,
    'SHOW_ROW_ACTIONS_MENU' => true,
    'SHOW_GRID_SETTINGS_MENU' => true,
    'SHOW_NAVIGATION_PANEL' => true,
    'SHOW_PAGINATION' => true,
    'SHOW_SELECTED_COUNTER' => false,
    'SHOW_TOTAL_COUNTER' => false,
    'SHOW_PAGESIZE' => true,
    'SHOW_ACTION_PANEL' => false,
    'ALLOW_COLUMNS_SORT' => true,
    'ALLOW_COLUMNS_RESIZE' => true,
    'ALLOW_HORIZONTAL_SCROLL' => true,
    'ALLOW_SORT' => true,
    'ALLOW_PIN_HEADER' => true,
    'AJAX_OPTION_HISTORY' => 'N'
]);


// $APPLICATION->IncludeComponent(
// 	'bitrix:main.userconsent.list',
// 	'',
// 	[
// 	    'PATH_TO_LIST' => BX_ROOT . '/admin/agreement_admin.php?lang=' . LANGUAGE_ID,
// 	    'PATH_TO_ADD' => BX_ROOT . '/admin/agreement_edit.php?ID=0&lang=' . LANGUAGE_ID,
// 	    'PATH_TO_EDIT' => BX_ROOT . '/admin/agreement_edit.php?ID=#id#&lang=' . LANGUAGE_ID,
// 	    'PATH_TO_CONSENT_LIST' => BX_ROOT .
// 	    	'/admin/agreement_consents.php?AGREEMENT_ID=#id#&apply_filter=Y&lang=' . LANGUAGE_ID,
// 	    'CAN_EDIT' => $canEdit,
// 	    'ADMIN_MODE' => true
// 	]
// );

?>

<?
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';

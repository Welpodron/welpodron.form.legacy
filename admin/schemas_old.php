<?php
// подключим все необходимые файлы:
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php'; // первый общий пролог

use Bitrix\Main\Application;
use Welpodron\Form\SchemaTable;

// require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/subscribe/include.php'; // инициализация модуля
// require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/subscribe/prolog.php'; // пролог модуля

// Получение таблицы
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/welpodron.form/lib/schema.php';

$tableFields = [];

foreach (SchemaTable::getEntity()->getFields() as $key => $value) {
    $tableFields[] = ['NAME' => $key, 'TITLE' => $value->getTitle()];
}

if ($tableFields) {
    // TODO: Rework
    $primaryKey = SchemaTable::getEntity()->getPrimary();

    $request = Application::getInstance()->getContext()->getRequest();

    $sTableID = SchemaTable::getTableName(); // ID таблицы
    // TODO: Fix initial sorting
    $oSort = new CAdminSorting($sTableID, $tableFields[0]['NAME'], 'desc');
    $lAdmin = new CAdminList($sTableID, $oSort);

    // проверку значений фильтра для удобства вынесем в отдельную функцию
    function CheckFilter()
    {
        global $arFilters, $lAdmin;
        foreach ($arFilters as $f) {
            global $$f;
        }

        // $lAdmin->AddFilterError('текст_ошибки');

        // В данном случае проверять нечего.
        // В общем случае нужно проверять значения переменных $find_имя
        // и в случае возниконовения ошибки передавать ее обработчику
        // посредством $lAdmin->AddFilterError('текст_ошибки').

        return count($lAdmin->arFilterErrors) == 0; // если ошибки есть, вернем false;
    }

    $arHeaders = [];
    $arFieldsNames = [];
    $arFilter = [];
    $arFilters = [];
    $arRenderedFilters = [];

    foreach ($tableFields as $value) {
        $arHeaders[] = ['id' => $value['NAME'], 'content' => $value['TITLE'], 'sort' => mb_strtolower($value['NAME']), 'default' => true];
        $arFieldsNames[] = $value['TITLE'];

        $filterName = 'find_' . mb_strtolower($value['NAME']);

        $arFilters[] = $filterName;
        $arRenderedFilters[] = ['NAME' => $value['NAME'], 'TITLE' => $value['TITLE'], 'FILTER' => $filterName];
    }

    $lAdmin->InitFilter($arFilters);

    if (CheckFilter()) {
        foreach ($arRenderedFilters as $value) {
            $currentFilterValue = htmlspecialchars(${$value['FILTER']});

            if ($currentFilterValue) {
                $arFilter['=' . $value['NAME']] = $currentFilterValue;
            }
        }
    }

    $selectQuery = [];
    $selectQuery['select'] = ['*'];
    $selectQuery['order'] = [mb_strtoupper($by) => mb_strtoupper($order)];

    if ($arFilter) {
        $selectQuery['filter'] = $arFilter;
    }

    $rows = SchemaTable::getList($selectQuery);

    $rsData = new CAdminResult($rows, $sTableID);

    $rsData->NavStart();

    $lAdmin->NavText($rsData->GetNavPrint('Заявки'));

    $lAdmin->AddHeaders($arHeaders);

    while ($arRes = $rsData->GetNext(true, false)) {
        if ($arRes[$primaryKey]) {
            // Поскольку строку не нужно редактировать передавать id строке не обязательно
            $row = &$lAdmin->AddRow($arRes[$primaryKey], $arRes);
// $row = &$lAdmin->AddRow(md5(uniqid('', false)), $arRes);
            // TODO: Rework!
            $row->AddViewField('PAYLOAD', '<a href="rubric_edit.php?ID=' . $arRes[$primaryKey] . '&lang=' . LANG . '">' . 'Посмотреть схему' . '</a>');

            $arActions = [];
// редактирование элемента
            $arActions[] = [
                'ICON' => 'edit',
                'DEFAULT' => true,
                'TEXT' => 'Изменить схему',
                'ACTION' => $lAdmin->ActionRedirect('welpodron.form_schemas_edit.php?ID=' . $arRes[$primaryKey])
            ];

            $arActions[] = [
                'ICON' => 'delete',
                'TEXT' => 'Удалить схему',
                'ACTION' => "if(confirm('" . 'Вы действительно хотите удалить схему?' . "')) " . $lAdmin->ActionDoGroup($arRes[$primaryKey], 'delete')
            ];

            $row->AddActions($arActions);

        }
    }

    $lAdmin->AddGroupActionTable([
        'delete' => 'Удалить выбранные схемы' // удалить выбранные элементы
    ]);

    // сформируем меню из одного пункта - добавление рассылки
    $aContext = [
        [
            'TEXT' => 'Добавить новую схему',
            'LINK' => 'rubric_edit.php?lang=' . LANG,
            'TITLE' => 'Добавить новую схему',
            'ICON' => 'btn_new'
        ]
    ];

// и прикрепим его к списку
    $lAdmin->AddAdminContextMenu($aContext);

// альтернативный вывод
    $lAdmin->CheckListMode();

// не забудем разделить подготовку данных и вывод
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

    // ******************************************************************** //
    //                ВЫВОД ФИЛЬТРА                                         //
    // ******************************************************************** //

// создадим объект фильтра
    $oFilter = new CAdminFilter($sTableID . '_filter', $arFieldsNames);

    ?>

    <form name="find_form" method="get" action="<?echo $APPLICATION->GetCurPage(); ?>">
<?$oFilter->Begin();?>
<?foreach ($arRenderedFilters as $value): ?>
<tr>
  <td><?=($value['TITLE'] . ':')?></td>
  <td>
      <input placeholder="Введите строку для поиска" title="Введите строку для поиска" type="text" name="<?=$value['FILTER']?>" size="47" value="<?=(htmlspecialchars(${$value['FILTER']}))?>">
  </td>
</tr>
<?endforeach;?>
<?
    $oFilter->Buttons(['table_id' => $sTableID, 'url' => $APPLICATION->GetCurPage(), 'form' => 'find_form']);
    $oFilter->End();
    ?>
</form>

<?
    $lAdmin->DisplayList();

}

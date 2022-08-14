<?
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php'; // первый общий пролог

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

use Bitrix\Main\UI\Extension;
Extension::load(['ui.sidepanel-content']);

global $adminSidePanelHelper;

$componentParameters['ID'] = $ID;

// TODO: check if user is admin because frame sometimes give error is session is expired

?>

<?if ($adminSidePanelHelper->isSidePanel()):?>
<?
    $APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
		    'POPUP_COMPONENT_NAME' => 'welpodron:admin.schemas.edit',
		    'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		    'POPUP_COMPONENT_PARAMS' => $componentParameters,
		    'RELOAD_GRID_AFTER_SAVE' => true,
		]
	);
?>
<?else:?>
<div class="ui-slider-no-access">
	<div class="ui-slider-no-access-inner">
		<div class="ui-slider-no-access-title">Задача не найдена или доступ запрещен</div>
		<div class="ui-slider-no-access-subtitle">Обратитесь к участникам задачи или администратору портала</div>
		<div class="ui-slider-no-access-img">
			<div class="ui-slider-no-access-img-inner"></div>
		</div>
	</div>
</div>
<?endif;?>

<?

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';

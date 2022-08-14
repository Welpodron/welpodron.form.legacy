<?if (!check_bitrix_sessid()) {return;}?>
<?=CAdminMessage::ShowNote('Модуль welpodron.form успешно удален');?>
<form action="<?=$APPLICATION->GetCurPage();?>">
	<input type="hidden" name="lang" value="<?=LANG?>">
	<input type="submit" name="" value="Вернуться к списку установленных пакетов">
<form>

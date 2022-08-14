<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

unset($arResult['ATTRIBUTES']['class']);
?>
<div data-type="<?=$arResult['ATTRIBUTES']['type']?>" data-name="<?=$arResult['ATTRIBUTES']['name']?>" data-field="true">
  <label>
    <span><?=$arResult['LABEL']?></span>
  </label>
  <div class="ui-ctl ui-ctl-w100">
    <input class="ui-ctl-element" <?foreach ($arResult['ATTRIBUTES'] as $attribute=> $value): echo (is_int($attribute) ? ' ' . $value . ' ' : ' ' . $attribute . '=' . '"' . $value . '" '); endforeach;?>>
  </div>
</div>

<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

unset($arResult['ATTRIBUTES']['class']);
?>
<div data-type="select" data-name="<?=$arResult['ATTRIBUTES']['name']?>" data-field="true">
  <label>
    <span><?=$arResult['LABEL']?></span>
  </label>
  <div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
    <div class="ui-ctl-after ui-ctl-icon-angle"></div>
    <select class="ui-ctl-element" <?foreach ($arResult['ATTRIBUTES'] as $attribute=> $value): echo (is_int($attribute) ? ' ' . $value . ' ' : ' ' . $attribute . '=' . '"' . $value . '" '); endforeach;?>>
      <?foreach ($arResult['ELEMENTS'] as $element):?>
      <?=$element->render()?>
      <?endforeach;?>
    </select>
  </div>
  <?=$arResult['CONTENT']?>
</div>

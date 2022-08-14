<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
<div <?foreach ($arResult['ATTRIBUTES'] as $attribute=> $value): echo (is_int($attribute) ? ' ' . $value . ' ' : ' ' . $attribute . '=' . '"' . $value . '" '); endforeach;?>>
  <?foreach ($arResult['ELEMENTS'] as $element):?>
  <?=$element->render()?>
  <?endforeach;?>
  <?=$arResult['CONTENT']?>
</div>

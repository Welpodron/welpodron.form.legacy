<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
<option <?foreach ($arResult['ATTRIBUTES'] as $attribute=> $value): echo (is_int($attribute) ? ' ' . $value . ' ' : ' ' . $attribute . '=' . '"' . $value . '" '); endforeach;?>>
  <?=$arResult['CONTENT']?>
</option>

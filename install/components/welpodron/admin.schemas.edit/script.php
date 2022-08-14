<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
<script>
  (() => {
    // REWRITE TO USE OBSERVER PUT THIS SHIT IS SCRIPTS!
    const handleChange = () => {
      // Bitrix check
      if (!BX || !BX.bitrix_sessid) {
        return;
      }
      // Core module check
      if (!welpodron || !welpodron.request || !welpodron.templater || !welpodron.templater.renderString) {
        return;
      }

      const select = document.getElementById('<?=$arResult['ID']?>');

      if (!select) {
        return;
      }

      const fieldset = select.closest('[data-fieldset]');

      if (!fieldset) {
        return;
      }

      const appendable = fieldset.querySelector('[data-name="restrictions"]');

      if (!appendable) {
        return;
      }

      select.addEventListener('change', ({
        currentTarget
      }) => {
        const data = new FormData();
        data.set('sessid', BX.bitrix_sessid());
        data.set('type', currentTarget.value);

        // TODO: Replace path to \Bitrix\Main\Engine\UrlManager::getInstance()->create()
        const path = '/bitrix/services/main/ajax.php?c=welpodron:admin.schemas.edit&mode=ajax&action=getTypeRestrictions';

        const request = new welpodron.request(path, {
          get: 'json',
        }).post({
          body: data
        }).then(data => {
          if (data.status !== "success") {
            throw new Error('Произошла ошибка при обработке запроса');
          }

          if (!data.data) {
            return;
          }

          welpodron.templater.renderString(data.data, appendable, {
            replace: true
          });
        }).catch(err => {
          const date = new Date();
          console.error(`[${date.toUTCString()}] Произошла ошибка при обработке запроса: ${err}`);
        });
      });
    }

    if (document.readyState === "complete") {
      return handleChange();
    } else {
      document.addEventListener('DOMContentLoaded', () => {
        return handleChange();
      }, {
        once: true
      });
    }
  })()

</script>

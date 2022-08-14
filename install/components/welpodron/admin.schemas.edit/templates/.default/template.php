<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\UI\Extension;
use Welpodron\Core\Storage;

Extension::load(['ui.forms', 'ui.alerts', 'ui.buttons.icons']);

CJSCore::Init(['welpodron.networker']);
CJSCore::Init(['welpodron.templater']);
CJSCore::Init(['welpodron.form']);

$formAction = CHTTP::urlAddParams($APPLICATION->getCurPageParam(), ['save' => 'y']);
$context = Storage::getInstance()->getContext('welpodron_form_schemas_edit');
?>

<form class="welpodron_form_schemas_edit_grid" submit="false" id="<?=$context['form_id']?>" method="post" action="<?=$formAction?>">
    <?=$arResult['ERRORS']->render()?>
    <?=$arResult['WRAPPER']->render()?>
    <?=$arResult['CONTROLS']->render()?>
    <!-- НАЧАЛО КОНТЕЙНЕРА КНОПОК СОХРАНЕНИЯ САЙДБАРА -->
    <div>
        <?php
        
        $buttons = [];
        $buttons[] = [
            'TYPE' => 'save',
        ];
        $buttons[] = [
            'TYPE' => 'cancel',
            'LINK' => $arParams['PATH_TO_LIST'],
            'CAPTION' => 'Отменить'
        ];
        $APPLICATION->includeComponent('bitrix:ui.button.panel', '', [
            'BUTTONS' => $buttons
        ]);
        
        ?>
    </div>
    <!-- КОНЕЦ КОНТЕЙНЕРА КНОПОК СОХРАНЕНИЯ САЙДБАРА -->
</form>

<script>
    (() => {
        // REWRITE TO USE OBSERVER PUT THIS SHIT IS SCRIPTS!
        const form = document.getElementById('<?=$context['form_id']?>');
        const appender = document.getElementById('<?=$context['form_appender']?>');
        const errorsContainer = document.getElementById('<?=$context['form_errors']?>');
        const wrapper = document.getElementById('<?=$context['form_wrapper']?>');

        appender.onclick = (evt) => {
            evt.preventDefault();
            appender.disabled = true;

            const data = new FormData();
            data.set('sessid', BX.bitrix_sessid());

            const request = new welpodron.request('/bitrix/services/main/ajax.php?c=welpodron:admin.schemas.edit&mode=ajax&action=getTemplate', {
                get: 'json',
            }).post({
                body: data
            }).then(data => {
                welpodron.templater.renderString(data.data, wrapper, {
                    trim: false
                });
            }).catch(err => {
                console.error(err);
            }).finally(() => {
                appender.disabled = false;
            });
        }

        new welpodron.forms.form(form, {
            before: (instance) => {
                instance.init();

                const currentData = instance.getArray();
                const required = new Set();
                const fields = {};

                currentData.every((field) => {
                    if (!((!!field) && (field.constructor === Object))) {
                        // if not raw object return
                        return false;
                    }

                    if (field.id && field.type) {
                        if (field.required === '1') {
                            required.add(field.id);
                        }

                        fields[field.id] = {
                            type: field.type,
                            label: field.label
                        }

                        if ((!!field.restrictions) && (field.restrictions.constructor === Object)) {
                            const restrictions = {};

                            for (const [restriction, params] of Object.entries(field.restrictions)) {
                                if ((!!params) && (params.constructor === Object)) {
                                    if (!Object.keys(params).includes('comparison')) {
                                        continue;
                                    }

                                    const temp = {};

                                    for (const [configName, configValue] of Object.entries(params)) {
                                        if (configName === "inverted" && configValue === "0") {
                                            continue;
                                        }

                                        temp[configName] = configValue;
                                    }

                                    restrictions[restriction] = temp;
                                }
                            }

                            if (Object.keys(restrictions).length) {
                                fields[field.id].restrictions = restrictions;
                            }
                        }
                    }

                    return true;
                });

                const schema = (JSON.stringify({
                    fields,
                    required: required
                }));

                const data = new FormData();
                data.set('sessid', BX.bitrix_sessid());
                data.set('id', <?=$arParams['ID']?> );
                data.set('payload', schema);

                const request = new welpodron.request('/bitrix/services/main/ajax.php?c=welpodron:admin.schemas.edit&mode=ajax&action=updateSchema', {
                    get: 'json'
                }).post({
                    body: data
                }).then((data) => {
                    if (data.status !== 'success') {
                        // TODO: Rework!
                        const errors = [];

                        data.errors.forEach(err => {
                            const template = `
                                <div class="ui-alert ui-alert-danger ui-alert-icon-danger">
                                    <span class="ui-alert-message">${err.message}</span>
                                    <span class="ui-alert-close-btn" onclick="this.parentNode.remove()"></span>
                                </div>
                            `;

                            welpodron.templater.renderString(template, errorsContainer, {
                                trim: false
                            });

                            errors.push(err.message);
                        });

                        throw errors;
                    }

                    if (!data.data) {
                        return;
                    }

                    console.log(data);
                }).catch(err => {
                    const date = new Date();

                    if (Array.isArray(err)) {
                        err.forEach(error => {
                            console.error(`[${date.toUTCString()}] Произошла ошибка при обработке запроса: ${error}`);
                        });

                        return;
                    }

                    console.error(`[${date.toUTCString()}] Произошла ошибка при обработке запроса: ${err}`);
                }).finally(() => {
                    instance.activate(true);
                    const saveBtn = document.querySelector("#ui-button-panel-save");
                    if (saveBtn) {
                        saveBtn.classList.remove('ui-btn-wait');
                    }

                    if (BX && BX.UI && BX.UI.SidePanel && BX.UI.SidePanel.Wrapper && BX.UI.SidePanel.Wrapper.reloadGridOnParentPage) {
                        BX.UI.SidePanel.Wrapper.reloadGridOnParentPage();
                    }
                    // instance.send();
                });
            }
        });
    })()

</script>

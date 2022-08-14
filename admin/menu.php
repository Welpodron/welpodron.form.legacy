<?

return [
    'parent_menu' => 'global_menu_welpodron', // раздел, где выводить пункт меню
    'text' => 'Настройка параметров модуля welpodron.form',
    'icon' => 'custom_menu_icon', // имя класса для вывода иконки
    // Подпункты
    'items' => [
        ['text' => 'Схемы форм', 'url' => 'welpodron.form_schemas.php?lang=' . LANGUAGE_ID],
        ['text' => 'Заявки', 'url' => 'welpodron.form_requests.php?lang=' . LANGUAGE_ID],
    ],

];

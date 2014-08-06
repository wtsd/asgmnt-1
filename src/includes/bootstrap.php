<?php

/* Отображать все возникающие ошибки (на продакшне можно выключить) */
error_reporting(E_ALL);
ini_set('display_errors', '1');

/* Корневая директория и более короткое название разделителя директорий */
define('ROOT', dirname(dirname(__FILE__)));
define('DS', DIRECTORY_SEPARATOR);

/* Все сообщения хранятся в одном файле, чтобы можно было их удобно редактировать */
require(ROOT . DS . 'includes' . DS . 'language.php');

/* Включение конфигурационного файла: если его нет, вывести сообщение об ошибке */
if ((@include ROOT . DS . 'config' . DS . 'main.php') != 1) {
    die($lang['err_noconfig']);
}

/* Подключение библиотеки шифрования и библиотеки пользовательских функций */
require(ROOT . DS . 'includes' . DS . 'encryption.php');
require(ROOT . DS . 'includes' . DS . 'library.php');

<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ROOT', dirname(dirname(__FILE__)));
define('DS', DIRECTORY_SEPARATOR);

if ((@include ROOT . DS . 'config' . DS . 'main.php') != 1) {
    die('Для корректной работы требуется установка файла конфигурации config/main.php по шаблону config/main.php.dist');
}

require(ROOT . DS . 'includes' . DS . 'library.php');

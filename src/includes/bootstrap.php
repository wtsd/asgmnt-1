<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ROOT', dirname(dirname(__FILE__)));
define('DS', DIRECTORY_SEPARATOR);

require(ROOT . DS . 'includes' . DS . 'language.php');
if ((@include ROOT . DS . 'config' . DS . 'main.php') != 1) {
    die($lang['err_noconfig']);
}
require(ROOT . DS . 'includes' . DS . 'encryption.php');
require(ROOT . DS . 'includes' . DS . 'library.php');

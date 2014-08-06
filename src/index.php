<?php

/**
 * Подключение файла для подгрузки необходимых библиотек, указания
 * опций для работы приложения и объявления основных констант.
 *
 * @package Assignment1
 * @author Vlad Gafurov <warlockfx@gmail.com>
 * @version 0.1
 * 
 */
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'bootstrap.php');

/**
 * Точка входа, начало роутинга и запуск контроллера.
 *
 */
doRouting();
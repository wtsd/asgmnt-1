<?php

function doRouting()
{
    global $lang;
    $title = $lang['title'];
    $isAuthorized = isAuthorized();
    $userName = getUsername();
    $role = getRole();
    $account = getAccount();
    $content = '';

    include(ROOT . DS . 'templates' . DS . 'index.php');
}

function parseUrl($index)
{
    $url = $_SERVER['REQUEST_URI'];
    $urlArray = explode('/', $url);
    array_shift($urlArray);
    if ((count($urlArray) > 0) && ($index !== null && isset($urlArray[$index]))) {
        return $urlArray[$index];
    } else {
        return null;
    }
}

function prepareTemplate($tpl, $values = null)
{
    ob_start();
    include(ROOT . DS . 'templates' . DS . $tpl . '.php');
    $result = ob_get_contents();
    ob_end_clean();
    return $result;
}

/* Database */
function connectDb($databaseId = 0)
{
    global $config, $lang;

    if ($config['db'][$databaseId]['handler'] === null) {
        $config['db'][$databaseId]['handler'] = mysqli_connect($config['db'][$databaseId]['host'], $config['db'][$databaseId]['user'], $config['db'][$databaseId]['pass']);
        if (!$config['db'][$databaseId]['handler']) {
            die(sprintf($lang['database_error'], $config['db'][$databaseId]['handler']->errno, $config['db'][$databaseId]['handler']->error));
        }
        mysqli_select_db($config['db'][$databaseId]['handler'], $config['db'][$databaseId]['dbname']);
    }
}

function selectDb($sql, $placeholders = null, $databaseId = 0)
{
}

function insertDb($sql, $placeholders = null, $databaseId = 0)
{
}

function updateDb($sql, $placeholders = null, $databaseId = 0)
{
}

/* Cookies */
function setCookies($key, $value)
{
    setcookie($key, $value, time() + 3600);
}

function getCookies($key)
{
    if (isset($_COOKIE[$key])) {
        return $_COOKIE[$key];
    } else {
        return null;
    }
}

/* Account management */
function getRole()
{
	return 'client';
}

function doSignup($values)
{
}

function doAuthorize($values)
{
}

function doLogout($values)
{
}

function frmLogin()
{
}

function frmSignup()
{
}

function getUsername()
{
	return 'Saul Goodman';
}

function isAuthorized()
{
	return false;
}


function getAccount()
{
	return '0.0';
}

function addAccount($executorId, $sum)
{
}

/* Order management */
function frmOrder()
{
}

function saveOrder($values)
{
}

function listOrders($values)
{
}

function seizeOrder($values)
{
}

function getOrderInfo($id)
{
}

function countComission($price)
{
}

function saveComission($amount, $order_id, $user_id)
{
}


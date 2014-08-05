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

	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (parseUrl(0) == 'ajax') {
        	if (isAuthorized()) {
        		if ($role == 'client') {
        			$allowedControllers = array('frmOrder', 'saveOrder', 'myOrders', 'doLogout');
        		} else {
        			$allowedControllers = array('seizeOrder', 'listOrders', 'doLogout');
        		}
        	} else {
				$allowedControllers = array('frmSignup', 'frmLogin', 'doAuthorize', 'doSignup');
        	}
        	if (in_array($_POST['controller'], $allowedControllers)) {
                $response = $_POST['controller']($_POST['values']);
                die(json_encode($response));
            }
        }
    }

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
	global $config;
    connectDb($databaseId);
    
    if (is_array($placeholders)) {
        for ($i = 0; $i < count($placeholders); $i++) {
            $placeholders[$i] = mysqli_real_escape_string($config['db'][$databaseId]['handler'], $placeholders[$i]);
        }
        $preparedSql = vsprintf($sql, $placeholders);
    } else {
        $preparedSql = $sql;
    }

    $resource = mysqli_query($config['db'][$databaseId]['handler'], $preparedSql);
    $result = array('rows' => array(), 'count' => 0);
    if ($resource) {
        while ($row = mysqli_fetch_assoc($resource)) {
            $result['rows'][] = $row;
            $result['count']++;
        }
    }
    return $result;
}

function insertDb($sql, $placeholders = null, $databaseId = 0)
{
	global $config;
    connectDb($databaseId);

    if (is_array($placeholders)) {
        for ($i = 0; $i < count($placeholders); $i++) {
            $placeholders[$i] = mysqli_real_escape_string($config['db'][$databaseId]['handler'], $placeholders[$i]);
        }
        $preparedSql = vsprintf($sql, $placeholders);
    } else {
        $preparedSql = $sql;
    }

    $resource = mysqli_multi_query($config['db'][$databaseId]['handler'], $preparedSql);
    if ($resource) {
        return mysqli_insert_id($config['db'][$databaseId]['handler']);
    } else {
        return 0;
    }
}

function updateDb($sql, $placeholders = null, $databaseId = 0)
{
	return insertDb($sql, $placeholders, $databaseId);
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
	return '';
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
    return array('status' => 'ok', 'html' => prepareTemplate('login'));
}

function frmSignup()
{
    return array('status' => 'ok', 'html' => prepareTemplate('signup'));
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
	if (isAuthorized() && getRole() == 'client') {

	}
}

function saveOrder($values)
{
	if (isAuthorized() && getRole() == 'client') {

	}
}

function listOrders($values)
{
	if (isAuthorized() && getRole() == 'executor') {

	}
}

function seizeOrder($values)
{
	if (isAuthorized() && getRole() == 'executor') {

	}
}

function getOrderInfo($id)
{
}

function countComission($price)
{
	global $config;

    return $price * ($config['comission'] / 100);
}

function saveComission($amount, $order_id, $user_id)
{
}


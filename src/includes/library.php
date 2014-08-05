<?php

define('SIGNUP_DB', 0);
define('SQL_SIGNUP_BY_ID', "SELECT * FROM `tblSignup` WHERE `id` = %d LIMIT 1");
define('SQL_SIGNUP_BY_LOGIN', "SELECT * FROM `tblSignup` WHERE `login` = '%s' LIMIT 1");
define('SQL_INSERT_SIGNUP', "INSERT INTO `tblSignup` SET `login` = '%s', `pass` = '%s', `role` = '%s'");

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

function setAuthorizedCookies($uid, $pass)
{
    setCookies('uid', base64_encode($uid));
    setCookies('token', base64_encode(substr($pass, ($uid % 10))));   
}

function getCookies($key)
{
    if (isset($_COOKIE[$key])) {
        return $_COOKIE[$key];
    } else {
        return null;
    }
}

function getCookieDecrypted()
{
    $uid = base64_decode(getCookies('uid'));
    $token = base64_decode(getCookies('token'));

    $sql = SQL_SIGNUP_BY_ID;
    $placeholders = array($uid);
    $rows = selectDb($sql, $placeholders, SIGNUP_DB);
    if ($rows['count'] == 1) {
        if (substr($rows['rows'][0]['pass'], ($rows['rows'][0]['id'] % 10)) == $token) {
            return $rows['rows'][0];
        }
    }
}


/* Account management */
function getRole()
{
    $row = getCookieDecrypted();
    if (isset($row['role'])) {
        return $row['role'];
    }

    return null;
}

function doSignup($values)
{
    global $lang;
    // @todo: Validate input

    // Check, if user is unique
    $placeholdersUnique = array($values['user']);
    $resultUnique = selectDb(SQL_SIGNUP_BY_LOGIN, $placeholdersUnique, SIGNUP_DB);

    if ($resultUnique['count'] == 0) {
        $hashedPass = hashPassword($values['pass']);
        $placeholdersInsert = array($values['user'], $hashedPass, $values['role']);
        $newId = insertDb(SQL_INSERT_SIGNUP, $placeholdersInsert, SIGNUP_DB);

        if ((int)$newId > 0) {
        	setAuthorizedCookies($newId, $hashedPass);

            $result = array();

            $result['username'] = $values['user'];
            $result['role'] = $values['role'];
            $result['role_label'] = $lang['groups'][$values['role']];
            $result['account'] = '0.0';
            if ($result['role'] == 'client') {
                $result['menu'] = prepareTemplate('menu-client');
            } else {
                $result['menu'] = prepareTemplate('menu-exec');
            }
            $result['menu'] = prepareTemplate('menu-exec');
            $result['html'] = $lang['reg_success'];
            $result['status'] = 'ok';

        } else {
            $result['username'] = '';
            $result['role'] = '';
            $result['account'] = '';
            $result['menu'] = prepareTemplate('menu-unauth');
            $result['html'] = $lang['reg_err'];
            $result['status'] = 'error';
        }
    } else {
        $result['username'] = '';
        $result['role'] = '';
        $result['account'] = '';
        $result['menu'] = prepareTemplate('menu-unauth');
        $result['html'] = $lang['reg_taken'];
        $result['status'] = 'error';
    }
    return $result;

}

function doAuthorize($values)
{
	global $lang;

    $placeholders = array($values['user']);
    $res = selectDb(SQL_SIGNUP_BY_LOGIN, $placeholders, SIGNUP_DB);

    $result = array();
    if ($res['count'] == 1) {
        $row = $res['rows'][0];
        if (validatePassword($values['pass'], $row['pass'])) {
        	setAuthorizedCookies($row['id'], $row['pass']);

            $result['username'] = $row['login'];
            $result['role'] = $row['role'];
            $result['role_label'] = $lang['groups'][$row['role']];
            $result['account'] = $row['account'];
            if ($row['role'] == 'client') {
                $result['menu'] = prepareTemplate('menu-client');
            } else {
                $result['menu'] = prepareTemplate('menu-exec');
            }
            $result['html'] = $lang['msg_welcome'];
            $result['status'] = 'ok';
        } else {
            $result['username'] = '';
            $result['role'] = '';
            $result['account'] = '';
            $result['menu'] = prepareTemplate('menu-unauth');
            $result['html'] = $lang['msg_err'];
            $result['status'] = 'error';
        }
    }

    return $result;
}

function doLogout($values)
{
    global $lang;

    setCookies('uid', 0);
    setCookies('token', '');
    unset($_COOKIE);
    // @todo: Return anonymous values
    $result = array();

    $result['username'] = '';
    $result['role'] = '';
    $result['account'] = '';
    $result['menu'] = prepareTemplate('menu-unauth');
    $result['html'] = $lang['msg_logout'];
    $result['status'] = 'ok';

    return $result;
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
    $row = getCookieDecrypted();
    if (isset($row['role'])) {
        return $row['login'];
    }

    return null;
}

function isAuthorized()
{
    $row = getCookieDecrypted();
    if (isset($row['id'])) {
        return true;
    }

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


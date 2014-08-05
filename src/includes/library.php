<?php

define('SIGNUP_DB', 0);
define('SQL_SIGNUP_BY_ID', "SELECT * FROM `tblSignup` WHERE `id` = %d LIMIT 1");
define('SQL_SIGNUP_BY_LOGIN', "SELECT * FROM `tblSignup` WHERE `login` = '%s' LIMIT 1");
define('SQL_INSERT_SIGNUP', "INSERT INTO `tblSignup` SET `login` = '%s', `pass` = '%s', `role` = '%s'");
define('SQL_ADD_ACCOUNT', "UPDATE `tblSignup` SET `account` = `account` + %f WHERE `id` = %d LIMIT 1");

define('ORDER_DB', 0);
define('SQL_INSERT_ORDER', "INSERT INTO `tblOrder` SET `caption` = '%s', `descr` = '%s', `price` = '%d', `uid` = %d, `status` = 1, `cdate` = Now()");
define('SQL_LIST_ORDERS', "SELECT * FROM `tblOrder` WHERE `status` = 1 ORDER BY `cdate` LIMIT 1000");
define('SQL_SEIZE_ORDER', "UPDATE `tblOrder` SET `status` = 2, `exec_id` = %d WHERE `id` = %d");
define('SQL_ORDER_INFO', "SELECT * FROM `tblOrder` WHERE `id` = %d LIMIT 1");

define('COMISSION_DB', 0);
define('SQL_INSERT_COMISSION', "INSERT INTO `tblComission` SET `order_id` = %d, `amount` = %f, `user_id` = %d, `cdate` = Now()");


/* Basic routing */
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
                    $clientControllers = array('frmOrder', 'saveOrder', 'myOrders', 'doLogout');
                    $allowedControllers = $clientControllers;
                } else {
                    $executorControllers = array('seizeOrder', 'listOrders', 'doLogout');
                    $allowedControllers = $executorControllers;
                }
            } else {
                $notAuthorizedControllers = array('frmSignup', 'frmLogin', 'doAuthorize', 'doSignup');
                $allowedControllers = $notAuthorizedControllers;
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
    $sqlUnique = SQL_SIGNUP_BY_LOGIN;
    $placeholdersUnique = array($values['user']);
    $resultUnique = selectDb($sqlUnique, $placeholdersUnique, SIGNUP_DB);

    if ($resultUnique['count'] == 0) {
        $sqlInsert = SQL_INSERT_SIGNUP;
        $hashedPass = hashPassword($values['pass']);
        $placeholdersInsert = array($values['user'], $hashedPass, $values['role']);
        $newId = insertDb($sqlInsert, $placeholdersInsert, SIGNUP_DB);

        if ((int)$newId > 0) {
            setAuthorizedCookies($newId, $hashedPass);

            $result = array();

            $result['username'] = $values['user'];
            $result['role'] = $values['role'];
            $result['role_label'] = $lang['groups'][$values['role']];
            $result['account'] = '0.0';
            if ($result['role'] == 'client') {
                $result['menu'] = prepareTemplate('menu-client');
                $result['content'] = prepareTemplate('frm-order');
            } else {
                $result['menu'] = prepareTemplate('menu-exec');
                $result['content'] = prepareTemplate('lst-orders');
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

    $sql = SQL_SIGNUP_BY_LOGIN;
    $placeholders = array($values['user']);
    $result = array();

    $res = selectDb($sql, $placeholders, SIGNUP_DB);
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
                $result['content'] = prepareTemplate('frm-order');
            } else {
                $result['menu'] = prepareTemplate('menu-exec');
                $result['content'] = prepareTemplate('lst-orders');
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
    $row = getCookieDecrypted();
    if (isset($row['account'])) {
        return $row['account'];
    }

    return '';
}

function addAccount($executorId, $sum)
{
    $sql = SQL_ADD_ACCOUNT;
    $placeholders = array($sum, $executorId);
    updateDb($sql, $placeholders, SIGNUP_DB);
}

/* Order management */
function frmOrder()
{
    if (isAuthorized() && getRole() == 'client') {
        $result = array();
        $result['html'] = prepareTemplate('frm-order');
        $result['status'] = 'ok';
        return $result;
    }
}

function saveOrder($values)
{
    global $lang;
    if (isAuthorized() && getRole() == 'client') {
        $row = getCookieDecrypted();
        if (isset($row['id'])) {
            $uid = $row['id'];
        }

        $sqlInsert = SQL_INSERT_ORDER;
        $placeholdersInsert = array(htmlspecialchars($values['caption'], ENT_QUOTES, 'utf-8'), htmlspecialchars($values['descr'], ENT_QUOTES, 'utf-8'), $values['price'], $uid);
        $newId = insertDb($sqlInsert, $placeholdersInsert, ORDER_DB);
        if ($newId > 0) {
            $result = array();
            $result['status'] = 'ok';
            $result['id'] = $newId;
            $result['msg'] = sprintf($lang['order_saved'], $newId);

            return $result;
        }
    }
}

function listOrders($values)
{
    global $lang;
    // @todo: Pagination
    if (isAuthorized() && getRole() == 'executor') {
        $sql = SQL_LIST_ORDERS;
        $rows = selectDb($sql, null, ORDER_DB);
        if ($rows['count'] > 0) {
            $result = array();
            $result['status'] = 'ok';
            $result['orders'] = $rows['rows'];
            $result['count'] = $rows['count'];
            
        } else {
            $result['msg'] = $lang['no_orders'];
            $result['status'] = 'void';
        }
        return $result;
    }
}

function seizeOrder($values)
{
    if (isAuthorized() && getRole() == 'executor') {
        $order_id = $values['order_id'];
        $order = getOrderInfo($order_id);
        $row = getCookieDecrypted();

        if ($order['status'] == 1 && $order['exec_id'] == 0) {
            // @todo: Check order status: if 0 and no executor, change its status
            $sql = SQL_SEIZE_ORDER;
            $placeholders = array($row['id'], intval($order_id));
            updateDb($sql, $placeholders, ORDER_DB);

            $comission = countComission($order['price']);
            saveComission($comission);
            
            addAccount($row['id'], $order['price'] - $comission);

            $row = getCookieDecrypted();
            $result = array();
            $result['account'] = $row['account'];
            $result['status'] = 'ok';
            return $result;
        
        } else {
            // This order was already taken
        }
    }
}


function getOrderInfo($id)
{
    $sql = SQL_ORDER_INFO;
    $placeholders = array(intval($id));
    $rows = selectDb($sql, $placeholders, ORDER_DB);
    if ($rows['count'] == 1) {
        return $rows['rows'][0];
    } else {
        return null;
    }
}

function countComission($price)
{
    global $config;

    return $price * ($config['comission'] / 100);
}

function saveComission($amount, $order_id, $user_id)
{
    $sql = SQL_INSERT_COMISSION;
    $placeholders = array($order_id, $amount, $user_id);
    insertDb($sql, $placeholders, COMISSION_DB);
}


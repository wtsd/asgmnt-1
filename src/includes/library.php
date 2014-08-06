<?php

/**
 * library.php — файл со всеми необходимыми для работы функциями,
 * используемый для работы всей системы.
 *
 * Для корректной работы приложения необходимо расширение mysqli.
 *
 * @package Assignment1
 * @author Vlad Gafurov <warlockfx@gmail.com>
 * @version 0.1
 *
 */

/**
 * Объявление всех запросов и указание базы данных, в которых нужно 
 * выполнять эти запросы.
 * 
 * Об этом было отдельно сказано в задании: «предусмотреть вариант,
 * когда каждая таблица располагается в отдельной базе данных».
 *
 */
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


/**
 * Контроллер приложения
 *
 * Проверяет пользовательский ввод и ссылки, проверяет разрешения
 * и запускает соответствующие функции.
 *
 * Существует три вида пользователей: анонимы, заказчики и 
 * исполнители. Для каждого вида пользователей разрешены свои
 * функции, которые вызываются через ajax-запрос методом post.
 *
 * По умолчанию запускается общий шаблон index.php.
 *
 */
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

/**
 * Парсер ссылки
 *
 * Для того, чтобы можно было позже добавить вариант приложения без
 * ajax`а, удобно будет заходить по прямой ссылке. Ссылка разбивается
 * по символу «/» и возвращается запрошенная её часть.
 *
 * @param int $index    Номер переменной, которую нужно вернуть.
 *
 * @return string|null  Запрошенная часть ссылки.
 */
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

/**
 * Подготовка шаблона к выводу
 * 
 * Подгрузка файла шаблона и передача ему свойств из массива
 * $values. Вместо немедленного вывода, осуществляется буферизация
 * и присвоение вывода переменной для более удобной работы.
 *
 * @param string $tpl           Название файла с шаблоном.
 * @param array|null $values    Аргументы для шаблона
 *
 * @return string               Финальный вариант шаблона с данными.
 */
function prepareTemplate($tpl, $values = null)
{
    ob_start();
    include(ROOT . DS . 'templates' . DS . $tpl . '.php');
    $result = ob_get_contents();
    ob_end_clean();

    return $result;
}

/* База данных */

/**
 * Подключение к базе данных
 *
 * В силу того, что по заданию, баз данных может быть сколь угодно много,
 * реквизиты для подключения к ним хранятся в конфигурационном файле,
 * а подключение может осуществляться к любой из этих баз.
 *
 * @param int $databaseId   Номер базы данных, к которой нужно подключиться.
 */
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

/**
 * Запрос на выборку из базы данных
 *
 * В целях безопасности, написаны отдельные функции для каждого вида
 * запросов. К тому же, раз невозможно использовать ООП, пришлось
 * отказаться от PDO в пользу mysqli и использовать вместо стандартного
 * биндинга, плейсхолдерами через sprintf.
 *
 * @param string $sql               Шаблон SQL-запроса.
 * @param array|null $placeholders  Массив для подстановки значений по маске в шаблон.
 * @param int $databaseId           Номер базы данных, к которой нужно подключиться.
 *
 * @return array                    Ассоциативный массив с количеством возвращаемых
 *                                  значений и непосредственно с данным.
 */
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

/**
 * Запрос вставки записи в БД
 *
 * Всё то же самое, что и в запросе на выборку, только в конце возвращается ID новой
 * записи.
 *
 * @param string $sql               Шаблон SQL-запроса.
 * @param array|null $placeholders  Массив для подстановки значений по маске в шаблон.
 * @param int $databaseId           Номер базы данных, к которой нужно подключиться.
 *
 * @return int                      Новый ID созданной записи.
 */
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

/**
 * Запрос обновления записи
 *
 * Обёртка для функции insertDb.
 *
 * @param string $sql               Шаблон SQL-запроса.
 * @param array|null $placeholders  Массив для подстановки значений по маске в шаблон.
 * @param int $databaseId           Номер базы данных, к которой нужно подключиться.
 *
 * @return int                      Новый ID созданной записи.
 */
function updateDb($sql, $placeholders = null, $databaseId = 0)
{
    return insertDb($sql, $placeholders, $databaseId);
}

/* Cookies */

/**
 * Установка куки
 *
 * Обёртка для установщика кук с целью последующей установки проверок данных,
 * передаваемых в куки и для создания общих правил хранения (время, домен и т.д.).
 *
 * @param string $key               Ключ куки
 * @param string $value             Значение для ключа
 */
function setCookies($key, $value)
{
    setcookie($key, $value, time() + 3600);
}

/**
 * Получение значения куки
 *
 * Если ключ есть, получить его в куке.
 *
 * @param string $key               Ключ куки
 *
 * @return string|null              Значение по ключу
 */
function getCookies($key)
{
    if (isset($_COOKIE[$key])) {
        return $_COOKIE[$key];
    } else {
        return null;
    }
}

/**
 * Установка кук об авторизации
 *
 * Простой способ защиты примитивным шифрованием уникального идентификатора
 * пользователя и криптованная часть хэша его пароля, чья длина варьируется
 * в зависимости от ID.
 *
 * @param string $uid               ID пользователя
 * @param string $pass              Хэш пароля пользователя
 */
function setAuthorizedCookies($uid, $pass)
{
    setCookies('uid', base64_encode($uid));
    setCookies('token', base64_encode(substr($pass, ($uid % 10))));   
}

/**
 * Получение информации об авторизованном пользователе
 *
 * Дешифровка и проверка валидности данных из куки и получение информации
 * из базы данных, если пользователь авторизован правильно.
 *
 * @return array        Данные о пользователе
 */
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


/* Управление учётными записями */

/**
 * Получение роли авторизованного пользователя
 *
 * Если пользователь авторизован, то возвращается его роль: заказчик
 * или исполнитель.
 *
 * @return string|null            Роль пользователя
 */
function getRole()
{
    $row = getCookieDecrypted();
    if (isset($row['role'])) {
        return $row['role'];
    }

    return null;
}

/**
 * Получение имени пользователя авторизованного пользователя
 *
 * Если пользователь авторизован, то возвращается его логин.
 *
 * @return string|null            Логин пользователя.
 */
function getUsername()
{
    $row = getCookieDecrypted();
    if (isset($row['role'])) {
        return $row['login'];
    }

    return null;
}

/**
 * Состояние авторизованности пользователя
 *
 * Получение информации о состоянии пользователя.
 *
 * @return bool            true для авторизованного, false — для неавторизованного.
 */
function isAuthorized()
{
    $row = getCookieDecrypted();
    if (isset($row['id'])) {
        return true;
    }

    return false;
}

/**
 * Получение информации о счёте
 *
 * Если пользователь авторизован, получается значение на счёте.
 *
 * @return float|string       Количество условных единиц на счету.
 */
function getAccount()
{
    $row = getCookieDecrypted();
    if (isset($row['account'])) {
        return $row['account'];
    }

    return '';
}

/**
 * Добавление денег на счёт
 *
 * Перевод денег на счёт исполнителя.
 *
 * @param int $executorId              ID исполнителя
 * @param float $sum                   Сумма
 */
function addAccount($executorId, $sum)
{
    $sql = SQL_ADD_ACCOUNT;
    $placeholders = array($sum, $executorId);
    updateDb($sql, $placeholders, SIGNUP_DB);
}

/**
 * Процесс регистрации
 *
 * Процедура регистрации: проверка уникальности имени пользователя,
 * добавление информации в БД.
 *
 * @todo: Добавить валидацию имени пользователя и пароля, если
 * необходимо.
 *
 * @param array $values             Передаваемые ajax-запросом данные для регистрации.
 * 
 * @return array                    Значения для последующей упаковки в json
 */
function doSignup($values)
{
    global $lang;

    $placeholdersUnique = array($values['user']);
    $resultUnique = selectDb(SQL_SIGNUP_BY_LOGIN, $placeholdersUnique, SIGNUP_DB);

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

/**
 * Процесс авторизации
 *
 * Хэширование пароля, проверка наличия записи с таким именем пользователя.
 *
 * @param array $values             Передаваемые ajax-запросом данные для авторизации.
 * 
 * @return array                    Значения для последующей упаковки в json
 */
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

/**
 * Процесс выхода из системы
 *
 * Процедура выхода из системы, очистка кук, перерисовка меню и отображение сообщения.
 *
 * @todo: Сделать обнуление данных на странице.
 *
 * @param array $values             Передаваемые ajax-запросом данные для авторизации.
 * 
 * @return array                    Значения для последующей упаковки в json
 */
function doLogout($values)
{
    global $lang;

    setCookies('uid', 0);
    setCookies('token', '');
    unset($_COOKIE);
    $result = array();

    $result['username'] = '';
    $result['role'] = '';
    $result['account'] = '';
    $result['menu'] = prepareTemplate('menu-unauth');
    $result['html'] = $lang['msg_logout'];
    $result['status'] = 'ok';

    return $result;

}

/**
 * Форма логина
 *
 * Сборка шаблона для формы авторизации.
 *
 * @return array                    Значения для последующей упаковки в json
 */
function frmLogin()
{
    return array('status' => 'ok', 'html' => prepareTemplate('login'));
}

/**
 * Форма регистрации
 *
 * Сборка шаблона для формы регистрации.
 *
 * @return array                    Значения для последующей упаковки в json
 */
function frmSignup()
{
    return array('status' => 'ok', 'html' => prepareTemplate('signup'));
}


/* Управление заказами */

/**
 * Форма добавления заказа
 *
 * Сборка шаблона для формы заказа.
 *
 * @return array                    Значения для последующей упаковки в json
 */
function frmOrder()
{
    if (isAuthorized() && getRole() == 'client') {
        $result = array();
        $result['html'] = prepareTemplate('frm-order');
        $result['status'] = 'ok';
        return $result;
    }
}

/**
 * Процесс сохранения запроса
 *
 * Сохранение данных о запросе и пользователе, вывод сообщения.
 *
 * @param array $values             Передаваемые ajax-запросом данные для авторизации.
 * 
 * @return array                    Значения для последующей упаковки в json
 */
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

/**
 * Отображение списка заказов
 *
 * Формирование списка заказов для последующего отображения их в таблице.
 *
 * @todo: Постраничный вывод.
 *
 * @param array $values             Передаваемые ajax-запросом данные для авторизации.
 * 
 * @return array                    Значения для последующей упаковки в json
 */
function listOrders($values)
{
    global $lang;
    // 
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

/**
 * Выполнение заказа
 *
 * Процедура выполнения заказа, перечисления денег на счёт исполнителя.
 *
 * @todo: Вывести сообщение о том, что заказ уже кем-то выполнен.
 *
 * @param array $values             Передаваемые ajax-запросом данные для авторизации.
 * 
 * @return array                    Значения для последующей упаковки в json
 */
function seizeOrder($values)
{
    if (isAuthorized() && getRole() == 'executor') {
        $order_id = $values['order_id'];
        $order = getOrderInfo($order_id);
        $row = getCookieDecrypted();

        if ($order['status'] == 1 && $order['exec_id'] == 0) {
            $placeholders = array($row['id'], intval($order_id));
            updateDb(SQL_SEIZE_ORDER, $placeholders, ORDER_DB);

            $comission = countComission($order['price']);
            saveComission($comission, $order['id'], $row['id']);
            
            addAccount($row['id'], $order['price'] - $comission);

            $row = getCookieDecrypted();
            $result = array();
            $result['account'] = $row['account'];
            $result['status'] = 'ok';
            return $result;
        }
    }
}

/**
 * Получение полной информации о заказе
 *
 * @param int $id             ID заказа
 * 
 * @return null|array         Реквизиты заказа
 */
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

/**
 * Расчёт комиссии
 *
 * Вычисление комиссии, исходя из значения процента, указанного
 * в конфигурационном файле.
 *
 * @param float $price   Стоимость
 * 
 * @return float         Комиссия
 */
function countComission($price)
{
    global $config;

    return $price * ($config['comission'] / 100);
}

/**
 * Сохранение комиссии в базе данных
 *
 * Сохранение комиссии, которая была вычтена из расчёта с исполнителем,
 * в базе данных. Этого не было в задании, но это должно быть важно.
 *
 * @param float $amount     Размер комиссии для сохранения.
 * @param int $order_id     Идентификатор заказа.
 * @param int $user_id      Идентификатор исполнителя.
 */
function saveComission($amount, $order_id, $user_id)
{
    $sql = SQL_INSERT_COMISSION;
    $placeholders = array($order_id, $amount, $user_id);
    insertDb($sql, $placeholders, COMISSION_DB);
}


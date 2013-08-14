<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-8-9
 * Time: 下午1:32
 * To change this template use File | Settings | File Templates.
 */
if(!defined('IN_DEAMON'))exit;

/*--------------------------------------------------主程序------------------------------------*/
require '../Conf/db.inc.php';
require '../DB/engine.class.php';
require '../DB/mssql.class.php';
require '../DB/mysql.class.php';


eval('class DB extends '.strtolower(DB_TYPE).'{}');

$table_servers = 'llw_servers';

$db = new DB;
$db -> connect(DB_HOST.':'.DB_PORT,DB_USER,DB_PWD);
$db -> select_db(DB_NAME);
$servers = $db -> select('*') -> from($table_servers) -> get() -> result_objects();
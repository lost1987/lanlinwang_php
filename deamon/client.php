<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-8-13
 * Time: 上午10:13
 * To change this template use File | Settings | File Templates.
 * 入口
 */
define('IN_DEAMON',1);

set_time_limit(0);
//屏蔽报错,日志
ini_set('display_errors','Off');
ini_set('log_errors','Off');

require 'main.php';
require 'common.php';
require 'inc.php';
require('../Excel/input.class.php');
require('../Excel/security.class.php');
require('../Excel/utf8.class.php');
require('../Lib/cookie.class.php');

$input = new Input();
$cookie = new Cookie();

$method =  !isset($_GET['m'])  ? $input->post('m') : $input->get('m');


if(!empty($method) && in_array($method,$allow['methods'] )){
    require "$method.php";
    exit;
}


?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en" id="vbulletin_html">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<center style="margin-top:20%">
<form action="client.php" method="post">
    <p>兰陵王守护进程管理</p>
   用户名 <input type="text" name="username"  />
    密码<input type="password" name="password" />
    <input type="hidden"  name="m" value="loginvalidate"/>
    <input type="submit" value="登录" />
</form>
</center>
</html>

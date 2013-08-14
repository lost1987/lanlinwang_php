<?php
if(!defined('IN_DEAMON') || !$cookie->userdata('is_login') )exit;
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-8-13
 * Time: 上午11:05
 * To change this template use File | Settings | File Templates.
 */

$res = 0;
$socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
if($socket)$res=1;
@socket_connect($socket,SOCKET_HOST,SOCKET_PORT);
@socket_write($socket,'stop',strlen('stop'));
@socket_close($socket);


echo $res;
<?php
if(!defined('IN_DEAMON'))exit;
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-8-13
 * Time: 上午11:16
 * To change this template use File | Settings | File Templates.
 */
header("Content-Type:text/html;charset=utf-8");
$username  = $input -> post('username');
$password = $input -> post('password');

if(!empty($username) && !empty($password)){
     $md5pwd = md5($password.APPKEY);
     $pwd = $db -> select("passwd") -> from('llw_admin') -> where("admin = '$username'") -> get() -> result_object()-> passwd;
    if(empty($pwd))exit('用户不存在');
     else{
         if($md5pwd == $pwd){
             $cookie->set_userdata('is_login',1);
             header('Location:client.php?m=deamon_manage');
             exit;
         }
         exit('密码错误');
     }
}

exit('请填写用户名和密码');
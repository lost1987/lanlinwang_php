<?php
if(!defined('IN_DEAMON') || !$cookie->userdata('is_login') )exit;
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-8-13
 * Time: 上午11:05
 * To change this template use File | Settings | File Templates.
 */

$isstart = 0;
$socket = @socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
if(@socket_connect($socket,SOCKET_HOST,SOCKET_PORT)){
    $isstart = 1;
    @socket_write($socket,'test',strlen('test'));
    if(@socket_read($socket,1)){
        @socket_close($socket);
    }
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en" id="vbulletin_html">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script type="text/javascript" src = "jquery.js" ></script>
    <script type="text/javascript">
        function startDeamon(){
            $("#msg").html("进程已经启动");
            $("#startbtn").attr('disabled',true);
            $("#stopbtn").attr('disabled',false);
            $.post('client.php?m=deamon','',function(data){
                        if(data.replace(/\s+/g,'') != ''){
                            $("#msg").html('进程已经终止');
                            $("#startbtn").attr('disabled',false);
                            $("#stopbtn").attr('disabled',true);
                        }
            });
        }

        function stopDeamon(){
            $("#stopbtn").attr('disabled',true);
            $.post('client.php?m=stop_deamon','',function(data){
                            if(data.replace(/\s+/g,'') == 0){
                                $("#msg").html('进程终止失败');
                                $("#stopbtn").attr('disabled',false);
                            }else{
                                $("#msg").html('进程已经终止')
                                $("#startbtn").attr('disabled',false);
                            }
              });
        }

        <? if($isstart){?>//如果进程已经启动
            $(function(){
                    isstart();
            })

            function isstart(){
                $("#msg").html("进程已经启动");
                $("#startbtn").attr('disabled',true);
                $("#stopbtn").attr('disabled',false);
            }
        <?}?>

    </script>
</head>
<center style="margin-top:20%">
    <form action="client.php" method="post">
        <p>兰陵王守护进程管理</p>
        <p style="color:red" id="msg"></p>
        <input type="button" value="启动进程" id="startbtn" onclick="startDeamon()"/>
        <input type="button" value="停止进程" id="stopbtn" onclick="stopDeamon()" disabled="true"/>
    </form>
</center>
</html>
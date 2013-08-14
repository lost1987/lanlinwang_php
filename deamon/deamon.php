<?php
if(!defined('IN_DEAMON') || !$cookie->userdata('is_login') )exit;
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Time: 下午3:57
 * Date: 13-8-2
 * To change this template use File | Settings | File Templates.
 * 统计守护进程/面向过程
 * 时间间隔10分钟
 */


define('INTERVAL',10 * 60);//定义统计间隔10分钟
$socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
socket_bind($socket,SOCKET_HOST,SOCKET_PORT);
socket_listen($socket,MAX_CONNECTION);
socket_set_nonblock($socket);//设置非阻塞 否则只会执行一次或者不会执行后面的函数  非阻塞模式必须多路复用
$clients = array($socket);
$time = time();
while(1){
    if(time() - $time >= INTERVAL){
            login_on_line($servers);//统计登录在线
            yuanbaoAtMoment($servers);//统计每一时刻用户拥有的元宝数
            yuanbaoAt72($servers);//统计72小时内登录过的用户的剩余元宝数
            loginIn24_72_168($servers);//统计24,48,72小时内未登陆的人数
            $time = time();
     }
   /* foreach($clients as $client){
         error_log(strval($client));
    }*/
    $read = $clients;
    if(@socket_select($read, $writes=NULL, $execs=NULL, 0) < 1){
        sleep(1);
        continue;
    }

    if(in_array($socket,$read)) {
        $clients[] = $newsock = socket_accept($socket);
        socket_set_nonblock($newsock);
        $signal = socket_read($newsock,4);
        if(!empty($signal)){
            if($signal == 'stop'){
                for($i=0;$i<count($clients) ; $i++){
                        @socket_close($clients[$i]);
                        unset($clients[$i]);
                }
                //exit('统计守护进程已停止');
                exit('-1');
            }else if($signal == 'test'){//测试连接
                socket_write($newsock,'1',1);
               @socket_close($newsock);
                for($i=0;$i<count($clients) ; $i++){
                    if($clients[$i] == $newsock){
                        unset($clients[$i]);
                    }
                }
            }
        }
    }

    sleep(1);

}

/*----------------------------------------------定义统计方法--------------------------------------------*/
function login_on_line($servers){
    global $db;
    $time = $db->timestamp('lastdate');
    foreach($servers as $server){
            $stat = use_database($server,$db);
            if(!$stat) continue;
            $curtime = time();
            $obj = $db -> select(' count(uid) as online_num ')
                                        -> from('user')
                                        -> where(" $curtime - $time < 10*60 ")
                                        ->  get() -> result_object();
            if(empty($obj))continue;
            $online_num = $obj -> online_num;
            $db -> query("insert into statistic (type,param_nums,time) values (1,$online_num,$curtime)");
    }
}

function yuanbaoAtMoment($servers){
     global $db;
    foreach($servers as $server){
        $stat = use_database($server,$db);
        if(!$stat) continue;
        $obj = $db -> select('sum(cz) as yuanbao')->from('user') -> get() -> result_object();
        if(empty($obj))continue;
        $yuanbao = $obj->yuanbao;
        $curtime = time();
        $db -> query("insert into statistic (type,param_nums,time,param_digits1) values (2,0,$curtime,$yuanbao)");
    }
}


function yuanbaoAt72($servers){
     global $db;
    $curtime = time();
    //72小时起始时间
     $starttime = $curtime - 60*60*72;
     foreach($servers as $server){
         $stat= use_database($server,$db);
         if(!$stat) continue;
          //查询72小时之内有登录记录的uid
          $time =  $db -> timestamp('time');
          $yuanbao72 = $db -> select("sum(cz) as yuanbao72") -> from('user')
                                -> where(" uid in (select distinct(uid) as uid from record where $time > $starttime and $time < $curtime and type =  3) ")
                                ->get() ->result_object()->yuanbao72;
         $db -> query("insert into statistic (type,param_nums,time,param_digits1) values (3,0,$curtime,$yuanbao72)");
     }
}

function loginIn24_72_168($servers){
     global $db;
     $curtime = time();
     $timestamp = $db -> timestamp('time');
     foreach($servers as $server){
           $stat = use_database($server,$db);
           if(!$stat) continue;
           //查询总用户人数
           $total_num = $db->select("count(uid) as num") -> from('user') -> get() -> result_object() -> num;

           //24小时未登录的人数
           $start24 = $curtime - 60*60*24;
           $login24_num= $db -> select("count(distinct(uid)) as num") -> from('record') -> where("$timestamp > $start24 and $timestamp < $curtime and type = 3")
                                        ->get() -> result_object() -> num;
           $nologin24_num = $total_num - $login24_num;

         //72小时未登录的人数
         $start72 = $curtime - 60*60*72;
         $login72_num= $db -> select("count(distinct(uid)) as num") -> from('record') -> where("$timestamp > $start72 and $timestamp < $curtime and type = 3")
             ->get() -> result_object() -> num;
         $nologin72_num = $total_num - $login72_num;

         //168小时未登录的人数
         $start168 = $curtime - 60*60*168;
         $login168_num= $db -> select("count(distinct(uid)) as num") -> from('record') -> where("$timestamp > $start168 and $timestamp < $curtime and type = 3")
             ->get() -> result_object() -> num;
         $nologin168_num = $total_num - $login168_num;

         $db -> query("insert into statistic (param_nums,type,time,param_digits1,param_digits2,param_digits3)
                         values (0,4,$curtime,$nologin24_num,$nologin72_num,$nologin168_num)");
      }
}





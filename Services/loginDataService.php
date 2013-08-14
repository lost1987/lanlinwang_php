<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-21
 * Time: 上午11:05
 * To change this template use File | Settings | File Templates.
 * 登录在线
 */
class LoginDataService extends Service{
    function LoginDataService(){
        $this -> table_loginData = 'record';
        $this -> table_user = 'user';
        $this->table_statistic = 'statistic';
        $this -> db =  new DB;
    }

    public function lists($condition){
        $server_ids = $condition -> server_ids;
        $starttime = strtotime($condition->starttime.' 00:00:00');//往前推半小时
        $endtime =  strtotime($condition->endtime.' 23:59:59');
        $timediff = $condition->timediff;

        $servers = $this->getServers($server_ids);
        $list = array();
        foreach($servers as $server){
            $this->connectServerDB($server);

            switch($timediff){
                //所有  统计成每10分钟统计一次
                case 1:
                    //查询登录时间点
                    $starttime = $starttime -  60*30;
                    $timePointCollection = timeTickArrayPoint($starttime,$endtime,60*30);
                    break;
                //24小时
                case 2:
                    $starttime = $starttime -  60*60*24;
                    $timePointCollection = timeTickArrayPoint($starttime,$endtime,60*60*24);
                    break;
            }

            $time = $this -> db -> fromunixtime('time');
            $templist_loginnum = $this -> db -> select("param_nums as loginnum , $time as date")
                -> from($this->table_statistic)->where(" time > $starttime and time < $endtime  and type =1")
                ->get()-> result_objects();

            //按时间合并数据
            $times = 0;
            foreach($timePointCollection as $timepoint){
                    $obj = new stdClass();
                    if($times == 0){
                        $times++;
                        continue;
                    }

                    $loginnum = 0;
                    $innertimes = 0;
                    $maxonlinelist = array();
                    foreach($templist_loginnum as $login){
                            if(strtotime($login->date) > $timePointCollection[$times-1] && strtotime($login->date) <= $timepoint){
                                   $loginnum += $login->loginnum;
                                   $maxonlinelist[] = $login->loginnum;
                                   $innertimes++;
                            }
                    }

                    for($i=0; $i< count($maxonlinelist) ; $i++){
                            for($k=count($maxonlinelist)-1; $k > $i ; $k--){
                                        if($maxonlinelist[$k] > $maxonlinelist[$k-1] ){
                                             $temp = $maxonlinelist[$k];
                                             $maxonlinelist[$k] = $maxonlinelist[$k-1];
                                             $maxonlinelist[$k-1] = $temp;
                                        }
                            }
                    }
                    $obj->maxonline = count($maxonlinelist) > 0  ? $maxonlinelist[0] : 0;

                    if($timediff==1){
                        $obj->date = date('Y-m-d H:i',$timePointCollection[$times-1]);
                        $obj->loginnum = $innertimes!=0 ?  ($loginnum/$innertimes) >> 0 : 0;
                    }
                    else{
                        $obj->date = date('Y-m-d',$timePointCollection[$times-1]);
                        $obj->loginnum = $loginnum;
                    }
                    $list[] = $obj;
                    $times++;
            }

        }

        if($timediff == 1){//因flex端无法识别 YYYY-MM-DD HH:NN:SS的格式所以这里做下处理
            foreach($list as &$obj){
                $dateCollection = explode(' ',$obj->date);
                $date = explode('-',$dateCollection[0]);
                $time = explode(':',$dateCollection[1]);
                $obj->date = implode('|',array($date[0],$date[1],$date[2],$time[0],$time[1],00));
            }
        }
        return $list;
    }

}

?>
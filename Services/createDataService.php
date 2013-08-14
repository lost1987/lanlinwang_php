<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-20
 * Time: 下午1:53
 * To change this template use File | Settings | File Templates.
 * 创建注册
 */
class CreateDataService extends Service
{
    function CreateDataService(){
        $this -> table_createData = 'record';
        $this -> db =  new DB;
    }

    public function lists($condition){
        $server_ids = $condition -> server_ids;
        $starttime = $condition->starttime.' 00:00:00';
        $endtime = $condition->endtime.' 23:59:59';
        $timediff = $condition->timediff;

        $date = $this->db->cast('time');
        $timecondition = " $date >= '$starttime' and $date <= '$endtime' ";

        $servers = $this->getServers($server_ids);
        $list = array();
        foreach($servers as $server){
               $this->connectServerDB($server);

                switch($timediff){
                            //所有  统计成每10分钟统计一次
                            case 1:
                                           //时间间隔数组
                                            $timePointCollection = timeTickArrayPoint($starttime,$endtime,60*10);

                                            //查询注册
                                            $templist_register = $this -> db -> select("count(uid) as registernum,concat(left(time,15),0) as date")
                                                -> from("$this->table_createData")
                                                -> where("type = 1 and $timecondition")
                                                -> group_by("concat(left(time,15),0)")
                                                -> order_by("left(time,15) asc")
                                                -> get()
                                                -> result_objects();

                                            //查询创建
                                            $templist_create = $this -> db -> select("count(uid) as createnum,concat(left(time,15),0) as date")
                                                -> from("$this->table_createData")
                                                -> where("type = 2 and $timecondition")
                                                -> group_by("concat(left(time,15),0)")
                                                -> order_by("left(time,15) asc")
                                                -> get()
                                                -> result_objects();

                                              break;
                            //24小时
                            case 2:
                                            $timePointCollection = timeTickArrayPoint($starttime,$endtime,60*60*24);

                                            //查询注册
                                            $templist_register = $this -> db -> select("count(uid) as registernum,left(time,10) as date")
                                            -> from("$this->table_createData")
                                            -> where("type = 1 and $timecondition")
                                            -> group_by("left(time,10)")
                                            -> order_by("left(time,10) asc")
                                            -> get()
                                            -> result_objects();


                                            //查询创建
                                            $templist_create = $this -> db -> select("count(uid) as createnum,left(time,10) as date")
                                                -> from("$this->table_createData")
                                                -> where("type = 2 and $timecondition")
                                                -> group_by("left(time,10)")
                                                -> order_by("left(time,10) asc")
                                                -> get()
                                                -> result_objects();
                                            break;
                }

               //统计所有时间段
               foreach($timePointCollection as $timepoint){

                   $obj = new stdClass();
                   $is_have_timepoint_createnums = FALSE;
                   $is_have_timepoint_registernums = FALSE;

                   foreach($templist_create as $create){
                                $create_timepoint = strtotime($create->date);
                                if($timepoint == $create_timepoint){
                                    $is_have_timepoint_createnums = TRUE;
                                    $obj->createnum=$create->createnum;
                                    break;
                                }
                    }

                   foreach($templist_register as $register){
                           $register_timepoint = strtotime($register->date);
                           if($timepoint == $register_timepoint){
                               $is_have_timepoint_registernums = TRUE;
                               $obj->registernum=$register->registernum;
                               break;
                           }
                   }

                   if(!$is_have_timepoint_createnums)
                       $obj->createnum = 0;
                   if(!$is_have_timepoint_registernums){
                       $obj->registernum = 0;
                   }

                   switch($timediff){
                       case 1:
                                    $obj->date = date('Y-m-d H:i',$timepoint);
                                    break;
                       case 2:
                                    $obj->date = date('Y-m-d',$timepoint);
                                    break;
                   }
                    $list[] = $obj;
                   //error_log('time:'.$obj->date.'|register:'.$obj->registernum.'|create:'.$obj->createnum);
               }

               //按时间合并数据

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

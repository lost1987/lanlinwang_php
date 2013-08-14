<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-21
 * Time: 上午11:34
 * To change this template use File | Settings | File Templates.
 */
class RechargeDataService extends Service
{
    function RechargeDataService(){
        $this -> table_rechargeData = 'record';
        $this -> db = new DB;
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

                    //查询充值人数
                    $templist_rechargeperson = $this -> db -> select("count(uid) as rechargeperson,concat(left(time,15),0) as date")
                        -> from("$this->table_rechargeData")
                        -> where("type = 0 and $timecondition")
                        -> group_by("concat(left(time,15),0)")
                        -> order_by("left(time,15) asc")
                        -> get()
                        -> result_objects();

                    break;
                //24小时
                case 2:
                    $timePointCollection = timeTickArrayPoint($starttime,$endtime,60*60*24);

                    //查询充值人数
                    $templist_rechargeperson = $this -> db -> select("count(uid) as rechargeperson,left(time,10) as date")
                        -> from("$this->table_rechargeData")
                        -> where("type = 1 and $timecondition")
                        -> group_by("left(time,10)")
                        -> order_by("left(time,10) asc")
                        -> get()
                        -> result_objects();

                    break;
            }

            //统计所有时间段  //按时间合并数据
            foreach($timePointCollection as $timepoint){

                $obj = new stdClass();
                foreach($templist_rechargeperson as $rechargeperson){
                        //把时间点相等的人数取出来 然后再判断时间点之前无充值而在时间点的充值的人数
                        $time = strtotime($rechargeperson->date);
                        if($time == $timepoint){
                            $obj->rechargeperson = $rechargeperson -> rechargeperson;
                            break;
                        }else{
                            $obj->rechargeperson = 0;
                        }
                }

                //然后再判断时间点之前无充值而在时间点的充值的人数
                switch($timediff){
                    case 1:
                                    $curtime = $this->db->timestamp('left(time,15)');
                                     break;
                    case 2:
                                    $curtime = $this->db->timestamp('left(time,10)');
                                     break;
                }

                $pasttime = $this -> db -> timestamp('time');
                $newrechargeperson = $this -> db -> select ("count(uid) as newrechargeperson")
                    -> from($this -> table_rechargeData)
                    -> where(" type = 0 and $curtime = $timepoint and uid not in (select uid from $this->table_rechargeData where type=0 and $pasttime < $timepoint)")
                    -> get()->result_object()->newrechargeperson;

                $obj -> newrechargeperson = $newrechargeperson;
                $obj -> date = date('Y-m-d H:i',$timepoint);

                $list[] = $obj;
                //error_log('time:'.$obj->date.'|register:'.$obj->registernum.'|create:'.$obj->createnum);
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

<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-26
 * Time: 上午10:34
 * To change this template use File | Settings | File Templates.
 * 玩家留存率
 */
class UserStayDataService extends Service
{
    function UserStayDataService(){
        $this -> table_userStay = 'record';
        $this -> db = new DB;
        $this -> days = array('1','2','3','4','5','6','7','14','30');
    }

    public function lists($page,$condition){
        $server_ids = $condition -> server_ids;
        $starttime = $condition->starttime;
        $endtime = $condition->endtime;


        $list = array();
        $servers = $this->getServers($server_ids);

        //计算时间点
        $_timePoints = timeTickArrayPoint($starttime,$endtime,60*60*24);
        $timeColumn = $this->db->timestamp('time');

        //分页
        $cur =0 ;
        $timePoints = array();
        foreach($_timePoints as $timepoint){
            if($cur >= $page->start && $cur < ($page->limit/count($servers))>>0 )
                $timePoints [] = $timepoint;
            $cur++;
        }

        foreach($servers  as $server){
                $this->connectServerDB($server);
                foreach($timePoints as $timepoint){
                         $obj = new stdClass();
                         $timeend = $timepoint + 60*60*23 + 59*60;
                         $registers  = $this->db -> select('uid') -> from($this->table_userStay)
                                 -> where(" $timeColumn > $timepoint and $timeColumn < $timeend and type = 1")
                                 -> get() -> result_objects();
                          $register_uids = array();
                          foreach($registers as $reg){
                              $register_uids[] = $reg->uid;
                          }
                         $register_uids = implode(',',$register_uids);
                         $obj -> createnum = count($registers);
                         if(count($registers) == 0){
                                foreach($this -> days as $day){
                                     $obj->{'day'.$day.'percent'} = 'N/A';
                                }
                         }else{
                             foreach($this->days as $day){
                                     $start_time_point =  $timepoint + ($day-1) * 60*60*24;
                                     $temp_time_point  = $timepoint + $day*60*60*24;
                                      //取时间内的有注册记录的登录过的人数
                                     $loginnum = $this->db->select('count(distinct(uid)) as num')
                                                        -> from($this->table_userStay)
                                                        -> where("$timeColumn > $start_time_point and $timeColumn < $temp_time_point and type = 3
                                               and uid in ($register_uids) ")
                                                        -> get() -> result_object() -> num;
                                 $obj->{'day'.$day.'percent'} = $obj->createnum==0||$loginnum==0 ? 'N/A' : $loginnum.'('.number_format($loginnum/$obj->createnum,4) * 100 . '%)';
                             }
                         }
                         $obj -> date =  date('Y-m-d',$timepoint);
                         $obj -> server = $server->name;
                         $list [] = $obj;
                }
        }

        return $list;
    }

    public function num_rows($condition){
        $server_ids = $condition -> server_ids;
        $servers = $this->getServers($server_ids);
        $starttime = $condition->starttime;
        $endtime = $condition->endtime;

        $_timePoints = timeTickArrayPoint($starttime,$endtime,60*60*24) ;
        return count($_timePoints) * count($servers);
    }

    public function total($condition){
        $server_ids = $condition -> server_ids;
        $starttime = $condition->starttime;
        $endtime = $condition->endtime;


        $list = array();
        $servers = $this->getServers($server_ids);

        //计算时间点
        $timePoints = timeTickArrayPoint($starttime,$endtime,60*60*24);
        $timeColumn = $this->db->timestamp('time');


        $obj = new stdClass();
        foreach($servers  as $server){
            $this->connectServerDB($server);
            foreach($timePoints as $timepoint){
                $timeend = $timepoint + 60*60*23 + 59*60;
                $registers  = $this->db -> select('uid') -> from($this->table_userStay)
                    -> where(" $timeColumn > $timepoint and $timeColumn < $timeend and type = 1")
                    -> get() -> result_objects();
                $register_uids = array();
                foreach($registers as $reg){
                    $register_uids[] = $reg->uid;
                }
                $register_uids = implode(',',$register_uids);
                $obj -> createnum += count($registers);
                if(count($registers) ==0 ){
                    foreach($this->days as $day){
                          $obj->{'day'.$day.'loginnum'} += 0;
                    }
                }else{
                    foreach($this->days as $day){
                        $start_time_point =  $timepoint + ($day-1) * 60*60*24;
                        $temp_time_point  = $timepoint + $day*60*60*24;
                        //取时间内的有注册记录的登录过的人数
                        $loginnum = $this->db->select('count(distinct(uid)) as num')
                            -> from($this->table_userStay)
                            -> where("$timeColumn > $start_time_point and $timeColumn < $temp_time_point and type = 3
                                               and uid in ($register_uids) ")
                            -> get() -> result_object() -> num;
                        $obj->{'day'.$day.'loginnum'} += $loginnum;
                    }
                }
                $list [] = $obj;
            }
        }

        foreach($this->days as $day){
            $loginnum = $obj->{'day'.$day.'loginnum'};
            $obj->{'day'.$day.'percent'} = $obj->createnum==0||$loginnum==0 ? 'N/A' : $loginnum.'('.number_format($loginnum/$obj->createnum,4) * 100 . '%)';
        }

        return $obj;
    }
}

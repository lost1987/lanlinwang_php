<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-21
 * Time: 下午4:24
 * To change this template use File | Settings | File Templates.
 * 时间流失率
 */
class TurnoverDataService extends Service
{
    function TurnoverDataService(){
        $this -> table_turnover = 'statistic';
        $this -> table_record = 'record';
        $this -> db = new DB;
        $this -> writeFatio = 4;//每个小时的统计频率 每隔10分钟写入一次数据 1个小时可能由于延迟能问题 大概会写入4次数据
    }

//    public function lists($page,$condition){
//        $server_ids = $condition -> server_ids;
//        $starttime = strtotime($condition->starttime.' 00:00:00');
//        $endtime = strtotime($condition->endtime.' 23:59:59');
//
//        $servers = $this->getServers($server_ids);
//        $list = array();
//
//        //查询服务器选项中的时间分组
//        $this->connectServerDB($servers[0]);
//        $columnTime =  "left({$this->db->fromunixtime('time','%Y-%m-%d %H:%i')},15)";
//        $timearray = $this -> db -> select("$columnTime as time")-> from ($this->table_turnover) ->where("time > $starttime and time < $endtime and type = 4")->order_by('time desc')
//                            ->get()->result_objects();
//        $this-> db -> close();
//        $total = count($timearray);
//        $returntimearray  = array();
//        //取出$page的限制条数的数据
//        for($i=0 ; $i<$total ; $i++){
//            if(   $i >= $page->start && $i<$page->start+$page->limit ){
//                  $returntimearray[] = $timearray[$i] -> time;
//                  $obj = new stdClass();
//                  $obj -> date = $timearray[$i] -> time;
//                  $obj -> turnover24 = 0;
//                  $obj -> turnover72 = 0;
//                  $obj -> turnover168 = 0;
//                  $list[] = $obj;
//            }
//        }
//        $times = "'".implode(',',$returntimearray)."'";
//
//        $register_num = 0;
//        foreach($servers as $server){
//               $this -> connectServerDB($server);
//               $templist = $this->db -> select("$columnTime as time,param_digits1,param_digits2,param_digits3") -> from($this->table_turnover)
//                                    -> where("find_in_set($columnTime,$times) > 0 and type = 4")
//                                    -> get() -> result_objects();
//
//              foreach($list as &$obj){
//                    foreach($templist as $temp){
//                        if($obj->date == $temp ->time){
//                                $obj-> turnover24 += $temp -> param_digits1;
//                                $obj-> turnover72 += $temp -> param_digits2;
//                                $obj-> turnover168 += $temp -> param_digits3;
//                        }
//                    }
//              }
//
//             //查询当前服务器的注册人数
//             $register_num += $this->db -> select("count(uid) as num ") -> from($this->table_record)
//                                        -> where("type = 1")
//                                        -> get() -> result_object() -> num;
//
//            if($server->id == 2442)$register_num += 1200;
//        }
//
//        foreach($list as &$obj){
//            if($register_num < $obj->turnover24){
//                $register_num = $obj->turnover24;
//            }
//
//            $obj -> date .= '0:00';
//             $obj -> turnover24percent = $register_num == 0 ? 0 : number_format($obj->turnover24/$register_num,2) * 100 .'%';
//             $obj -> turnover72percent = $register_num == 0 ? 0 : number_format($obj->turnover72/$register_num,2) * 100 .'%';
//             $obj -> turnover168percent = $register_num == 0 ? 0 : number_format($obj->turnover168/$register_num,2) * 100 .'%';
//        }
//
//        return $list;
//    }

    public function lists($page,$condition){
        $server_ids = $condition -> server_ids;
        $starttime = strtotime($condition->starttime.' 00:00:00');
        $endtime = strtotime($condition->endtime.' 23:59:59');

        $timePoints = timeTickArrayPoint($starttime,$endtime,60*60);//60分钟一个时间点

        $servers = $this->getServers($server_ids);
        $start = $page->start * $this->writeFatio;
        $limit = $this->writeFatio * $page->limit;//15分钟统计一次 就是1个小时统计4次 那么就是按1小时来统计的话就要取4*18条
        $list = array();
        $register_num = 0;
        foreach($servers  as $server){
            $this -> connectServerDB($server);

            $templist = $this-> db -> select("param_digits1,param_digits2,param_digits3,time")
                                    -> from($this->table_turnover)
                                    -> where(" type = 4 and time > $starttime and time < $endtime ")
                                    -> limit($start,$limit,' time desc ')
                                    -> get()
                                    -> result_objects();

            //查询当前服务器注册的总人数
             $register_num += $this->db -> select("count(uid) as num ") -> from($this->table_record)
                                        -> where("type = 1")
                                        -> get() -> result_object() -> num;
             if($server->id==2442)$register_num+=1200;

            $flag = 0;
            $innerflag = 0;
            foreach($timePoints as $point){
                    if($flag >= $page->start&& $flag < ($page->start+$page->limit) ){
                            $hour = date('Y-m-d H',$point);
                            $param1 = 0;
                            $param2 = 0;
                            $param3 = 0;
                            foreach($templist as $temp){
                                $temphour = date('Y-m-d H',$temp->time);
                                if($temphour == $hour){
                                        $param1 += $temp->param_digits1;
                                        $param2 += $temp ->param_digits2;
                                        $param3 += $temp -> param_digits3;
                                }
                            }

                            if(empty($list[$innerflag])){
                                $list[$innerflag] = new stdClass();
                                $list[$innerflag] ->turnover24 = 0;
                                $list[$innerflag] ->turnover72 = 0;
                                $list[$innerflag]  -> turnover168 = 0;
                                $list[$innerflag]  -> date =  date('Y-m-d H:i:s',$point);
                            }

                            $list[$innerflag] ->turnover24 += $param1/$this->writeFatio>>0;
                            $list[$innerflag] ->turnover72 += $param2/$this->writeFatio>>0;
                            $list[$innerflag]  -> turnover168 += $param3/$this->writeFatio>>0;
                            $innerflag++;
                    }
                   $flag++;
            }
        }

        foreach($list as &$obj){
                $obj->turnover24percent = $register_num == 0 ? 0 : number_format($obj->turnover24/$register_num,2);
                if($obj->turnover24percent > 1) $obj->turnover24percent = '100%';
                else $obj->turnover24percent = $obj->turnover24percent * 100 .'%';
                $obj -> turnover72percent = $register_num == 0 ? 0 : number_format($obj->turnover72/$register_num,2) * 100 .'%';
                if($obj->turnover72percent > 1) $obj->turnover72percent = '100%';
                else $obj->turnover72percent = $obj->turnover72percent * 100 .'%';
                $obj -> turnover168percent = $register_num == 0 ? 0 : number_format($obj->turnover168/$register_num,2) * 100 .'%';
                if($obj->turnover168percent > 1) $obj->turnover168percent = '100%';
                else $obj->turnover168percent = $obj->turnover168percent * 100 .'%';
        }

        return $list;

    }

    public function num_rows($condition){
        $starttime = strtotime($condition->starttime.' 00:00:00');
        $endtime =  strtotime($condition->endtime.' 23:59:59');
        $timePoints = timeTickArrayPoint($starttime,$endtime,60*60);//60分钟一个时间点
        return count($timePoints);
    }

    public function total($condition){
        $server_ids = $condition -> server_ids;
        $starttime = strtotime($condition->starttime.' 00:00:00');
        $endtime = strtotime($condition->endtime.' 23:59:59');

        $servers = $this->getServers($server_ids);
        $list = array();

        $obj = new stdClass();
        foreach($servers as $server){
            $this -> connectServerDB($server);
            $temp = $this -> db -> select('sum(param_digits1) as turnover24,
                                         sum(param_digits2) as turnover72,
                                         sum(param_digits3) as turnover168,count(id) as num')
                            -> from($this->table_turnover)
                            -> where("time > $starttime and time < $endtime and type = 4")
                            -> get() -> result_object();

            $obj -> turnover24 += ($temp->turnover24/$temp->num)>>0;
            $obj -> turnover72 += ($temp -> turnover72/$temp->num)>>0;
            $obj -> turnover168 += ($temp -> turnover168/$temp->num)>>0;
        }

        return $obj;
    }

}


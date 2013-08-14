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
    }

    public function lists($page,$condition){
        $server_ids = $condition -> server_ids;
        $starttime = strtotime($condition->starttime.' 00:00:00');
        $endtime = strtotime($condition->endtime.' 23:59:59');

        $servers = $this->getServers($server_ids);
        $list = array();

        //查询服务器选项中的时间分组
        $this->connectServerDB($servers[0]);
        $columnTime =  "left({$this->db->fromunixtime('time','%Y-%m-%d %H:%i')},15)";
        $timearray = $this -> db -> select("$columnTime as time")-> from ($this->table_turnover) ->where("time > $starttime and time < $endtime and type = 4")->order_by('time desc')
                            ->get()->result_objects();
        $this-> db -> close();
        $total = count($timearray);
        $returntimearray  = array();
        //取出$page的限制条数的数据
        for($i=0 ; $i<$total ; $i++){
            if(   $i >= $page->start && $i<$page->start+$page->limit ){
                  $returntimearray[] = $timearray[$i] -> time;
                  $obj = new stdClass();
                  $obj -> date = $timearray[$i] -> time;
                  $obj -> turnover24 = 0;
                  $obj -> turnover72 = 0;
                  $obj -> turnover168 = 0;
                  $list[] = $obj;
            }
        }
        $times = "'".implode(',',$returntimearray)."'";

        $register_num = 0;
        foreach($servers as $server){
               $this -> connectServerDB($server);
               $templist = $this->db -> select("$columnTime as time,param_digits1,param_digits2,param_digits3") -> from($this->table_turnover)
                                    -> where("find_in_set($columnTime,$times) > 0 and type = 4")
                                    -> get() -> result_objects();

              foreach($list as &$obj){
                    foreach($templist as $temp){
                        if($obj->date == $temp ->time){
                                $obj-> turnover24 += $temp -> param_digits1;
                                $obj-> turnover72 += $temp -> param_digits2;
                                $obj-> turnover168 += $temp -> param_digits3;
                        }
                    }
              }

             //查询当前服务器的注册人数
             $register_num += $this->db -> select("count(uid) as num ") -> from($this->table_record)
                                        -> where("type = 1")
                                        -> get() -> result_object() -> num;
        }

        foreach($list as &$obj){
             $obj -> date .= '0:00';
             $obj -> turnover24percent = $register_num == 0 ? 0 : number_format($obj->turnover24/$register_num,2) * 100 .'%';
             $obj -> turnover72percent = $register_num == 0 ? 0 : number_format($obj->turnover72/$register_num,2) * 100 .'%';
             $obj -> turnover168percent = $register_num == 0 ? 0 : number_format($obj->turnover168/$register_num,2) * 100 .'%';
        }

        return $list;
    }

    public function num_rows($condition){
        $server_ids = $condition -> server_ids;
        $starttime = strtotime($condition->starttime.' 00:00:00');
        $endtime =  strtotime($condition->endtime.' 23:59:59');
        $servers = $this->getServers($server_ids);
        $list = array();

        //查询服务器选项中的时间分组
        $this->connectServerDB($servers[0]);
        $num = $this -> db -> select("count(id) as num")-> from ($this->table_turnover) ->where("time > $starttime and time < $endtime and type = 4")->order_by('time desc')
            ->get()->result_object() -> num;
        return $num;
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


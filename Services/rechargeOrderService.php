<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-4-25
 * Time: 上午11:05
 * To change this template use File | Settings | File Templates.
 * 充值玩家排名
 */
class RechargeOrderService extends Service
{

    function RechargeOrderService(){
        $this->table_record = 'record';
        $this->table_player = 'user';
        $this -> db = new DB;
    }

    public function lists($condition){
        $servers = $this->getServers($condition -> servers);
        $starttime = $condition -> starttime.' 00:00:00';
        $endtime = $condition -> endtime .' 23:59:59';
        $list = array();
        $total_rows = 50;

         // 总充值元宝
        foreach($servers as $server){
            $this->connectServerDB($server);
            $templist = $this->db->select("a.*, b.level,b.uid as pid,b.loginname as account_name,b.name from ( select sum(param1) as recharge_yuanbao,uid")
                                    -> from("$this->table_record")
                                    -> where("type=0 and time > '$starttime' and time < '$endtime'")
                                    -> group_by("uid")
                                    -> order_by("sum(param1) desc limit 0,$total_rows) as a left join $this->table_player b on a.uid = b.uid")
                                    -> get() -> result_objects();

            foreach($templist as &$temp){
                  $temp -> server = $server;
                  $list[] = $temp;
            }
        }

        //排序
        for($i = 0 ; $i < count($list) ; $i++){
             for($k = count($list)-1 ; $k > $i ; $k--){
                 if($list[$k]->recharge_yuanbao > $list[$i-1]->recharge_yuanbao){
                     $temp = $list[$k];
                     $list[$k] = $list[$k-1];
                     $list[$k-1] = $temp;
                 }
             }
        }

        $returnlist = array_slice($list,0,$total_rows);
        unset($this->db);
        unset($list);
        unset($servers);
        //查询其他数据
        foreach($returnlist as &$param){
                 $db_temp = new DB;
                 $db_temp -> connect($param->server->ip.':'.$param->server->port,$param->server->dbuser,$param->server->dbpwd,TRUE);
                 $db_temp -> select_db($param->server->dynamic_dbname);
                //查询每个用户非充值获取的元宝
                /*$param -> unrecharge_yuanbao = $db_temp -> select("sum(param1) as unrecharge_yuanbao")
                                                                    -> from($this->table_record)
                                                                    -> where("type=@[非充值用户获取元宝类型] and time > $starttime and time < $endtime")
                                                                    -> get()
                                                                    -> result_object() -> unrecharge_yuanbao;*/
                $param -> unrecharge_yuanbao = 0;

                //查询每个用户总消耗的元宝
                $param -> used_yuanbao = $db_temp -> select("sum(param1) as used_yuanbao")
                    -> from($this->table_record)
                    -> where("type=4 and time > $starttime and time < $endtime")
                    -> get()
                    -> result_object() -> used_yuanbao;

                $param -> shengyu_yuanbao = $param -> recharge_yuanbao + $param->unrecharge_yuanbao - $param->used_yuanbao;

                $param -> servername = $param->server->name;
        }

        return $returnlist ;
    }

    public function getCondition($condition){}

}

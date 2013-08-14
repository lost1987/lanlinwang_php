<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-7-18
 * Time: 下午3:49
 * To change this template use File | Settings | File Templates.
 */
class PlayerService extends  Service
{

    function PlayerService(){
          $this -> table_user = 'user';
          $this -> db = new  DB;
    }

    public function lists($page,$condition){
            $server = $this->getServer($condition -> server->id);
            $list = array();
            $consql = $this->getCondition($condition);

            if(!empty($server)){
                $this->connectServerDB($server);
                $list =  $this->db->select("*")
                    ->from("$this->table_user")
                    ->where($consql)
                    ->order_by($this->getOrder($condition->order))
                    ->limit($page->start,$page->limit,'uid asc')
                    ->get()
                    ->result_objects();
            }
           return $list;
    }

    public function num_rows($condition){
        $server =  $this->getServer($condition -> server->id);
        if(empty($server)) return 0;
        $this->connectServerDB($server);
        $consql = $this->getCondition($condition);
        return $this->db->select("count(uid) as num") -> from($this->table_user)->where($consql)->get()->result_object()->num;
    }

    public function getCondition($condition){
        $account_or_name = str_replace(' ','',$condition->account_or_name);
        $onlinestatus = $condition->onlinestatus;
        $lastdate = $this->db->timestamp('lastdate');
        $now = time();

        $sql = '';
        if(!empty($account_or_name)){
            $sql =  " (loginname like '%$account_or_name%' or name like '%$account_or_name%') ";
        }


        if(!empty($onlinestatus)){
            if(empty($sql)){
                  if($onlinestatus == 1)
                    $sql = " ($now - $lastdate) <= 10*60 ";
                 else
                     $sql = " ($now - $lastdate) > 10*60 ";
            }else{
                if($onlinestatus == 1)
                    $sql .= " and ($now - $lastdate) <= 10*60";
                else
                    $sql = "and ($now - $lastdate) > 10*60 ";
            }
        }

        if(!empty($sql))
        $sql = ' where '.$sql;

        return $sql;
    }

    private  function getOrder($order){
        switch($order){
            case 0: $orderSql = " level desc";
                break;
            case 1: $orderSql = " level desc";
                break;
            case 2: $orderSql = " money2 desc";
                break;
            case 3: $orderSql = " money1 desc";
                break;
        }
        return $orderSql;
    }

    public function playerSearch($servers,$condition){
        $loginname_or_name  = $condition -> playername_or_id;
        $online = $condition->online;
        $servers = $this->getServers($servers);

        $list = array();
        foreach($servers as $server){
            $this->connectServerDB($server);

            //查询用户
            if(!$online){
               $templist = $this->db->select('uid,loginname,name')
                                -> from($this->table_user)
                                -> where("name like '%$loginname_or_name%' or loginname like '%$loginname_or_name%'")
                                -> get() -> result_objects();
            }else{
                $timestamp = $this->db->timestamp('lastdate');
                $now = time();
                $templist = $this->db->select('uid,loginname,name')
                    -> from($this->table_user)
                    -> where("name like '%$loginname_or_name%' or loginname like '%$loginname_or_name% and $now-$timestamp < 60*10'")
                    -> get() -> result_objects();
            }

            foreach($templist as $temp){
                $temp -> name = "{$temp->loginname}__{$temp->name}__{$server->name}";
                $temp -> server_id = $server -> id;
                $list[]=$temp;
            }

        }
            return $list;
    }
}

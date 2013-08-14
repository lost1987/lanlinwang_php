<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-21
 * Time: 下午1:47
 * To change this template use File | Settings | File Templates.
 */
class LevelDataService extends Service
{

    function LevelDataService(){
        $this -> table_level = 'user';
        $this -> table_record = 'record';
        $this -> db = new DB;
    }

    public function lists($condition){
        $server_ids = $condition -> server_ids;
        $servers = $this->getServers($server_ids);
        $list = array();

        $curtime = time();
        $starttime24 = $curtime - 60*60*24;
        $starttime72 = $curtime - 60*60*72;
        $columnTime = $this->db->timestamp('time');
        $totalplayers = 0;
        foreach($servers as $server){
               $this->connectServerDB($server);

               //查询等级分布
               $level_dispatch = $this->db -> select("level as levels")
                                            -> from($this->table_level)
                                            -> group_by("level")
                                            ->get()->result_objects();

               foreach($level_dispatch as $dispatch){

                    $level = $dispatch -> levels;
                    if(!isset($list[$level])) $list[$level] = new stdClass();

                   $list[$level]  -> levels = $level;

                   //查询此时的等级人数
                   $list[$level] -> levelsnum += $this->db->select('count(uid) as num') -> from($this->table_level)
                                                                -> where("level = $level") -> get() -> result_object() -> num;

                   //查询对于此时24小时未登录的人数等级分布
                   $list[$level] -> offline24 += $this -> db -> select('count(uid) as num')
                       -> from($this->table_level)
                       -> where("uid not in (select distinct(uid) as uid from $this->table_record where $columnTime > $starttime24 and  $columnTime < $curtime and type = 3 ) and level = $level")
                       -> get()
                       -> result_object() -> num;


                   //查询对于此时72小时未登录的人数等级分布
                   $list[$level] -> offline72 += $this -> db -> select('count(uid) as num')
                       -> from($this->table_level)
                       -> where("uid not in (select distinct(uid) as uid from $this->table_record where $columnTime > $starttime72 and  $columnTime < $curtime and type = 3) and level = $level")
                       -> get()
                       -> result_object() -> num;
               }

               //查询玩家的全部人数
               $totalplayers += $this->db -> select('count(uid) as num') -> from ($this->table_level)->get()->result_object()->num;

        }

        $returnlist = array();
        foreach($list as $obj){
            $obj -> levelspercent = number_format($obj->levelsnum/$totalplayers,2) * 100 . '%';
            $obj -> offline24percent =  isset($obj->offline24) ? (1- number_format($obj->offline24/$obj->levelsnum,2)) * 100 .'%' : '/';
            $obj -> offline72percent = isset($obj->offline72) ? (1-number_format($obj->offline72/$obj->levelsnum,2)) * 100 .'%' : '/';
            $returnlist[] = $obj;
        }

        return $returnlist;
    }

    public function num_rows($condition){
        return 0;
    }

    public function total($condition){
        $server_ids = $condition -> server_ids;
        $servers = $this->getServers($server_ids);

        $curtime = time();
        $starttime24 = $curtime - 60*60*24;
        $starttime72 = $curtime - 60*60*72;
        $columnTime = $this->db->timestamp('time');
        $obj = new stdClass();
        foreach($servers as $server){
            $this->connectServerDB($server);

                //查询此时的等级人数
                 $obj->levelsnum += $this->db->select('count(uid) as num') -> from($this->table_level)
                                        -> get() -> result_object() -> num;

                //查询对于此时24小时未登录的人数等级分布
                $obj->offline24 += $this -> db -> select('count(uid) as num')
                    -> from($this->table_level)
                    -> where("uid not in (select distinct(uid) as uid from $this->table_record where $columnTime > $starttime24 and  $columnTime < $curtime and type = 3 )")
                    -> get()
                    -> result_object() -> num;


                //查询对于此时72小时未登录的人数等级分布
               $obj -> offline72 += $this -> db -> select('count(uid) as num')
                    -> from($this->table_level)
                    -> where("uid not in (select distinct(uid) as uid from $this->table_record where $columnTime > $starttime72 and  $columnTime < $curtime and type = 3)")
                    -> get()
                    -> result_object() -> num;
            }
        return $obj;
    }
}

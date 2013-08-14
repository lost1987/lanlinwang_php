<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-6
 * Time: 上午11:47
 * To change this template use File | Settings | File Templates.
 */
class Service
{

     public  $db;

     function service(){
         //初始化DB
         $db = null;

         $db = new DB();
         $db -> connect(DB_HOST.':'.DB_PORT,DB_USER,DB_PWD);

         $this -> db = $db;
     }

    /**
     * @param $server_ids  包含服务器信息的数组或者只包含服务器ID的字符串
     * @return mixed
     */
    protected  function getServers($server_ids){
        if(is_array($server_ids)){
            $servers = array();
            foreach($server_ids as $server){
                  $servers[] = $server->id;
            }
            $server_ids = implode(',',$servers);
        }

        $server_db = new DB();
        $server_db -> connect(DB_HOST.':'.DB_PORT,DB_USER,DB_PWD,TRUE);
        $server_db -> select_db(DB_NAME);
        $server_table = DB_PREFIX.'servers';
        $sql = "select * from $server_table where id in ($server_ids)";
        $servers = $server_db->query($sql) ->result_objects();
        $server_db -> close();
        unset($server_db);
        return $servers;
    }

    protected function getServer($server_id){
            $server_db = new DB();
            $server_db -> connect(DB_HOST.':'.DB_PORT,DB_USER,DB_PWD,TRUE);
            $server_db -> select_db(DB_NAME);
            $server_table = DB_PREFIX.'servers';
            $sql = "select * from $server_table where id = $server_id";
            $server =$server_db -> query($sql) -> result_object();
            $server_db -> close();
            return $server;
    }

    protected  function connectServerDB($server){
            $this -> db ->  connect($server->ip.':'.$server->port,$server->dbuser,$server->dbpwd);
            $this -> db ->  select_db($server->dynamic_dbname);
    }

}

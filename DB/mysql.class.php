<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-6
 * Time: 上午11:35
 * To change this template use File | Settings | File Templates.
 */
class Mysql
{

    public static $debug=FALSE;

    public $link;

    public $queryState;

    private $_sql;

    private $_limit;

    private $_table;

    private $_condition;

    private $_on_condition;

    private $_order_by;

    private $_group_by;

    function mysql(){
        $this -> flush();
    }

    public function connect($DB_HOST,$DB_USER,$DBPWD,$NEWLINK=FALSE){
        $this->link = mysql_connect($DB_HOST,$DB_USER,$DBPWD,$NEWLINK);
        return $this->link;
    }

    public function charset($charset){
    }

    public function select_db($dbname){
        return   mysql_select_db($dbname,$this->link);
    }

    public function query($sql){
        if(!empty($this->link))
            $this -> queryState = mysql_query($sql,$this->link);
        return $this;
    }


    public function result_objects(){
        if(!empty($this->queryState)){
            $list = array();
            while($row = mysql_fetch_object($this->queryState)){
                $list[] = $row;
            }
            return $list;
        }
        return FALSE;
    }

    public function result_object(){
        if(!empty($this->queryState)){
            if($row = mysql_fetch_object($this->queryState)){
                return  $row;
            }
        }
        return FALSE;
    }

    public function insert_id($table=null){
        return mysql_insert_id($this->link);
    }

    public function close(){
        mysql_close($this->link);
    }

    /**
     * @addonal 扩展方法
     */

    public function select($sql){
        $this -> _sql = 'select '.$sql;
        return $this;
    }

    public function from($table){
        $this -> _table = ' from '.$table;
        return $this;
    }

    public function where($condition){
        if(empty($condition))return $this;

        $testCondition = trim($condition);

        if(preg_match('/[ ]*where(.*)/i',$testCondition))
          $condition = preg_replace('/[ ]*where(.*)/','$1',$condition);
        if(preg_match('/[ ]*where(.*)/i',$this->_condition))
        $this -> _condition = preg_replace('/[ ]*where(.*)/','$1',$this->_condition);

        $this -> _condition = " where $this->_condition $condition ";
        return $this;
    }

    public function on($on_condition){
        if(empty($on_condition))return $this;

        $this -> _on_condition = " on $on_condition ";
        return $this;
    }

    public function limit($start,$limit,$order=null){
        $this->_limit = " limit $start,$limit ";
        if(empty($this->_order_by) && !empty($order))
        $this->order_by($order);
        return $this;
    }

    public  function one(){
        $this->_limit = ' limit 1 ';
        return $this;
    }

    public function order_by($order){
        if(strpos($order,'order') > -1)$order = str_replace('order','',$order);
        if(strpos($order,'by') > -1)$order = str_replace('by','',$order);
        $this -> _order_by = ' order by '.$order;
        return $this;
    }

    public function group_by($group_by){
        $this -> _group_by = ' group by '.$group_by;
        return $this;
    }

    //执行查询
    public function get($flush=TRUE){
        if(self::$debug)
        error_log( $this->_sql.
            $this->_table.
            $this->_on_condition.
            $this->_condition.
            $this->_group_by.
            $this->_order_by.
            $this->_limit);

      $this->queryState =  mysql_query(
            $this->_sql.
            $this->_table.
            $this->_on_condition.
            $this->_condition.
            $this->_group_by.
            $this->_order_by.
            $this->_limit
            ,$this->link);

        if($flush)$this->flush();
        return $this;
    }

    //返回当前的sql语句
    public function fetch($flush=TRUE){
        $sql =  $this->_sql.
                $this->_table.
                $this->_on_condition.
                $this->_condition.
                $this->_group_by.
                $this->_order_by.
                $this->_limit;

        if($flush)$this->flush();
        return $sql;
    }

    //清空所有变量
    private function flush(){
        $this -> _sql = '';
        $this -> _limit = '';
        $this -> _table = '';
        $this -> _condition = '';
        $this -> _on_condition = '';
        $this -> _order_by = '';
        $this -> _group_by = '';
    }

    public function datetime($columnName,$limit=20,$total=120){
        return $columnName;
    }

    public function cast($columnName){
        return $columnName;
    }

    public function trans_begin(){
        mysql_query('set autocommit = 0',$this->link);
        mysql_query('begin',$this->link);
    }

    public function commit(){
        mysql_query('commit',$this->link);
        mysql_query('end',$this->link);
        mysql_query('set autocommit = 1',$this->link);
    }

    public function rollback(){
        mysql_query('rollback',$this->link);
        mysql_query('end',$this->link);
        mysql_query('set autocommit = 1',$this->link);
    }

    public function timestamp($columnName){
            return " unix_timestamp($columnName) ";
    }

    public function fromunixtime($columnName,$format='%Y-%m-%d %H:%i:%S'){
           return " from_unixtime($columnName,'$format')";
    }
}

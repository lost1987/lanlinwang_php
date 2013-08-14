<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-6
 * Time: 上午11:35
 * To change this template use File | Settings | File Templates.
 */
class Mssql
{

        public $link;

        public $queryState;

        private $_sql;

        private $_limit_pre;

        private $_limit_after;

        private $_table;

        private $_condition;

        private $_on_condition;

        private $_order_by;

        private $_group_by;

        function Mssql(){
                $this -> flush();
        }

        public function connect($DB_HOST,$DB_USER,$DBPWD,$NEWLINK=FALSE){
            $this->link = mssql_connect($DB_HOST,$DB_USER,$DBPWD,$NEWLINK);
            return $this->link;
        }

        public function charset($charset){
        }

        public function select_db($dbname){
            return mssql_select_db($dbname,$this->link);
        }

        public function query($sql){
            if(!empty($this->link))
            $this -> queryState = mssql_query($sql,$this->link);
            return $this;
        }


        public function result_objects(){
            if(!empty($this->queryState)){
                $list = array();
                while($row = mssql_fetch_object($this->queryState)){
                        $list[] = $row;
                }
                return $list;
            }
            return FALSE;
        }

       public function result_object(){
           if(!empty($this->queryState)){
               if($row = mssql_fetch_object($this->queryState)){
                   return  $row;
               }
           }
           return FALSE;
       }

       public function insert_id($table){
           $sql = "select IDENT_CURRENT('$table')  as  insert_id";
           return $this->query($sql)->result_object()->insert_id;
       }

       public function close(){
           mssql_close($this->link);
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

       public function limit($start,$limit,$order='id asc'){
           if(strpos($order,'order') > -1)$order = str_replace('order','',$order);
           if(strpos($order,'by') > -1)$order = str_replace('by','',$order);
           $this->_limit_pre = "select * from (select row_number() over (order by $order) as rownumber,  ";
           $this->_limit_after = " ) as t where t.rownumber > $start and t.rownumber <= $limit";
           $this->_sql = str_replace('select',' ',$this->_sql);
           return $this;
       }

       public function one(){
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

       public function get($flush=TRUE){
        /*   error_log($this->_limit_pre.
               $this->_sql.
               $this->_table.
               $this->_on_condition.
               $this->_condition.
               $this->_group_by.
               $this->_limit_after.
               $this->_order_by);*/
           $this->queryState = mssql_query(
                $this->_limit_pre.
                $this->_sql.
                $this->_table.
                $this->_on_condition.
                $this->_condition.
                $this->_group_by.
                $this->_limit_after.
                $this->_order_by
               ,$this->link);

            if($flush)$this->flush();
            return $this;
       }

       public function fetch($flush=TRUE){
            $sql = $this->_limit_pre.
                   $this->_sql.
                   $this->_table.
                   $this->_on_condition.
                   $this->_condition.
                   $this->_group_by.
                   $this->_limit_after.
                   $this->_order_by;

            if($flush)$this->flush();
            return $sql;
       }

       private function flush(){
           $this -> _sql = '';
           $this -> _limit_pre = '';
           $this -> _limit_after = '';
           $this -> _table = '';
           $this -> _condition = '';
           $this -> _on_condition = '';
           $this -> _order_by = '';
           $this -> _group_by = '';
       }

       public function datetime($columnName,$limit=20,$total=120){
            return "CONVERT(varchar($limit), $columnName, $total)";
       }


       public function cast($columnName){
           return "cast($columnName as datetime)";
       }

       public function trans_begin(){
           mssql_query('begin tran',$this->link);
       }

       public function commit(){
           mssql_query('commit tran',$this->link);
       }

       public function rollback(){
           mssql_query('rollback tran',$this->link);
       }

       public function timestamp($columnName){
           return '';
       }

    public function fromunixtime($columnName,$format='%Y-%m-%d %H:%i:%S'){
        return '';
    }
}

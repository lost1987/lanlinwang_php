<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-18
 * Time: 下午1:37
 * To change this template use File | Settings | File Templates.
 * 综合数据查询
 */

Class complexDataService extends  Service {

    function complexDataService(){
        $this -> table_complex = 'record';
        $this -> table_statistic = 'statistic';
        $this -> db =  new DB;
    }

    public function lists($page,$condition){
        $server_ids = $condition -> server_ids;
        $starttime = $condition->starttime;
        $endtime = $condition->endtime;

        $timePointCollection = timeTickArrayPoint($starttime,$endtime,60*60*24,null,TRUE);
        $list = array();
        $currow = 0;//当前的行数
        $servers = $this->getServers($server_ids);
        foreach($timePointCollection as $timepoint){
                if($currow >= $page->start && $currow < $page->limit){
                        $obj = new stdClass();
                        foreach($servers as $server){
                               $this->connectServerDB($server);
                               $time = $this->db->timestamp('left(time,10)');
                               $obj -> registernum += $this->db->select("count(uid) as registernum")
                                                                            ->from($this->table_complex)
                                                                            ->where(" $time = $timepoint and type=1")
                                                                            ->get()->result_object() -> registernum;

                               $obj -> createnum +=  $this->db->select("count(uid) as createnum")
                                                                           ->from($this->table_complex)
                                                                           ->where(" $time = $timepoint and type=2")
                                                                           ->get()->result_object() -> createnum;


                               $obj -> loginnum  += $this->db->select("count(uid) as loginnum")
                                                                           ->from($this->table_complex)
                                                                           ->where(" $time = $timepoint and type=3")
                                                                           ->get()->result_object() -> loginnum;

                                //最高在线,平均在线 放一下

                                $curtime  = $this->db->timestamp($this->db->fromunixtime('time','%Y-%m-%d'));
                                $ave = $this -> db->select("sum(param_nums) as aveonline,count(id) as times")
                                                                ->from($this->table_statistic)
                                                                ->where("$curtime = $timepoint and type = 1")
                                                                ->get()->result_objects();

                                $obj -> aveonline += empty($ave->aveonline) ? 0 : ($ave->aveonline/$ave->times)>>0;
                                $obj->maxonline += $this -> db->select("max(param_nums) as maxonline")
                                                        ->from($this->table_statistic)
                                                        ->where("$curtime = $timepoint and type = 1")
                                                        ->get()->result_object()->maxonline;


                                //充值元宝
                                $obj -> recharge += $this->db->select("sum(param1) as recharge")
                                                                ->from($this->table_complex)
                                                                ->where(" $time = $timepoint and type=0")
                                                                ->get()->result_object() -> recharge;


                               //充值人数
                               $obj -> rechargeperson +=  $this->db->select("count(distinct(uid)) as rechargeperson")
                                                                   ->from($this->table_complex)
                                                                   ->where(" $time = $timepoint and type=0")
                                                                   ->get()->result_object() -> rechargeperson;

                               //充值次数
                               $obj -> rechargenum += $this->db->select("count(uid) as rechargenum")
                                                                       ->from($this->table_complex)
                                                                       ->where(" $time = $timepoint and type=0")
                                                                       ->get()->result_object() -> rechargenum;
                              //新增充值元宝
                              $obj -> newrecharge += $this ->db -> select ("sum(param1) as newrecharge")
                                                                          ->from($this->table_complex)
                                                                          ->where(" $time = $timepoint and type=0 and uid not in (select distinct(uid) as uid from $this->table_complex where $time < $timepoint and type=0)")
                                                                          ->get()->result_object()->newrecharge;

                            //新增充值人数
                            $obj -> newrechargeperson += $this ->db -> select ("count(distinct(uid)) as newrechargeperson")
                                                                            ->from($this->table_complex)
                                                                            ->where(" $time = $timepoint and type=0 and uid not in (select distinct(uid) as uid from $this->table_complex where $time < $timepoint and type =0)")
                                                                            ->get()->result_object()->newrechargeperson;


                            //老用户登录
                            $obj -> oldlogin += $this -> db ->select("count(uid) as oldlogin")
                                                            -> from($this->table_complex)
                                                            -> where(" $time = $timepoint and type=3 and uid in (select distinct(uid) as uid from $this->table_complex where $time < $timepoint and type = 3)")
                                                            -> get()->result_object()->oldlogin;

                            //老用户充值
                            $obj -> oldrecharge += $this ->db -> select ("sum(param1) as oldrecharge")
                                                                ->from($this->table_complex)
                                                                ->where(" $time = $timepoint and type=0 and uid in (select distinct(uid) as uid from $this->table_complex where $time < $timepoint and type = 3)")
                                                                ->get()->result_object()->oldrecharge;


                            //消费元宝
                            $obj -> consumption +=  $this->db->select("count(distinct(uid)) as consumption")
                                                                ->from($this->table_complex)
                                                                ->where(" $time = $timepoint and type=4")
                                                                ->get()->result_object() -> consumption;


                            //剩余元宝
                            $curtime  = $this->db->timestamp($this->db->fromunixtime('time','%Y-%m-%d'));
                            $overyuanbao =  $this->db->select("param_digits1 as overyuanbao,time")
                                                                ->from($this->table_statistic)
                                                                ->where(" $curtime = $timepoint and type = 2")
                                                                -> get() ->result_objects();

                            for($i =0 ; $i < count($overyuanbao) ; $i++){
                                for($k = count($overyuanbao) -1; $k > $i ; $k--){
                                       if($overyuanbao[$k]->time > $overyuanbao[$k-1]->time){
                                           $temp = $overyuanbao[$k];
                                           $overyuanbao[$k] = $overyuanbao[$k-1];
                                           $overyuanbao[$k-1] = $temp;
                                       }
                                }
                            }

                            $obj->overyuanbao += count($overyuanbao) > 0 ? $overyuanbao[0]->overyuanbao : 0;

                            // 查询72小时内有登录的用户的剩余元宝
                            $overyuanbao72 =  $this->db->select("param_digits1 as overyuanbao72,time")
                                ->from($this->table_statistic)
                                ->where(" $curtime = $timepoint and type = 3")
                                -> get() ->result_objects();

                            for($i =0 ; $i < count($overyuanbao72) ; $i++){
                                for($k = count($overyuanbao72) -1; $k > $i ; $k--){
                                    if($overyuanbao72[$k]->time > $overyuanbao72[$k-1]->time){
                                        $temp = $overyuanbao72[$k];
                                        $overyuanbao72[$k] = $overyuanbao72[$k-1];
                                        $overyuanbao72[$k-1] = $temp;
                                    }
                                }
                            }

                            $obj->overyuanbao72 += count($overyuanbao72) > 0 ? $overyuanbao72[0]->overyuanbao72 : 0;
                        }

                        //ARPU
                        $obj -> arpu = ($obj->rechargeperson != 0) ? number_format($obj->recharge/$obj->rechargeperson,2) : '/';
                        //首冲ARPU
                        $obj -> newarpu = ($obj->newrechargeperson !=0 ) ? number_format($obj->newrecharge/$obj->newrechargeperson,2) : '/';
                        //付费率
                        $obj -> rechargeratio = ($obj->registernum !=0 ) ? number_format($obj->newrechargeperson/($obj->registernum),2)*100 .'%' : '/';

                        $obj -> date = date('Y-m-d',$timepoint);
                        $list[] = $obj;
                }

                $currow++;
        }
        return $list;
    }

    public function num_rows($condition){
        $starttime = $condition->starttime;
        $endtime = $condition->endtime;
        $timePointCollection = timeTickArrayPoint($starttime,$endtime,60*60*24);
        return count($timePointCollection);
    }

    public function total($condition){
        $server_ids = $condition -> server_ids;
        $starttime = $condition->starttime;
        $endtime = $condition->endtime;

        $servers = $this->getServers($server_ids);
        $timePointCollection = timeTickArrayPoint($starttime,$endtime,60*60*24);

        $obj = new stdClass();
        foreach($timePointCollection as $timepoint){
                foreach($servers as $server){
                    $this->connectServerDB($server);
                    $time = $this->db->timestamp('left(time,10)');
                    $obj -> registernum += $this->db->select("count(uid) as registernum")
                        ->from($this->table_complex)
                        ->where(" $time = $timepoint and type=1")
                        ->get()->result_object() -> registernum;

                    $obj -> createnum +=  $this->db->select("count(uid) as createnum")
                        ->from($this->table_complex)
                        ->where(" $time = $timepoint and type=2")
                        ->get()->result_object() -> createnum;


                    $obj -> loginnum  += $this->db->select("count(uid) as loginnum")
                        ->from($this->table_complex)
                        ->where(" $time = $timepoint and type=3")
                        ->get()->result_object() -> loginnum;

                    //最高在线,平均在线 放一下
                    $curtime  = $this->db->timestamp($this->db->fromunixtime('time','%Y-%m-%d'));
                    $ave = $this -> db->select("sum(param_nums) as aveonline,count(id) as times")
                        ->from($this->table_statistic)
                        ->where("$curtime = $timepoint and type = 1")
                        ->get()->result_objects();

                    $obj -> aveonline += empty($ave->aveonline) ? 0 : ($ave->aveonline/$ave->times)>>0;
                    $obj->maxonline += $this -> db->select("max(param_nums) as maxonline")
                        ->from($this->table_statistic)
                        ->where("$curtime = $timepoint and type = 1")
                        ->get()->result_object()->maxonline;



                    //充值元宝
                    $obj -> recharge += $this->db->select("sum(param1) as recharge")
                        ->from($this->table_complex)
                        ->where(" $time = $timepoint and type=0")
                        ->get()->result_object() -> recharge;


                    //充值人数
                    $obj -> rechargeperson +=  $this->db->select("count(distinct(uid)) as rechargeperson")
                        ->from($this->table_complex)
                        ->where(" $time = $timepoint and type=0")
                        ->get()->result_object() -> rechargeperson;

                    //充值次数
                    $obj -> rechargenum += $this->db->select("count(uid) as rechargenum")
                        ->from($this->table_complex)
                        ->where(" $time = $timepoint and type=0")
                        ->get()->result_object() -> rechargenum;

                    //新增充值元宝
                    $obj -> newrecharge += $this ->db -> select ("sum(param1) as newrecharge")
                        ->from($this->table_complex)
                        ->where(" $time = $timepoint and type=0 and uid not in (select distinct(uid) as uid from $this->table_complex where $time < $timepoint and type=0)")
                        ->get()->result_object()->newrecharge;

                    //新增充值人数
                    $obj -> newrechargeperson += $this ->db -> select ("count(distinct(uid)) as newrechargeperson")
                        ->from($this->table_complex)
                        ->where(" $time = $timepoint and type=0 and uid not in (select distinct(uid) as uid from $this->table_complex where $time < $timepoint and type =0)")
                        ->get()->result_object()->newrechargeperson;


                    //老用户登录
                    $obj -> oldlogin += $this -> db ->select("count(uid) as oldlogin")
                        -> from($this->table_complex)
                        -> where(" $time = $timepoint and type=3 and uid in (select distinct(uid) as uid from $this->table_complex where $time < $timepoint and type = 3)")
                        -> get()->result_object()->oldlogin;

                    //老用户充值
                    $obj -> oldrecharge += $this ->db -> select ("sum(param1) as oldrecharge")
                        ->from($this->table_complex)
                        ->where(" $time = $timepoint and type=0 and uid in (select distinct(uid) as uid from $this->table_complex where $time < $timepoint and type = 3)")
                        ->get()->result_object()->oldrecharge;


                    //消费元宝
                    $obj -> consumption +=  $this->db->select("count(distinct(uid)) as consumption")
                        ->from($this->table_complex)
                        ->where(" $time = $timepoint and type=4")
                        ->get()->result_object() -> consumption;


                    //剩余元宝
                    $curtime  = $this->db->timestamp($this->db->fromunixtime('time','%Y-%m-%d'));
                    $overyuanbao =  $this->db->select("param_digits1 as overyuanbao,time")
                        ->from($this->table_statistic)
                        ->where(" $curtime = $timepoint and type = 2")
                        -> get() ->result_objects();

                    for($i =0 ; $i < count($overyuanbao) ; $i++){
                        for($k = count($overyuanbao) -1; $k > $i ; $k--){
                            if($overyuanbao[$k]->time > $overyuanbao[$k-1]->time){
                                $temp = $overyuanbao[$k];
                                $overyuanbao[$k] = $overyuanbao[$k-1];
                                $overyuanbao[$k-1] = $temp;
                            }
                        }
                    }

                    $obj->overyuanbao += count($overyuanbao) > 0 ? $overyuanbao[0]->overyuanbao : 0;

                    // 查询72小时内有登录的用户的剩余元宝
                    $overyuanbao72 =  $this->db->select("param_digits1 as overyuanbao72,time")
                        ->from($this->table_statistic)
                        ->where(" $curtime = $timepoint and type = 3")
                        -> get() ->result_objects();

                    for($i =0 ; $i < count($overyuanbao72) ; $i++){
                        for($k = count($overyuanbao72) -1; $k > $i ; $k--){
                            if($overyuanbao72[$k]->time > $overyuanbao72[$k-1]->time){
                                $temp = $overyuanbao72[$k];
                                $overyuanbao72[$k] = $overyuanbao72[$k-1];
                                $overyuanbao72[$k-1] = $temp;
                            }
                        }
                    }

                    $obj->overyuanbao72 += count($overyuanbao72) > 0 ? $overyuanbao72[0]->overyuanbao72 : 0;
                }
         }

        //ARPU
        $obj -> arpu = ($obj->rechargeperson != 0) ? number_format($obj->recharge/$obj->rechargeperson,2) : '/';
        //首冲ARPU
        $obj -> newarpu = ($obj->newrechargeperson !=0 ) ? number_format($obj->newrecharge/$obj->newrechargeperson,2) : '/';
        //付费率
        $obj -> rechargeratio = ($obj->registernum !=0 ) ? number_format($obj->newrechargeperson/($obj->registernum),2)*100 .'%' : '/';

         return $obj;
    }

}
<?php

$mc = new Memcaches(MEMCACHED_HOST,MEMCACHED_PORT);
$arr = array(
    'name'
);

//$mc -> setList($arr);
$a = $mc -> memcache -> get('name');
//$mc -> delList($arr);
var_dump($a);

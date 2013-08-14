<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-5-9
 * Time: 下午3:02
 * To change this template use File | Settings | File Templates.
 * 测试入口
 */

define('BASEPATH',dirname(dirname(__FILE__)));

$testClass = 'Memcaches';
$testFile = '/Lib/memcaches.class.php';
$testCase = 'memcached.php';

include BASEPATH.'/Conf/db.inc.php';
include BASEPATH.$testFile;
include './'.$testCase;




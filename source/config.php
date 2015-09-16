<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
//开发环境设置
return array(
   "db" => array(
                'host' =>'127.0.0.1',
                'port' => 3306,
                'dbname' => 'card_db',
                'username' => 'tomzhao',
                'password' => '111111',
                'charset' => 'utf8',
                'persistent' => 1),
   "sphinx" => array(
                'host' =>'127.0.0.1',
                'port' => 9306,
                'sp_port' => 9312,
                'dbname' => 'card_db',
                'username' => 'root',
                'password' => '111111',
                'charset' => 'utf8',
                'persistent' => 1,
                'sphinx_use'=>1,
                'reindex_cmd'=>'/opt/sphinx/reindex.sh'),
    "valid_time"=>2*60,
    "delay_time"=>30,
    "time_out"=>10,
    "room_container_max"=>100,
    "room_container_limit"=>50,
    "py"=>'pinyin-utf8.dat',
    "robot"=>'讯鸟客服',
    "notify_url"=>"http://xunyi-test.nvwayun.com",
    "redis"=>array('host' => '127.0.0.1','port'=>6379),
    'gearman'=>array('host' => '127.0.0.1','port'=>4730),
    'xunyi_url'=>array('server'=>"http://xunyi-test.nvwayun.com/weixin/api/xunyi/",'switch'=>0)
    );
?>
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
    "redis"=>array('host' => '127.0.0.1','port'=>6379),
    "http"=>array('host' => '0.0.0.0','port'=>8080),
    //"http"=>array('host' => '192.168.2.235','port'=>8080),
    //"http"=>array('host' => '10.122.75.227','port'=>8080),
    );
?>
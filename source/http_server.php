<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
include_once './common/hash_map.php';
 class http_server
{
    private $hash_map;
    private $server;

    public function __destruct ()
    {
        $this->hash_map->clear();
    }

    public function start_server($server_ip,$port)
    {
        $this->server = new swoole_http_server($server_ip, $port);
        $this->server->set(array(
        'worker_num' => 8,
        'max_request' => 10000,
        'max_conn' => 100000,
        'dispatch_mode' => 2,
        'debug_mode'=> 1,
        'daemonize' => false));
        
        $this->server->on('Request', function($request, $response) {
        $url=$request->server["request_uri"]; 
        $func=$this->get_func($url);
        $response->cookie("User", "Swoole");
        $response->header("X-Server", "Swoole");
        if(function_exists($func))
        {
            $result=$func($request,$response);
            $response->end($result);
        }else {
            //$response-> header("HTTP/1.1 404 Not Found");
            return;
        }        
        }); 
        echo "Https Server is start at ".date("Y-m-d H:i:s")." on port ".$port."\r\n";
        $this->server->start();
    }
    public function get_func($str_key)
    {
        return $this->hash_map->get($str_key);
    }
    
    public function insert_func($str_key,$object)
    {
        $this->hash_map->put($str_key,$object);
    }
    
    public function bind_func($func_array) 
    {
        if(count($func_array)==0)
        {
            return ;
        }
        
        $this->hash_map = new hash_map();
        foreach ($func_array as $key=>$value)
        {
            if(file_exists($value['file_name']))
            {
                include_once $value['file_name'];
            } else {
                continue;
            }
            
            if(function_exists($value["func"]))
            {
                $this->insert_func($value["url"],$value["func"]);
            }else {
                echo "function not exist!!";
            }            
        }
    }    
}

$conf= require 'config.php';
$http_server=new http_server();
$route_conf= require 'route_config.php';
if(!isset($route_conf['user']))
{
    echo "route config error";
}
$http_server->bind_func($route_conf['user']);
$http_server->start_server("192.168.2.235",8080);


<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
include_once './common/hash_map.php';
include_once './common/db_base.php';
require('vendor/autoload.php');
require('logger.php');

 class http_server
{
    private $hash_map;
    private $server;
    public $logger;

    public function __destruct ()
    {
        $this->hash_map->clear();
    }

    public function init ()
    {
        $this->logger = new logger(__DIR__.'/logs');        
    }

    public function get__model($model_name)
    {
        return $this->hash_map->get($model_name);
    }
    
    public function bind_model($model_array) 
    {
        if(count($model_array)==0)
        {
            return ;
        }
        
        if($this->hash_map==null){
            $this->hash_map = new hash_map();
        }

        foreach ($model_array as $key=>$value)
        {
            if(file_exists($value['file_name']))
            {
                include_once $value['file_name'];
            } else {
                continue;
            }

            if(class_exists($value["model_name"]))
            {
                //
                $object=new $value["model_name"]();
                $object->init();
                $this->hash_map->put($value["model_name"],$object);
            }else {
                echo $value['file_name']."   ".$value["func"]."\r\n";
                echo "function not exist!!";
            }            
        }
    }    
    
    public function start_server($server_ip,$port)
    {
        $this->server = new swoole_http_server($server_ip, $port);
        $this->server->set(array(
        'worker_num' => 6,
        'max_request' => 10000,
        'max_conn' => 100000,
        'dispatch_mode' => 2,
        'debug_mode'=> 1,
        'daemonize' => false));

        $this->server->on('WorkerStart', function ($serv, $worker_id){
            global $argv;
            $route_conf= require 'route_config.php';
            $this->init();
            $this->bind_model($route_conf);

            if($worker_id >= $this->server->setting['worker_num']) {
                swoole_set_process_name("php {$argv[0]} task worker");
            } else {
                swoole_set_process_name("php {$argv[0]} event worker");
            }
        });

        $this->server->on('Request', function($request, $response) {
            $url=$request->server["request_uri"];
            $response->cookie("User", "Swoole");
            $response->header("X-Server", "Swoole");
            $response->header("Access-Control-Allow-Origin", "*");
            $pathinfo=$request->server["path_info"];

            if ($request->server["request_method"]=="GET")
            {       
                 $this->logger->info($func."   ".json_encode($request->get));   
            } else{
                 $this->logger->info($func."   ".json_encode($request->post));   
            }
            //print_var($request);
            $request_info = explode('/', $url);
            if(count($request_info)==3){
                $obj=$this->get__model($request_info[1]);
                if($obj!=null && method_exists ($request_info[1],$request_info[2])){
                    $result=$obj->$request_info[2]($request, $response);
                    $response->end($result);
                }else{
                    $response-> status(404);
                    $this->logger->info($func."  response 404 not found");   
                    $response->end("404 not found");
                    return;
                }
            }else {
                $response-> status(404);
                $this->logger->info($func."  response 404 not found");   
                $response->end("404 not found");
                return;
            } 
        }); 
        echo "Https Server is start at ".date("Y-m-d H:i:s")." on port ".$port."\r\n";
        $this->server->start();
    }
}

$conf= require 'config.php';
$http_server=new http_server();
$route_conf= require 'route_config.php';
if(count($route_conf)==0)
{
    echo "route config error";
    return;
}
$http_server->start_server($conf["http"]["host"],$conf["http"]["port"]);


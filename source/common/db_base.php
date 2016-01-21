<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
include_once 'db_mysql_pdo.php';
include_once 'hash_map.php';
class db_base
{
    private  $db_mysql;
    private  $redis;
    public function connect_db($conf,$db,$redis="redis") 
    {
        $this->db_mysql=new db_mysql_pdo( array($conf[$db]));

        $this->redis= new Redis();
        return $this->redis->connect($conf[$redis]['host'],$conf[$redis]['port'] );        
    }
    
    public function  setnx($strKey,$value)
    {
        $this->redis->setnx($strKey,$value);        
    }

    public function lock($str_key)
    {
        $lock=0;
        while ($lock!=0)
        {
            $time_now=(int)(microtime(true)*1000);
            $lock=$this->setnx($str_key, $time_now+1000);
            if($lock==1 || ($time_now>$this->get($str_key) && $time_now>$this->getset($str_key,$time_now+1000)))
            {
                echo "lockeed...\r\n";
                break;
            }
            else
            {
                usleep(10);
            }
        }
        return $lock;
    }
    
    public function unlock($str_key)
    {
        $this->delete($str_key);        
    }

    public function  set($strKey,$value)
    {
        $this->redis->set($strKey,$value);
        //一周失效
        $this->redis->setTimeout($strKey, 60*60*24*7);
    }
    
    public function  get($strKey)
    {
        return $this->redis->get($strKey);
    }
    
    public function  delete($strKey)
    {
        $this->redis->delete($strKey);        
        while ($this->redis->exists($strKey))
        {
            $this->redis->delete($strKey);        
        }
    }

    public function  lSize($strKey)
    {
        return $this->redis->lSize($strKey);
    }

    public function  lGet($strKey,$index)
    {
        return $this->redis->lGet($strKey,$index);
    }
    
    public function  lrem($strKey,$value,$index)
    {
        return $this->redis->lrem($strKey,$value,$index);
    }
    
    public function  lPush($strKey,$value)
    {
        $this->redis->lPush($strKey,$value);        
    }

    public function  rPush($strKey,$value)
    {
        $this->redis->rPush($strKey,$value);        
    }
    
    public function  lPop($strKey)
    {
        return $this->redis->lPop($strKey);        
    }

    public function  lSet($strKey,$index,$value)
    {
        return $this->redis->lSet($strKey,$index,$value);
    }
    
    /**
     * 把值保存到键对应的散列表中指定的field中
     * @param unknown $key
     * @param unknown $field
     * @param unknown $value
     */
    public function hSet($key,$field,$value)
    {
        return $this->redis->hSet($key,$field,$value);
    }
    
    
    /**
     * 返回键对应的散列表中指定的field对应的值
     * @param unknown $key
     * @param unknown $field
     */
    public function hGet($key,$field)
    {
        return $this->redis->hGet($key,$field);
    }
    
    
    /**
     * 返回键对应的的散列表中所有field和value
     * @param unknown $key
     */
    public function hGetAll($key)
    {
        return $this->redis->hGetAll($key);
    }
    
    /**
     * 键对应的散列表中存在指定的field的时候返回"1"，不存在的时候返回"0"
     * @param unknown $key
     * @param unknown $field
     */
    public function hExists($key,$field)
    {
        return $this->redis->hExists($key,$field);
    }
    

    /**
     * 删除键对应的散列表中的filed
     * @param unknown $key
     * @param unknown $field
     */
    public function hDel($key,$field)
    {
        return $this->redis->hDel($key,$field);
    }
    
    /**
     * 模糊查询key是否存在
     * @param unknown $key
     */
    public function keysExists($key)
    {
        return $this->redis->keys($key);
    }

    public function get_timestamp()
    {
        $str_sql="select unix_timestamp()";
        try
        {
            $timestamp=$this->db_mysql->fetchOne($str_sql);
            return $timestamp;
        }
        catch (Exception $ex)
        {
            echo $ex->getMessage()."\r\n";
        }
        return 0;    
    }
    
    public function query($str_sql)
    {
        return $this->db_mysql->query($str_sql);
    }
    
    public function insertId()
    {
        return $this->db_mysql->insertId();
    }
    
    public function insert($table,$data) 
    {
       return $this->db_mysql->insert($table,$data);
    }
    
    public function update ($table, $data, $where = '', $checkField = false)
    {
        return $this->db_mysql->update($table,$data,$where,$checkField);
    }

    /**
     * 返回sql语句的第一个单元的数据
     * @param unknown $str_sql:查询sql
     */
    public function fetchOne($str_sql)
    {
        return $this->db_mysql->fetchOne($str_sql);
    }
    
    /**
     *  去sql语句查询结果的第一行
     * @param unknown $str_sql:查询sql
     */
    public function fetchRow($str_sql)
    {
        return $this->db_mysql->fetchRow($str_sql);
    }
    
    /**
     *  去sql语句查询结果的第一行
     * @param unknown $str_sql:查询sql
     */
    public function fetchAll($str_sql)
    {
        return $this->db_mysql->fetchAll($str_sql);
    }     
}    
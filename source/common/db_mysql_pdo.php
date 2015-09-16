<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * mysql连接类，包装了一些原始操作，支持简单的读写分离
 *
 * @author tiandiou
 *
 */
class db_mysql_pdo 
{
    /**
     * 构造函数
     * 由于该连接对象支持数据库集群，所以$params是一个二维数组，指向多台服务器
     * 构造时，按照读写性将服务器分组
     *
     * @param $params 数据库服务器的连接参数
     * @return unknown_type
     */
    public function __construct ($params)
    {
        for ($i = 0; $i < sizeof($params); $i ++) {
            if (! isset($params[$i]['rw'])) {
                $params[$i]['rw'] = 'rw';
            }
            $this->_params[$params[$i]['rw']][] = $params[$i];
            $this->_params['all'][] = $params[$i];
        }
    }
    /**
     * 根据读写性要求设置一个适合的pdo对象
     *
     * @param $rw 服务器的读写性
     * @return object
     */
    // array('host' => '127.0.0.1', 'port'=>3306,'dbname'=>'card_db' );
    public function getConnection ($rw = 'rw')
    {
        $params = $this->_params;
        //如果是只读查询，从所有服务器中随机挑选一台
        if ($rw == 'r') {
            $rw = 'all';
        }
        if (isset($this->_pdos[$rw]) && $this->_pdos[$rw] instanceof PDO) {
            $this->_pdo = $this->_pdos[$rw];
            return $this->_pdo;
        }
        //随机从满足读写性要求的服务器中挑选出一台服务器
        $index = rand(0, sizeof($params[$rw]) - 1);
        $params = $params[$rw][$index];
        if(!isset($params['port'])){
            $params['port'] = '3306';
        }
        $dsn = "mysql:host={$params['host']};port={$params['port']};dbname={$params['dbname']}";
        $pdo = new PDO($dsn, $params['username'], $params['password']);
        if ($params['persistent']) {
            $pdo->setAttribute(PDO::ATTR_PERSISTENT, true);
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("SET NAMES {$params['charset']}");
        $this->_pdo = $pdo;
        $this->_pdos[$rw] = $pdo;
        return $this->_pdo;
    }
    /**
     * 执行一次数据库查询
     * @param $sql
     * @param $bindValue 绑定到sql语句中的关联数组
     * @return object
     */
    public function query ($sql, $bindValue = array())
    {
        if ($this->isReading($sql)) {
            $mode = 'r';
        } else {
            $mode = 'rw';
        }
        try {
            $this->getConnection($mode);
            $stmt = $this->_pdo->prepare($sql);
            if (empty($bindValue)) {
                foreach ($bindValue as $key => $val) {
                    $stmt->bindValue(':' . $key, $val);
                }
            }
            $stmt->execute();
            return $stmt;
        }
        catch (Exception $e) 
        {
            if($e->errorInfo[1] == 70100 || $e->errorInfo[1] == 2006)
            {  
                 $count = 0;  
                 unset( $this->_pdo);
                 unset( $this->_pdos);
                 while(!$this->getConnection() && $count<10)
                {  
                     sleep(1);  
                     echo "数据库重新连接失败(try:{$count})\n";  
                     $count++;  
                 }
                 
                 if($count==5)
                 {
                    throw new Exception($e->getMessage());
                 }
                 else
                 {
                     echo "数据库重新连接成功(try:{$count})\n";
                     return $this->query($sql, $bindValue);  
                 }
             }
             else
             {
                    throw new Exception($e->getMessage());
             }
        }
    }
    /**
     * 取上一次插入的自增id
     *
     * @return int
     */
    public function insertId ()
    {
        return $this->_pdo->lastInsertId();
    }
    /**
     * 去sql语句对应的所有数据，一般为2维数组
     *
     * @param $sql
     * @return array
     */
    public function fetchAll ($sql, $limit = null, $offset = 0)
    {
        $sql = $this->limit($sql, $limit, $offset);
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * 取第一维为关联数组，key为sql语句中第一个字段
     *
     * @param $sql
     * @param $limit
     * @param $offset
     * @return array
     */
    public function fetchGroup ($sql, $field)
    {
        $set = $this->fetchAll($sql);
        $t = array();
        foreach ($set as $key => $val) {
            $t[$val[$field]][] = $val;
        }
        return $t;
    }
    /**
     * 以sql中某一列作为键返回结果
     *
     * @param $sql
     * @param $field
     * @return aray
     */
    public function fetchAssoc ($sql, $field)
    {
        $set = $this->fetchAll($sql);
        $t = array();
        foreach ($set as $key => $val) {
            $t[$val[$field]] = $val;
        }
        return $t;
    }
    public function fetchCol ($sql, $field)
    {
        $set = $this->fetchAssoc($sql, $field);
        return array_keys($set);
    }
    /**
     * 去sql语句查询结果的第一行
     *
     * @param $sql
     * @return array
     */
    public function fetchRow ($sql)
    {
        if (! preg_match('/\slimit\s/', $sql)) {
            $sql = $this->limit($sql, 1);
        }
        $stmt = $this->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    /**
     * 取sql语句的第一个单元的数据
     *
     * @param $sql
     * @return string
     */
    public function fetchOne ($sql)
    {
        $row = $this->fetchRow($sql);
        if (empty($row)) {
            return null;
        }
        foreach ($row as $val) {
            return $val;
        }
    }
    /**
     * 将sql语句的查询结果中的两个字段拼成关联数据返回
     *
     * @param $sql
     * @param $key 作为关联数组的key的列列名
     * @param $val 作为关联数组的value的列列名
     * @return array
     */
    public function fetchKeyValue ($sql, $key, $val, $limit = null, $offset = 0)
    {
        $rows = $this->fetchAll($sql);
        $r = array();
        foreach ($rows as $row) {
            $r[$row[$key]] = $row[$val];
        }
        return $r;
    }
    /**
     * 为sql语句添加limit
     *
     * @param $sql
     * @param $limit
     * @param $offset
     * @return string
     */
    public function limit ($sql, $limit, $offset = 0)
    {
        if ($limit === null) {
            return $sql;
        }
        $offset = (($offset < 0) ? 0 : $offset);
        return $sql .= " LIMIT {$limit} OFFSET {$offset}";
    }
    /**
     * 插入操作
     *
     * @param string $table
     * @param array $data
     * @return int
     */
    public function insert ($table, $data = array(), $checkField = false)
    {
        $checkField = $checkField ? $table : null;
        $set = $this->getSetList($data, ',', $checkField);
        $sql = "INSERT INTO {$table} SET {$set}";
        $this->query($sql);
        return $this->_pdo->lastInsertId();
    }
    /**
     * 更新
     *
     * @param $table
     * @param $data
     * @param $where
     * @param $checkField 是否检查$data与数据库匹配，检查将会增加一次数据查询
     * @return int
     */
    public function update ($table, $data, $where = '', $checkField = false)
    {
        $set = '';
        $checkField = $checkField ? $table : null;
        $set = $this->getSetList($data, ',', $checkField);
        $sql = "UPDATE {$table} SET {$set}";
        if (! empty($where)) {
            $sql .= " WHERE {$where}";
        }
        $stmt = $this->query($sql);
        return $stmt->rowCount();
    }
    /**
     * 删除满足where条件的记录
     *
     * @param $table
     * @param $where
     * @return int
     */
    public function delete ($table, $where = '')
    {
        $sql = "DELETE FROM {$table}";
        if (! empty($where)) {
            $sql .= " WHERE {$where}";
        }
        $stmt = $this->query($sql);
        return $stmt->rowCount();
    }
    /**
     * 将关联数组组合为 key1 = value1,key2 = value2....的格式
     *
     * @param array $data
     * @param string $connector
     * @return string
     */
    public function getSetList ($data, $connector = ',', $checkField = null)
    {
        $set = $comma = '';
        foreach ($data as $key => $value) {
            if (! empty($checkField)) {
                $fields = $this->getTableFields($checkField);
                if (! in_array($key, $fields)) {
                    continue;
                }
            }
            if (preg_match('/^[\w\d_]+\s*\(.*?\)$/i', $value)) { //如果是函数调用，则不加单引号
                $set .= " {$comma} {$key} = {$value}";
            } else {
                $value = addslashes(stripslashes($value));
                $set .= " {$comma} {$key} = '{$value}'";
            }
            $comma = $connector;
        }
        return $set;
    }
    /**
     * 取表的字段名，以数组形式返回
     *
     * @param $table
     * @return array
     */
    public function getTableFields ($table)
    {
        $allFields = $this->fetchAll("SHOW COLUMNS FROM {$table}");
        foreach ($allFields as $field) {
            $fields[] = $field['Field'];
        }
        return $fields;
    }
    public function getTableInfo ($table)
    {
        return $this->fetchAssoc("SHOW COLUMNS FROM {$table}", 'Field');
    }
    /**
     * 析构函数，关闭连接
     *
     * @return unknown_type
     */
    public function __destruct ()
    {
        $this->_pdo = null;
    }
    /**
     * 通过sql第一个单词简单判断sql语句是否可以执行，不一定安全
     *
     * @param $sql
     * @return bool
     */
    public function isReading ($sql)
    {
        preg_match('/^\s*([^\s]+)/', $sql, $regs);
        if (! isset($regs[1]) || empty($regs[1])) {
            throw new Exception('Unknow SQL command');
        }
        $cmd = strtolower($regs[1]);
        $allow = array('select', 'show', 'set', 'desc', 'describle', 'use');
        if (in_array($cmd, $allow, true)) {
            return true;
        }
        return false;
    }
}

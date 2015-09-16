<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class  hash_map
{
    var $m_table;

 
    public function __construct()
    {
        $this->m_table = array ();
    }
 
    public function put($key, $value)
    {
        if (!array_key_exists($key, $this->m_table))
        {
            $this->m_table[$key] = $value;
            return null;
        }
        else
        {
            $tempValue = $this->m_table[$key];
            $this->m_table[$key] = $value;
            return $tempValue;
        }
     }
 
 
    public function get($key)
   {
      if (array_key_exists($key, $this->m_table))
           return $this->m_table[$key];
       return null;
   }

    public function remove($key) 
    {
        if(array_key_exists($key, $this->m_table))
        {
            unset($this->m_table[$key]);
        }
        return $this->m_table;
    }
  
    public function keys()
    {
        return array_keys($this->m_table);
    }
 
    public function values()
    {
        return array_values($this->m_table);
    }
  
    public function put_all($map)
    {
       if(!$map->isEmpty()&& $map->size()>0)
        {
            $keys = $map->keys();
            foreach($keys as $key)
            {
                $this->put($key,$map->get($key));
            }
       }
    }
  
    public function clear()
    {
        unset($this->m_table);
        //$this->m_table = null;
        $this->m_table = array ();
    }
 
    public function contains_value($value)
    {
        while ($curValue = current($this->m_table)) 
        {
            if ($curValue == $value)
            {
                return true;
            }
            next($this->m_table);
        }
        return false;
    }

    public function containsKey($key)
    {
        if (array_key_exists($key, $this->m_table))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
 
    public function size()
    {
        return count($this->m_table);
    } 
    
    public function is_empty()
    {
       return (count($this->m_table) == 0);
    }

    public function toString()
    {
        print_r($this->m_table);
    }
}

?>
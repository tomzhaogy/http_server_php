<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class test extends db_base
{
	public function init ()
	{
		$conf= require 'config.php';
		$this->connect_db($conf,"db","redis");
	}

	function sys( $request, $response) {
	//return "<h1>Test "."  Tomzhao!</h1>";
	return "<h1>Test ".date("Y-m-d H:i:s")."  Tomzhao!</h1>";
	}

	function hello($request, $response) {
	 return "<h1>Hello ".date("Y-m-d H:i:s")."  Tomzhao!</h1>";
	}
}


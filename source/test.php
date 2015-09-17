<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function test( $db_read,$request, $response) {
    return "<h1>Test ".$db_read->get_timestamp()."  Tomzhao!</h1>";
    //return "<h1>Test ".date("Y-m-d H:i:s")."  Tomzhao!</h1>";
}

function hello($db_read,$request, $response) {
    return "<h1>Hello ".$db_read->get_timestamp()."  Tomzhao!</h1>";
   // return "<h1>Hello ".date("Y-m-d H:i:s")."  Tomzhao!</h1>";
}
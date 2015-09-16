<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function test($request, $response) {
    return "<h1>Test ".date("Y-m-d H:i:s")."  Tomzhao!</h1>";
}

function hello($request, $response) {
    return "<h1>Hello ".date("Y-m-d H:i:s")."  Tomzhao!</h1>";
}
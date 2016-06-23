<?php
require_once('../PayParts.php');
require_once('params.php');

$storeId = $params['StoreId'];                //Идентификатор магазина
$password = $params['Password'];              //Пароль вашего магазина

$pp = new PayParts($StoreId, $Password);

$getState = $pp->getState($_GET['ORDER'], false); //orderId, showRefund

var_dump($getState);
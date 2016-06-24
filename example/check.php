<?php

require __DIR__ . '/vendor/autoload.php';

use PayParts\PayParts;

$params = require_once('params.php');

$storeId = $params['StoreId'];                //Идентификатор магазина
$password = $params['Password'];              //Пароль вашего магазина

$pp = new PayParts($storeId, $password);

$getState = $pp->getState($_GET['ORDER'], false); //orderId, showRefund

var_dump($getState);
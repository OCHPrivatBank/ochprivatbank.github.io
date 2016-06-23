<?php
require_once('../PayParts.php');

$StoreId = '01841655274A4951BBAF';               //Идентификатор магазина
$Password = '6b9ac727dae5484980db6a177537869f';  //Пароль вашего магазина

$pp = new PayParts($StoreId, $Password);

$getState = $pp->getState($_GET['ORDER'], false); //orderId, showRefund

var_dump($getState);
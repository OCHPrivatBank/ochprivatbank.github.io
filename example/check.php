<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 16.05.2016
 * Time: 13:41
 */

require_once('class/PayParts.php');

$StoreId = '01841655274A4951BBAF';               //Идентификатор магазина
$Password = '6b9ac727dae5484980db6a177537869f';  //Пароль вашего магазина


$pp = new PayParts($StoreId, $Password);


$getState = $pp->getState($_GET['ORDER'], false); //orderId, showRefund

var_dump($getState);
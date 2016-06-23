<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 16.05.2016
 * Time: 14:19
 */


require_once('class/PayParts.php');
$StoreId = '01841655274A4951BBAF';               //Идентификатор магазина
$Password = '6b9ac727dae5484980db6a177537869f';  //Пароль вашего магазина


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = file_get_contents("php://input");

    $file = 'Log\CallBack.log';
    $myfile = fopen($file, "a") or die("Unable to open file!");
    fwrite($myfile, "\n". $data);
    fclose($myfile);



/*    $current = file_get_contents($file);
    $current .= "$data\n";
    file_put_contents($file, $current);*/

    $pp = new PayParts($StoreId, $Password);
    $ar = $pp->checkCallBack($data);


}
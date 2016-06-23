<?php
require_once('../PayParts.php');
require_once('params.php');

$storeId = $params['StoreId'];                //Идентификатор магазина
$password = $params['Password'];              //Пароль вашего магазина

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = file_get_contents('php://input');

    $file = 'log\callback.log';
    $myFile = fopen($file, 'a') or die('Unable to open file!');
    fwrite($myFile, "\n" . $data);
    fclose($myFile);

    /*  $current = file_get_contents($file);
        $current .= "$data\n";
        file_put_contents($file, $current);*/

    $pp = new PayParts($storeId, $password);
    $ar = $pp->checkCallBack($data);


}
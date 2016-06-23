<?php
require_once('../PayParts.php');

$storeId = '01841655274A4951BBAF';               //Идентификатор магазина
$password = '6b9ac727dae5484980db6a177537869f';  //Пароль вашего магазина

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
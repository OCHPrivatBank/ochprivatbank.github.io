<?php
header('Content-Type: text/html; charset=utf-8');
require_once('class/PayParts.php');

session_start();


$ProductsList=array(
                array(  "name" => "Телевизор",
                        "count" => 2,
                        "price" => 100.00),

                array(  "name" => "Микроволновка",
                        "count" => 1,
                        "price" => 200.00)
);


$_SESSION['StoreId'] ='01841655274A4951BBAF';               //Идентификатор магазина
$_SESSION['Password']='6b9ac727dae5484980db6a177537869f';  //Пароль вашего магазина

$options=array( 'ResponseUrl'   =>  'http://10.1.39.51/response.php',                       //URL, на который Банк отправит результат сделки (НЕ ОБЯЗАТЕЛЬНО)
                'RedirectUrl'   =>  'http://10.1.39.51/redirect.php',                       //URL, на который Банк сделает редирект клиента (НЕ ОБЯЗАТЕЛЬНО)
                'PartsCount'    =>  5,                                                      //Количество частей на которые делится сумма транзакции ( >1)
                'Prefix'        =>  '',                                                     //параметр не обязательный если Prefix указан с пустотой или не указа вовсе префикс будет ORDER
                'OrderID'       =>  '',                                                     //если OrderID задан с пустотой или не укан вовсе OrderID сгенерится автоматически
                'merchantType'  =>  'PP',                                                   //II - Мгновенная рассрочка; PP - Оплата частями
                'Currency'      =>  '',                                                     //можна указать другую валюту 980 – Украинская гривна; 840 – Доллар США; 643 – Российский рубль. Значения в соответствии с ISO
                'ProductsList'  =>  $ProductsList,                                          //Список продуктов, каждый продукт содержит поля: name - Наименование товара price - Цена за еденицу товара (Пример: 100.00) count - Количество товаров данного вида
                'recipientId'   => ''                                                       //Идентификатор получателя, по умолчанию берется основной получатель. Установка основного получателя происходит в профиле магазина.
);



 if(isset($_POST['send']))
 {
     $pp= new PayParts($_SESSION['StoreId'], $_SESSION['Password']);

     $pp->SetOptions($options);

     $send = $pp->Create('hold');//hold //pay

     $_SESSION['OrderID'] = $pp->getLOG()['OrderID'];
     //var_dump($pp->getLOG());
     header('Location: https://payparts2.privatbank.ua/ipp/v2/payment?token='.$send['token']);
     exit;

 }


?>

<form action="" method="POST" xmlns="http://www.w3.org/1999/html">
    <table border="1">
        <tr>
            <td>Наименование товара</td>
            <td>Колличество</td>
            <td>Цена</td>

        </tr>
        <? foreach ($ProductsList as $product) {
            echo '<tr>';
            foreach ($product as $item => $value) {
                echo "<td> $value </td>";
            }
            echo '</tr>';
        } ?>

    </table>
    <input name="send" type="submit" value="Оплатить"/>
</form>



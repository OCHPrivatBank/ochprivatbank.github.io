<?php

namespace PayParts;

use InvalidArgumentException;

/**
 * Класс для оплаты частями от Приват Банк
 * @package PayParts
 */
class PayParts
{
    private $prefix = 'ORDER';
    private $orderID;              // Уникальный номер платежа
    private $storeId;              // Идентификатор магазина
    private $password;             // Пароль вашего магазина
    private $PartsCount;           // Количество частей на которые делится сумма транзакции ( >1 )
    private $MerchantType;         // Тип кредита
    private $responseUrl;          // URL, на который Банк отправит результат сделки
    private $redirectUrl;          // URL, на который Банк сделает редирект клиента
    private $amount;               // Окончательная сумма покупки, без плавающей точки
    private $ProductsList;
    private $recipientId;
    private $currency;
    private $productsString;
    private $payURL = 'https://payparts2.privatbank.ua/ipp/v2/payment/create';
    private $holdURL = 'https://payparts2.privatbank.ua/ipp/v2/payment/hold';
    private $stateURL = 'https://payparts2.privatbank.ua/ipp/v2/payment/state';
    private $confirmHoldURL = 'https://payparts2.privatbank.ua/ipp/v2/payment/confirm';
    private $cancelHoldUrl = 'https://payparts2.privatbank.ua/ipp/v2/payment/cancel';

    private $LOG = array();

    private $keysProducts = ['name', 'count', 'price'];

    private $options = ['PartsCount', 'MerchantType', 'ProductsList'];

    /**
     * PayParts constructor.
     * создаём идентификаторы магазина
     *
     * @param string $StoreId - Идентификатор магазина
     * @param string $Password - Пароль вашего магазина
     * @throws \InvalidArgumentException
     */
    public function __construct($StoreId, $Password)
    {
        $this->setStoreId($StoreId);
        $this->setPassword($Password);
    }

    /**
     * PayParts SetOptions.
     * @param array $options
     *
     * ResponseUrl - URL, на который Банк отправит результат сделки (НЕ ОБЯЗАТЕЛЬНО)<br>
     * RedirectUrl - URL, на который Банк сделает редирект клиента (НЕ ОБЯЗАТЕЛЬНО)<br>
     * PartsCount - Количество частей на которые делится сумма транзакции ( >1)<br>
     * Prefix - параметр не обязательный если Prefix указан с пустотой или не указа вовсе префикс будет ORDER<br>
     * OrderID' - если OrderID задан с пустотой или не укан вовсе OrderID сгенерится автоматически<br>
     * MerchantType - II - Мгновенная рассрочка; PP - Оплата частями; PB - Оплата частями. Деньги в периоде. IA - Мгновенная рассрочка. Акционная.<br>
     * Currency' - Валюта по умолчанию 980 – Украинская гривна; Значения в соответствии с ISO<br>
     * ProductsList - Список продуктов, каждый продукт содержит поля: name - Наименование товара price - Цена за еденицу товара (Пример: 100.00) count - Количество товаров данного вида<br>
     * recipientId - Идентификатор получателя, по умолчанию берется основной получатель. Установка основного получателя происходит в профиле магазина.
     * @throws InvalidArgumentException - Ошибка установки аргумента
     */
    public function setOptions(array $options)
    {
        if (empty($options) || !is_array($options)) {
            throw new InvalidArgumentException('Options must by set as array');
        } else {

            foreach ($options as $PPOptions => $value) {
                if (method_exists('PayParts\PayParts', "set$PPOptions")) {
                    call_user_func_array(array($this, "set$PPOptions"), array($value));
                } else {
                    throw new InvalidArgumentException($PPOptions . ' cannot be set by this setter');
                }
            }
        }

        $flag = 0;

        foreach ($this->options as $variable) {
            if (isset($this->{$variable})) {
                ++$flag;
            } else {
                throw new InvalidArgumentException($variable . ' is necessary');
            }
        }
        if ($flag === count($this->options)) {
            $this->options['SUCCESS'] = true;
        } else {
            $this->options['SUCCESS'] = false;
        }
    }

    /**
     * PayParts Create.
     * Создание платежа
     *
     * @param string $method
     * <a href="https://bw.gitbooks.io/api-oc/content/pay.html">'hold'</a> - Создание платежа без списания<br>
     * <a href="https://bw.gitbooks.io/api-oc/content/hold.html">'pay'</a> - Создание платежа со списанием
     * @return mixed|string
     * @throws \InvalidArgumentException
     */
    public function create($method = 'pay')
    {
        if ($this->options['SUCCESS']) {

            //проверка метода
            if ($method === 'hold') {
                $Url = $this->holdURL;
                $this->LOG['Type'] = 'Hold';
            } else {
                $Url = $this->payURL;
                $this->LOG['Type'] = 'Pay';
            }

            $SignatureForCall = [
                $this->password,
                $this->storeId,
                $this->orderID,
                (string)($this->amount * 100),
                $this->PartsCount,
                $this->MerchantType,
                $this->responseUrl,
                $this->redirectUrl,
                $this->productsString,
                $this->password
            ];

            $param['storeId'] = $this->storeId;
            $param['orderId'] = $this->orderID;
            $param['amount'] = $this->amount;
            $param['partsCount'] = $this->PartsCount;
            $param['merchantType'] = $this->MerchantType;
            $param['products'] = $this->ProductsList;
            $param['responseUrl'] = $this->responseUrl;
            $param['redirectUrl'] = $this->redirectUrl;
            $param['signature'] = $this->calcSignature($SignatureForCall);

            if (!empty($this->currency)) {
                $param['currency'] = $this->currency;
            }

            if (!empty($this->recipientId)) {
                $param['recipient'] = array('recipientId' => $this->recipientId);
            }


            $this->LOG['CreateData'] = json_encode($param);

            $CreateResult = json_decode($this->sendPost($param, $Url), true);

            $checkSignature = [
                $this->password,
                $CreateResult['state'],
                $CreateResult['storeId'],
                $CreateResult['orderId'],
                $CreateResult['message'],
                $CreateResult['token'],
                $this->password
            ];

            $this->LOG['CreateResult'] = json_encode($CreateResult);

            if ($this->calcSignature($checkSignature) === $CreateResult['signature']) {
                return $CreateResult;
            } else {
                return 'error';
            }

        } else {
            throw new InvalidArgumentException('No options');
        }

    }


    /**
     * PayParts getState.
     * <a href="https://bw.gitbooks.io/api-oc/content/state.html">Получение результата сделки</a>
     * @param string $orderId -Уникальный номер платежа
     * @param bool $showRefund true - получить детали возвратов по платежу,<br> false - получить статус платежа без дополнительных деталей о возвратах
     * @return mixed|string
     */
    public function getState($orderId, $showRefund = true)
    {
        $SignatureForCall = [$this->password, $this->storeId, $orderId, $this->password];

        $data = array(
            'storeId' => $this->storeId,
            'orderId' => $orderId,
            'showRefund' => var_export($showRefund, true), //($showRefund) ? 'true' : 'false'
            'signature' => $this->calcSignature($SignatureForCall)
        );

        $res = json_decode($this->sendPost($data, $this->stateURL), true);

        $ResSignature = [
            $this->password,
            $res['state'],
            $res['storeId'],
            $res['orderId'],
            $res['paymentState'],
            $res['message'],
            $this->password
        ];

        if ($this->calcSignature($ResSignature) === $res['signature']) {
            return $res;
        } else {
            return 'error';
        }
    }


    /**
     * PayParts checkCallBack.
     * Получение результата сделки (асинхронный коллбэк)
     *
     * @param string $string результат post запроса
     * @return mixed|string валидирует и отдаёт ответ
     */
    public function checkCallBack($string)
    {
        $sa = json_decode($string, true);

        $srt = [$this->password, $this->storeId, $sa['orderId'], $sa['paymentState'], $sa['message'], $this->password];

        if ($this->calcSignature($srt) === $sa['signature']) {
            return $sa;
        } else {
            return 'error';
        }

    }

    /**
     * PayParts ConfirmHold.
     * <a href="https://bw.gitbooks.io/api-oc/content/confirm.html">Подтверждение платежа</a>
     *
     * @param string $orderId Уникальный номер платежа
     * @return mixed|string
     */
    public function confirmHold($orderId)
    {
        $signatureForConfirmHold = [$this->password, $this->storeId, $orderId, $this->password];

        $data = array(
            'storeIdentifier' => $this->storeId,
            'orderId' => $orderId,
            'signature' => $this->calcSignature($signatureForConfirmHold)
        );

        return json_decode($this->sendPost($data, $this->confirmHoldURL), true);

        /* Проверка временно не доступна, в связи с отсутствием реализации на стороне API

        $ResSignature = array($this->Password, $res['storeIdentifier'], $res['orderId'], $this->Password);

        if ($this->CalcSignature($ResSignature) === $res['signature']) {
            return $res;
        } else {
            return 'error';
        }*/
    }

    /**
     * PayParts CancelHold.
     * <a href="https://bw.gitbooks.io/api-oc/content/cancel.html">Отмена платежа</a>
     *
     * @param string $orderId Уникальный номер платежа
     * @param string $recipientId Идентификатор получателя, по умолчанию берется основной получатель. Установка основного получателя происходит в профиле магазина.
     * @return mixed|string
     */
    public function cancelHold($orderId, $recipientId = '')
    {

        $signatureForCancelHold = [$this->password, $this->storeId, $orderId, $this->password];

        $data = array(
            'storeId' => $this->storeId,
            'orderId' => $orderId,
            'signature' => $this->calcSignature($signatureForCancelHold)
        );
        if (!empty($recipientId)) {
            $data['recipientId'] = $recipientId;
        }

        return json_decode($this->sendPost($data, $this->cancelHoldUrl), true);

        /* Проверка временно не доступна, в связи с отсутствием реализации на стороне API

        $ResSignature = array($this->Password, $res['storeIdentifier'], $res['orderId'], $this->Password);

        if ($this->CalcSignature($ResSignature) === $res['signature']) {
            return $res;
        } else {
            return 'error';
        }*/
    }

    /**
     * PayParts getLOG. частичный лог
     *
     * @return array
     */
    public function getLOG()
    {
        return $this->LOG;
    }

    /**
     * Вычисление сигнатуры
     *
     * @param array $array
     * @return string
     */
    private function calcSignature($array)
    {
        $signature = '';
        foreach ($array as $item) {
            $signature .= $item;
        }
        return base64_encode(sha1($signature, true));

    }

    /**
     * Send POST
     *
     * @param $param
     * @param $url
     * @return mixed
     */
    private function sendPost($param, $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json; charset=utf-8'
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($param));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

        return curl_exec($ch);
    }

    /**
     * Setter for StoreId
     *
     * @param $argument
     * @throws \InvalidArgumentException
     */
    private function setStoreId($argument)
    {
        if (empty($argument)) {
            throw new InvalidArgumentException('StoreId is empty');
        }
        $this->storeId = $argument;
    }

    /**
     * Setter for Password
     *
     * @param $argument
     * @throws \InvalidArgumentException
     */
    private function setPassword($argument)
    {
        if (empty($argument)) {
            throw new InvalidArgumentException('Password is empty');
        }
        $this->password = $argument;
    }


    /**
     * Setter for ResponseUrl
     *
     * @param $argument
     */
    private function setResponseUrl($argument)
    {
        if (!empty($argument)) {
            $this->responseUrl = $argument;
        }
    }

    /**
     * Setter for RedirectUrl
     *
     * @param $argument
     */
    private function setRedirectUrl($argument)
    {
        if (!empty($argument)) {
            $this->redirectUrl = $argument;
        }
    }

    /**
     * Setter for PartsCount
     *
     * @param $argument
     * @throws \InvalidArgumentException
     */
    private function setPartsCount($argument)
    {
        if ($argument < 1) {
            throw new InvalidArgumentException('PartsCount cannot be <1');
        }
        $this->PartsCount = $argument;
    }

    /**
     * Setter for Prefix
     *
     * @param string $argument
     */
    private function setPrefix($argument = '')
    {
        if (!empty($argument)) {
            $this->prefix = $argument;
        }
    }

    /**
     * Setter for OrderID
     *
     * @param string $argument
     */
    private function setOrderID($argument = '')
    {
        if (empty($argument)) {
            $this->orderID = $this->prefix . '-' . strtoupper(sha1(time() . mt_rand(1, 99999)));
        } else {
            $this->orderID = $this->prefix . '-' . strtoupper($argument);
        }

        $this->LOG['OrderID'] = $this->orderID;
    }

    /**
     * Setter for RecipientId
     *
     * @param string $argument
     */
    private function setRecipientId($argument = '')
    {
        if (!empty($argument)) {
            $this->recipientId = $argument;
        }
    }

    /**
     * Setter for MerchantType
     *
     * @param $argument
     * @throws \InvalidArgumentException
     */
    private function setMerchantType($argument)
    {
        if (in_array($argument, array('II', 'PP', 'PB', 'IA'), false)) {
            $this->MerchantType = $argument;
        } else {
            throw new InvalidArgumentException('MerchantType must be in array(\'II\', \'PP\', \'PB\', \'IA\')');
        }
    }

    /**
     * Setter for Currency
     *
     * @param string $argument
     * @throws \InvalidArgumentException
     */
    private function setCurrency($argument = '')
    {
        if (!empty($argument)) {
            if (in_array($argument, array('980'), false)) {
                $this->currency = $argument;
            } else {
                throw new InvalidArgumentException('something is wrong with Currency');
            }
        }
    }

    /**
     * Setter for ProductList
     *
     * @param $argument
     * @throws \InvalidArgumentException
     */
    private function setProductsList($argument)
    {
        if (!empty($argument) && is_array($argument)) {
            foreach ($argument as $arr) {
                foreach ($this->keysProducts as $item) {
                    if (!array_key_exists($item, $arr)) {
                        throw new InvalidArgumentException("$item key does not exist");
                    }
                    if (empty($arr[$item])) {
                        throw new InvalidArgumentException("$item value cannot be empty");
                    }
                }

                $this->amount += $arr['count'] * $arr['price'];
                $this->productsString .= $arr['name'] . $arr['count'] . $arr['price'] * 100;
            }
            $this->ProductsList = $argument;
        } else {
            throw new InvalidArgumentException('something is wrong');
        }
    }

}
<?php

namespace PayParts\Tests;

error_reporting(E_ALL ^ E_NOTICE);

require_once(__DIR__ . DIRECTORY_SEPARATOR . '../src/PayParts/PayParts.php');

use PayParts\PayParts;

class PayPartsTest extends \PHPUnit_Framework_TestCase
{
    private $payParts;

    const STORE_ID = '7E2FF85BE494424A9FFE';
    const STORE_PASSWORD = '39d67eac8c344181b3fb44136e85992e';
    const OPTIONS = [
        'ResponseUrl' => '/response.php',
        'RedirectUrl' => '/redirect.php',
        'PartsCount' => 1,
        'Prefix' => 'ORDER',
        'OrderID' => '',
        'merchantType' => 'PP',
        'Currency' => '980',
        'ProductsList' => [
            [
                'name' => 'Телевизор',
                'count' => 2,
                'price' => 100.00
            ],
            [
                'name' => 'Микроволновка',
                'count' => 1,
                'price' => 200.00
            ]
        ],
        'recipientId' => '111'
    ];


    public function setUp()
    {
        $this->payParts = new PayParts(self::STORE_ID, self::STORE_PASSWORD);
        $this->payParts->setOptions(self::OPTIONS);
    }

    public function tearDown()
    {
        $this->payParts = null;
    }

    public function testPayPartsIsInit()
    {
        $this->assertTrue($this->payParts !== false);
    }

    /**
     * @expectedException  \InvalidArgumentException
     * @expectedExceptionMessage StoreId is empty
     */
    public function testStoreIdIsEmpty()
    {
        new PayParts(null, self::STORE_PASSWORD);
    }

    /**
     * @expectedException  \InvalidArgumentException
     * @expectedExceptionMessage Password is empty
     */
    public function testStorePasswordIsEmpty()
    {
        new PayParts(self::STORE_ID, null);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Options must by set as array
     */
    public function testSetOptionsIsEmpty()
    {
        $options = [];
        $pp = new PayParts(self::STORE_ID, self::STORE_PASSWORD);
        $pp->setOptions($options);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage SomeOption cannot be set by this setter
     */
    public function testSetOptionsHasWrongSetter()
    {
        $options = ['SomeOption' => 1];
        $pp = new PayParts(self::STORE_ID, self::STORE_PASSWORD);
        $pp->setOptions($options);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage artsCount cannot be <1
     */
    public function testSetOptionsPartsCountLessOne()
    {
        $options = [
            'PartsCount' => 0,
            'merchantType' => 'PP',
            'ProductsList' => self::OPTIONS['ProductsList']
        ];
        $pp = new PayParts(self::STORE_ID, self::STORE_PASSWORD);
        $pp->setOptions($options);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage something is wrong
     */
    public function testProductListIsEmpty()
    {
        $options = [
            'PartsCount' => 5,
            'merchantType' => 'PP',
            'ProductsList' => []
        ];
        $pp = new PayParts(self::STORE_ID, self::STORE_PASSWORD);
        $pp->setOptions($options);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage name value cannot be empty
     */
    public function testProductListHasEmptyValue()
    {
        $options = [
            'PartsCount' => 5,
            'merchantType' => 'PP',
            'ProductsList' => [
                [
                    'name' => '',
                    'count' => 2,
                    'price' => 100.00
                ]
            ]
        ];
        $pp = new PayParts(self::STORE_ID, self::STORE_PASSWORD);
        $pp->setOptions($options);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage name key does not exist
     */
    public function testProductListKeyDoesNotExist()
    {
        $options = [
            'PartsCount' => 5,
            'merchantType' => 'PP',
            'ProductsList' => [
                [
                    'count' => 2,
                    'price' => 100.00
                ]
            ]
        ];
        $pp = new PayParts(self::STORE_ID, self::STORE_PASSWORD);
        $pp->setOptions($options);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage MerchantType must be in array('II', 'PP')
     */
    public function testWrongMerchantType()
    {
        $options = [
            'PartsCount' => 1,
            'merchantType' => 'DD',
            'ProductsList' => self::OPTIONS['ProductsList']
        ];
        $pp = new PayParts(self::STORE_ID, self::STORE_PASSWORD);
        $pp->setOptions($options);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage something is wrong with Currency
     */
    public function testSetWrongCurrency()
    {
        $options = [
            'PartsCount' => 1,
            'merchantType' => 'PP',
            'ProductsList' => self::OPTIONS['ProductsList'],
            'Currency' => '111',
        ];
        $pp = new PayParts(self::STORE_ID, self::STORE_PASSWORD);
        $pp->setOptions($options);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage ProductsList is necessary
     */
    public function testSetOptionsWithOutRequired()
    {
        $options = [
            'ResponseUrl' => '/response.php',
            'RedirectUrl' => '/redirect.php',
            'PartsCount' => 5,
            'Prefix' => '',
            'merchantType' => 'PP',
            'Currency' => '',
            'recipientId' => ''
        ];
        $pp = new PayParts(self::STORE_ID, self::STORE_PASSWORD);
        $pp->setOptions($options);
    }

    public function testSetOptionsSuccess()
    {
        $pp = new PayParts(self::STORE_ID, self::STORE_PASSWORD);
        $pp->setOptions(self::OPTIONS);
    }

    public function testCreateHoldSuccess()
    {
        $this->payParts->create('hold');
        $this->assertRegExp('/ORDER-\w+/', $this->payParts->getLOG()['OrderID'], 'OrderID key is present');
    }

    public function testCreatePaySuccess()
    {
        $this->payParts->create('pay');
        $this->assertRegExp('/ORDER-\w+/', $this->payParts->getLOG()['OrderID'], 'OrderID key is present');
    }

    public function testGetState()
    {
        $cb = $this->payParts->getState($this->payParts->getLOG()['OrderID'], false);
        $this->assertEquals('error', $cb);
    }

    public function testCheckCallback()
    {
        $this->payParts->create('hold');
        $cb = $this->payParts->checkCallBack("{'orderId':'ORDER-1111','paymentState':'Hold','message'''}");
        $this->assertEquals('error', $cb);
    }

    public function testConfirmHold()
    {
        $this->payParts->create('hold');
        $cb = $this->payParts->confirmHold($this->payParts->getLOG()['OrderID']);
        $this->assertNull($cb);
    }

    public function testCancelHold()
    {
        $this->payParts->create('hold');
        $cb = $this->payParts->cancelHold($this->payParts->getLOG()['OrderID'],'111');
        $this->assertNull($cb);
    }
}

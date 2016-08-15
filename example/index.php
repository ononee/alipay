<?php 

include '../src/alipay.php';

$alipay = new Alipay();
$alipay
->setPartner('2088111111111111')
//->isQrPay(TRUE)
->setSellerId('2088111111111111')
->setSubject('购买会员')
->setOutTradeNo(time())
->setTotalFree(30)
->setBody('购买会员')
->setShowUrl('http://www.exmaple.com')
->setNotifyUrl('http://www.exmaple.com')
->setReturnUrl('http://www.exmaple.com')
->setKey('AAAAAAAAAAAAAAAAAAAAA');

header('Location:'.$alipay->getPayLink());
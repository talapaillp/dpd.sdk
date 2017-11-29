<?php<?php
require __DIR__ .'/../src/autoload.php';

$config = new \Ipol\DPD\Config\Config([
    'KLIENT_NUMBER'   => '',
    'KLIENT_KEY'      => '',
    'KLIENT_CURRENCY' => 'RUB',
    'IS_TEST'         => true,
]);

// получить созданный ранее заказ и отменить его
$orderId = 1; // внешний ID заказа
$order = \Ipol\DPD\DB\Connection::getInstance($config)->getTable('order')->getByOrderId($orderId);

$ret = $order->dpd()->cancel();

var_dump($ret);
<?php
require __DIR__ .'/../src/autoload.php';

$config = new \Ipol\DPD\Config\Config([
    'KLIENT_NUMBER'   => '',
    'KLIENT_KEY'      => '',
    'KLIENT_CURRENCY' => 'RUB',
]);

$table  = \Ipol\DPD\DB\Connection::getInstance($config)->getTable('terminal');
$api    = \Ipol\DPD\API\User\User::getInstanceByConfig($config);

$loader = new \Ipol\DPD\DB\Terminal\Agent($api, $table);
$loader->loadUnlimited();
$loader->loadLimited();

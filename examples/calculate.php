<?php<?php
require __DIR__ .'/../src/autoload.php';

$config = new \Ipol\DPD\Config\Config([
    'KLIENT_NUMBER'   => '',
    'KLIENT_KEY'      => '',
    'KLIENT_CURRENCY' => 'RUB',
]);

$shipment = new \Ipol\DPD\Shipment($config);
$shipment->setSender('Россия', 'Москва', 'г. Москва');
$shipment->setReceiver('Россия', 'Тульская область', 'г. Тула');

// $shipment->setSelfDelivery(false);
// $shipment->setSelfPickup(false);

$shipment->setItems([
    [
        'NAME'       => 'Товар 1',
        'QUANTITY'   => 1,
        'PRICE'      => 1000,
        'VAT_RATE'   => 18,
        'WEIGHT'     => 1000,
        'DIMENSIONS' => [
            'LENGTH' => 200,
            'WIDTH'  => 100,
            'HEIGHT' => 50,
        ]
    ],

    [
        'NAME'       => 'Товар 2',
        'QUANTITY'   => 1,
        'PRICE'      => 1000,
        'VAT_RATE'   => 18,
        'WEIGHT'     => 1000,
        'DIMENSIONS' => [
            'LENGTH' => 350,
            'WIDTH'  => 70,
            'HEIGHT' => 200,
        ]
    ],

    [
        'NAME'       => 'Товар 3',
        'QUANTITY'   => 1,
        'PRICE'      => 1000,
        'VAT_RATE'   => 18,
        'WEIGHT'     => 1000,
        'DIMENSIONS' => [
            'LENGTH' => 220,
            'WIDTH'  => 100,
            'HEIGHT' => 70,
        ]
    ],
], 3000);

$tariff = $shipment->calculator()->calculate();
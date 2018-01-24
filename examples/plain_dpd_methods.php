<?php
// вначале нужно либо склонировать сдк в папку vendor
// git clone https://bitbucket.org/DPDinRussia/dpd.sdk.git vendor/dpd.sdk

require __DIR__ .'/vendor/dpd.sdk/src/autoload.php';

use \Ipol\DPD\API\User\User as ApiClient;

$client = new ApiClient(
    $clientNumber = 'КЛИЕНТСКИЙ_НОМЕР', 
    $secretKey    = 'ТОКЕН_АВТОРИЗАЦИИ', 
    $testMode     = true
);

/**
 *  getCitiesCashPay, Возвращает список городов с возможностью доставки наложенным платежом
 */
$cities = $client->getService('geography')->getCitiesCashPay($countryCode = 'RU');


/**
 * getParcelShops, 
 * 
 * Возвращает список пунктов приема/выдачи посылок, имеющих ограничения по габаритам и весу, 
 * с указанием режима работы пункта и доступностью выполнения самопривоза/самовывоза.
 * При работе с  методом  необходимо проводить получение информации по списку подразделений ежедневно.
 */
$items = $client->getService('geography')->getParcelShops(
    $countryCode = 'RU',  // страна (RU, BY, KZ)
    $regionCode  = false, // код региона чтобы ограничить выборку, можно узнать из результата метода getCitiesCashPay
    $cityCode    = false, // код города  чтобы ограничить выборку, можно узнать из результата метода getCitiesCashPay
    $cityName    = false  // название города  чтобы ограничить выборку
);

/**
 * getTerminalsSelfDelivery2
 * 
 * Возвращает список подразделений DPD, не имеющих ограничений по габаритам и весу посылок приема/выдачи
 * параметров нет, т.е нет возможности фильтровать
 */
$items = $client->getService('geography')->getTerminalsSelfDelivery2();

/**
 * getServiceCost2
 * 
 * Рассчитать общую стоимость доставки по России и странам ТС.
 */
$tariffs = $client->getService('calculator')->getServiceCost([
    'PICKUP'         => [ // от куда, см. метод getCitiesCashPay
        'COUNTRY_CODE' => 'RU',
        'COUNTRY_NAME' => 'Россия',
        'REGION_CODE'  => '',
        'REGION_NAME'  => 'Тульская',
        'CITY_CODE'    => '71000001000',
        'CITY_ID'      => '195902175',
        'CITY_NAME'    => 'Тула',
    ],
    'DELIVERY'       => [ // куда, см. метод getCitiesCashPay
        'COUNTRY_CODE' => 'RU',
        'COUNTRY_NAME' => 'Россия',
        'REGION_CODE'  => '',
        'REGION_NAME'  => 'Москва',
        'CITY_CODE'    => '77000000000',
        'CITY_ID'      => '49694102',
        'CITY_NAME'    => 'Москва',
    ],
    'WEIGHT'         => 1,    // вес, кг
    'VOLUME'         => 0.04, // объем, м3
    'SELF_PICKUP'    => 1,    // от двери - 1, от терминала - 0
    'SELF_DELIVERY'  => 1,    // до двери - 1, до терминала - 0
    'DECLARED_VALUE' => 1000, // объявленная ценность, руб.
]);

/**
 * getServiceCostByParcels2
 * 
 * Рассчитать стоимость доставки по параметрам  посылок по России и странам ТС.
 */
$tariffs = $client->getService('calculator')->getServiceCostByParcels([
    'PICKUP'         => [ // от куда, см. метод getCitiesCashPay
        'COUNTRY_CODE' => 'RU',
        'COUNTRY_NAME' => 'Россия',
        'REGION_CODE'  => '',
        'REGION_NAME'  => 'Тульская',
        'CITY_CODE'    => '71000001000',
        'CITY_ID'      => '195902175',
        'CITY_NAME'    => 'Тула',
    ],
    'DELIVERY'       => [ // куда, см. метод getCitiesCashPay
        'COUNTRY_CODE' => 'RU',
        'COUNTRY_NAME' => 'Россия',
        'REGION_CODE'  => '',
        'REGION_NAME'  => 'Москва',
        'CITY_CODE'    => '77000000000',
        'CITY_ID'      => '49694102',
        'CITY_NAME'    => 'Москва',
    ],
    'SELF_PICKUP'    => 1,    // от двери - 1, от терминала - 0
    'SELF_DELIVERY'  => 1,    // до двери - 1, до терминала - 0
    'DECLARED_VALUE' => 1000, // объявленная ценность, руб.
    'PARCEL'         => [ // список посылок
        [
            'WEIGHT' => 1,  // вес, кг
            'LENGTH' => 10, // длина, см
            'WIDTH'  => 20, // ширина, см
            'HEIGHT' => 10, // высота, см
        ],

        [
            'WEIGHT' => 5,  // вес, кг
            'LENGTH' => 20, // длина, см
            'WIDTH'  => 20, // ширина, см
            'HEIGHT' => 20, // высота, см
        ],
    ]
]);

/**
 * getServiceCostInternational
 * 
 * Рассчитать общую стоимость доставки по международным направлениям
 * 
 * параметры верные, но DPD всегда отвечает ошибкой (не возможна услуга по указанным параметрам).
 * Я завтра узнаю что им не нравится
 */
$tariffs = $client->getService('calculator')->getServiceCostInternational([
    'PICKUP'         => [ // от куда, см. метод getCitiesCashPay
        'COUNTRY_CODE' => 'RU',
        'COUNTRY_NAME' => 'Россия',
        'REGION_CODE'  => '',
        'REGION_NAME'  => 'Москва',
        'CITY_CODE'    => '77000000000',
        'CITY_ID'      => '49694102',
        'CITY_NAME'    => 'Москва',
    ],
    'DELIVERY'       => [ // куда, см. метод getCitiesCashPay
        'COUNTRY_CODE' => 'DE',
        'COUNTRY_NAME' => 'Германия',
        'REGION_CODE'  => '',
        'REGION_NAME'  => '',
        'CITY_CODE'    => '',
        'CITY_ID'      => '',
        'CITY_NAME'    => 'Мюнхен',
    ],
    'SELF_PICKUP'    => 0,    // от двери - 1, от терминала - 0
    'SELF_DELIVERY'  => 0,    // до двери - 1, до терминала - 0    
    'WEIGHT'         => 0.41,    // вес, кг
    'LENGTH'         => 47,   // длина, см
    'WIDTH'          => 15,   // ширина, см
    'HEIGHT'         => 15,   // высота, см
    'DECLARED_VALUE' => 800, // объявленная ценность, руб.
    'INSURANCE'      => 0,    // страховка да - 1, нет - 0
]);
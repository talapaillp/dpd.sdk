<?php
namespace Ipol\DPD;

use \Ipol\DPD\API\User\User;
use \Ipol\DPD\Config\ConfigInterface;
use \Ipol\DPD\DB\Connection as DB;

class Shipment
{
	protected $config;

	protected $selfPickup;

	protected $selfDelivery;

	protected $declaredValue;

	protected $orderItems = array();

	protected $orderItemsPrice = 0;

	protected $dimensions = array();

	protected $paymentMethod = array(
		'PERSONE_TYPE_ID' => null,
		'PAY_SYSTEM_ID'   => null,
	);

	/**
	 * Конструктор класса
	 */
	public function __construct(ConfigInterface $config)
	{
		$this->config        = $config;
		$this->selfDelivery  = true;
		$this->selfPickup    = $this->getConfig()->get('SELF_PICKUP', true);
		$this->declaredValue = $this->getConfig()->get('DECLARED_VALUE', true);
	}

	/**
	 * Устанавливает конфиг для работы
	 */
	public function setConfig(ConfigInterface $config)
	{
		$this->config = $config;

		return $this;
	}

	/**
	 * Возвращает конфиг
	 */
	public function getConfig()
	{
		return $this->config;
	}

	public function getDB()
	{
		return DB::getInstance($this->config);
	}

	/**
	 * Устанавливает местоположение отправителя
	 * 
	 * @param mixed $country
	 *               - Если передан массив, считается что это распознанное местоположение
	 *               - Если передано число, считается что это ID местоположения
	 *               - В противном случае это название страны 
	 * @param string $region
	 * @param string $city
	 *
	 * @return self
	 */
	public function setSender($country, $region = false, $city = false)
	{
		if (is_array($country)) {
			$this->locationFrom = $country;
		} elseif (is_numeric($country)) {
			$this->locationFrom = $this->getDB()->getTable('location')->findFirst($country);
		} else {
			$this->locationFrom = $this->getDB()->getTable('location')->getByAddress($country, $region, $city);
		}

		return $this;
	}

	/**
	 * Возвращает местоположение отправителя
	 * 
	 * @return array
	 */
	public function getSender()
	{
		return $this->locationFrom;
	}

	/**
	 * Устанавливает местоположение отправителя
	 * 
	 * @param mixed $country
	 *               - Если передан массив, считается что это распознанное местоположение
	 *               - Если передано число, считается что это ID местоположения
	 *               - В противном случае это название страны 
	 * @param string $region
	 * @param string $city
	 *
	 * @return self
	 */
	public function setReceiver($country, $region = false, $city = false)
	{		
		if (is_array($country)) {
			$this->locationTo = $country;
		} elseif (is_numeric($country)) {
			$this->locationTo = $this->getDB()->getTable('location')->findFirst($country);
		} else {
			$this->locationTo = $this->getDB()->getTable('location')->getByAddress($country, $region, $city);
		}

		return $this;
	}

	/**
	 * Возвращает местоположение получателя
	 * 
	 * @return array
	 */
	public function getReceiver()
	{
		return $this->locationTo;
	}

	/**
	 * Устанавливает от куда будут забирать посылку
	 * true  - от терминала
	 * false - от двери
	 * 
	 * @param bool $selfPickup
	 */
	public function setSelfPickup($selfPickup)
	{
		$this->selfPickup = $selfPickup;

		return $this;
	}

	/**
	 * Возвращает флаг от куда будут забирать посылку
	 * 
	 * @return bool
	 */
	public function getSelfPickup()
	{
		return $this->selfPickup;
	}

	/**
	 * Устанавливает док куда будут забирать посылку
	 * true  - до терминала
	 * false - до двери
	 * 
	 * @param bool $selfPickup
	 */
	public function setSelfDelivery($selfDelivery)
	{
		$this->selfDelivery = $selfDelivery;

		return $this;
	}

	/**
	 * Возвращает флаг до куда будут забирать посылку
	 * 
	 * @return bool
	 */
	public function getSelfDelivery()
	{
		return $this->selfDelivery;
	}

	/**
	 * Устанавливает флаг - использовать ли объявленную ценность груза
	 * 
	 * @param bool $declaredValue
	 */
	public function setDeclaredValue($declaredValue)
	{
		$this->declaredValue = $declaredValue;

		return $this;
	}

	/**
	 * Возвращает флаг - использовать ли объявленную ценность груза
	 * 
	 * @param bool $declaredValue
	 */
	public function getDeclaredValue()
	{
		return $this->declaredValue;
	}

	/**
	 * Устанавливает список товаров для доставки
	 * 
	 * $items = [
	 * 	productId => [
	 * 		NAME     => Название товара
	 * 		QUANTITY => кол-во
	 * 		PRICE    => стоимость за еденицу
	 * 		VAT_RATE => ставка налога, процент или строка Без НДС
	 *		WEIGHT   => вес, граммы,
	 * 		DIMENSIONS => [
	 *			'LENGTH' => длина, мм,
	 *			'WIDTH'  => ширина, мм,
	 *			'HEIGHT' => высота, мм,
	 * 		]
	 * 	],
	 * 
	 * 	...
	 * ]
	 * 
	 * @param array   $items             список товаров
	 * @param integer $itemsPrice        сумма наложенного платежа
	 * @param array   $defaultDimensions массив с габаритами по умолчанию
	 */
	public function setItems($items, $itemsPrice = 0, $defaultDimensions = array())
	{
		$this->orderItems      = $items;
		$this->orderItemsPrice = $itemsPrice;
		$this->dimensions      = $this->calcShipmentDimensions($items, $defaultDimensions);

		return $this;
	}

	/**
	 * Возвращает список товаров в отправке
	 * 
	 * @return array
	 */
	public function getItems()
	{
		return $this->orderItems;
	}

	/**
	 * Возвращает стоимость товаров входящих в отправку
	 * 
	 * @return float
	 */
	public function getPrice()
	{
		return $this->orderItemsPrice;
	}

	/**
	 * Возвращает габариты посылки
	 * 
	 * @return array
	 */
	public function getDimensions()
	{
		return $this->dimensions;
	}

	/**
	 * Устанавливает габариты заказа
	 * 
	 * @param float $width
	 * @param float $height
	 * @param float $length
	 * @param float $weight
	 */
	public function setDimensions($width, $height, $length, $weight)
	{
		$this->dimensions['WIDTH']  = $width;
		$this->dimensions['HEIGHT'] = $height;
		$this->dimensions['LENGTH'] = $length;
		$this->dimensions['WEIGHT'] = $weight;
	}

	/**
	 * Возвращает ширину посылки, см
	 * 
	 * @return float
	 */
	public function getWidth()
	{
		return $this->dimensions['WIDTH'];
	}

	/**
	 * Устанавливает ширину посылки, см
	 * 
	 * @param float $width
	 */
	public function setWidth($width)
	{
		$this->dimensions['WIDTH'] = $width;

		return $this;
	}

	/**
	 * Возвращает высоту посылки, см
	 * 
	 * @return float
	 */
	public function getHeight()
	{
		return $this->dimensions['HEIGHT'];
	}

	/**
	 * Устанавливает высоту посылки, см
	 * 
	 * @param float $height
	 */
	public function setHeight($height)
	{
		$this->dimensions['HEIGHT'] = $height;

		return $this;
	}

	/**
	 * Возвращает длинну посылки, см
	 * 
	 * @return float
	 */
	public function getLength()
	{
		return $this->dimensions['LENGTH'];
	}

	/**
	 * Устанавливает длину посылки, см
	 * 
	 * @param float $length
	 */
	public function setLength($length)
	{
		$this->dimensions['LENGTH'] = $length;

		return $this;
	}

	/**
	 * Возвращает вес отправки, кг
	 * 
	 * @return float
	 */
	public function getWeight()
	{
		return $this->dimensions['WEIGHT'];
	}

	/**
	 * Устанавливает вес отправки, кг
	 * 
	 * @param float $weight
	 */
	public function setWeight($weight)
	{
		$this->dimensions['WEIGHT'] = $weight;

		return $this;
	}

	/**
	 * Возвращает объем отправки, м3
	 * 
	 * @return float
	 */
	public function getVolume()
	{
		$volume = $this->dimensions['WIDTH'] * $this->dimensions['HEIGHT'] * $this->dimensions['LENGTH'];

		return round($volume / 1000000, 3);
	}

	/**
	 * Устанавливает способ оплаты
	 * 
	 * @param int $personTypeId
	 * @param int $paySystemId
	 */
	public function setPaymentMethod($personTypeId, $paySystemId)
	{
		$this->paymentMethod = array(
			'PERSON_TYPE_ID' => $personTypeId,
			'PAY_SYSTEM_ID'  => $paySystemId,
		);	

		return $this;
	}

	/**
	 * Возвращает способ оплаты
	 * 
	 * @return array
	 */
	public function getPaymentMethod()
	{
		return $this->paymentMethod;
	}

	/**
	 * Проверяет возможность осуществления доставки
	 *
	 * @return  bool
	 */
	public function isPossibileDelivery()
	{
		return $this->locationFrom && $this->locationTo;
	}

	/**
	 * Проверяет возможность осуществления в терминал доставки
	 *
	 * @return  bool
	 */
	public function isPossibileSelfDelivery($isPaymentOnDelivery = null)
	{
		if (!$this->isPossibileDelivery()) {
			return false;
		}

		$isPaymentOnDelivery = is_null($isPaymentOnDelivery) ? $this->isPaymentOnDelivery() : $isPaymentOnDelivery;

		$row = \Ipol\DPD\DB\Terminal\Table::getList([
			'select' => array('CNT'),

			'filter' => array_filter(array_merge(
				[
					'LOCATION_ID'   => $this->locationTo['ID'],
				],

				$isPaymentOnDelivery
					? ['NPP_AVAILABLE' => 'Y', '>=NPP_AMOUNT'  => $this->getPrice()]
					: []
			)),

			'runtime' => array(
				new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')
			),
		])->fetch();

		return $row['CNT'] > 0;
	}

	/**
	 * Использовать ли наложенный платеж
	 * 
	 * @return bool
	 */
	public function isPaymentOnDelivery()
	{
		$locationTo = $this->getReceiver();
		if (!User::isActiveAccount($this->getConfig(), $locationTo['COUNTRY_CODE'])) {
			return false;
		}

		$payment = array_filter($this->getPaymentMethod());
		if (empty($payment)) {
			return false;
		}

		if (empty($payment['PAY_SYSTEM_ID'])) {
			$useDefault = $this->getConfig()->get('COMMISSION_NPP_DEFAULT', []);

			return isset($useDefault[$payment['PERSON_TYPE_ID']])
				&& $useDefault[$payment['PERSON_TYPE_ID']]
			;
		}
		
		$arPaymentIds = $this->getConfig()->get('COMMISSION_NPP_PAYMENT', [], $payment['PERSON_TYPE_ID']);
		$arPaymentIds = isset($arPaymentIds[$payment['PERSON_TYPE_ID']]) 
			? $arPaymentIds[$payment['PERSON_TYPE_ID']] 
			: []
		;
		
		return in_array($payment['PAY_SYSTEM_ID'], $arPaymentIds);
	}

	/**
	 * Возвращает калькулятор для расчета стоимости доставки посылки
	 * 
	 * @param User $api
	 * 
	 * @return \Ipol\DPD\Calculator
	 */
	public function calculator(User $api = null)
	{
		return new Calculator($this, $api);
	}

	/**
	 * Возвращает суммарный вес и объем товаров в заказе
	 * 
	 * @param  array $items             состав заказа
	 * @param  array $defaultDimensions значения по умолчанию, если не переданы беруться из настроек модуля
	 * @return array(weight, volume)
	 */
	protected function calcShipmentDimensions(&$items, $defaultDimensions = array())
	{
		$defaultDimensions = $defaultDimensions ?: array(
			'WEIGHT' => $this->getConfig()->get('WEIGHT'),
			'LENGTH' => $this->getConfig()->get('LENGTH'),
			'WIDTH'  => $this->getConfig()->get('WIDTH'),
			'HEIGHT' => $this->getConfig()->get('HEIGHT'),
		);

		$defaultDimensions['VOLUME'] = $defaultDimensions['WIDTH'] * $defaultDimensions['HEIGHT'] * $defaultDimensions['LENGTH'];
		$needCheckWeight     = false;
		$needCheckDimensions = false;


		if ($items) {
			// получаем габариты одного вида товара в посылке с учетом кол-ва
			foreach ($items as &$item) {
				if (!is_array($item['DIMENSIONS'])) {
					$item['DIMENSIONS'] = unserialize($item['DIMENSIONS']);
				}

				$needCheckWeight = $needCheckWeight || $item['WEIGHT'] <= 0;
				$needCheckVolume = $needCheckDimensions || !($item['DIMENSIONS']['WIDTH'] && $item['DIMENSIONS']['HEIGHT'] && $item['DIMENSIONS']['LENGTH']);	
			}
		} else {
			$needCheckWeight = true;
			$needCheckVolume = true;
		}

		$sumDimensions = $this->sumDimensions($items);

		if ($needCheckWeight && $sumDimensions['WEIGHT'] < $defaultDimensions['WEIGHT']) {
			$sumDimensions['WEIGHT'] = $defaultDimensions['WEIGHT'];
		}

		if ($needCheckVolume && $sumDimensions['VOLUME'] < $defaultDimensions['VOLUME']) {
			$sumDimensions['WIDTH']  = $defaultDimensions['WIDTH'];
			$sumDimensions['HEIGHT'] = $defaultDimensions['HEIGHT'];
			$sumDimensions['LENGTH'] = $defaultDimensions['LENGTH'];
			// $sumDimensions['VOLUME'] = $defaultDimensions['VOLUME'];
		}

		return array(
			// мм -> см
			'WIDTH'  => $sumDimensions['WIDTH']  / 10,

			// мм -> см
			'HEIGHT' => $sumDimensions['HEIGHT'] / 10,

			// мм -> см
			'LENGTH' => $sumDimensions['LENGTH'] / 10,

			// граммы -> кг
			'WEIGHT' => $sumDimensions['WEIGHT'] / 1000,

			// мм3 -> м3
			// 'VOLUME' => $sumDimensions['VOLUME'] / 1000000000,
		);
	}

	/**
	 * Расчитывает габариты с учетом кол-ва
	 * 
	 * @param  $width
	 * @param  $height
	 * @param  $length
	 * @param  $quantity
	 * 
	 * @return array
	 */
	protected function calcItemDimensionWithQuantity($width, $height, $length, $quantity)
	{
		$ar = array($width, $height, $length);
		$qty = $quantity;
		sort($ar);

		if ($qty <= 1) {
			return array(
				'X' => $ar[0],
				'Y' => $ar[1],
				'Z' => $ar[2],
			);
		}

		$x1 = 0;
		$y1 = 0;
		$z1 = 0;
		$l  = 0;

		$max1 = floor(Sqrt($qty));
		for ($y = 1; $y <= $max1; $y++) {
			$i = ceil($qty / $y);
			$max2 = floor(Sqrt($i));
			for ($z = 1; $z <= $max2; $z++) {
				$x = ceil($i / $z);
				$l2 = $x*$ar[0] + $y*$ar[1] + $z*$ar[2];
				if ($l == 0 || $l2 < $l) {
					$l = $l2;
					$x1 = $x;
					$y1 = $y;
					$z1 = $z;
				}
			}
		}
		
		return array(
			'X' => $x1 * $ar[0],
			'Y' => $y1 * $ar[1],
			'Z' => $z1 * $ar[2]
		);
	}

	/**
	 * Расчитывает суммарные габариты посылки
	 * 
	 * @param  array $items [description]
	 * @return array
	 */
	protected function sumDimensions($items)
	{
		$ret = array(
			'WEIGHT' => 0,
			'VOLUME' => 0,
			'LENGTH' => 0,
			'WIDTH'  => 0,
			'HEIGHT' => 0,
		);

		$a = array();
		foreach ($items as $item) {
			$a[] = self::calcItemDimensionWithQuantity(
				$item['DIMENSIONS']['WIDTH'],
				$item['DIMENSIONS']['HEIGHT'],
				$item['DIMENSIONS']['LENGTH'],
				$item['QUANTITY']
			);

			$ret['WEIGHT'] += $item['WEIGHT'] * $item['QUANTITY'];
		}

		$n = count($a);
		if ($n <= 0) { 
			return $ret;
		}

		for ($i3 = 1; $i3 < $n; $i3++) {
			// отсортировать размеры по убыванию
			for ($i2 = $i3-1; $i2 < $n; $i2++) {
				for ($i = 0; $i <= 1; $i++) {
					if ($a[$i2]['X'] < $a[$i2]['Y']) {
						$a1 = $a[$i2]['X'];
						$a[$i2]['X'] = $a[$i2]['Y'];
						$a[$i2]['Y'] = $a1;
					};

					if ($i == 0 && $a[$i2]['Y']<$a[$i2]['Z']) {
						$a1 = $a[$i2]['Y'];
						$a[$i2]['Y'] = $a[$i2]['Z'];
						$a[$i2]['Z'] = $a1;
					}
				}

				$a[$i2]['Sum'] = $a[$i2]['X'] + $a[$i2]['Y'] + $a[$i2]['Z']; // сумма сторон
			}

			// отсортировать грузы по возрастанию
			for ($i2 = $i3; $i2 < $n; $i2++) {
				for ($i = $i3; $i < $n; $i++) {
					if ($a[$i-1]['Sum'] > $a[$i]['Sum']) {
						$a2 = $a[$i];
						$a[$i] = $a[$i-1];
						$a[$i-1] = $a2;
					}
				}
			}

			// расчитать сумму габаритов двух самых маленьких грузов
			if ($a[$i3-1]['X'] > $a[$i3]['X']) {
				$a[$i3]['X'] = $a[$i3-1]['X'];
			}

			if ($a[$i3-1]['Y'] > $a[$i3]['Y']) { 
				$a[$i3]['Y'] = $a[$i3-1]['Y'];
			}

			$a[$i3]['Z'] = $a[$i3]['Z'] + $a[$i3-1]['Z'];
			$a[$i3]['Sum'] = $a[$i3]['X'] + $a[$i3]['Y'] + $a[$i3]['Z']; // сумма сторон
		}

		return array_merge($ret, array(
			'LENGTH' => $length = Round($a[$n-1]['X'], 2),
			'WIDTH'  => $width  = Round($a[$n-1]['Y'], 2),
			'HEIGHT' => $height = Round($a[$n-1]['Z'], 2),
			'VOLUME' => $width * $height * $length,
		));
	}
} 
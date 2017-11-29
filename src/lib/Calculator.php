<?php
namespace Ipol\DPD;

use \Ipol\DPD\API\User\User as API;
use \Ipol\DPD\API\User\UserInterface;
use \Ipol\DPD\Currency\ConverterInterface;

class Calculator
{
	protected static $lastResult = false;

	protected $api;

	protected $shipment;

	protected $currencyConverter;

	/**
	 * Возвращает список всех тарифов которые могут быть использованы
	 *
	 * @return array
	 */
	public static function TariffList()
	{
		return array(
			"PCL" => "DPD Online Classic",
			// "CUR" => "DPD CLASSIC domestic",
			"CSM" => "DPD Online Express",
			"ECN" => "DPD ECONOMY",
			"ECU" => "DPD ECONOMY CU",
			// "NDY" => "DPD EXPRESS",
			// "TEN" => "DPD 10:00",
			// "DPT" => "DPD 13:00",
			// "BZP" => "DPD 18:00",
		);
	}

	/**
	 * Возвращает список тарифов которые можно использовать
	 *
	 * @return array
	 */
	public function AllowedTariffList()
	{
		$disableTariffs = (array) $this->getConfig()->get('TARIFF_OFF');
		return array_diff_key(static::TariffList(), array_flip($disableTariffs));
	}

	/**
	 * Возвращает последний расчет
	 * 
	 * @return array
	 */
	public static function getLastResult()
	{
		return static::$lastResult;
	}

	public function __construct(Shipment $shipment, UserInterface $api = null)
	{
		$this->shipment                  = $shipment;
		$this->api                       = $api ?: API::getInstanceByConfig($this->getConfig());
		$this->defaultTariffCode         = $this->getConfig()->get('DEFAULT_TARIFF_CODE');
		$this->minCostWhichUsedDefTariff = $this->getConfig()->get('DEFAULT_TARIFF_THRESHOLD', 0);
	}

	/**
	 * Возвращает конфиг
	 */
	public function getConfig()
	{
		return $this->getShipment()->getConfig();
	}

	/**
	 * Устанавливает конвертер валюты
	 */
	public function setCurrencyConverter(ConverterInterface $converter)
	{
		$this->currencyConverter = $converter;

		return $this;
	}

	/**
	 * Возвращает конвертер валюты
	 */
	public function getCurrencyConverter()
	{
		return $this->currencyConverter;
	}


	/**
	 * Устанавливает посылку для расчета стоимости
	 * 
	 * @param \Ipol\DPD\Shipment $shipment
	 */
	public function setShipment(Shipment $shipment)
	{
		$this->shipment = $shipment;

		return $this;
	}

	/**
	 * Возвращает посыдку для расчета стоимости
	 * 
	 * @return \Ipol\DPD\Shipment $shipment
	 */
	public function getShipment()
	{
		return $this->shipment;
	}

	/**
	 * Устанавливает тариф и порог мин. стоимости доставки
	 * при не достижении которого будет использован переданный тариф
	 * 
	 * @param string  $tariffCode
	 * @param float   $minCostWhichUsedTariff
	 */
	public function setDefaultTariff($tariffCode, $minCostWhichUsedTariff = 0)
	{
		$this->defaultTariffCode = $tariffCode;
		$this->minCostWhichUsedDefTariff = $minCostWhichUsedTariff;
	}

	/**
	 * Возвращает тариф по умолчанию
	 * 
	 * @return string
	 */
	public function getDefaultTariff()
	{
		return $this->defaultTariffCode;
	}

	/**
	 * Возвращает порог стоимости доставки при недостижении которого
	 * будет использован тариф по умолчанию
	 * 
	 * @return float
	 */
	public function getMinCostWhichUsedDefTariff()
	{
		return $this->minCostWhichUsedDefTariff;
	}

	/**
	 * Расчитывает стоимость доставки
	 * 
	 * @return array Оптимальный тариф доставки
	 */
	public function calculate($currency = false)
	{
		if (!$this->getShipment()->isPossibileDelivery()) {
			return false;
		}

		$parms = $this->getServiceParmsArray();
		$tariffs = $this->getListFromService($parms);

		if (empty($tariffs)) {
			return false;
		}

		$tariff = $this->getActualTariff($tariffs);
		$tariff = $this->adjustTariffWithCommission($tariff);
		$tariff = $this->convertCurrency($tariff, $currency);

		return self::$lastResult = $tariff;
	}

	/**
	 * Возвращает стоимость доставки для конкретного тарифа
	 * @param  string $tariffCode
	 * @return array
	 */
	public function calculateWithTariff($tariffCode, $currency = false)
	{
		if (!$this->getShipment()->isPossibileDelivery()) {
			return false;
		}

		$parms = $this->getServiceParmsArray();
		$tariffs = $this->getListFromService($parms);

		if (empty($tariffs)) {
			return false;
		}

		foreach($tariffs as $tariff) {
			if ($tariff['SERVICE_CODE'] == $tariffCode) {
				$tariff = $this->adjustTariffWithCommission($tariff);
				$tariff = $this->convertCurrency($tariff, $currency);

				return self::$lastResult = $tariff;
			}
		}

		return false;
	}

	/**
	 * Корректирует стоимость тарифа с учетом комиссии на наложенный платеж 
	 * 
	 * @param  array $tariff
	 * @param  int   $personTypeId
	 * @param  int   $paySystemId
	 * @return array
	 */
	public function adjustTariffWithCommission($tariff)
	{
		if (!$this->getShipment()->isPaymentOnDelivery()) {
			return $tariff;
		}

		$payment = $this->getShipment()->getPaymentMethod();

		$useCommission     = $this->getConfig()->get('COMMISSION_NPP_CHECK',   false, $payment['PERSON_TYPE_ID']);
		$commissionPercent = $this->getConfig()->get('COMMISSION_NPP_PERCENT', 2,     $payment['PERSON_TYPE_ID']);
		$minCommission     = $this->getConfig()->get('COMMISSION_NPP_MINSUM',  0,     $payment['PERSON_TYPE_ID']);

		if (!$useCommission) {
			return $tariff;
		}

		$sum = ($this->getShipment()->getPrice() * $commissionPercent / 100);
		$tariff['COST'] += $sum < $minCommission ? $minCommission : $sum;

		return $tariff;
	}

	/**
	 * Возвращает параметры для запроса на основе данных отправки
	 * 
	 * @return array
	 */
	public function getServiceParmsArray()
	{
		return array(
			'PICKUP'         => $this->getShipment()->getSender(),
			'DELIVERY'       => $this->getShipment()->getReceiver(),
			'WEIGHT'         => $this->getShipment()->getWeight(),
			'VOLUME'         => $this->getShipment()->getVolume(),
			'SELF_PICKUP'    => $this->getShipment()->getSelfPickup()   ? 1 : 0,
			'SELF_DELIVERY'  => $this->getShipment()->getSelfDelivery() ? 1 : 0,
			'DECLARED_VALUE' => $this->getShipment()->getDeclaredValue() ? round($this->shipment->getPrice(), 2) : 0,
		);
	}

	/**
	 * Получает список тарифов у внешнего сервиса
	 * с учетом разрешенных тарифов
	 * 
	 * @param  array $parms
	 * @return array
	 */
	public function getListFromService($parms)
	{
		$tariffs = $this->api->getService('calculator')->getServiceCost($parms);
		return array_intersect_key($tariffs, $this->AllowedTariffList());
	}

	/**
	 * Возвращает актуальный тариф с учетом мин. тарифа по умолчанию
	 * 
	 * @param  array $tariffs
	 * @return array
	 */
	protected function getActualTariff(array $tariffs)
	{
		$defaultTariff = false;
		$actualTariff = reset($tariffs);

		foreach($tariffs as $tariff) {
			if ($tariff['SERVICE_CODE'] == $this->getDefaultTariff()) {
				$defaultTariff = $tariff;
			}

			if ($tariff['COST'] < $actualTariff['COST']) {
				$actualTariff = $tariff;
			}
		}

		if ($defaultTariff
			&& $actualTariff['COST'] < $this->getMinCostWhichUsedDefTariff()
		) {
			return $defaultTariff;
		}

		return $actualTariff;
	}

	/**
	 * Конвертирует стоимость доставки в указанную валюту
	 * 
	 * @param array  $tariff
	 * @param string $currencyTo
	 * 
	 * @return array
	 */
	protected function convertCurrency($tariff, $currencyTo)
	{
		$converter = $this->getCurrencyConverter();
		
		if ($converter) {
			$currencyFrom = $this->api->getClientCurrency();
			$currencyTo   = $currencyTo ?: $currencyFrom;

			$tariff['COST']     = $converter->convert($tariff['COST'], $currencyFrom, $currencyTo);
			$tariff['CURRENCY'] = $currencyTo;
		}

		return $tariff;
	}
}
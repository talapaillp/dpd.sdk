<?php
namespace Ipol\DPD\DB\Location;

use \Ipol\DPD\Config\ConfigInterface;
use \Ipol\DPD\API\User\UserInterface;
use \Ipol\DPD\DB\TableInterface;
use \Ipol\DPD\Utils;

/**
 * Класс реализует методы обновления информации о городах в которых работает DPD
 */
class Agent
{
	protected static $cityFilePath = 'ftp://intergration:xYUX~7W98@ftp.dpd.ru:22/integration/GeographyDPD_20171125.csv';

	protected $api;
	protected $table;

	/**
	 * Конструктор
	 * 
	 * @param \Ipol\DPD\User\UserInterface $api   инстанс API
	 * @param \Ipol\DPD\DB\TableInterface  $table инстанс таблицы для записи данных в БД
	 */
	public function __construct(UserInterface $api, TableInterface $table)
	{
		$this->api   = $api;
		$this->table = $table;
	}

	/**
	 * @return \Ipol\DPD\User\UserInterface
	 */
	public function getApi()
	{
		return $this->api;
	}

	/**
	 * @return \Ipol\DPD\DB\Location\Table
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * Возвращает normalizer адресов
	 * 
	 * @return \Ipol\DPD\DB\Location\Normilizer
	 */
	public function getNormalizer()
	{
		return $this->getTable()->getNormalizer();
	}

	/**
	 * Обновляет список всех городов обслуживания
	 * 
	 * @param integer $position Стартовая позиция курсора в файле
	 * 
	 * @return void
	 */
	public function loadAll($position = 0)
	{
		ini_set('auto_detect_line_endings', true);

		static::$cityFilePath = $this->getTable()->getConfig()->get('DATA_DIR') .'/cities.csv';

		$file = fopen(self::$cityFilePath, 'r');
		if ($file === false) {
			return false;
		}

		fseek($file, $position ?: 0);
		$start_time = time();

		$i = 0;

		while(($row = fgetcsv($file, null, ';')) !== false) {
			if (Utils::isNeedBreak($start_time) && 1 != 1) {
				return ftell($file);
			}

			$region = explode(',', $row[4]);

			$this->loadLocation(
				$this->getNormalizer()->normilize(
					$country    = $row[5],
					$regionName = end($region),
					$cityName   = $row[2] .' '. $row[3]
				),

				[
					'CITY_ID'         => $row[0],
					'CITY_CODE'       => mb_substr($row[1], 2),
					'ORIG_NAME'       => $origName = implode(', ', [trim($country), trim($regionName), trim($cityName)]),
					'ORIG_NAME_LOWER' => mb_strtolower($origName),
				]
			);

			echo ++$i, "\r";
		}

		return true;
	}

	/**
	 * Обновляет города в которых доступен НПП
	 * 
	 * @param string $position Стартовая позиция импорта
	 * 
	 * @return void
	 */
	public function loadCashPay($position = 'RU:0')
	{
		$position   = explode(':', $position ?: 'RU:0');
		$index      = 0;
		$started    = false;
		$start_time = time();

		foreach(['RU', 'KZ', 'BY', 'UA'] as $countryCode) {
			if ($position[0] != $countryCode && $started === false) {
				continue;
			}

			$started  = true;
			$arCities = $this->getApi()->getService('geography')->getCitiesCashPay($countryCode);

			foreach ($arCities as $arCity) {
				if ($index++ < $position[1]) {
					continue;
				}

				if (Utils::isNeedBreak($start_time)) {
					return sprintf('%s:%s', $countryCode, $index);
				}

				$this->loadLocation(
					$this->getNormalizer()->normilize(
						$country = $arCity['COUNTRY_NAME'],
						$region  = $arCity['REGION_NAME'],
						$city    = $arCity['ABBREVIATION'] .' '. $arCity['CITY_NAME']
					),

					[
						'CITY_ID'         => $arCity['CITY_ID'],
						'CITY_CODE'       => $arCity['CITY_CODE'],
						'IS_CASH_PAY'     => 'Y',
						'ORIG_NAME'       => $origName = implode(', ', [trim($country), trim($region), trim($city)]),
						'ORIG_NAME_LOWER' => mb_strtolower($origName),
					]
				);
			}
		}

		return true;
	}

	/**
	 * Сохраняет город в БД
	 * 
	 * @param array $city
	 * @param array $additFields
	 * 
	 * @return bool
	 */
	protected function loadLocation($city, $additFields = array())
	{
		$fields = array_merge($city, $additFields);

		$exists = $this->getTable()->findFirst([
			'select' => 'ID',
			'where'  => 'COUNTRY_NAME = :country AND REGION_NAME = :region AND CITY_NAME = :city',
			'bind'   => [
				'country' => $fields['COUNTRY_NAME'],
				'region'  => $fields['REGION_NAME'],
				'city'    => $fields['CITY_NAME']
			]
		]);

		if ($exists) {
			$result = $this->getTable()->update($exists['ID'], $fields);
		} else {
			$result = $this->getTable()->add($fields);
		}

		return $result ? ($exists ? $exists['ID'] : $result) : false;
	}
}
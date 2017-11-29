<?php
namespace Ipol\DPD\DB\Location;

use Ipol\DPD\DB\AbstractTable;

class Table extends AbstractTable
{
	public function getTableName()
	{
		return 'b_ipol_dpd_location';
	}

	public function getFields()
	{
		return [
			'ID'           => null,
			'COUNTRY_CODE' => null,
			'COUNTRY_NAME' => null,
			'REGION_CODE'  => null,
			'REGION_NAME'  => null,
			'CITY_CODE'    => null,
			'CITY_NAME'    => null,
			'CITY_ABBR'    => null,
			'IS_CASH_PAY'  => null,
			'CITY_ID'      => null,
		];
	}

	public function getNormilizer()
	{
		return new Normilizer();
	}

	/**
	 * Возвращает запись по ID города
	 * 
	 * @param  int $locationId
	 * @param  array  $select
	 * @return array|false
	 */
	public static function getByCityId($cityId, $select = '*')
	{
		return $this->findFirst([
			'select' => $select,
			'where'  => 'CITY_ID = :city_id',
			'bind'   => [
				':city_id' => $cityId,
			]
		]);
	}

	public function getByAddress($country, $region, $city, $select = '*')
	{
		$city = $this->getNormilizer()->normilize($country, $region, $city);
		
		return $this->findFirst([
			'select' => $select,
			'where'  => 'COUNTRY_NAME = :country AND REGION_NAME = :region AND CITY_NAME = :city',
			'bind'   => [
				'country' => $city['COUNTRY_NAME'],
				'region'  => $city['REGION_NAME'],
				'city'    => $city['CITY_NAME'],
			]
		]);
	}
}
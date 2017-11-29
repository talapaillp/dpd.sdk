<?php
namespace Ipol\DPD\DB\Terminal;

use \Ipol\DPD\DB\AbstractTable;

class Table extends AbstractTable
{
	public function getTableName()
	{
		return 'b_ipol_dpd_terminal';
	}

	public function getFields()
	{
		return [
			'ID'                        => null,
			'LOCATION_ID'               => null,
			'CODE'                      => null,
			'NAME'                      => null,
			'ADDRESS_FULL'              => null,
			'ADDRESS_SHORT'             => null,
			'ADDRESS_DESCR'             => null,
			'PARCEL_SHOP_TYPE'          => null,
			'SCHEDULE_SELF_PICKUP'      => null,
			'SCHEDULE_SELF_DELIVERY'    => null,
			'SCHEDULE_PAYMENT_CASH'     => null,		
			'SCHEDULE_PAYMENT_CASHLESS' => null,
			'LATITUDE'                  => 0,
			'LONGITUDE'                 => 0,
			'IS_LIMITED'                => 'N',
			'LIMIT_MAX_SHIPMENT_WEIGHT' => 0,
			'LIMIT_MAX_WEIGHT'          => 0,
			'LIMIT_MAX_LENGTH'          => 0,
			'LIMIT_MAX_WIDTH'           => 0,
			'LIMIT_MAX_HEIGHT'          => 0,
			'LIMIT_MAX_VOLUME'          => 0,
			'LIMIT_SUM_DIMENSION'       => 0,
			'NPP_AMOUNT'                => 0,		
			'NPP_AVAILABLE'             => 'N',
		];
	}

	/**
	 * Возвращает записи по местоположению
	 * 
	 * @param  int $locationId
	 * @param  array  $select
	 * @return array|false
	 */
	public function findByLocationId($locationId, $select = '*')
	{	
		return $this->find([
			'select' => $select,
			'where'  => 'LOCATION_ID = :location_id',
			'bind'   => [
				':location_id' => $locationId
			]
		]);
	}

	/**
	 * Возвращает запись по коду
	 */
	public function getByCode($code, $select = '*')
	{
		return $this->findFirst([
			'select' => $select,
			'where'  => 'CODE = :code',
			'bind'   => [
				':code' => $code,
			]
		]);
	}
}
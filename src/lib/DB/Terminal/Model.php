<?php
namespace Ipol\DPD\DB\Terminal;

use \Ipol\DPD\DB\Model as BaseModel;

class Model extends BaseModel
{
	/**
	 * Проверяет параметры посылки
	 * 
	 * @param  Shipment $shipment
	 * @param  boolean  $checkLocation
	 * @return bool
	 */
	public function checkShipment(\Ipol\DPD\Shipment $shipment, $checkLocation = true)
	{
		if ($checkLocation 
			&& !$this->checkLocation($shipment->getReceiver())
		) {
			return false;
		}

		if ($shipment->isPaymentOnDelivery() 
			&& !$this->checkShipmentPayment($shipment)
		) {
			return false;
		}

		if (!$this->checkShipmentDimessions($shipment)) {
			return false;
		}

		return true;
	}

	/**
	 * Проверяет местоположение терминала и переданного местоположения
	 * 
	 * @param  array  $location
	 * @return bool
	 */
	public function checkLocation(array $location)
	{
		return $this->fields['LOCATION_ID'] == $location['ID'];
	}

	/**
	 * Возвращает возможность НПП на терминале
	 * 
	 * @param  Shipment $shipment
	 * @return bool
	 */
	public function checkShipmentPayment(\Ipol\DPD\Shipment $shipment)
	{
		if ($this->fields['NPP_AVAILABLE'] != 'Y')  {
			return false;
		}

		return $this->fields['NPP_AMOUNT'] >= $shipment->getPrice();
	}

	/**
	 * Проверяет габариты посылки
	 * 
	 * @param  Shipment $shipment
	 * @return bool
	 */
	public function checkShipmentDimessions(\Ipol\DPD\Shipment $shipment)
	{
		if ($this->fields['IS_LIMITED'] != 'Y') {
			return true;
		}

		return (
				$this->fields['LIMIT_MAX_WEIGHT'] <= 0
				|| $shipment->getWeight() <= $this->fields['LIMIT_MAX_WEIGHT']
			)

			&& (
				$this->fields['LIMIT_MAX_VOLUME'] <= 0
				|| $shipment->getVolume() <= $this->fields['LIMIT_MAX_VOLUME']
			)

			&& (
				$this->fields['LIMIT_SUM_DIMENSION'] <= 0
				|| array_sum([$shipment->getWidth(), $shipment->getHeight(), $shipment->getLength()]) <= $this->fields['LIMIT_SUM_DIMENSION']
			)
		;
	}
}
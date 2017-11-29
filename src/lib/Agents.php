<?php
namespace Ipol\DPD;

use \Ipol\DPD\API\User as API;
use \Ipol\DPD\Config\ConfigInterface;

/**
 * Класс периодических заданий
 */
class Agents
{
	/**
	 * Проверяет статусы заказов
	 * 
	 * @return string
	 */
	public static function checkOrderStatus(ConfigInterface $config)
	{
		self::checkPindingOrderStatus($config);
		self::checkTrakingOrderStatus($config);
	}

	/**
	 * Проверяет статусы заказов ожидающих проверки
	 * 
	 * @return void
	 */
	protected static function checkPindingOrderStatus(ConfigInterface $config)
	{
		$orders = \Ipol\DPD\DB\Order\Table::getList(array(
			'filter' => array(
				'=ORDER_STATUS' => \Ipol\DPD\Order::STATUS_PENDING,
			),

			'order' => array(
				'ORDER_DATE_STATUS' => 'ASC',
				'ORDER_DATE_CREATE' => 'ASC',
			),

			'limit' => 2,
		));

		while($order = $orders->Fetch()) {
			$order = new \Ipol\DPD\DB\Order\Model($order);
			$order->dpd()->checkStatus();
		}
	}

	/**
	 * Проверяет статусы заказов прошедшие проверку
	 * 
	 * @return void
	 */
	protected static function checkTrakingOrderStatus(ConfigInterface $config)
	{
		if (!$config->get('STATUS_ORDER_CHECK')) {
			return;
		}

		do {
			$ret = API::getInstanceByConfig($config)->getService('tracking')->getStatesByClient();
			if (!$ret) {
				return;
			}

			$states = (array) $ret['STATES'];
			$states = array_key_exists('DPD_ORDER_NR', $states) ? array($states) : $states;

			// сортируем статусы по их времени наступления
			uasort($states, function($a, $b) {
				if ($a['CLIENT_ORDER_NR'] == $b['CLIENT_ORDER_NR']) {
					$time1 = strtotime($a['TRANSITION_TIME']);
					$time2 = strtotime($b['TRANSITION_TIME']);

					return $time1 - $time2;
				}

				return $a['CLIENT_ORDER_NR'] - $b['CLIENT_ORDER_NR'];
			});

			foreach ($states as $state) {
				$order = \Ipol\DPD\DB\Order\Table::findByOrder($state['CLIENT_ORDER_NR']);
				if (!$order) {
					continue;
				}

				$status = $state['NEW_STATE'];
				
				if ($order->isSelfDelivery()
					&& $status == \Ipol\DPD\Order::STATUS_TRANSIT_TERMINAL
					&& $order->receiverTerminalCode == $state['TERMINAL_CODE']
				) {
					$status = \Ipol\DPD\Order::STATUS_ARRIVE;
				}

				$order->setOrderStatus($status, $statusTime);
				$order->orderNum = $state['DPD_ORDER_NR'] ?: $order->orderNum;
				$order->save();
			}

			if ($ret['DOC_ID'] > 0) {
				API::getInstanceByConfig($config)->getService('tracking')->confirm($ret['DOC_ID']);
			}
		} while($ret['RESULT_COMPLETE'] != 1);
	}

	/**
	 * Загружает в локальную БД данные о местоположениях и терминалах
	 * 
	 * @return string
	 */
	public static function loadExternalData(ConfigInterface $config)
	{
		$currStep = $config->get('LOAD_EXTERNAL_DATA_STEP');
		$position = $config->get('LOAD_EXTERNAL_DATA_POSITION');

		switch ($currStep) {
			case 'LOAD_LOCATION_ALL':
				$ret      = \Ipol\DPD\DB\Location\Agent::loadAll($position);
				$nextStep = 'LOAD_LOCATION_CASH_PAY';

			break;

			case 'LOAD_LOCATION_CASH_PAY':
				$ret      = \Ipol\DPD\DB\Location\Agent::loadCashPay($position);
				$nextStep = 'LOAD_TERMINAL_UNLIMITED';

			break;

			case 'LOAD_TERMINAL_UNLIMITED':
				$ret      = \Ipol\DPD\DB\Terminal\Agent::loadUnlimited($position);
				$nextStep = 'LOAD_TERMINAL_LIMITED';
			break;

			case 'LOAD_TERMINAL_LIMITED':
				$ret      = \Ipol\DPD\DB\Terminal\Agent::loadLimited($position);
				$nextStep = 'LOAD_FINISH';

			break;
			
			default:
				$ret      = true;
				$nextStep = 'LOAD_LOCATION_ALL';
			break;
		}

		$nextStep = is_bool($ret) ? $nextStep : $currStep;
		$position = is_bool($ret) ? ''        : $ret;

		$config->set('LOAD_EXTERNAL_DATA_STEP', $nextStep);
		$config->set('LOAD_EXTERNAL_DATA_POSITION', $position);
	}
}
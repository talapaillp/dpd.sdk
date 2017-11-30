<?php
namespace Ipol\DPD\API\Service;

use \Ipol\DPD\API\User\UserInterface;
use \Ipol\DPD\API\Client\Factory as ClientFactory;

class Tracking implements ServiceInterface
{
	protected $wdsl = 'http://ws.dpd.ru/services/tracing?wsdl';

	public function __construct(User $user)
	{
		$this->client = ClientFactory::create($this->wdsl, $user);
		$this->client->setCacheTime(0);
	}

	/**
	 * Возвращает трекинг статусы
	 * 
	 * @return array
	 */
	public function getStatesByClient()
	{
		return $this->client->invoke('getStatesByClient');
	}

	/**
	 * Подтверждает получение статусов
	 * 
	 * @param  $docId
	 * 
	 * @return array
	 */
	public function confirm($docId)
	{
		return $this->client->invoke('confirm', array(
			'docId' => $docId
		));
	}

	/**
	 * Получить все состояния заказа клиента, изменившиеся с момента последнего вызова данного метода
	 * 
	 * @param $dateFrom по умолчанию now() - 15 дней
	 * @param $dateTo   по умолчанию now()
	 * @param $limit    по умолчанию 100
	 */
	public function getEvents($dateFrom = false, $dateTo = false, $limit = false)
	{
		return $this->client->invoke('getEvents', array_filter(array(
			'DATE_TO'       => $dateTo,
			'DATE_FROM'     => $dateFrom,
			'MAX_ROW_COUNT' => $limit,
		)), 'request')
	}
}
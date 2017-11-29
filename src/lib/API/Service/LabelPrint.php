<?php
namespace Ipol\DPD\API\Service;

use \Ipol\DPD\API\User\UserInterface;
use \Ipol\DPD\API\Client\Factory as ClientFactory;

class LabelPrint implements ServiceInterface
{
	protected $wdsl = 'http://ws.dpd.ru/services/label-print?wsdl';

	public function __construct(UserInterface $user)
	{
		$this->client = ClientFactory::create($this->wdsl, $user);
		$this->client->setCacheTime(0);
	}

	/**
	 * Формирует файл с наклейками DPD
	 * 
	 * @return mixed
	 */
	public function createLabelFile($orderNum, $parcelsNumber = 1, $fileFormat = 'PDF', $pageSize = 'A5')
	{
		return $this->client->invoke('createLabelFile', array(
			'fileFormat' => $fileFormat,
			'pageSize'   => $pageSize,
			'order'      => array(
				'orderNum'      => $orderNum,
				'parcelsNumber' => $parcelsNumber
			),
		), 'getLabelFile');
	}
}
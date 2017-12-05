<?php
namespace Ipol\DPD\API\Client;

use \Ipol\DPD\API\User\UserInterface;

/**
 * Фабрика по созданию клиента к API
 * 
 * По умолчанию создает SOAP-клиента
 */
class Factory
{
	/**
	 * Возвращает SOAP-клиент для работы с API
	 * 
	 * @return \Ipol\DPD\API\Client\ClientInterface
	 */
	public static function create($wdsl, UserInterface $user)
	{
		if (class_exists('\\SoapClient')) {
			return new Soap($wdsl, $user);
		}

		throw new \Exception("Soap client is not found", 1);
	}
}
<?php
namespace Ipol\DPD\API\Client;

use \Ipol\DPD\API\User\UserInterface;

class Factory
{
	/**
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
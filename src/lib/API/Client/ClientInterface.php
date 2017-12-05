<?php
namespace Ipol\DPD\API\Client;

/**
 * Интерфейс подключения к API
 */
interface ClientInterface
{
	/**
	 * Выполняет запрос к внешнему API
	 * 
	 * @param  string $method
	 * @param  array  $args
	 * @param  string $wrap
	 * @return mixed
	 */
	public function invoke($method, array $args = array(), $wrap = 'request');
}
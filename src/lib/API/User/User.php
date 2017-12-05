<?php
namespace Ipol\DPD\API\User;

use \Ipol\DPD\Config\ConfigInterface;
use \Ipol\DPD\Config\Config;

/**
 * Класс реализует доступ к метода API
 */
class User implements UserInterface
{
	/**
	 * @var array
	 */
	public static $classmap = array(
		'geography'   => '\\Ipol\\DPD\\API\\Service\\Geography',
		'calculator'  => '\\Ipol\\DPD\\API\\Service\\Calculator',
		'order'       => '\\Ipol\\DPD\\API\\Service\\Order',
		'label-print' => '\\Ipol\\DPD\\API\\Service\\LabelPrint',
		'tracking'    => '\\Ipol\\DPD\\API\\Service\\Tracking',
	);

	/**
	 * @var self
	 */
	protected static $instances = [];

	/**
	 * @var array
	 */
	protected $services = [];

	/**
	 * Проверяет наличие данных авторизации к аккаунту
	 * 
	 * @param  string  $account
	 * @return boolean
	 */
	public static function isActiveAccount(ConfigInterface $config, $account = false)
	{
		$accountLang = $account !== false ? $account : $config->get('API_DEF_COUNTRY');
		$accountLang = $accountLang == 'RU' ? '' : $accountLang;

		$clientNumber   = $config->get(trim('KLIENT_NUMBER_'. $accountLang, '_'));
		$clientKey      = $config->get(trim('KLIENT_KEY_'. $accountLang, '_'));

		return $clientNumber && $clientKey;
	}
	
	/**
	 * Возвращает инстанс класса по псевдониму
	 * 
	 * @param string $alias
	 * 
	 * @return \Ipol\DPD\User\UserInterface
	 */
	public static function getInstanceByAlias($alias)
	{
		if (isset(static::$instances[$alias])) {
			return static::$instances[$alias];
		}

		return false;
	}
    
    /**
	 * Возвращает инстанс класса с параметрами доступа указанными в настройках
	 * 
	 * @return \Ipol\DPD\User\UserInterface
	 */
	public static function getInstanceByConfig(ConfigInterface $config, $account = false)
	{
		$accountLang = $account !== false ? $account : $config->get('API_DEF_COUNTRY');
        $accountLang = $accountLang == 'RU' ? '' : $accountLang;
        
		$clientNumber   = $config->get(trim('KLIENT_NUMBER_'. $accountLang, '_'));
		$clientKey      = $config->get(trim('KLIENT_KEY_'. $accountLang, '_'));
		$testMode       = $config->get('IS_TEST');
		$currency       = $config->get(trim('KLIENT_CURRENCY_'. $accountLang, '_'), 'RUB');
		$alias          = md5($clientNumber . $clientKey . $testMode);

		if (isset(static::$instances[$alias])) {
			return static::$instances[$alias];
		}

		return new static($clientNumber, $clientKey, $testMode, $currency);
	}

	protected $clientNumber;
	protected $secretKey;
	protected $testMode;
	protected $currency;

	/**
	 * @param string  $clientNumber номер клиента
	 * @param string  $secretKey    ключ доступа к API
	 * @param boolean $testMode     тестовый режим
	 * @param string  $currency     используемая валюта в API
	 * @param string  $alias        псевдоним под которым зарегистрируется текущий инстанс
	 */
	public function __construct($clientNumber, $secretKey, $testMode = false, $currency = false, $alias = false)
	{
		$this->clientNumber        = $clientNumber;
		$this->secretKey           = $secretKey;
		$this->testMode            = (bool) $testMode;
		$this->currency            = $currency;

		$alias = $alias ?: md5($clientNumber . $secretKey . $testMode);
		static::$instances[$alias] = $this;
	}

	/**
	 * Возвращает номер клиента DPD
	 * 
	 * @return string
	 */
	public function getClientNumber()
	{
		return $this->clientNumber;
	}

	/**
	 * Возвращает токен авторизации DPD
	 * 
	 * @return string
	 */
	public function getSecretKey()
	{
		return $this->secretKey;
	}

	/**
	 * Проверяет включен ли режим тестирования
	 * 
	 * @return boolean
	 */
	public function isTestMode()
	{
		return (bool) $this->testMode;
	}

	/**
	 * Возвращает валюту аккаунта
	 * 
	 * @return string
	 */
	public function getClientCurrency()
	{
		return $this->currency;
	}

	/**
	 * Возвращает конкретную службу API
	 * 
	 * @param  string $serviceName имя службы
	 * 
	 * @return \Ipol\API\Service\ServiceInterface
	 */
	public function getService($serviceName)
	{
		if (isset(static::$classmap[$serviceName])) {
			return $this->services[$serviceName] ?: $this->services[$serviceName] = new static::$classmap[$serviceName]($this);
		}

		throw new \Exception("Service {$serviceName} not found");
	}

	/**
	 * Конвертирует переданный uri в соответствии с тестовым режимом
	 * 
	 * @param string $uri
	 * 
	 * @return string
	 */
	public function resolveWsdl($uri)
	{
		if ($this->testMode) {
			return str_replace('ws.dpd.ru', 'wstest.dpd.ru', $uri);
		}

		return $uri;
	}
}
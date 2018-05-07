<?php
namespace Ipol\DPD\API\Service;

use \Ipol\DPD\API\User\UserInterface;
use Bitrix\Main\Web\HttpClient;

/**
 * Служба для работы со статусами заказа
 */
class TrackingOrder implements ServiceInterface
{
    const API_URL = 'http://mow-mobtr.dpd.ru:9090/ordertracking';
    const TEST_API_URL = 'http://91.209.80.50:9090/ordertracking ';

    protected $url = '';

    protected $auth = [];

    /**
     * Конструктор класса
     * 
     * @param \Ipol\DPD\API\User\UserInterface
     */
	public function __construct(UserInterface $user)
	{
        $this->auth = array(
            'clientNumber' => $user->getClientNumber(),
            'clientKey'    => $user->getSecretKey(),
        );

        $this->url = $user->isTestMode() 
            ? static::TEST_API_URL
            : static::API_URL
        ;
    }

    public function getByOrderNumber($orderNumber)
    {
        return $this->invoke([
            'orderNum' => $orderNumber,
        ]);
    }

    protected function invoke(array $data)
    {
        $data = json_encode(array_merge($data, ['auth' => $data]));

        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/json' . PHP_EOL,
                'content' => $data,
            ),
        ));

        $result = @file_get_contents($this->url, false, $context);
        $result = json_decode($result);

        return $result ?: [];
    }
}
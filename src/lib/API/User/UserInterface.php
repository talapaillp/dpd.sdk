<?php
namespace Ipol\DPD\API\User;

/**
 * Интерфейс соединения с API
 */
interface UserInterface
{
    /**
     * Возвращает номер клиента
     * 
     * @return string
     */
    public function getClientNumber();

    /**
     * Возвращает ключ авторизации к API
     * 
     * @return string
     */
    public function getSecretKey();

    /**
     * Возвращает включен ли тестовый режим
     * 
     * @return boolean
     */
    public function isTestMode();

    /**
     * Возвращает валюту аккаунта
     * 
     * @return string
     */
    public function getClientCurrency();

    /**
     * Возвращает сервис для работы со службой по ее имени
     * 
     * @return string
     */
    public function getService($serviceName);

    /**
     * Корректирует адрес wdsl
     */
    public function resolveWsdl($uri);
}
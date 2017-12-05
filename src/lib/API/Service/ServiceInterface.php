<?php
namespace Ipol\DPD\API\Service;

use Ipol\DPD\API\User\UserInterface;

/**
 * Интерфейс отдельной служюы API
 */
interface ServiceInterface
{
    /**
     * Конструктор класса
     * 
     * @param \Ipol\DPD\API\User\UserInterface
     */
    public function __construct(UserInterface $user);
}
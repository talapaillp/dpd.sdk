<?php
namespace Ipol\DPD\API\Service;

use Ipol\DPD\API\User\UserInterface;

interface ServiceInterface
{
    public function __construct(UserInterface $user);
}
<?php
namespace Ipol\DPD\DB;

interface ConnectionInterface
{
    /**
     * @return \PDO
     */
    public function getPDO();
}
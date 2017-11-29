<?php
namespace Ipol\DPD\Config;

interface ConfigInterface
{
    /**
     * Получение значения опции
     */
    public function get($option, $defaultValue = null);

    /**
     * Запись значения опции
     */
    public function set($option, $value);
}
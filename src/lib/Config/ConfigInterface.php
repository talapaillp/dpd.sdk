<?php
namespace Ipol\DPD\Config;

/**
 * Интерфейс конфига
 */
interface ConfigInterface
{
    /**
     * Получение значения опции
     * 
     * @param string $option       Название опции
     * @param mixed  $defaultValue Значение по умолчанию, если опция не определена
     * 
     * @return mixed
     */
    public function get($option, $defaultValue = null);

    /**
     * Запись значения опции
     * 
     * @param string $option Название опции
     * @param mixed  $value  Значение опции
     * 
     * @return self
     */
    public function set($option, $value);
}
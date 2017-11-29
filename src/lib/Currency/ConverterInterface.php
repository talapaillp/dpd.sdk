<?php
use \Ipol\DPD\Currency;

interface ConverterInterface
{
    /**
     * Производит конвертацию валюты
     * 
     * @param double $amount
     * @param string $currencyFrom
     * @param string $currencyTo
     * @param string $actualDate
     * 
     * @return double
     */
    public function convert($amount, $currencyFrom, $currencyTo, $actualDate = false);
}
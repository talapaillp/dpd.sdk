<?php
namespace Ipol\DPD\DB\Location;

class Normilizer
{      
    /**
     * Возвращает нормализованную информацию о нас. пункте
     */
    public function normilize($country, $region, $locality)
    {
        return array_merge(
            $country  = $this->normilizeCountry($country),
            $region   = $this->normilizeRegion($region, $country),
            $locality = $this->normilizeCity($locality, $region)
        );
    }

    /**
     * Возвращает информацию о стране
     * 
     * @return array
     */
    public function normilizeCountry($country)
    {
        return [
            'COUNTRY_NAME' => $country,
            'COUNTRY_CODE' => array_search(mb_strtolower($country, 'UTF-8'), $this->getCountryList()),
        ];
    }

    /**
     * Возвращает информацию о регионе
     * 
     * @param string $region
     * @param array  $country
     * 
     * @return array
     */
    public function normilizeRegion($region, $country)
    {
        $this->trimAbbr($region, $this->getRegionAbbrList());

        return [
            'REGION_NAME' => $region,
            'REGION_CODE' => array_search(
                mb_strtolower($region, 'UTF-8'),
                $this->getRegionCodeList($country['COUNTRY_CODE'])
            ),
        ];
    }

    /**
     * Возвращает нормализованную информацию о нас. пункте
     * 
     * @param string $city
     * @param array  $region
     */
    public function normilizeCity($city, $region)
    {
        $abbr = $this->trimAbbr($city, array_merge(
            $this->getCityAbbrList(),
            $this->getVillageAbbrList()
        ));
        

        $city = $this->checkAnalog($city, $region);
        
        return [
            'CITY_NAME' => $city,
            'CITY_ABBR' => $abbr,
        ];
    }

    /**
     * Объединяет города аналоги в один город
     * 
     * @param string $city
     * @param array  $region
     */
    public function checkAnalog($city, $region)
    {
        $regionLower = mb_strtolower($region['REGION_NAME'], 'UTF-8');
        $cityLower   = mb_strtolower($city, 'UTF-8');

        foreach ($this->getCityAnalogs() as $analog => $analogs) {
            if (isset($analogs[$cityLower]) 
                && in_array($regionLower, $analogs[$cityLower])
            ) {
                return $analog;
            }
        }

        return $city;
    }

    /**
     * Удаляет из строки аббревиатуру и возвращает ее
     * 
     * @param string $string
     * @param array  $abbrList
     * 
     * @return string|false
     */
    protected function trimAbbr(&$string, $abbrList)
	{
        usort($abbrList, function($a, $b) {
            return mb_strlen($b, 'UTF-8') - mb_strlen($a, 'UTF-8');
        });       

        foreach ($abbrList as $abbr) {
            $abbr       = trim($abbr);
            $abbrRegexp = '/\b'. preg_quote($abbr) .'\b/sUui';

            if (preg_match($abbrRegexp, $string)) {
                $string = preg_replace($abbrRegexp, '', $string);
                $string = trim($string, '.');
                $string = preg_replace('{\s{2,}}', ' ', $string);
                $string = trim($string);

                return $abbr;
            }
        }

        return null;
    }
    
    /**
     * Возвращает список стран
     */
    protected function getCountryList()
    {
        return [
            'RU' => 'россия',
            'KZ' => 'казахстан',
            'BY' => 'беларусь',
        ];
    }

    /**
     * Возвращает аббревиатуру региона
     */
    protected function getRegionAbbrList()
    {
        return [
            'автономный округ',
            'область',
            'аобл',
            'обл',
            'АО',
            'республика',
            'респ',
            'край',
            'г',
        ];
    }

    /**
     * Возвращает список кодов регионов
     * 
     * @param string $region
     * @param string $countryCode
     */
    protected function getRegionCodeList($countryCode)
    {
        $file = __DIR__ .'/../../../../data/regions_'. $countryCode .'.php';

        if (file_exists($file)) {
            return include($file);
        }

        return [];
    }

    /**
     * Возвращает аббревиатуру города
     */
    protected function getCityAbbrList()
    {
        return [
            'город',
            'г',
        ];
    }

    /**
     * Возвращает аббревиатуру нас. пункта
     */
    protected function getVillageAbbrList()
    {
        return [
            'посёлок городского типа',
            'поселок городского типа',
            'пгт',
            'деревня',
            'д',
            'село',
            'c',
            'поселок',
            'посёлок',
            'п',
            'станция',
            'ст', 
            'аул', 
            'станица',
            'ст-ца',
        ];
    }

    /**
     * Возвращает список городов аналогов
     */
    protected function getCityAnalogs()
    {
        return [
            // город
            'Москва' => [
                // аналог       // области
                'зеленоград' => ['москва', 'московская'],
                'твepь'      => ['москва', 'московская'],
                'тверь_969'  => ['москва', 'московская'],
                'московский' => ['москва', 'московская'],
            ],
        
            'Санкт-петербург' => [
                'колпино'      => ['ленинградская'],
                'красное cело' => ['ленинградская'],
                'кронштадт'    => ['ленинградская'],
                'ломоносов'    => ['ленинградская'],
                'павловск'     => ['ленинградская'],
                'пушкин'       => ['ленинградская'],
                'сестрорецк'   => ['ленинградская'],
                'петергоф'     => ['ленинградская'],
            ],

            'Севастополь' => [
                'инкерман' => ['крым'],
            ],
        ];
    }
}
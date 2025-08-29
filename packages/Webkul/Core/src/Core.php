<?php

namespace Webkul\Core;

use Carbon\Carbon;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Core\Repositories\CountryRepository;
use Webkul\Core\Repositories\CountryStateRepository;

class Core
{
    /**
     * The Krayin version.
     *
     * @var string
     */
    const KRAYIN_VERSION = '2.1.2';

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(
        protected CountryRepository $countryRepository,
        protected CoreConfigRepository $coreConfigRepository,
        protected CountryStateRepository $countryStateRepository
    ) {}

    /**
     * Get the version number of the Krayin.
     *
     * @return string
     */
    public function version()
    {
        return static::KRAYIN_VERSION;
    }

    /**
     * Retrieve all timezones.
     */
    public function timezones(): array
    {
        $timezones = [];

        foreach (timezone_identifiers_list() as $timezone) {
            $timezones[$timezone] = $timezone;
        }

        return $timezones;
    }

    /**
     * Retrieve all locales.
     */
    public function locales(): array
    {
        $options = [];

        foreach (config('app.available_locales') as $key => $title) {
            $options[] = [
                'title' => $title,
                'value' => $key,
            ];
        }

        return $options;
    }

    /**
     * Retrieve all countries.
     *
     * @return \Illuminate\Support\Collection
     */
    public function countries()
    {
        return $this->countryRepository->all();
    }

    /**
     * Returns country name by code.
     */
    public function country_name(string $code): string
    {
        $country = $this->countryRepository->findOneByField('code', $code);

        return $country ? $country->name : '';
    }

    /**
     * Returns state name by code.
     */
    public function state_name(string $code): string
    {
        $state = $this->countryStateRepository->findOneByField('code', $code);

        return $state ? $state->name : $code;
    }

    /**
     * Retrieve all country states.
     *
     * @return \Illuminate\Support\Collection
     */
    public function states(string $countryCode)
    {
        return $this->countryStateRepository->findByField('country_code', $countryCode);
    }

    /**
     * Retrieve all grouped states by country code.
     *
     * @return \Illuminate\Support\Collection
     */
    public function groupedStatesByCountries()
    {
        $collection = [];

        foreach ($this->countryStateRepository->all() as $state) {
            $collection[$state->country_code][] = $state->toArray();
        }

        return $collection;
    }

    /**
     * Retrieve all grouped states by country code.
     *
     * @return \Illuminate\Support\Collection|false
     */
    public function findStateByCountryCode($countryCode = null, $stateCode = null)
    {
        $collection = $this->countryStateRepository->findByField([
            'country_code' => $countryCode,
            'code'         => $stateCode,
        ]);

        if (count($collection)) {
            return $collection->first();
        } else {
            return false;
        }
    }

    /**
     * Create singleton object through single facade.
     *
     * @param  string  $className
     * @return mixed
     */
    public function getSingletonInstance($className)
    {
        static $instances = [];

        if (array_key_exists($className, $instances)) {
            return $instances[$className];
        }

        return $instances[$className] = app($className);
    }

    /**
     * Format date
     *
     * @return string
     */
    public function formatDate($date, $format = 'd M Y h:iA')
    {
        return Carbon::parse($date)->format($format);
    }

    /**
     * Week range.
     *
     * @param  string  $date
     * @param  int  $day
     * @return string
     */
    public function xWeekRange($date, $day)
    {
        $ts = strtotime($date);

        if (! $day) {
            $start = (date('D', $ts) == 'Sun') ? $ts : strtotime('last sunday', $ts);

            return date('Y-m-d', $start);
        } else {
            $end = (date('D', $ts) == 'Sat') ? $ts : strtotime('next saturday', $ts);

            return date('Y-m-d', $end);
        }
    }

    /**
     * Return currency symbol from currency code.
     *
     * @param  string|null  $code
     * @return string
     */
    public function currencySymbol($code = null)
    {
        $code = $code ?: $this->currentCurrencyCode();

        if ($code === 'BRL') {
            return 'R$';
        }

        $formatter = new \NumberFormatter(app()->getLocale().'@currency='.$code, \NumberFormatter::CURRENCY);

        return $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
    }

    /**
     * Format price with base currency symbol.
     *
     * @param  float|null  $price
     * @return string
     */
    public function formatBasePrice($price)
    {
        if (is_null($price)) {
            $price = 0;
        }

        $code   = $this->currentCurrencyCode();
        $locale = app()->getLocale() ?: 'pt_BR';

        if ($code === 'BRL') {
            // Formatação brasileira
            return 'R$ ' . number_format($price, 2, ',', '.');
        }

        // Outras moedas seguem Intl
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($price, $code);
    }

    /**
     * Get the config field.
     */
    public function getConfigField(string $fieldName): ?array
    {
        return system_config()->getConfigField($fieldName);
    }

    /**
     * Retrieve information for configuration.
     */
    public function getConfigData(string $field): mixed
    {
        return system_config()->getConfigData($field);
    }

    /**
     * Resolve the current currency code with fallbacks.
     */
    private function currentCurrencyCode(): string
    {
        // 1) .env
        $env = env('APP_CURRENCY');

        if (! empty($env)) {
            return strtoupper($env);
        }

        // 2) Banco (core_config)
        try {
            $db = system_config()->getConfigData('general.settings.currency');
            if (! empty($db)) {
                return strtoupper($db);
            }
        } catch (\Throwable $e) {
            // ignora se não houver durante bootstrap
        }

        // 3) config/app.php
        $cfg = config('app.currency');
        if (! empty($cfg)) {
            return strtoupper($cfg);
        }

        // 4) padrão
        return 'BRL';
    }
}

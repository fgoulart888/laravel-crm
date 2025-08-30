<?php

namespace Webkul\Core;

class Core extends \Webkul\Core\Core
{
    // Código base da moeda
    public function getBaseCurrencyCode()
    {
        return 'BRL';
    }

    // Símbolo da moeda
    public function getBaseCurrencySymbol()
    {
        return 'R$';
    }

    // Formatação padrão usada em cards (dashboard etc.)
    public function formatBasePrice($amount, $format = true)
    {
        $value = number_format((float) $amount, 2, ',', '.');
        return $format ? ('R$ ' . $value) : $value;
    }

    // Algumas telas usam este também:
    public function formatPrice($amount, $currencyCode = null, $format = true)
    {
        $currencyCode = $currencyCode ?: 'BRL';
        $value = number_format((float) $amount, 2, ',', '.');

        if ($format) {
            $symbol = $currencyCode === 'BRL' ? 'R$' : $this->getBaseCurrencySymbol();
            return $symbol . ' ' . $value;
        }

        return $value;
    }
}

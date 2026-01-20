<?php

namespace App\Services;

class CountryConfigService
{
    /**
     * Configuration des pays avec leurs spécificités
     */
    public static function getCountryConfigs(): array
    {
        return [
            'BJ' => [
                'name' => 'Bénin',
                'currency' => 'XOF',
                'currency_symbol' => 'FCFA',
                'currency_position' => 'after',
                'decimal_separator' => ',',
                'thousands_separator' => ' ',
                'vat_rates' => [
                    ['rate' => 18.00, 'name' => 'TVA normale', 'is_default' => true],
                    ['rate' => 0.00, 'name' => 'Exonéré', 'is_default' => false],
                ],
                'default_vat_rate' => 18.00,
                'tax_id_label' => 'IFU',
                'tax_id_format' => '/^\d{13}$/',
                'vat_number_label' => 'N° IFU',
                'features' => [
                    'ppf_integration' => false,
                    'urssaf_integration' => false,
                    'e_invoicing' => false,
                    'siret_required' => false,
                ],
                'date_format' => 'd/m/Y',
                'timezone' => 'Africa/Porto-Novo',
            ],
        ];
    }

    /**
     * Récupère la configuration d'un pays
     */
    public static function getCountryConfig(string $countryCode): ?array
    {
        return self::getCountryConfigs()[$countryCode] ?? null;
    }

    /**
     * Récupère les taux de TVA d'un pays
     */
    public static function getVatRates(string $countryCode): array
    {
        $config = self::getCountryConfig($countryCode);
        return $config['vat_rates'] ?? [
            ['rate' => 20.00, 'name' => 'TVA standard', 'is_default' => true],
            ['rate' => 0.00, 'name' => 'Exonéré', 'is_default' => false],
        ];
    }

    /**
     * Récupère le taux de TVA par défaut d'un pays
     */
    public static function getDefaultVatRate(string $countryCode): float
    {
        $config = self::getCountryConfig($countryCode);
        return $config['default_vat_rate'] ?? 20.00;
    }

    /**
     * Récupère les fonctionnalités disponibles pour un pays
     */
    public static function getCountryFeatures(string $countryCode): array
    {
        $config = self::getCountryConfig($countryCode);
        return $config['features'] ?? [
            'ppf_integration' => false,
            'urssaf_integration' => false,
            'e_invoicing' => false,
            'siret_required' => false,
        ];
    }

    /**
     * Vérifie si une fonctionnalité est disponible pour un pays
     */
    public static function isFeatureAvailableForCountry(string $countryCode, string $feature): bool
    {
        $features = self::getCountryFeatures($countryCode);
        return $features[$feature] ?? false;
    }

    /**
     * Liste des pays disponibles pour le select
     */
    public static function getCountriesForSelect(): array
    {
        $countries = [];
        foreach (self::getCountryConfigs() as $code => $config) {
            $countries[$code] = $config['name'];
        }
        asort($countries);
        return $countries;
    }

    /**
     * Liste des taux de TVA pour un select
     */
    public static function getVatRatesForSelect(string $countryCode): array
    {
        $rates = [];
        foreach (self::getVatRates($countryCode) as $vat) {
            $rates[(string)$vat['rate']] = $vat['name'] . ' (' . number_format($vat['rate'], 2, ',', '') . '%)';
        }
        return $rates;
    }

    /**
     * Récupère le format monétaire pour un pays
     */
    public static function formatMoney(float $amount, string $countryCode): string
    {
        $config = self::getCountryConfig($countryCode);
        
        if (!$config) {
            return number_format($amount, 2, ',', ' ') . ' €';
        }

        $formatted = number_format(
            $amount,
            2,
            $config['decimal_separator'],
            $config['thousands_separator']
        );

        if ($config['currency_position'] === 'before') {
            return $config['currency_symbol'] . ' ' . $formatted;
        }

        return $formatted . ' ' . $config['currency_symbol'];
    }

    /**
     * Récupère le label du numéro fiscal pour un pays
     */
    public static function getTaxIdLabel(string $countryCode): string
    {
        $config = self::getCountryConfig($countryCode);
        return $config['tax_id_label'] ?? 'N° fiscal';
    }

    /**
     * Vérifie si le SIRET est requis pour un pays
     */
    public static function isSiretRequired(string $countryCode): bool
    {
        return self::isFeatureAvailableForCountry($countryCode, 'siret_required');
    }
}

<?php

namespace AndrewSvirin\Ebics\Models\X509;

use AndrewSvirin\Ebics\Models\Bank;
use LogicException;

/**
 * Automatic resolving by bank X509 certificate generator @see X509GeneratorInterface.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class BankX509Generator extends AbstractX509Generator
{
    /**
     * Set certificate options by Bank.
     */
    public function setCertificateOptionsByBank(Bank $bank): void
    {
        $countryName = $this->resolveCountryName($bank->getUrl());
        $domainName = $this->resolveDomainName($bank->getUrl());
        $establishmentName = $this->resolveEstablishmentName($bank->getUrl());
        $this->certificateOptions = [
            'subject' => [
                'DN' => [
                    'id-at-countryName' => $countryName,
                    'id-at-commonName' => $domainName,
                ],
            ],
            'issuer' => [
                'DN' => [
                    'id-at-countryName' => $countryName,
                    'id-at-commonName' => $establishmentName,
                ],
            ],
        ];
    }

    /**
     * Resolve country name by URL.
     */
    private function resolveCountryName(string $url): string
    {
        /** @var string[] */
        $urlArr = parse_url($url);
        if (!isset($urlArr['host'])) {
            throw new LogicException('Host not parsed.');
        }

        $explode = explode('.', $urlArr['host']);
        $domain = end($explode);

        switch ($domain) {
            case 'fr':
                return 'FR';
            case 'ch':
                return 'CH';
            default:
                return 'DE';
        }
    }

    /**
     * Resolve domain name by URL.
     */
    private function resolveDomainName(string $url): string
    {
        /** @var string[] */
        $urlArr = parse_url($url);
        if (!isset($urlArr['host'])) {
            throw new LogicException('Host not parsed.');
        }

        $explode = explode('.', $urlArr['host']);
        $explode[0] = '*';

        return implode('.', $explode);
    }

    /**
     * Resolve establishment name by URL.
     */
    private function resolveEstablishmentName(string $url): string
    {
        /** @var string[] */
        $urlArr = parse_url($url);
        if (!isset($urlArr['host'])) {
            throw new LogicException('Host not parsed.');
        }

        $explode = explode('.', $urlArr['host']);

        return ucfirst($explode[1]);
    }
}

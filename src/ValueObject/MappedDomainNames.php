<?php

declare(strict_types=1);

/*
 * This file is part of Ymir WordPress plugin.
 *
 * (c) Carl Alexander <support@ymirapp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ymir\Plugin\ValueObject;

use Ymir\Plugin\Support\Collection;

/**
 * Domain names mapped to the environment.
 */
class MappedDomainNames
{
    /**
     * All the domain names mapped to the environment.
     *
     * @var Collection
     */
    private $mappedDomainNames;

    /**
     * The primary domain name used by the environment.
     *
     * @var string
     */
    private $primaryDomainName;

    /**
     * Constructor.
     */
    public function __construct(array $domainNames, string $primaryDomainName)
    {
        $this->mappedDomainNames = (new Collection($domainNames))->filter(function (string $domainName) {
            return !$this->isYmirVanityDomainName($domainName);
        })->unique()->values();
        $this->primaryDomainName = $primaryDomainName;
    }

    /**
     * Get the primary domain name.
     */
    public function getPrimaryDomainName(): string
    {
        return $this->primaryDomainName;
    }

    /**
     * Get the URL of the primary domain name.
     */
    public function getPrimaryDomainNameUrl(): string
    {
        return 'https://'.$this->getPrimaryDomainName();
    }

    /**
     * Checks if the given domain name is a mapped domain name.
     */
    public function isMappedDomainName(string $domainName): bool
    {
        if ($this->isPrimaryDomainName($domainName) || in_array($domainName, $this->mappedDomainNames->all())) {
            return true;
        }

        $wildcardDomains = $this->mappedDomainNames->filter(function (string $domainName) {
            return $this->isWildcardDomainName($domainName);
        })->all();

        foreach ($wildcardDomains as $wildcardDomain) {
            if (1 === preg_match(sprintf('/^[a-z0-9_][a-z0-9-_]+\.%s$/i', preg_quote(substr($wildcardDomain, 2), '/')), $domainName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the given domain name is the primary domain name.
     */
    public function isPrimaryDomainName(string $domainName): bool
    {
        return $domainName === $this->getPrimaryDomainName();
    }

    /**
     * Checks if the given domain name is a wildcard domain name.
     */
    private function isWildcardDomainName(string $domainName): bool
    {
        return 0 === stripos($domainName, '*.');
    }

    /**
     * Checks if the given domain name is a Ymir vanity domain name.
     */
    private function isYmirVanityDomainName(string $domainName): bool
    {
        return (bool) preg_match('#[^.]*\.ymirsites\.com#i', $domainName);
    }
}

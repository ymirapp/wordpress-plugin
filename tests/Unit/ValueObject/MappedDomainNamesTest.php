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

namespace Ymir\Plugin\Tests\Unit\ValueObject;

use Ymir\Plugin\Tests\Unit\TestCase;
use Ymir\Plugin\ValueObject\MappedDomainNames;

class MappedDomainNamesTest extends TestCase
{
    public function testGetPrimaryDomainName(): void
    {
        $primaryDomainName = 'primary_domain_name';

        $this->assertSame($primaryDomainName, (new MappedDomainNames([], $primaryDomainName))->getPrimaryDomainName());
    }

    public function testGetPrimaryDomainNameUrl(): void
    {
        $primaryDomainName = 'primary_domain_name';

        $this->assertSame('https://'.$primaryDomainName, (new MappedDomainNames([], $primaryDomainName))->getPrimaryDomainNameUrl());
    }

    public function testIsMappedDomainNameIgnoresYmirVanityDomainName(): void
    {
        $mappedDomainName = 'subdomain.ymirsites.com';

        $this->assertFalse((new MappedDomainNames([$mappedDomainName], 'primary_domain_name'))->isMappedDomainName($mappedDomainName));
    }

    public function testIsMappedDomainNameWithMappedDomainName(): void
    {
        $mappedDomainName = 'mapped_domain_name';

        $this->assertTrue((new MappedDomainNames([$mappedDomainName], 'primary_domain_name'))->isMappedDomainName($mappedDomainName));
    }

    public function testIsMappedDomainNameWithPrimaryDomainName(): void
    {
        $primaryDomainName = 'primary_domain_name';

        $this->assertTrue((new MappedDomainNames([], $primaryDomainName))->isMappedDomainName($primaryDomainName));
    }

    public function testIsMappedDomainNameWithUnmappedDomainName(): void
    {
        $this->assertFalse((new MappedDomainNames([], 'primary_domain_name'))->isMappedDomainName('unmapped_domain_name'));
    }

    public function testIsMappedDomainNameWithWildcardDomainName(): void
    {
        $this->assertTrue((new MappedDomainNames(['*.mapped_domain_name'], 'primary_domain_name'))->isMappedDomainName('foo.mapped_domain_name'));
    }
}

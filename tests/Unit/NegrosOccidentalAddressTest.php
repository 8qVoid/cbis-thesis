<?php

namespace Tests\Unit;

use App\Support\NegrosOccidentalAddress;
use PHPUnit\Framework\TestCase;

class NegrosOccidentalAddressTest extends TestCase
{
    public function test_complete_address_list_includes_victorias_under_v(): void
    {
        $cities = NegrosOccidentalAddress::cities();
        $labels = array_map(NegrosOccidentalAddress::cityLabel(...), $cities);

        $this->assertCount(32, $cities);
        $this->assertSame(662, array_sum(array_map('count', NegrosOccidentalAddress::barangaysByCity())));
        $this->assertSame('Victorias City', end($labels));
        $this->assertSame('City of Victorias', end($cities));
        $this->assertCount(26, NegrosOccidentalAddress::barangaysFor('City of Victorias'));
    }
}

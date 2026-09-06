<?php

namespace Tests\Unit;

use App\Support\PhilippinePhone;
use PHPUnit\Framework\TestCase;

class PhilippinePhoneTest extends TestCase
{
    public function test_phone_form_input_accepts_digits_only(): void
    {
        $this->assertSame('+639171234567', PhilippinePhone::normalizeMobileInput('9171234567'));
        $this->assertNull(PhilippinePhone::normalizeMobileInput('+639171234567'));
        $this->assertNull(PhilippinePhone::normalizeMobileInput('917-123-4567'));
        $this->assertNull(PhilippinePhone::normalizeMobileInput('917abc4567'));
    }

    public function test_contact_numbers_reject_letters_and_symbols(): void
    {
        $this->assertTrue(PhilippinePhone::isValidContactNumber('09171234567'));
        $this->assertTrue(PhilippinePhone::isValidContactNumber('0344330313'));
        $this->assertFalse(PhilippinePhone::isValidContactNumber('0917-123-4567'));
        $this->assertFalse(PhilippinePhone::isValidContactNumber('0917abc4567'));
        $this->assertFalse(PhilippinePhone::isValidContactNumber('09171234567 OR 1=1'));
    }

    public function test_facility_application_can_use_two_digits_only_numbers(): void
    {
        $this->assertTrue(PhilippinePhone::isValidContactNumber('09171234567,0344330313', true, 2));
        $this->assertFalse(PhilippinePhone::isValidContactNumber('09171234567,(034)4330313', true, 2));
    }
}

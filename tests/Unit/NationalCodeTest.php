<?php

namespace Tests\Unit;

use App\Helpers\Helper;
use PHPUnit\Framework\TestCase;

class NationalCodeTest extends TestCase {
    public function test_validate_generated_codes () {
        for ( $i = 0; $i < 10; $i++ ) {
            $code = Helper::generate();
            $this->assertTrue(Helper::validateNationalCode($code), "Generated code $code should be valid");
        }

        // generate from a given 9-digit prefix
        $code = Helper::generate('001035914'); // 9 digits
        $this->assertTrue(Helper::validateNationalCode($code));
    }

    public function test_it_rejects_invalid_national_codes () : void {
        // List of invalid national codes
        $invalidCodes = [
            '1234567890', // wrong check digit
            '1111111111', // all digits same
            '0000000000', // all zeros
            'abcdefghij', // non-numeric
            '',           // empty string
            '12345',      // less than 10 digits
            '123456789012'// more than 10 digits
        ];

        foreach ( $invalidCodes as $code ) {
            $this->assertFalse(Helper::validateNationalCode($code), "Failed asserting that $code is invalid");
        }
    }
}

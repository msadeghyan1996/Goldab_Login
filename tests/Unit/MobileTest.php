<?php

namespace Tests\Unit;

use App\Helpers\Helper;
use app\Supports\Sanitizer;
use PHPUnit\Framework\TestCase;

class MobileTest extends TestCase {
    /**
     * Test normalizeMobile function
     */
    public function testNormalizeMobile () : void {
        $cases = [
            '۰۹۱۲۱۲۳۴۵۶۷'     => '09121234567',
            '+989121234567'   => '09121234567',
            '00989121234567'  => '09121234567',
            '9121234567'      => '09121234567',
            '%2B989121234567' => '09121234567',
            '0989121234567'   => '09121234567',
            '0912abc1234'     => null,
            '12345'           => null,
            ''                => null,
        ];

        foreach ( $cases as $input => $expected ) {
            $this->assertSame($expected, Helper::normalizeMobile($input));
        }
    }

    /**
     * Test Sanitizer phone|trim rule
     */
    public function testSanitizerPhoneTrim () : void {
        $data = [
            'mobile' => ' 09147233442 ',
            'phone'  => 'tester',
        ];

        $rules = [
            'mobile' => 'phone|trim',
            'phone'  => 'phone|trim',
        ];

        $sanitizer = Sanitizer::make($data, $rules);
        $sanitized = $sanitizer->sanitize();

        $this->assertArrayHasKey('mobile', $sanitized);
        $this->assertSame('09147233442', $sanitized['mobile']);
        $this->assertSame('',$sanitized['phone']);
        $this->assertIsString($sanitized['mobile']);
    }
}

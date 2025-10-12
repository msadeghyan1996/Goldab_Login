<?php

use App\Domain\Auth\Services\DigitNormalizer;

it('normalizes persian and arabic digits to ascii', function () {
    $normalizer = new DigitNormalizer;

    expect($normalizer->normalize('۰۱۲۳۴۵۶۷۸۹'))->toBe('0123456789')
        ->and($normalizer->normalize('٠١٢٣٤٥٦٧٨٩'))->toBe('0123456789')
        ->and($normalizer->normalize('کد ۱۲۳۴'))->toBe('کد 1234');
});

it('does not change ascii digits', function () {
    $normalizer = new DigitNormalizer;

    expect($normalizer->normalize('9876543210'))->toBe('9876543210');
});

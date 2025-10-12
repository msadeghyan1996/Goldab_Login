<?php

use App\Domain\Auth\Services\NationalIdValidator;


it('accepts valid national ids', function () {
    $validator = new NationalIdValidator;

    $validIds = [
        validNationalId(),
        validNationalId(),
        validNationalId(),
    ];

    foreach ($validIds as $id) {
        expect($validator->isValid($id))->toBeTrue();
    }
});

it('rejects invalid formats', function () {
    $validator = new NationalIdValidator;

    expect($validator->isValid('abc'))
        ->toBeFalse()
        ->and($validator->isValid('123456789'))
        ->toBeFalse();
});

it('rejects repeated digits', function () {
    $validator = new NationalIdValidator;

    expect($validator->isValid('1111111111'))->toBeFalse();
});

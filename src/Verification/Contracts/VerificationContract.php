<?php

namespace Src\Verification\Contracts;

use App\Models\Verification;

interface VerificationContract{
    public function create(
        string $phone,
        ?string $password = null
    ) : array;

    public function verify(
        string $code,
        string $phone
    );
}

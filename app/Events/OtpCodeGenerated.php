<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OtpCodeGenerated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $phoneNumber,
        public readonly string $code
    ) {
    }
}

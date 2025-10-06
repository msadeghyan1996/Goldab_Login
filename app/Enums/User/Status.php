<?php

namespace App\Enums\User;

use App\Traits\TranslatableEnum;

enum Status: string {
    use TranslatableEnum;

    case ACTIVE = 'Active';
    case INACTIVE = 'Inactive';

    public function label () : string {
        return match ($this) {
            self::ACTIVE => 'فعال',
            self::INACTIVE => 'غیرفعال',
        };
    }
}

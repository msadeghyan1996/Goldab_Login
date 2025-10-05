<?php

namespace App\Traits;

use App\Utility\EnumTranslationCache;
use BackedEnum;

trait TranslatableEnum {

    abstract public function label () : string;

    public static function options () : array {
        return collect(self::cases())->mapWithKeys(fn($case) => [$case->value => $case->label()])->toArray();
    }

    public function value () : string|int {
        return $this->value;
    }

    public static function values () : array {
        return array_map(fn(BackedEnum $case) => $case->value, self::cases());
    }

    public static function hasValue ($value) : bool {
        return in_array($value, self::values());
    }
}

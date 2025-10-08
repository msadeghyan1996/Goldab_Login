<?php

namespace Src\User\Contracts;

interface UserContract{
    public function completeProfile(
        int $userId,
        array $data
    ): array;

}

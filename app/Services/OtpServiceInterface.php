<?php
namespace App\Services;
interface OtpServiceInterface
{
    public function generateOtp(string $mobileNumber, string $purpose): array;
    public function verifyOtp(string $mobileNumber, string $code, string $purpose): bool;
}
<?php
namespace App\Providers\Sms;
interface SmsProviderInterface
{
    public function send(string $phoneNumber, string $message): array;
    public function getProviderName(): string;
}
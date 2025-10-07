<?php
namespace App\Providers\Email;
interface EmailProviderInterface
{
    public function send(string $email, string $subject, string $message): array;
    public function getProviderName(): string;
}
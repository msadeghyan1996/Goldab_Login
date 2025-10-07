<?php
namespace App\Channels;
interface ChannelInterface
{
    public function send(string $recipient, string $message, array $options = []): bool;
    public function getName(): string;
    public function validateRecipient(string $recipient): bool;
}
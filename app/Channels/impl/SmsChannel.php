<?php
namespace App\Channels\impl;
use App\Channels\ChannelInterface;
use App\Providers\Sms\SmsProviderInterface;
use Illuminate\Support\Facades\Log;
class SmsChannel implements ChannelInterface
{
    protected $provider;
    public function __construct(SmsProviderInterface $provider)
    {
        $this->provider = $provider;
    }
    public function send(string $recipient, string $message, array $options = []): bool
    {
        try {
            if (!$this->validateRecipient($recipient)) {
                Log::warning('Invalid phone number format', [
                    'recipient' => $recipient,
                    'channel' => 'sms',
                ]);
                return false;
            }
            $result = $this->provider->send($recipient, $message);
            if ($result['success']) {
                Log::info('SMS sent successfully', [
                    'recipient' => $recipient,
                    'provider' => $this->provider->getProviderName(),
                    'message_id' => $result['message_id'] ?? null,
                ]);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'recipient' => $recipient,
                'provider' => $this->provider->getProviderName(),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    public function getName(): string
    {
        return 'sms';
    }
    public function validateRecipient(string $recipient): bool
    {
        return preg_match('/^[0-9]{10,15}$/', $recipient) === 1;
    }
}
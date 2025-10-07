<?php
namespace App\Channels\impl;
use App\Providers\Email\EmailProviderInterface;
use App\Channels\ChannelInterface;
use Illuminate\Support\Facades\Log;
class EmailChannel implements ChannelInterface
{
    protected $provider;
    public function __construct(EmailProviderInterface $provider)
    {
        $this->provider = $provider;
    }
    public function send(string $recipient, string $message, array $options = []): bool
    {
        try {
            if (!$this->validateRecipient($recipient)) {
                Log::warning('Invalid email address format', [
                    'recipient' => $recipient,
                    'channel' => 'email',
                ]);
                return false;
            }
            $subject = $options['subject'] ?? 'OTP Verification';
            $result = $this->provider->send($recipient, $subject, $message);
            if ($result['success']) {
                Log::info('Email sent successfully', [
                    'recipient' => $recipient,
                    'provider' => $this->provider->getProviderName(),
                    'message_id' => $result['message_id'] ?? null,
                ]);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Email sending failed', [
                'recipient' => $recipient,
                'provider' => $this->provider->getProviderName(),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    public function getName(): string
    {
        return 'email';
    }
    public function validateRecipient(string $recipient): bool
    {
        return filter_var($recipient, FILTER_VALIDATE_EMAIL) !== false;
    }
}
<?php
namespace App\Services\impl;
use App\Channels\impl\SmsChannel;
use App\Jobs\SendNotificationJob;
use Illuminate\Support\Facades\Queue;
class NotificationService extends BaseService
{
    public function sendAsync(string $recipient, string $message, array $options = []): void
    {
        try {
            Queue::push(new SendNotificationJob(
                SmsChannel::class,
                $recipient,
                $message,
                $options
            ));
            $this->logInfo('SMS queued for async sending', [
                'recipient' => $this->maskRecipient($recipient),
            ]);
        } catch (\Exception $e) {
            $this->handleException($e, 'NotificationService::sendAsync');
            $this->logError('Failed to queue SMS', [
                'recipient' => $this->maskRecipient($recipient),
                'error' => $e->getMessage(),
            ]);
        }
    }
    public function sendSync(string $recipient, string $message, array $options = []): bool
    {
        try {
            $channel = app(SmsChannel::class);
            $success = $channel->send($recipient, $message, $options);
            $this->logInfo('SMS sent synchronously', [
                'recipient' => $this->maskRecipient($recipient),
                'success' => $success,
            ]);
            return $success;
        } catch (\Exception $e) {
            $this->handleException($e, 'NotificationService::sendSync');
            $this->logError('Synchronous SMS failed', [
                'recipient' => $this->maskRecipient($recipient),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    public function sendOtp(string $recipient, string $code, bool $async = true): void
    {
        $message = $this->formatOtpMessage($code);
        if ($async) {
            $this->sendAsync($recipient, $message);
        } else {
            $this->sendSync($recipient, $message);
        }
    }
    protected function formatOtpMessage(string $code): string
    {
        $appName = env('APP_NAME', 'OTP Auth');
        return sprintf(
            "%s Verification\n\nYour code: %s\n\nValid for 5 minutes.\nDo not share this code.",
            $appName,
            $code
        );
    }
    protected function maskRecipient(string $recipient): string
    {
        $length = strlen($recipient);
        return str_repeat('*', $length - 4) . substr($recipient, -4);
    }
}
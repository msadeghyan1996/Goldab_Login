<?php
namespace App\Jobs;
use App\Channels\ChannelInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
class SendNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;
    protected $channelClass;
    protected $recipient;
    protected $message;
    protected $options;
    public $tries = 3;
    public $retryAfter = 60;
    public function __construct(string $channelClass, string $recipient, string $message, array $options = [])
    {
        $this->channelClass = $channelClass;
        $this->recipient = $recipient;
        $this->message = $message;
        $this->options = $options;
    }
    public function handle()
    {
        try {
            $channel = app($this->channelClass);
            $success = $channel->send($this->recipient, $this->message, $this->options);
            if ($success) {
                Log::info('Notification sent successfully (async)', [
                    'channel' => $channel->getName(),
                    'recipient' => $this->recipient,
                ]);
            } else {
                Log::warning('Notification sending failed (async)', [
                    'channel' => $channel->getName(),
                    'recipient' => $this->recipient,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Notification job failed', [
                'channel' => $this->channelClass,
                'recipient' => $this->recipient,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            if ($this->attempts() < $this->tries) {
                $this->release($this->retryAfter);
            }
        }
    }
    public function failed(\Throwable $exception)
    {
        Log::error('Notification job failed permanently', [
            'channel' => $this->channelClass,
            'recipient' => $this->recipient,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
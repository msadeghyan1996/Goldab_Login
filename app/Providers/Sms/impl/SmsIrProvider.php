<?php
namespace App\Providers\Sms\impl;
use App\Providers\Sms\SmsProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
class SmsIrProvider implements SmsProviderInterface
{
    protected const string API_BASE_URL = 'https://api.sms.ir/v1/send';
    protected string $username;
    protected string $password;
    protected string $line;
    public function __construct()
    {
        $this->username = env('SMS_IR_USERNAME');
        $this->password = env('SMS_IR_PASSWORD');
        $this->line = env('SMS_IR_LINE');
    }
    public function send(string $phoneNumber, string $message): array
    {
        try {
            if (empty($this->username) || empty($this->password) || empty($this->line)) {
                throw new \Exception('SMS.ir credentials (username, password, line) are required.');
            }
            $response = Http::withOptions([
                'verify' => env('SMS_IR_VERIFY_SSL', false), 
            ])->get(self::API_BASE_URL, [
                'username' => $this->username,
                'password' => $this->password,
                'line' => $this->line,
                'mobile' => $phoneNumber,
                'text' => $message,
            ]);
            if ($response->successful()) {
                Log::info('SMS.ir: Message sent successfully', [
                    'mobile' => $phoneNumber,
                    'status' => $response->status(),
                ]);
                return [
                    'success' => true,
                    'message_id' => uniqid('smsir_'),
                ];
            }
            $errorMessage = $response->body();
            Log::error('SMS.ir: API error', [
                'mobile' => $phoneNumber,
                'error' => $errorMessage,
                'status' => $response->status(),
            ]);
            return [
                'success' => false,
                'message_id' => null,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('SMS.ir: Exception occurred', [
                'mobile' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
    public function getProviderName(): string
    {
        return 'sms.ir';
    }
}
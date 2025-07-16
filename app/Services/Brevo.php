<?php

namespace App\Services;

use App\Models\Meta;
use Illuminate\Support\Facades\Log;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use SendinBlue\Client\Configuration;
use GuzzleHttp\Client;
use SendinBlue\Client\Model\SendSmtpEmail;

class Brevo
{
    protected TransactionalEmailsApi $apiInstance;
    protected array $to = [];
    protected array $cc = [];
    protected array $bcc = [];
    protected string $subject = 'No Subject';
    protected string $htmlContent = '';
    protected array $attachments = [];
    protected array $sender = [];
    protected array $headers = [];

    public function __construct()
    {
        try {
            $settings = Meta::getValue("brevo_settings");
        } catch (\Exception $e) {
            $settings = [
                "api_key" => env('BREVO_API_KEY'),
                "sender" => [
                    "email" => env('BREVO_SENDER_EMAIL'),
                    "name" => env('BREVO_SENDER_NAME'),
                ]
            ];
        }

        if (!$settings || !isset($settings['api_key'], $settings['sender']['email'])) {
            throw new \Exception('Brevo configuration is missing or invalid.');
        }

        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $settings["api_key"]);
        $this->apiInstance = new TransactionalEmailsApi(new Client(), $config);
        $this->sender = $settings["sender"];
    }

    public function to(string $email, string $name = "Diji"): self
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->to[] = ['email' => $email, "name" => $name];
        }
        return $this;
    }

    public function cc($emails): self
    {
        foreach ((array) $emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->cc[] = ['email' => $email];
            }
        }
        return $this;
    }

    public function bcc($emails): self
    {
        foreach ((array) $emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->bcc[] = ['email' => $email];
            }
        }
        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function view(string $view, array $data = []): self
    {
        $this->htmlContent = view($view, $data)->render();
        return $this;
    }

    public function content(string $content): self
    {
        $this->htmlContent = $content;
        return $this;
    }

    public function from(string $email, string $name = null): self
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->sender = [
                'email' => $email,
                'name' => $name ?? $email,
            ];
        }
        return $this;
    }

    public function attachments(array $attachments): self
    {
        foreach ($attachments as $attachment) {
            if (!empty($attachment['filename']) && !empty($attachment['output'])) {
                $this->attachments[] = [
                    'name' => $attachment['filename'],
                    'content' => base64_encode($attachment['output']),
                ];
            }
        }
        return $this;
    }

    public function headers(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    public function send(): bool
    {
        try {
            if (empty($this->to)) {
                throw new \Exception('Recipient email(s) missing.');
            }

            $data = [
                'sender' => $this->sender,
                'to' => $this->to,
                'subject' => $this->subject,
                'htmlContent' => $this->htmlContent,
            ];

            if (!empty($this->cc)) {
                $data['cc'] = $this->cc;
            }

            if (!empty($this->bcc)) {
                $data['bcc'] = $this->bcc;
            }

            if (!empty($this->attachments)) {
                $data['attachment'] = $this->attachments;
            }

            if (!empty($this->headers)) {
                $data['headers'] = $this->headers;
            }

            $email = new SendSmtpEmail($data);
            $this->apiInstance->sendTransacEmail($email);
            $this->reset();
            return true;
        } catch (\Throwable $e) {
            Log::error('Brevo Send Error: ' . $e->getMessage());
            return false;
        }
    }

    protected function reset(): void
    {
        $this->to = [];
        $this->cc = [];
        $this->bcc = [];
        $this->subject = 'No Subject';
        $this->htmlContent = '';
        $this->attachments = [];
        $this->headers = [];
    }
}

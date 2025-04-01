<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use SendinBlue\Client\Configuration;
use GuzzleHttp\Client;
use SendinBlue\Client\Model\SendSmtpEmail;

class Brevo {
    protected TransactionalEmailsApi $apiInstance;
    protected array $to = [];
    protected array $cc = [];
    protected string $subject = 'No Subject';
    protected string $htmlContent = '';
    protected array $attachments = [];
    protected array $sender = [];

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', env('BREVO_API_KEY'));
        $this->apiInstance = new TransactionalEmailsApi(new Client(), $config);
        $this->sender = [
            'email' => env('MAIL_FROM_ADDRESS'),
            'name' => config('app.name'),
        ];
    }

    public function to(string $email): self
    {
        $this->to[] = ['email' => $email];
        return $this;
    }

    public function cc($emails): self
    {
        if(!$emails){
            return $this;
        }

        $emails = is_array($emails) ? $emails : [$emails];
        foreach ($emails as $email) {
            $this->cc[] = ['email' => $email];
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
        $this->sender = [
            'email' => $email,
            'name' => $name ?? $email,
        ];
        return $this;
    }

    public function attachments(array $attachments): self
    {
        foreach ($attachments as $attachment) {
            $this->attachments[] = [
                'name' => $attachment['filename'],
                'content' => base64_encode($attachment['output']),
            ];
        }
        return $this;
    }

    public function send(): bool
    {
        try {
            $data = [
                'sender' => $this->sender,
                'to' => $this->to,
                'subject' => $this->subject,
                'htmlContent' => $this->htmlContent,
            ];

            if($this->cc){
                $data["cc"] = $this->cc;
            }

            if($this->attachments){
                $data['attachment'] = $this->attachments;
            }

            $email = new SendSmtpEmail($data);

            $this->apiInstance->sendTransacEmail($email);
            return true;
        } catch (\Exception $e) {
            Log::error('Brevo Send Error: ' . $e->getMessage());
            return false;
        }
    }
}

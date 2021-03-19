<?php
namespace com\selfcoders\financetracker;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class NotificationRecipient
{
    const TYPE_EMAIL = "email";
    const TYPE_PUSHOVER_LOW = "pushover_low";
    const TYPE_PUSHOVER_HIGH = "pushover_high";

    public string $type;
    public string $target;

    public static function fromString(string $string): NotificationRecipient
    {
        $recipient = new self;

        list($recipient->type, $recipient->target) = explode(":", $string, 2);

        return $recipient;
    }

    public function __toString(): string
    {
        return implode(":", [$this->type, $this->target]);
    }

    public function sendForWatchListEntries(array $entries)
    {
        $subject = "Stock limit reached";
        $body = TwigRenderer::render("notification", [
            "entries" => $entries
        ]);

        switch ($this->type) {
            case self::TYPE_EMAIL:
                $this->sendMail($subject, $body);
                break;
            case self::TYPE_PUSHOVER_LOW:
                $this->sendPushover($subject, $body, getenv("PUSHOVER_LOW_SOUND"));
                break;
            case self::TYPE_PUSHOVER_HIGH:
                $this->sendPushover($subject, $body, getenv("PUSHOVER_HIGH_SOUND"));
                break;
        }
    }

    private function sendMail(string $subject, string $body)
    {
        $smtpPort = getenv("SMTP_PORT");
        if ($smtpPort === false) {
            $smtpPort = 25;
        }

        $transport = new Swift_SmtpTransport(getenv("SMTP_HOST"), $smtpPort, "tls");
        $transport->setUsername(getenv("SMTP_USERNAME"));
        $transport->setPassword(getenv("SMTP_PASSWORD"));

        $mailer = new Swift_Mailer($transport);

        $message = new Swift_Message($subject);
        $message->setFrom(getenv("MAIL_FROM"));
        $message->setTo($this->target);
        $message->setBody($body);

        $mailer->send($message);
    }

    private function sendPushover(string $title, string $message, string $sound)
    {
        $client = new Client;
        $client->post("https://api.pushover.net/1/messages.json", [
            RequestOptions::FORM_PARAMS => [
                "token" => getenv("PUSHOVER_TOKEN"),
                "user" => $this->target,
                "title" => $title,
                "message" => $message,
                "sound" => $sound
            ]
        ]);
    }
}
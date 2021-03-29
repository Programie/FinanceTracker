<?php
namespace com\selfcoders\financetracker;

use com\selfcoders\financetracker\models\WatchListEntry;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class NotificationRecipient
{
    const TYPE_EMAIL = "email";
    const TYPE_PUSHOVER = "pushover";

    public string $type;
    public string $limitType;
    public string $target;

    public static function fromString(string $string): NotificationRecipient
    {
        $recipient = new self;

        list($recipient->type, $recipient->limitType, $recipient->target) = explode(":", $string, 3);

        return $recipient;
    }

    public function __toString(): string
    {
        return implode(":", [$this->type, $this->limitType, $this->target]);
    }

    public function sendForWatchListEntry(WatchListEntry $entry)
    {
        if ($entry->getReachedLimitType() !== $this->limitType) {
            return;
        }

        $subject = sprintf("Stock limit reached - %s", $entry->getName());
        $body = TwigRenderer::render("notification", [
            "entry" => $entry,
            "limitType" => $this->limitType
        ]);

        switch ($this->type) {
            case self::TYPE_EMAIL:
                $this->sendMail($subject, $body);
                break;
            case self::TYPE_PUSHOVER:
                switch ($this->limitType) {
                    case WatchListEntry::LIMIT_TYPE_LOW:
                        $this->sendPushover($subject, $body, getenv("PUSHOVER_LOW_SOUND"));
                        break;
                    case WatchListEntry::LIMIT_TYPE_HIGH:
                        $this->sendPushover($subject, $body, getenv("PUSHOVER_HIGH_SOUND"));
                        break;
                }
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
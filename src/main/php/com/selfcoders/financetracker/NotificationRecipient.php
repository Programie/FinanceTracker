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
    const TYPE_PUSHOVER = "pushover";

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
                break;
            case self::TYPE_PUSHOVER:
                $client = new Client;
                $client->post("https://api.pushover.net/1/messages.json", [
                    RequestOptions::FORM_PARAMS => [
                        "token" => getenv("PUSHOVER_TOKEN"),
                        "user" => $this->target,
                        "message" => $body,
                        "title" => $subject,
                        "sound" => getenv("PUSHOVER_SOUND")
                    ]
                ]);
                break;
        }
    }
}
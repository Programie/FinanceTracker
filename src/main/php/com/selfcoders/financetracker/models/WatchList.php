<?php
namespace com\selfcoders\financetracker\models;

use com\selfcoders\financetracker\NotificationRecipient;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="com\selfcoders\financetracker\orm\WatchListRepository")
 * @ORM\Table(name="watchlists")
 */
class WatchList
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private int $id;
    /**
     * @ORM\Column(type="string", unique=true)
     */
    private string $name;
    /**
     * @ORM\Column(type="string")
     */
    private ?string $notificationRecipients;
    /**
     * @var WatchListEntry[]
     * @ORM\OneToMany(targetEntity="WatchListEntry", mappedBy="watchList")
     */
    private mixed $entries;

    public function __construct()
    {
        $this->entries = new ArrayCollection;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return WatchList
     */
    public function setName(string $name): WatchList
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return NotificationRecipient[]
     */
    public function getNotificationRecipients(): array
    {
        if ($this->notificationRecipients === null) {
            return [];
        }

        $recipients = [];

        $lines = array_filter(array_unique(explode("\n", str_replace("\r", "", $this->notificationRecipients))));
        foreach ($lines as $line) {
            $recipients[] = NotificationRecipient::fromString($line);
        }

        return $recipients;
    }

    /**
     * @return WatchListEntry[]
     */
    public function getEntries()
    {
        return $this->entries;
    }
}
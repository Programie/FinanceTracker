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
     * @ORM\Column(type="string", columnDefinition="enum('bid', 'ask')")
     */
    private string $priceType;
    /**
     * @ORM\Column(type="integer")
     */
    private int $updateInterval;
    /**
     * @ORM\Column(type="string")
     */
    private ?string $notificationRecipients;
    /**
     * @ORM\Column(type="boolean")
     */
    private bool $notificationsEnabled;
    /**
     * @var WatchListEntry[]
     * @ORM\OneToMany(targetEntity="WatchListEntry", mappedBy="watchList")
     * @ORM\OrderBy({"name"="ASC"})
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
     * @return string
     */
    public function getPriceType(): string
    {
        return $this->priceType;
    }

    /**
     * @param string $priceType
     * @return WatchList
     */
    public function setPriceType(string $priceType): WatchList
    {
        $this->priceType = $priceType;
        return $this;
    }

    public function getUpdateInterval(): int
    {
        return $this->updateInterval;
    }

    /**
     * @param int $updateInterval
     * @return WatchList
     */
    public function setUpdateInterval(int $updateInterval): WatchList
    {
        $this->updateInterval = $updateInterval;
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
     * @return bool
     */
    public function isNotificationsEnabled(): bool
    {
        return $this->notificationsEnabled;
    }

    /**
     * @param bool $notificationsEnabled
     * @return WatchList
     */
    public function setNotificationsEnabled(bool $notificationsEnabled): WatchList
    {
        $this->notificationsEnabled = $notificationsEnabled;
        return $this;
    }

    /**
     * @return WatchListEntry[]
     */
    public function getEntries()
    {
        return $this->entries;
    }

    public function getNotifiedEntries()
    {
        $entries = [];

        foreach ($this->entries as $entry) {
            if ($entry->notificationTriggered()) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * @return array
     */
    public function getTotal(): array
    {
        $count = 0;
        $price = 0;
        $totalPrice = 0;
        $currentPrice = 0;
        $currentTotalPrice = 0;
        $priceDifference = 0;
        $totalPriceDifference = 0;

        foreach ($this->getEntries() as $entry) {
            $count += $entry->getCount();
            $price += $entry->getPrice();
            $totalPrice += $entry->getTotalPrice();
            $currentPrice += $entry->getCurrentPrice();
            $currentTotalPrice += $entry->getCurrentTotalPrice();
            $priceDifference += $entry->getPriceDifference();
            $totalPriceDifference += $entry->getTotalPriceDifference();
        }

        return [
            "count" => $count,
            "price" => $price,
            "totalPrice" => $totalPrice,
            "currentPrice" => $currentPrice,
            "currentTotalPrice" => $currentTotalPrice,
            "priceDifference" => $priceDifference,
            "totalPriceDifference" => $totalPriceDifference
        ];
    }
}
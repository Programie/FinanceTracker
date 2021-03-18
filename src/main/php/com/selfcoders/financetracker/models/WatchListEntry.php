<?php
namespace com\selfcoders\financetracker\models;

use com\selfcoders\financetracker\Date;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Entity(repositoryClass="com\selfcoders\financetracker\orm\WatchListEntryRepository")
 * @ORM\Table(name="watchlistentries")
 */
class WatchListEntry implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private int $id;
    /**
     * @ORM\ManyToOne(targetEntity="WatchList", inversedBy="entries")
     * @ORM\JoinColumn(name="watchListId", referencedColumnName="id")
     */
    private WatchList $watchList;
    /**
     * @ORM\Column(type="string")
     */
    private string $isin;
    /**
     * @ORM\Column(type="string")
     */
    private string $name;
    /**
     * @ORM\Column(type="date", name="`date`")
     */
    private ?Date $date;
    /**
     * @ORM\Column(type="float")
     */
    private float $count;
    /**
     * @ORM\Column(type="float")
     */
    private float $price;
    /**
     * @ORM\Column(type="boolean")
     */
    private bool $limitEnabled;
    /**
     * @ORM\Column(type="string")
     */
    private ?string $limitType;
    /**
     * @ORM\Column(type="float", name="`limit`")
     */
    private ?float $limit;
    /**
     * @ORM\Column(type="boolean")
     */
    private bool $newsEnabled;
    /**
     * @ORM\Column(type="boolean")
     */
    private bool $notified = false;
    /**
     * @ORM\OneToOne(targetEntity="State")
     * @ORM\JoinColumn(name="stateId", referencedColumnName="id")
     */
    private ?State $state;
    /**
     * @ORM\Column(type="integer")
     */
    private ?int $stateId;

    /**
     * @return WatchList
     */
    public function getWatchList(): WatchList
    {
        return $this->watchList;
    }

    /**
     * @param WatchList $watchList
     * @return WatchListEntry
     */
    public function setWatchList(WatchList $watchList): WatchListEntry
    {
        $this->watchList = $watchList;
        return $this;
    }

    /**
     * @return string
     */
    public function getIsin(): string
    {
        return $this->isin;
    }

    /**
     * @param string $isin
     * @return WatchListEntry
     */
    public function setIsin(string $isin): WatchListEntry
    {
        $this->isin = $isin;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return WatchListEntry
     */
    public function setName(string $name): WatchListEntry
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Date|null
     */
    public function getDate(): ?Date
    {
        return $this->date;
    }

    /**
     * @param Date $date
     * @return WatchListEntry
     */
    public function setDate(Date $date): WatchListEntry
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return float
     */
    public function getCount(): float
    {
        return $this->count;
    }

    /**
     * @param float $count
     * @return WatchListEntry
     */
    public function setCount(float $count): WatchListEntry
    {
        $this->count = $count;
        return $this;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @param float $price
     * @return WatchListEntry
     */
    public function setPrice(float $price): WatchListEntry
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLimitEnabled(): bool
    {
        return $this->limitEnabled;
    }

    /**
     * @param bool $limitEnabled
     * @return WatchListEntry
     */
    public function setLimitEnabled(bool $limitEnabled): WatchListEntry
    {
        $this->limitEnabled = $limitEnabled;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLimitType(): ?string
    {
        return $this->limitType;
    }

    /**
     * @param string|null $limitType
     * @return WatchListEntry
     */
    public function setLimitType(?string $limitType): WatchListEntry
    {
        $this->limitType = $limitType;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getLimit(): ?float
    {
        return $this->limit;
    }

    /**
     * @param float|null $limit
     * @return WatchListEntry
     */
    public function setLimit(?float $limit): WatchListEntry
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNewsEnabled(): bool
    {
        return $this->newsEnabled;
    }

    /**
     * @param bool $newsEnabled
     * @return WatchListEntry
     */
    public function setNewsEnabled(bool $newsEnabled): WatchListEntry
    {
        $this->newsEnabled = $newsEnabled;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNotified(): bool
    {
        return $this->notified;
    }

    /**
     * @param bool $notified
     * @return WatchListEntry
     */
    public function setNotified(bool $notified): WatchListEntry
    {
        $this->notified = $notified;
        return $this;
    }

    /**
     * @return State|null
     */
    public function getState(): ?State
    {
        if ($this->stateId === null) {
            return null;
        }

        return $this->state;
    }

    /**
     * @param State|null $state
     * @return WatchListEntry
     */
    public function setState(?State $state): WatchListEntry
    {
        $this->state = $state;
        $this->stateId = $state?->getId();
        return $this;
    }

    public function getTotalPrice(): float
    {
        return $this->price * $this->count;
    }

    public function getCurrentPrice(): ?float
    {
        return $this->getState()?->getPrice();
    }

    public function getCurrentTotalPrice(): ?float
    {
        $price = $this->getCurrentPrice();
        if ($price === null) {
            return null;
        }

        return $price * $this->count;
    }

    public function getPriceDifference(): ?float
    {
        $currentPrice = $this->getCurrentPrice();
        if ($currentPrice === null) {
            return null;
        }

        return $currentPrice - $this->price;
    }

    public function getTotalPriceDifference(): ?float
    {
        $difference = $this->getPriceDifference();
        if ($difference === null) {
            return null;
        }

        return $difference * $this->count;
    }

    public function getLimitDifference(): ?float
    {
        if (!$this->isLimitEnabled()) {
            return null;
        }

        if ($this->limit === null) {
            return null;
        }

        $currentPrice = $this->getCurrentPrice();
        if ($currentPrice === null) {
            return null;
        }

        return $currentPrice - $this->limit;
    }

    public function hasReachedLimit(): bool
    {
        $difference = $this->getLimitDifference();
        if ($difference === null) {
            return false;
        }

        if ($this->limitType === null) {
            return false;
        }

        $limitType = strtolower($this->limitType);
        if ($limitType === "high" and $difference >= 0) {
            return true;
        } elseif ($limitType === "low" and $difference <= 0) {
            return true;
        } else {
            return false;
        }
    }

    public function jsonSerialize()
    {
        return [
            "isin" => $this->getIsin(),
            "name" => $this->getName(),
            "count" => $this->getCount(),
            "watchDate" => $this->getDate(),
            "watchValue" => $this->getPrice(),
            "currentValue" => $this->getCurrentPrice(),
            "dayStartValue" => $this->getState()?->getDayStartPrice(),
            "limitEnabled" => $this->isLimitEnabled(),
            "limitType" => $this->getLimitType(),
            "limit" => $this->getLimit(),
            "notified" => $this->isNotified()
        ];
    }
}
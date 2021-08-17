<?php
namespace com\selfcoders\financetracker\models;

use com\selfcoders\financetracker\DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="com\selfcoders\financetracker\orm\StateRepository")
 * @ORM\Table(name="states")
 */
class State
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
    private string $isin;
    /**
     * @ORM\Column(type="string")
     */
    private string $name;
    /**
     * @ORM\Column(type="string", columnDefinition="enum('bid', 'ask')")
     */
    private string $priceType;
    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $fetched;
    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $updated;
    /**
     * @ORM\Column(type="float")
     */
    private float $price;
    /**
     * @ORM\Column(type="float")
     */
    private ?float $previousPrice;
    /**
     * @ORM\Column(type="float")
     */
    private ?float $dayStartPrice;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
     * @return State
     */
    public function setIsin(string $isin): State
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
     * @return State
     */
    public function setName(string $name): State
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
     * @return State
     */
    public function setPriceType(string $priceType): State
    {
        $this->priceType = $priceType;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getFetched(): DateTime
    {
        return $this->fetched;
    }

    /**
     * @param DateTime $fetched
     * @return State
     */
    public function setFetched(DateTime $fetched): State
    {
        $this->fetched = $fetched;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdated(): DateTime
    {
        return $this->updated;
    }

    /**
     * @param DateTime $updated
     * @return State
     */
    public function setUpdated(DateTime $updated): State
    {
        $this->updated = $updated;
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
     * @return State
     */
    public function setPrice(float $price): State
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getPreviousPrice(): ?float
    {
        return $this->previousPrice;
    }

    /**
     * @param float|null $previousPrice
     * @return State
     */
    public function setPreviousPrice(?float $previousPrice): State
    {
        $this->previousPrice = $previousPrice;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getDayStartPrice(): ?float
    {
        return $this->dayStartPrice;
    }

    /**
     * @param float|null $dayStartPrice
     * @return State
     */
    public function setDayStartPrice(?float $dayStartPrice): State
    {
        $this->dayStartPrice = $dayStartPrice;
        return $this;
    }
}
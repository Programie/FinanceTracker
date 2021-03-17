<?php
namespace com\selfcoders\financetracker\models;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="news")
 */
class News
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private int $id;
    /**
     * @ORM\Column(type="string")
     */
    private string $isin;
    /**
     * @ORM\Column(type="string")
     */
    private string $name;
    /**
     * @ORM\Column(type="string")
     */
    private string $items;

    /**
     * @return string
     */
    public function getIsin(): string
    {
        return $this->isin;
    }

    /**
     * @param string $isin
     * @return News
     */
    public function setIsin(string $isin): News
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
     * @return News
     */
    public function setName(string $name): News
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return NewsItem[]
     */
    public function getItems(): array
    {
        $json = json_decode($this->items, true);

        $items = [];

        foreach ($json as $itemArray) {
            $items[] = NewsItem::fromArray($itemArray);
        }

        return $items;
    }

    /**
     * @param NewsItem[] $items
     * @return News
     */
    public function setItems(array $items): News
    {
        $this->items = json_encode($items);
        return $this;
    }
}
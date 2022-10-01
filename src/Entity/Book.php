<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\DescriptionEntityTrait;
use App\Entity\Traits\QuantityEntityTrait;
use App\Entity\Traits\TitleEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="books",
 *     options={"engine":"MyISAM"},
 * )
 */
class Book extends AbstractEntity
{
    use TitleEntityTrait,
        DescriptionEntityTrait,
        QuantityEntityTrait;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\AuthorBook", mappedBy="book")
     */
    protected Collection $authorBooks;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Reading", mappedBy="book")
     */
    protected Collection $reading;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Order", mappedBy="book")
     */
    protected Collection $orders;

    public function __construct()
    {
        $this->authorBooks = new ArrayCollection();
        $this->reading     = new ArrayCollection();
        $this->orders      = new ArrayCollection();
    }

    /**
     * @return Collection|AuthorBook[]
     */
    public function getAuthorBooks(): Collection
    {
        return $this->authorBooks;
    }

    public function addAuthorBook(AuthorBook $authorBook): self
    {
        if (!$this->authorBooks->contains($authorBook)) {
            $this->authorBooks[] = $authorBook;
            $authorBook->setBook($this);
        }

        return $this;
    }

    public function removeAuthorBook(AuthorBook $authorBook): self
    {
        if ($this->authorBooks->removeElement($authorBook)) {
            // set the owning side to null (unless already changed)
            if ($authorBook->getBook() === $this) {
                $authorBook->setBook(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Reading[]
     */
    public function getReading(): Collection
    {
        return $this->reading;
    }

    public function addReading(Reading $reading): self
    {
        if (!$this->reading->contains($reading)) {
            $this->reading[] = $reading;
            $reading->setBook($this);
        }

        return $this;
    }

    public function removeReading(Reading $reading): self
    {
        if ($this->reading->removeElement($reading)) {
            // set the owning side to null (unless already changed)
            if ($reading->getBook() === $this) {
                $reading->setBook(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Order[]
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): self
    {
        if (!$this->orders->contains($order)) {
            $this->orders[] = $order;
            $order->setBook($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): self
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getBook() === $this) {
                $order->setBook(null);
            }
        }

        return $this;
    }

}

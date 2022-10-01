<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\QuantityEntityTrait;
use App\Entity\Traits\Timestampable\CreatedAtEntityTrait;
use App\Entity\Traits\Timestampable\EndAtEntityTrait;
use App\Entity\Traits\Timestampable\StartAtEntityTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="orders",
 *     options={"engine":"MyISAM"},
 *      indexes={
 *          @ORM\Index(name="fk_book_id", columns={"book_id"}),
 *          @ORM\Index(name="fk_user_id", columns={"user_id"}),
 *     }
 * )
 */
class Order extends AbstractEntity
{

    use StartAtEntityTrait,
        EndAtEntityTrait,
        QuantityEntityTrait,
        CreatedAtEntityTrait;

    public const STATUS_OPEN     = 1;
    public const STATUS_DONE     = 2;
    public const STATUS_CANCELED = 3;

    public const STATUSES = [
        self::STATUS_OPEN     => 'Open',
        self::STATUS_DONE     => 'Done',
        self::STATUS_CANCELED => 'Canceled',
    ];

    /**
     * @ORM\Column(type="integer", options={"unsigned": true})
     */
    protected ?int $readingType = null;

    /**
     * @ORM\Column(name="status", type="smallint", nullable=true, options={"unsigned": true})
     */
    protected ?int $status = self::STATUS_OPEN;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Book", inversedBy="orders")
     * @ORM\JoinColumn(name="book_id", referencedColumnName="id")
     */
    protected ?Book $book;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="orders")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected ?User $user;

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): self
    {
        $this->book = $book;

        return $this;
    }

    public function getReadingType(): ?int
    {
        return $this->readingType;
    }

    public function setReadingType(int $readingType): self
    {
        $this->readingType = $readingType;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }


}

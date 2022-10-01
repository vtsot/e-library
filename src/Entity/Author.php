<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\Contact\FirstNameEntityTrait;
use App\Entity\Traits\Contact\LastNameEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use function array_filter;
use function implode;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="authors",
 *     options={"engine":"MyISAM"},
 * )
 */
class Author extends AbstractEntity
{

    use FirstNameEntityTrait,
        LastNameEntityTrait;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\AuthorBook", mappedBy="author")
     */
    protected Collection $authorBooks;

    public function __construct()
    {
        $this->authorBooks = new ArrayCollection();
    }

    public function __toString(): string
    {
        $labels = [];
        if (!$this->getId()) {
            $labels[] = 'New Author';
        } else {
            $labels[] = $this->getFirstName();
            $labels[] = $this->getLastName();
        }

        return implode(' ', array_filter($labels));
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
            $authorBook->setAuthor($this);
        }

        return $this;
    }

    public function removeAuthorBook(AuthorBook $authorBook): self
    {
        if ($this->authorBooks->removeElement($authorBook)) {
            // set the owning side to null (unless already changed)
            if ($authorBook->getAuthor() === $this) {
                $authorBook->setAuthor(null);
            }
        }

        return $this;
    }
}

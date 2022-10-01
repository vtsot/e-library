<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\NameEntityTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="categories",
 *     options={"engine":"MyISAM"},
 * )
 */
class Category extends AbstractEntity
{
    use NameEntityTrait;
}

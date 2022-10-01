<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\DataFixtures\Traits\EntityDataFixtureTrait;
use App\Entity\BookCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class BookCategoryFixtures extends Fixture implements DependentFixtureInterface
{
    use EntityDataFixtureTrait;

    public const MAX_CATEGORY = 5;

    public function getDependencies()
    {
        return [
            BookFixtures::class,
            CategoryFixtures::class,
        ];
    }

    public function load(ObjectManager $manager)
    {
        for ($i = 0; $i <= BookFixtures::COUNT_BOOKS; $i++) {
            $bookReferenceId = 'book-' . $i;
            $maxCategory     = random_int(1, self::MAX_CATEGORY);
            for ($j = 1; $j <= $maxCategory; $j++) {
                $categoryReferenceId = 'category-' . random_int(0, CategoryFixtures::COUNT_CATEGORIES);
                $data              = [
                    'book'     => $this->getReference($bookReferenceId),
                    'category' => $this->getReference($categoryReferenceId)
                ];

                $entity = $this->createEntity(BookCategory::class, $data);
                $manager->persist($entity);
            }
        }

        // save
        $manager->flush();
    }

}

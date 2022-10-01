<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\DataFixtures\Traits\EntityDataFixtureTrait;
use App\Entity\AuthorBook;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AuthorBookFixtures extends Fixture implements DependentFixtureInterface
{
    use EntityDataFixtureTrait;

    public const MAX_AUTHORS = 5;

    public function getDependencies()
    {
        return [
            BookFixtures::class,
            AuthorFixtures::class,
        ];
    }

    public function load(ObjectManager $manager)
    {
        for ($i = 0; $i <= BookFixtures::COUNT_BOOKS; $i++) {
            $bookReferenceId = 'book-' . $i;
            $maxAuthor       = random_int(1, self::MAX_AUTHORS);
            for ($j = 1; $j <= $maxAuthor; $j++) {
                $authorReferenceId = 'author-' . random_int(0, AuthorFixtures::COUNT_AUTHORS);
                $data              = [
                    'book'   => $this->getReference($bookReferenceId),
                    'author' => $this->getReference($authorReferenceId)
                ];

                $entity = $this->createEntity(AuthorBook::class, $data);
                $manager->persist($entity);
            }
        }

        // save
        $manager->flush();
    }

}

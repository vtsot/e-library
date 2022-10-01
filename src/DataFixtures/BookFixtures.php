<?php
declare(strict_types=1);

namespace App\DataFixtures;

use App\DataFixtures\Traits\EntityDataFixtureTrait;
use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class BookFixtures extends Fixture implements DependentFixtureInterface
{
    use EntityDataFixtureTrait;

    public const COUNT_BOOKS = 100;

    public function getDependencies()
    {
        return [
            UserFixtures::class,
            AuthorFixtures::class,
            CategoryFixtures::class,
        ];
    }

    public function load(ObjectManager $manager)
    {
        for ($i = 0; $i <= self::COUNT_BOOKS; $i++) {
            $data   = $this->createData('book', $i);
            $entity = $this->createEntity(Book::class, $data);
            $manager->persist($entity);
            $this->addReference('book-' . $i, $entity);
        }

        // save
        $manager->flush();
    }

    private function createData(string $prefix, int $index): array
    {
        $indexKey = $this->createIndexKey($index);
        $suffix   = ($indexKey ? '-' . $indexKey : '');

        return [
            'quantity'    => random_int(1, 20),
            'title'       => $prefix . $suffix . '-Title',
            'description' => $prefix . $suffix . '-Description',
        ];
    }


}

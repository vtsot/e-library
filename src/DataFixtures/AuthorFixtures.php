<?php
declare(strict_types=1);

namespace App\DataFixtures;

use App\DataFixtures\Traits\EntityDataFixtureTrait;
use App\Entity\Author;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AuthorFixtures extends Fixture
{
    use EntityDataFixtureTrait;

    public const COUNT_AUTHORS = 10;

    public function load(ObjectManager $manager)
    {
        for ($i = 0; $i <= self::COUNT_AUTHORS; $i++) {
            $data   = $this->createData('author', $i);
            $entity = $this->createEntity(Author::class, $data);
            $manager->persist($entity);
            $this->addReference('author-' . $i, $entity);
        }

        // save
        $manager->flush();
    }

    private function createData(string $prefix, int $index): array
    {
        $indexKey = $this->createIndexKey($index);
        $suffix   = ($indexKey ? '-' . $indexKey : '');

        return [
            'firstName'     => $prefix . $suffix . 'FirstName',
            'lastName'      => $prefix . $suffix . 'LastName',
        ];
    }

}

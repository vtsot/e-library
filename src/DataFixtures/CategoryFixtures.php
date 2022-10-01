<?php
declare(strict_types=1);

namespace App\DataFixtures;

use App\DataFixtures\Traits\EntityDataFixtureTrait;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    use EntityDataFixtureTrait;

    public const COUNT_CATEGORIES = 10;

    public function load(ObjectManager $manager)
    {
        for ($i = 0; $i <= self::COUNT_CATEGORIES; $i++) {
            $data   = $this->createData('Category', $i);
            $entity = $this->createEntity(Category::class, $data);
            $manager->persist($entity);
            $this->addReference('category-' . $i, $entity);
        }

        // save
        $manager->flush();
    }

    private function createData(string $prefix, int $index): array
    {
        $indexKey = $this->createIndexKey($index);
        $suffix   = ($indexKey ? '-' . $indexKey : '');

        return [
            'name' => $prefix . $suffix . 'Name',
        ];
    }

}

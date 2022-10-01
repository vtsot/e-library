<?php
declare(strict_types=1);

namespace App\DataFixtures;

use App\DataFixtures\Traits\EntityDataFixtureTrait;
use App\Entity\Contracts\UserInterface;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    use EntityDataFixtureTrait;

    public const COUNT_ADMINS     = 2;
    public const COUNT_LIBRARIANS = 5;
    public const COUNT_READERS    = 10;

    public function load(ObjectManager $manager)
    {
        for ($i = 0; $i <= self::COUNT_ADMINS; $i++) {
            $data   = $this->createData(UserInterface::ROLE_ADMIN, 'admin', $i);
            $entity = $this->createEntity(User::class, $data);
            $manager->persist($entity);
            $this->addReference('admin-' . $i, $entity);
        }

        for ($i = 0; $i <= self::COUNT_LIBRARIANS; $i++) {
            $data   = $this->createData(UserInterface::ROLE_LIBRARIAN, 'lib', $i);
            $entity = $this->createEntity(User::class, $data);
            $manager->persist($entity);
            $this->addReference('librarian-' . $i, $entity);
        }

        for ($i = 0; $i <= self::COUNT_READERS; $i++) {
            $data   = $this->createData(UserInterface::ROLE_READER, 'reader', $i);
            $entity = $this->createEntity(User::class, $data);
            $manager->persist($entity);
            $this->addReference('reader-' . $i, $entity);
        }

        // save
        $manager->flush();
    }

    private function createData(string $role, string $prefix, int $index): array
    {
        $indexKey = $this->createIndexKey($index);
        $suffix   = ($indexKey ? '-' . $indexKey : '');

        return [
            'username'      => $prefix . $suffix,
            'email'         => $prefix . $suffix . '@example.com',
            'firstName'     => $prefix . $suffix . 'FirstName',
            'lastName'      => $prefix . $suffix . 'LastName',
            'plainPassword' => $prefix . $suffix,
            'roles'         => [$role],
            'active'        => true,
        ];
    }

}

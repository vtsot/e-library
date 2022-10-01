<?php

namespace App\DataFixtures\Traits;

use App\Entity\AbstractEntity;
use App\Entity\Contracts\EntityInterface;
use function sprintf;

trait EntityDataFixtureTrait
{
    private function createIndexKey(int $i): ?string
    {
        return $i <= 0 ? null : (string)sprintf("%04d", $i);
    }

    private function createEntity(string $class, array $data): AbstractEntity
    {
        /** @var AbstractEntity $obj */
        $obj = (new $class);
        $obj->update($data);

        return $obj;
    }
}

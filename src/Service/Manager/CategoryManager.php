<?php
declare(strict_types=1);

namespace App\Service\Manager;

class CategoryManager extends AbstractManager
{

    public function query(array $filter = []): array
    {
        $params = [];
        $sqlWhere = '';
        $sqlMatch = '';

        if (!empty($filter['q'])) {

            $sqlWhere .= $this->resolveSqlWhere(
                ['categories.name'],
                $filter['mode'] ?? null
            );

            $sqlMatch .= $this->resolveSqlMatch(
                'categories.name',
                $filter['mode'] ?? null
            );

            $params['query'] = $this->resolveSqlQueryParam($filter['q'], $filter['mode'] ?? null);
        }

        $sql = "
        SELECT categories.id AS _id, categories.* " . ($sqlMatch ? ', (' . $sqlMatch . ') AS relevant ' : '') . "
        FROM `categories`
        WHERE 1 {$sqlWhere}
            " . ($sqlMatch ? " AND {$sqlMatch} " : null) . "
        ";

        return [$sql, $params];
    }

    public function create(array $data): int
    {
        $sql = "
        INSERT INTO `categories` (
            `name`
        )
        VALUES  (
            :name
        )";

        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery([
            'name' => $data['name'] ?? null,
        ]);

        $id = (int)$conn->lastInsertId();

        return $id;
    }

    public function update(int $id, array $data): int
    {
        $sql = "UPDATE `categories` SET
                `name` = :name
                WHERE id = :id";
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery([
            'id' => $id,
            'name' => $data['name'] ?? null,
        ]);

        return $id;
    }

    public function get(int $id): ?array
    {
        $sql = "SELECT * FROM `categories` WHERE id = :id";
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);

        $result = $stmt->executeQuery(['id' => $id])->fetchAssociative();

        return $result ?: null;
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM `categories` WHERE id = :id";
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery(['id' => $id]);
    }

}

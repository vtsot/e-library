<?php
declare(strict_types=1);

namespace App\Service\Manager;

use DateTime;
use DateTimeInterface;

class ReadingManager extends AbstractManager
{

    public function query(array $filter = []): array
    {
        $params    = [];
        $sqlWhere  = '';
        $sqlMatch  = '';
        $sqlMatch2 = '';
        $sqlMatch3 = '';

        if (!empty($filter['q'])) {
            $sqlWhere .= $this->resolveSqlWhere(
                [
                    'users.username',
                    'users.email',
                    'users.first_name',
                    'users.last_name',
                    'authors.first_name',
                    'authors.last_name',
                    'book.title',
                    'book.description'
                ],
                $filter['mode'] ?? null
            );

            $sqlMatch .= $this->resolveSqlMatch(
                'users.username, users.email, users.first_name, users.last_name',
                $filter['mode'] ?? null
            );

            $sqlMatch2 .= $this->resolveSqlMatch(
                'authors.first_name, authors.last_name',
                $filter['mode'] ?? null
            );

            $sqlMatch3 .= $this->resolveSqlMatch(
                'book.title, book.description',
                $filter['mode'] ?? null
            );

            $whereMatch   = [];
            $whereMatch[] = $sqlMatch;
            $whereMatch[] = $sqlMatch2;
            $whereMatch[] = $sqlMatch3;
            $whereMatch   = array_filter($whereMatch);
            if ($whereMatch) {
                $sqlWhere .= ' AND ( ' . implode(' OR ', $whereMatch) . ' ) ';
            }

            $params['query'] = $this->resolveSqlQueryParam($filter['q'], $filter['mode'] ?? null);
        }

        if (!empty($filter['type'])) {
            $sqlWhere               .= " AND (reading.reading_type = :reading_type) ";
            $params['reading_type'] = $filter['type'];
        }

        if (!empty($filter['user_id'])) {
            $sqlWhere          .= " AND (reading.user_id = :user_id) ";
            $params['user_id'] = $filter['user_id'];
        }

        if (!empty($filter['isProlong'])) {
            $sqlWhere .= " AND (reading.prolong_at IS NOT NULL) ";
        }


        $sql    = "
        SELECT reading.id AS _id, reading.*, IF(reading.end_at < NOW(), 1, 0) AS is_expire
        " . ($sqlMatch ? ', (' . $sqlMatch . ') AS relevant ' : '') . "
        " . ($sqlMatch2 ? ', (' . $sqlMatch2 . ') AS relevant2 ' : '') . "
        " . ($sqlMatch3 ? ', (' . $sqlMatch3 . ') AS relevant3 ' : '') . "
        FROM `reading`
        LEFT JOIN `users` ON users.id = reading.user_id
        LEFT JOIN `authors_books` AS ab ON ab.book_id = reading.book_id
        LEFT JOIN `authors` AS authors ON authors.id = ab.author_id
        LEFT JOIN `books` AS book ON book.id = reading.book_id
        WHERE 1 {$sqlWhere}
        GROUP BY reading.id";

        return [$sql, $params];
    }

    public function create(array $data): int
    {
        /** @var DateTimeInterface $startAt */
        $startAt = $data['start_at'] ?? null;

        /** @var DateTimeInterface $endAt */
        $endAt = $data['end_at'] ?? null;

        $sql = "
        INSERT INTO `reading` (
            `book_id`, `user_id`, `quantity`, `reading_type`, `start_at`, `end_at`
        )
        VALUES  (
            :book_id, :user_id, :quantity, :reading_type, :start_at, :end_at
        )";

        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery([
            'book_id'      => $data['book_id'] ?? null,
            'user_id'      => $data['user_id'] ?? null,
            'quantity'     => $data['quantity'] ?? null,
            'reading_type' => $data['reading_type'] ?? null,
            'start_at'     => $startAt ? $startAt->format('Y-m-d') : null,
            'end_at'       => $endAt ? $endAt->format('Y-m-d') : null,
        ]);

        $id = (int)$conn->lastInsertId();

        return $id;
    }

    public function update(int $id, array $data): int
    {
        /** @var DateTimeInterface $startAt */
        $startAt = $data['start_at'] ?? null;

        /** @var DateTimeInterface $endAt */
        $endAt = $data['end_at'] ?? null;

        $sql  = "UPDATE `reading` SET
                `book_id` = :book_id,
                `user_id` = :user_id,
                `quantity` = :quantity,
                `reading_type` = :reading_type,
                `start_at` = :start_at,
                `end_at` = :end_at
                WHERE id = :id";
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery([
            'id'           => $id,
            'book_id'      => $data['book_id'] ?? null,
            'user_id'      => $data['user_id'] ?? null,
            'quantity'     => $data['quantity'] ?? null,
            'reading_type' => $data['reading_type'] ?? null,
            'start_at'     => $startAt ? $startAt->format('Y-m-d') : null,
            'end_at'       => $endAt ? $endAt->format('Y-m-d') : null,
        ]);

        return $id;
    }

    public function get(int $id): ?array
    {
        $sql  = "SELECT * FROM `reading` WHERE id = :id";
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);

        return $stmt->executeQuery(['id' => $id])->fetchAssociative();
    }

    public function delete(int $id): void
    {
        $sql  = "DELETE FROM `reading` WHERE id = :id";
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery(['id' => $id]);
    }

    public function countBooksByUser(int $userId): int
    {
        $sql  = "SELECT COUNT(id) FROM `reading` WHERE user_id = :user_id";
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);

        return (int)$stmt->executeQuery(['user_id' => $userId])->fetchOne();
    }

    public function countBooksByBook(int $bookId): int
    {
        $sql  = "SELECT COUNT(id) FROM `reading` WHERE book_id = :book_id";
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);

        return (int)$stmt->executeQuery(['book_id' => $bookId])->fetchOne();
    }

    public function prolong(int $id, array $data): int
    {
        /** @var DateTimeInterface $prolongAt */
        $prolongAt = $data['prolong_at'] ?? null;

        $sql  = "UPDATE `reading` SET
                `prolong_at` = :prolong_at
                WHERE id = :id";
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery([
            'id'         => $id,
            'prolong_at' => $prolongAt ? $prolongAt->format('Y-m-d') : null,
        ]);

        return $id;
    }

    public function prolongCancel(int $id): int
    {
        $this->prolong($id, ['prolong_at' => null]);

        return $id;
    }

    public function prolongAccept(int $id): int
    {
        $data = $this->get($id);

        /** @var DateTimeInterface $prolongAt */
        $prolongAt = $data['prolong_at'] ? DateTime::createFromFormat('Y-m-d', $data['prolong_at']) : null;

        $sql  = "UPDATE `reading` SET
                `prolong_at` = null,
                `end_at` = :prolong_at
                WHERE id = :id";
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery([
            'id'         => $id,
            'prolong_at' => $prolongAt ? $prolongAt->format('Y-m-d') : null,
        ]);

        return $id;
    }
}

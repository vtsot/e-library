<?php
declare(strict_types=1);

namespace App\Service\Manager;

use App\Entity\Order;
use DateTime;
use DateTimeInterface;

class OrderManager extends AbstractManager
{


    protected ReadingManager $readingManager;

    public function __construct(
        ReadingManager $readingManager
    ) {
        $this->readingManager = $readingManager;
    }

    public function query(array $filter = []): array
    {
        $params    = [];
        $sqlWhere  = '';
        $sqlMatch  = '';
        $sqlMatch2 = '';

        if (!empty($filter['q'])) {
            $sqlWhere .= $this->resolveSqlWhere(
                ['books.title', 'books.description', 'authors.first_name', 'authors.last_name'],
                $filter['mode'] ?? null
            );

            $sqlMatch .= $this->resolveSqlMatch(
                'books.title, books.description',
                $filter['mode'] ?? null
            );

            $sqlMatch2 .= $this->resolveSqlMatch(
                'authors.first_name, authors.last_name',
                $filter['mode'] ?? null
            );

            $whereMatch = [];
            $whereMatch[] = $sqlMatch;
            $whereMatch[] = $sqlMatch2;
            $whereMatch = array_filter($whereMatch);
            if ($whereMatch) {
                $sqlWhere .= ' AND ( ' . implode(' OR ', $whereMatch). ' ) ';
            }

            $params['query'] = $this->resolveSqlQueryParam($filter['q'], $filter['mode'] ?? null);
        }

        if (!empty($filter['status'])) {
            $sqlWhere         .= " AND (orders.status = :status) ";
            $params['status'] = $filter['status'];
        }

        $sql = "
        SELECT orders.id AS _id, orders.*
        , books.title, books.description
        , authors.first_name, authors.last_name
        " . ($sqlMatch ? ', (' . $sqlMatch . ') AS relevant ' : '') . "
        " . ($sqlMatch2 ? ', (' . $sqlMatch2 . ') AS relevant2 ' : '') . "
        FROM `orders`
        LEFT JOIN `books` AS books ON books.id = orders.book_id
        LEFT JOIN `authors_books` AS ab ON ab.book_id = books.id
        LEFT JOIN `authors` AS authors ON authors.id = ab.author_id
        WHERE 1 {$sqlWhere}
        GROUP BY orders.id
        ORDER BY orders.id DESC, orders.created_at DESC";
dump($sql);
        return [$sql, $params];
//        $params = [];
//
//        if (!empty($filter['q'])) {
//            $sql             .= " AND (
//                book.title          LIKE :query OR
//                book.description    LIKE :query OR
//                author.first_name   LIKE :query OR
//                author.last_name    LIKE :query
//            )";
//            $params['query'] = '%' . $filter['q'] . '%';
//        }
//
//        if (!empty($filter['status'])) {
//            $sql              .= " AND (orders.status = :status) ";
//            $params['status'] = $filter['status'];
//        }
//
//
//        $sql .= " GROUP BY orders.id";
//        $sql .= " ORDER BY orders.id ASC , orders.created_at ASC ";
//
//        return [$sql, $params];
    }

    public function create(array $data): int
    {
        /** @var DateTimeInterface $startAt */
        $startAt = $data['start_at'] ?? null;
        /** @var DateTimeInterface $endAt */
        $endAt = $data['end_at'] ?? null;

        $sql = "
        INSERT INTO `orders` (
            `book_id`, `user_id`, `quantity`, `reading_type`, `start_at`, `end_at`, `status`
        )
        VALUES  (
            :book_id, :user_id, :quantity, :reading_type, :start_at, :end_at, :status
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
            'status'       => Order::STATUS_OPEN,

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

        $sql  = "UPDATE `orders` SET
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
            'book_id'      => $data['book_id'],
            'user_id'      => $data['user_id'],
            'quantity'     => $data['quantity'],
            'reading_type' => $data['reading_type'],
            'start_at'     => $startAt ? $startAt->format('Y-m-d') : null,
            'end_at'       => $endAt ? $endAt->format('Y-m-d') : null
        ]);

        return $id;
    }

    public function get(int $id): ?array
    {
        $sql  = "SELECT * FROM `orders` WHERE id = :id";
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);

        return $stmt->executeQuery(['id' => $id])->fetchAssociative();
    }

    public function delete(int $id): void
    {
        $sql  = "DELETE FROM `orders` WHERE id = :id";
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery(['id' => $id]);
    }

    public function status($id, array $data): int
    {
        $sql  = "UPDATE `orders` SET
                `status` = :status
                WHERE id = :id";
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery([
            'id'     => $id,
            'status' => $data['status'] ?? null,
        ]);

        return $id;
    }

    public function cancel(int $id): int
    {
        $this->status($id, ['status' => Order::STATUS_CANCELED]);
        return $id;
    }

    public function done(int $id): int
    {
        $this->status($id, ['status' => Order::STATUS_DONE]);
        $order   = $this->get($id);
        $startAt = $order['start_at'] ? DateTime::createFromFormat('Y-m-d', $order['start_at']) : null;
        $endAt   = $order['end_at'] ? DateTime::createFromFormat('Y-m-d', $order['end_at']) : null;
        $data    = [
            'book_id'      => $order['book_id'],
            'quantity'     => $order['quantity'],
            'user_id'      => $order['user_id'],
            'reading_type' => $order['reading_type'],
            'start_at'     => $startAt,
            'end_at'       => $endAt,
        ];
        $this->readingManager->create($data);

        return $id;
    }

    public function open(int $id): int
    {
        $this->status($id, ['status' => Order::STATUS_OPEN]);

        return $id;
    }

}

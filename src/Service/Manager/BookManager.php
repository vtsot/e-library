<?php
declare(strict_types=1);

namespace App\Service\Manager;

use App\Entity\Order;
use DateTimeInterface;

class BookManager extends AbstractManager
{

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

        if (!empty($filter['category'])) {
            $sqlWhere             .= " AND (
                bc.category_id = :category_id
            )";
            $params['category_id'] = (int)$filter['category'];
        }

        $sql = "
        SELECT books.id AS _id, books.*
        " . ($sqlMatch ? ', (' . $sqlMatch . ') AS relevant ' : '') . "
        " . ($sqlMatch2 ? ', (' . $sqlMatch2 . ') AS relevant2 ' : '') . "
        FROM `books`
        LEFT JOIN `authors_books` AS ab ON ab.book_id = books.id
        LEFT JOIN `books_categories` AS bc ON bc.book_id = books.id
        LEFT JOIN `authors` AS authors ON authors.id = ab.author_id
        WHERE 1 {$sqlWhere}
        GROUP BY books.id";

        return [$sql, $params];
    }

    public function create(array $data): int
    {
        $sql = "
        INSERT INTO `books` (
            `title`, `description`, `quantity`
        )
        VALUES  (
            :title, :description, :quantity
        )";

        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery([
            'title'       => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'quantity'    => (int)$data['quantity'] ?: 0,
        ]);

        $id = (int)$conn->lastInsertId();
        $this->updateAuthors($id, $data['authors'] ?? []);
        $this->updateCategories($id, $data['categories'] ?? []);

        return $id;
    }

    public function update(int $id, array $data): int
    {
        $sql  = "UPDATE `books` SET
                `title` = :title,
                `description` = :description,
                `quantity`  = :quantity
                WHERE id = :id";
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery([
            'id'          => $id,
            'title'       => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'quantity'    => (int)$data['quantity'] ?: 0,
        ]);

        $this->updateAuthors($id, $data['authors'] ?? []);
        $this->updateCategories($id, $data['categories'] ?? []);

        return $id;
    }

    protected function updateAuthors(int $bookId, array $authorIds): void
    {
        $conn = $this->getConnection();

        // delete authors before add new
        $sql  = "DELETE FROM `authors_books` WHERE book_id = :book_id";
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery(['book_id' => $bookId]);

        // add new authors
        foreach ($authorIds as $authorId) {
            $sql  = "INSERT INTO `authors_books` (`author_id`, `book_id`) VALUES  (:author_id, :book_id)";
            $stmt = $conn->prepare($sql);
            $stmt->executeQuery(['author_id' => $authorId, 'book_id' => $bookId]);
        }
    }

    public function getAuthors(int $bookId): array
    {
        $conn = $this->getConnection();
        $sql  = "SELECT authors.id AS _id, authors.* FROM `authors` WHERE id IN(SELECT author_id FROM authors_books WHERE book_id = :book_id)";
        $stmt = $conn->prepare($sql);

        return $stmt->executeQuery(['book_id' => $bookId])->fetchAllAssociativeIndexed();
    }

    protected function updateCategories(int $bookId, array $categoryIds): void
    {
        $conn = $this->getConnection();

        // delete categories before add new
        $sql  = "DELETE FROM `books_categories` WHERE book_id = :book_id";
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery(['book_id' => $bookId]);

        // add new categories
        foreach ($categoryIds as $categoryId) {
            $sql  = "INSERT INTO `books_categories` (`book_id`, `category_id`) VALUES  (:book_id, :category_id)";
            $stmt = $conn->prepare($sql);
            $stmt->executeQuery(['book_id' => $bookId, 'category_id' => $categoryId]);
        }
    }

    public function getCategories(int $bookId): array
    {
        $conn = $this->getConnection();
        $sql  = "SELECT categories.id AS _id, categories.* FROM `categories` WHERE id IN(SELECT category_id FROM books_categories WHERE book_id = :book_id)";
        $stmt = $conn->prepare($sql);

        return $stmt->executeQuery(['book_id' => $bookId])->fetchAllAssociativeIndexed();
    }

    public function get(int $id): ?array
    {
        $sql  = "SELECT * FROM `books` WHERE id = :id";
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);

        $result = $stmt->executeQuery(['id' => $id])->fetchAssociative();
        if ($result) {
            $result['authors']    = $this->getAuthors($id);
            $result['categories'] = $this->getCategories($id);
        }

        return $result ?: null;
    }

    public function delete(int $id): void
    {
        $sql  = "DELETE FROM `books` WHERE id = :id";
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery(['id' => $id]);
    }

    public function order(array $data): int
    {
        /** @var DateTimeInterface $startAt */
        $startAt = $data['start_at'] ?? null;

        /** @var DateTimeInterface $endAt */
        $endAt = $data['end_at'] ?? null;

        $sql = "
        INSERT INTO `orders` (
            `book_id`, `user_id`, `reading_type`, `quantity`, `start_at`, `end_at`, `status`, `created_at`
        )
        VALUES  (
            :book_id, :user_id, :reading_type, :quantity, :start_at, :end_at, :status, :created_at
        )";

        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery([
            'book_id'      => $data['book_id'] ?? null,
            'user_id'      => $data['user_id'] ?? null,
            'reading_type' => $data['reading_type'] ?? null,
            'quantity'     => $data['quantity'] ?? null,
            'start_at'     => $startAt ? $startAt->format('Y-m-d') : null,
            'end_at'       => $endAt ? $endAt->format('Y-m-d') : null,
            'status'       => Order::STATUS_OPEN,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $orderId = (int)$conn->lastInsertId();

        return $orderId;
    }
}

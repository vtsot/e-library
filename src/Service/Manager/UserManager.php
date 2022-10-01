<?php
declare(strict_types=1);

namespace App\Service\Manager;

use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use function array_key_exists;

class UserManager extends AbstractManager
{

    protected EncoderFactoryInterface $encoderFactory;

    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    public function query(array $filter = []): array
    {
        $params   = [];
        $sqlWhere = '';
        $sqlMatch = '';

        if (!empty($filter['q'])) {
            $sqlWhere .= $this->resolveSqlWhere(
                ['users.username', 'users.email', 'users.first_name', 'users.last_name'],
                $filter['mode'] ?? null
            );

            $sqlMatch .= $this->resolveSqlMatch(
                'users.username, users.email, users.first_name, users.last_name',
                $filter['mode'] ?? null
            );

            $params['query'] = $this->resolveSqlQueryParam($filter['q'], $filter['mode'] ?? null);
        }

        if (!empty($filter['role'])) {
            $sqlWhere .= " AND (users.roles LIKE :role)";
            $params['role'] = '%' . $filter['role'] . '%';
        }

        if (array_key_exists('active', $filter) &&
            strlen($filter['active']) > 0
        ) {
            $sqlWhere .= " AND users.active = :active ";
            $params['active'] = (int)$filter['active'];
        }

        $sql = "
        SELECT users.id AS _id, users.* " . ($sqlMatch ? ', (' . $sqlMatch . ') AS relevant ' : '') . "
        FROM `users`
        WHERE 1 {$sqlWhere}
            " . ($sqlMatch ? " AND {$sqlMatch} " : null) . "
        ";

        return [$sql, $params];
    }

    public function create(array $data): int
    {
        $salt    = bin2hex(random_bytes(12));
        $encoder = $this->encoderFactory->getEncoder(User::class);

        $sql = "
        INSERT INTO `users` (
            `first_name`, `last_name`,
            `username`, `password`, `email`, `salt`, `roles`, `active`
        )
        VALUES  (
            :first_name, :last_name,
            :username, :password, :email, :salt, :roles, :active
        )";

        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery([
            'first_name' => $data['first_name'] ?? null,
            'last_name'  => $data['last_name'] ?? null,
            'username'   => $data['username'] ?? null,
            'password'   => $encoder->encodePassword($data['password'], $salt),
            'email'      => $data['email'] ?? null,
            'salt'       => $salt,
            'roles'      => json_encode([$data['role'] ?? null]),
            'active'     => 1,
        ]);

        $id = (int)$conn->lastInsertId();

        return $id;
    }

    public function update(int $id, array $data): int
    {
        $sql  = "UPDATE `users` SET
                `first_name` = :first_name,
                `last_name` = :last_name,
                `username` = :username,
                `email` = :email
                WHERE id = :id";
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery([
            'id'         => $id,
            'first_name' => $data['first_name'] ?? null,
            'last_name'  => $data['last_name'] ?? null,
            'username'   => $data['username'] ?? null,
            'email'      => $data['email'] ?? null,
        ]);

        // update password
        if (array_key_exists('password', $data) && $data['password']) {
            $salt    = $data['salt'];
            $encoder = $this->encoderFactory->getEncoder(User::class);
            $sql     = "UPDATE `users` SET `password` = :password WHERE id = :id";
            $conn    = $this->getConnection();
            $stmt    = $conn->prepare($sql);
            $stmt->executeQuery([
                'id'       => $id,
                'password' => $encoder->encodePassword($data['password'], $salt),
            ]);
        }

        // update active
        if (array_key_exists('active', $data)) {
            $sql  = "UPDATE `users` SET `active` = :active WHERE id = :id";
            $conn = $this->getConnection();
            $stmt = $conn->prepare($sql);
            $stmt->executeQuery([
                'id'     => $id,
                'active' => (int)$data['active'],
            ]);
        }

        return $id;
    }

    public function get(int $id): ?array
    {
        $sql  = "SELECT * FROM `users` WHERE id = :id";
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);

        $result = $stmt->executeQuery(['id' => $id])->fetchAssociative();

        return $result ?: null;
    }

    public function delete(int $id): void
    {
        $sql  = "DELETE FROM `users` WHERE id = :id";
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery(['id' => $id]);
    }

}

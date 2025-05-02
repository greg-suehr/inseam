<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\Exception\ORMException;

class SafeEntityManager
{
    public function __construct(private EntityManagerInterface $em) {}

    public function safe(callable $operation, mixed $default = []): mixed
    {
        try {
            return $operation($this->em);
        } catch (DBALException | ORMException $e) {
            return $default;
        }
    }
}

?>

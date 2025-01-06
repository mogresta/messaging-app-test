<?php

namespace App\Repository;

use App\Entity\Message;
use App\Enum\MessageStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 *
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    /** removed the by method as it used raw sql and didn't have pagination
     * alternatively Doctrine Paginator could be returned as a response
     * */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /** @return array{ items: mixed[], totalItems: int<0, max>, page: int, limit: int, totalPages: float } */
    public function getPaginatedMessages(
        ?MessageStatus $status = null,
        int $page = 1,
        int $limit = 10
    ): array {
        $messages = $this->getMessages($status, $page, $limit);

        return [
            'items' => $messages,
            'totalItems' => count($messages),
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil(count($messages) / $limit)
        ];
    }

    /** @return mixed[] */
    private function getMessages(
        ?MessageStatus $status = null,
        int $page = 1,
        int $limit = 10
    ): array {
        $queryBuilder = $this->createQueryBuilder('m')
            ->orderBy('m.createdAt', 'DESC');

        if ($status !== null) {
            $queryBuilder
                ->andWhere('m.status = :status')
                ->setParameter('status', $status);
        }

        $firstResult = ($page - 1) * $limit;

        $queryBuilder
            ->setFirstResult($firstResult)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getArrayResult();
    }
}

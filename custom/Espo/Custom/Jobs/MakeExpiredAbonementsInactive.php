<?php

namespace Espo\Custom\Jobs;

use Espo\Core\Job\JobDataLess;
use Espo\ORM\EntityManager;

class MakeExpiredAbonementsInactive implements JobDataLess
{
    // Pass dependencies through the constructor using DI.
    public function __construct(private EntityManager $entityManager)
    {
    }

    public function run(): void 
    {
        $updateQuery = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in('Abonement')
            ->set(['isActive' => false])
            ->where([
                'endDate' => date('Y-m-d', time() - 21 * 60 * 60)//yesterday(-24h), but in Kiev timezone(+3h) 
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($updateQuery);
        
    }    
}

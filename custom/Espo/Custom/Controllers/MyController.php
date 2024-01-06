<?php

namespace Espo\Custom\Controllers;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\ORM\EntityManager;

class MyController {

    public function __construct(private EntityManager $entityManager) {}

     public function getActionTest(Request $request, Response $response): string 
     {
           
        $abonements = $this->entityManager
            ->getRDBRepository('Abonement')
            ->where(['endDate' => date('Y-m-d', time() - 19 * 60 * 60)])
            ->find();

        foreach( $abonements as $abon ){
            $abon->set(['isActive' => false]);
        }

        return count($abonements);
     }

}
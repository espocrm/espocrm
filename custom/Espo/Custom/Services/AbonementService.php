<?php

namespace Espo\Custom\Services;

use \Datetime;
use Espo\ORM\EntityManager;

class AbonementService {
    const ONETIME_NAME = 'Разове заняття';
    const TRIAL_NAME = 'Пробне заняття';

    public function __construct(private EntityManager $entityManager) {}

    public function getNewAbonsStat($date, $gropusIds)
    {
        $newAbonements = $this->getAbonements($date, $gropusIds);
        
        $newAbonementsCount = count($newAbonements);
        $onetimeCount = count($this->filterByAbonplan($newAbonements, self::ONETIME_NAME));
        $trialCount = count($this->filterByAbonplan($newAbonements, self::TRIAL_NAME));
        $regularCount = $newAbonementsCount - $onetimeCount - $trialCount;

        return [
            'regular' => $regularCount,
            'onetime' => $onetimeCount,
            'trial' => $trialCount
        ];
    }

    private function getAbonements($date, $gropusIds)
    {
        $selectParams = [
            'salesDate' => $date,
            'groups.id' => $gropusIds
        ];

        return $this->entityManager
            ->getRepository('Abonement')
            ->distinct()
            ->join('groups')
            ->where($selectParams)
            ->find();
    }
    
    private function filterByAbonplan($abonements, $abonplanName)
    {
        $filteredAbons = [];
        foreach($abonements as $abon) {
            if ($abon->get('abonplanName') == $abonplanName) {
                array_push($filteredAbons, $abon);
            }
        }
        return $filteredAbons;
    }
}
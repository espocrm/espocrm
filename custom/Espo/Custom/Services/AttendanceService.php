<?php

namespace Espo\Custom\Services;

use \Datetime;
use Espo\ORM\EntityManager;
use Espo\Custom\Services\GroupService;

class AttendanceService {

    public function __construct(private EntityManager $entityManager, private GroupService $groupService) {}

    public function getStatistics($date, $trainerId, $teamsIds)
    {   
        $trainings = $this->getTrainigs($date, $trainerId, $teamsIds);
        return $this->groupService->getAttendance($trainings);        
    }

    private function getTrainigs($date, $trainerId, $teamsIds) {
        $selectParams = [
            'startDateOnly' => $date,
            'assignedUserId' => $trainerId,
            'teams.id' => $teamsIds
        ];

        return $this->entityManager
            ->getRepository('Training')
            ->distinct()
            ->join('teams')
            ->where($selectParams)
            ->find();
    }
}
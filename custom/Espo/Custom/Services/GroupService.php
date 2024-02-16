<?php

namespace Espo\Custom\Services;

use \Datetime;
use Espo\ORM\EntityManager;
use Espo\Custom\Services\AbonementService;

class GroupService {

    public function __construct(private EntityManager $entityManager, private AbonementService $abonementService) {}

    public function getAttendance($trainings)
    {   
        $groupsAttendance = [];
        foreach($trainings as $training) {
            $date = $training->get('startDateOnly');
            $gropusIds = [ $training->get('groupId') ];

            array_push($groupsAttendance, [
                'name' => $training->get('groupName'),
                'attendanceCount' => $this->getMarksCount($training->get('id')),
                'abonements' => $this->abonementService->getNewAbonsStat($date, $gropusIds)
            ]);
        }

        return $groupsAttendance;
    }

    private function getMarksCount($trainingId)
    {
        $selectParams = [
            'trainingId' => $trainingId
        ];

        $marks = $this->entityManager
            ->getRepository('Mark')
            ->where($selectParams)
            ->find();

        return count($marks);
    }
}
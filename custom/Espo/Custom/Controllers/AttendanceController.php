<?php

namespace Espo\Custom\Controllers;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Custom\Services\AttendanceService;

class AttendanceController {
   
    public function __construct(private AttendanceService $attendanceService) {}

    public function getActionStatistics(Request $request, Response $response): string
    {
        $date = $request->getRouteParam('date');
        $trainerId = $request->getRouteParam('trainerId');
        $teamsString = explode('=', $request->getRouteParam('teamsIds'))[1];
        $teamsIds = explode('&', $teamsString);

        return json_encode([
            'date' => $date,
            'groups' => $this->attendanceService->getStatistics($date, $trainerId, $teamsIds)
        ]);
    }
}
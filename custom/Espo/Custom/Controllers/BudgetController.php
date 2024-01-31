<?php

namespace Espo\Custom\Controllers;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Custom\Services\BudgetService;

class BudgetController {
    private $totalProfit = 0;
    private $totalExpenses = 0;

    public function __construct(private BudgetService $budgetService) {}

    public function getActionIncome(Request $request, Response $response): string 
    {
        $dateFrom = $request->getRouteParam('dateFrom');
        $dateTo = $request->getRouteParam('dateTo');
        $teamsString = explode('=', $request->getRouteParam('teams'))[1];
        $teamsIds = explode('&', $teamsString);
        

        return json_encode( 
            $this->budgetService->getBudget($dateFrom, $dateTo, $teamsIds)
        );
    }

    public function getActionDetail(Request $request, Response $response): string
    {
        $date = $request->getRouteParam('dateFrom');
        $teamsString = explode('=', $request->getRouteParam('teams'))[1];
        $teamsIds = explode('&', $teamsString);

        return json_encode(
            $this->budgetService->getDetails($date, $teamsIds)
        );
    }
}
<?php

namespace Espo\Custom\Controllers;

use \Datetime;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\ORM\EntityManager;

class BudgetController {

    public function __construct(private EntityManager $entityManager) {}

    public function getActionIncome(Request $request, Response $response): string 
    {
        $dateFrom = $request->getRouteParam('dateFrom');
        $dateTo = $request->getRouteParam('dateTo');
        
        return json_encode(
            array(
                'total' => array(
                    'expenses' => 0,
                    'profit' => 0,
                    'income' => 0
                ),
                'list' => $this->getBudgetList($dateFrom, $dateTo)
            )
        );
    }

    private function getBudgetList($dateFrom, $dateTo)
    {
        $date1 = new DateTime($dateFrom);
        $date2 = new DateTime($dateTo);   

        $interval = $date1->diff($date2);
        $daysCount = $interval->days;

        $abons = $this->getProfitByDate('Abonement', $dateFrom, $dateTo);
        $indivs = $this->getProfitByDate('Indiv', $dateFrom, $dateTo);
        $rents = $this->getProfitByDate('Rent', $dateFrom, $dateTo);
        $rentplans = $this->getProfitByDate('RentPlan', $dateFrom, $dateTo);
        $goods = $this->getProfitByDate('Goods', $dateFrom, $dateTo);
        $expenses = $this->getExpensesByDate($dateFrom, $dateTo);

        $date = $dateFrom;
        $expensesList = array();
        for ($i = 0;  $i <= $daysCount; $i++) {
            $profitTotalSum = $this->getAllProfitTotalSum($date, $abons, $indivs, $rents, $rentplans, $goods);
            $expensesTotalSum = $this->getTotalSumByDate($date, $expenses, 'cost', 'date');

            array_push($expensesList,
                array(
                    'date' => $date,
                    'profit' => $profitTotalSum,
                    'expenses' => $expensesTotalSum,
                    'income' => $profitTotalSum - $expensesTotalSum
                )
            );

            $date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
        }
        return $expensesList;
    }

    public function getProfitByDate($entityName, $dateFrom, $dateTo)
    {
        $dateTimeParams = [
            'createdAt>=' => "${dateFrom} 00:00:00",
            'createdAt<=' => "${dateTo} 23:59:59",
        ];

        return $this->entityManager
            ->getRDBRepository($entityName)
            ->where($dateTimeParams)
            ->find();
    }

    private function getExpensesByDate($dateFrom, $dateTo)
    {
        $dateParams = [
            'date>=' => $dateFrom,
            'date<=' => $dateTo,
        ];

        return $this->entityManager
            ->getRDBRepository('Expenses')
            ->where($dateParams)
            ->find();
    }

    private function getAllProfitTotalSum($date, $abons, $indivs, $rents, $rentplans, $goods)
    {
        $allProfitTotalSum = 0;
        
        $allProfitTotalSum += $this->getTotalSumByDate($date, $abons, 'price', 'createdAt');
        $allProfitTotalSum += $this->getTotalSumByDate($date, $indivs, 'defaultPrice', 'createdAt');
        $allProfitTotalSum += $this->getTotalSumByDate($date, $rents, 'customPrice', 'createdAt');
        $allProfitTotalSum += $this->getTotalSumByDate($date, $rentplans, 'price', 'createdAt');
        $allProfitTotalSum += $this->getTotalSumByDate($date, $goods, 'price', 'createdAt');
        
        return $allProfitTotalSum;
    }

    private function getTotalSumByDate($date, $entities, $priceFieldName, $dateFieldName): float
    {
        $sum = 0;
        foreach($entities as $entity) {
            $datePart = explode(" ", $entity->get($dateFieldName))[0];
            if ($datePart == $date) {
                $sum +=  $entity->get($priceFieldName);
            }
        }
        return $sum;
    }
}
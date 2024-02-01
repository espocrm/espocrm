<?php

namespace Espo\Custom\Services;

use \Datetime;
use Espo\ORM\EntityManager;

class BudgetService {
    private $totalProfit = 0;
    private $totalExpenses = 0;

    public function __construct(private EntityManager $entityManager) {}

    public function getBudget($dateFrom, $dateTo, $teamsIds)
    {   
        return array(
            'list' => $this->getBudgetList($dateFrom, $dateTo, $teamsIds),
            'total' => array(
                'profit' => $this->totalProfit,
                'expenses' => $this->totalExpenses,
                'income' => $this->totalProfit - $this->totalExpenses
                )
            );
    }

    public function getDetails($date, $teamsIds)
    {
        $abons = $this->getProfitByDate('Abonement', $date, $date, $teamsIds);
        $indivs = $this->getProfitByDate('Indiv', $date, $date, $teamsIds);
        $rents = $this->getProfitByDate('Rent', $date, $date, $teamsIds);
        $rentplans = $this->getProfitByDate('RentPlan', $date, $date, $teamsIds);
        $goods = $this->getProfitByDate('Goods', $date, $date, $teamsIds);

        $profitDetailList = [
            [
                'name' => 'Абонементи', 
                'value' => $this->getTotalSumByDate($date, $abons, 'price', 'salesDate'),
                'count' => count($abons)
            ],
            [
                'name' => 'Індиви', 
                'value' => $this->getTotalSumByDate($date, $indivs, 'price', 'salesDate'),
                'count' => count($indivs)
            ],
            [
                'name' => 'Оренда разова', 
                'value' => $this->getTotalSumByDate($date, $rents, 'price', 'salesDate'),
                'count' => count($rents)
            ],
            [
                'name' => 'Оренда планова', 
                'value' => $this->getTotalSumByDate($date, $rentplans, 'price', 'salesDate'),
                'count' => count($rentplans)
            ],
            [
                'name' => 'Товари', 
                'value' => $this->getTotalSumByDate($date, $goods, 'price', 'salesDate'),
                'count' => count($goods)
            ],
        ];

        $expensesList = $this->getExpensesByDate($date, $date, $teamsIds);

        return array(
            'profitDetails' => $profitDetailList,
            'expensesDetails' => $this->getExpensesDetailList($expensesList)
        );
    }

    private function getExpensesDetailList($expensesList)
    {
        $expensesDetailList = [];
        foreach($expensesList as $expense) {
            array_push(
                $expensesDetailList, 
                [
                    'name' => $expense->get('name'),
                    'value' => $expense->get('price')
                ]);
        }
        return $expensesDetailList;
    }

    private function getBudgetList($dateFrom, $dateTo, $teamsIds)
    {
        $date1 = new DateTime($dateFrom);
        $date2 = new DateTime($dateTo);   

        $interval = $date1->diff($date2);
        $daysCount = $interval->days;

        $abons = $this->getProfitByDate('Abonement', $dateFrom, $dateTo, $teamsIds);
        $indivs = $this->getProfitByDate('Indiv', $dateFrom, $dateTo, $teamsIds);
        $rents = $this->getProfitByDate('Rent', $dateFrom, $dateTo, $teamsIds);
        $rentplans = $this->getProfitByDate('RentPlan', $dateFrom, $dateTo, $teamsIds);
        $goods = $this->getProfitByDate('Goods', $dateFrom, $dateTo, $teamsIds);
        $expenses = $this->getExpensesByDate($dateFrom, $dateTo, $teamsIds);

        $date = $dateFrom;
        $expensesList = array();
        for ($i = 0;  $i <= $daysCount; $i++) {
            $profitTotalSum = $this->getAllProfitTotalSum($date, $abons, $indivs, $rents, $rentplans, $goods);
            $expensesTotalSum = $this->getTotalSumByDate($date, $expenses, 'price', 'date');

            array_push($expensesList,
                array(
                    'date' => $date,
                    'profit' => $profitTotalSum,
                    'expenses' => $expensesTotalSum,
                    'income' => $profitTotalSum - $expensesTotalSum
                )
            );
            $this->totalProfit += $profitTotalSum;
            $this->totalExpenses += $expensesTotalSum;

            $date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
        }
        return $expensesList;
    }

    private function getProfitByDate($entityName, $dateFrom, $dateTo, $teamsIds)
    {
        $selectParams = [
            'salesDate>=' => $dateFrom,
            'salesDate<=' => $dateTo,
            'teams.id' => $teamsIds
        ];

        return $this->entityManager
            ->getRepository($entityName)
            ->distinct()
            ->join('teams')
            ->where($selectParams)
            ->find();
    }

    private function getExpensesByDate($dateFrom, $dateTo, $teamsIds)
    {
        $selectParams = [
            'salesDate>=' => $dateFrom,
            'salesDate<=' => $dateTo,
            'teams.id' => $teamsIds
        ];

        return $this->entityManager
            ->getRepository('Expenses')
            ->distinct()
            ->join('teams')
            ->where($selectParams)
            ->find();
    }

    private function getAllProfitTotalSum($date, $abons, $indivs, $rents, $rentplans, $goods)
    {
        $allProfitTotalSum = 0;
        
        $allProfitTotalSum += $this->getTotalSumByDate($date, $abons, 'price', 'salesDate');
        $allProfitTotalSum += $this->getTotalSumByDate($date, $indivs, 'price', 'salesDate');
        $allProfitTotalSum += $this->getTotalSumByDate($date, $rents, 'price', 'salesDate');
        $allProfitTotalSum += $this->getTotalSumByDate($date, $rentplans, 'price', 'salesDate');
        $allProfitTotalSum += $this->getTotalSumByDate($date, $goods, 'price', 'salesDate');
        
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
<?php

namespace Espo\Modules\Crm\Controllers;

class Opportunity extends \Espo\Core\Controllers\Record
{
	public function actionReportByLeadSource($params, $data, $request)
	{
		$dateFrom = $request->get('dateFrom');
		$dateTo = $request->get('dateTo');
		
		return array(
			'Call' => 5000,
			'Partner' => 3400,
			'Public Relations' => 6700,
			'Web Site' => 5000,
			'Campaign' => 3200,
			'Other' => 5000,
		);
	}
	
	public function actionReportByStage($params, $data, $request)
	{
		$dateFrom = $request->get('dateFrom');
		$dateTo = $request->get('dateTo');
		
		return array(
			'Prospecting' => 5000,
			'Qualification' => 3400,
			'Needs Analysis' => 6700,
			'Closed Won' => 5000,
		);
	}
	
	public function actionReportSalesByMonth($params, $data, $request)
	{
		$dateFrom = $request->get('dateFrom');
		$dateTo = $request->get('dateTo');
		
		return array(
			'month' => array('2013-01','2013-02','2013-03','2013-04','2013-05','2013-06'),
			'values' => array(1200, 3000, 4000, 2500, 3000)
		);
	}
	
	public function actionReportSalesPipeline($params, $data, $request)
	{
		$dateFrom = $request->get('dateFrom');
		$dateTo = $request->get('dateTo');
		
		return array(
			'Prospecting' => 6000,
			'Qualification' => 3400,
			'Needs Analysis' => 2700,
			'Closed Won' => 2000,
		);
	}
}


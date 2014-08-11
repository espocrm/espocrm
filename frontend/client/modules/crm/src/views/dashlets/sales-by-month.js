/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/ 

Espo.define('Crm:Views.Dashlets.SalesByMonth', 'Crm:Views.Dashlets.Abstract.Chart', function (Dep) {

	return Dep.extend({

		name: 'SalesByMonth',
		
		optionsFields: _.extend(_.clone(Dep.prototype.optionsFields), {
			'dateFrom': {
				type: 'date',
				required: true,				
			},
			'dateTo': {
				type: 'date',
				required: true,					
			}
		}),
		
		defaultOptions: {
			dateFrom: function () {
				return moment().format('YYYY') + '-01-01'
			},
			dateTo: function () {
				return moment().format('YYYY') + '-12-31'
			},
		},
		
		url: function () {
			return 'Opportunity/action/reportSalesByMonth?dateFrom=' + this.getOption('dateFrom') + '&dateTo=' + this.getOption('dateTo');
		},	
		
		prepareData: function (response) {				
			var months = this.months = Object.keys(response).sort();			
					
			var values = [];
			
			for (var month in response) {
				values.push(response[month]);
			}
		
			this.chartData = [];
			
			var mid = 0;
			if (values.length) {
				mid = values.reduce(function(a, b) {return a + b}) / values.length;
			}
			
			var data = [];
							
			values.forEach(function (value, i) {
				data.push({
					data: [[i, value]],
					color: (value >= mid) ? this.colorGood : this.colorBad
				});
			}, this);
			
			return data;		
		},	
					
		setup: function () {
			this.currency = this.getConfig().get('defaultCurrency');
			this.currencySymbol = '';			
		
			this.colorGood = '#5ABD37';
			this.colorBad = '#5ABD37';			
		},			
		
		drow: function () {
			var self = this;
			this.flotr.draw(this.$container.get(0), this.chartData, {
				shadowSize: false,
				bars: {
					show: true,
					horizontal: false,
					shadowSize: 0,
					lineWidth: 1,
					fillOpacity: 1,
					barWidth: 0.5,
				},
				grid: {
					horizontalLines: true,
					verticalLines: false,
					outline: 'sw'
				},
				yaxis: {
					min: 0,
					showLabels: true,
					tickFormatter: function (value) {
						if (value == 0) {
							return '';
						}
						return self.formatNumber(value) + ' ' + self.currency;
					},					
				},
				xaxis: {
					min: 0,
					tickFormatter: function (value) {
						if (value % 1 == 0) {
							var i = parseInt(value);
							if (i in self.months) {
								return self.months[i];
							}
						}
						return '';						
					},
				},
				mouse: {
					track: true,
					relative: true,
					trackFormatter: function (obj) {
						return self.formatNumber(obj.y) + ' ' + self.currency;
					},
				},
			});
		},
	});
});



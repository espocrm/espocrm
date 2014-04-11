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

Espo.define('Crm:Views.Dashlets.Abstract.Chart', 'Views.Dashlets.Abstract.Base', function (Dep) {

	return Dep.extend({
	
		_template: '<div class="chart-container"></div><div class="legend-container"></div>',
		
		decimalMark: '.',
		
		thousandSeparator: ',',
		
		colors: ['#6FA8D6', '#4E6CAD', '#EDC555', '#ED8F42', '#5ABD37', '#DE6666', '#9666DE', '#ABC478'],
		
		init: function () {
			Dep.prototype.init.call(this);
			
			if (!('Flotr' in window)) {
				this.addReadyCondition(function () {
					return ('Flotr' in window);
				});
				Espo.loadLib('client/modules/crm/lib/flotr2.min.js', function () {
					this.tryReady();
				}.bind(this));
			}
			
			if (this.getPreferences().has('decimalMark')) {
				this.decimalMark = this.getPreferences().get('decimalMark') 
			} else {
				if (this.getSettings().has('decimalMark')) {
					this.decimalMark = this.getSettings().get('decimalMark') 
				}
			}							
			if (this.getPreferences().has('thousandSeparator')) {
				this.thousandSeparator = this.getPreferences().get('thousandSeparator') 
			} else {
				if (this.getSettings().has('thousandSeparator')) {
					this.thousandSeparator = this.getSettings().get('thousandSeparator') 
				}
			}
			
			this.once('after:render', function () {
				$(window).on('resize.chart' + this.name, function () {
					this.drow();
				}.bind(this));
			}, this);
			
			this.once('remove', function () {
				$(window).off('resize.chart' + this.name)
			}, this);			
		},
		
		formatNumber: function (value) {
			if (value !== null) {
				var parts = value.toString().split(".");
				parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandSeparator);
				if (parts[1] == 0) {
					parts.splice(1, 1); 
				}
				return parts.join(this.decimalMark);
			}
			return '';
		},
		
		afterRender: function () {		
			this.fetch(function (data) {
				this.chartData = this.prepareData(data);										
				
				var $container = this.$container = this.$el.find('.chart-container');
			
				var height = '245px';
				if (this.chartData.length > 5) {
					height = '215px';
				}
				$container.css('height', height);
			
				setTimeout(function () {			
					this.drow();
				}.bind(this), 1);
			});
		},
		
		url: function () {},
		
		prepareData: function (response) {
			return response;
		},
		
		fetch: function (callback) {
			$.ajax({
				type: 'get',
				url: this.url(),
				success: function (response) {
					callback.call(this, response);
				}.bind(this)
			});	
		},

	});
});


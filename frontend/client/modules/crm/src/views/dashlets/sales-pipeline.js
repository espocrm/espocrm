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

Espo.define('Crm:Views.Dashlets.SalesPipeline', 'Crm:Views.Dashlets.Abstract.Chart', function (Dep) {

    return Dep.extend({

        name: 'SalesPipeline',
        
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
            return 'Opportunity/action/reportSalesPipeline?dateFrom=' + this.getOption('dateFrom') + '&dateTo=' + this.getOption('dateTo');
        },
                        
        prepareData: function (response) {
            var d = [];            
            for (var label in response) {
                var value = response[label];
                d.push({
                    stage: this.getLanguage().translateOption(label, 'stage', 'Opportunity'),
                    value: value
                });
            }
            
            var data = [];
            for (var i = 0; i < d.length; i++) {
                var item = d[i];
                var value = item.value;
                var nextValue = ((i + 1) < d.length) ? d[i + 1].value : value;
                data.push({
                    data: [[i, value], [i + 1, nextValue]],
                    label: item.stage
                });
            }
            
            this.maxY = 1000;
            if (d.length) {    
                for (var i = 0; i < d.length; i++) {
                    var y = d[i].value + (d[i].value / 20);
                    if (y > this.maxY) {
                        this.maxY = y;
                    }
                }            
                
            }
            
            return data;    
        },
                        
        setup: function () {
            this.currency = this.getConfig().get('defaultCurrency');
            this.currencySymbol = '';
            
            var data = [
                {
                    value: 12000,
                    stage: 'Prospecting'
                },
                {
                    value: 5050,
                    stage: 'Qualification'
                },
                {
                    value: 4050,
                    stage: 'Needs Analysis'
                },
                {
                    value: 3230,
                    stage: 'Value Proposition'
                },
                {
                    value: 2000,
                    stage: 'Proposal/Price Quote'
                },
                {
                    value: 1200.5,
                    stage: 'Negotiation/Review'
                },
                {
                    value: 700,
                    stage: 'Closed Won'
                },
            ];
            
            this.chartData = [];
            
            for (var i = 0; i < data.length; i++) {
                var item = data[i];
                var value = item.value;
                var nextValue = ((i + 1) < data.length) ? data[i + 1].value : value;
                var o = {
                    data: [[i, value], [i + 1, nextValue]],
                    label: item.stage
                };                

                this.chartData.push(o);
            }
            
            this.maxY = 1000;
            if (data.length) {                
                this.maxY = data[0].value + (data[0].value / 20);
            }
        },            
        
        drow: function () {
            var self = this;
            
            var colors = Espo.Utils.clone(this.colors);
            
            this.chartData.forEach(function (item, i) {
                if (i + 1 > colors.length) {
                    colors.push('#164');
                }
                if (this.chartData.length == i + 1) {
                    colors[i] = this.successColor;
                }
            }, this);
            
            
            this.flotr.draw(this.$container.get(0), this.chartData, {
                colors: colors,
                shadowSize: false,
                lines: {
                    show: true,
                    fill: true,
                    fillOpacity: 1,
                },
                points: {
                    show: true,
                },
                grid: {
                    horizontalLines: false,
                    outline: 'sw'
                },
                yaxis: {
                    min: 0,
                    max: this.maxY,
                    showLabels: false,                        
                },
                xaxis: {
                    min: 0,
                    showLabels: false,
                },
                mouse: {
                    track: true,
                    relative: true,
                    position: 'ne',
                    trackFormatter: function (obj) {
                        if (obj.x >= self.chartData.length) {
                            return null;
                        }
                        return self.formatNumber(obj.y) + ' ' + self.currency;
                    },
                },
                legend: {
                    show: true,
                    noColumns: 5,
                    container: this.$el.find('.legend-container'),
                    labelBoxMargin: 0
                },
            });
        },

    });
});



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

Espo.define('Crm:Views.Dashlets.OpportunitiesByStage', 'Crm:Views.Dashlets.Abstract.Chart', function (Dep) {

    return Dep.extend({

        name: 'OpportunitiesByStage',
        
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
            return 'Opportunity/action/reportByStage?dateFrom=' + this.getOption('dateFrom') + '&dateTo=' + this.getOption('dateTo');
        },
            
        prepareData: function (response) {
            var d = [];            
            for (var label in response) {
                var value = response[label];
                d.push({
                    stage: label,
                    value: value
                });
            }
            
            this.stageList = [];
            
            var data = [];
            var i = 0;
            d.forEach(function (item) {
                var o = {
                    data: [[item.value, d.length - i]],
                    label: this.getLanguage().translateOption(item.stage, 'stage', 'Opportunity'),
                }
                if (item.stage == 'Closed Won') {
                    o.color = this.successColor;
                }
                data.push(o);
                this.stageList.push(this.getLanguage().translateOption(item.stage, 'stage', 'Opportunity'));
                i++;
            }, this);
            
            return data;    
        },            
                    
        setup: function () {
            this.currency = this.getConfig().get('defaultCurrency');
            this.currencySymbol = '';
        },
        
        drow: function () {
            var self = this;
            this.flotr.draw(this.$container.get(0), this.chartData, {
                colors: this.colors,
                shadowSize: false,
                bars: {
                    show: true,
                    horizontal: true,
                    shadowSize: 0,
                    lineWidth: 1,
                    fillOpacity: 1,
                    barWidth: 0.5,
                },
                grid: {
                    horizontalLines: false,
                    outline: 'sw'
                },
                yaxis: {
                    min: 0,
                    showLabels: false,                        
                },
                xaxis: {
                    min: 0,
                    tickFormatter: function (value) {                        
                        if (value != 0) {
                            return self.formatNumber(value) + ' ' + self.currency;
                        }
                        return '';
                    },
                },
                mouse: {
                    track: true,
                    relative: true,
                    position: 's',
                    trackFormatter: function (obj) {
                        return self.formatNumber(obj.x) + ' ' + self.currency;
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



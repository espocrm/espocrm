/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('crm:views/dashlets/sales-by-month', 'crm:views/dashlets/abstract/chart', function (Dep) {

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
                    color: (value >= mid) ? this.successColor : this.colorBad
                });
            }, this);

            return data;
        },

        setup: function () {
            this.currency = this.getConfig().get('defaultCurrency');
            this.currencySymbol = '';

            this.colorBad = this.successColor;
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
                                return moment(self.months[i] + '-01').format('MMM YYYY');
                            }
                        }
                        return '';
                    }
                },
                mouse: {
                    track: true,
                    relative: true,
                    trackFormatter: function (obj) {
                        return self.formatNumber(obj.y) + ' ' + self.currency;
                    },
                }
            });
        },
    });
});



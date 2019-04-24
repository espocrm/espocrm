/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
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

define('crm:views/dashlets/opportunities-by-stage', 'crm:views/dashlets/abstract/chart', function (Dep) {

    return Dep.extend({

        name: 'OpportunitiesByStage',

        setupDefaultOptions: function () {
            this.defaultOptions['dateFrom'] = this.defaultOptions['dateFrom'] || moment().format('YYYY') + '-01-01';
            this.defaultOptions['dateTo'] = this.defaultOptions['dateTo'] || moment().format('YYYY') + '-12-31';
        },

        url: function () {
            var url = 'Opportunity/action/reportByStage?dateFilter='+ this.getDateFilter();

            if (this.getDateFilter() === 'between') {
                url += '&dateFrom=' + this.getOption('dateFrom') + '&dateTo=' + this.getOption('dateTo');
            }
            return url;
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

            this.isEmpty = true;

            var data = [];
            var i = 0;
            d.forEach(function (item) {
                if (item.value) this.isEmpty = false;
                var o = {
                    data: [[item.value, d.length - i]],
                    label: this.getLanguage().translateOption(item.stage, 'stage', 'Opportunity'),
                }
                if (item.stagsuccessColore == 'Closed Won') {
                    o.color = this.successColor;
                }
                data.push(o);
                this.stageList.push(this.getLanguage().translateOption(item.stage, 'stage', 'Opportunity'));
                i++;
            }, this);

            var max = 0;
            if (d.length) {
                d.forEach(function (item) {
                    if ( item.value && item.value > max) {
                        max = item.value;
                    }
                }, this);
            }
            this.max = max;

            return data;
        },

        setup: function () {
            this.currency = this.getConfig().get('defaultCurrency');
            this.currencySymbol = this.getMetadata().get(['app', 'currency', 'symbolMap', this.currency]) || '';
        },

        isNoData: function () {
            return this.isEmpty;
        },

        draw: function () {
            var self = this;
            this.flotr.draw(this.$container.get(0), this.chartData, {
                colors: this.colorList,
                shadowSize: false,
                bars: {
                    show: true,
                    horizontal: true,
                    shadowSize: 0,
                    lineWidth: 1,
                    fillOpacity: 1,
                    barWidth: 0.5
                },
                grid: {
                    horizontalLines: false,
                    outline: 'sw',
                    color: this.gridColor,
                    tickColor: this.tickColor
                },
                yaxis: {
                    min: 0,
                    showLabels: false,
                    color: this.textColor
                },
                xaxis: {
                    min: 0,
                    color: this.textColor,
                    max: this.max + 0.08 * this.max,
                    tickFormatter: function (value) {
                        if (value == 0) {
                            return '';
                        }
                        if (value % 1 == 0) {
                            if (value > self.max + 0.05 * this.max) {
                                return '';
                            }
                            return self.currencySymbol + self.formatNumber(Math.floor(value), false, true).toString();
                        }
                        return '';
                    }
                },
                mouse: {
                    track: true,
                    relative: true,
                    position: 'w',
                    autoPositionHorizontal: true,
                    lineColor: this.hoverColor,
                    trackFormatter: function (obj) {
                        var label = (obj.series.label || self.translate('None'));
                        var value = label  + '<br>' + self.currencySymbol + self.formatNumber(obj.x, true);
                        return value;
                    }
                },
                legend: {
                    show: true,
                    noColumns: this.getLegendColumnNumber(),
                    container: this.$el.find('.legend-container'),
                    labelBoxMargin: 0,
                    labelFormatter: self.labelFormatter.bind(self),
                    labelBoxBorderColor: 'transparent',
                    backgroundOpacity: 0
                }
            });

            this.adjustLegend();
        }
    });
});

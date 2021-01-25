/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

Espo.define('crm:views/dashlets/opportunities-by-lead-source', 'crm:views/dashlets/abstract/chart', function (Dep) {

    return Dep.extend({

        name: 'OpportunitiesByLeadSource',

        url: function () {
            var url = 'Opportunity/action/reportByLeadSource?dateFilter='+ this.getDateFilter();

            if (this.getDateFilter() === 'between') {
                url += '&dateFrom=' + this.getOption('dateFrom') + '&dateTo=' + this.getOption('dateTo');
            }
            return url;
        },

        prepareData: function (response) {
            var data = [];
            for (var label in response) {
                var value = response[label];
                data.push({
                    label: this.getLanguage().translateOption(label, 'source', 'Lead'),
                    data: [[0, value]]
                });
            }
            return data;
        },

        isNoData: function () {
            return !this.chartData.length;
        },

        setupDefaultOptions: function () {
            this.defaultOptions['dateFrom'] = this.defaultOptions['dateFrom'] || moment().format('YYYY') + '-01-01';
            this.defaultOptions['dateTo'] = this.defaultOptions['dateTo'] || moment().format('YYYY') + '-12-31';
        },

        setup: function () {
            this.currency = this.getConfig().get('defaultCurrency');
            this.currencySymbol = this.getMetadata().get(['app', 'currency', 'symbolMap', this.currency]) || '';
        },

        draw: function () {
            var self = this;
            this.flotr.draw(this.$container.get(0), this.chartData, {
                colors: this.colorList,
                shadowSize: false,
                pie: {
                    show: true,
                    explode: 0,
                    lineWidth: 1,
                    fillOpacity: 1,
                    sizeRatio: 0.8,
                    labelFormatter: function (total, value) {
                        var percentage = (100 * value / total).toFixed(2);
                        if (percentage < 7) return '';
                        return '<span class="small" style="font-size: 0.8em;color:'+this.textColor+'">'+ percentage.toString() +'%' + '</span>';
                    }
                },
                grid: {
                    horizontalLines: false,
                    verticalLines: false,
                    outline: '',
                    tickColor: this.tickColor
                },
                yaxis: {
                    showLabels: false,
                    color: this.textColor
                },
                xaxis: {
                    showLabels: false,
                    color: this.textColor
                },
                legend: {
                    show: false,
                },
                mouse: {
                    track: true,
                    relative: true,
                    lineColor: this.hoverColor,
                    trackFormatter: function (obj) {
                        var value = self.currencySymbol + self.formatNumber(obj.y, true);

                        var fraction = obj.fraction || 0;
                        var percentage = (100 * fraction).toFixed(2).toString();

                        return (obj.series.label || self.translate('None')) + '<br>' + value + ' / ' + percentage + '%';
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

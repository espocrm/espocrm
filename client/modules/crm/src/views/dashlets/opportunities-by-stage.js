/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

define('crm:views/dashlets/opportunities-by-stage', ['crm:views/dashlets/abstract/chart'], function (Dep) {

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
            let d = [];

            for (let label in response) {
                var value = response[label];

                d.push({
                    stage: label,
                    value: value,
                });
            }

            this.stageList = [];

            this.isEmpty = true;

            var data = [];
            var i = 0;

            d.forEach(item => {
                if (item.value) {
                    this.isEmpty = false;
                }

                var o = {
                    data: [[item.value, d.length - i]],
                    label: this.getLanguage().translateOption(item.stage, 'stage', 'Opportunity'),
                }

                /*if (item.stagsuccessColore === 'Closed Won') {
                    o.color = this.successColor;
                }*/

                data.push(o);

                this.stageList.push(this.getLanguage().translateOption(item.stage, 'stage', 'Opportunity'));
                i++;
            });

            let max = 0;

            if (d.length) {
                d.forEach(item => {
                    if ( item.value && item.value > max) {
                        max = item.value;
                    }
                });
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
            this.flotr.draw(this.$container.get(0), this.chartData, {
                colors: this.colorList,
                shadowSize: false,
                bars: {
                    show: true,
                    horizontal: true,
                    shadowSize: 0,
                    lineWidth: 1 * this.fontSizeFactor,
                    fillOpacity: 1,
                    barWidth: 0.5,
                },
                grid: {
                    horizontalLines: false,
                    outline: 'sw',
                    color: this.gridColor,
                    tickColor: this.tickColor,
                },
                yaxis: {
                    min: 0,
                    showLabels: false,
                    color: this.textColor,
                },
                xaxis: {
                    min: 0,
                    color: this.textColor,
                    max: this.max + 0.08 * this.max,
                    tickFormatter: value => {
                        value = parseFloat(value);

                        if (!value) {
                            return '';
                        }

                        if (value % 1 === 0) {
                            if (value > this.max + 0.05 * this.max) {
                                return '';
                            }

                            return this.currencySymbol +
                                '<span class="numeric-text">' +
                                this.formatNumber(Math.floor(value), false, true).toString() +
                                '</span>';
                        }

                        return '';
                    },
                },
                mouse: {
                    track: true,
                    relative: true,
                    position: 'w',
                    autoPositionHorizontal: true,
                    lineColor: this.hoverColor,
                    trackFormatter: obj => {
                        let label = this.getHelper().escapeString(obj.series.label || this.translate('None'));

                        return label  + '<br>' + this.currencySymbol +
                            '<span class="numeric-text">' +
                            this.formatNumber(obj.x, true) +
                            '</span>';
                    },
                },
                legend: {
                    show: true,
                    noColumns: this.getLegendColumnNumber(),
                    container: this.$el.find('.legend-container'),
                    labelBoxMargin: 0,
                    labelFormatter: this.labelFormatter.bind(this),
                    labelBoxBorderColor: 'transparent',
                    backgroundOpacity: 0,
                },
            });

            this.adjustLegend();
        },
    });
});

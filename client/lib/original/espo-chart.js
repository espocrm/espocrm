/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/dashlets/abstract/chart', ['views/dashlets/abstract/base','lib!flotr2'], function (Dep, Flotr) {

    return Dep.extend({

        templateContent: '<div class="chart-container"></div><div class="legend-container"></div>',

        decimalMark: '.',
        thousandSeparator: ',',

        defaultColorList: ['#6FA8D6', '#4E6CAD', '#EDC555', '#ED8F42', '#DE6666', '#7CC4A4', '#8A7CC2', '#D4729B'],
        successColor: '#85b75f',
        gridColor: '#ddd',
        tickColor: '#e8eced',
        textColor: '#333',
        hoverColor: '#FF3F19',
        legendColumnWidth: 110,
        legendColumnNumber: 6,

        labelFormatter: function (v) {
            return '<span style="color:'+this.textColor+'">' + v + '</span>';
        },

        init: function () {
            Dep.prototype.init.call(this);

            this.fontSizeFactor = this.getThemeManager().getFontSizeFactor();

            this.flotr = Flotr;

            this.successColor = this.getThemeManager().getParam('chartSuccessColor') || this.successColor;
            this.colorList = this.getThemeManager().getParam('chartColorList') || this.defaultColorList;
            this.tickColor = this.getThemeManager().getParam('chartTickColor') || this.tickColor;
            this.gridColor = this.getThemeManager().getParam('chartGridColor') || this.gridColor;
            this.textColor = this.getThemeManager().getParam('textColor') || this.textColor;
            this.hoverColor = this.getThemeManager().getParam('hoverColor') || this.hoverColor;

            if (this.getPreferences().has('decimalMark')) {
                this.decimalMark = this.getPreferences().get('decimalMark')
            } else {
                if (this.getConfig().has('decimalMark')) {
                    this.decimalMark = this.getConfig().get('decimalMark')
                }
            }

            if (this.getPreferences().has('thousandSeparator')) {
                this.thousandSeparator = this.getPreferences().get('thousandSeparator')
            } else {
                if (this.getConfig().has('thousandSeparator')) {
                    this.thousandSeparator = this.getConfig().get('thousandSeparator')
                }
            }

            this.on('resize', () => {
                if (!this.isRendered()) {
                    return;
                }

                setTimeout(() => {
                    this.adjustContainer();

                    if (this.isNoData()) {
                        this.showNoData();

                        return;
                    }

                    this.draw();
                }, 50);
            });

            $(window).on('resize.chart' + this.id, () => {
                this.adjustContainer();

                if (this.isNoData()) {
                    this.showNoData();

                    return;
                }

                this.draw();
            });

            this.once('remove', () => {
                $(window).off('resize.chart' + this.id)
            });
        },

        formatNumber: function (value, isCurrency, useSiMultiplier) {
            if (value !== null) {
                let maxDecimalPlaces = 2;

                var currencyDecimalPlaces = this.getConfig().get('currencyDecimalPlaces');

                var siSuffix = '';

                if (useSiMultiplier) {
                    if (value >= 1000000) {
                        siSuffix = 'M';
                        value = value / 1000000;
                    } else if (value >= 1000) {
                        siSuffix = 'k';
                        value = value / 1000;
                    }
                }

                if (isCurrency) {
                    if (currencyDecimalPlaces === 0) {
                        value = Math.round(value);
                    } else if (currencyDecimalPlaces) {
                        value = Math.round(value * Math.pow(10, currencyDecimalPlaces)) /
                            (Math.pow(10, currencyDecimalPlaces));
                    } else {
                        value = Math.round(value * Math.pow(10, maxDecimalPlaces)) / (Math.pow(10, maxDecimalPlaces));
                    }
                } else {
                    let maxDecimalPlaces = 4;

                    if (useSiMultiplier) {
                        maxDecimalPlaces = 2;
                    }

                    value = Math.round(value * Math.pow(10, maxDecimalPlaces)) / (Math.pow(10, maxDecimalPlaces));
                }

                var parts = value.toString().split(".");
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandSeparator);

                if (isCurrency) {
                    if (currencyDecimalPlaces === 0) {
                        delete parts[1];
                    }
                    else if (currencyDecimalPlaces) {
                        var decimalPartLength = 0;

                        if (parts.length > 1) {
                            decimalPartLength = parts[1].length;
                        } else {
                            parts[1] = '';
                        }

                        if (currencyDecimalPlaces && decimalPartLength < currencyDecimalPlaces) {
                            var limit = currencyDecimalPlaces - decimalPartLength;

                            for (let i = 0; i < limit; i++) {
                                parts[1] += '0';
                            }
                        }
                    }
                }

                return parts.join(this.decimalMark) + siSuffix;
            }

            return '';
        },

        getLegendColumnNumber: function () {
            const width = this.$el.closest('.panel-body').width();

            const legendColumnNumber = Math.floor(width / (this.legendColumnWidth * this.fontSizeFactor));

            return legendColumnNumber || this.legendColumnNumber;
        },

        getLegendHeight: function () {
            const lineNumber = Math.ceil(this.chartData.length / this.getLegendColumnNumber());
            let legendHeight = 0;

            const lineHeight = (this.getThemeManager().getParam('dashletChartLegendRowHeight') || 19) *
                this.fontSizeFactor;

            const paddingTopHeight = (this.getThemeManager().getParam('dashletChartLegendPaddingTopHeight') || 7) *
                this.fontSizeFactor;

            if (lineNumber > 0) {
                legendHeight = lineHeight * lineNumber + paddingTopHeight;
            }

            return legendHeight;
        },

        adjustContainer: function () {
            const legendHeight = this.getLegendHeight();
            const heightCss = `calc(100% - ${legendHeight.toString()}px)`;

            this.$container.css('height', heightCss);
        },

        adjustLegend: function () {
            const number = this.getLegendColumnNumber();

            if (!number) {
                return;
            }

            const dashletChartLegendBoxWidth = (this.getThemeManager().getParam('dashletChartLegendBoxWidth') || 21) *
                this.fontSizeFactor;

            const containerWidth = this.$legendContainer.width();
            const width = Math.floor((containerWidth - dashletChartLegendBoxWidth * number) / number);
            const columnNumber = this.$legendContainer.find('> table tr:first-child > td').length / 2;

            const tableWidth = (width + dashletChartLegendBoxWidth) * columnNumber;

            this.$legendContainer.find('> table')
                .css('table-layout', 'fixed')
                .attr('width', tableWidth);

            this.$legendContainer.find('td.flotr-legend-label').attr('width', width);
            this.$legendContainer.find('td.flotr-legend-color-box').attr('width', dashletChartLegendBoxWidth);

            this.$legendContainer.find('td.flotr-legend-label > span').each((i, span) => {
                span.setAttribute('title', span.textContent);
            });
        },

        afterRender: function () {
            this.$el.closest('.panel-body').css({
                'overflow-y': 'visible',
                'overflow-x': 'visible',
            });

            this.$legendContainer = this.$el.find('.legend-container');
            this.$container = this.$el.find('.chart-container');

            this.fetch(function (data) {
                this.chartData = this.prepareData(data);

                this.adjustContainer();

                if (this.isNoData()) {
                    this.showNoData();

                    return;
                }

                setTimeout(() => {
                    if (!this.$container.length || !this.$container.is(":visible")) {
                        return;
                    }

                    this.draw();
                }, 1);
            });
        },

        isNoData: function () {
            return false;
        },

        url: function () {},

        prepareData: function (response) {
            return response;
        },

        fetch: function (callback) {
            Espo.Ajax.getRequest(this.url())
                .then(response => {
                    callback.call(this, response);
                });
        },

        getDateFilter: function () {
            return this.getOption('dateFilter') || 'currentYear';
        },

        showNoData: function () {
            this.$container.empty();

            const $text = $('<span>').html(this.translate('No Data')).addClass('text-muted');

            const $div = $('<div>')
                .css('text-align', 'center')
                .css('font-size', 'calc(var(--font-size-base) * 1.2)')
                .css('display', 'table')
                .css('width', '100%')
                .css('height', '100%')
                .css('user-select', 'none');

            $text
                .css('display', 'table-cell')
                .css('vertical-align', 'middle')
                .css('padding-bottom', 'calc(var(--font-size-base) * 1.5)');


            $div.append($text);

            this.$container.append($div);
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/dashlets/sales-pipeline', ['crm:views/dashlets/abstract/chart', 'lib!espo-funnel-chart'],
function (Dep) {

    return Dep.extend({

        name: 'SalesPipeline',

        setupDefaultOptions: function () {
            this.defaultOptions['dateFrom'] = this.defaultOptions['dateFrom'] || moment().format('YYYY') + '-01-01';
            this.defaultOptions['dateTo'] = this.defaultOptions['dateTo'] || moment().format('YYYY') + '-12-31';
        },

        url: function () {
            var url = 'Opportunity/action/reportSalesPipeline?dateFilter='+ this.getDateFilter();

            if (this.getDateFilter() === 'between') {
                url += '&dateFrom=' + this.getOption('dateFrom') + '&dateTo=' + this.getOption('dateTo');
            }

            if (this.getOption('useLastStage')) {
                url += '&useLastStage=true';
            }

            if (this.getOption('teamId')) {
                url += '&teamId=' + this.getOption('teamId');
            }

            return url;
        },

        isNoData: function () {
            return this.isEmpty;
        },

        prepareData: function (response) {
            let list = [];

            this.isEmpty = true;

            response.dataList.forEach(item => {
                if (item.value) {
                    this.isEmpty = false;
                }

                list.push({
                    stageTranslated: this.getLanguage().translateOption(item.stage, 'stage', 'Opportunity'),
                    value: item.value,
                    stage: item.stage,
                });
            });

            return list;
        },

        setup: function () {
            this.currency = this.getConfig().get('defaultCurrency');
            this.currencySymbol = this.getMetadata().get(['app', 'currency', 'symbolMap', this.currency]) || '';

            this.chartData = [];
        },

        draw: function () {
            let colors = Espo.Utils.clone(this.colorList);

            this.chartData.forEach((item, i) => {
                if (i + 1 > colors.length) {
                    colors.push('#164');
                }

                if (this.chartData.length === i + 1 && item.stage === 'Closed Won') {
                    colors[i] = this.successColor;
                }

                this.chartData[i].color = colors[i];
            });

            this.$container.empty();

            let tooltipStyleString =
                'opacity:0.7;background-color:#000;color:#fff;position:absolute;'+
                'padding:2px 8px;-moz-border-radius:4px;border-radius:4px;white-space:nowrap;'

            // noinspection JSUnusedGlobalSymbols
            new EspoFunnel.Funnel(
                this.$container.get(0),
                {
                    colors: colors,
                    outlineColor: this.hoverColor,
                    callbacks: {
                        tooltipHtml: (i) => {
                            let value = this.chartData[i].value;

                            return this.chartData[i].stageTranslated +
                                '<br>' + this.currencySymbol +
                                '<span class="numeric-text">' +
                                this.formatNumber(value, true) +
                                '</span>';
                        },
                    },
                    tooltipClassName: 'flotr-mouse-value',
                    tooltipStyleString: tooltipStyleString,
                },
                this.chartData
            );

            this.drawLegend();
            this.adjustLegend();
        },

        drawLegend: function () {
            let number = this.getLegendColumnNumber();
            let $container = this.$el.find('.legend-container');

            let html = '<table style="font-size: smaller; color:' + this.textColor + '">';

            this.chartData.forEach((item, i) => {
                if (i % number === 0) {
                    if (i > 0) {
                        html += '</tr>';
                    }

                    html += '<tr>';
                }

                let stageTranslated = this.getHelper().escapeString(item.stageTranslated);

                let box = '<div style="border: var(--1px) solid transparent; padding: var(--1px)">'+
                    '<div style="width: var(--13px); height: var(--9px); border: var(--1px) solid ' + item.color + '">'+
                    '<div style="width: var(--14px); height: var(--10px); background-color:' + item.color + ';"></div></div></div>';

                html += '<td class="flotr-legend-color-box">' + box + '</td>';

                html += '<td class="flotr-legend-label"><span title="' + stageTranslated + '">'+
                    stageTranslated + '</span></td>';
            });

            html += '</tr>';
            html += '</table>';

            $container.html(html);
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/dashlets/sales-by-month', ['crm:views/dashlets/abstract/chart'], function (Dep) {

    return Dep.extend({

        name: 'SalesByMonth',

        columnWidth: 50,

        setupDefaultOptions: function () {
            this.defaultOptions['dateFrom'] = this.defaultOptions['dateFrom'] || moment().format('YYYY') + '-01-01';
            this.defaultOptions['dateTo'] = this.defaultOptions['dateTo'] || moment().format('YYYY') + '-12-31';
        },

        url: function () {
            var url = 'Opportunity/action/reportSalesByMonth?dateFilter='+ this.getDateFilter();

            if (this.getDateFilter() === 'between') {
                url += '&dateFrom=' + this.getOption('dateFrom') + '&dateTo=' + this.getOption('dateTo');
            }

            return url;
        },

        getLegendHeight: function () {
            return 0;
        },

        isNoData: function () {
            return this.isEmpty;
        },

        prepareData: function (response) {
            var monthList = this.monthList = response.keyList;

            var dataMap = response.dataMap || {};
            var values = [];

            monthList.forEach(month => {
                values.push(dataMap[month]);
            });

            this.chartData = [];

            this.isEmpty = true;

            var mid = 0;

            if (values.length) {
                mid = values.reduce((a, b) => a + b) / values.length;
            }

            var data = [];
            var max = 0;

            values.forEach((value, i) => {
                if (value) {
                    this.isEmpty = false;
                }

                if (value && value > max) {
                    max = value;
                }

                data.push({
                    data: [[i, value]],
                    color: (value >= mid) ? this.successColor : this.colorBad,
                });
            });

            this.max = max;

            return data;
        },

        setup: function () {
            this.currency = this.getConfig().get('defaultCurrency');
            this.currencySymbol = this.getMetadata().get(['app', 'currency', 'symbolMap', this.currency]) || '';

            this.colorBad = this.successColor;
        },

        getTickNumber: function () {
            var containerWidth = this.$container.width();

            return Math.floor(containerWidth / this.columnWidth * this.fontSizeFactor);
        },

        draw: function () {
            var tickNumber = this.getTickNumber();

            this.flotr.draw(this.$container.get(0), this.chartData, {
                shadowSize: false,
                bars: {
                    show: true,
                    horizontal: false,
                    shadowSize: 0,
                    lineWidth: 1 * this.fontSizeFactor,
                    fillOpacity: 1,
                    barWidth: 0.5,
                },
                grid: {
                    horizontalLines: true,
                    verticalLines: false,
                    outline: 'sw',
                    color: this.gridColor,
                    tickColor: this.tickColor,
                },
                yaxis: {
                    min: 0,
                    showLabels: true,
                    color: this.textColor,
                    max: this.max + 0.08 * this.max,
                    tickFormatter: (value) => {
                        value =  parseFloat(value);

                        if (!value) {
                            return '';
                        }

                        if (value % 1 === 0) {
                            return this.currencySymbol +
                                '<span class="numeric-text">' +
                                this.formatNumber(Math.floor(value), false, true).toString() + '</span>';
                        }

                        return '';
                    },
                },
                xaxis: {
                    min: 0,
                    color: this.textColor,
                    noTicks: tickNumber,
                    tickFormatter: (value) => {
                        if (value % 1 === 0) {
                            let i = parseInt(value);

                            if (i in this.monthList) {
                                if (this.monthList.length - tickNumber > 5 && i === this.monthList.length - 1) {
                                    return '';
                                }

                                return moment(this.monthList[i] + '-01').format('MMM YYYY');
                            }
                        }

                        return '';
                    }
                },
                mouse: {
                    track: true,
                    relative: true,
                    lineColor: this.hoverColor,
                    position: 's',
                    autoPositionVertical: true,
                    trackFormatter: obj => {
                        let i = parseInt(obj.x);
                        let value = '';

                        if (i in this.monthList) {
                            value += moment(this.monthList[i] + '-01').format('MMM YYYY') + '<br>';
                        }

                        return value + this.currencySymbol +
                            '<span class="numeric-text">' + this.formatNumber(obj.y, true) + '</span>';
                    }
                },
            })
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/dashlets/opportunities-by-lead-source', ['crm:views/dashlets/abstract/chart'], function (Dep) {

    return Dep.extend({

        name: 'OpportunitiesByLeadSource',

        url: function () {
            let url = 'Opportunity/action/reportByLeadSource?dateFilter=' + this.getDateFilter();

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
            this.flotr.draw(this.$container.get(0), this.chartData, {
                colors: this.colorList,
                shadowSize: false,
                pie: {
                    show: true,
                    explode: 0,
                    lineWidth: 1 * this.fontSizeFactor,
                    fillOpacity: 1,
                    sizeRatio: 0.8,
                    labelFormatter: (total, value) => {
                        const percentage = Math.round(100 * value / total);

                        if (percentage < 5) {
                            return '';
                        }

                        return '<span class="small numeric-text" style="font-size: 0.8em;color:'+this.textColor+'">' +
                            percentage.toString() +'%' + '</span>';
                    },
                },
                grid: {
                    horizontalLines: false,
                    verticalLines: false,
                    outline: '',
                    tickColor: this.tickColor,
                },
                yaxis: {
                    showLabels: false,
                    color: this.textColor,
                },
                xaxis: {
                    showLabels: false,
                    color: this.textColor,
                },
                mouse: {
                    track: true,
                    relative: true,
                    lineColor: this.hoverColor,
                    trackFormatter: (obj) => {
                        const value = this.currencySymbol +
                            '<span class="numeric-text">' + this.formatNumber(obj.y, true) + '</span>';

                        const fraction = obj.fraction || 0;
                        const percentage = '<span class="numeric-text">' +
                            (100 * fraction).toFixed(2).toString() +'</span>';

                        const label = this.getHelper().escapeString(obj.series.label || this.translate('None'));

                        return label + '<br>' +  value + ' / ' + percentage + '%';
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


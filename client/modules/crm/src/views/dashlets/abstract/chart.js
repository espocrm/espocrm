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

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

define('crm:views/dashlets/abstract/chart', ['views/dashlets/abstract/base','lib!Flotr'], function (Dep, Flotr) {

    return Dep.extend({

        _template: '<div class="chart-container"></div><div class="legend-container"></div>',

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

            this.on('resize', function () {
                if (!this.isRendered()) return;
                setTimeout(function () {
                    this.adjustContainer();
                    if (this.isNoData()) {
                        this.showNoData();
                        return;
                    }
                    this.draw();
                }.bind(this), 50);
            }, this);

            $(window).on('resize.chart' + this.id, function () {
                this.adjustContainer();
                if (this.isNoData()) {
                    this.showNoData();
                    return;
                }
                this.draw();
            }.bind(this));

            this.once('remove', function () {
                $(window).off('resize.chart' + this.id)
            }, this);
        },

        formatNumber: function (value, isCurrency, useSiMultiplier) {
            if (value !== null) {
                var maxDecimalPlaces = 2;
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
                        value = Math.round(value * Math.pow(10, currencyDecimalPlaces)) / (Math.pow(10, currencyDecimalPlaces));
                    } else {
                        value = Math.round(value * Math.pow(10, maxDecimalPlaces)) / (Math.pow(10, maxDecimalPlaces));
                    }
                } else {
                    var maxDecimalPlaces = 4;
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
                    } else if (currencyDecimalPlaces) {
                        var decimalPartLength = 0;
                        if (parts.length > 1) {
                            decimalPartLength = parts[1].length;
                        } else {
                            parts[1] = '';
                        }

                        if (currencyDecimalPlaces && decimalPartLength < currencyDecimalPlaces) {
                            var limit = currencyDecimalPlaces - decimalPartLength;
                            for (var i = 0; i < limit; i++) {
                                parts[1] += '0';
                            }
                        }
                    }
                }

                var value = parts.join(this.decimalMark) + siSuffix;
                return value;
            }
            return '';
        },

        getLegendColumnNumber: function () {
            var width = this.$el.closest('.panel-body').width();
            var legendColumnNumber = Math.floor(width / this.legendColumnWidth);
            return legendColumnNumber || this.legendColumnNumber;
        },

        getLegendHeight: function () {
            var lineNumber = Math.ceil(this.chartData.length / this.getLegendColumnNumber());
            var legendHeight = 0;

            var lineHeight = this.getThemeManager().getParam('dashletChartLegendRowHeight') || 19;
            var paddingTopHeight = this.getThemeManager().getParam('dashletChartLegendPaddingTopHeight') || 7;

            if (lineNumber > 0) {
                legendHeight = lineHeight * lineNumber + paddingTopHeight;
            }
            return legendHeight;
        },

        adjustContainer: function () {
            var legendHeight = this.getLegendHeight();
            var heightCss = 'calc(100% - ' + legendHeight.toString() + 'px)';
            this.$container.css('height', heightCss);
        },

        adjustLegend: function () {
            var number = this.getLegendColumnNumber();
            if (!number) return;

            var dashletChartLegendBoxWidth = this.getThemeManager().getParam('dashletChartLegendBoxWidth') || 21;

            var containerWidth = this.$legendContainer.width();

            var width = Math.floor((containerWidth - dashletChartLegendBoxWidth * number) / number);

            var columnNumber = this.$legendContainer.find('> table tr:first-child > td').length / 2;

            var tableWidth = (width + dashletChartLegendBoxWidth) * columnNumber;

            this.$legendContainer.find('> table')
                .css('table-layout', 'fixed')
                .attr('width', tableWidth);

            this.$legendContainer.find('td.flotr-legend-label').attr('width', width);
            this.$legendContainer.find('td.flotr-legend-color-box').attr('width', dashletChartLegendBoxWidth);

            this.$legendContainer.find('td.flotr-legend-label > span').each(function(i, span) {
                span.setAttribute('title', span.textContent);
            }.bind(this));
        },

        afterRender: function () {
            this.$el.closest('.panel-body').css({
                'overflow-y': 'visible',
                'overflow-x': 'visible'
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

                setTimeout(function () {
                    if (!this.$container.length || !this.$container.is(":visible")) return;
                    this.draw();
                }.bind(this), 1);
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
            $.ajax({
                type: 'get',
                url: this.url(),
                success: function (response) {
                    callback.call(this, response);
                }.bind(this)
            });
        },

        getDateFilter: function () {
            return this.getOption('dateFilter') || 'currentYear';
        },

        showNoData: function () {
            var fontSize = this.getThemeManager().getParam('fontSize') || 14;
            this.$container.empty();
            var textFontSize = fontSize * 1.2;

            var $text = $('<span>').html(this.translate('No Data')).addClass('text-muted');

            var $div = $('<div>').css('text-align', 'center')
                                 .css('font-size', textFontSize + 'px')
                                 .css('display', 'table')
                                 .css('width', '100%')
                                 .css('height', '100%');

            $text
                .css('display', 'table-cell')
                .css('vertical-align', 'middle')
                .css('padding-bottom', fontSize * 1.5 + 'px');


            $div.append($text);

            this.$container.append($div);
        }

    });
});

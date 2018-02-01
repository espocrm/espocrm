/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('crm:views/dashlets/abstract/chart', ['views/dashlets/abstract/base','lib!Flotr'], function (Dep, Flotr) {

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

        legendColumnWidth: 90,

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

            this.once('after:render', function () {
                $(window).on('resize.chart' + this.name, function () {
                    this.draw();
                }.bind(this));
            }, this);

            this.once('remove', function () {
                $(window).off('resize.chart' + this.name)
            }, this);
        },

        formatNumber: function (value, isCurrency) {
            if (value !== null) {
                var maxDecimalPlaces = 2;
                var currencyDecimalPlaces = this.getConfig().get('currencyDecimalPlaces');

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

                var value = parts.join(this.decimalMark);
                return value;
            }
            return '';
        },

        getLegentColumnNumber: function () {
            var width = this.$el.closest('.panel-body').width();
            var legendColumnNumber = Math.floor(width / this.legendColumnWidth);
            return legendColumnNumber || this.legendColumnNumber;
        },

        getLegentHeight: function () {
            var lineNumber = Math.ceil(this.chartData.length / this.getLegentColumnNumber());
            var legendHeight = 0;

            var lineHeight = this.getThemeManager().getParam('dashletChartLegentRowHeight') || 22;
            if (lineNumber > 0) {
                legendHeight = lineHeight * lineNumber;
            }
            return legendHeight;
        },

        afterRender: function () {
            this.$el.closest('.panel-body').css({
                'overflow-y': 'visible',
                'overflow-x': 'visible'
            });
            this.fetch(function (data) {
                this.chartData = this.prepareData(data);

                var $container = this.$container = this.$el.find('.chart-container');
                var legendHeight = this.getLegentHeight();
                var heightCss = 'calc(100% - '+legendHeight.toString()+'px)';
                $container.css('height', heightCss);

                setTimeout(function () {
                    if (!$container.size() || !$container.is(":visible")) return;
                    this.draw();
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

        getDateFilter: function () {
            return this.getOption('dateFilter') || 'currentYear';
        }

    });
});

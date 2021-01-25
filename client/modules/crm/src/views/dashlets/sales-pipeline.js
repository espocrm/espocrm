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

define('crm:views/dashlets/sales-pipeline', ['crm:views/dashlets/abstract/chart', 'lib!espo-funnel-chart'], function (Dep) {

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
            var list = [];

            this.isEmpty = true;

            response.dataList.forEach(function (item) {
                if (item.value) this.isEmpty = false;
                list.push({
                    stageTranslated: this.getLanguage().translateOption(item.stage, 'stage', 'Opportunity'),
                    value: item.value,
                    stage: item.stage,
                });
            }, this);

            return list;
        },

        setup: function () {
            this.currency = this.getConfig().get('defaultCurrency');
            this.currencySymbol = this.getMetadata().get(['app', 'currency', 'symbolMap', this.currency]) || '';

            this.chartData = [];
        },

        draw: function () {
            var self = this;

            var colors = Espo.Utils.clone(this.colorList);

            this.chartData.forEach(function (item, i) {
                if (i + 1 > colors.length) {
                    colors.push('#164');
                }
                if (this.chartData.length == i + 1 && item.stage === 'Closed Won') {
                    colors[i] = this.successColor;
                }
                this.chartData[i].color = colors[i];
            }, this);

            this.$container.empty();

            var funnel = new EspoFunnel.Funnel(
                this.$container.get(0),
                {
                    colors: colors,
                    callbacks: {
                        tooltipHtml: function (i) {
                            var value = self.chartData[i].value;
                            return self.chartData[i].stageTranslated + '<br>' + self.currencySymbol + self.formatNumber(value, true)
                        },
                    },
                    tooltipClassName: 'flotr-mouse-value',
                    tootlipStyleString:
                        'opacity:0.7;background-color:#000;color:#fff;position:absolute;'+
                        'padding:2px 8px;-moz-border-radius:4px;border-radius:4px;white-space:nowrap;',
                },
                this.chartData
            );

            this.drawLegend();

            this.adjustLegend();
        },

        drawLegend: function () {
            var number = this.getLegendColumnNumber();

            var $container = this.$el.find('.legend-container');

            var html = '<table style="font-size: smaller; color:'+this.textColor+'">';

            this.chartData.forEach(function (item, i) {
                if (i % number == 0) {
                    if (i > 0) html += '</tr>';
                    html += '<tr>';
                }

                var box = '<div style="border:1px solid transparent;padding:1px">'+
                    '<div style="width:13px;height:9px;border:1px solid '+item.color+'">'+
                    '<div style="width:14px;height:10px;background-color:'+item.color+';"></div></div></div>';

                html += '<td class="flotr-legend-color-box">'+box+'</td>';

                html += '<td class="flotr-legend-label"><span title="'+item.stageTranslated+'">'+
                    item.stageTranslated+'</span></td>';
            }, this);

            html += '</tr>';

            html += '</table>';

            $container.html(html);
        },

    });
});

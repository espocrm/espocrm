define('custom:views/dashlets/activity-summary', ['crm:views/dashlets/abstract/chart'], function (Dep) {

    return Dep.extend({

        name: 'ActivitySummary',

        columnWidth: 60,

        setupDefaultOptions: function () {
            this.defaultOptions['days'] = this.defaultOptions['days'] || 30;
        },

        url: function () {
            var days = this.getOption('days') || 30;
            return 'ActivitySummary?days=' + days;
        },

        isNoData: function () {
            return this.isEmpty;
        },

        prepareData: function (response) {
            response = response || {};
            var byAccount = response.byAccount || [];
            this.accountList = [];
            this.isEmpty = true;

            var types = ['Meeting', 'Call', 'Task', 'Email'];
            var seriesMap = {
                Meeting: [],
                Call: [],
                Task: [],
                Email: []
            };

            var max = 0;

            byAccount.forEach((item, i) => {
                this.accountList.push(item.accountName || item.accountId);
                if (item.total && item.total > 0) {
                    this.isEmpty = false;
                }
                if (item.total > max) {
                    max = item.total;
                }

                types.forEach(type => {
                    var count = item[type] || 0;
                    seriesMap[type].push([i, count]);
                });
            });

            this.max = max;

            var colors = {
                Meeting: '#4E6CAD',
                Call: '#6FA8D6',
                Task: '#EDC555',
                Email: '#DE6666'
            };

            var chartData = [];
            types.forEach(type => {
                var label = type;
                if (this.getLanguage()) {
                    label = this.getLanguage().translate(type, 'scopeNames') || type;
                }

                chartData.push({
                    label: label,
                    data: seriesMap[type],
                    color: colors[type],
                    bars: {
                        show: true,
                        stacked: true,
                        horizontal: false,
                        barWidth: 0.5,
                        fillOpacity: 0.9,
                        lineWidth: 1 * (this.fontSizeFactor || 1)
                    }
                });
            });

            return chartData;
        },

        draw: function () {
            var accountCount = this.accountList.length;

            this.flotr.draw(this.$container.get(0), this.chartData, {
                shadowSize: false,
                grid: {
                    horizontalLines: true,
                    verticalLines: false,
                    outline: 'sw',
                    color: this.gridColor,
                    tickColor: this.tickColor
                },
                yaxis: {
                    min: 0,
                    showLabels: true,
                    color: this.textColor,
                    max: (this.max > 0 ? this.max * 1.15 : 5),
                    tickFormatter: value => {
                        if (value % 1 === 0) {
                            return value.toString();
                        }
                        return '';
                    }
                },
                xaxis: {
                    min: -0.5,
                    max: accountCount > 0 ? accountCount - 0.5 : 0.5,
                    color: this.textColor,
                    noTicks: accountCount,
                    tickFormatter: value => {
                        var i = Math.round(value);
                        if (Math.abs(value - i) < 0.001 && i in this.accountList) {
                            return this.getHelper().escapeString(this.accountList[i]);
                        }
                        return '';
                    }
                },
                mouse: {
                    track: true,
                    relative: true,
                    lineColor: this.hoverColor,
                    position: 's',
                    trackFormatter: obj => {
                        var index = Math.round(obj.x);
                        var accountName = this.accountList[index] || '';
                        var seriesLabel = obj.series.label || '';
                        var count = Math.round(obj.y);
                        return (accountName ? '<b>' + this.getHelper().escapeString(accountName) + '</b><br>' : '') +
                            this.getHelper().escapeString(seriesLabel) + ': ' +
                            '<span class="numeric-text">' + count + '</span>';
                    }
                },
                legend: {
                    show: true,
                    noColumns: this.getLegendColumnNumber(),
                    container: this.$legendContainer,
                    labelBoxMargin: 0,
                    labelFormatter: this.labelFormatter.bind(this),
                    labelBoxBorderColor: 'transparent',
                    backgroundOpacity: 0
                }
            });

            this.adjustLegend();
        }
    });
});

define('custom:views/dashlets/activity-summary', ['crm:views/dashlets/abstract/chart'], function (Dep) {

    return Dep.extend({

        name: 'ActivitySummary',

        setup: function () {
            this.accountList = [];
            this.isEmpty = true;
            this.max = 0;
        },

        url: function () {
            return 'ActivitySummary/action/report';
        },

        prepareData: function (response) {
            const list = response.dataList || [];
            const typeList = ['Call', 'Meeting', 'Email'];
            const offsetList = [-0.26, 0, 0.26];

            this.accountList = list.map(item => item.accountName || item.accountId);
            this.isEmpty = list.length === 0;
            this.max = Math.max(1, ...list.map(item => item.total || 0));

            return typeList.map((type, typeIndex) => ({
                label: this.getLanguage().translate(type, 'scopeNamesPlural'),
                data: list.map((item, index) => [
                    item[type] || 0,
                    index + offsetList[typeIndex],
                ]),
            }));
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
                    barWidth: 0.24,
                },
                grid: {
                    horizontalLines: false,
                    outline: 'sw',
                    color: this.gridColor,
                    tickColor: this.tickColor,
                },
                xaxis: {
                    min: 0,
                    max: this.max * 1.12,
                    color: this.textColor,
                    tickFormatter: value => value % 1 === 0 ? this.formatNumber(value, false, false) : '',
                },
                yaxis: {
                    min: -0.5,
                    max: this.accountList.length - 0.5,
                    ticks: this.accountList.map((name, index) => [index, this.getHelper().escapeString(name)]),
                    color: this.textColor,
                },
                mouse: {
                    track: true,
                    relative: true,
                    position: 'w',
                    autoPositionHorizontal: true,
                    lineColor: this.hoverColor,
                    trackFormatter: obj =>
                        this.getHelper().escapeString(obj.series.label) + '<br>' +
                        this.formatNumber(obj.x, false, false),
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

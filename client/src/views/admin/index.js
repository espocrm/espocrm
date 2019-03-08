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

Espo.define('views/admin/index', 'view', function (Dep) {

    return Dep.extend({

        template: 'admin/index',

        events: {
            'click [data-action]': function (e) {
                Espo.Utils.handleAction(this, e);
            },
        },

        data: function () {
            return {
                panelDataList: this.panelDataList,
                iframeUrl: this.iframeUrl,
                iframeHeight: this.getConfig().get('adminPanelIframeHeight') || 1330
            };
        },

        setup: function () {
            this.panelDataList = [];

            var panels = this.getMetadata().get('app.adminPanel') || {};
            for (var name in panels) {
                var panelItem = Espo.Utils.cloneDeep(panels[name]);
                panelItem.name = name;
                panelItem.itemList = panelItem.itemList || [];
                if (panelItem.items) {
                    panelItem.items.forEach(function (item) {
                        panelItem.itemList.push(item);
                    }, this);
                }
                this.panelDataList.push(panelItem);
            }

            this.panelDataList.sort(function (v1, v2) {
                if (!('order' in v1) && ('order' in v2)) return 0;
                if (!('order' in v2)) return 0;
                return v1.order - v2.order;
            }.bind(this));

            var iframeParams = [
                'version=' + encodeURIComponent(this.getConfig().get('version')),
                'css=' + encodeURIComponent(this.getConfig().get('siteUrl') + '/' + this.getThemeManager().getStylesheet())
            ];
            this.iframeUrl = this.getConfig().get('adminPanelIframeUrl') || 'https://s.espocrm.com/';
            if (~this.iframeUrl.indexOf('?')) {
                this.iframeUrl += '&' + iframeParams.join('&');
            } else {
                this.iframeUrl += '?' + iframeParams.join('&');
            }

            if (!this.getConfig().get('adminNotificationsDisabled')) {
                this.createView('notificationsPanel', 'views/admin/panels/notifications', {
                    el: this.getSelector() + ' .notifications-panel-container'
                });
            }
        },

        updatePageTitle: function () {
            this.setPageTitle(this.getLanguage().translate('Administration'));
        },

        actionClearCache: function () {
            this.trigger('clear-cache');
        },

        actionRebuild: function () {
            this.trigger('rebuild');
        },

    });
});

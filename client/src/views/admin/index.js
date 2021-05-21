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

define('views/admin/index', 'view', function (Dep) {

    return Dep.extend({

        template: 'admin/index',

        events: {
            'click [data-action]': function (e) {
                Espo.Utils.handleAction(this, e);
            },
            'keyup input[data-name="quick-search"]': function (e) {
                this.processQuickSearch(e.currentTarget.value);
            },
        },

        data: function () {
            return {
                panelDataList: this.panelDataList,
                iframeUrl: this.iframeUrl,
                iframeHeight: this.getConfig().get('adminPanelIframeHeight') || 1330,
                iframeDisabled: this.getConfig().get('adminPanelIframeDisabled') || false,
            };
        },

        afterRender: function () {
            if (this.quickSearchText) {
                this.$el.find('input[data-name="quick-search"]').val(this.quickSearchText);

                this.processQuickSearch(this.quickSearchText);
            }
        },

        setup: function () {
            this.panelDataList = [];

            var panels = this.getMetadata().get('app.adminPanel') || {};

            for (var name in panels) {
                var panelItem = Espo.Utils.cloneDeep(panels[name]);

                panelItem.name = name;
                panelItem.itemList = panelItem.itemList || [];
                panelItem.label = this.translate(panelItem.label, 'labels', 'Admin');

                if (panelItem.itemList) {
                    panelItem.itemList.forEach(function (item) {
                        item.label = this.translate(item.label, 'labels', 'Admin');

                        if (item.description) {
                            item.keywords = (this.getLanguage().get('Admin', 'keywords', item.description) || '')
                                .split(',');
                        } else {
                            item.keywords = [];
                        }
                    }, this);
                }

                // Legacy support.
                if (panelItem.items) {
                    panelItem.items.forEach(function (item) {
                        item.label = this.translate(item.label, 'labels', 'Admin');
                        panelItem.itemList.push(item);
                        item.keywords = [];
                    }, this);
                }

                this.panelDataList.push(panelItem);
            }

            this.panelDataList.sort(function (v1, v2) {
                if (!('order' in v1) && ('order' in v2)) {
                    return 0;
                }

                if (!('order' in v2)) {
                    return 0;
                }

                return v1.order - v2.order;
            }.bind(this));

            var iframeParams = [
                'version=' + encodeURIComponent(this.getConfig().get('version')),
                'css=' + encodeURIComponent(this.getConfig().get('siteUrl') +
                    '/' + this.getThemeManager().getStylesheet())
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

        processQuickSearch: function (text) {
            text = text.trim();

            this.quickSearchText = text;

            var $noData = this.$noData || this.$el.find('.no-data');

            $noData.addClass('hidden');

            if (!text) {
                this.$el.find('.admin-content-section').removeClass('hidden');
                this.$el.find('.admin-content-row').removeClass('hidden');

                return;
            }

            text = text.toLowerCase();

            this.$el.find('.admin-content-section').addClass('hidden');
            this.$el.find('.admin-content-row').addClass('hidden');

            anythingMatched = false;

            this.panelDataList.forEach(function (panel, panelIndex) {
                var panelMatched = false;

                var panelLabelMatched = false;

                if (panel.label && panel.label.toLowerCase().indexOf(text) === 0) {
                    panelMatched = true;
                    panelLabelMatched = true;
                }

                panel.itemList.forEach(function (row, rowIndex) {
                    if (!row.label) return;

                    var matched = false;

                    if (panelLabelMatched) {
                        matched = true;
                    }

                    if (!matched) {
                        matched = row.label.toLowerCase().indexOf(text) === 0;
                    }

                    if (!matched) {
                        var wordList = row.label.split(' ');

                        wordList.forEach(function (word) {
                            if (word.toLowerCase().indexOf(text) === 0) {
                                matched = true;
                            }
                        }, this);

                        if (!matched) {
                            matched = ~row.keywords.indexOf(text);
                        }
                        if (!matched) {
                            if (text.length > 3) {
                                row.keywords.forEach(function (word) {
                                    if (word.indexOf(text) === 0) {
                                        matched = true;
                                    }
                                }, this);
                            }

                        }
                    }

                    if (matched) {
                        panelMatched = true;

                        this.$el.find(
                            '.admin-content-section[data-index="'+panelIndex.toString()+'"] '+
                            '.admin-content-row[data-index="'+rowIndex.toString()+'"]'
                        ).removeClass('hidden');

                        anythingMatched = true;
                    }
                }, this);

                if (panelMatched) {

                    this.$el
                        .find('.admin-content-section[data-index="' + panelIndex.toString() + '"]')
                        .removeClass('hidden');

                    anythingMatched = true;
                }
            }, this);

            if (!anythingMatched) {
                $noData.removeClass('hidden');
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

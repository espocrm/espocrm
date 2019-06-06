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

Espo.define('views/modals/last-viewed', ['views/modal', 'search-manager'], function (Dep, SearchManager) {

    return Dep.extend({

        header: false,

        scope: 'ActionHistoryRecord',

        className: 'dialog dialog-record',

        template: 'modals/last-viewed',

        backdrop: true,

        events: _.extend({
            'click .list .cell > a': function () {
                this.close();
            },
        }, Dep.prototype.events),

        setup: function () {
            Dep.prototype.setup.call(this);

            this.headerHtml = this.getLanguage().translate('LastViewed', 'scopeNamesPlural');
            this.headerHtml = '<a href="#LastViewed" class="action" data-action="listView">' + this.headerHtml + '</a>';

            this.waitForView('list');

            this.getCollectionFactory().create(this.scope, function (collection) {
                collection.maxSize = this.getConfig().get('recordsPerPage');
                this.collection = collection;

                collection.url = 'LastViewed';

                this.loadList();
                collection.fetch();
            }, this);

        },

        actionListView: function () {
            this.getRouter().navigate('#LastViewed', {trigger: true});
            this.close();
        },

        loadList: function () {
            var viewName = this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.listLastViewed') ||
                           'views/record/list';

            this.listenToOnce(this.collection, 'sync', function () {
                this.createView('list', viewName, {
                    collection: this.collection,
                    el: this.containerSelector + ' .list-container',
                    selectable: false,
                    checkboxes: false,
                    massActionsDisabled: true,
                    rowActionsView: false,
                    type: 'listLastViewed',
                    searchManager: this.searchManager,
                    checkAllResultDisabled: true,
                    buttonsDisabled: true,
                    headerDisabled: true
                });
            }, this);
        },
    });
});

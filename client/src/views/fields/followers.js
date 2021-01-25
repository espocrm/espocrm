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

Espo.define('views/fields/followers', 'views/fields/link-multiple', function (Dep) {

    return Dep.extend({

        foreignScope: 'User',

        portionSize: 4,

        setup: function () {
            Dep.prototype.setup.call(this);

            this.limit = this.portionSize;

            this.listenTo(this.model, 'change:isFollowed', function () {
                if (this.model.get('isFollowed')) {
                    var idList = this.model.get(this.idsName) || [];
                    if (!~idList.indexOf(this.getUser().id)) {
                        idList.unshift(this.getUser().id);
                        var nameMap = this.model.get(this.nameHashName) || {};

                        nameMap[this.getUser().id] = this.getUser().get('name');
                        this.model.trigger('change:' + this.idsName);
                        this.render();
                    }
                } else {
                    var idList = this.model.get(this.idsName) || [];
                    var index = idList.indexOf(this.getUser().id);
                    if (~index) {
                        idList.splice(index, 1);
                        this.model.trigger('change:' + this.idsName);
                        this.render();
                    }
                }
            }, this);

            this.events['click [data-action="showMoreFollowers"]'] = function (e) {
                this.showMoreFollowers();
                $(e.currentTarget).remove();
            };
        },

        reloadFollowers: function () {
            this.getCollectionFactory().create('User', function (collection) {
                collection.url = this.model.name + '/' + this.model.id + '/followers';
                collection.offset = 0;
                collection.maxSize = this.limit;

                this.listenToOnce(collection, 'sync', function () {
                    var idList = [];
                    var nameMap = {};
                    collection.forEach(function (user) {
                        idList.push(user.id);
                        nameMap[user.id] = user.get('name');
                    }, this);
                    this.model.set(this.idsName, idList);
                    this.model.set(this.nameHashName, nameMap);
                    this.render();
                }, this);

                collection.fetch();
            }, this);
        },

        showMoreFollowers: function () {
            this.getCollectionFactory().create('User', function (collection) {
                collection.url = this.model.name + '/' + this.model.id + '/followers';
                collection.offset = this.ids.length || 0;
                collection.maxSize = this.portionSize;
                collection.data.select = ['id', 'name'].join(',');
                collection.orderBy = null;
                collection.order = null;

                this.listenToOnce(collection, 'sync', function () {
                    var idList = this.model.get(this.idsName) || [];
                    var nameMap = this.model.get(this.nameHashName) || {};
                    collection.forEach(function (user) {
                        idList.push(user.id);
                        nameMap[user.id] = user.get('name');
                    }, this);

                    this.limit += this.portionSize;

                    this.model.trigger('change:' + this.idsName);
                    this.render();
                }, this);

                collection.fetch();
            }, this);
        },

        getValueForDisplay: function () {
            if (this.mode == 'detail' || this.mode == 'list') {
                var list = [];
                this.ids.forEach(function (id) {
                    list.push(this.getDetailLinkHtml(id));
                }, this);
                var str = null;
                if (list.length) {
                    str = '' + list.join(', ') + '';
                }
                if (list.length >= this.limit) {
                    str += ', <a href="javascript:" data-action="showMoreFollowers">...</a>'
                }
                return str;
            }
        },

    });
});

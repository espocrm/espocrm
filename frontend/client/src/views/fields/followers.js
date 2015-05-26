/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 ************************************************************************/

Espo.define('Views.Fields.Followers', 'Views.Fields.LinkMultiple', function (Dep) {

    return Dep.extend({

        foreignScope: 'User',


        setup: function () {
            Dep.prototype.setup.call(this);

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
        },

        getValueForDisplay: function () {
            if (this.mode == 'detail' || this.mode == 'list') {
                var names = [];
                this.ids.forEach(function (id) {
                    names.push(this.getDetailLinkHtml(id));
                }, this);
                if (names.length) {
                    return '<div>' + names.join(', ') + '</div>';
                }
                return;
            }
        },

    });
});



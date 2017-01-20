/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2017 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('crm:views/contact/fields/accounts', 'views/fields/link-multiple-with-role-active', function (Dep) {

    return Dep.extend({

        roleType: 'varchar',

        events: {
            'click [data-action="switchPrimary"]': function (e) {
                $target = $(e.currentTarget);
                var id = $target.data('id');
                this.togglePrimary(e);
            },
            'click [data-action="markInactive"]': function (e) {
                this.toggleActive(e);
           },
        },

        togglePrimary: function(e) {
            $target = $(e.currentTarget);
            var id = $target.data('id');
            if (id != this.primaryId) {
                this.$el.find('button[data-action="switchPrimary"]').removeClass('active').children().addClass('text-muted');
                $target.addClass('active').children().removeClass('text-muted');
                this.setPrimaryId(id);
            }
        },

        toggleActive: function(e) {
            $target = $(e.currentTarget);
            var id = $target.data('id');
            var wasActive = this.columns[id]['active'];
            var wasPrimary = (id == this.primaryId);
            this.columns[id]['active'] = !wasActive;

            if (wasActive) {
                $target.removeClass('active').children().removeClass('text-muted');
                // If primary & other active exist, set first of those primary
                if (wasPrimary) {
                    var otherActive = _.filter(this.columns, function (val, key) {
                        return val.active;
                    });

                    if (otherActive.length) {
                        let $newPrimary = this.$el.find('button[data-action="switchPrimary"]:first');
                        this.$el.find('button[data-action="switchPrimary"]:not(.active):first').click();
                    }
                }

            } else {
                $target.addClass('active').children().addClass('text-muted');
                // If NOT primary & primary exists and is inactive, set this to primary
                if (!wasPrimary && this.primaryId && !this.columns[this.primaryId].active) {
                    $target.siblings(':button').click();
                }
            }
        },

        getAttributeList: function () {
            var list = Dep.prototype.getAttributeList.call(this);
            list.push('accountId');
            list.push('accountName');
            list.push('title');
            return list;
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.primaryIdFieldName = 'accountId';
            this.primaryNameFieldName = 'accountName';
            this.primaryRoleFieldName = 'title';

            this.primaryId = this.model.get(this.primaryIdFieldName);
            this.primaryName = this.model.get(this.primaryNameFieldName);

            this.listenTo(this.model, 'change:' + this.primaryIdFieldName, function () {
                this.primaryId = this.model.get(this.primaryIdFieldName);
                this.primaryName = this.model.get(this.primaryNameFieldName);
            }.bind(this));
        },

        setPrimaryId: function (id) {
            this.primaryId = id;
            if (id) {
                this.primaryName = this.nameHash[id];
            } else {
                this.primaryName = null;
            }
        },

        renderLinks: function () {

            if (this.primaryId) {
                this.addLinkHtml(this.primaryId, this.primaryName, false);
            }

            this.ids.forEach(function (id) {
                if (id != this.primaryId && this.columns[id] && this.columns[id]['active']) {
                    this.addLinkHtml(id, this.nameHash[id], false);
                }
            }, this);

            this.ids.forEach(function (id) {
                if (id != this.primaryId && this.columns[id] && !this.columns[id]['active']) {
                    this.addLinkHtml(id, this.nameHash[id], false);
                }
            }, this);
        },

        getValueForDisplay: function () {
            if (this.mode == 'detail' || this.mode == 'list') {
                var names = [];
                if (this.primaryId) {
                    names.push(this.getDetailLinkHtml(this.primaryId, this.primaryName));
                }
                this.ids.forEach(function (id) {
                    if (id != this.primaryId && this.columns[id]['active']) {
                        names.push(this.getDetailLinkHtml(id));
                    }
                }, this);
                this.ids.forEach(function (id) {
                    if (id != this.primaryId && !this.columns[id]['active']) {
                        names.push(this.getDetailLinkHtml(id));
                    }
                }, this);
                return names.join('');
            }
        },

        deleteLink: function (id) {
            if (id == this.primaryId) {
                this.setPrimaryId(null);
            }
            Dep.prototype.deleteLink.call(this, id);
        },

        deleteLinkHtml: function (id) {
            Dep.prototype.deleteLinkHtml.call(this, id);
            this.managePrimaryButton();
        },

        addLinkHtml: function (id, name, update) {
            if (this.mode == 'search') {
                return Dep.prototype.addLinkHtml.call(this, id, name);
            }

            var $el = Dep.prototype.addLinkHtml.call(this, id, name);

            var isPrimary = (id == this.primaryId);

            var isActive = typeof this.columns[id]['active'] == 'undefined' || this.columns[id]['active'];
            var iconHtml = '<span class="glyphicon glyphicon-ban-circle ' + (isActive ? 'text-muted' : '') + '"></span>';
            var title = 'Is Inactive';
            var $hasActive = $('<button type="button" class="btn btn-link btn-sm pull-right ' + (isActive ? 'active' : '') + '" title="'+title+'" data-action="markInactive" data-id="'+id+'">'+iconHtml+'</button>');
            $hasActive.insertAfter($el.children().first().children().first());

            iconHtml = '<span class="glyphicon glyphicon-star ' + (!isPrimary ? 'text-muted' : '') + (isPrimary ? 'active' : '') + '"></span>';
            title = this.translate('Primary');

            var $primary = $('<button type="button" class="btn btn-link btn-sm pull-right hiddent ' + (isPrimary ? 'active' : '') + '" title="'+title+'" data-action="switchPrimary" data-id="'+id+'">'+iconHtml+'</button>');

            $primary.insertAfter($hasActive);
            this.managePrimaryButton();

            if (!isPrimary && (typeof update !== 'boolean' || update)) {
                var activeCols = _.filter(this.columns, function (val, key) {
                    return val.active;
                });

                if (!activeCols.length) {
                    this.$el.find('button[data-action="switchPrimary"]').removeClass('active').children().addClass('text-muted');
                    $primary.addClass('active').children().removeClass('text-muted');
                    this.setPrimaryId(id, false);
                }
            }
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
        },

        managePrimaryButton: function () {
            var $primary = this.$el.find('button[data-action="switchPrimary"]');
            if ($primary.size() > 1) {
                $primary.removeClass('hidden');
            } else {
                $primary.addClass('hidden');
            }
        },

        fetch: function () {
            var data = Dep.prototype.fetch.call(this);

            data[this.primaryIdFieldName] = this.primaryId;
            data[this.primaryNameFieldName] = this.primaryName;
            data[this.primaryRoleFieldName] = (this.columns[this.primaryId] || {}).role || null;

            return data;
        },

    });

});

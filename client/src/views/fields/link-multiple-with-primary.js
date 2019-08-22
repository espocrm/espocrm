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

Espo.define('views/fields/link-multiple-with-primary', 'views/fields/link-multiple', function (Dep) {

    return Dep.extend({

        primaryLink: null,

        events: {
            'click [data-action="switchPrimary"]': function (e) {
                $target = $(e.currentTarget);
                var id = $target.data('id');

                if (!$target.hasClass('active')) {
                    this.$el.find('button[data-action="switchPrimary"]').removeClass('active').children().addClass('text-muted');
                    $target.addClass('active').children().removeClass('text-muted');
                    this.setPrimaryId(id);
                }
            }
        },

        getAttributeList: function () {
            var list = Dep.prototype.getAttributeList.call(this);
            list.push(this.primaryIdFieldName);
            list.push(this.primaryNameFieldName);
            return list;
        },

        setup: function () {
            this.primaryLink = this.options.primaryLink || this.primaryLink;

            this.primaryIdFieldName = this.primaryLink + 'Id';
            this.primaryNameFieldName = this.primaryLink + 'Name';

            Dep.prototype.setup.call(this);


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

            this.trigger('change');
        },

        renderLinks: function () {
            if (this.primaryId) {
                this.addLinkHtml(this.primaryId, this.primaryName);
            }
            this.ids.forEach(function (id) {
                if (id != this.primaryId) {
                    this.addLinkHtml(id, this.nameHash[id]);
                }
            }, this);
        },

        getValueForDisplay: function () {
            if (this.mode == 'detail' || this.mode == 'list') {
                var names = [];
                if (this.primaryId) {
                    names.push(this.getDetailLinkHtml(this.primaryId, this.primaryName));
                }
                if (!this.ids.length) {
                    return;
                }
                this.ids.forEach(function (id) {
                    if (id != this.primaryId) {
                        names.push(this.getDetailLinkHtml(id));
                    }
                }, this);
                return '<div>' + names.join('</div><div>') + '</div>';
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

        addLinkHtml: function (id, name) {
            name = name || id;

            if (this.mode == 'search') {
                return Dep.prototype.addLinkHtml.call(this, id, name);
            }

            var $container = this.$el.find('.link-container');
            var $el = $('<div class="form-inline list-group-item link-with-role clearfix link-group-item-with-primary">').addClass('link-' + id).attr('data-id', id);

            var nameHtml = '<div>' + this.getHelper().escapeString(name) || id + '&nbsp;' + '</div>';
            var removeHtml = '<a href="javascript:" class="pull-right" data-id="' + id + '" data-action="clearLink"><span class="fas fa-times"></a>';

            $left = $('<div>');
            $left.append(nameHtml);
            $el.append($left);

            $right = $('<div>');
            $right.append(removeHtml);
            $el.append($right);

            var isPrimary = (id == this.primaryId);
            var iconHtml = '<span class="fas fa-star fa-sm ' + (!isPrimary ? 'text-muted' : '') + '"></span>';
            var title = this.translate('Primary');
            var $primary = $('<button type="button" class="btn btn-link btn-sm pull-right hidden" title="'+title+'" data-action="switchPrimary" data-id="'+id+'">'+iconHtml+'</button>');
            $primary.insertBefore($el.children().first().children().first());

            $container.append($el);

            this.managePrimaryButton();

            return $el;
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
        },

        managePrimaryButton: function () {
            var $primary = this.$el.find('button[data-action="switchPrimary"]');
            if ($primary.length > 1) {
                $primary.removeClass('hidden');
            } else {
                $primary.addClass('hidden');
            }

            if ($primary.filter('.active').length == 0) {
                var $first = $primary.first();
                if ($first.length) {
                    $first.addClass('active').children().removeClass('text-muted');
                    this.setPrimaryId($first.data('id'));
                }
            }
        },

        fetch: function () {
            var data = Dep.prototype.fetch.call(this);

            data[this.primaryIdFieldName] = this.primaryId;
            data[this.primaryNameFieldName] = this.primaryName;

            return data;
        },

    });
});



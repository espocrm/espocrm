/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/fields/link-multiple-with-primary', ['views/fields/link-multiple'], function (Dep) {

    /**
     * A link-multiple field with a primary.
     *
     * @class
     * @name Class
     * @extends module:views/fields/link-multiple.Class
     * @memberOf module:views/fields/link-multiple-with-primary
     */
    return Dep.extend(/** @lends module:views/fields/link-multiple-with-primary.Class# */{

        primaryLink: null,

        events: {
            'click [data-action="switchPrimary"]': function (e) {
                let $target = $(e.currentTarget);

                var id = $target.data('id');

                if (!$target.hasClass('active')) {
                    this.$el.find('button[data-action="switchPrimary"]')
                        .removeClass('active')
                        .children()
                        .addClass('text-muted');

                    $target.addClass('active').children().removeClass('text-muted');

                    this.setPrimaryId(id);
                }
            },
        },

        /**
         * @inheritDoc
         */
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

            this.listenTo(this.model, 'change:' + this.primaryIdFieldName, () => {
                this.primaryId = this.model.get(this.primaryIdFieldName);
                this.primaryName = this.model.get(this.primaryNameFieldName);
            });
        },

        setPrimaryId: function (id) {
            this.primaryId = id;

            if (id) {
                this.primaryName = this.nameHash[id];
            }
            else {
                this.primaryName = null;
            }

            this.trigger('change');
        },

        renderLinks: function () {
            if (this.primaryId) {
                this.addLinkHtml(this.primaryId, this.primaryName);
            }

            this.ids.forEach((id) => {
                if (id !== this.primaryId) {
                    this.addLinkHtml(id, this.nameHash[id]);
                }
            });
        },

        getValueForDisplay: function () {
            if (this.isDetailMode() || this.isListMode()) {
                let itemList = [];

                if (this.primaryId) {
                    itemList.push(this.getDetailLinkHtml(this.primaryId, this.primaryName));
                }

                if (!this.ids.length) {
                    return;
                }

                this.ids.forEach(id => {
                    if (id !== this.primaryId) {
                        itemList.push(this.getDetailLinkHtml(id));
                    }
                });

                return itemList
                    .map(item => $('<div>').append(item).get(0).outerHTML)
                    .join('');
            }
        },

        deleteLink: function (id) {
            if (id === this.primaryId) {
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

            id = Handlebars.Utils.escapeExpression(id);
            name = Handlebars.Utils.escapeExpression(name);

            if (this.mode === 'search') {
                return Dep.prototype.addLinkHtml.call(this, id, name);
            }

            let $container = this.$el.find('.link-container');

            let $el = $('<div>')
                .addClass('form-inline clearfix ')
                .addClass('list-group-item link-with-role link-group-item-with-primary')
                .addClass('link-' + id)
                .attr('data-id', id);

            let $name = $('<div>').text(name).append('&nbsp;');

            let $remove = $('<a>')
                .attr('href', 'javascript:')
                .attr('data-id', id)
                .attr('data-action', 'clearLink')
                .addClass('pull-right')
                .append(
                    $('<span>').addClass('fas fa-times')
                );

            let $left = $('<div>');
            let $right = $('<div>');

            $left.append($name);
            $right.append($remove);

            $el.append($left);
            $el.append($right);

            let isPrimary = (id === this.primaryId);

            let $star = $('<span>')
                .addClass('fas fa-star fa-sm')
                .addClass(!isPrimary ? 'text-muted' : '')

            let $button = $('<button>')
                .attr('type', 'button')
                .addClass('btn btn-link btn-sm pull-right hidden')
                .attr('title', this.translate('Primary'))
                .attr('data-action', 'switchPrimary')
                .attr('data-id', id)
                .html($star);

            $button.insertBefore($el.children().first().children().first());

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
            }
            else {
                $primary.addClass('hidden');
            }

            if ($primary.filter('.active').length === 0) {
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



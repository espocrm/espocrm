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

define('views/settings/fields/tab-list', ['views/fields/array'], function (Dep) {

    return Dep.extend({

        addItemModalView: 'views/settings/modals/tab-list-field-add',

        noGroups: false,

        noDelimiters: false,

        setup: function () {
            Dep.prototype.setup.call(this);

            this.selected.forEach(item => {
                if (item && typeof item === 'object') {
                    if (!item.id) {
                        item.id = this.generateItemId();
                    }
                }
            });

            this.events['click [data-action="editGroup"]'] = (e) => {
                var id = $(e.currentTarget).parent().data('value').toString();

                this.editGroup(id);
            };
        },

        generateItemId: function () {
            return Math.floor(Math.random() * 1000000 + 1).toString();
        },

        setupOptions: function () {
            this.params.options = Object.keys(this.getMetadata().get('scopes'))
                .filter(scope => {
                    if (this.getMetadata().get('scopes.' + scope + '.disabled')) {
                        return false;
                    }

                    if (!this.getAcl().checkScope(scope)) {
                        return false;
                    }

                    return this.getMetadata().get('scopes.' + scope + '.tab');
                })
                .sort((v1, v2) => {
                    return this.translate(v1, 'scopeNamesPlural')
                        .localeCompare(this.translate(v2, 'scopeNamesPlural'));
                });

            if (!this.noDelimiters) {
                this.params.options.push('_delimiter_');
                this.params.options.push('_delimiter-ext_');
            }

            this.translatedOptions = {};

            this.params.options.forEach(item => {
                this.translatedOptions[item] = this.translate(item, 'scopeNamesPlural');
            });

            this.translatedOptions['_delimiter_'] = '. . .';
            this.translatedOptions['_delimiter-ext_'] = '. . .';
        },

        addValue: function (value) {
            if (value && typeof value === 'object') {
                if (!value.id) {
                    value.id = this.generateItemId();
                }

                var html = this.getItemHtml(value);

                this.$list.append(html);

                this.selected.push(value);

                this.trigger('change');

                return;
            }

            Dep.prototype.addValue.call(this, value);
        },

        removeValue: function (value) {
            var index = this.getGroupIndexById(value);

            if (~index) {
                this.$list.children('[data-value="' + value + '"]').remove();

                this.selected.splice(index, 1);
                this.trigger('change');

                return;
            }

            Dep.prototype.removeValue.call(this, value);
        },

        getItemHtml: function (value) {
            if (value && typeof value === 'object') {
                return this.getGroupItemHtml(value);
            }

            return Dep.prototype.getItemHtml.call(this, value);
        },

        getGroupItemHtml: function (item) {
            let label = item.text || '';

            return $('<div>')
                .addClass('list-group-item')
                .attr('data-value', item.id)
                .css('cursor', 'default')
                .append(
                    $('<a>')
                        .attr('role', 'button')
                        .attr('tabindex', '0')
                        .attr('data-value', item.id)
                        .attr('data-action', 'editGroup')
                        .css('margin-right', '7px')
                        .append(
                            $('<span>').addClass('fas fa-pencil-alt fa-sm')
                        ),
                    $('<span>').text(label),
                    '&nbsp;',
                    $('<a>')
                        .addClass('pull-right')
                        .attr('role', 'button')
                        .attr('tabindex', '0')
                        .attr('data-value', item.id)
                        .attr('data-action', 'removeValue')
                        .append(
                            $('<span>').addClass('fas fa-times')
                        )
                )
                .get(0).outerHTML;
        },

        fetchFromDom: function () {
            var selected = [];

            this.$el.find('.list-group .list-group-item').each((i, el) => {
                var value = $(el).data('value').toString();
                var groupItem = this.getGroupValueById(value);

                if (groupItem) {
                    selected.push(groupItem);

                    return;
                }

                selected.push(value);
            });

            this.selected = selected;
        },

        getGroupIndexById: function (id) {
            for (var i = 0; i < this.selected.length; i++) {
                var item = this.selected[i];

                if (item && typeof item === 'object') {
                    if (item.id === id) {
                        return i;
                    }
                }
            }

            return -1;
        },

        getGroupValueById: function (id) {
            for (let item of this.selected) {
                if (item && typeof item === 'object') {
                    if (item.id === id) {
                        return item;
                    }
                }
            }

            return null;
        },

        editGroup: function (id) {
            var item = Espo.Utils.cloneDeep(this.getGroupValueById(id) || {});

            var index = this.getGroupIndexById(id);
            var tabList = Espo.Utils.cloneDeep(this.selected);

            this.createView('dialog', 'views/settings/modals/edit-tab-group', {
                itemData: item,
            }, (view) => {
                view.render();

                this.listenToOnce(view, 'apply', (itemData) => {
                    for (var a in itemData) {
                        tabList[index][a] = itemData[a];
                    }

                    this.model.set(this.name, tabList);

                    view.close();
                });
            });
        },

        getAddItemModalOptions: function () {
            return _.extend(
                Dep.prototype.getAddItemModalOptions.call(this),
                {
                    noGroups: this.noGroups,
                }
            );
        },
    });
});

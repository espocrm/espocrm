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

define('views/modals/mass-update', ['views/modal', 'helpers/mass-action'], function (Dep, MassActionHelper) {

    return Dep.extend({

        cssName: 'mass-update',

        className: 'dialog dialog-record',

        template: 'modals/mass-update',

        layoutName: 'massUpdate',

        ACTION_UPDATE: 'update',

        ACTION_ADD: 'add',

        ACTION_REMOVE: 'remove',

        data: function () {
            return {
                scope: this.scope,
                fieldList: this.fieldList,
                entityType: this.entityType,
            };
        },

        events: {
            'click button[data-action="update"]': function () {
                this.update();
            },
            'click a[data-action="add-field"]': function (e) {
                var field = $(e.currentTarget).data('name');
                this.addField(field);
            },
            'click button[data-action="reset"]': function (e) {
                this.reset();
            }
        },

        setup: function () {
            this.buttonList = [
                {
                    name: 'update',
                    label: 'Update',
                    style: 'danger',
                    disabled: true
                },
                {
                    name: 'cancel',
                    label: 'Cancel'
                }
            ];

            this.entityType = this.options.entityType || this.options.scope;
            this.scope = this.options.scope || this.entityType;

            this.ids = this.options.ids;
            this.where = this.options.where;
            this.searchParams = this.options.searchParams;
            this.byWhere = this.options.byWhere;

            this.hasActionMap = {};

            let totalCount = this.options.totalCount;

            this.helper = new MassActionHelper(this);

            this.idle = this.byWhere && this.helper.checkIsIdle(totalCount);

            this.headerHtml = this.translate(this.scope, 'scopeNamesPlural') +
                ' <span class="chevron-right"></span> ' + this.translate('Mass Update');

            var forbiddenList = this.getAcl().getScopeForbiddenFieldList(this.entityType, 'edit') || [];

            this.wait(true);

            this.getModelFactory().create(this.entityType, (model) => {
                this.model = model;

                this.getHelper().layoutManager.get(this.entityType, this.layoutName, (layout) => {
                    layout = layout || [];

                    this.fieldList = [];

                    layout.forEach((field) => {
                        if (~forbiddenList.indexOf(field)) {
                            return;
                        }

                        if (model.hasField(field)) {
                            this.fieldList.push(field);
                        }
                    });

                    this.wait(false);
                });
            });

            this.addedFieldList = [];
        },

        addField: function (name) {
            this.$el.find('[data-action="reset"]').removeClass('hidden');

            this.$el.find('ul.filter-list li[data-name="'+name+'"]').addClass('hidden');

            if (this.$el.find('ul.filter-list li:not(.hidden)').length === 0) {
                this.$el.find('button.select-field').addClass('disabled').attr('disabled', 'disabled');
            }

            this.addedFieldList.push(name);

            let label = this.getHelper().escapeString(
                this.translate(name, 'fields', this.entityType)
            );

            let $cell =
                $('<div>')
                    .addClass('cell form-group')
                    .attr('data-name', name)
                    .append(
                        $('<label>')
                            .addClass('control-label')
                            .text(label)
                    )
                    .append(
                        $('<div>')
                            .addClass('field')
                            .attr('data-name', name)
                    );

            let $row =
                $('<div>')
                    .addClass('item grid-auto-fill-md')
                    .attr('data-name', name)
                    .append($cell);

            this.$el.find('.fields-container').append($row);

            let type = this.model.getFieldType(name);
            let viewName = this.model.getFieldParam(name, 'view') || this.getFieldManager().getViewName(type);

            let actionList = this.getMetadata().get(['entityDefs', this.entityType, name, 'massUpdateActionList']) ||
                this.getMetadata().get(['fields', type, 'massUpdateActionList']);

            let hasActionDropdown = actionList !== null;

            this.hasActionMap[name] = hasActionDropdown;

            this.disableButton('update');

            this.createView(name, viewName, {
                model: this.model,
                el: this.getSelector() + ' .field[data-name="' + name + '"]',
                defs: {
                    name: name,
                },
                mode: 'edit',
            }, view => {
                this.enableButton('update');

                view.render();
            });

            if (hasActionDropdown) {
                let $select =
                    $('<select>')
                        .addClass('item-action form-control')
                        .attr('data-name', name);

                actionList.forEach(action => {
                    let label = this.translate(Espo.Utils.upperCaseFirst(action));

                    $select.append(
                        $('<option>')
                            .text(label)
                            .val(action)
                    );
                });

                let $cellAction =
                    $('<div>')
                        .addClass('cell call-action form-group')
                        .attr('data-name', name)
                        .append(
                            $('<label>')
                                .addClass('control-label hidden-xs')
                                .html('&nbsp;')
                        )
                        .append(
                            $('<div>')
                                .addClass('field')
                                .attr('data-name', name)
                                .append($select)
                        );

                $row.append($cellAction);
            }
        },

        actionUpdate: function () {
            this.disableButton('update');

            let attributes = {};
            let actions = {};

            this.addedFieldList.forEach(field => {
                let action = this.fetchAction(field);
                let itemAttributes = this.getView(field).fetch();

                let itemActualAttributes = {};

                this.getFieldManager()
                    .getEntityTypeFieldActualAttributeList(this.entityType, field)
                    .forEach(attribute => {
                        actions[attribute] = action;

                        itemActualAttributes[attribute] = itemAttributes[attribute];
                    });

                _.extend(attributes, itemActualAttributes);
            });

            this.model.set(attributes);

            let notValid = false;

            this.addedFieldList.forEach(field => {
                let view = this.getView(field);

                notValid = view.validate() || notValid;
            });

            if (notValid) {
                this.notify('Not valid', 'error');
                this.enableButton('update');

                return;
            }

            Espo.Ui.notify(this.translate('Saving...'));

            Espo.Ajax
                .postRequest('MassAction', {
                    action: 'update',
                    entityType: this.entityType,
                    params: {
                        ids: this.ids || null,
                        where: (!this.ids || this.ids.length === 0) ? this.options.where : null,
                        searchParams: (!this.ids || this.ids.length === 0) ? this.options.searchParams : null,
                    },
                    data: {
                        values: attributes,
                        actions: actions,
                    },
                    idle: this.idle,
                })
                .then(result => {
                    result = result || {};

                    if (result.id) {
                        this.helper
                            .process(result.id, 'update')
                            .then(view => {
                                this.listenToOnce(view, 'close', () => this.close());

                                this.listenToOnce(view, 'success', result => {
                                    this.trigger('after:update', {
                                        count: result.count,
                                        idle: true,
                                    });
                                });
                            });

                        return;
                    }

                    this.trigger('after:update', {
                        count: result.count,
                    });
                })
                .catch(() => {
                    this.enableButton('update');
                });
        },

        fetchAction: function (name) {
            if (!this.hasActionMap[name]) {
                return this.ACTION_UPDATE;
            }

            let $dropdown = this.$el.find('select.item-action[data-name="'+name+'"]');

            return $dropdown.val() || this.ACTION_UPDATE;
        },

        reset: function () {
            this.addedFieldList.forEach(field => {
                this.clearView(field);

                this.$el.find('.item[data-name="'+field+'"]').remove();
            });

            this.addedFieldList = [];
            this.hasActionMap = {};

            this.model.clear();

            this.$el.find('[data-action="reset"]').addClass('hidden');
            this.$el.find('button.select-field').removeClass('disabled').removeAttr('disabled');
            this.$el.find('ul.filter-list').find('li').removeClass('hidden');

            this.disableButton('update');
        },
    });
});

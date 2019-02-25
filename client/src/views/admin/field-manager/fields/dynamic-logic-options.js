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

Espo.define('views/admin/field-manager/fields/dynamic-logic-options', ['views/fields/base', 'model'], function (Dep, Model) {

    return Dep.extend({

        editTemplate: 'admin/field-manager/fields/dynamic-logic-options/edit',

        events: {
            'click [data-action="editConditions"]': function (e) {
                var index = parseInt($(e.currentTarget).data('index'));
                this.edit(index);
            },
            'click [data-action="addOptionList"]': function (e) {
                this.addOptionList();
            },
            'click [data-action="removeOptionList"]': function (e) {
                var index = parseInt($(e.currentTarget).data('index'));
                this.removeItem(index);
            }
        },

        data: function () {
            return {
                itemDataList: this.itemDataList
            };
        },

        setup: function () {
            this.optionsDefsList = Espo.Utils.cloneDeep(this.model.get(this.name)) || []
            this.scope = this.options.scope;

            this.setupItems();
            this.setupItemViews();
        },

        setupItems: function () {
            this.itemDataList = [];
            this.optionsDefsList.forEach(function (item, i) {
                this.itemDataList.push({
                    conditionGroupViewKey: 'conditionGroup' + i.toString(),
                    optionsViewKey: 'options' + i.toString(),
                    index: i
                });
            }, this);
        },

        setupItemViews: function () {
            this.optionsDefsList.forEach(function (item, i) {
                this.createStringView(i);
                this.createOptionsView(i);
            }, this);
        },

        createOptionsView: function (num) {
            var key = 'options' + num.toString();
            if (!this.optionsDefsList[num]) return;

            var model = new Model();
            model.set('options', this.optionsDefsList[num].optionList || []);

            this.createView(key, 'views/fields/multi-enum', {
                el: this.getSelector() + ' .options-container[data-key="'+key+'"]',
                model: model,
                name: 'options',
                mode: 'edit',
                params: {
                    options: this.model.get('options'),
                    translatedOptions: this.model.get('translatedOptions')
                }
            }, function (view) {
                if (this.isRendered()) {
                    view.render();
                }

                this.listenTo(this.model, 'change:options', function () {
                    view.setTranslatedOptions(this.getTranslatedOptions());
                    view.setOptionList(this.model.get('options'));
                }, this);

                this.listenTo(model, 'change', function () {
                    this.optionsDefsList[num].optionList = model.get('options') || [];
                }, this);
            }, this);
        },

        getTranslatedOptions: function () {
            if (this.model.get('translatedOptions')) {
                return this.model.get('translatedOptions');
            }
            var translatedOptions = {};
            var list = this.model.get('options') || [];
            list.forEach(function (value) {
                translatedOptions[value] = this.getLanguage().translateOption(value, this.options.field, this.options.scope);
            }, this);

            return translatedOptions;
        },

        createStringView: function (num) {
            var key = 'conditionGroup' + num.toString();
            if (!this.optionsDefsList[num]) return;

            this.createView(key, 'views/admin/dynamic-logic/conditions-string/group-base', {
                el: this.getSelector() + ' .string-container[data-key="'+key+'"]',
                itemData: {
                    value: this.optionsDefsList[num].conditionGroup
                },
                operator: 'and',
                scope: this.scope
            }, function (view) {
                if (this.isRendered()) {
                    view.render();
                }
            }, this);
        },

        edit: function (num) {
            this.createView('modal', 'views/admin/dynamic-logic/modals/edit', {
                conditionGroup: this.optionsDefsList[num].conditionGroup,
                scope: this.options.scope
            }, function (view) {
                view.render();

                this.listenTo(view, 'apply', function (conditionGroup) {
                    this.optionsDefsList[num].conditionGroup = conditionGroup;
                    this.createStringView(num);
                }, this);
            }, this);
        },

        addOptionList: function () {
            var i = this.itemDataList.length;

            this.optionsDefsList.push({
                optionList: this.model.get('options') || [],
                conditionGroup: null
            });

            this.setupItems();
            this.reRender();
            this.setupItemViews();
        },

        removeItem: function (num) {
            this.optionsDefsList.splice(num, 1);

            this.setupItems();
            this.reRender();
            this.setupItemViews();
        },

        fetch: function () {
            var data = {};

            data[this.name] = this.optionsDefsList;

            if (!this.optionsDefsList.length) {
                data[this.name] = null;
            }

            return data;
        }
    });

});

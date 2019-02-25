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

Espo.define('views/admin/label-manager/edit', 'view', function (Dep) {

    return Dep.extend({

        template: 'admin/label-manager/edit',

        data: function () {
            return {
                categoryList: this.getCategoryList(),
                scope: this.scope
            };
        },

        events: {
            'click [data-action="showCategory"]': function (e) {
                var name = $(e.currentTarget).data('name');
                this.showCategory(name);
            },
            'click [data-action="hideCategory"]': function (e) {
                var name = $(e.currentTarget).data('name');
                this.hideCategory(name);
            },
            'click [data-action="cancel"]': function (e) {
                this.actionCancel();
            },
            'click [data-action="save"]': function (e) {
                this.actionSave();
            },
            'change input.label-value': function (e) {
                var name = $(e.currentTarget).data('name');
                var value = $(e.currentTarget).val();
                this.setLabelValue(name, value);
            }
        },

        setup: function () {
            this.scope = this.options.scope;
            this.language = this.options.language;

            this.dirtyLabelList = [];

            this.wait(true);

            this.ajaxPostRequest('LabelManager/action/getScopeData', {
                scope: this.scope,
                language: this.language
            }).then(function (data) {
                this.scopeData = data;

                this.scopeDataInitial = Espo.Utils.cloneDeep(this.scopeData);
                this.wait(false);
            }.bind(this));
        },

        getCategoryList: function () {
            var categoryList = Object.keys(this.scopeData).sort(function (v1, v2) {
                return v1.localeCompare(v2);
            }.bind(this));

            return categoryList;
        },

        setLabelValue: function (name, value) {
            var category = name.split('[.]')[0];

            value = value.replace(/\\\\n/i, '\n');

            value = value.trim();

            this.scopeData[category][name] = value;

            this.dirtyLabelList.push(name);
            this.setConfirmLeaveOut(true);

            if (!this.hasView(category)) return;

            this.getView(category).categoryData[name] = value;
        },

        setConfirmLeaveOut: function (value) {
            this.getRouter().confirmLeaveOut = value;
        },

        afterRender: function () {
            this.$save = this.$el.find('button[data-action="save"]');
            this.$cancel = this.$el.find('button[data-action="cancel"]');
        },

        actionSave: function () {
            this.$save.addClass('disabled').attr('disabled');
            this.$cancel.addClass('disabled').attr('disabled');

            var data = {};

            this.dirtyLabelList.forEach(function (name) {
                var category = name.split('[.]')[0];
                var value = this.scopeData[category][name];
                data[name] = value;
            }, this);

            Espo.Ui.notify(this.translate('saving', 'messages'));
            this.ajaxPostRequest('LabelManager/action/saveLabels', {
                scope: this.scope,
                language: this.language,
                labels: data
            }).then(function (returnData) {
                this.scopeDataInitial = Espo.Utils.cloneDeep(this.scopeData);
                this.dirtyLabelList = [];
                this.setConfirmLeaveOut(false);

                this.$save.removeClass('disabled').removeAttr('disabled');
                this.$cancel.removeClass('disabled').removeAttr('disabled');

                for (var key in returnData) {
                    var name = key.split('[.]').splice(1).join('[.]');
                    this.$el.find('input.label-value[data-name="'+name+'"]').val(returnData[key]);
                }

                Espo.Ui.success(this.translate('Saved'));
            }.bind(this)).fail(function () {
                this.$save.removeClass('disabled').removeAttr('disabled');
                this.$cancel.removeClass('disabled').removeAttr('disabled');
            }.bind(this));
        },

        actionCancel: function () {
            this.scopeData = Espo.Utils.cloneDeep(this.scopeDataInitial);
            this.dirtyLabelList = [];
            this.setConfirmLeaveOut(false);

            this.getCategoryList().forEach(function (category) {
                if (!this.hasView(category)) return;

                this.getView(category).categoryData = this.scopeData[category];
                this.getView(category).reRender();
            }, this);
        },

        showCategory: function (category) {
            this.$el.find('a[data-action="showCategory"][data-name="'+category+'"]').addClass('hidden');

            if (this.hasView(category)) {
                this.$el.find('a[data-action="hideCategory"][data-name="'+category+'"]').removeClass('hidden');
                this.$el.find('.panel-body[data-name="'+category+'"]').removeClass('hidden');
                return;
            }
            this.createView(category, 'views/admin/label-manager/category', {
                el: this.getSelector() + ' .panel-body[data-name="'+category+'"]',
                categoryData: this.getCategoryData(category),
                scope: this.scope,
                language: this.language
            }, function (view) {
                this.$el.find('.panel-body[data-name="'+category+'"]').removeClass('hidden');
                this.$el.find('a[data-action="hideCategory"][data-name="'+category+'"]').removeClass('hidden');
                view.render();
            }, this);
        },

        hideCategory: function (category) {
            this.clearView(category);

            this.$el.find('.panel-body[data-name="'+category+'"]').addClass('hidden');
            this.$el.find('a[data-action="showCategory"][data-name="'+category+'"]').removeClass('hidden');
            this.$el.find('a[data-action="hideCategory"][data-name="'+category+'"]').addClass('hidden');
        },

        getCategoryData: function (category) {
            return this.scopeData[category] || {};
        }

    });
});



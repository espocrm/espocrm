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

define('views/dashlets/options/base',
['views/modal', 'views/record/detail', 'model', 'view-record-helper'],
function (Dep, Detail, Model, ViewRecordHelper) {

    return Dep.extend({

        name: null,

        template: 'dashlets/options/base',

        cssName: 'options-modal',

        className: 'dialog dialog-record',

        fieldsMode: 'edit',

        data: function () {
            return {
                options: this.optionsData,
            };
        },

        buttonList: [
            {
                name: 'save',
                label: 'Apply',
                style: 'primary',
                title: 'Ctrl+Enter',
            },
            {
                name: 'cancel',
                label: 'Cancel',
                title: 'Esc',
            },
        ],

        shortcutKeys: {
            'Control+Enter': 'save',
        },

        getDetailLayout: function () {
            let layout = this.getMetadata().get(['dashlets', this.name, 'options', 'layout']);

            if (layout) {
                return layout;
            }

            layout = [{rows: []}];

            let i = 0;
            let a = [];

            for (let field in this.fields) {
                if (!(i % 2)) {
                    a = [];

                    layout[0].rows.push(a);
                }

                a.push({name: field});

                i++;
            }

            return layout;
        },

        init: function () {
            Dep.prototype.init.call(this);

            this.fields = Espo.Utils.cloneDeep(this.options.fields);
            this.fieldList = Object.keys(this.fields);
            this.optionsData = this.options.optionsData;
        },

        setup: function () {
            this.id = 'dashlet-options';

            this.recordHelper = new ViewRecordHelper();

            let model = this.model = new Model();

            model.name = 'DashletOptions';
            model.defs = {
                fields: this.fields
            };

            model.set(this.optionsData);

            model.dashletName = this.name;

            this.middlePanelDefs = {};
            this.middlePanelDefsList = [];

            this.setupBeforeFinal();

            this.createView('record', 'views/record/detail-middle', {
                model: model,
                recordHelper: this.recordHelper,
                _layout: {
                    type: 'record',
                    layout: Detail.prototype.convertDetailLayout.call(this, this.getDetailLayout())
                },
                el: this.options.el + ' .record',
                layoutData: {
                    model: model,
                },
            });

            this.header =
                this.getLanguage().translate('Dashlet Options') + ': ' +
                Handlebars.Utils.escapeExpression(this.getLanguage().translate(this.name, 'dashlets'));
        },

        setupBeforeFinal: function () {},

        fetchAttributes: function () {
            let attributes = {};

            this.fieldList.forEach(field => {
                let fieldView = this.getView('record').getFieldView(field);

                _.extend(attributes, fieldView.fetch());
            });

            this.model.set(attributes, {silent: true});

            let valid = true;

            this.fieldList.forEach(field => {
                let fieldView = this.getView('record').getFieldView(field);

                if (fieldView && fieldView.isEditMode() && !fieldView.disabled && !fieldView.readOnly) {
                    valid = !fieldView.validate() && valid;
                }
            });

            if (!valid) {
                this.notify('Not Valid', 'error');

                return null;
            }

            return attributes;
        },

        actionSave: function () {
            var attributes = this.fetchAttributes();

            if (attributes == null) {
                return;
            }

            this.trigger('save', attributes);
        },

        getFieldViews: function (withHidden) {
            if (this.hasView('record')) {
                return this.getView('record').getFieldViews(withHidden) || {};
            }

            return {};
        },

        getFieldView: function (name) {
            return (this.getFieldViews(true) || {})[name] || null;
        },

        hideField: function (name, locked) {
            this.recordHelper.setFieldStateParam(name, 'hidden', true);

            if (locked) {
                this.recordHelper.setFieldStateParam(name, 'hiddenLocked', true);
            }

            var processHtml = () => {
                let fieldView = this.getFieldView(name);

                if (fieldView) {
                    let $field = fieldView.$el;
                    let $cell = $field.closest('.cell[data-name="' + name + '"]');
                    let $label = $cell.find('label.control-label[data-name="' + name + '"]');

                    $field.addClass('hidden');
                    $label.addClass('hidden');
                    $cell.addClass('hidden-cell');

                    return;
                }

                this.$el.find('.cell[data-name="' + name + '"]').addClass('hidden-cell');
                this.$el.find('.field[data-name="' + name + '"]').addClass('hidden');
            };

            if (this.isRendered()) {
                processHtml();
            } else {
                this.once('after:render', () => processHtml());
            }

            let view = this.getFieldView(name);

            if (view) {
                view.setDisabled(locked);
            }
        },

        showField: function (name) {
            if (this.recordHelper.getFieldStateParam(name, 'hiddenLocked')) {
                return;
            }

            this.recordHelper.setFieldStateParam(name, 'hidden', false);

            let processHtml = () => {
                let fieldView = this.getFieldView(name);

                if (fieldView) {
                    let $field = fieldView.$el;
                    let $cell = $field.closest('.cell[data-name="' + name + '"]');
                    let $label = $cell.find('label.control-label[data-name="' + name + '"]');

                    $field.removeClass('hidden');
                    $label.removeClass('hidden');
                    $cell.removeClass('hidden-cell');

                    return;
                }

                this.$el.find('.cell[data-name="' + name + '"]').removeClass('hidden-cell');
                this.$el.find('.field[data-name="' + name + '"]').removeClass('hidden');
                this.$el.find('label.control-label[data-name="' + name + '"]').removeClass('hidden');
            };

            if (this.isRendered()) {
                processHtml();
            } else {
                this.once('after:render', () => processHtml());
            }

            let view = this.getFieldView(name);

            if (view) {
                if (!view.disabledLocked) {
                    view.setNotDisabled();
                }
            }
        },
    });
});


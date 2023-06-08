/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/dashlets/options/base', ['views/modal', 'model'], function (Dep, Model) {

    /**
     * @class
     * @name Class
     * @extends module:views/modal
     * @memberOf module:views/dashlets/options/base
     */
    return Dep.extend(/** @lends module:views/dashlets/options/base.Class# */{

        name: null,
        template: 'dashlets/options/base',
        cssName: 'options-modal',
        className: 'dialog dialog-record',
        fieldsMode: 'edit',
        escapeDisabled: true,

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
            'Escape': function (e) {
                if (this.saveDisabled) {
                    return;
                }

                e.stopPropagation();
                e.preventDefault();

                let focusedFieldView = this.getRecordView().getFocusedFieldView();

                if (focusedFieldView) {
                    this.model.set(focusedFieldView.fetch(), {skipReRender: true});
                }

                if (this.getRecordView().isChanged) {
                    this.confirm(this.translate('confirmLeaveOutMessage', 'messages'))
                        .then(() => this.actionClose());

                    return;
                }

                this.actionClose();
            },
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

            /** @var {module:model} */
            let model = this.model = new Model();

            model.name = 'DashletOptions';
            model.setDefs({fields: this.fields});
            model.set(this.optionsData);

            model.dashletName = this.name;
            model.userId = this.options.userId;

            this.middlePanelDefs = {};
            this.middlePanelDefsList = [];

            this.setupBeforeFinal();

            this.createView('record', 'views/record/edit-for-modal', {
                model: model,
                detailLayout: this.getDetailLayout(),
                selector: '.record',
            });

            this.$header =
                $('<span>')
                    .append(
                        $('<span>').text(this.getLanguage().translate('Dashlet Options')),
                        ' &middot; ',
                        $('<span>').text(this.getLanguage().translate(this.name, 'dashlets')),
                    );
        },

        setupBeforeFinal: function () {},

        onBackdropClick: function () {
            if (this.getRecordView().isChanged) {
                return;
            }

            this.close();
        },

        /**
         * @return {module:views/record/edit}
         */
        getRecordView: function () {
            return this.getView('record');
        },

        /**
         * @return {Object|null}
         */
        fetchAttributes: function () {
            let attributes = this.getRecordView().fetch();

            if (this.getRecordView().validate()) {
                return null;
            }

            return attributes;
        },

        actionSave: function () {
            let attributes = this.fetchAttributes();

            if (attributes == null) {
                return;
            }

            this.trigger('save', attributes);
        },

        getFieldViews: function (withHidden) {
            if (!this.hasView('record')) {
                return {};
            }

            return this.getRecordView().getFieldViews(withHidden);
        },

        getFieldView: function (name) {
            return (this.getFieldViews(true) || {})[name] || null;
        },

        hideField: function (name, locked) {
            if (!this.getRecordView()) {
                this.whenRendered().then(() => this.hideField(name), locked);

                return;
            }

            this.getRecordView().hideField(name, locked);
        },

        showField: function (name) {
            if (!this.getRecordView()) {
                this.whenRendered().then(() => this.showField(name));

                return;
            }

            this.getRecordView().showField(name);
        },
    });
});


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

Espo.define('views/admin/entity-manager/modals/edit-formula', ['views/modal', 'model'], function (Dep, Model) {

    return Dep.extend({

        _template: '<div class="record">{{{record}}}</div>',

        data: function () {
            return {
            };
        },

        setup: function () {
            this.buttonList = [
                {
                    name: 'save',
                    label: 'Save',
                    style: 'danger'
                },
                {
                    name: 'cancel',
                    label: 'Cancel'
                }
            ];

            var scope = this.scope = this.options.scope || false;

            this.header = this.translate('Formula', 'labels', 'EntityManager') + ': ' + this.translate(scope, 'scopeNames');

            var model = this.model = new Model();
            model.name = 'EntityManager';

            this.wait(true);
            this.ajaxGetRequest('Metadata/action/get', {
                key: 'formula.' + scope
            }).then(function (formulaData) {
                formulaData = formulaData || {};

                model.set('beforeSaveCustomScript', formulaData.beforeSaveCustomScript || null);

                this.createView('record', 'views/admin/entity-manager/record/edit-formula', {
                    el: this.getSelector() + ' .record',
                    model: model,
                    targetEntityType: this.scope
                });
                this.wait(false);
            }.bind(this));
        },

        actionSave: function () {
            this.disableButton('save');

            var data = this.getView('record').fetch();
            this.model.set(data);
            if (this.getView('record').validate()) return;

            if (data.beforeSaveCustomScript === '') {
                data.beforeSaveCustomScript = null;
            }

            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));
            this.ajaxPostRequest('EntityManager/action/formula', {
                data: data,
                scope: this.scope
            }).then(function () {
                Espo.Ui.success(this.translate('Saved'));
                this.trigger('after:save');
            }.bind(this)).fail(function () {
                this.enableButton('save');
            }.bind(this));
        }


    });
});


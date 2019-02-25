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

Espo.define('views/personal-data/modals/personal-data', ['views/modal'], function (Dep) {

    return Dep.extend({

        className: 'dialog dialog-record',

        template: 'personal-data/modals/personal-data',

        backdrop: true,

        setup: function () {
            Dep.prototype.setup.call(this);

            this.buttonList = [
                {
                    name: 'cancel',
                    label: 'Close'
                }
            ];

            this.headerHtml = this.getLanguage().translate('Personal Data');
            this.headerHtml += ': ' + Handlebars.Utils.escapeExpression(this.model.get('name'));

            if (this.getAcl().check(this.model, 'edit')) {
                this.buttonList.unshift({
                    name: 'erase',
                    label: 'Erase',
                    style: 'danger',
                    disabled: true
                });
            }

            this.fieldList = [];

            this.scope = this.model.name;

            this.createView('record', 'views/personal-data/record/record', {
                el: this.getSelector() + ' .record',
                model: this.model
            }, function (view) {
                this.listenTo(view, 'check', function (fieldList) {
                    this.fieldList = fieldList;
                    if (fieldList.length) {
                        this.enableButton('erase');
                    } else {
                        this.disableButton('erase');
                    }
                });

                if (!view.fieldList.length) {
                    this.disableButton('export');
                }
            });
        },

        actionErase: function () {
            this.confirm({
                message: this.translate('erasePersonalDataConfirmation', 'messages'),
                confirmText: this.translate('Erase')
            }, function () {
                this.disableButton('erase');
                this.ajaxPostRequest('DataPrivacy/action/erase', {
                    fieldList: this.fieldList,
                    entityType: this.scope,
                    id: this.model.id
                }).then(function () {
                    Espo.Ui.success(this.translate('Done'));

                    this.trigger('erase');
                }.bind(this)).fail(function () {
                    this.enableButton('erase');
                }.bind(this));
            }.bind(this));
        }

    });
});

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

define('views/admin/entity-manager/formula', ['view', 'lib!espo', 'model'], function (Dep, Espo, Model) {

    return Dep.extend({

        template: 'admin/entity-manager/formula',

        scope: null,

        events: {
            'click [data-action="save"]': function () {
                this.actionSave();
            },
            'click [data-action="close"]': function () {
                this.actionClose();
            },
        },

        data: function () {
            return {
                scope: this.scope,
            };
        },

        setup: function () {
            let scope = this.scope = this.options.scope || false;

            var model = this.model = new Model();

            model.name = 'EntityManager';

            this.wait(
                Espo.Ajax
                    .getRequest('Metadata/action/get', {
                        key: 'formula.' + scope
                    })
                    .then(formulaData => {
                        formulaData = formulaData || {};

                        model.set('beforeSaveCustomScript', formulaData.beforeSaveCustomScript || null);

                        this.createView('record', 'views/admin/entity-manager/record/edit-formula', {
                            el: this.getSelector() + ' .record',
                            model: model,
                            targetEntityType: this.scope,
                        });
                    })
            );

            this.listenTo(this.model, 'change', (m, o) => {
                if (!o.ui) {
                    return;
                }

                this.setIsChanged();
            });
        },

        afterRender: function () {
            this.$save = this.$el.find('[data-action="save"]');
        },

        disableButtons: function () {
            this.$save.addClass('disabled').attr('disabled', 'disabled');
        },

        enableButtons: function () {
            this.$save.removeClass('disabled').removeAttr('disabled');
        },

        actionSave: function () {
            this.disableButtons();

            var data = this.getView('record').fetch();

            this.model.set(data);

            if (this.getView('record').validate()) {
                return;
            }

            if (data.beforeSaveCustomScript === '') {
                data.beforeSaveCustomScript = null;
            }

            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            Espo.Ajax
                .postRequest('EntityManager/action/formula', {
                    data: data,
                    scope: this.scope
                })
                .then(() => {
                    Espo.Ui.success(this.translate('Saved'));

                    this.enableButtons();

                    this.setIsNotChanged();
                })
                .catch(() => this.enableButtons());
        },

        actionClose: function () {
            this.setIsNotChanged();

            this.getRouter().navigate('#Admin/entityManager/scope=' + this.scope, {trigger: true});
        },

        setConfirmLeaveOut: function (value) {
            this.getRouter().confirmLeaveOut = value;
        },

        setIsChanged: function () {
            this.isChanged = true;
            this.setConfirmLeaveOut(true);
        },

        setIsNotChanged: function () {
            this.isChanged = false;
            this.setConfirmLeaveOut(false);
        },

        updatePageTitle: function () {
            this.setPageTitle(this.getLanguage().translate('Formula', 'labels', 'EntityManager'));
        },

    });
});

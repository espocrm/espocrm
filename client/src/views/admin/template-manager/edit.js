/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

import View from 'view';
import Model from 'model';

export default class TemplateManagerEditView extends View {

    template = 'admin/template-manager/edit'

    data() {
        return {
            title: this.title,
            hasSubject: this.hasSubject
        };
    }

    events = {
        /** @this TemplateManagerEditView */
        'click [data-action="save"]': function () {
            this.actionSave();
        },
        /** @this TemplateManagerEditView */
        'click [data-action="cancel"]': function () {
            this.actionCancel();
        },
        /** @this TemplateManagerEditView */
        'click [data-action="resetToDefault"]': function () {
            this.actionResetToDefault();
        },
        /** @this TemplateManagerEditView */
        'keydown.form': function (e) {
            const key = Espo.Utils.getKeyFromKeyEvent(e);

            if (key === 'Control+KeyS' || key === 'Control+Enter') {
                this.actionSave();

                e.preventDefault();
                e.stopPropagation();
            }
        },
    }

    setup() {
        this.wait(true);

        this.fullName = this.options.name;

        this.name = this.fullName;
        this.scope = null;

        const arr = this.fullName.split('_');

        if (arr.length > 1) {
            this.scope = arr[1];
            this.name = arr[0];
        }

        this.hasSubject = !this.getMetadata().get(['app', 'templates', this.name, 'noSubject']);

        this.title = this.translate(this.name, 'templates', 'Admin');
        if (this.scope) {
            this.title += ' · ' + this.translate(this.scope, 'scopeNames');
        }

        this.attributes = {};

        Espo.Ajax.getRequest('TemplateManager/action/getTemplate', {
            name: this.name,
            scope: this.scope
        }).then(data => {
            const model = this.model = new Model();

            model.name = 'TemplateManager';
            model.set('body', data.body);
            this.attributes.body = data.body;

            if (this.hasSubject) {
                model.set('subject', data.subject);
                this.attributes.subject = data.subject;
             }

            this.listenTo(model, 'change', () => {
                this.setConfirmLeaveOut(true);
            });

            this.createView('bodyField', 'views/admin/template-manager/fields/body', {
                name: 'body',
                model: model,
                selector: '.body-field',
                mode: 'edit'
            });

            if (this.hasSubject) {
                this.createView('subjectField', 'views/fields/varchar', {
                    name: 'subject',
                    model: model,
                    selector: '.subject-field',
                    mode: 'edit'
                });
            }

            this.wait(false);
        });
    }

    setConfirmLeaveOut(value) {
        this.getRouter().confirmLeaveOut = value;
    }

    afterRender() {
        this.$save = this.$el.find('button[data-action="save"]');
        this.$cancel = this.$el.find('button[data-action="cancel"]');
        this.$resetToDefault = this.$el.find('button[data-action="resetToDefault"]');
    }

    actionSave() {
        this.$save.addClass('disabled').attr('disabled');
        this.$cancel.addClass('disabled').attr('disabled');
        this.$resetToDefault.addClass('disabled').attr('disabled');

        const bodyFieldView = /** @type {import('views/fields/base').default} */
            this.getView('bodyField');

        bodyFieldView.fetchToModel();

        const data = {
            name: this.name,
            body: this.model.get('body'),
        };

        if (this.scope) {
            data.scope = this.scope;
        }

        if (this.hasSubject) {
            const subjectFieldView = /** @type {import('views/fields/base').default} */
                this.getView('subjectField');

            subjectFieldView.fetchToModel();

            data.subject = this.model.get('subject');
        }

        Espo.Ui.notify(this.translate('saving', 'messages'));

        Espo.Ajax.postRequest('TemplateManager/action/saveTemplate', data)
            .then(() => {
                this.setConfirmLeaveOut(false);

                this.attributes.body = data.body;
                this.attributes.subject = data.subject;

                this.$save.removeClass('disabled').removeAttr('disabled');
                this.$cancel.removeClass('disabled').removeAttr('disabled');
                this.$resetToDefault.removeClass('disabled').removeAttr('disabled');

                Espo.Ui.success(this.translate('Saved'));
            })
            .catch(() => {
                this.$save.removeClass('disabled').removeAttr('disabled');
                this.$cancel.removeClass('disabled').removeAttr('disabled');
                this.$resetToDefault.removeClass('disabled').removeAttr('disabled');
            });
    }

    actionCancel() {
        this.model.set('subject', this.attributes.subject);
        this.model.set('body', this.attributes.body);

        this.setConfirmLeaveOut(false);
    }

    actionResetToDefault() {
        this.confirm(this.translate('confirmation', 'messages'), () => {
            this.$save.addClass('disabled').attr('disabled');
            this.$cancel.addClass('disabled').attr('disabled');
            this.$resetToDefault.addClass('disabled').attr('disabled');

            const data = {
                name: this.name,
                body: this.model.get('body'),
            };

            if (this.scope) {
                data.scope = this.scope;
            }

            Espo.Ui.notifyWait();

            Espo.Ajax.postRequest('TemplateManager/action/resetTemplate', data)
                .then(returnData => {
                    this.$save.removeClass('disabled').removeAttr('disabled');
                    this.$cancel.removeClass('disabled').removeAttr('disabled');
                    this.$resetToDefault.removeClass('disabled').removeAttr('disabled');

                    this.attributes.body = returnData.body;
                    this.attributes.subject = returnData.subject;

                    this.model.set('subject', returnData.subject);
                    this.model.set('body', returnData.body);
                    this.setConfirmLeaveOut(false);

                    Espo.Ui.notify(false);
                })
                .catch(() => {
                    this.$save.removeClass('disabled').removeAttr('disabled');
                    this.$cancel.removeClass('disabled').removeAttr('disabled');
                    this.$resetToDefault.removeClass('disabled').removeAttr('disabled');
                });
        });
    }
}

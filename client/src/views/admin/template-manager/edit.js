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

Espo.define('views/admin/template-manager/edit', ['view', 'model'], function (Dep, Model) {

    return Dep.extend({

        template: 'admin/template-manager/edit',

        data: function () {
            return {
                title: this.title,
                hasSubject: this.hasSubject
            };
        },

        events: {
            'click [data-action="save"]': function () {
                this.actionSave();
            },
            'click [data-action="cancel"]': function () {
                this.actionCancel();
            },
            'click [data-action="resetToDefault"]': function () {
                this.actionResetToDefault();
            }
        },

        setup: function () {
            this.wait(true);

            this.fullName = this.options.name;

            this.name = this.fullName;
            this.scope = null;

            var arr = this.fullName.split('_');
            if (arr.length > 1) {
                this.scope = arr[1];
                this.name = arr[0];
            }

            this.hasSubject = !this.getMetadata().get(['app', 'templates', this.name, 'noSubject']);

            this.title = this.translate(this.name, 'templates', 'Admin');
            if (this.scope) {
                this.title += ' :: ' + this.translate(this.scope, 'scopeNames');
            }

            this.attributes = {};

            this.ajaxGetRequest('TemplateManager/action/getTemplate', {
                name: this.name,
                scope: this.scope
            }).then(function (data) {

                var model = this.model = new Model();
                model.name = 'TemplateManager';
                model.set('body', data.body);
                this.attributes.body = data.body;

                if (this.hasSubject) {
                    model.set('subject', data.subject);
                    this.attributes.subject = data.subject;
                 }

                this.listenTo(model, 'change', function () {
                    this.setConfirmLeaveOut(true);
                }, this);

                this.createView('bodyField', 'views/fields/wysiwyg', {
                    name: 'body',
                    model: model,
                    el: this.getSelector() + ' .body-field',
                    mode: 'edit'
                });
                if (this.hasSubject) {
                    this.createView('subjectField', 'views/fields/varchar', {
                        name: 'subject',
                        model: model,
                        el: this.getSelector() + ' .subject-field',
                        mode: 'edit'
                    });
                }
                this.wait(false);
            }.bind(this));
        },

        setConfirmLeaveOut: function (value) {
            this.getRouter().confirmLeaveOut = value;
        },

        afterRender: function () {
            this.$save = this.$el.find('button[data-action="save"]');
            this.$cancel = this.$el.find('button[data-action="cancel"]');
            this.$resetToDefault = this.$el.find('button[data-action="resetToDefault"]');
        },

        actionSave: function () {
            this.$save.addClass('disabled').attr('disabled');
            this.$cancel.addClass('disabled').attr('disabled');
            this.$resetToDefault.addClass('disabled').attr('disabled');

            this.getView('bodyField').fetchToModel();

            var data = {
                name: this.name,
                body: this.model.get('body')
            };
            if (this.scope) {
                data.scope = this.scope;
            }
            if (this.hasSubject) {
                this.getView('subjectField').fetchToModel();
                data.subject = this.model.get('subject');
            }

            Espo.Ui.notify(this.translate('saving', 'messages'));
            this.ajaxPostRequest('TemplateManager/action/saveTemplate', data).then(function () {
                this.setConfirmLeaveOut(false);

                this.attributes.body = data.body;
                this.attributes.subject = data.subject;

                this.$save.removeClass('disabled').removeAttr('disabled');
                this.$cancel.removeClass('disabled').removeAttr('disabled');
                this.$resetToDefault.removeClass('disabled').removeAttr('disabled');

                Espo.Ui.success(this.translate('Saved'));
            }.bind(this)).fail(function () {
                this.$save.removeClass('disabled').removeAttr('disabled');
                this.$cancel.removeClass('disabled').removeAttr('disabled');
                this.$resetToDefault.removeClass('disabled').removeAttr('disabled');
            }.bind(this));
        },

        actionCancel: function () {
            this.model.set('subject', this.attributes.subject);
            this.model.set('body', this.attributes.body);
            this.setConfirmLeaveOut(false);
        },

        actionResetToDefault: function () {
            this.confirm(this.translate('confirmation', 'messages'), function () {
                this.$save.addClass('disabled').attr('disabled');
                this.$cancel.addClass('disabled').attr('disabled');
                this.$resetToDefault.addClass('disabled').attr('disabled');

                var data = {
                    name: this.name,
                    body: this.model.get('body')
                };

                if (this.scope) {
                    data.scope = this.scope;
                }

                Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

                this.ajaxPostRequest('TemplateManager/action/resetTemplate', data).then(function (returnData) {
                    this.$save.removeClass('disabled').removeAttr('disabled');
                    this.$cancel.removeClass('disabled').removeAttr('disabled');
                    this.$resetToDefault.removeClass('disabled').removeAttr('disabled');

                    this.attributes.body = returnData.body;
                    this.attributes.subject = returnData.subject;

                    this.model.set('subject', returnData.subject);
                    this.model.set('body', returnData.body);
                    this.setConfirmLeaveOut(false);

                    Espo.Ui.notify(false);
                }.bind(this)).fail(function () {
                    this.$save.removeClass('disabled').removeAttr('disabled');
                    this.$cancel.removeClass('disabled').removeAttr('disabled');
                    this.$resetToDefault.removeClass('disabled').removeAttr('disabled');
                }.bind(this));
            }.bind(this));
        }

    });
});

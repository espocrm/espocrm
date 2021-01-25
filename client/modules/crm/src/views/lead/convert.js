/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('crm:views/lead/convert', 'view', function (Dep) {

    return Dep.extend({

        template: 'crm:lead/convert',

        data: function () {
            return {
                scopeList: this.scopeList,
                scope: this.model.name,
            };
        },

        events: {
            'change input.check-scope': function (e) {
                var scope = $(e.currentTarget).data('scope');
                var $div = this.$el.find('.edit-container-' + Espo.Utils.toDom(scope));
                if (e.currentTarget.checked)    {
                    $div.removeClass('hide');
                } else {
                    $div.addClass('hide');
                }
            },
            'click button[data-action="convert"]': function (e) {
                this.convert();
            },
            'click button[data-action="cancel"]': function (e) {
                this.getRouter().navigate('#Lead/view/' + this.id, {trigger: true});
            },
        },

        setup: function () {
            this.wait(true);
            this.id = this.options.id;

            this.notify('Loading...');

            this.getModelFactory().create('Lead', function (model) {
                this.model = model;
                model.id = this.id;

                this.listenToOnce(model, 'sync', function () {
                    this.build();
                }.bind(this));
                model.fetch();
            }.bind(this));

        },

        build: function () {
            var scopeList = this.scopeList = [];
            (this.getMetadata().get('entityDefs.Lead.convertEntityList') || []).forEach(function (scope) {
                if (scope == 'Account' && this.getConfig().get('b2cMode')) {
                    return;
                }
                if (this.getMetadata().get(['scopes', scope, 'disabled'])) return;
                if (this.getAcl().check(scope, 'edit')) {
                    scopeList.push(scope);
                }
            }, this);
            var i = 0;

            var attributeList = this.getFieldManager().getEntityTypeAttributeList(this.model.name);
            var ignoreAttributeList = ['createdAt', 'modifiedAt', 'modifiedById', 'modifiedByName', 'createdById', 'createdByName'];

            if (scopeList.length) {
                this.ajaxPostRequest('Lead/action/getConvertAttributes', {
                    id: this.model.id
                }).done(function (data) {
                    scopeList.forEach(function (scope) {
                        this.getModelFactory().create(scope, function (model) {
                            model.populateDefaults();

                            model.set(data[scope] || {}, {silent: true});

                            var convertEntityViewName = this.getMetadata().get(['clientDefs', scope, 'recordViews', 'edit']) || 'views/record/edit';
                            this.createView(scope, convertEntityViewName, {
                                model: model,
                                el: '#main .edit-container-' + Espo.Utils.toDom(scope),
                                buttonsPosition: false,
                                buttonsDisabled: true,
                                layoutName: 'detailConvert',
                                exit: function () {},
                            }, function (view) {
                                i++;
                                if (i == scopeList.length) {
                                    this.wait(false);
                                    this.notify(false);
                                }
                            }, this);

                        }, this);
                    }, this);
                }.bind(this));
            }

            if (scopeList.length == 0) {
                this.wait(false);
            }
        },

        convert: function () {

            var scopeList = [];

            this.scopeList.forEach(function (scope) {
                var el = this.$el.find('input[data-scope="' + scope + '"]').get(0);
                if (el && el.checked) {
                    scopeList.push(scope);
                }
            }.bind(this));

            if (scopeList.length == 0) {
                this.notify('Select one or more checkboxes', 'error');
                return;
            }

            this.getRouter().confirmLeaveOut = false;

            var notValid = false;
            scopeList.forEach(function (scope) {
                var editView = this.getView(scope);
                editView.model.set(editView.fetch());
                notValid = editView.validate() || notValid;
            }, this);

            var data = {
                id: this.model.id,
                records: {},
            };

            scopeList.forEach(function (scope) {
                data.records[scope] = this.getView(scope).model.attributes;
            }, this);

            var process = function (data) {
                this.$el.find('[data-action="convert"]').addClass('disabled');
                this.notify(this.translate('pleaseWait', 'messages'));

                Espo.Ajax.postRequest('Lead/action/convert', data)
                .then(
                    function () {
                        this.getRouter().confirmLeaveOut = false;
                        this.getRouter().navigate('#Lead/view/' + this.model.id, {trigger: true});
                        this.notify('Converted', 'success');
                    }.bind(this)
                ).fail(
                    function (xhr) {
                        Espo.Ui.notify(false);
                        this.$el.find('[data-action="convert"]').removeClass('disabled');

                        var response = null;

                        if (~[409].indexOf(xhr.status)) {
                            var statusReasonHeader = xhr.getResponseHeader('X-Status-Reason');
                            if (statusReasonHeader) {
                                try {
                                    var response = JSON.parse(statusReasonHeader);
                                } catch (e) {
                                    console.error('Could not parse X-Status-Reason header');
                                }
                            }

                            if (response && response.reason === 'duplicate') {
                                xhr.errorIsHandled = true;

                                this.createView('duplicate', 'views/modals/duplicate', {
                                    duplicates: response.duplicates,
                                }, function (view) {
                                    view.render();

                                    this.listenToOnce(view, 'save', function () {
                                        data.skipDuplicateCheck = true;
                                        process(data);
                                    }, this);

                                });
                            }
                        }
                    }.bind(this)
                );
            }.bind(this);

            if (!notValid) {
                process(data);
            } else {
                this.notify('Not Valid', 'error');
            }
        },

    });
});

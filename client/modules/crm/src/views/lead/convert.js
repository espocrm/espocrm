/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('crm:views/lead/convert', ['view'], function (Dep) {

    return Dep.extend({

        template: 'crm:lead/convert',

        data: function () {
            return {
                scopeList: this.scopeList,
                scope: this.model.entityType,
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

            Espo.Ui.notify(' ... ');

            this.getModelFactory().create('Lead', (model) => {
                this.model = model;
                model.id = this.id;

                this.listenToOnce(model, 'sync', () => {
                    this.build();
                });

                model.fetch();
            });
        },

        build: function () {
            var scopeList = this.scopeList = [];

            (this.getMetadata().get('entityDefs.Lead.convertEntityList') || []).forEach(scope => {
                if (scope === 'Account' && this.getConfig().get('b2cMode')) {
                    return;
                }

                if (this.getMetadata().get(['scopes', scope, 'disabled'])) {
                    return
                }

                if (this.getAcl().check(scope, 'edit')) {
                    scopeList.push(scope);
                }
            });

            let i = 0;

            let ignoreAttributeList = [
                'createdAt',
                'modifiedAt',
                'modifiedById',
                'modifiedByName',
                'createdById',
                'createdByName',
            ];

            if (scopeList.length) {
                Espo.Ajax.postRequest('Lead/action/getConvertAttributes', {id: this.model.id})
                    .then(data => {
                        scopeList.forEach(scope => {
                            this.getModelFactory().create(scope, model => {
                                model.populateDefaults();

                                model.set(data[scope] || {}, {silent: true});

                                let convertEntityViewName = this.getMetadata()
                                    .get(['clientDefs', scope, 'recordViews', 'edit']) || 'views/record/edit';

                                this.createView(scope, convertEntityViewName, {
                                    model: model,
                                    fullSelector: '#main .edit-container-' + Espo.Utils.toDom(scope),
                                    buttonsPosition: false,
                                    buttonsDisabled: true,
                                    layoutName: 'detailConvert',
                                    exit: () => {},
                                }, view => {
                                    i++;

                                    if (i === scopeList.length) {
                                        this.wait(false);
                                        Espo.Ui.notify(false);
                                    }
                                });
                            });
                        });
                    });
            }

            if (scopeList.length === 0) {
                this.wait(false);
            }
        },

        convert: function () {
            let scopeList = [];

            this.scopeList.forEach(scope => {
                var el = this.$el.find('input[data-scope="' + scope + '"]').get(0);

                if (el && el.checked) {
                    scopeList.push(scope);
                }
            });

            if (scopeList.length === 0) {
                this.notify('Select one or more checkboxes', 'error');

                return;
            }

            this.getRouter().confirmLeaveOut = false;

            let notValid = false;

            scopeList.forEach(scope => {
                let editView = this.getView(scope);

                editView.model.set(editView.fetch());
                notValid = editView.validate() || notValid;
            });

            let data = {
                id: this.model.id,
                records: {},
            };

            scopeList.forEach(scope => {
                data.records[scope] = this.getView(scope).model.attributes;
            });

            var process = (data) => {
                this.$el.find('[data-action="convert"]').addClass('disabled');

                Espo.Ui.notify(' ... ');

                Espo.Ajax
                .postRequest('Lead/action/convert', data)
                .then(() => {
                    this.getRouter().confirmLeaveOut = false;
                    this.getRouter().navigate('#Lead/view/' + this.model.id, {trigger: true});

                    this.notify('Converted', 'success');
                })
                .catch(xhr => {
                    Espo.Ui.notify(false);

                    this.$el.find('[data-action="convert"]').removeClass('disabled');

                    if (xhr.status !== 409) {
                        return;
                    }

                    if (xhr.getResponseHeader('X-Status-Reason') !== 'duplicate') {
                        return;
                    }

                    let response = null;

                    try {
                        response = JSON.parse(xhr.responseText);
                    } catch (e) {
                        console.error('Could not parse response header.');

                        return;
                    }

                    xhr.errorIsHandled = true;

                    this.createView('duplicate', 'views/modals/duplicate', {duplicates: response}, view => {
                        view.render();

                        this.listenToOnce(view, 'save', () => {
                            data.skipDuplicateCheck = true;

                            process(data);
                        });
                    });
                });
            };

            if (notValid) {
                this.notify('Not Valid', 'error');

                return;
            }

            process(data);
        },
    });
});

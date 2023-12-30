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

define('views/admin/formula-sandbox/index', ['view', 'model'], function (Dep, Model) {

    return Dep.extend({

        template: 'admin/formula-sandbox/index',

        targetEntityType: null,

        storageKey: 'formulaSandbox',

        data: function () {
            return {};
        },

        setup: function () {
            let entityTypeList = [''].concat(
                this.getMetadata()
                    .getScopeEntityList()
                    .filter(item => {
                        return this.getMetadata().get(['scopes', item, 'object']);
                    })
            );

            let data = {
                script: null,
                targetId: null,
                targetType: null,
                output: null,
            };

            if (this.getSessionStorage().has(this.storageKey)) {
                let storedData = this.getSessionStorage().get(this.storageKey);

                data.script = storedData.script || null;
                data.targetId = storedData.targetId || null;
                data.targetName = storedData.targetName || null;
                data.targetType = storedData.targetType || null;
            }

            let model = this.model = new Model();

            model.name = 'Formula';

            model.setDefs({
                fields: {
                    targetType: {
                        type: 'enum',
                        options: entityTypeList,
                        translation: 'Global.scopeNames',
                        view: 'views/fields/entity-type',
                    },
                    target: {
                        type: 'link',
                        entity: data.targetType,
                    },
                    script: {
                        type: 'formula',
                        view: 'views/fields/formula',
                    },
                    output: {
                        type: 'text',
                        readOnly: true,
                        displayRawText: true,
                        tooltip: true,
                    },
                    errorMessage: {
                        type: 'text',
                        readOnly: true,
                        displayRawText: true,
                    },
                }
            });

            model.set(data);

            this.createRecordView();

            this.listenTo(this.model, 'change:targetType', (m, v, o) => {
                if (!o.ui) {
                    return;
                }

                setTimeout(() => {
                    this.targetEntityType = this.model.get('targetType');

                    this.model.set({
                        targetId: null,
                        targetName: null,
                    }, {silent: true});

                    let attributes = Espo.Utils.cloneDeep(this.model.attributes);

                    this.clearView('record');

                    this.model.set(attributes, {silent: true});

                    this.model.defs.fields.target.entity = this.targetEntityType;

                    this.createRecordView()
                        .then(view => view.render());
                }, 10);
            });

            this.listenTo(this.model, 'run', () => this.run());

            this.listenTo(this.model, 'change', (m, o) => {
                if (!o.ui) {
                    return;
                }

                let dataToStore = {
                    script: this.model.get('script'),
                    targetType: this.model.get('targetType'),
                    targetId: this.model.get('targetId'),
                    targetName: this.model.get('targetName'),
                };

                this.getSessionStorage().set(this.storageKey, dataToStore);
            });
        },

        createRecordView: function () {
            return this.createView('record', 'views/admin/formula-sandbox/record/edit', {
                selector: '.record',
                model: this.model,
                targetEntityType: this.targetEntityType,
                confirmLeaveDisabled: true,
                shortcutKeysEnabled: true,
            });
        },

        updatePageTitle: function () {
            this.setPageTitle(this.getLanguage().translate('Formula Sandbox', 'labels', 'Admin'));
        },

        run: function () {
            let script = this.model.get('script');

            this.model.set({
                output: null,
                errorMessage: null,
            });

            if (script === '' || script === null) {
                this.model.set('output', null);

                Espo.Ui.warning(
                    this.translate('emptyScript', 'messages', 'Formula')
                );

                return;
            }

            Espo.Ajax
                .postRequest('Formula/action/run', {
                    expression: script,
                    targetId: this.model.get('targetId'),
                    targetType: this.model.get('targetType'),
                })
                .then(response => {
                    this.model.set('output', response.output || null);

                    let errorMessage = null;

                    if (!response.isSuccess) {
                        errorMessage = response.message || null;
                    }

                    this.model.set('errorMessage', errorMessage);

                    if (response.isSuccess) {
                        Espo.Ui.success(
                            this.translate('runSuccess', 'messages', 'Formula')
                        );

                        return;
                    }

                    if (response.isSyntaxError) {
                        let msg = this.translate('checkSyntaxError', 'messages', 'Formula');

                        if (response.message) {
                            msg += ' ' + response.message;
                        }

                        Espo.Ui.error(msg);

                        return;
                    }

                    let msg = this.translate('runError', 'messages', 'Formula');

                    if (response.message) {
                        msg += ' ' + response.message;
                    }

                    Espo.Ui.error(msg);
                });
        },
    });
});

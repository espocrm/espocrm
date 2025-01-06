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

export default class IntegrationsEditView extends View {

    template = 'admin/integrations/edit'

    data() {
        return {
            integration: this.integration,
            dataFieldList: this.dataFieldList,
            helpText: this.helpText
        };
    }

    events = {
        /** @this IntegrationsEditView */
        'click button[data-action="cancel"]': function () {
            this.getRouter().navigate('#Admin/integrations', {trigger: true});
        },
        /** @this IntegrationsEditView */
        'click button[data-action="save"]': function () {
            this.save();
        },
    }

    setup() {
        this.integration = this.options.integration;

        this.helpText = false;

        if (this.getLanguage().has(this.integration, 'help', 'Integration')) {
            this.helpText = this.translate(this.integration, 'help', 'Integration');
        }

        this.fieldList = [];

        this.dataFieldList = [];

        this.model = new Model();
        this.model.id = this.integration;
        this.model.name = 'Integration';
        this.model.urlRoot = 'Integration';

        this.model.defs = {
            fields: {
                enabled: {
                    required: true,
                    type: 'bool',
                },
            }
        };

        this.wait(true);

        this.fields = this.getMetadata().get(`integrations.${this.integration}.fields`);

        Object.keys(this.fields).forEach(name => {
            this.model.defs.fields[name] = this.fields[name];
            this.dataFieldList.push(name);
        });

        this.model.populateDefaults();

        this.listenToOnce(this.model, 'sync', () => {
            this.createFieldView('bool', 'enabled');

            Object.keys(this.fields).forEach(name => {
                this.createFieldView(this.fields[name]['type'], name, null, this.fields[name]);
            });

            this.wait(false);
        });

        this.model.fetch();
    }

    hideField(name) {
        this.$el.find('label[data-name="'+name+'"]').addClass('hide');
        this.$el.find('div.field[data-name="'+name+'"]').addClass('hide');

        const view = this.getView(name);

        if (view) {
            view.disabled = true;
        }
    }

    showField(name) {
        this.$el.find('label[data-name="'+name+'"]').removeClass('hide');
        this.$el.find('div.field[data-name="'+name+'"]').removeClass('hide');

        const view = this.getFieldView(name);

        if (view) {
            view.disabled = false;
        }
    }

    /**
     * @since 9.0.0
     * @param {string} name
     * @return {import('views/fields/base').default}
     */
    getFieldView(name) {
        return this.getView(name)
    }

    afterRender() {
        if (!this.model.get('enabled')) {
            this.dataFieldList.forEach(name => {
                this.hideField(name);
            });
        }

        this.listenTo(this.model, 'change:enabled', () => {
            if (this.model.get('enabled')) {
                this.dataFieldList.forEach(name => this.showField(name));
            } else {
                this.dataFieldList.forEach(name => this.hideField(name));
            }
        });
    }

    createFieldView(type, name, readOnly, params) {
        const viewName = this.model.getFieldParam(name, 'view') || this.getFieldManager().getViewName(type);

        this.createView(name, viewName, {
            model: this.model,
            selector: `.field[data-name="${name}"]`,
            defs: {
                name: name,
                params: params
            },
            mode: readOnly ? 'detail' : 'edit',
            readOnly: readOnly,
        });

        this.fieldList.push(name);
    }

    save() {
        this.fieldList.forEach(field => {
            const view = this.getFieldView(field);

            if (!view.readOnly) {
                view.fetchToModel();
            }
        });

        let notValid = false;

        this.fieldList.forEach(field => {
            const fieldView = this.getFieldView(field);

            if (fieldView && !fieldView.disabled) {
                notValid = fieldView.validate() || notValid;
            }
        });

        if (notValid) {
            Espo.Ui.error(this.translate('Not valid'));

            return;
        }

        this.listenToOnce(this.model, 'sync', () => {
            Espo.Ui.success(this.translate('Saved'));
        });

        Espo.Ui.notify(this.translate('saving', 'messages'));

        this.model.save();
    }
}

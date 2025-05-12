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

import MainView from 'views/main';

class ConvertLeadView extends MainView {

    template = 'crm:lead/convert'

    data() {
        return {
            scopeList: this.scopeList,
            scope: this.scope,
        };
    }

    setup() {
        this.scope = 'Lead';

        this.addHandler('change', 'input.check-scope', (e, /** HTMLInputElement */target) => {
            const scope = target.dataset.scope;
            const $div = this.$el.find(`.edit-container-${Espo.Utils.toDom(scope)}`);

            if (target.checked)    {
                $div.removeClass('hide');
            } else {
                $div.addClass('hide');
            }
        });

        this.addActionHandler('convert', () => this.convert());

        this.addActionHandler('cancel', () => {
            this.getRouter().navigate(`#Lead/view/${this.id}`, {trigger: true});
        });

        this.createView('header', 'views/header', {
            model: this.model,
            fullSelector: '#main > .header',
            scope: this.scope,
            fontSizeFlexible: true,
        });

        this.wait(true);
        this.id = this.options.id;

        Espo.Ui.notifyWait();

        this.getModelFactory().create('Lead', model => {
            this.model = model;
            model.id = this.id;

            this.listenToOnce(model, 'sync', () => this.build());

            model.fetch();
        });
    }

    build() {
        const scopeList = this.scopeList = [];

        (this.getMetadata().get('entityDefs.Lead.convertEntityList') || []).forEach(scope => {
            if (scope === 'Account' && this.getConfig().get('b2cMode')) {
                return;
            }

            if (this.getMetadata().get(['scopes', scope, 'disabled'])) {
                return
            }

            if (this.getAcl().check(scope, 'create')) {
                scopeList.push(scope);
            }
        });

        let i = 0;

        const ignoreAttributeList = [
            'createdAt',
            'modifiedAt',
            'modifiedById',
            'modifiedByName',
            'createdById',
            'createdByName',
        ];

        if (scopeList.length === 0) {
            this.wait(false);

            return;
        }


        Espo.Ajax.postRequest('Lead/action/getConvertAttributes', {id: this.model.id}).then(data => {
            scopeList.forEach(scope => {
                this.getModelFactory().create(scope, model => {
                    model.populateDefaults();

                    model.set(data[scope] || {}, {silent: true});

                    const convertEntityViewName = this.getMetadata()
                        .get(['clientDefs', scope, 'recordViews', 'edit']) || 'views/record/edit';

                    this.createView(scope, convertEntityViewName, {
                        model: model,
                        fullSelector: '#main .edit-container-' + Espo.Utils.toDom(scope),
                        buttonsPosition: false,
                        buttonsDisabled: true,
                        layoutName: 'detailConvert',
                        exit: () => {},
                    }, () => {
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

    convert() {
        const scopeList = [];

        this.scopeList.forEach(scope => {
            /** @type {HTMLInputElement} */
            const el = this.$el.find(`input[data-scope="${scope}"]`).get(0);

            if (el && el.checked) {
                scopeList.push(scope);
            }
        });

        if (scopeList.length === 0) {
            Espo.Ui.error(this.translate('selectAtLeastOneRecord', 'messages'))

            return;
        }

        this.getRouter().confirmLeaveOut = false;

        let notValid = false;

        scopeList.forEach(scope => {
            const editView = /** @type {import('views/record/edit').default} */this.getView(scope);

            editView.setConfirmLeaveOut(false);

            editView.model.set(editView.fetch());
            notValid = editView.validate() || notValid;
        });

        const data = {
            id: this.model.id,
            records: {},
        };

        scopeList.forEach(scope => {
            data.records[scope] = this.getView(scope).model.attributes;
        });

        const process = (data) => {
            this.$el.find('[data-action="convert"]').addClass('disabled');

            Espo.Ui.notifyWait();

            Espo.Ajax
            .postRequest('Lead/action/convert', data)
            .then(() => {
                this.getRouter().confirmLeaveOut = false;
                this.getRouter().navigate('#Lead/view/' + this.model.id, {trigger: true});

                Espo.Ui.notify(this.translate('Converted', 'labels', 'Lead'));
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
            Espo.Ui.error(this.translate('Not valid'))

            return;
        }

        process(data);
    }

    getHeader() {
        const headerIconHtml = this.getHeaderIconHtml();
        const scopeLabel = this.getLanguage().translate(this.model.entityType, 'scopeNamesPlural');

        const $root =
            $('<span>')
                .append(
                    $('<a>')
                        .attr('href', '#Lead')
                        .text(scopeLabel)
                );

        if (headerIconHtml) {
            $root.prepend(headerIconHtml);
        }

        const name = this.model.get('name') || this.model.id;
        const url = `#${this.model.entityType}/view/${this.model.id}`;

        const $name =
            $('<a>')
                .attr('href', url)
                .addClass('action')
                .append($('<span>').text(name));

        return this.buildHeaderHtml([
            $root,
            $name,
            $('<span>').text(this.translate('convert', 'labels', 'Lead'))
        ]);
    }
}

export default ConvertLeadView;

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

class IndexFieldManagerView extends View {

    template = 'admin/field-manager/index'
    scopeList = null
    scope = null
    type = null

    data() {
        return {
            scopeList: this.scopeList,
            scope: this.scope,
        };
    }

    events = {
        /** @this IndexFieldManagerView */
        'click #scopes-menu a.scope-link': function (e) {
            const scope = $(e.currentTarget).data('scope');

            this.openScope(scope);
        },
        /** @this IndexFieldManagerView */
        'click #fields-content a.field-link': function (e) {
            e.preventDefault();

            const scope = $(e.currentTarget).data('scope');
            const field = $(e.currentTarget).data('field');

            this.openField(scope, field);
        },
        /** @this IndexFieldManagerView */
        'click [data-action="addField"]': function () {
            this.createView('dialog', 'views/admin/field-manager/modals/add-field', {}, (view) => {
                view.render();

                this.listenToOnce(view, 'add-field', type => {
                    this.createField(this.scope, type);
                });
            });
        },
    }

    setup() {
        this.scopeList = [];

        const scopesAll = Object.keys(this.getMetadata().get('scopes')).sort((v1, v2) => {
            return this.translate(v1, 'scopeNamesPlural').localeCompare(this.translate(v2, 'scopeNamesPlural'));
        });

        scopesAll.forEach(scope => {
            if (
                this.getMetadata().get('scopes.' + scope + '.entity') &&
                this.getMetadata().get('scopes.' + scope + '.customizable')
            ) {
                this.scopeList.push(scope);
            }
        });

        this.scope = this.options.scope || null;
        this.field = this.options.field || null;

        this.on('after:render', () => {
            if (!this.scope) {
                this.renderDefaultPage();

                return;
            }

            if (!this.field) {
                this.openScope(this.scope);
            }
            else {
                this.openField(this.scope, this.field);
            }
        });

        this.createView('header', 'views/admin/field-manager/header', {
            selector: '> .page-header',
            scope: this.scope,
            field: this.field,
        });
    }

    openScope(scope) {
        this.scope = scope;
        this.field = null;

        this.getHeaderView().setField(null);

        this.getRouter().navigate('#Admin/fieldManager/scope=' + scope, {trigger: false});

        Espo.Ui.notifyWait();

        this.createView('content', 'views/admin/field-manager/list', {
            fullSelector: '#fields-content',
            scope: scope,
        }, (view) => {
            view.render();

            Espo.Ui.notify(false);

            $(window).scrollTop(0);
        });
    }

    /**
     *
     * @return {import('./header').default}
     */
    getHeaderView() {
        return this.getView('header');
    }

    openField(scope, field) {
        this.scope = scope;
        this.field = field;

        this.getHeaderView().setField(field);

        this.getRouter()
            .navigate('#Admin/fieldManager/scope=' + scope + '&field=' + field, {trigger: false});

        Espo.Ui.notifyWait();

        this.createView('content', 'views/admin/field-manager/edit', {
            fullSelector: '#fields-content',
            scope: scope,
            field: field,
        }, (view) => {
            view.render();

            Espo.Ui.notify(false);

            $(window).scrollTop(0);

            this.listenTo(view, 'after:save', () => {
                Espo.Ui.success(this.translate('Saved'));
            });
        });
    }

    /**
     * @private
     * @param {string} scope
     * @param {string} type
     */
    createField(scope, type) {
        this.scope = scope;
        this.type = type;

        this.getRouter()
            .navigate('#Admin/fieldManager/scope=' + scope + '&type=' + type + '&create=true', {trigger: false});

        Espo.Ui.notifyWait();

        this.createView('content', 'views/admin/field-manager/edit', {
            fullSelector: '#fields-content',
            scope: scope,
            type: type,
        }, view => {
            view.render();

            Espo.Ui.notify(false);
            $(window).scrollTop(0);

            view.once('after:save', () => {
                this.openScope(this.scope);

                if (!this.getMetadata().get(`scopes.${this.scope}.layouts`)) {
                    Espo.Ui.success(this.translate('Created'), {suppress: true});

                    return;
                }

                const message = this.translate('fieldCreatedAddToLayouts', 'messages', 'FieldManager')
                    .replace('{link}', `#Admin/layouts/scope=${this.scope}&em=true`);

                setTimeout(() => {
                    Espo.Ui.notify(message, 'success', undefined, {closeButton: true});
                }, 100);
            });
        });
    }

    renderDefaultPage() {
        $('#fields-content').html(this.translate('selectEntityType', 'messages', 'Admin'));
    }

    updatePageTitle() {
        this.setPageTitle(this.getLanguage().translate('Field Manager', 'labels', 'Admin'));
    }
}

export default IndexFieldManagerView;

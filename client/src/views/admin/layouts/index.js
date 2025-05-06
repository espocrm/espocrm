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
import LayoutDefaultPageView from 'views/admin/layouts/default-page';
import LayoutCreateModalView from 'views/admin/layouts/modals/create';

class LayoutIndexView extends View {

    template = 'admin/layouts/index'

    scopeList = null
    baseUrl = '#Admin/layouts'
    typeList = [
        'list',
        'detail',
        'listSmall',
        'detailSmall',
        'defaultSidePanel',
        'bottomPanelsDetail',
        'filters',
        'massUpdate',
        'sidePanelsDetail',
        'sidePanelsEdit',
        'sidePanelsDetailSmall',
        'sidePanelsEditSmall',
    ]
    /**
     * @type {string|null}
     */
    scope = null
    /**
     * @type {string|null}
     */
    type = null

    data() {
        return {
            scopeList: this.scopeList,
            typeList: this.typeList,
            scope: this.scope,
            layoutScopeDataList: this.getLayoutScopeDataList(),
            headerHtml: this.getHeaderHtml(),
            em: this.em,
        };
    }

    setup() {
        this.addHandler('click', '#layouts-menu a.layout-link', 'onLayoutLinkClick');
        this.addHandler('click', 'a.accordion-toggle', 'onItemHeaderClick');
        this.addHandler('keydown.shortcuts', '', 'onKeyDown');

        this.addActionHandler('createLayout', () => this.actionCreateLayout());

        this.em = this.options.em || false;
        this.scope = this.options.scope || null;
        this.type = this.options.type || null;

        this.scopeList = [];

        const scopeFullList = this.getMetadata().getScopeList().sort((v1, v2) => {
            return this.translate(v1, 'scopeNamesPlural')
                .localeCompare(this.translate(v2, 'scopeNamesPlural'));
        });

        scopeFullList.forEach(scope => {
            if (
                this.getMetadata().get('scopes.' + scope + '.entity') &&
                this.getMetadata().get('scopes.' + scope + '.layouts')
            ) {
                this.scopeList.push(scope);
            }
        });

        if (this.em && this.scope) {
            if (this.scopeList.includes(this.scope)) {
                this.scopeList = [this.scope];
            }
            else {
                this.scopeList = [];
            }
        }

        this.on('after:render', () => {
            $("#layouts-menu a[data-scope='" + this.options.scope + "'][data-type='" + this.options.type + "']")
                .addClass('disabled');

            this.renderLayoutHeader();

            if (!this.options.scope || !this.options.type) {
                this.checkLayout();

                this.renderDefaultPage();
            }

            if (this.scope && this.options.type) {
                this.checkLayout();

                this.openLayout(this.options.scope, this.options.type);
            }
        });
    }

    checkLayout() {
        const scope = this.options.scope;
        const type = this.options.type;

        if (!scope) {
            return;
        }

        const item = this.getLayoutScopeDataList().find(item => item.scope === scope);

        if (!item) {
            throw new Espo.Exceptions.NotFound("Layouts not available for entity type.");
        }

        if (type && !item.typeList.includes(type)) {
            throw new Espo.Exceptions.NotFound("The layout type is not available for the entity type.");
        }
    }

    afterRender() {
        // To ensure notify about added field is closed. When followed to here from the field manager.
        Espo.Ui.notify();

        this.controlActiveButton();
    }

    controlActiveButton() {
        if (!this.scope) {
            return;
        }

        const $header = this.$el.find(`.accordion-toggle[data-scope="${this.scope}"]`);

        this.undisableLinks();

        if (this.em && this.scope && !this.type) {
            $header.addClass('disabled');

            return;
        }

        $header.removeClass('disabled');

        this.$el.find(`a.layout-link[data-scope="${this.scope}"][data-type="${this.type}"]`)
            .addClass('disabled');
    }

    /**
     * @param {MouseEvent} e
     */
    onLayoutLinkClick(e) {
        e.preventDefault();

        const scope = $(e.target).data('scope');
        const type = $(e.target).data('type');

        if (this.getContentView()) {
            if (this.scope === scope && this.type === type) {
                return;
            }
        }

        this.getRouter().checkConfirmLeaveOut(() => {
            this.openLayout(scope, type);

            this.controlActiveButton();
        });
    }

    openDefaultPage() {
        this.clearView('content');
        this.type = null;

        this.renderDefaultPage();
        this.controlActiveButton();

        this.navigate(this.scope);
    }

    /**
     * @param {MouseEvent} e
     */
    onItemHeaderClick(e) {
        e.preventDefault();

        if (this.em) {
            if (!this.getContentView()) {
                return;
            }

            this.getRouter().checkConfirmLeaveOut(() => {
                this.openDefaultPage();
            });

            return;
        }

        const $target = $(e.target);
        const scope = $target.data('scope');
        const $collapse = $('.collapse[data-scope="' + scope + '"]');

        $collapse.hasClass('in') ?
            $collapse.collapse('hide') :
            $collapse.collapse('show');
    }

    /**
     * @param {KeyboardEvent} e
     */
    onKeyDown(e) {
        const key = Espo.Utils.getKeyFromKeyEvent(e);

        if (!this.hasView('content')) {
            return;
        }

        if (key === 'Control+Enter' || key === 'Control+KeyS') {
            e.stopPropagation();
            e.preventDefault();

            this.getContentView().actionSave();
        }
    }

    undisableLinks() {
        $("#layouts-menu a.layout-link").removeClass('disabled');
    }

    /**
     * @return {module:views/admin/layouts/base}
     */
    getContentView() {
        return this.getView('content')
    }

    openLayout(scope, type) {
        this.scope = scope;
        this.type = type;

        this.navigate(scope, type);

        Espo.Ui.notifyWait();

        const typeReal = this.getMetadata()
            .get('clientDefs.' + scope + '.additionalLayouts.' + type + '.type') || type;

        this.createView('content', 'views/admin/layouts/' + Espo.Utils.camelCaseToHyphen(typeReal), {
            fullSelector: '#layout-content',
            scope: scope,
            type: type,
            realType: typeReal,
            setId: this.setId,
            em: this.em,
        }, view => {
            this.renderLayoutHeader();
            view.render();
            Espo.Ui.notify(false);

            $(window).scrollTop(0);

            if (this.em) {
                this.listenToOnce(view, 'cancel', () => {
                    this.openDefaultPage();
                });

                this.listenToOnce(view, 'after-delete', () => {
                    this.openDefaultPage();

                    Promise.all([
                        this.getMetadata().loadSkipCache(),
                        this.getLanguage().loadSkipCache(),
                    ]).then(() => {
                        this.reRender();
                    });
                });
            }
        });
    }

    navigate(scope, type) {
        let url = '#Admin/layouts/scope=' + scope;

        if (type) {
            url += '&type=' + type;
        }

        if (this.em) {
            url += '&em=true';
        }

        this.getRouter().navigate(url, {trigger: false});
    }

    renderDefaultPage() {
        $('#layout-header').html('').hide();

        if (this.em) {
            this.assignView('default', new LayoutDefaultPageView(), '#layout-content')
                .then(/** LayoutDefaultPageView */view => {
                    view.render();
                });

            return;
        }

        this.clearView('default');

        $('#layout-content').html(this.translate('selectLayout', 'messages', 'Admin'));
    }

    renderLayoutHeader() {
        const $header = $('#layout-header');

        if (!this.scope) {
            $header.html('');

            return;
        }

        const list = [];

        const separatorHtml = '<span class="breadcrumb-separator"><span></span></span>';

        if (!this.em) {
            list.push(
                $('<span>').text(this.translate(this.scope, 'scopeNames'))
            );
        }

        list.push(
            $('<span>').text(this.translateLayoutName(this.type, this.scope))
        );

        const html = list.map($item => $item.get(0).outerHTML).join(' ' + separatorHtml + ' ');

        $header.show().html(html);
    }

    updatePageTitle() {
        this.setPageTitle(this.getLanguage().translate('Layout Manager', 'labels', 'Admin'));
    }

    getHeaderHtml() {
        const separatorHtml = '<span class="breadcrumb-separator"><span></span></span>';

        const list = [];

        const $root = $('<a>')
            .attr('href', '#Admin')
            .text(this.translate('Administration'));

        list.push($root);

        if (this.em) {
            list.push(
                $('<a>')
                    .attr('href', '#Admin/entityManager')
                    .text(this.translate('Entity Manager', 'labels', 'Admin'))
            );

            if (this.scope) {
                list.push(
                    $('<a>')
                        .attr('href', `#Admin/entityManager/scope=` + this.scope)
                        .text(this.translate(this.scope, 'scopeNames'))
                );

                list.push(
                    $('<span>').text(this.translate('Layouts', 'labels', 'EntityManager'))
                );
            }
        } else {
            list.push(
                $('<span>').text(this.translate('Layout Manager', 'labels', 'Admin'))
            );
        }

        return list.map($item => $item.get(0).outerHTML).join(' ' + separatorHtml + ' ');
    }

    translateLayoutName(type, scope) {
        if (this.getLanguage().get(scope, 'layouts', type)) {
            return this.getLanguage().translate(type, 'layouts', scope);
        }

        return this.getLanguage().translate(type, 'layouts', 'Admin');
    }

    getLayoutScopeDataList() {
        const dataList = [];

        this.scopeList.forEach(scope => {
            const item = {};

            let typeList = Espo.Utils.clone(this.typeList);

            item.scope = scope;
            item.url = this.baseUrl + '/scope=' + scope;

            if (this.em) {
                item.url += '&em=true';
            }

            if (
                this.getMetadata().get(['clientDefs', scope, 'bottomPanels', 'edit'])
            ) {
                typeList.push('bottomPanelsEdit');
            }

            if (
                this.getMetadata().get(['clientDefs', scope, 'defaultSidePanelDisabled']) ||
                this.getMetadata().get(['clientDefs', scope, 'defaultSidePanelFieldList'])
            ) {
                typeList = typeList.filter(it => it !== 'defaultSidePanel');
            }

            if (this.getMetadata().get(['clientDefs', scope, 'kanbanViewMode'])) {
                typeList.push('kanban');
            }

            const additionalLayouts = this.getMetadata().get(['clientDefs', scope, 'additionalLayouts']) || {};

            for (const aItem in additionalLayouts) {
                typeList.push(aItem);
            }

            typeList = typeList.filter(name => {
                return !this.getMetadata()
                    .get(['clientDefs', scope, 'layout' + Espo.Utils.upperCaseFirst(name) + 'Disabled'])
            });

            const typeDataList = [];

            typeList.forEach(type => {
                let url = this.baseUrl + '/scope=' + scope + '&type=' + type;

                if (this.em) {
                    url += '&em=true';
                }

                typeDataList.push({
                    type: type,
                    url: url,
                    label: this.translateLayoutName(type, scope),
                });
            });

            item.typeList = typeList;
            item.typeDataList = typeDataList;

            dataList.push(item);
        });

        return dataList;
    }

    actionCreateLayout() {
        const view = new LayoutCreateModalView({scope: this.scope});

        this.assignView('dialog', view).then(/** LayoutCreateModalView */view => {
            view.render();

            this.listenToOnce(view, 'done', () => {
                Promise.all([
                    this.getMetadata().loadSkipCache(),
                    this.getLanguage().loadSkipCache(),
                ]).then(() => {
                    this.reRender();
                });
            });
        });
    }
}

export default LayoutIndexView;

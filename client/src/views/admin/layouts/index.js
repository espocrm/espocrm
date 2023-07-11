/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

import View from 'view';

class LayoutIndexView extends View {

    template = 'admin/layouts/index'

    scopeList = null
    baseUrl = '#Admin/layouts'
    typeList = [
        'list',
        'detail',
        'listSmall',
        'detailSmall',
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

        this.em = this.options.em || false;
        this.scope = this.options.scope || null;
        this.type = this.options.type || null;

        this.scopeList = [];

        let scopeFullList = this.getMetadata().getScopeList().sort((v1, v2) => {
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
            this.scopeList = [this.scope];
        }

        this.on('after:render', () => {
            $("#layouts-menu a[data-scope='" + this.options.scope + "'][data-type='" + this.options.type + "']")
                .addClass('disabled');

            this.renderLayoutHeader();

            if (!this.options.scope || !this.options.type) {
                this.renderDefaultPage();
            }
            if (this.scope && this.options.type) {
                this.openLayout(this.options.scope, this.options.type);
            }
        });
    }

    afterRender() {
        this.controlActiveButton();
    }

    controlActiveButton() {
        if (!this.scope) {
            return;
        }

        let $header = this.$el.find(`.accordion-toggle[data-scope="${this.scope}"]`);

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

        let scope = $(e.target).data('scope');
        let type = $(e.target).data('type');

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
                this.clearView('content');
                this.type = null;

                this.renderDefaultPage();
                this.controlActiveButton();

                this.navigate(this.scope);
            });

            return;
        }

        let $target = $(e.target);
        let scope = $target.data('scope');
        let $collapse = $('.collapse[data-scope="' + scope + '"]');

        $collapse.hasClass('in') ?
            $collapse.collapse('hide') :
            $collapse.collapse('show');
    }

    /**
     * @param {KeyboardEvent} e
     */
    onKeyDown(e) {
        let key = Espo.Utils.getKeyFromKeyEvent(e);

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

        Espo.Ui.notify(' ... ');

        let typeReal = this.getMetadata()
            .get('clientDefs.' + scope + '.additionalLayouts.' + type + '.type') || type;

        this.createView('content', 'views/admin/layouts/' + Espo.Utils.camelCaseToHyphen(typeReal), {
            fullSelector: '#layout-content',
            scope: scope,
            type: type,
            setId: this.setId,
        }, view => {
            this.renderLayoutHeader();
            view.render();
            Espo.Ui.notify(false);

            $(window).scrollTop(0);
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
        $('#layout-content').html(this.translate('selectLayout', 'messages', 'Admin'));
    }

    renderLayoutHeader() {
        let $header = $('#layout-header');

        if (!this.scope) {
            $header.html('');

            return;
        }

        let list = [];

        let separatorHtml = '<span class="breadcrumb-separator"><span class="chevron-right"></span></span>';

        if (!this.em) {
            list.push(
                $('<span>').text(this.translate(this.scope, 'scopeNames'))
            );
        }

        list.push(
            $('<span>').text(this.translateLayoutName(this.type, this.scope))
        );

        let html = list.map($item => $item.get(0).outerHTML).join(' ' + separatorHtml + ' ');

        $header.show().html(html);
    }

    updatePageTitle() {
        this.setPageTitle(this.getLanguage().translate('Layout Manager', 'labels', 'Admin'));
    }

    getHeaderHtml() {
        let separatorHtml = '<span class="breadcrumb-separator"><span class="chevron-right"></span></span>';

        let list = [];

        let $root = $('<a>')
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
                    $('<span>').text(this.translate('Layouts'), 'labels', 'EntityManager')
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
        let dataList = [];

        this.scopeList.forEach(scope =>{
            let item = {};

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
                !this.getMetadata().get(['clientDefs', scope, 'defaultSidePanelDisabled']) &&
                !this.getMetadata().get(['clientDefs', scope, 'defaultSidePanelFieldList'])
            ) {
                typeList.push('defaultSidePanel');
            }

            if (this.getMetadata().get(['clientDefs', scope, 'kanbanViewMode'])) {
                typeList.push('kanban');
            }

            let additionalLayouts = this.getMetadata().get(['clientDefs', scope, 'additionalLayouts']) || {};

            for (let aItem in additionalLayouts) {
                typeList.push(aItem);
            }

            typeList = typeList.filter(name => {
                return !this.getMetadata()
                    .get(['clientDefs', scope, 'layout' + Espo.Utils.upperCaseFirst(name) + 'Disabled'])
            });

            let typeDataList = [];

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
}

export default LayoutIndexView;

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

    /**
     * @param {MouseEvent} e
     */
    onLayoutLinkClick(e) {
        e.preventDefault();

        let scope = $(e.target).data('scope');
        let type = $(e.target).data('type');

        if (this.getView('content')) {
            if (this.scope === scope && this.type === type) {
                return;
            }
        }

        this.getRouter().checkConfirmLeaveOut(() => {
            $("#layouts-menu a.layout-link").removeClass('disabled');
            $(e.target).addClass('disabled');

            this.openLayout(scope, type);
        });
    }

    /**
     * @param {MouseEvent} e
     */
    onItemHeaderClick(e) {
        e.preventDefault();

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
        }, (view) => {
            this.renderLayoutHeader();
            view.render();
            Espo.Ui.notify(false);

            $(window).scrollTop(0);
        });
    }

    navigate(scope, type) {
        let url = '#Admin/layouts/scope=' + scope + '&type=' + type;

        if (this.em) {
            url += '&em=true';
        }

        this.getRouter().navigate(url, {trigger: false});
    }

    renderDefaultPage() {
        $("#layout-header").html('').hide();
        $("#layout-content").html(this.translate('selectLayout', 'messages', 'Admin'));
    }

    renderLayoutHeader() {
        let $header = $("#layout-header");

        if (!this.scope) {
            $header.html('');

            return;
        }

        let html = '';

        if (!this.em) {
            html += this.getLanguage().translate(this.scope, 'scopeNamesPlural') +
            " <span class=\"breadcrumb-separator\"><span class=\"chevron-right\"></span></span> ";
        }

        html += this.getLanguage().translate(this.type, 'layouts', 'Admin');

        $header.show().html(html);
    }

    updatePageTitle() {
        this.setPageTitle(this.getLanguage().translate('Layout Manager', 'labels', 'Admin'));
    }

    getHeaderHtml() {
        let separatorHtml = '<span class="breadcrumb-separator"><span class="chevron-right"></span></span>';

        let html = "<a href=\"#Admin\">" + this.translate('Administration') + "</a> " + separatorHtml + ' ';

        if (this.em) {
            html += "<a href=\"#Admin/entityManager\">" +
                this.translate('Entity Manager', 'labels', 'Admin') + "</a>";

            if (this.scope) {
                html += ' ' + separatorHtml + ' ' +
                    "<a href=\"#Admin/entityManager/scope=" + this.scope + "\">" +
                    this.translate(this.scope, 'scopeNames') + '</a>' +
                    ' ' + separatorHtml + ' ' + this.translate('Layouts', 'labels', 'EntityManager');
            }
        } else {
            html += this.translate('Layout Manager', 'labels', 'Admin');
        }

        return html;
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

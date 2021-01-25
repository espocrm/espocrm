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

define('views/admin/layouts/index', 'view', function (Dep) {

    return Dep.extend({

        template: 'admin/layouts/index',

        scopeList: null,

        typeList: [
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
        ],

        scope: null,

        type: null,

        baseUrl: '#Admin/layouts',

        data: function () {
            return {
                scopeList: this.scopeList,
                typeList: this.typeList,
                scope: this.scope,
                layoutScopeDataList: this.getLayoutScopeDataList(),
                headerHtml: this.getHeaderHtml(),
                em: this.em,
            };
        },

        events: {
            'click #layouts-menu a.layout-link': function (e) {
                e.preventDefault();

                var scope = $(e.currentTarget).data('scope');
                var type = $(e.currentTarget).data('type');
                if (this.getView('content')) {
                    if (this.scope == scope && this.type == type) {
                        return;
                    }
                }

                this.getRouter().checkConfirmLeaveOut(function () {
                    $("#layouts-menu a.layout-link").removeClass('disabled');
                    $(e.target).addClass('disabled');

                    this.openLayout(scope, type);
                }.bind(this));
            },
            'click a.accordion-toggle': function (e) {
                e.preventDefault();

                var $target = $(e.currentTarget);
                var scope = $target.data('scope');

                var $collapse = $('.collapse[data-scope="'+scope+'"]');

                if ($collapse.hasClass('in')) {
                    $collapse.collapse('hide');
                } else {
                    $collapse.collapse('show');
                }
            },
        },

        getLayoutScopeDataList: function () {
            var dataList = [];
            this.scopeList.forEach(function (scope) {
                var item = {};
                item.scope = scope;

                var typeList = Espo.Utils.clone(this.typeList);

                item.url = this.baseUrl + '/scope=' + scope;
                if (this.em) item.url += '&em=true';

                if (
                    this.getMetadata().get(['clientDefs', scope, 'bottomPanels', 'edit'])
                ) {
                    typeList.push('bottomPanelsEdit');
                }

                if (
                    !this.getMetadata().get(['clientDefs', scope, 'defaultSidePanelDisabled'])
                    &&
                    !this.getMetadata().get(['clientDefs', scope, 'defaultSidePanelFieldList'])
                ) {
                    typeList.push('defaultSidePanel');
                }

                if (this.getMetadata().get(['clientDefs', scope, 'kanbanViewMode'])) {
                    typeList.push('kanban');
                }

                var additionalLayouts = this.getMetadata().get(['clientDefs', scope, 'additionalLayouts']) || {};
                for (var aItem in additionalLayouts) {
                    typeList.push(aItem);
                }

                var typeList = typeList.filter(function (name) {
                    return !this.getMetadata().get(['clientDefs', scope, 'layout' + Espo.Utils.upperCaseFirst(name) + 'Disabled'])
                }, this);

                var typeDataList = [];
                typeList.forEach(function (type) {
                    var url = this.baseUrl + '/scope=' + scope + '&type=' + type;
                    if (this.em) url += '&em=true';
                    typeDataList.push({
                        type: type,
                        url: url,
                    });
                }, this);

                item.typeList = typeList;

                item.typeDataList = typeDataList;

                dataList.push(item);
            }, this);

            return dataList;
        },

        setup: function () {
            this.em = this.options.em || false;

            this.scope = this.options.scope || null;
            this.type = this.options.type || null;

            this.scopeList = [];

            var scopeFullList = this.getMetadata().getScopeList().sort(function (v1, v2) {
                return this.translate(v1, 'scopeNamesPlural').localeCompare(this.translate(v2, 'scopeNamesPlural'));
            }.bind(this));

            scopeFullList.forEach(function (scope) {
                if (this.getMetadata().get('scopes.' + scope + '.entity') &&
                    this.getMetadata().get('scopes.' + scope + '.layouts')) {
                    this.scopeList.push(scope);
                }
            }, this);

            if (this.em && this.scope) {
                this.scopeList = [this.scope];
            }

            this.on('after:render', function () {
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

        },

        openLayout: function (scope, type) {
            this.scope = scope;
            this.type = type;

            this.navigate(scope, type);

            Espo.Ui.notify(this.translate('loading', 'messages'));

            var typeReal = this.getMetadata().get('clientDefs.' + scope + '.additionalLayouts.' + type + '.type') || type;

            this.createView('content', 'views/admin/layouts/' + Espo.Utils.camelCaseToHyphen(typeReal), {
                el: '#layout-content',
                scope: scope,
                type: type,
                setId: this.setId,
            }, function (view) {
                this.renderLayoutHeader();
                view.render();
                this.notify(false);
                $(window).scrollTop(0);
            }.bind(this));
        },

        navigate: function (scope, type) {
            var url = '#Admin/layouts/scope=' + scope + '&type=' + type;

            if (this.em) {
                url += '&em=true';
            }

            this.getRouter().navigate(url, {trigger: false});
        },

        renderDefaultPage: function () {
            $("#layout-header").html('').hide();
            $("#layout-content").html(this.translate('selectLayout', 'messages', 'Admin'));
        },

        renderLayoutHeader: function () {
            if (!this.scope) {
                $("#layout-header").html('');
                return;
            }

            var html = '';

            if (!this.em) {
                html += this.getLanguage().translate(this.scope, 'scopeNamesPlural') +
                " <span class=\"breadcrumb-separator\"><span class=\"chevron-right\"></span></span> ";
            }

            html += this.getLanguage().translate(this.type, 'layouts', 'Admin');

            $("#layout-header").show().html(html);
        },

        updatePageTitle: function () {
            this.setPageTitle(this.getLanguage().translate('Layout Manager', 'labels', 'Admin'));
        },

        getHeaderHtml: function () {
            var separatorHtml = '<span class="breadcrumb-separator"><span class="chevron-right"></span></span>';

            var html = "<a href=\"#Admin\">"+this.translate('Administration')+"</a> " + separatorHtml + ' ';

            if (this.em) {
                html += "<a href=\"#Admin/entityManager\">" + this.translate('Entity Manager', 'labels', 'Admin') + "</a>";

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
        },

    });
});

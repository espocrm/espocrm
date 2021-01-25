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

define('views/admin/label-manager/index', 'view', function (Dep) {

    return Dep.extend({

        template: 'admin/label-manager/index',

        scopeList: null,

        scope: null,

        language: null,

        languageList: null,

        data: function () {
            return {
                scopeList: this.scopeList,
                languageList: this.languageList,
                scope: this.scope,
                language: this.language
            };
        },

        events: {
            'click [data-action="selectScope"]': function (e) {
                var scope = $(e.currentTarget).data('name');
                this.getRouter().checkConfirmLeaveOut(function () {
                    this.selectScope(scope);
                }, this);
            },
            'change select[data-name="language"]': function (e) {
                var language = $(e.currentTarget).val();
                this.getRouter().checkConfirmLeaveOut(function () {
                    this.selectLanguage(language);
                }, this);
            }
        },

        setup: function () {
            this.languageList = this.getMetadata().get(['app', 'language', 'list']) || ['en_US'];
            this.languageList.sort(function (v1, v2) {
                return this.getLanguage().translateOption(v1, 'language').localeCompare(this.getLanguage().translateOption(v2, 'language'));
            }.bind(this));

            this.wait(true);

            this.ajaxPostRequest('LabelManager/action/getScopeList').then(function (scopeList) {
                this.scopeList = scopeList;

                this.scopeList.sort(function (v1, v2) {
                    return this.translate(v1, 'scopeNamesPlural').localeCompare(this.translate(v2, 'scopeNamesPlural'));
                }.bind(this));

                this.scopeList = this.scopeList.filter(function (scope) {
                    if (scope === 'Global') return;
                    if (this.getMetadata().get(['scopes', scope])) {
                        if (this.getMetadata().get(['scopes', scope, 'disabled'])) return;
                    }
                    return true;
                }, this);

                this.scopeList.unshift('Global');

                this.wait(false);
            }.bind(this));


            this.scope = this.options.scope || 'Global';
            this.language = this.options.language || this.getConfig().get('language');

            this.once('after:render', function () {
                this.selectScope(this.scope, true);
            }, this);
        },

        selectLanguage: function (language) {
            this.language = language;

            if (this.scope) {
                this.getRouter().navigate('#Admin/labelManager/scope=' + this.scope + '&language=' + this.language, {trigger: false});
            } else {
                this.getRouter().navigate('#Admin/labelManager/language=' + this.language, {trigger: false});
            }

            this.createRecordView();
        },

        selectScope: function (scope, skipRouter) {
            this.scope = scope;

            if (!skipRouter) {
                this.getRouter().navigate('#Admin/labelManager/scope=' + scope + '&language=' + this.language, {trigger: false});
            }

            this.$el.find('[data-action="selectScope"]').removeClass('disabled').removeAttr('disabled');
            this.$el.find('[data-name="'+scope+'"][data-action="selectScope"]').addClass('disabled').attr('disabled', 'disabled');

            this.createRecordView();
        },

        createRecordView: function () {
            Espo.Ui.notify(this.translate('loading', 'messages'));

            this.createView('record', 'views/admin/label-manager/edit', {
                el: this.getSelector() + ' .language-record',
                scope: this.scope,
                language: this.language,
            }, function (view) {
                view.render();
                Espo.Ui.notify(false);
                $(window).scrollTop(0);

            }, this);
        },

        updatePageTitle: function () {
            this.setPageTitle(this.getLanguage().translate('Label Manager', 'labels', 'Admin'));
        },
    });
});



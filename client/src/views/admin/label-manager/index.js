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
import Select from 'ui/select';

class LabelManagerView extends  View {

    template = 'admin/label-manager/index'

    scopeList = null
    scope = null
    language = null
    languageList = null

    events = {
        /** @this LabelManagerView */
        'click [data-action="selectScope"]': function (e) {
            const scope = $(e.currentTarget).data('name');

            this.getRouter().checkConfirmLeaveOut(() => {
                this.selectScope(scope);
            });
        },
        /** @this LabelManagerView */
        'change select[data-name="language"]': function (e) {
            const language = $(e.currentTarget).val();

            this.getRouter().checkConfirmLeaveOut(() => {
                this.selectLanguage(language);
            });
        }
    }

    data() {
        return {
            scopeList: this.scopeList,
            languageList: this.languageList,
            scope: this.scope,
            language: this.language,
        };
    }

    setup() {
        this.languageList = this.getMetadata().get(['app', 'language', 'list']) || ['en_US'];

        this.languageList.sort((v1, v2) => {
            return this.getLanguage().translateOption(v1, 'language')
                .localeCompare(this.getLanguage().translateOption(v2, 'language'));
        });

        this.wait(true);

        Espo.Ajax.postRequest('LabelManager/action/getScopeList').then(scopeList => {
            this.scopeList = scopeList;

            this.scopeList.sort((v1, v2) => {
                return this.translate(v1, 'scopeNamesPlural')
                    .localeCompare(this.translate(v2, 'scopeNamesPlural'));
            });

            this.scopeList = this.scopeList.filter(scope => {
                if (scope === 'Global') {
                    return;
                }

                if (this.getMetadata().get(['scopes', scope])) {
                    if (this.getMetadata().get(['scopes', scope, 'disabled'])) {
                        return;
                    }
                }

                return true;
            });

            this.scopeList.unshift('Global');

            this.wait(false);
        });

        this.scope = this.options.scope || 'Global';
        this.language = this.options.language || this.getConfig().get('language');

        this.once('after:render', () => {
            this.selectScope(this.scope, true);
        });
    }

    afterRender() {
        Select.init(
            this.element.querySelector(`select[data-name="language"]`)
        );
    }

    selectLanguage(language) {
        this.language = language;

        if (this.scope) {
            this.getRouter().navigate(
                '#Admin/labelManager/scope=' + this.scope + '&language=' + this.language,
                {trigger: false}
            );
        } else {
            this.getRouter().navigate('#Admin/labelManager/language=' + this.language, {trigger: false});
        }

        this.createRecordView();
    }

    selectScope(scope, skipRouter) {
        this.scope = scope;

        if (!skipRouter) {
            this.getRouter().navigate('#Admin/labelManager/scope=' + scope + '&language=' + this.language,
                {trigger: false});
        }

        this.$el.find('[data-action="selectScope"]')
            .removeClass('disabled')
            .removeAttr('disabled');

        this.$el.find('[data-name="' + scope + '"][data-action="selectScope"]')
            .addClass('disabled')
            .attr('disabled', 'disabled');

        this.createRecordView();
    }

    createRecordView() {
        Espo.Ui.notifyWait();

        this.createView('record', 'views/admin/label-manager/edit', {
            selector: '.language-record',
            scope: this.scope,
            language: this.language,
        }, view => {
            view.render();

            Espo.Ui.notify(false);

            $(window).scrollTop(0);
        });
    }

    updatePageTitle() {
        this.setPageTitle(this.getLanguage().translate('Label Manager', 'labels', 'Admin'));
    }
}

export default LabelManagerView;

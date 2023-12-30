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

 define('crm:views/activities/list', ['views/list-related'], function (Dep) {

    return Dep.extend({

        createButton: false,

        unlinkDisabled: true,

        filtersDisabled: true,

        setup: function () {
            this.rowActionsView = 'views/record/row-actions/default';

            Dep.prototype.setup.call(this);

            this.type = this.options.type;
        },

        getHeader: function () {
            let name = this.model.get('name') || this.model.id;

            let recordUrl = '#' + this.scope  + '/view/' + this.model.id;

            let $name =
                $('<a>')
                    .attr('href', recordUrl)
                    .addClass('font-size-flexible title')
                    .text(name);

            if (this.model.get('deleted')) {
                $name.css('text-decoration', 'line-through');
            }

            let headerIconHtml = this.getHelper().getScopeColorIconHtml(this.foreignScope);
            let scopeLabel = this.getLanguage().translate(this.scope, 'scopeNamesPlural');

            let $root = $('<span>').text(scopeLabel);

            if (!this.rootLinkDisabled) {
                $root = $('<span>')
                    .append(
                        $('<a>')
                            .attr('href', '#' + this.scope)
                            .addClass('action')
                            .attr('data-action', 'navigateToRoot')
                            .text(scopeLabel)
                    );
            }

            if (headerIconHtml) {
                $root.prepend(headerIconHtml);
            }

            let linkLabel = this.type === 'history' ? this.translate('History') : this.translate('Activities');

            let $link = $('<span>').text(linkLabel);

            let $target = $('<span>').text(this.translate(this.foreignScope, 'scopeNamesPlural'));

            return this.buildHeaderHtml([
                $root,
                $name,
                $link,
                $target,
            ]);
        },

        /**
         * @inheritDoc
         */
        updatePageTitle: function () {
            this.setPageTitle(this.translate(this.foreignScope, 'scopeNamesPlural'));
        },
    });
});

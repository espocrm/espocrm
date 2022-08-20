/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/edit', ['views/main'], function (Dep) {

    /**
     * An edit view page.
     *
     * @class
     * @name Class
     * @extends module:views/main.Class
     * @memberOf module:views/edit
     */
    return Dep.extend(/** @lends module:views/edit.Class# */{

        /**
         * @inheritDoc
         */
        template: 'edit',

        /**
         * @inheritDoc
         */
        scope: null,

        /**
         * @inheritDoc
         */
        name: 'Edit',

        /**
         * @inheritDoc
         */
        menu: null,

        /**
         * @inheritDoc
         */
        optionsToPass: [
            'returnUrl',
            'returnDispatchParams',
            'attributes',
            'rootUrl',
            'duplicateSourceId',
            'returnAfterCreate',
        ],

        /**
         * A header view name.
         *
         * @type {string}
         */
        headerView: 'views/header',

        /**
         * A record view name.
         *
         * @type {string}
         */
        recordView: 'views/record/edit',

        /**
         * A root breadcrumb item not to be a link.
         *
         * @type {boolean}
         */
        rootLinkDisabled: false,

        /**
         * @inheritDoc
         */
        setup: function () {
            this.headerView = this.options.headerView || this.headerView;
            this.recordView = this.options.recordView || this.recordView;

            this.setupHeader();
            this.setupRecord();

            this.getHelper().processSetupHandlers(this, 'edit');
        },

        /**
         * Set up a header.
         */
        setupHeader: function () {
            this.createView('header', this.headerView, {
                model: this.model,
                el: '#main > .header',
                scope: this.scope,
            });
        },

        /**
         * Set up a record.
         */
        setupRecord: function () {
            let o = {
                model: this.model,
                el: '#main > .record',
                scope: this.scope,
                shortcutKeysEnabled: true,
            };

            this.optionsToPass.forEach(option => {
                o[option] = this.options[option];
            });

            let params = this.options.params || {};

            if (params.rootUrl) {
                o.rootUrl = params.rootUrl;
            }

            if (params.focusForCreate) {
                o.focusForCreate = true;
            }

            return this.createView('record', this.getRecordViewName(), o);
        },

        /**
         * Get a record view name.
         *
         * @returns {string}
         */
        getRecordViewName: function () {
            return this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.edit') || this.recordView;
        },

        /**
         * @inheritDoc
         */
        getHeader: function () {
            let headerIconHtml = this.getHeaderIconHtml();
            let rootUrl = this.options.rootUrl || this.options.params.rootUrl || '#' + this.scope;
            let scopeLabel = this.getLanguage().translate(this.scope, 'scopeNamesPlural');

            let $root = $('<span>').text(scopeLabel);

            if (!this.options.noHeaderLinks && !this.rootLinkDisabled) {
                $root =
                    $('<span>')
                        .append(
                            $('<a>')
                                .attr('href', rootUrl)
                                .addClass('action')
                                .attr('data-action', 'navigateToRoot')
                                .text(scopeLabel)
                        );
            }

            if (headerIconHtml) {
                $root.prepend(headerIconHtml);
            }

            if (this.model.isNew()) {
                let $create = $('<span>').text(this.getLanguage().translate('create'));

                return this.buildHeaderHtml([$root, $create]);
            }

            let name = this.model.get('name') || this.model.id;

            let $name = $('<span>').text(name);

            if (!this.options.noHeaderLinks) {
                let url = '#' + this.scope + '/view/' + this.model.id;

                $name =
                    $('<a>')
                        .attr('href', url)
                        .addClass('action')
                        .append($name);
            }

            return this.buildHeaderHtml([$root, $name]);
        },

        /**
         * @inheritDoc
         */
        updatePageTitle: function () {
            var title;

            if (this.model.isNew()) {
                title = this.getLanguage().translate('Create') + ' ' +
                    this.getLanguage().translate(this.scope, 'scopeNames');
            }
            else {
                var name = this.model.get('name');

                if (name) {
                    title = name;
                }
                else {
                    title = this.getLanguage().translate(this.scope, 'scopeNames')
                }
            }

            this.setPageTitle(title);
        },
    });
});

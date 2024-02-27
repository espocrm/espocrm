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

/** @module module:views/edit */

import MainView from 'views/main';

/**
 * An edit view.
 */
class EditView extends MainView {

    /** @inheritDoc */
    template = 'edit'

    /** @inheritDoc */
    name = 'Edit'

    /** @inheritDoc */
    optionsToPass = [
        'returnUrl',
        'returnDispatchParams',
        'attributes',
        'rootUrl',
        'duplicateSourceId',
        'returnAfterCreate',
        'highlightFieldList',
    ]

    /**
     * A header view name.
     *
     * @type {string}
     */
    headerView = 'views/header'

    /**
     * A record view name.
     *
     * @type {string}
     */
    recordView = 'views/record/edit'

    /**
     * A root breadcrumb item not to be a link.
     *
     * @type {boolean}
     */
    rootLinkDisabled = false

    /** @inheritDoc */
    setup() {
        this.headerView = this.options.headerView || this.headerView;
        this.recordView = this.options.recordView || this.recordView;

        this.setupHeader();
        this.setupRecord();
    }

    /** @inheritDoc */
    setupFinal() {
        super.setupFinal();

        this.wait(
            this.getHelper().processSetupHandlers(this, 'edit')
        );
    }

    /**
     * Set up a header.
     */
    setupHeader() {
        this.createView('header', this.headerView, {
            model: this.model,
            fullSelector: '#main > .header',
            scope: this.scope,
        });
    }

    /**
     * Set up a record.
     */
    setupRecord() {
        const o = {
            model: this.model,
            fullSelector: '#main > .record',
            scope: this.scope,
            shortcutKeysEnabled: true,
        };

        this.optionsToPass.forEach(option => {
            o[option] = this.options[option];
        });

        const params = this.options.params || {};

        if (params.rootUrl) {
            o.rootUrl = params.rootUrl;
        }

        if (params.focusForCreate) {
            o.focusForCreate = true;
        }

        return this.createView('record', this.getRecordViewName(), o);
    }



    /**
     * @return {module:views/record/edit}
     */
    getRecordView() {
        return this.getView('record');
    }

    /**
     * Get a record view name.
     *
     * @returns {string}
     */
    getRecordViewName() {
        return this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.edit') || this.recordView;
    }

    /** @inheritDoc */
    getHeader() {
        const headerIconHtml = this.getHeaderIconHtml();
        const rootUrl = this.options.rootUrl || this.options.params.rootUrl || '#' + this.scope;
        const scopeLabel = this.getLanguage().translate(this.scope, 'scopeNamesPlural');

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
            const $create = $('<span>').text(this.getLanguage().translate('create'));

            return this.buildHeaderHtml([$root, $create]);
        }

        const name = this.model.get('name') || this.model.id;

        let $name = $('<span>').text(name);

        if (!this.options.noHeaderLinks) {
            const url = '#' + this.scope + '/view/' + this.model.id;

            $name =
                $('<a>')
                    .attr('href', url)
                    .addClass('action')
                    .append($name);
        }

        return this.buildHeaderHtml([$root, $name]);
    }

    /** @inheritDoc */
    updatePageTitle() {
        if (this.model.isNew()) {
            const title = this.getLanguage().translate('Create') + ' ' +
                this.getLanguage().translate(this.scope, 'scopeNames');

            this.setPageTitle(title);

            return;
        }

        const name = this.model.get('name');

        const title = name ? name : this.getLanguage().translate(this.scope, 'scopeNames');

        this.setPageTitle(title);
    }
}

export default EditView;

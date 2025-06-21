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

    /**
     * A root URL.
     *
     * @type {string}
     */
    rootUrl

    /**
     * @private
     * @type {string}
     */
    nameAttribute

    /** @inheritDoc */
    setup() {
        this.headerView = this.options.headerView || this.headerView;
        this.recordView = this.options.recordView || this.recordView;

        this.rootUrl = this.options.rootUrl || this.options.params.rootUrl || '#' + this.scope;

        this.nameAttribute = this.getMetadata().get(`clientDefs.${this.entityType}.nameAttribute`) || 'name';

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

        if (params.rootData) {
            o.rootData = params.rootData;
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
        const scopeLabel = this.getLanguage().translate(this.scope, 'scopeNamesPlural');

        let root = document.createElement('span');
        root.text = scopeLabel;
        root.style.userSelect = 'none';

        if (!this.options.noHeaderLinks && !this.rootLinkDisabled) {
            const a = document.createElement('a');
            a.href = this.rootUrl;
            a.classList.add('action');
            a.dataset.action = 'navigateToRoot';
            a.text = scopeLabel;

            root = document.createElement('span');
            root.style.userSelect = 'none';
            root.append(a);
        }

        const iconHtml = this.getHeaderIconHtml();

        if (iconHtml) {
            root.insertAdjacentHTML('afterbegin', iconHtml);
        }

        if (this.model.isNew()) {
            const create = document.createElement('span');
            create.textContent = this.getLanguage().translate('create');
            create.style.userSelect = 'none';

            return this.buildHeaderHtml([root, create]);
        }

        const name = this.model.attributes[this.nameAttribute] || this.model.id;

        let title = document.createElement('span');
        title.textContent = name;

        if (!this.options.noHeaderLinks) {
            const url = `#${this.scope}/view/${this.model.id}`;

            const a = document.createElement('a');
            a.href = url;
            a.classList.add('action');

            a.append(title);

            title = a;
        }

        return this.buildHeaderHtml([root, title]);
    }

    /** @inheritDoc */
    updatePageTitle() {
        if (this.model.isNew()) {
            const title = this.getLanguage().translate('Create') + ' ' +
                this.getLanguage().translate(this.scope, 'scopeNames');

            this.setPageTitle(title);

            return;
        }

        const name = this.model.attributes[this.nameAttribute];

        const title = name ? name : this.getLanguage().translate(this.scope, 'scopeNames');

        this.setPageTitle(title);
    }

    setupReuse(params) {
        const recordView = this.getRecordView();

        if (!recordView) {
            return;
        }

        if (!recordView.setupReuse) {
            return;
        }

        recordView.setupReuse();
    }
}

export default EditView;

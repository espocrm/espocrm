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

/** @module module:views/detail */

import MainView from 'views/main';
import DetailModesView from 'views/detail/modes';

/**
 * A detail view.
 */
class DetailView extends MainView {

    /** @inheritDoc */
    template = 'detail'
    /** @inheritDoc */
    name = 'Detail'

    /** @inheritDoc */
    optionsToPass = [
        'attributes',
        'returnUrl',
        'returnDispatchParams',
        'rootUrl',
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
    recordView = 'views/record/detail'

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
    rootUrl = ''

    /**
     * Is return.
     *
     * @protected
     */
    isReturn = false

    /** @inheritDoc */
    shortcutKeys = {}

    /**
     * An entity type.
     *
     * @type {string}
     */
    entityType

    /**
     * A default view mode.
     *
     * @protected
     */
    defaultViewMode = 'detail'

    /**
     * A view mode.
     *
     * @protected
     * @type {string}
     */
    viewMode

    /**
     * @private
     * @type {string}
     */
    viewModeIsStorable

    /**
     * @private
     * @type {boolean}
     */
    hasMultipleModes = false

    /**
     * @private
     * @type {import('views/detail/modes').default}
     */
    modesView

    /**
     * A 'detail' view mode.
     * @const
     */
    MODE_DETAIL = 'detail'

    /**
     * @private
     * @type {string}
     */
    nameAttribute

    /** @inheritDoc */
    setup() {
        super.setup();

        this.entityType = this.model.entityType || this.model.name;

        this.headerView = this.options.headerView || this.headerView;
        this.recordView = this.options.recordView || this.recordView;

        this.rootUrl = this.options.rootUrl || this.options.params.rootUrl || '#' + this.scope;
        this.isReturn = this.options.isReturn || this.options.params.isReturn || false;

        this.nameAttribute = this.getMetadata().get(`clientDefs.${this.entityType}.nameAttribute`) || 'name';

        this.setupModes();
        this.setupHeader();
        this.setupRecord();
        this.setupPageTitle();
        this.initFollowButtons();
        this.initStarButtons();
        this.initRedirect();

        this.addActionHandler('fullRefresh', () => this.actionFullRefresh());
    }

    /** @inheritDoc */
    setupFinal() {
        super.setupFinal();

        this.wait(
            this.getHelper().processSetupHandlers(this, 'detail')
        );
    }

    /** @private */
    initRedirect() {
        if (!this.options.params.isAfterCreate) {
            return;
        }

        const redirect = () => {
            Espo.Ui.success(this.translate('Created'));

            setTimeout(() => {
                this.getRouter().navigate(this.rootUrl, {trigger: true});
            }, 1000)
        };

        if (
            this.model.lastSyncPromise &&
            this.model.lastSyncPromise.getStatus() === 403
        ) {
            redirect();

            return;
        }

        this.listenToOnce(this.model, 'fetch-forbidden', () => redirect())
    }

    /**
     * Set up a page title.
     */
    setupPageTitle() {
        this.listenTo(this.model, 'after:save', () => {
            this.updatePageTitle();
        });

        this.listenTo(this.model, 'sync', model => {
            if (model && model.hasChanged(this.nameAttribute)) {
                this.updatePageTitle();
            }
        });
    }

    /**
     * Set up a header.
     */
    setupHeader() {
        this.createView('header', this.headerView, {
            model: this.model,
            fullSelector: '#main > .header',
            scope: this.scope,
            fontSizeFlexible: true,
        });

        this.listenTo(this.model, 'sync', model => {
            if (model && model.hasChanged(this.nameAttribute)) {
                if (this.getView('header')) {
                    this.getView('header').reRender();
                }
            }
        });
    }

    /**
     * Set up modes.
     */
    setupModes() {
        this.defaultViewMode = this.options.defaultViewMode ||
            this.getMetadata().get(`clientDefs.${this.scope}.detailDefaultViewMode`) ||
            this.defaultViewMode;

        this.viewMode = this.viewMode || this.defaultViewMode;

        const viewModeList = this.options.viewModeList || this.viewModeList ||
            this.getMetadata().get(`clientDefs.${this.scope}.detailViewModeList`);

        this.viewModeList = viewModeList ? viewModeList : [this.MODE_DETAIL];

        this.viewModeIsStorable = this.viewModeIsStorable !== undefined ?
            this.viewModeIsStorable :
            this.getMetadata().get(`clientDefs.${this.scope}.detailViewModeIsStorable`);

        this.hasMultipleModes = this.viewModeList.length > 1;

        if (this.viewModeIsStorable && this.hasMultipleModes) {
            let viewMode = null;

            const modeKey = this.getViewModeKey();

            if (this.getStorage().has('state', modeKey)) {
                const storedViewMode = this.getStorage().get('state', modeKey);

                if (storedViewMode && this.viewModeList.includes(storedViewMode)) {
                    viewMode = storedViewMode;
                }
            }

            if (!viewMode) {
                viewMode = this.defaultViewMode;
            }

            this.viewMode = /** @type {string} */viewMode;
        }

        if (this.hasMultipleModes) {
            this.addActionHandler('switchMode', (e, target) => this.switchViewMode(target.dataset.value));

            this.modesView = new DetailModesView({
                mode: this.viewMode,
                modeList: this.viewModeList,
                scope: this.scope,
            });

            this.assignView('modes', this.modesView, '.modes');
        }
    }

    /**
     * @private
     * @return {string}
     */
    getViewModeKey() {
        return `detailViewMode-${this.scope}-${this.model.id}}`;
    }

    /**
     * Set up a record.
     *
     * @return {Promise<import('view').default>}
     */
    setupRecord() {
        const o = {
            model: this.model,
            fullSelector: '#main > .record',
            scope: this.scope,
            shortcutKeysEnabled: true,
            isReturn: this.isReturn,
        };

        this.optionsToPass.forEach((option) => {
            o[option] = this.options[option];
        });

        if (this.options.params && this.options.params.rootUrl) {
            o.rootUrl = this.options.params.rootUrl;
        }

        if (this.options.params && this.options.params.rootData) {
            o.rootData = this.options.params.rootData;
        }

        if (this.model.get('deleted')) {
            o.readOnly = true;
        }

        // noinspection JSValidateTypes
        return this.createView('record', this.getRecordViewName(), o, view => {
            this.listenTo(view, 'after:mode-change', mode => {
                // Mode change should also re-render the header what the methods do.
                mode === 'edit' ?
                    this.hideAllHeaderActionItems() :
                    this.showAllHeaderActionItems();
            });

            if (this.modesView) {
                this.listenTo(view, 'after:set-detail-mode', () => this.modesView.enable());
                this.listenTo(view, 'after:set-edit-mode', () => this.modesView.disable());
            }
        });
    }

    /**
     * Get a record view name.
     *
     * @returns {string}
     */
    getRecordViewName() {
        return this.getMetadata().get(`clientDefs.${this.scope}.recordViews.${this.viewMode}`) || this.recordView;
    }

    /**
     * Switch a view mode.
     *
     * @param {string} mode
     */
    switchViewMode(mode) {
        this.clearView('record');
        this.setViewMode(mode, true);

        Espo.Ui.notifyWait();

        if (this.modesView) {
            this.modesView.changeMode(mode);
        }

        this.setupRecord().then(view => {
            view.render()
                .then(() => Espo.Ui.notify(false));
        });
    }

    /**
     * Set a view mode.
     *
     * @param {string} mode A mode.
     * @param {boolean} [toStore=false] To preserve a mode being set.
     */
    setViewMode(mode, toStore) {
        this.viewMode = mode;

        if (toStore && this.viewModeIsStorable) {
            const modeKey = this.getViewModeKey();

            this.getStorage().set('state', modeKey, mode);
        }
    }

    /** @private */
    initStarButtons() {
        if (!this.getMetadata().get(`scopes.${this.scope}.stars`)) {
            return;
        }

        this.addStarButtons();
        this.listenTo(this.model, 'change:isStarred', () => this.controlStarButtons());
    }

    /** @private */
    addStarButtons() {
        const isStarred = this.model.get('isStarred');

        this.addMenuItem('buttons', {
            name: 'unstar',
            iconHtml: '<span class="fas fa-star fa-sm text-warning"></span>',
            className: 'btn-s-wide',
            text: this.translate('Starred'),
            hidden: !isStarred,
            //title: this.translate('Unstar'),
            onClick: () => this.actionUnstar(),
        }, true);

        this.addMenuItem('buttons', {
            name: 'star',
            iconHtml: '<span class="far fa-star fa-sm"></span>',
            className: 'btn-s-wide',
            text: this.translate('Star'),
            //title: this.translate('Star'),
            hidden: isStarred || !this.model.has('isStarred'),
            onClick: () => this.actionStar(),
        }, true);
    }

    /** @private */
    controlStarButtons() {
        const isStarred = this.model.get('isStarred');

        if (isStarred) {
            this.hideHeaderActionItem('star');
            this.showHeaderActionItem('unstar');

            return;
        }

        this.hideHeaderActionItem('unstar');
        this.showHeaderActionItem('star');
    }

    /**
     * Action 'star'.
     */
    actionStar() {
        this.disableMenuItem('star');

        Espo.Ajax
            .putRequest(`${this.entityType}/${this.model.id}/starSubscription`)
            .then(() => {
                this.hideHeaderActionItem('star');

                this.model.set('isStarred', true, {sync: true});
            })
            .finally(() => this.enableMenuItem('star'));
    }

    /**
     * Action 'unstar'.
     */
    actionUnstar() {
        this.disableMenuItem('unstar');

        Espo.Ajax
            .deleteRequest(`${this.entityType}/${this.model.id}/starSubscription`)
            .then(() => {
                this.hideHeaderActionItem('unstar');

                this.model.set('isStarred', false, {sync: true});
            })
            .finally(() => this.enableMenuItem('unstar'));
    }

    /** @private */
    initFollowButtons() {
        if (!this.getMetadata().get(['scopes', this.scope, 'stream'])) {
            return;
        }

        this.addFollowButtons();

        this.listenTo(this.model, 'change:isFollowed', () => {
            this.controlFollowButtons();
        });
    }

    /** @private */
    addFollowButtons() {
        const isFollowed = this.model.get('isFollowed');

        this.addMenuItem('buttons', {
            name: 'unfollow',
            label: 'Followed',
            style: 'success',
            action: 'unfollow',
            hidden: !isFollowed,
        }, true);

        this.addMenuItem('buttons', {
            name: 'follow',
            label: 'Follow',
            style: 'default',
            iconHtml: '<span class="fas fa-rss fa-sm"></span>',
            text: this.translate('Follow'),
            action: 'follow',
            hidden: isFollowed ||
                !this.model.has('isFollowed') ||
                !this.getAcl().checkModel(this.model, 'stream'),
        }, true);
    }

    /** @private */
    controlFollowButtons() {
        const isFollowed = this.model.get('isFollowed');

        if (isFollowed) {
            this.hideHeaderActionItem('follow');
            this.showHeaderActionItem('unfollow');

            return;
        }

        this.hideHeaderActionItem('unfollow');

        if (this.getAcl().checkModel(this.model, 'stream')) {
            this.showHeaderActionItem('follow');
        }
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Action 'follow'.
     */
    actionFollow() {
        this.disableMenuItem('follow');

        Espo.Ajax
            .putRequest(this.entityType + '/' + this.model.id + '/subscription')
            .then(() => {
                this.hideHeaderActionItem('follow');

                this.model.set('isFollowed', true, {sync: true});

                this.enableMenuItem('follow');
            })
            .catch(() => {
                this.enableMenuItem('follow');
            });
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Action 'unfollow'.
     */
    actionUnfollow() {
        this.disableMenuItem('unfollow');

        Espo.Ajax
            .deleteRequest(this.entityType + '/' + this.model.id + '/subscription')
            .then(() => {
                this.hideHeaderActionItem('unfollow');

                this.model.set('isFollowed', false, {sync: true});

                this.enableMenuItem('unfollow');
            })
            .catch(() => {
                this.enableMenuItem('unfollow');
            });
    }

    /**
     * @inheritDoc
     */
    getHeader() {
        const name = this.model.attributes[this.nameAttribute] || this.model.id;

        const title = document.createElement('span');
        title.classList.add('font-size-flexible', 'title')
        title.textContent = name;

        if (this.model.attributes.deleted) {
            title.style.textDecoration = 'line-through';
        }

        if (this.getRecordMode() === 'detail') {
            title.title = this.translate('clickToRefresh', 'messages');
            title.dataset.action = 'fullRefresh';
            title.style.cursor = 'pointer';
        }

        const scopeLabel = this.getLanguage().translate(this.scope, 'scopeNamesPlural');

        let root = document.createElement('span');
        root.text = scopeLabel;
        root.style.userSelect = 'none';

        if (!this.rootLinkDisabled) {
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

        return this.buildHeaderHtml([
            root,
            title,
        ]);
    }

    /**
     * @inheritDoc
     */
    updatePageTitle() {
        if (this.model.has(this.nameAttribute)) {
            this.setPageTitle(this.model.attributes[this.nameAttribute] || this.model.id);

            return;
        }

        super.updatePageTitle();
    }

    /**
     * @return {module:views/record/detail}
     */
    getRecordView() {
        return this.getView('record');
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @param {string} name A relationship name.
     * @deprecated As of v8.4.
     */
    updateRelationshipPanel(name) {
        this.model.trigger(`update-related:${name}`);

        console.warn('updateRelationshipPanel method is deprecated.');
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Action 'duplicate'.
     */
    actionDuplicate() {
        Espo.Ui.notifyWait();

        Espo.Ajax
            .postRequest(this.scope + '/action/getDuplicateAttributes', {id: this.model.id})
            .then(attributes => {
                Espo.Ui.notify(false);

                const url = '#' + this.scope + '/create';

                this.getRouter().dispatch(this.scope, 'create', {
                    attributes: attributes,
                    returnUrl: this.getRouter().getCurrentUrl(),
                    options: {
                        duplicateSourceId: this.model.id,
                    },
                });

                this.getRouter().navigate(url, {trigger: false});
            });
    }

    /**
     * @protected
     */
    hideAllHeaderActionItems() {
        if (!this.getHeaderView()) {
            return;
        }

        this.getHeaderView().hideAllMenuItems();
    }

    /**
     * @protected
     */
    showAllHeaderActionItems() {
        if (!this.getHeaderView()) {
            return;
        }

        this.getHeaderView().showAllActionItems();
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Hide a view mode.
     *
     * @param {string} mode
     * @since 8.4.0
     */
    hideViewMode(mode) {
        if (!this.modesView) {
            return;
        }

        this.modesView.hideMode(mode);
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Show a view mode.
     *
     * @param {string} mode
     * @since 8.4.0
     */
    showViewMode(mode) {
        if (!this.modesView) {
            return;
        }

        this.modesView.showMode(mode);
    }

    /**
     * @protected
     */
    async actionFullRefresh() {
        if (this.getRecordMode() === 'edit') {
            return;
        }

        Espo.Ui.notifyWait();

        await this.model.fetch();

        this.model.trigger('update-all');

        Espo.Ui.notify();
    }

    /**
     * @private
     * @return {'detail'|'edit'}
     */
    getRecordMode() {
        if (this.getRecordView().getMode) {
            return this.getRecordView().getMode();
        }

        return 'detail';
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

export default DetailView;

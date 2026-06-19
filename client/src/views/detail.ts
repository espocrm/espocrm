/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

import MainView, {MainViewOptions} from 'views/main';
import DetailModesView from 'views/detail/modes';
import Model from 'model';
import Ui from 'ui';
import type View from 'view';
import Ajax from 'ajax';

export interface DetailViewSchema {
    model: Model;
    options: DetailViewOptions;
}

export interface DetailViewOptions extends MainViewOptions {
    defaultViewMode?: string;
    rootUrl?: string;
    params?: {
        rootUrl?: string;
        isReturn?: boolean;
        isAfterCreate?: boolean;
        rootData?: Record<string, unknown>;
    };
    recordView?: string;
}

type RecordView = View & {
    setupReuse?: () => void;
    getMode: () => 'detail' | 'edit';
    blockUpdateWebSocket?: (toUnblock: boolean) => void;
}

/**
 * A detail view.
 */
class DetailView<S extends DetailViewSchema = DetailViewSchema> extends MainView<S> {

    protected template = 'detail'

    readonly name: string = 'Detail'

    protected optionsToPass: string[] = [
        'attributes',
        'returnUrl',
        'returnDispatchParams',
        'rootUrl',
    ]

    /**
     * A header view name.
     */
    protected headerView: string = 'views/header'

    /**
     * A record view name.
     */
    protected recordView: string = 'views/record/detail'

    /**
     * A root breadcrumb item not to be a link.
     */
    protected rootLinkDisabled: boolean = false

    /**
     * A root URL.
     */
    protected rootUrl: string

    /**
     * Is return.
     */
    protected isReturn: boolean = false

    /**
     * A shortcut-key => action map.
     */
    protected shortcutKeys: (Record<string, (event: KeyboardEvent) => void>) | null = null

    /**
     * An entity type.
     */
    protected entityType: string

    /**
     * A default view mode.
     */
    protected defaultViewMode: string = 'detail'

    /**
     * A view mode.
     */
    protected viewMode: string

    private viewModeIsStorable: boolean

    private hasMultipleModes: boolean = false

    private modesView: DetailModesView

    /**
     * A 'detail' view mode.
     */
    readonly MODE_DETAIL = 'detail'

    private nameAttribute: string

    /**
     * An available view mode list.
     */
    protected viewModeList: string[]

    protected setup() {
        if (!this.model.entityType) {
            throw new Error('No entity type.');
        }

        this.entityType = this.model.entityType;

        this.headerView = this.options.headerView || this.headerView;
        this.recordView = this.options.recordView || this.recordView;

        this.rootUrl = this.options.rootUrl ?? this.options.params?.rootUrl ?? this.rootUrl ?? `#${this.scope}`;
        this.isReturn = this.options.isReturn || this.options.params?.isReturn || false;

        this.nameAttribute = this.getMetadata().get(`clientDefs.${this.entityType}.nameAttribute`) ?? 'name';

        this.setupModes();
        this.setupHeader();
        this.setupRecord();
        this.setupPageTitle();
        this.initFollowButtons();
        this.initStarButtons();
        this.initRedirect();

        this.addActionHandler('fullRefresh', () => this.actionFullRefresh());
    }

    protected setupFinal() {
        super.setupFinal();

        this.wait(
            this.getHelper().processSetupHandlers(this, 'detail')
        );
    }

    private initRedirect() {
        if (!this.options.params?.isAfterCreate) {
            return;
        }

        const redirect = () => {
            Ui.success(this.translate('Created'));

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
    protected setupPageTitle() {
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
    protected setupHeader() {
        this.createView('header', this.headerView, {
            model: this.model,
            fullSelector: '#main > .header',
            scope: this.scope,
            fontSizeFlexible: true,
        });

        this.listenTo(this.model, 'sync', model => {
            if (model && model.hasChanged(this.nameAttribute)) {
                this.getHeaderView()?.reRender({buffer: true});
            }
        });
    }

    /**
     * Set up modes.
     */
    protected setupModes() {
        this.defaultViewMode = this.options.defaultViewMode ||
            this.getMetadata().get(`clientDefs.${this.scope}.detailDefaultViewMode`) ||
            this.defaultViewMode;

        this.viewMode = this.viewMode || this.defaultViewMode;

        const viewModeList = this.options.viewModeList ?? this.viewModeList ??
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

            this.viewMode = viewMode;
        }

        if (this.hasMultipleModes) {
            this.addActionHandler('switchMode', (_e, target) => this.switchViewMode(target.dataset.value as string));

            this.modesView = new DetailModesView({
                mode: this.viewMode,
                modeList: this.viewModeList,
                scope: this.scope,
            });

            this.assignView('modes', this.modesView, '.modes');
        }
    }

    private getViewModeKey(): string {
        return `detailViewMode-${this.scope}-${this.model.id}}`;
    }

    /**
     * Set up a record.
     */
    protected async setupRecord(): Promise<RecordView> {
        const o = {
            model: this.model,
            fullSelector: '#main > .record',
            scope: this.scope,
            shortcutKeysEnabled: true,
            isReturn: this.isReturn,
        } as Record<string, unknown>;

        this.optionsToPass.forEach((option) => {
            o[option] = this.options[option];
        });

        const params = this.options.params ?? {};

        o.rootUrl = this.rootUrl;

        if (params.rootData) {
            o.rootData = this.options.params?.rootData;
        }

        if (this.model.get('deleted')) {
            o.readOnly = true;
        }

        const view = await this.createView<RecordView>('record', this.getRecordViewName(), o);

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

        return view;
    }

    /**
     * Get a record view name.
     */
    protected getRecordViewName(): string {
        return this.getMetadata().get(`clientDefs.${this.scope}.recordViews.${this.viewMode}`) ?? this.recordView;
    }

    /**
     * Switch a view mode.
     *
     * @param mode A mode.
     */
    protected async switchViewMode(mode: string) {
        this.clearView('record');
        this.setViewMode(mode, true);

        Ui.notifyWait();

        if (this.modesView) {
            this.modesView.changeMode(mode).then(() => {});
        }

        const view = await this.setupRecord();

        await view.render();
        Ui.notify();
    }

    /**
     * Set a view mode.
     *
     * @param {string} mode A mode.
     * @param {boolean} [toStore=false] To preserve a mode being set.
     */
    private setViewMode(mode: string, toStore: boolean = false) {
        this.viewMode = mode;

        if (toStore && this.viewModeIsStorable) {
            const modeKey = this.getViewModeKey();

            this.getStorage().set('state', modeKey, mode);
        }
    }

    private initStarButtons() {
        if (!this.getMetadata().get(`scopes.${this.scope}.stars`)) {
            return;
        }

        this.addStarButtons();
        this.listenTo(this.model, 'change:isStarred', () => this.controlStarButtons());
    }

    private addStarButtons() {
        const isStarred = this.model.get('isStarred');

        this.addMenuItem('buttons', {
            name: 'unstar',
            iconHtml: '<span class="fas fa-star fa-sm text-warning"></span>',
            className: 'btn-s-wide',
            text: this.translate('Starred'),
            hidden: !isStarred,
            style: 'text',
            //title: this.translate('Unstar'),
            onClick: () => this.actionUnstar(),
        }, true);

        this.addMenuItem('buttons', {
            name: 'star',
            iconHtml: '<span class="far fa-star fa-sm"></span>',
            className: 'btn-s-wide',
            text: this.translate('Star'),
            style: 'text',
            hidden: isStarred || !this.model.has('isStarred'),
            onClick: () => this.actionStar(),
        }, true);
    }

    private controlStarButtons() {
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
    private actionStar() {
        this.disableMenuItem('star');

        Ajax.putRequest(`${this.entityType}/${this.model.id}/starSubscription`)
            .then(() => {
                this.hideHeaderActionItem('star');

                this.model.set('isStarred', true, {sync: true});
            })
            .finally(() => this.enableMenuItem('star'));
    }

    /**
     * Action 'unstar'.
     */
    private actionUnstar() {
        this.disableMenuItem('unstar');

        Ajax.deleteRequest(`${this.entityType}/${this.model.id}/starSubscription`)
            .then(() => {
                this.hideHeaderActionItem('unstar');

                this.model.set('isStarred', false, {sync: true});
            })
            .finally(() => this.enableMenuItem('unstar'));
    }

    private initFollowButtons() {
        if (!this.getMetadata().get(['scopes', this.scope, 'stream'])) {
            return;
        }

        this.addFollowButtons();

        this.listenTo(this.model, 'change:isFollowed', () => {
            this.controlFollowButtons();
        });
    }

    private addFollowButtons() {
        const isFollowed = this.model.get('isFollowed');

        this.addMenuItem('buttons', {
            name: 'unfollow',
            label: 'Followed',
            style: 'text',
            iconHtml: '<span class="fas fa-rss fa-sm text-success"></span>',
            className: 'text-success',
            action: 'unfollow',
            hidden: !isFollowed,
            onClick: () => this.actionUnfollow(),
        }, true);

        this.addMenuItem('buttons', {
            name: 'follow',
            label: 'Follow',
            style: 'text',
            iconHtml: '<span class="fas fa-rss fa-sm"></span>',
            text: this.translate('Follow'),
            action: 'follow',
            hidden: isFollowed ||
                !this.model.has('isFollowed') ||
                !this.getAcl().checkModel(this.model, 'stream'),
            onClick: () => this.actionFollow(),
        }, true);
    }

    private controlFollowButtons() {
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

    /**
     * Action 'follow'.
     */
    private actionFollow() {
        this.disableMenuItem('follow');

        Ajax.putRequest(`${this.entityType}/${this.model.id}/subscription`)
            .then(() => {
                this.hideHeaderActionItem('follow');

                this.model.set('isFollowed', true, {sync: true});

                this.enableMenuItem('follow');
            })
            .catch(() => {
                this.enableMenuItem('follow');
            });
    }

    /**
     * Action 'unfollow'.
     */
    private actionUnfollow() {
        this.disableMenuItem('unfollow');

        Ajax.deleteRequest(`${this.entityType}/${this.model.id}/subscription`)
            .then(() => {
                this.hideHeaderActionItem('unfollow');

                this.model.set('isFollowed', false, {sync: true});

                this.enableMenuItem('unfollow');
            })
            .catch(() => {
                this.enableMenuItem('unfollow');
            });
    }

    getHeader(): string {
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
        root.textContent = scopeLabel;
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

    updatePageTitle() {
        if (this.model.has(this.nameAttribute)) {
            this.setPageTitle(this.model.attributes[this.nameAttribute] || this.model.id);

            return;
        }

        super.updatePageTitle();
    }

    protected getRecordView(): RecordView {
        return this.getView<RecordView>('record') as RecordView;
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Action 'duplicate'.
     */
    actionDuplicate() {
        Ui.notifyWait();

        Ajax
            .postRequest(`${this.scope}/action/getDuplicateAttributes`, {id: this.model.id})
            .then(attributes => {
                Ui.notify();

                const url = `#${this.scope}/create`;

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

    protected hideAllHeaderActionItems() {
        this.getHeaderView()?.hideAllMenuItems();
    }

    protected showAllHeaderActionItems() {
        this.getHeaderView()?.showAllActionItems();
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Hide a view mode.
     *
     * @param {string} mode A mode.
     * @since 8.4.0
     */
    hideViewMode(mode: string) {
        if (!this.modesView) {
            return;
        }

        this.modesView.hideMode(mode);
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Show a view mode.
     *
     * @param {string} mode A mode.
     * @since 8.4.0
     */
    showViewMode(mode: string) {
        if (!this.modesView) {
            return;
        }

        this.modesView.showMode(mode);
    }

    protected async actionFullRefresh() {
        if (this.getRecordMode() === 'edit') {
            return;
        }

        Ui.notifyWait();

        await this.model.fetch();

        this.model.trigger('update-all');

        Ui.notify();
    }

    private getRecordMode(): 'detail' | 'edit' {
        if (this.getRecordView().getMode) {
            return this.getRecordView().getMode();
        }

        return 'detail';
    }

    setupReuse(params: Record<string, unknown>) {
        // noinspection BadExpressionStatementJS
        params;

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

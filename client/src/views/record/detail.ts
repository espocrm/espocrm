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

import BaseRecordView, {BaseRecordViewOptions, BaseRecordViewSchema, SaveOptions} from 'views/record/base';
import ViewRecordHelper from 'view-record-helper';
import ActionItemSetup from 'helpers/action-item-setup';
import StickyBarHelper from 'helpers/record/misc/sticky-bar';
import SelectTemplateModalView from 'views/modals/select-template';
import DebounceHelper from 'helpers/util/debounce';
import {inject} from 'di';
import ShortcutManager from 'helpers/site/shortcut-manager';
import WebSocketManager from 'web-socket-manager';
import Utils from 'utils';
import LayoutConverter from 'helpers/record/detail/layout-converter';
import _ from 'underscore';
import type Model from 'model';
import type BaseFieldView from 'views/fields/base';
import type {Defs} from 'dynamic-logic';
import Ui from 'ui';
import DynamicHandler from 'dynamic-handler';
import type Collection from 'collection';
import DetailRecordButtonsView from 'views/record/detail/buttons';

type ReturnDispatchParams = Record<string, unknown> & {
    controller?: string;
    action?: string;
    options?: Record<string, unknown>;
};

/**
 * A panel soft-locked type.
 */
type PanelSoftLockedType = 'default' | 'acl' | 'delimiter' | 'dynamicLogic';

/**
 * A button. Handled by an `action{Name}` method, a click handler or a handler class.
 */
export interface Button {
    /**
     * A name.
     */
    name: string;
    /**
     * A label.
     */
    label?: string;
    /**
     * A label translation path.
     */
    labelTranslation?: string;
    /**
     * An HTML.
     */
    html?: string;
    /**
     * A text.
     */
    text?: string;
    /**
     *  A style.
     */
    style?: 'default' | 'danger' | 'success' | 'warning' | 'primary';
    /**
     * Hidden.
     */
    hidden?: boolean;
    /**
     * A title (not translatable).
     */
    title?: string;
    /**
     * Disabled.
     */
    disabled?: boolean;
    /**
     * A click handler.
     */
    onClick?: () => void;
}

/**
 * A dropdown item. Handled by an `action{Name}` method, a click handler or a handler class.
 */
export interface DropdownItem {
    /**
     * A name.
     */
    name: string;
    /**
     * A label.
     */
    label?: string;
    /**
     * A label translation path.
     */
    labelTranslation?: string;
    /**
     * An HTML.
     */
    html?: string;
    /**
     * A text.
     */
    text?: string;
    /**
     * Hidden.
     */
    hidden?: boolean;
    /**
     * Data attributes.
     */
    data?: Record<string, string>;
    /**
     * A title (not translatable).
     */
    title?: string;
    /**
     *  Disabled.
     */
    disabled?: boolean;
    /**
     * A click handler.
     */
    onClick?: () => void;
    /**
     * A group index.
     */
    groupIndex?: number;
}

/**
 * A row.
 */
type RowDefs = (CellDefs | false)[];

/**
 * Cell definitions.
 */
interface CellDefs {
    /**
     * A name (usually a field name).
     */
    name?: string;
    /**
     * An overridden field view name or a view instance.
     */
    view?: string | BaseFieldView<any, any, any>;
    /**
     * An overridden field type.
     */
    type?: string;
    /**
     * Read-only.
     */
    readOnly?: boolean;
    /**
     * Disable inline edit.
     */
    inlineEditDisabled?: boolean;
    /**
     * Overridden field parameters.
     */
    params?: Record<string, unknown>;
    /**
     * Field view options.
     */
    options?: Record<string, unknown>;
    /**
     * A label text (not-translatable).
     */
    labelText?: string;
    /**
     * No label.
     */
    noLabel?: boolean;
    /**
     * A translatable label (using the `fields` category).
     */
    label?: string;
    /**
     * A label translation path.
     *
     * @since 9.4.0
     */
    labelTranslation?: string | null;
    /**
     * A width.
     */
    span?: 1 | 2 | 3 | 4;
}

/**
 * Panel definitions.
 */
export interface PanelDefs {
    /**
     * A translatable label.
     */
    label?: string;
    /**
     * A custom label.
     */
    customLabel?: string;
    /**
     * A name. Useful to be able to show/hide by a name.
     */
    name?: string;
    /**
     * A style.
     */
    style?: 'default' | 'success' | 'danger' | 'warning' | 'info';
    /**
     * Is a tab-break.
     */
    tabBreak?: boolean;
    /**
     * A tab label. If starts with `$`, a translation of the `tabs` category is used.
     */
    tabLabel?: string;
    /**
     * Rows.
     */
    rows?: RowDefs[];
    /**
     * Columns
     */
    columns?: RowDefs[];
    /**
     * A note text.
     */
    noteText?: string;
    /**
     * A note style.
     */
    noteStyle?: 'success' | 'danger' | 'warning' | 'info';
}

export interface DetailRecordViewSchema extends BaseRecordViewSchema {
    model: Model;
    options: DetailRecordViewOptions;
}

/**
 * Options.
 */
export interface DetailRecordViewOptions extends BaseRecordViewOptions {
    /**
     * A scope.
     */
    scope?: string;
    /**
     * A layout name.
     */
    layoutName?: string;
    /**
     * A detail layout.
     */
    detailLayout?: PanelDefs[];
    /**
     * Read-only.
     */
    readOnly?: boolean;
    /**
     *
     */
    rootUrl?: string;
    /**
     *
     */
    returnUrl?: string;
    /**
     *
     */
    returnAfterCreate?: boolean;
    /**
     *
     */
    editModeDisabled?: boolean;
    /**
     *
     */
    confirmLeaveDisabled?: boolean;
    /**
     *
     */
    isWide?: boolean;
    /**
     *
     */
    sideView?: string | null;
    /**
     *
     */
    bottomView?: string | null;
    /**
     * Disable inline edit.
     */
    inlineEditDisabled?: boolean;
    /**
     * Disable buttons.
     */
    buttonsDisabled?: boolean;
    /**
     *
     */
    navigateButtonsDisabled?: boolean;
    /**
     *
     */
    dynamicLogicDefs?: Defs;
    /**
     * A record helper. For a form state management.
     */
    recordHelper?: import('view-record-helper').default;
    /**
     *
     */
    attributes?: Record<string, unknown>;
    /**
     * Buttons.
     */
    buttonList?: Button[];
    /**
     * Dropdown items.
     */
    dropdownItemList?: DropdownItem[];
    /**
     * Additional data.
     */
    dataObject?: Record<string, unknown>;
    /**
     * Data from the root view.
     */
    rootData?: Record<string, unknown>;
    /**
     * Enable shortcut keys.
     */
    shortcutKeysEnabled?: boolean;
    /**
     * Focus on create.
     */
    focusForCreate?: boolean
    /**
     * Disable WebSocket.
     *
     * @since 9.2.0
     */
    webSocketDisabled?: boolean;
    /**
     * Disable the bottom view.
     */
    bottomDisabled?: boolean;
    /**
     * Disable the side view.
     */
    sideDisabled?: boolean;
    /**
     * The index of the record in the collection.
     *
     * @internal
     */
    indexOfRecord?: number
    /**
     * Return dispatch parameters.
     *
     * @internal
     */
    returnDispatchParams?: ReturnDispatchParams;
    /**
     * Is return.
     *
     * @internal
     */
    isReturn?: boolean;
    /**
     * A type.
     *
     * @internal
     */
    type?: string;
    /**
     * An exist function.
     *
     * @internal
     */
    exit?: (action: string) => {};
}

/**
 * A detail record view.
 */
class DetailRecordView<S extends DetailRecordViewSchema = DetailRecordViewSchema> extends BaseRecordView<S> {

    @inject(ShortcutManager)
    private shortcutManager: ShortcutManager

    protected template: string = 'record/detail'

    protected type: string = 'detail'

    /**
     * A layout name. Can be overridden by an option parameter.
     */
    protected layoutName: string = 'detail'

    /**
     * A layout. If null, then will be loaded from the backend (using the `layoutName` value).
     * Can be overridden by an option parameter.
     */
    protected detailLayout: PanelDefs[] | null = null

    /**
     * A fields mode.
     */
    protected fieldsMode: 'detail' | 'edit' | 'list' = 'detail'

    /**
     * A current mode. Only for reading.
     */
    mode: 'detail' | 'edit' = 'detail'

    private gridLayout: any = null

    /**
     * Disable buttons. Can be overridden by an option parameter.
     */
    protected buttonsDisabled: boolean = false

    /**
     * Is record new. Only for reading.
     */
    isNew: boolean = false

    /**
     * A button list.
     */
    protected buttonList: Button[] = [
        {
            name: 'edit',
            label: 'Edit',
            title: 'Ctrl+Space',
        },
    ]

    /**
     * A dropdown item list.
     */
    protected dropdownItemList: DropdownItem[] = [
        {
            name: 'delete',
            label: 'Remove',
            groupIndex: 0,
        },
    ]

    /**
     * A button list for edit mode.
     */
    protected buttonEditList: (Button & {edit?: true})[] = [
        {
            name: 'save',
            label: 'Save',
            style: 'primary',
            edit: true,
            title: 'Ctrl+Enter',
        },
        {
            name: 'cancelEdit',
            label: 'Cancel',
            edit: true,
            title: 'Esc',
        },
    ]

    /**
     * A dropdown item list for edit mode.
     */
    protected dropdownEditItemList: DropdownItem[] = []

    /**
     * All action items disabled;
     */
    protected allActionItemsDisabled: boolean = false

    /**
     * A DOM element ID. Only for reading.
     *
     * @internal
     */
    protected id: string

    /**
     * A return-URL. Can be overridden by an option parameter.
     */
    protected returnUrl: string | null = null

    /**
     * A return dispatch params. Can be overridden by an option parameter.
     */
    protected returnDispatchParams: ReturnDispatchParams | null = null

    /**
     * A middle view name.
     */
    protected middleView: string = 'views/record/detail-middle'

    /**
     * A side view name.
     */
    protected sideView: string | null = 'views/record/detail-side'

    /**
     * A bottom view name.
     */
    protected bottomView: string | null = 'views/record/detail-bottom'

    /**
     * Disable a side view. Can be overridden by an option parameter.
     */
    protected sideDisabled: boolean = false

    /**
     * Disable a bottom view. Can be overridden by an option parameter.
     */
    protected bottomDisabled: boolean = false

    protected gridLayoutType: string = 'record'

    /**
     * Disable edit mode. Can be overridden by an option parameter.
     */
    protected editModeDisabled: boolean = false

    /**
     * Disable navigate (prev, next) buttons. Can be overridden by an option parameter.
     */
    protected navigateButtonsDisabled: boolean = false

    /**
     * Read-only. Can be overridden by an option parameter.
     */
    readOnly: boolean = false

    /**
     * Read-only locked.
     */
    protected readOnlyLocked: boolean

    /**
     * Middle view expanded to full width (no side view).
     * Can be overridden by an option parameter.
     */
    protected isWide: boolean = false

    /**
     * Enable a duplicate action.
     */
    protected duplicateAction: boolean = true

    /**
     * Enable a self-assign action.
     */
    protected selfAssignAction: boolean = false

    /**
     * Enable a print-pdf action.
     */
    protected printPdfAction: boolean = true

    /**
     * Enable a convert-currency action.
     */
    protected convertCurrencyAction: boolean = true

    /**
     * Enable a save-and-continue-editing action.
     */
    protected saveAndContinueEditingAction: boolean = true

    /**
     * Disable the inline-edit. Can be overridden by an option parameter.
     */
    protected inlineEditDisabled: boolean = false

    /**
     * Disable a portal layout usage. Can be overridden by an option parameter.
     */
    protected portalLayoutDisabled: boolean = false

    private panelSoftLockedTypeList: PanelSoftLockedType[] = [
        'default',
        'acl',
        'delimiter',
        'dynamicLogic',
    ]

    /**
     * Dynamic logic. Can be overridden by an option parameter.
     */
    protected dynamicLogicDefs: Defs = {}

    /**
     * Disable confirm leave-out processing.
     */
    protected confirmLeaveDisabled = false

    protected setupHandlerType: string = 'record/detail'

    protected currentTab: number = 0

    private middlePanelDefs: Record<string, any>
    private middlePanelDefsList: Record<string, any>[]

    private $middle: JQuery
    private $bottom: JQuery

    private blockUpdateWebSocketPeriod: number = 500

    /**
     * @internal
     */
    protected stickButtonsFormBottomSelector: string

    protected stickButtonsContainerAllTheWay: boolean

    protected dynamicHandlerClassName: string

    /**
     * Disable access control.
     */
    protected accessControlDisabled: boolean

    protected inlineEditModeIsOn: boolean = false

    /**
     * A Ctrl+Enter shortcut action.
     */
    protected shortcutKeyCtrlEnterAction: string = 'save'

    private returnAfterCreate: boolean

    /**
     * Additional data. Passed to sub-views and fields.
     *
     * @since 9.0.0
     */
    protected dataObject: Record<string, any>

    /**
     * Data from the root view.
     *
     * @since 9.0.0
     */
    protected rootData: Record<string, unknown>

    private _webSocketDebounceHelper: DebounceHelper

    private _webSocketDebounceInterval: number = 500

    @inject(WebSocketManager)
    private webSocketManager: WebSocketManager

    private indexOfRecord: number

    private panelFieldListMap: Record<string, any>

    private underShowMoreDetailPanelList: string[]

    private recordUpdateWebSocketTopic: string

    private isSubscribedToWebSocket: boolean = false

    private updateWebSocketIsBlocked: boolean = false

    private _hasMiddleTabs: boolean

    /**
     * @internal
     */
    protected hasModifyDetailLayout: boolean = false

    /**
     * A shortcut-key => action map.
     */
    protected shortcutKeys: (Record<string, (event: KeyboardEvent) => void>) = {
        'Control+Enter': function (this: DetailRecordView, e: KeyboardEvent) {
            this.handleShortcutKeyCtrlEnter(e);
        },
        'Control+Alt+Enter': function (this: DetailRecordView, e: KeyboardEvent) {
            this.handleShortcutKeyCtrlAltEnter(e);
        },
        'Control+KeyS': function (this: DetailRecordView, e: KeyboardEvent) {
            this.handleShortcutKeyCtrlS(e);
        },
        'Control+Space': function (this: DetailRecordView, e: KeyboardEvent) {
            this.handleShortcutKeyCtrlSpace(e);
        },
        'Escape': function (this: DetailRecordView, e: KeyboardEvent) {
            this.handleShortcutKeyEscape(e);
        },
        'Control+Backslash': function (this: DetailRecordView, e: KeyboardEvent) {
            this.handleShortcutKeyControlBackslash(e);
        },
        'Control+ArrowLeft': function (this: DetailRecordView, e: KeyboardEvent) {
            this.handleShortcutKeyControlArrowLeft(e);
        },
        'Control+ArrowRight': function (this: DetailRecordView, e: KeyboardEvent) {
            this.handleShortcutKeyControlArrowRight(e);
        },
    }


    /**
     * An `edit` action.
     */
    protected actionEdit() {
        if (!this.editModeDisabled) {
            this.setEditMode();

            this.focusOnFirstDiv();
            $(window).scrollTop(0);

            return;
        }

        const options = {
            id: this.model.id,
            model: this.model.clone(),
        } as any;

        if (this.model.collection) {
            const index = this.model.collection.indexOf(this.model);

            if (index > -1) {
                options.model.collection = this.model.collection;
                options.model.collection.models[index] = options.model;
            }
        }

        if (this.options.rootUrl) {
            options.rootUrl = this.options.rootUrl;
        }

        if (this.inlineEditModeIsOn) {
            options.attributes = this.getChangedAttributes();

            this.resetModelChanges();
        }

        this.getRouter().navigate(`#${this.scope}/edit/${this.model.id}`, {trigger: false});
        this.getRouter().dispatch(this.scope, 'edit', options);
    }

    // noinspection JSUnusedGlobalSymbols
    actionDelete() {
        this.delete();
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * A `save` action.
     *
     * @param data Data.
     */
    protected actionSave(data?: {options?: SaveOptions}): Promise<void> {
        data = data || {};

        const modeBeforeSave = this.mode;

        const promise = this.save(data.options)
            .catch(reason => {
                if (
                    modeBeforeSave === this.MODE_EDIT &&
                    ['error', 'cancel'].includes(reason)
                ) {
                    this.setEditMode();
                }

                return Promise.reject(reason);
            });

        if (!this.lastSaveCancelReason || this.lastSaveCancelReason === 'notModified') {
            this.setDetailMode();

            this.focusOnFirstDiv();
            $(window).scrollTop(0);
        }

        return promise;
    }

    protected actionCancelEdit() {
        this.cancelEdit();

        this.focusOnFirstDiv();
        $(window).scrollTop(0);
    }

    protected focusOnFirstDiv() {
        const element = /** @type {HTMLElement} */this.$el.find('> div').get(0);

        if (element) {
            element.focus({preventScroll: true});
        }
    }

    /**
     * A `save-and-continue-editing` action.
     */
    protected actionSaveAndContinueEditing(data?: {options?: SaveOptions}) {
        data = data || {};

        this.save(data.options)
            .catch(() => {});
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * A `self-assign` action.
     */
    protected actionSelfAssign() {
        const attributes = {
            assignedUserId: this.getUser().id,
            assignedUserName: this.getUser().get('name'),
        } as Record<string, any>;

        if ('getSelfAssignAttributes' in this) {
            const attributesAdditional = this.getSelfAssignAttributes();

            if (attributesAdditional) {
                for (const i in attributesAdditional) {
                    attributes[i] = attributesAdditional[i];
                }
            }
        }

        this.model
            .save(attributes, {patch: true})
            .then(() => {
                Ui.success(this.translate('Self-Assigned'));
            });
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * A `convert-currency` action.
     */
    protected actionConvertCurrency() {
        this.createView('modalConvertCurrency', 'views/modals/convert-currency', {
            entityType: this.entityType,
            model: this.model,
        }).then(view => {
            view.render();

            this.listenToOnce(view, 'after:update', attributes => {
                let isChanged = false;

                for (const a in attributes) {
                    if (attributes[a] !== this.model.get(a)) {
                        isChanged = true;

                        break;
                    }
                }

                if (!isChanged) {
                    Ui.warning(this.translate('notUpdated', 'messages'));

                    return;
                }

                this.model
                    .fetch()
                    .then(() => {
                        Ui.success(this.translate('done', 'messages'));
                    });
            });
        });
    }

    /**
     * Compose attribute values for a self-assignment.
     */
    protected getSelfAssignAttributes(): Record<string, unknown> | null {
        return null;
    }

    /**
     * Set up action items.
     */
    protected setupActionItems() {
        if (this.model.isNew()) {
            this.isNew = true;

            this.removeActionItem('delete');
        } else if (this.scope && this.getMetadata().get(['clientDefs', this.scope, 'removeDisabled'])) {
            this.removeActionItem('delete');
        }

        if (
            this.duplicateAction &&
            this.entityType &&
            this.scope &&
            this.getAcl().check(this.entityType, 'create') &&
            !this.getMetadata().get(['clientDefs', this.scope, 'duplicateDisabled']) &&
            !this.getMetadata().get(['clientDefs', this.scope, 'createDisabled'])
        ) {
            this.addDropdownItem({
                label: 'Duplicate',
                name: 'duplicate',
                groupIndex: 0,
            });
        }

        if (
            this.selfAssignAction &&
            this.entityType &&
            this.getAcl().check(this.entityType, 'edit') &&
            !this.getAcl().getScopeForbiddenFieldList(this.entityType).includes('assignedUser') &&
            !this.getUser().isPortal() && this.model.has('assignedUserId')
        ) {
            this.addDropdownItem({
                label: 'Self-Assign',
                name: 'selfAssign',
                hidden: !!this.model.get('assignedUserId'),
                groupIndex: 0,
            });

            this.listenTo(this.model, 'change:assignedUserId', () => {
                if (!this.model.get('assignedUserId')) {
                    this.showActionItem('selfAssign');
                } else {
                    this.hideActionItem('selfAssign');
                }
            });
        }

        if (this.type === this.TYPE_DETAIL && this.printPdfAction) {
            let printPdfAction = true;

            if (
                !~(this.getHelper().getAppParam('templateEntityTypeList') || [])
                    .indexOf(this.entityType)
            ) {
                printPdfAction = false;
            }

            if (printPdfAction) {
                this.addDropdownItem({
                    label: 'Print to PDF',
                    name: 'printPdf',
                    groupIndex: 6,
                });
            }
        }

        if (
            this.type === this.TYPE_DETAIL &&
            this.convertCurrencyAction &&
            this.entityType &&
            this.scope &&
            this.getAcl().check(this.entityType, 'edit') &&
            !this.getMetadata().get(['clientDefs', this.scope, 'convertCurrencyDisabled'])
        ) {
            const currencyFieldList = this.getFieldManager()
                .getEntityTypeFieldList(this.entityType, {
                    type: 'currency',
                    acl: 'edit',
                });

            if (currencyFieldList.length) {
                this.addDropdownItem({
                    label: 'Convert Currency',
                    name: 'convertCurrency',
                    groupIndex: 5,
                });
            }
        }

        if (
            this.type === this.TYPE_DETAIL &&
            this.getMetadata().get(['scopes', this.scope ?? '_', 'hasPersonalData'])
        ) {
            if (this.getAcl().getPermissionLevel('dataPrivacyPermission') === 'yes') {
                this.dropdownItemList.push({
                    label: 'View Personal Data',
                    name: 'viewPersonalData',
                    groupIndex: 4,
                });
            }
        }

        if (this.type === this.TYPE_DETAIL && this.getMetadata().get(['scopes', this.scope ?? '_', 'stream'])) {
            this.addDropdownItem({
                label: 'View Followers',
                name: 'viewFollowers',
                groupIndex: 4,
            });
        }

        if (this.buttonsDisabled) {
            return;
        }

        if (this.type === this.TYPE_DETAIL) {
            const actionItemSetup = new ActionItemSetup();

            actionItemSetup.setup({
                view: this,
                type: 'detailActionList',
                waitFunc: promise => this.wait(promise),
                addFunc: item => this.addDropdownItem(item),
                showFunc: name => this.showActionItem(name),
                hideFunc: name => this.hideActionItem(name),
            });

            if (this.saveAndContinueEditingAction) {
                this.dropdownEditItemList.push({
                    name: 'saveAndContinueEditing',
                    label: 'Save & Continue Editing',
                    title: 'Ctrl+S',
                    groupIndex: 0,
                });
            }
        }

        if (this.type === this.TYPE_EDIT || this.type === this.TYPE_DETAIL) {
            const actionItemSetup = new ActionItemSetup();

            actionItemSetup.setup({
                view: this,
                type: 'editActionList',
                waitFunc: promise => this.wait(promise),
                addFunc: item => {
                    if (this.type === this.TYPE_EDIT) {
                        this.addDropdownItem(item);
                    } else {
                        this.dropdownEditItemList.push(item);
                    }
                },
                showFunc: name => this.showActionItem(name),
                hideFunc: name => this.hideActionItem(name),
            });
        }
    }

    /**
     * Disable action items.
     */
    disableActionItems() {
        this.disableButtons();
    }

    /**
     * Enable action items.
     */
    enableActionItems() {
        this.enableButtons();
    }

    /**
     * Hide a button or dropdown action item.
     *
     * @param name A name.
     */
    hideActionItem(name: string) {
        for (const item of this.buttonList) {
            if (item.name === name) {
                item.hidden = true;

                break;
            }
        }

        for (const item of this.dropdownItemList) {
            if (item.name === name) {
                item.hidden = true;

                break;
            }
        }

        for (const item of this.dropdownEditItemList) {
            if (item.name === name) {
                item.hidden = true;

                break;
            }
        }

        for (const item of this.buttonEditList) {
            if (item.name === name) {
                item.hidden = true;

                break;
            }
        }

        if (this.isRendered()) {
            this.reRenderButtons();
        }
    }

    /**
     * Show a button or dropdown action item.
     *
     * @param name A name.
     */
    showActionItem(name: string) {
        for (const item of this.buttonList) {
            if (item.name === name) {
                item.hidden = false;

                break;
            }
        }

        for (const item of this.dropdownItemList) {
            if (item.name === name) {
                item.hidden = false;

                break;
            }
        }

        for (const item of this.dropdownEditItemList) {
            if (item.name === name) {
                item.hidden = false;

                break;
            }
        }

        for (const item of this.buttonEditList) {
            if (item.name === name) {
                item.hidden = false;

                break;
            }
        }

        if (this.isRendered()) {
            this.reRenderButtons();
        }
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Disable a button or dropdown action item.
     *
     * @param name A name.
     */
    disableActionItem(name: string) {
        for (const item of this.buttonList) {
            if (item.name === name) {
                item.disabled = true;

                break;
            }
        }

        for (const item of this.dropdownItemList) {
            if (item.name === name) {
                item.disabled = true;

                break;
            }
        }

        for (const item of this.dropdownEditItemList) {
            if (item.name === name) {
                item.disabled = true;

                break;
            }
        }

        for (const item of this.buttonEditList) {
            if (item.name === name) {
                item.disabled = true;

                break;
            }
        }

        if (this.isRendered()) {
            this.reRenderButtons();
        }
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Enable a button or dropdown action item.
     *
     * @param name A name.
     */
    enableActionItem(name: string) {
        for (const item of this.buttonList) {
            if (item.name === name) {
                item.disabled = false;

                break;
            }
        }

        for (const item of this.dropdownItemList) {
            if (item.name === name) {
                item.disabled = false;

                break;
            }
        }

        for (const item of this.dropdownEditItemList) {
            if (item.name === name) {
                item.disabled = false;

                break;
            }
        }

        for (const item of this.buttonEditList) {
            if (item.name === name) {
                item.disabled = false;

                break;
            }
        }

        if (this.isRendered()) {
            this.reRenderButtons();
        }
    }

    /**
     * Whether an action item is visible and not disabled.
     *
     * @param name An action item name.
     */
    hasAvailableActionItem(name: string) {
        if (this.allActionItemsDisabled) {
            return false;
        }

        if (this.type === this.TYPE_DETAIL && this.mode === this.MODE_EDIT) {
            const hasButton = this.buttonEditList
                .findIndex(item => item.name === name && !item.disabled && !item.hidden) !== -1;

            if (hasButton) {
                return true;
            }

            return this.dropdownEditItemList
                .findIndex(item => item.name === name && !item.disabled && !item.hidden) !== -1;
        }

        const hasButton = this.buttonList
            .findIndex(item => item.name === name && !item.disabled && !item.hidden) !== -1;

        if (hasButton) {
            return true;
        }

        return this.dropdownItemList
            .findIndex(item => item.name === name && !item.disabled && !item.hidden) !== -1;
    }

    /**
     * Show a panel.
     *
     * @param name A panel name.
     * @param softLockedType A soft-locked type.
     */
    showPanel(name: string, softLockedType: PanelSoftLockedType = 'default') {
        if (this.recordHelper.getPanelStateParam(name, 'hiddenLocked')) {
            return;
        }

        softLockedType = softLockedType || 'default';

        const softLockedParam = 'hidden' + Utils.upperCaseFirst(softLockedType) + 'Locked'

        this.recordHelper.setPanelStateParam(name, softLockedParam, false);

        if (
            softLockedType === 'dynamicLogic' &&
            this.recordHelper.getPanelStateParam(name, 'hidden') === false
        ) {
            return;
        }

        for (let i = 0; i < this.panelSoftLockedTypeList.length; i++) {
            const iType = this.panelSoftLockedTypeList[i];

            if (iType === softLockedType) {
                continue;
            }

            const iParam = 'hidden' + Utils.upperCaseFirst(iType) + 'Locked';

            if (this.recordHelper.getPanelStateParam(name, iParam)) {
                return;
            }
        }

        const middleView = this.getMiddleView();

        if (middleView) {
            middleView.showPanelInternal(name);
        }

        const bottomView = this.getBottomView();

        if (bottomView) {
            if ('showPanel' in bottomView) {
                bottomView.showPanel(name);
            }
        } else if (this.bottomView) {
            this.once('ready', () => {
                const view = this.getBottomView();

                if (!view) {
                    return;
                }

                if ('processShowPanel' in view) {
                    view.processShowPanel(name);

                    return;
                }

                if ('showPanel' in view) {
                    (view as { showPanel: (name: string) => void}).showPanel(name);
                }
            });
        }

        const sideView = this.getSideView();

        if (sideView) {
            if ('showPanel' in sideView) {
                sideView.showPanel(name);
            }
        } else if (this.sideView) {
            this.once('ready', () => {
                const view = this.getSideView();

                if (!view) {
                    return;
                }

                if ('processShowPanel' in view) {
                    view.processShowPanel(name);

                    return;
                }
                if ('showPanel' in view) {
                    (view as { showPanel: (name: string) => void}).showPanel(name);
                }
            });
        }

        this.recordHelper.setPanelStateParam(name, 'hidden', false);

        if (this.middlePanelDefs[name]) {
            this.controlTabVisibilityShow(this.middlePanelDefs[name].tabNumber);

            this.adjustMiddlePanels();
        }

        this.recordHelper.trigger('panel-show');
    }

    /**
     * Hide a panel.
     *
     * @param name A panel name.
     * @param [locked=false] Won't be able to un-hide.
     * @param [softLockedType='default']
     *   A soft-locked type.
     */
    hidePanel(name: string, locked?: boolean, softLockedType: PanelSoftLockedType = 'default') {
        softLockedType = softLockedType || 'default';

        if (locked) {
            this.recordHelper.setPanelStateParam(name, 'hiddenLocked', true);
        }

        const softLockedParam = 'hidden' + Utils.upperCaseFirst(softLockedType) + 'Locked'

        this.recordHelper.setPanelStateParam(name, softLockedParam, true);

        if (
            softLockedType === 'dynamicLogic' &&
            this.recordHelper.getPanelStateParam(name, 'hidden') === true
        ) {
            return;
        }

        const middleView = this.getMiddleView();

        if (middleView) {
            middleView.hidePanelInternal(name);
        }

        const bottomView = this.getBottomView();

        if (bottomView) {
            if ('hidePanel' in bottomView) {
                bottomView.hidePanel(name);
            }
        } else if (this.bottomView) {
            this.once('ready', () => {
                const view = this.getBottomView();

                if (!view) {
                    return;
                }

                if ('processHidePanel' in view) {
                    view.processHidePanel(name);

                    return;
                }
                if ('hidePanel' in view) {
                    (view as { hidePanel: (name: string) => void }).hidePanel(name);
                }
            });
        }

        const sideView = this.getSideView();

        if (sideView) {
            if ('hidePanel' in sideView) {
                sideView.hidePanel(name);
            }
        } else if (this.sideView) {
            this.once('ready', () => {
                const view = this.getSideView();

                if (!view) {
                    return;
                }

                if ('processHidePanel' in view) {
                    view.processHidePanel(name);

                    return;
                }

                if ('hidePanel' in view) {
                    (view as { hidePanel: (name: string) => void }).hidePanel(name);
                }
            });
        }

        this.recordHelper.setPanelStateParam(name, 'hidden', true);

        if (this.middlePanelDefs[name]) {
            this.controlTabVisibilityHide(this.middlePanelDefs[name].tabNumber);

            this.adjustMiddlePanels();
        }
    }

    protected afterRender() {
        this.$middle = this.$el.find('.middle').first();

        if (this.bottomView) {
            this.$bottom = this.$el.find('.bottom').first();
        }

        this.adjustMiddlePanels();

        this.initStickableButtonsContainer();
        this.initFieldsControlBehaviour();
    }

    private initFieldsControlBehaviour() {
        const fields = this.getFieldViews();

        let fieldInEditMode: BaseFieldView | null = null;

        for (const field in fields) {
            const fieldView = fields[field];

            this.listenTo(fieldView, 'edit', (view) => {
                if (fieldInEditMode && fieldInEditMode.isEditMode()) {
                    fieldInEditMode.inlineEditClose();
                }

                fieldInEditMode = view;
            });

            this.listenTo(fieldView, 'inline-edit-on', () => {
                this.inlineEditModeIsOn = true;
            });

            this.listenTo(fieldView, 'inline-edit-off', (o) => {
                o = o || {};

                if (o.all) {
                    return;
                }

                this.inlineEditModeIsOn = false;

                this.setIsNotChanged();
            });

            this.listenTo(fieldView, 'after:inline-edit-off', o => {
                if (this.updatedAttributes && !o.noReset) {
                    this.resetModelChanges();
                }
            });
        }
    }

    private initStickableButtonsContainer() {
        const helper = new StickyBarHelper(
            this,
            this.stickButtonsFormBottomSelector,
            this.stickButtonsContainerAllTheWay,
            this.numId
        );

        helper.init();
    }

    fetch(): Record<string, unknown> {
        let data = super.fetch();

        if (this.hasView('side')) {
            const view = this.getSideView();

            if ('fetch' in view) {
                data = _.extend(data, view.fetch());
            }
        }

        if (this.hasView('bottom')) {
            const view = this.getBottomView();

            if ('fetch' in view) {
                data = _.extend(data, view.fetch());
            }
        }

        return data;
    }

    setEditMode() {
        this.trigger('before:set-edit-mode');

        this.inlineEditModeIsOn = false;

        this.$el.find('.record-buttons').addClass('hidden');
        this.$el.find('.edit-buttons').removeClass('hidden');

        return new Promise(resolve => {
            const fields = this.getFieldViews(true);

            const promiseList = [];

            for (const field in fields) {
                const fieldView = fields[field];

                if (fieldView.readOnly) {
                    continue;
                }

                if (fieldView.isEditMode()) {
                    fieldView.fetchToModel();
                    fieldView.removeInlineEditLinks();
                    fieldView.setIsInlineEditMode(false);
                }

                promiseList.push(
                    fieldView
                        .setEditMode()
                        .then(() => {
                            return fieldView.render();
                        })
                );
            }

            this.mode = this.MODE_EDIT;

            this.trigger('after:set-edit-mode');
            this.trigger('after:mode-change', this.MODE_EDIT);

            Promise.all(promiseList).then(() => resolve(undefined));
        });
    }

    setDetailMode() {
        this.trigger('before:set-detail-mode');

        this.$el.find('.edit-buttons').addClass('hidden');
        this.$el.find('.record-buttons').removeClass('hidden');

        this.inlineEditModeIsOn = false;

        return new Promise(resolve => {
            const fields = this.getFieldViews(true);

            const promiseList = [];

            for (const field in fields) {
                const fieldView = fields[field];

                if (!fieldView.isDetailMode()) {
                    if (fieldView.isEditMode()) {
                        fieldView.trigger('inline-edit-off', {
                            all: true,
                        });
                    }

                    promiseList.push(
                        fieldView
                            .setDetailMode()
                            .then(() => fieldView.render())
                    );
                }
            }

            this.mode = this.MODE_DETAIL;

            this.trigger('after:set-detail-mode');
            this.trigger('after:mode-change', this.MODE_DETAIL);

            Promise.all(promiseList).then(() => resolve(undefined));
        });
    }

    cancelEdit() {
        this.resetModelChanges();

        this.setDetailMode();
        this.setIsNotChanged();
    }

    /**
     * Whether in edit mode.
     */
    isEditMode(): boolean {
        return this.mode === 'edit';
    }

    protected resetModelChanges() {
        let skipReRender = true;

        if (this.updatedAttributes) {
            this.attributes = this.updatedAttributes;
            this.updatedAttributes = null;

            skipReRender = false;
        }

        const attributes = this.model.attributes;

        for (const attr in attributes) {
            if (!(attr in this.attributes)) {
                this.model.unset(attr);
            }
        }

        this.model.setMultiple(this.attributes, {
            skipReRenderInEditMode: skipReRender,
            action: 'cancel-edit',
        });
    }

    /**
     * Delete the record.
     */
    async delete() {
        await this.confirm({
            message: this.translate('removeRecordConfirmation', 'messages', this.scope),
            confirmText: this.translate('Remove'),
        })

        this.trigger('before:delete');
        this.trigger('delete');

        Ui.notifyWait();

        const collection = this.model.collection;

        await this.model.destroy({wait: true});

        if (collection) {
            if (collection.total > 0) {
                collection.total--;
            }
        }

        this.model.set('deleted', true, {silent: true});

        Ui.success(this.translate('Removed'), {suppress: true});

        this.trigger('after:delete');
        this.exit('delete');
    }

    /**
     * Get field views.
     *
     * @param withHidden With hidden.
     */
    getFieldViews(withHidden = false): Record<string, BaseFieldView> {
        const fields = {};

        if (this.hasView('middle')) {
            if ('getFieldViews' in this.getMiddleView()) {
                _.extend(fields, Utils.clone(this.getMiddleView().getFieldViews()));
            }
        }

        if (this.hasView('side')) {
            if ('getFieldViews' in this.getSideView()) {
                _.extend(fields, this.getSideView().getFieldViews(withHidden));
            }
        }

        if (this.hasView('bottom')) {
            if ('getFieldViews' in this.getBottomView()) {
                _.extend(fields, this.getBottomView().getFieldViews(withHidden));
            }
        }

        return fields;
    }

    /**
     * Get a field view.
     *
     * @param name A field name.
     */
    getFieldView(name: string): BaseFieldView | null {
        let view : any;

        if (this.hasView('middle')) {
            view = (this.getMiddleView().getFieldViews() || {})[name];
        }

        if (!view && this.hasView('side')) {
            view = (this.getSideView().getFieldViews(true) || {})[name];
        }

        if (!view && this.hasView('bottom')) {
            view = (this.getBottomView().getFieldViews(true) || {})[name];
        }

        return (view ?? null) as BaseFieldView | null;
    }

    protected data(): Record<string, any> {
        let navigateButtonsEnabled = !this.navigateButtonsDisabled && !!this.model.collection;

        let previousButtonEnabled = false;
        let nextButtonEnabled = false;

        if (navigateButtonsEnabled) {
            const collection = this.model.collection as Collection;

            if (this.indexOfRecord > 0 || collection.offset) {
                previousButtonEnabled = true;
            }

            const total = collection.total !== undefined ?
                collection.total : collection.length;

            if (this.indexOfRecord < total - 1 - collection.offset) {
                nextButtonEnabled = true;
            } else {
                if (total === -1) {
                    nextButtonEnabled = true;
                } else if (total === -2) {
                    if (this.indexOfRecord < collection.length - 1 - collection.offset) {
                        nextButtonEnabled = true;
                    }
                }
            }

            if (!previousButtonEnabled && !nextButtonEnabled) {
                navigateButtonsEnabled = false;
            }
        }

        const hasMiddleTabs = this.hasTabs();
        const middleTabDataList = hasMiddleTabs ? this.getMiddleTabDataList() : [];

        return {
            scope: this.scope,
            entityType: this.entityType,
            buttonsDisabled: this.buttonsDisabled,
            id: this.id,
            isWide: this.isWide,
            isSmall: this.type === 'editSmall' || this.type === 'detailSmall',
            navigateButtonsEnabled: navigateButtonsEnabled,
            previousButtonEnabled: previousButtonEnabled,
            nextButtonEnabled: nextButtonEnabled,
            hasMiddleTabs: hasMiddleTabs,
            middleTabDataList: middleTabDataList,
        };
    }

    private getDropdownItemDataList(type: 'detail' | 'edit' = 'detail'): (DropdownItem | false)[] {
        const dropdownGroups: DropdownItem[][] = [];

        const list = type === 'edit' ?
            this.dropdownEditItemList : this.dropdownItemList;

        list.forEach(item => {
            // For bc.
            if ((item as any) === false) {
                return;
            }

            const index = (item.groupIndex === undefined ? 9999 : item.groupIndex) + 100;

            if (dropdownGroups[index] === undefined) {
                dropdownGroups[index] = [];
            }

            dropdownGroups[index].push(item);
        });

        const dropdownItemList: (DropdownItem | false)[] = [];

        dropdownGroups.forEach(list => {
            list.forEach(it => dropdownItemList.push(it));

            dropdownItemList.push(false);
        });

        return dropdownItemList;
    }

    protected init() {
        this.entityType = this.model.entityType || this.model.name || 'Common';
        this.scope = this.options.scope || this.entityType;

        this.layoutName = this.options.layoutName || this.layoutName;
        this.detailLayout = this.options.detailLayout || this.detailLayout;

        this.type = this.options.type || this.type;

        this.buttonList = this.options.buttonList || this.buttonList;
        this.dropdownItemList = this.options.dropdownItemList || this.dropdownItemList;

        this.buttonList = Utils.cloneDeep(this.buttonList);
        this.buttonEditList = Utils.cloneDeep(this.buttonEditList);
        this.dropdownItemList = Utils.cloneDeep(this.dropdownItemList);
        this.dropdownEditItemList = Utils.cloneDeep(this.dropdownEditItemList);

        this.returnAfterCreate = this.options.returnAfterCreate ?? false;

        this.returnUrl = this.options.returnUrl || this.returnUrl;
        this.returnDispatchParams = this.options.returnDispatchParams || this.returnDispatchParams;

        this.exit = this.options.exit || this.exit;

        if (this.shortcutKeys) {
            this.shortcutKeys = Utils.cloneDeep(this.shortcutKeys);
        }
    }

    protected setup() {
        this.setupEventHandlers();

        if (typeof this.model === 'undefined') {
            throw new Error('Model has not been injected into record view.');
        }

        this.setupButtons();

        this.recordHelper = this.options.recordHelper ?? new ViewRecordHelper();

        this._initInlineEditSave();

        const collection = this.model.collection;

        if (collection) {
            this.listenTo(this.model, 'destroy', () => {
                if (this.model.id) {
                    collection.remove(this.model.id);
                }

                collection.trigger('sync', collection, {}, {});
            });

            if (this.options.indexOfRecord != null) {
                this.indexOfRecord = this.options.indexOfRecord!;
            } else {
                this.indexOfRecord = collection.indexOf(this.model);
            }
        }

        this.middlePanelDefs = {};
        this.middlePanelDefsList = [];

        if (this.getUser().isPortal() && !this.portalLayoutDisabled) {
            if (
                this.getMetadata()
                    .get(['clientDefs', this.scope ?? '_', 'additionalLayouts', `${this.layoutName}Portal`])
            ) {
                this.layoutName += 'Portal';
            }
        }

        this.numId = Math.floor((Math.random() * 10000) + 1).toString();

        // For testing purpose.
        $(window).on('fetch-record.' + this.cid, () => this._webSocketDebounceHelper.process());

        this.once('remove', () => {
            if (this.isChanged) {
                this.resetModelChanges();
            }
            this.setIsNotChanged();

            $(window).off('scroll.detail-' + this.numId);
            $(window).off('fetch-record.' + this.cid);
        });

        this.id = Utils.toDom(this.entityType ?? '_') + '-' + Utils.toDom(this.type) + '-' + this.numId;

        this.isNew = this.model.isNew();

        if (!this.editModeDisabled) {
            if ('editModeDisabled' in this.options) {
                this.editModeDisabled = this.options.editModeDisabled as boolean;
            }
        }

        this.confirmLeaveDisabled = this.options.confirmLeaveDisabled || this.confirmLeaveDisabled;

        this.buttonsDisabled = this.options.buttonsDisabled || this.buttonsDisabled;

        // for backward compatibility
        // @todo remove
        if ('buttonsPosition' in this.options && !this.options.buttonsPosition) {
            this.buttonsDisabled = true;
        }

        if ('isWide' in this.options) {
            this.isWide = this.options.isWide as boolean;
        }

        if ('sideView' in this.options) {
            this.sideView = this.options.sideView as string;
        }

        if ('bottomView' in this.options) {
            this.bottomView = this.options.bottomView as string;
        }

        this.sideDisabled = this.options.sideDisabled || this.sideDisabled;
        this.bottomDisabled = this.options.bottomDisabled || this.bottomDisabled;

        this.readOnly = this.options.readOnly || this.readOnly;

        if (!this.readOnly && !this.isNew) {
            this.readOnly = this.getMetadata().get(['clientDefs', this.scope ?? '_', 'editDisabled']) || false;
        }

        if (this.getMetadata().get(['clientDefs', this.scope ?? '_', 'createDisabled'])) {
            this.duplicateAction = false;
        }

        if ((this.getConfig().get('currencyList') || []).length <= 1) {
            this.convertCurrencyAction = false;
        }

        this.readOnlyLocked = this.readOnly;

        this.inlineEditDisabled = this.inlineEditDisabled ||
            this.getMetadata().get(['clientDefs', this.scope ?? '_', 'inlineEditDisabled']) ||
            false;

        this.inlineEditDisabled = this.options.inlineEditDisabled || this.inlineEditDisabled;
        this.navigateButtonsDisabled = this.options.navigateButtonsDisabled || this.navigateButtonsDisabled;
        this.dynamicLogicDefs = this.options.dynamicLogicDefs ?? this.dynamicLogicDefs;

        this.dataObject = this.options.dataObject || {};
        this.rootData = this.options.rootData || {};

        this.setupActionItems();
        this.setupBeforeFinal();
        this.setupDynamicBehavior();

        this._webSocketDebounceHelper = new DebounceHelper({
            interval: this._webSocketDebounceInterval,
            handler: () => this.handleRecordUpdate(),
        });

        if (
            !this.options.webSocketDisabled &&
            !this.isNew &&
            this.webSocketManager.isEnabled() &&
            this.getMetadata().get(['scopes', this.entityType ?? '_', 'object'])
        ) {
            this.subscribeToWebSocket();

            this.once('remove', () => {
                if (this.isSubscribedToWebSocket) {
                    this.unsubscribeFromWebSocket();
                }
            });
        }

        this.wait(
            this.getHelper().processSetupHandlers(this, this.setupHandlerType)
        );

        this.initInlineEditDynamicWithLogicInteroperability();

        this.forcePatchAttributeDependencyMap =
            this.getMetadata().get(['clientDefs', this.scope ?? '_', 'forcePatchAttributeDependencyMap']) ?? {};
    }

    private setupEventHandlers() {
        this.addHandler('click', '.button-container .action', (event, target) => {
            let actionItems = undefined;

            if (target.classList.contains('detail-action-item')) {
                actionItems = [...this.buttonList, ...this.dropdownItemList]
            } else if (target.classList.contains('edit-action-item')) {
                actionItems = [...this.buttonEditList, ...this.dropdownEditItemList];
            }

            Utils.handleAction(this, event as MouseEvent, target, {actionItems: actionItems});
        });

        this.addActionHandler('showMoreDetailPanels', () => this.showMoreDetailPanels());

        this.addHandler('click', '.middle-tabs > button', (_, target) => {
            this.selectTab(parseInt(target.dataset.tab as string));
        });
    }

    protected setupBeforeFinal() {
        if (!this.accessControlDisabled) {
            this.manageAccess();
        }

        this.attributes = this.model.getClonedAttributes();

        if (this.options.attributes) {
            this.model.setMultiple(this.options.attributes);
        }

        this.listenTo(this.model, 'sync', () => {
            this.attributes = this.model.getClonedAttributes();
        });

        this.listenTo(this.model, 'change', (m, o) => {
            if (o.sync) {
                for (const attribute in m.attributes) {
                    if (!m.hasChanged(attribute)) {
                        continue;
                    }

                    this.attributes[attribute] = Utils.cloneDeep(m.get(attribute));
                }

                return;
            }

            if (this.mode === this.MODE_EDIT || this.inlineEditModeIsOn) {
                this.setIsChanged();
            }
        });
    }

    /**
     * @protected
     */
    setupDynamicBehavior() {
        const dependencyDefs =
            Utils.clone(this.getMetadata().get(['clientDefs', this.entityType ?? '_', 'formDependency']) || {});

        // noinspection JSDeprecatedSymbols
        this.dependencyDefs = _.extend(dependencyDefs, this.dependencyDefs);

        this.initDependency();

        const dynamicLogic = {...this.getMetadata().get(`logicDefs.${this.entityType}`, {})};

        this.dynamicLogicDefs = _.extend(dynamicLogic, this.dynamicLogicDefs);

        this.initDynamicLogic();
        this.setupFieldLevelSecurity();
        this.initDynamicHandler();
    }

    /**
     * @private
     */
    _initInlineEditSave() {
        this.listenTo(this.recordHelper, 'inline-edit-save', (field, o) => {
            this.inlineEditSave(field, o);
        });
    }

    private inlineEditSave(field: string, options?: SaveOptions) {
        const view = this.getFieldView(field);

        if (!view) {
            throw new Error(`No field '${field}'.`);
        }

        options = _.extend({
            inline: true,
            field: field,
            afterValidate: () => {
                if (options?.bypassClose) {
                    return;
                }

                view.inlineEditClose(true)
            },
        }, options ?? {}) as Record<string, any> & SaveOptions;

        this.save(options)
            .then(() => {
                view.trigger('after:inline-save');
                view.trigger('after:save');


                if (options.bypassClose) {
                    view.initialAttributes = this.model.getClonedAttributes();
                }
            })
            .catch(reason => {
                if (reason === 'notModified') {
                    if (options.bypassClose) {
                        return;
                    }

                    view.inlineEditClose(true);

                    return;
                }

                if (reason === 'error') {
                    if (options.bypassClose) {
                        return;
                    }

                    const initialAttributes = {...view.initialAttributes};

                    view.inlineEdit()
                        .then(() => view.initialAttributes = initialAttributes);
                }
            });
    }

    private initInlineEditDynamicWithLogicInteroperability() {
        let blockEdit = false;

        const process = (type: string, field: string) => {
            if (!this.inlineEditModeIsOn || this.editModeDisabled) {
                return;
            }

            if (blockEdit) {
                return;
            }

            if (type === 'required') {
                const fieldView = this.getFieldView(field);

                if (fieldView && fieldView.validateRequired) {
                    fieldView.suspendValidationMessage();

                    try {
                        if (!fieldView.validateRequired()) {
                            return;
                        }
                    } catch (e) {}
                }
            }

            blockEdit = true;

            setTimeout(() => blockEdit = false, 300);

            setTimeout(() => {
                this.setEditMode();

                this.getFieldViewList()
                    .forEach(view => view.removeInlineEditLinks());
            }, 10);
        };

        this.on('set-field-required', field => process('required', field));
        this.on('set-field-option-list', field => process('options', field));
        this.on('reset-field-option-list', field => process('options', field));
    }

    private initDynamicHandler() {
        const dynamicHandlerClassName = this.dynamicHandlerClassName ||
            this.getMetadata().get(['clientDefs', this.scope ?? '_', 'dynamicHandler']);

        const init = (dynamicHandler: DynamicHandler) => {
            this.listenTo(this.model, 'change', (model, o) => {
                if ('onChange' in dynamicHandler) {
                    (dynamicHandler as any).onChange.call(dynamicHandler, model, o);
                }

                const changedAttributes = model.changedAttributes();

                for (const attribute in changedAttributes) {
                    const methodName = 'onChange' + Utils.upperCaseFirst(attribute);

                    if (methodName in dynamicHandler) {
                        // @ts-ignore
                        dynamicHandler[methodName]
                            .call(dynamicHandler, model, changedAttributes[attribute], o);
                    }
                }
            });

            if ('init' in dynamicHandler) {
                (dynamicHandler as any).init();
            }
        };

        if (dynamicHandlerClassName) {
            this.wait(
                new Promise(resolve => {
                    Espo.loader.require(dynamicHandlerClassName, DynamicHandler => {
                        const dynamicHandler = new DynamicHandler(this);

                        init(dynamicHandler);

                        resolve(undefined);
                    });
                })
            );
        }

        const handlerList = this.scope ?
            (this.getMetadata().get(['clientDefs', this.scope, 'dynamicHandlerList']) || []) as string[] : [];

        if (handlerList.length) {
            const self = this;

            const promiseList: Promise<any>[] = [];

            handlerList.forEach(className => {
                promiseList.push(
                    new Promise(resolve => {
                        Espo.loader.require(className, DynamicHandler => {
                            resolve(new DynamicHandler(self));
                        });
                    })
                );
            });

            this.wait(
                Promise.all(promiseList).then(list => {
                    list.forEach((dynamicHandler) => {
                        init(dynamicHandler);
                    });
                })
            );
        }
    }

    /**
     * @private
     */
    initShortcuts() {
        if (this.shortcutKeys && this.options.shortcutKeysEnabled) {
            this.shortcutManager.add(this, this.shortcutKeys);

            this.once('remove', () => {
                this.shortcutManager.remove(this);
            });
        }
    }

    protected setupFinal() {
        this.build();

        this.initShortcuts();

        if (!this.options.focusForCreate) {
            this.once('after:render', () => this.focusOnFirstDiv());
        }
    }

    protected setIsChanged() {
        this.isChanged = true;
        this.recordHelper.setIsChanged(true);

        if (this.confirmLeaveDisabled) {
            return;
        }

        this.setConfirmLeaveOut(true);
    }

    protected setIsNotChanged() {
        this.isChanged = false;
        this.recordHelper.setIsChanged(false);

        if (this.confirmLeaveDisabled) {
            return;
        }

        this.setConfirmLeaveOut(false);
    }

    protected switchToModelByIndex(indexOfRecord: number) {
        const collection = this.model.collection;

        if (!collection) {
            return;
        }

        const model = collection.at(indexOfRecord);

        if (!model) {
            console.error("Model is not found in collection by index.");

            return;
        }

        const id = model.id;
        const scope = this.entityType || this.scope;

        this.getRouter().navigate(`#${scope}/view/${id}`, {trigger: false});

        this.getRouter().dispatch(scope, 'view', {
            id: id,
            model: model,
            indexOfRecord: indexOfRecord,
            rootUrl: this.options.rootUrl,
        });
    }

    actionPrevious() {
        this.model.abortLastFetch();

        if (!this.model.collection) {
            return;
        }

        const collection = this.model.collection;

        if (this.indexOfRecord <= 0 && !collection.offset) {
            return;
        }

        if (
            this.indexOfRecord === 0 &&
            collection.offset > 0 &&
            collection.maxSize
        ) {
            collection.offset = Math.max(0, collection.offset - collection.maxSize);

            collection.fetch()
                .then(() => {
                    const indexOfRecord = collection.length - 1;

                    if (indexOfRecord < 0) {
                        return;
                    }

                    this.switchToModelByIndex(indexOfRecord);
                });

            return;
        }

        const indexOfRecord = this.indexOfRecord - 1;

        this.switchToModelByIndex(indexOfRecord);
    }

    actionNext() {
        this.model.abortLastFetch();

        if (!this.model.collection) {
            return;
        }

        const collection = this.model.collection;

        if (!(this.indexOfRecord < collection.total - 1 - collection.offset) && collection.total >= 0) {
            return;
        }

        if (collection.total === -2 && this.indexOfRecord >= collection.length - 1 - collection.offset) {
            return;
        }

        const indexOfRecord = this.indexOfRecord + 1;

        if (indexOfRecord <= collection.length - 1 - collection.offset) {
            this.switchToModelByIndex(indexOfRecord);

            return;
        }

        collection
            .fetch({
                more: true,
                remove: false,
            })
            .then(() => {
                this.switchToModelByIndex(indexOfRecord);
            });
    }

    // noinspection JSUnusedGlobalSymbols
    protected actionViewPersonalData() {
        this.createView('viewPersonalData', 'views/personal-data/modals/personal-data', {
            model: this.model
        }). then(view => {
            view.render();

            this.listenToOnce(view, 'erase', () => {
                this.clearView('viewPersonalData');
                this.model.fetch();
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    protected actionViewFollowers(data: Record<string, any>) {
        if (!this.entityType) {
            return;
        }

        const viewName = this.getMetadata()
                .get(['clientDefs', this.entityType, 'relationshipPanels', 'followers', 'viewModalView']) ||
            this.getMetadata().get(['clientDefs', 'User', 'modalViews', 'relatedList']) ||
            'views/modals/followers-list';

        const selectDisabled =
            !this.getUser().isAdmin() &&
            this.getAcl().getPermissionLevel('followerManagementPermission') === 'no' &&
            this.getAcl().getPermissionLevel('portalPermission') === 'no';

        const options = {
            model: this.model,
            link: 'followers',
            scope: 'User',
            title: this.translate('Followers'),
            filtersDisabled: true,
            url: `${this.entityType}/${this.model.id}/followers`,
            createDisabled: true,
            selectDisabled: selectDisabled,
            rowActionsView: 'views/user/record/row-actions/relationship-followers',
        } as Record<string, any>;

        if (data.viewOptions) {
            for (const item in data.viewOptions) {
                options[item] = data.viewOptions[item];
            }
        }

        Ui.notifyWait();

        this.createView('modalRelatedList', viewName, options).then(view => {
            Ui.notify();

            view.render();

            this.listenTo(view, 'action', (event, element) => {
                Utils.handleAction(this, event, element);
            });

            this.listenToOnce(view, 'close', () => {
                this.clearView('modalRelatedList');
            });

            view.listenTo(this.model, 'after:relate:followers', () => {
                this.model.fetch();
            });

            view.listenTo(this.model, 'after:unrelate:followers', () => {
                this.model.fetch();
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    async actionPrintPdf() {
        const view = new SelectTemplateModalView({
            entityType: this.entityType,
            onSelect: (models: Model[]) => {
                const model = models[0];

                const url = `?entryPoint=pdf&entityType=${this.entityType}&entityId=${this.model.id}` +
                    `&templateId=${model.id}`;

                window.open(url, '_blank');
            },
        });

        await this.assignView('modal', view);

        await view.render();
    }

    afterSave() {
        if (this.isNew) {
            Ui.success(this.translate('Created'));
        } else {
            Ui.success(this.translate('Saved'));
        }

        this.enableActionItems();

        this.setIsNotChanged();

        setTimeout(() => {
            this.unblockUpdateWebSocket();
        }, this.blockUpdateWebSocketPeriod);
    }

    beforeSave() {
        Ui.notify(this.translate('saving', 'messages'));

        this.blockUpdateWebSocket();
    }

    beforeBeforeSave() {
        this.disableActionItems();
    }

    afterSaveError() {
        this.enableActionItems();
    }

    afterNotModified() {
        const msg = this.translate('notModified', 'messages');

        Ui.warning(msg);

        this.enableActionItems();
        this.setIsNotChanged();
    }

    afterNotValid() {
        Ui.error(this.translate('Not valid'))

        this.enableActionItems();
    }

    /**
     * @internal
     */
    protected errorHandlerDuplicate(
        duplicates: any[],
        options: Record<string, any>,
        resolve: () => void,
        reject: (reason: 'error' | 'cancel') => void,
    ) {
        // noinspection BadExpressionStatementJS
        options;

        Ui.notify(false);

        this.createView('duplicate', 'views/modals/duplicate', {
            scope: this.entityType,
            duplicates: duplicates,
            model: this.model,
        }).then(view => {
            view.render();

            this.listenToOnce(view, 'save', () => {
                this.actionSave({
                    options: {
                        headers: {
                            'X-Skip-Duplicate-Check': 'true',
                        }
                    }
                })
                    .then(() => resolve())
                    .catch(() => reject('error'))
            });

            this.listenToOnce(view, 'cancel', () => reject('cancel'));
        });

        return true;
    }

    // noinspection JSUnusedGlobalSymbols
    protected errorHandlerModified(data: Record<string, any>, options: Record<string, any>) {
        Ui.notify(false);

        const versionNumber = data.versionNumber as number;
        const values = data.values || {};

        const attributeList = Object.keys(values);

        const diffAttributeList: string[] = [];

        attributeList.forEach(attribute => {
            if (this.attributes[attribute] !== values[attribute]) {
                diffAttributeList.push(attribute);
            }
        });

        if (diffAttributeList.length === 0) {
            setTimeout(() => {
                this.model.set('versionNumber', versionNumber, {silent: true});
                this.attributes.versionNumber = versionNumber;

                if (options.inline && options.field) {
                    this.inlineEditSave(options.field);

                    return;
                }

                this.actionSave();
            }, 5);

            return;
        }

        this.createView('dialog', 'views/modals/resolve-save-conflict', {
            model: this.model,
            attributeList: diffAttributeList,
            currentAttributes: Utils.cloneDeep(this.model.attributes),
            originalAttributes: Utils.cloneDeep(this.attributes),
            actualAttributes: Utils.cloneDeep(values),
        })
        .then(view => {
            view.render();

            this.listenTo(view, 'resolve', () => {
                this.model.set('versionNumber', versionNumber, {silent: true});
                this.attributes.versionNumber = versionNumber;

                for (const attribute in values) {
                    this.setInitialAttributeValue(attribute, values[attribute]);
                }
            });
        });
    }

    /**
     * Get a middle view.
     *
     */
    getMiddleView(): import('views/record/detail-middle').default {
        return this.getView('middle') as any;
    }

    /**
     * Get a side view.
     */
    protected getSideView(): import('views/record/detail-side').default {
        return this.getView('side') as any;
    }

    /**
     * Get a bottom view.
     */
    protected getBottomView(): import('views/record/detail-bottom').default {
        return this.getView('bottom') as any;
    }

    setReadOnly() {
        if (!this.readOnlyLocked) {
            this.readOnly = true;
        }

        const bottomView = this.getBottomView();

        if (bottomView && 'setReadOnly' in bottomView) {
            bottomView.setReadOnly();
        }

        const sideView = this.getSideView();

        if (sideView && 'setReadOnly' in sideView) {
            sideView.setReadOnly();
        }

        this.getFieldList().forEach((field) => {
            this.setFieldReadOnly(field);
        });
    }

    setNotReadOnly(onlyNotSetAsReadOnly: boolean = false) {
        if (!this.readOnlyLocked) {
            this.readOnly = false;
        }

        const bottomView = this.getBottomView();

        if (bottomView && 'setNotReadOnly' in bottomView) {
            bottomView.setNotReadOnly(onlyNotSetAsReadOnly);
        }

        const sideView = this.getSideView();

        if (sideView && 'setNotReadOnly' in sideView) {
            sideView.setNotReadOnly(onlyNotSetAsReadOnly);
        }

        this.getFieldList().forEach((field) => {
            if (onlyNotSetAsReadOnly) {
                if (this.recordHelper.getFieldStateParam(field, 'readOnly')) {
                    return;
                }
            }

            this.setFieldNotReadOnly(field);
        });
    }

    private manageAccessEdit(second: boolean = false) {
        if (this.isNew) {
            return;
        }

        const editAccess = this.getAcl().checkModel(this.model, 'edit', true);

        if (!editAccess || this.readOnlyLocked) {
            this.readOnly = true;

            this.hideActionItem('edit');

            if (this.selfAssignAction) {
                this.hideActionItem('selfAssign');
            }
        } else {
            this.showActionItem('edit');

            if (this.selfAssignAction) {
                this.hideActionItem('selfAssign');

                if (this.model.has('assignedUserId')) {
                    if (!this.model.get('assignedUserId')) {
                        this.showActionItem('selfAssign');
                    }
                }
            }

            if (!this.readOnlyLocked) {
                if (this.readOnly && second) {
                    if (this.isReady) {
                        this.setNotReadOnly(true);
                    } else {
                        this.on('ready', () => this.setNotReadOnly(true));
                    }
                }

                this.readOnly = false;
            }
        }

        if (editAccess === null) {
            this.listenToOnce(this.model, 'sync', () => {
                this.model.trigger('acl-edit-ready');

                this.manageAccessEdit(true);
            });
        }
    }

    private manageAccessDelete() {
        if (this.isNew) {
            return;
        }

        const deleteAccess = this.getAcl().checkModel(this.model, 'delete', true);

        if (!deleteAccess) {
            this.hideActionItem('delete');
        } else {
            this.showActionItem('delete');
        }

        if (deleteAccess === null) {
            this.listenToOnce(this.model, 'sync', () => this.manageAccessDelete());
        }
    }

    private manageAccessStream() {
        if (this.isNew) {
            return;
        }

        if (
            ['no', 'own'].includes(this.getAcl().getLevel('User', 'read') as string) &&
            this.getAcl().getPermissionLevel('portalPermission') === 'no'
        ) {
            this.hideActionItem('viewFollowers');

            return;
        }

        const streamAccess = this.getAcl().checkModel(this.model, 'stream', true);

        if (!streamAccess) {
            this.hideActionItem('viewFollowers');
        } else {
            this.showActionItem('viewFollowers');
        }

        if (streamAccess === null) {
            this.listenToOnce(this.model, 'sync', () => this.manageAccessStream());
        }
    }

    manageAccess() {
        this.manageAccessEdit();
        this.manageAccessDelete();
        this.manageAccessStream();
    }

    /**
     * Add a button.
     *
     */
    addButton(item: Button, toBeginning: boolean = false) {
        const name = item.name;

        if (!name) {
            return;
        }

        for (const it of this.buttonList) {
            if (it.name === name) {
                return;
            }
        }

        toBeginning ?
            this.buttonList.unshift(item) :
            this.buttonList.push(item);
    }

    /**
     * Add a dropdown item.
     */
    addDropdownItem(item: DropdownItem, toBeginning: boolean = false) {
        if (!item) {
            // For bc.
            return;
        }

        const name = item.name;

        if (!name) {
            return;
        }

        for (const item of this.dropdownItemList) {
            if (item.name === name) {
                return;
            }
        }

        toBeginning ?
            this.dropdownItemList.unshift(item) :
            this.dropdownItemList.push(item);
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Add an 'edit' mode button.
     *
     * @param item
     * @param [toBeginning]
     */
    addButtonEdit(item: Button, toBeginning = false) {
        const name = item.name;

        if (!name) {
            return;
        }

        for (const item of this.buttonEditList) {
            if (item.name === name) {
                return;
            }
        }

        toBeginning ?
            this.buttonEditList.unshift(item) :
            this.buttonEditList.push(item);
    }



    // noinspection JSUnusedGlobalSymbols
    /**
     * @deprecated Use `enableActionItems`.
     */
    enableButtons() {
        this.allActionItemsDisabled = false;
        this.reRenderButtons();
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @deprecated Use `disableActionItems`.
     */
    disableButtons() {
        this.allActionItemsDisabled = true;

        this.reRenderButtons();
    }

    /**
     * Remove a button or dropdown item.
     *
     * @param {string} name A name.
     */
    removeActionItem(name: string) {
        this.removeButton(name);
    }

    /**
     * @deprecated Use `removeActionItem`.
     *
     * @param {string} name A name.
     */
    removeButton(name: string) {
        for (const [i, item] of this.buttonList.entries()) {
            if (item.name === name) {
                this.buttonList.splice(i, 1);

                break;
            }
        }

        for (const [i, item] of this.dropdownItemList.entries()) {
            if (item.name === name) {
                this.dropdownItemList.splice(i, 1);

                break;
            }
        }

        if (!this.isRendered()) {
            return;
        }

        this.reRenderButtons();
    }

    /**
     * Convert a detail layout to an internal layout.
     *
     * @param layout A detail layout.
     */
    protected convertDetailLayout(layout: PanelDefs[]): Record<string, any>[] {
        this.panelFieldListMap = {};
        this.underShowMoreDetailPanelList = [];

        return new LayoutConverter().convert(layout, {
            selector: this.getSelector() as string,
            id: this.id,
            entityType: this.entityType,
            model: this.model,
            panelFieldListMap: this.panelFieldListMap,
            middlePanelDefs: this.middlePanelDefs,
            middlePanelDefsList: this.middlePanelDefsList,
            underShowMoreDetailPanelList: this.underShowMoreDetailPanelList,
            dynamicLogic: this.dynamicLogic,
            dynamicLogicDefs: this.dynamicLogicDefs,
            recordHelper: this.recordHelper,
            hidePanel: (name) => this.hidePanel(name),
            validateField: (name) => this.validateField(name),
            readOnly: this.readOnly,
            readOnlyLocked: this.readOnlyLocked,
            inlineEditDisabled: this.inlineEditDisabled,
            dataObject: this.dataObject,
            fieldsMode: this.fieldsMode,
        });
    }

    protected getGridLayout(callback: (items: any[]) => void) {
        if (this.gridLayout !== null) {
            callback(this.gridLayout);

            return;
        }

        if (this.detailLayout) {
            this.gridLayout = {
                type: this.gridLayoutType,
                layout: this.convertDetailLayout(this.detailLayout),
            };

            callback(this.gridLayout);

            return;
        }

        this.getHelper().layoutManager.get(this.entityType, this.layoutName, (detailLayout: any) => {
            if (this.hasModifyDetailLayout) {
                detailLayout = Utils.cloneDeep(detailLayout);

                this.modifyDetailLayout(detailLayout);
            }

            this.detailLayout = detailLayout as PanelDefs[];

            this.gridLayout = {
                type: this.gridLayoutType,
                layout: this.convertDetailLayout(this.detailLayout),
            };

            callback(this.gridLayout);
        });
    }

    /**
     * Create a side view.
     */
    protected createSideView() {
        const el = this.getSelector() || '#' + (this.id);

        this.createView('side', this.sideView!, {
            model: this.model,
            scope: this.scope,
            fullSelector: el + ' .side',
            type: this.type,
            readOnly: this.readOnly,
            inlineEditDisabled: this.inlineEditDisabled,
            recordHelper: this.recordHelper,
            recordViewObject: this,
            isReturn: this.options.isReturn,
            dataObject: this.dataObject,
        });
    }

    /**
     * Create a middle view.
     *
     */
    protected createMiddleView(callback?: (view: import('views/record/detail-middle').default) => void) {
        const el = this.getSelector() || '#' + (this.id);

        this.waitForView('middle');

        this.getGridLayout(layout => {
            if (
                this.hasTabs() &&
                this.options.isReturn &&
                this.isStoredTabForThisRecord()
            ) {
                this.selectStoredTab();
            }

            this.createView('middle', this.middleView, {
                model: this.model,
                scope: this.scope,
                type: this.type,
                layoutDefs: layout,
                fullSelector: el + ' .middle',
                layoutData: {
                    model: this.model,
                },
                recordHelper: this.recordHelper,
                recordViewObject: this,
                panelFieldListMap: this.panelFieldListMap,
            }, callback);
        });
    }

    /**
     * Create a bottom view.
     *
     */
    protected createBottomView() {
        const el = this.getSelector() || '#' + (this.id);

        this.createView('bottom', this.bottomView!, {
            model: this.model,
            scope: this.scope,
            fullSelector: el + ' .bottom',
            readOnly: this.readOnly,
            type: this.type,
            inlineEditDisabled: this.inlineEditDisabled,
            recordHelper: this.recordHelper,
            recordViewObject: this,
            portalLayoutDisabled: this.portalLayoutDisabled,
            isReturn: this.options.isReturn,
            dataObject: this.dataObject,
        });
    }

    /**
     * Create views.
     *
     * @param [callback]
     */
    protected build(callback?: (view: import('views/record/detail-middle').default) => void) {
        if (!this.sideDisabled && this.sideView) {
            this.createSideView();
        }

        if (this.middleView) {
            this.createMiddleView(callback);
        }

        if (!this.bottomDisabled && this.bottomView) {
            this.createBottomView();
        }
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Called after create.
     *
     * @return True if redirecting is processed.
     */
    exitAfterCreate(): boolean {
        if (!this.returnAfterCreate && this.model.id) {
            const url = `#${this.scope}/view/${this.model.id}`;

            this.getRouter().navigate(url, {trigger: false});

            this.getRouter().dispatch(this.scope, 'view', {
                id: this.model.id,
                rootUrl: this.options.rootUrl,
                model: this.model,
                isAfterCreate: true,
            });

            return true;
        }

        return false;
    }

    /**
     * Called after save or cancel. By default, redirects a page. Can be overridden in options.
     *
     * @param [after] Name of an action after which #exit is invoked.
     */
    protected exit(after?: string | 'create' | 'save' | 'cancel' | 'delete') {
        if (after) {
            const methodName = 'exitAfter' + Utils.upperCaseFirst(after);

            if (methodName in this) {
                // @ts-ignore
                const result = this[methodName]();

                if (result) {
                    return;
                }
            }
        }

        let url: string;
        let options: any;

        if (this.returnUrl) {
            url = this.returnUrl;
        } else {
            if (after === 'delete') {
                url = this.options.rootUrl ?? `#${this.scope}`;

                if (url !== `#${this.scope}`) {
                    this.getRouter().navigate(url, {trigger: true});

                    return;
                }

                this.getRouter().navigate(url, {trigger: false});
                this.getRouter().dispatch(this.scope, null, {isReturn: true});

                return;
            }

            if (this.model.id) {
                url = `#${this.scope}/view/${this.model.id}`;

                if (!this.returnDispatchParams) {
                    this.getRouter().navigate(url, {trigger: false});

                    options = {
                        id: this.model.id,
                        model: this.model,
                    };

                    if (this.options.rootUrl) {
                        options.rootUrl = this.options.rootUrl;
                    }

                    this.getRouter().dispatch(this.scope, 'view', options);
                }
            } else {
                url = this.options.rootUrl ?? '#' + this.scope;
            }
        }

        if (this.returnDispatchParams && this.returnDispatchParams.controller) {
            const controller = this.returnDispatchParams.controller;
            const action = this.returnDispatchParams.action;
            options = this.returnDispatchParams.options || {};

            this.getRouter().navigate(url, {trigger: false});
            this.getRouter().dispatch(controller, action, options);

            return;
        }

        this.getRouter().navigate(url, {trigger: true});
    }

    protected subscribeToWebSocket() {
        const topic = `recordUpdate.${this.entityType}.${this.model.id}`;

        this.recordUpdateWebSocketTopic = topic;
        this.isSubscribedToWebSocket = true;

        this.webSocketManager.subscribe(topic, () => this._webSocketDebounceHelper.process());
    }

    protected unsubscribeFromWebSocket() {
        if (!this.isSubscribedToWebSocket) {
            return;
        }

        this.webSocketManager.unsubscribe(this.recordUpdateWebSocketTopic);

        this.isSubscribedToWebSocket = false;
    }

    private async handleRecordUpdate() {
        if (this.updateWebSocketIsBlocked) {
            return;
        }

        if (this.inlineEditModeIsOn || this.mode === this.MODE_EDIT) {
            const m = this.model.clone();

            await m.fetch();

            if (this.inlineEditModeIsOn || this.mode === this.MODE_EDIT) {
                this.updatedAttributes = Utils.cloneDeep(m.attributes);
            }

            return;
        }

        await this.model.fetch({highlight: true});
    }

    /**
     * @internal
     * @param [toUnblock]
     */
    blockUpdateWebSocket(toUnblock: boolean = false) {
        this.updateWebSocketIsBlocked = true;

        if (toUnblock) {
            setTimeout(() => {
                this.unblockUpdateWebSocket();
            }, this.blockUpdateWebSocketPeriod);
        }
    }

    private unblockUpdateWebSocket() {
        this.updateWebSocketIsBlocked = false;
    }

    /**
     * Show more detail panels.
     */
    protected showMoreDetailPanels() {
        this.hidePanel('showMoreDelimiter');

        this.underShowMoreDetailPanelList.forEach(item => {
            this.showPanel(item);
        });
    }

    // noinspection JSUnusedGlobalSymbols
    protected getTabCount(): number {
        if (!this.hasTabs()) {
            return 0;
        }

        let count = 1;

        (this.detailLayout || []).forEach(item => {
            if (item.tabBreak) {
                count ++;
            }
        });

        return count;
    }

    protected hasTabs(): boolean {
        if (typeof this._hasMiddleTabs !== 'undefined') {
            return this._hasMiddleTabs;
        }

        if (!this.detailLayout) {
            return false;
        }

        for (const item of this.detailLayout) {
            if (item.tabBreak) {
                this._hasMiddleTabs = true;

                return true;
            }
        }

        this._hasMiddleTabs = false;

        return false;
    }

    private getMiddleTabDataList(): {label: string}[] {
        const currentTab = this.currentTab;

        const panelDataList = this.middlePanelDefsList;

        return panelDataList
            .filter((item, i) => i === 0 || item.tabBreak)
            .map((item, i) => {
                let label = item.tabLabel;

                let hidden = false;

                if (i > 0) {
                    hidden = panelDataList
                        .filter(panel => panel.tabNumber === i)
                        .findIndex(panel => !this.recordHelper.getPanelStateParam(panel.name, 'hidden')) === -1;
                }

                if (!label) {
                    label = i === 0 ?
                        this.translate('Overview') :
                        (i + 1).toString();
                } else if (label.substring(0, 7) === '$label:') {
                    label = this.translate(label.substring(7), 'labels', this.scope);
                } else if (label[0] === '$') {
                    label = this.translate(label.substring(1), 'tabs', this.scope);
                }

                return {
                    label: label,
                    isActive: currentTab === i,
                    hidden: hidden,
                };
            });
    }

    /**
     * Select a tab.
     */
    protected selectTab(tab: number) {
        this.currentTab = tab;

        $('.popover.in').removeClass('in');

        this.whenRendered().then(() => {
            this.$el.find('.middle-tabs > button').removeClass('active');
            this.$el.find(`.middle-tabs > button[data-tab="${tab}"]`).addClass('active');

            this.$el.find('.middle > .panel[data-tab]').addClass('tab-hidden');
            this.$el.find(`.middle > .panel[data-tab="${tab}"]`).removeClass('tab-hidden');

            this.adjustMiddlePanels();
            this.recordHelper.trigger('panel-show');
        });

        this.storeTab();
    }

    private storeTab() {
        const key = 'tab_middle';
        const keyRecord = 'tab_middle_record';

        this.getSessionStorage().set(key, this.currentTab);
        this.getSessionStorage().set(keyRecord, `${this.entityType}_${this.model.id}`);
    }

    private selectStoredTab() {
        const key = 'tab_middle';

        const tab = this.getSessionStorage().get(key);

        if (tab > 0) {
            this.selectTab(tab);
        }
    }

    private isStoredTabForThisRecord() {
        const keyRecord = 'tab_middle_record';

        return this.getSessionStorage().get(keyRecord) === `${this.entityType}_${this.model.id}`;
    }

    protected onInvalid(invalidFieldList: string[]) {
        if (!this.hasTabs()) {
            return;
        }

        const tabList = [];

        for (const field of invalidFieldList) {
            const view = this.getMiddleView().getFieldView(field);

            if (!view) {
                continue;
            }

            const tabString = view.$el
                .closest('.panel.tab-hidden')
                .attr('data-tab');

            const tab = parseInt(tabString);

            if (tabList.indexOf(tab) !== -1) {
                continue;
            }

            tabList.push(tab);
        }

        if (!tabList.length) {
            return;
        }

        const $tabs = this.$el.find('.middle-tabs');

        tabList.forEach(tab => {
            const $tab = $tabs.find(`> [data-tab="${tab.toString()}"]`);

            $tab.addClass('invalid');

            $tab.one('click', () => {
                $tab.removeClass('invalid');
            });
        })
    }


    private controlTabVisibilityShow(tab: number) {
        if (!this.hasTabs() || tab === 0) {
            return;
        }

        if (this.isBeingRendered()) {
            this.once('after:render', () => this.controlTabVisibilityShow(tab));

            return;
        }

        this.$el.find(`.middle-tabs > [data-tab="${tab.toString()}"]`).removeClass('hidden');
    }

    private controlTabVisibilityHide(tab: number) {
        if (!this.hasTabs() || tab === 0) {
            return;
        }

        if (this.isBeingRendered()) {
            this.once('after:render', () => this.controlTabVisibilityHide(tab));

            return;
        }

        const panelList = this.middlePanelDefsList.filter(panel => panel.tabNumber === tab);

        const allIsHidden = panelList
            .findIndex(panel => !this.recordHelper.getPanelStateParam(panel.name, 'hidden')) === -1;

        if (!allIsHidden) {
            return;
        }

        const $tab = this.$el.find(`.middle-tabs > [data-tab="${tab.toString()}"]`);

        $tab.addClass('hidden');

        if (this.currentTab === tab) {
            this.selectTab(0);
        }
    }

    private adjustMiddlePanels() {
        if (!this.isRendered() || !this.$middle.length) {
            return;
        }

        const $panels = this.$middle.find('> .panel');
        const $bottomPanels = this.$bottom ? this.$bottom.find('> .panel') : null;

        $panels
            .removeClass('first')
            .removeClass('last')
            .removeClass('in-middle');

        const $visiblePanels = $panels.filter(`:not(.tab-hidden):not(.hidden)`);

        $visiblePanels.each((i, el) => {
            const $el = $(el);

            if (i === $visiblePanels.length - 1) {
                if ($bottomPanels && $bottomPanels.first().hasClass('sticked')) {
                    if (i === 0) {
                        $el.addClass('first');

                        return;
                    }

                    $el.addClass('in-middle');

                    return;
                }

                if (i === 0) {
                    return;
                }

                $el.addClass('last');

                return;
            }

            if (i > 0 && i < $visiblePanels.length - 1) {
                $el.addClass('in-middle');

                return;
            }

            if (i === 0) {
                $el.addClass('first');
            }
        });
    }

    protected focusForEdit() {
        this.$el
            .find('.field:not(.hidden) .form-control:not([disabled])')
            .first()
            .focus();
    }

    protected focusForCreate() {
        this.$el
            .find('.form-control:not([disabled])')
            .first()
            .focus();
    }

    protected handleShortcutKeyCtrlEnter(event: KeyboardEvent) {
        const action = this.shortcutKeyCtrlEnterAction;

        if (this.inlineEditModeIsOn || this.buttonsDisabled || !action) {
            return;
        }

        if (this.mode !== this.MODE_EDIT) {
            return;
        }

        if (!this.hasAvailableActionItem(action)) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (document.activeElement instanceof HTMLInputElement) {
            // Fields may need to fetch data first.
            document.activeElement.dispatchEvent(new Event('change', {bubbles: true}));
        }

        const methodName = 'action' + Utils.upperCaseFirst(action);

        // @ts-ignore
        this[methodName]();
    }

    private handleShortcutKeyCtrlS(event: KeyboardEvent) {
        if (this.inlineEditModeIsOn || this.buttonsDisabled) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (this.mode !== this.MODE_EDIT) {
            return;
        }

        if (!this.saveAndContinueEditingAction) {
            return;
        }

        if (!this.hasAvailableActionItem('saveAndContinueEditing')) {
            return;
        }

        this.actionSaveAndContinueEditing();
    }

    private handleShortcutKeyCtrlSpace(e: KeyboardEvent) {
        if (this.inlineEditModeIsOn || this.buttonsDisabled) {
            return;
        }

        if (this.type !== this.TYPE_DETAIL || this.mode !== this.MODE_DETAIL) {
            return;
        }

        if (Utils.isKeyEventInTextInput(e)) {
            return;
        }

        if (!this.hasAvailableActionItem('edit')) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        this.actionEdit();

        if (!this.editModeDisabled) {
            setTimeout(() => this.focusForEdit(), 200);
        }
    }

    protected handleShortcutKeyEscape(event: KeyboardEvent) {
        if (this.inlineEditModeIsOn || this.buttonsDisabled) {
            return;
        }

        if (this.type !== this.TYPE_DETAIL || this.mode !== this.MODE_EDIT) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        // Fetching a currently edited form element.
        this.model.setMultiple(this.fetch());

        if (this.isChanged) {
            this.confirm({message: this.translate('confirmLeaveOutMessage', 'messages')})
                .then(() => this.actionCancelEdit());

            return;
        }

        this.actionCancelEdit();
    }

    protected handleShortcutKeyCtrlAltEnter(event: KeyboardEvent) {
        // noinspection BadExpressionStatementJS
        event;
    }

    /**
     * @internal
     */
    handleShortcutKeyControlBackslash(event: KeyboardEvent) {
        if (!this.hasTabs()) {
            return;
        }

        const $buttons = this.$el.find('.middle-tabs > button:not(.hidden)');

        if ($buttons.length === 1) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        let index = $buttons.toArray().findIndex((el: any) => $(el).hasClass('active'));

        index++;

        if (index >= $buttons.length) {
            index = 0;
        }

        const $tab = $($buttons.get(index));

        const tab = parseInt($tab.attr('data-tab') ?? '0');

        this.selectTab(tab);

        if (this.mode === this.MODE_EDIT) {
            setTimeout(() => {
                this.$middle
                    .find(`.panel[data-tab="${tab}"] .cell:not(.hidden)`)
                    .first()
                    .trigger('focus');
            }, 50);

            return;
        }

        this.$el
            .find(`.middle-tabs button[data-tab="${tab}"]`)
            .trigger('focus');
    }

    protected handleShortcutKeyControlArrowLeft(event: KeyboardEvent) {
        if (this.inlineEditModeIsOn || this.buttonsDisabled) {
            return;
        }

        if (this.navigateButtonsDisabled) {
            return;
        }

        if (this.type !== this.TYPE_DETAIL || this.mode !== this.MODE_DETAIL) {
            return;
        }

        if (Utils.isKeyEventInTextInput(event)) {
            return;
        }

        const $button = this.$el.find('button[data-action="previous"]');

        if (!$button.length || $button.hasClass('disabled')) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        this.actionPrevious();
    }

    protected handleShortcutKeyControlArrowRight(event: KeyboardEvent) {
        if (this.inlineEditModeIsOn || this.buttonsDisabled) {
            return;
        }

        if (this.navigateButtonsDisabled) {
            return;
        }

        if (this.type !== this.TYPE_DETAIL || this.mode !== this.MODE_DETAIL) {
            return;
        }

        if (Utils.isKeyEventInTextInput(event)) {
            return;
        }

        const $button = this.$el.find('button[data-action="next"]');

        if (!$button.length || $button.hasClass('disabled')) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        this.actionNext();
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Get a current mode.
     *
     * @since 8.0.0
     */
    getMode(): 'detail' | 'edit' {
        return this.mode;
    }

    /**
     * @internal
     * @since 9.2.0
     */
    setupReuse() {
        this.initShortcuts();
    }

    protected setupButtons() {
        if (this.buttonsDisabled) {
            return;
        }

        const buttonsView = new DetailRecordButtonsView({
            entityType: this.entityType,
            dataProvider: () => {
                return {
                    buttonList: this.buttonList,
                    dropdownItemList: this.getDropdownItemDataList(),
                    allDisabled: this.allActionItemsDisabled,
                };
            },
            actionClassName: 'detail-action-item',
        });

        this.assignView('buttons', buttonsView);

        if (this.type === 'detail') {
            const editButtonsView = new DetailRecordButtonsView({
                entityType: this.entityType,
                dataProvider: () => {
                    return {
                        buttonList: this.buttonEditList,
                        dropdownItemList: this.getDropdownItemDataList('edit'),
                        allDisabled: this.allActionItemsDisabled,
                    };
                },
                actionClassName: 'edit-action-item',
            });

            this.assignView('editButtons', editButtonsView);
        }
    }

    private getButtonsView(): DetailRecordButtonsView | null {
        return this.getView<DetailRecordButtonsView>('buttons');
    }

    private getEditButtonsView(): DetailRecordButtonsView | null {
        return this.getView<DetailRecordButtonsView>('editButtons');
    }

    private reRenderButtons() {
        this.getButtonsView()?.reRender({buffer: true});
        this.getEditButtonsView()?.reRender({buffer: true});
    }

    /**
     * @internal
     */
    protected modifyDetailLayout(layout: any) {
        // noinspection BadExpressionStatementJS
        layout;
    }
}

export default DetailRecordView;

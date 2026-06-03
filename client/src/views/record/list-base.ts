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

import View from 'view';
import MassActionHelper from 'helpers/mass-action';
import ExportHelper from 'helpers/export';
import RecordModal from 'helpers/record-modal';
import SelectProvider from 'helpers/list/select-provider';
import RecordListSettingsView from 'views/record/list/settings';
import ListSettingsHelper from 'helpers/list/settings';
import StickyBarHelper from 'helpers/list/misc/sticky-bar';
import ListColumnResizeHelper from 'helpers/record/list/column-resize';
import ListColumnWidthControlHelper from 'helpers/record/list/column-width-control';
import _ from 'underscore';
import Utils from 'utils';
import Ui from 'ui';
import type Collection from 'collection';
import type {WhereItem} from 'collection';
import type Model from 'model';
import Ajax from 'ajax';
import type MassUpdateModalView from 'views/modals/mass-update';

import type {Button, DropdownItem, MassActionItem} from 'views/record/list';

/**
 * List view options.
 *
 * @internal
 */
export interface ListBaseRecordViewOptions<TLayout = any> {
    /**
     * A collection.
     */
    collection?: Collection;
    /**
     * A layout.
     */
    listLayout?: TLayout;
    /**
     * List layouts for entity types.
     */
    multiListLayout?: Record<string, TLayout>;
    /**
     * A type.
     */
    type?: string | 'list' | 'listSmall';
    /**
     * A layout name.
     */
    layoutName?: string;
    /**
     * To show row checkboxes.
     */
    checkboxes?: boolean;
    /**
     * Clicking on the record link will trigger the 'select' event.
     */
    selectable?: boolean;
    /**
     * Do not build rows on initialization. Use when the collection will be fetched afterward.
     */
    skipBuildRows?: boolean;
    /**
     * Disable buttons.
     */
    buttonsDisabled?: boolean;
    /**
     * Disable select-all-results.
     */
    checkAllResultDisabled?: boolean;
    /**
     * To enable the pagination.
     */
    pagination?: boolean;
    /**
     * Disable the header.
     */
    headerDisabled?: boolean;
    /**
     * Disable the no-data label (when no results).
     */
    noDataDisabled?: boolean;
    /**
     * A row actions view.
     */
    rowActionsView?: string | null;
    /**
     * Disable row actions.
     */
    rowActionsDisabled?: boolean;
    /**
     * The show-more button.
     */
    showMore?: boolean;
    /**
     * Keep a current root URL.
     */
    keepCurrentRootUrl?: boolean;
    /**
     * Disable the sticky bar.
     */
    stickyBarDisabled?: boolean;
    /**
     * To make bar sticky regardless of scrolling.
     */
    forceStickyBar?: boolean;
    /**
     * Disable mass actions.
     */
    massActionsDisabled?: boolean;
    /**
     * Dropdown items.dropdownItem
     */
    dropdownItemList?: DropdownItem[];
    /**
     * Mandatory select attributes. Attributes to be selected regardless being in the layout.
     */
    mandatorySelectAttributeList?: string[];
    /**
     * Disable edit.
     */
    editDisabled?: boolean;
    /**
     * Disable remove.
     */
    removeDisabled?: boolean;
    /**
     * To show a record count.
     */
    showCount?: boolean;
    /**
     * Force displaying the top bar even if empty.
     */
    forceDisplayTopBar?: boolean;
    /**
     * Enable the 'unlink' mass-action.
     */
    unlinkMassAction?: boolean;
    /**
     * Row-actions options.
     */
    rowActionsOptions?: Record<string, unknown>;
    /**
     * Additional row-action list.
     */
    additionalRowActionList?: string[];
    /**
     * Enable settings dropdown.
     */
    settingsEnabled?: boolean;
    /**
     * A settings helper.
     */
    settingsHelper?: ListSettingsHelper;
    /**
     * Display total count.
     */
    displayTotalCount?: boolean;
    /**
     * Root data.
     */
    rootData?: Record<string, unknown>;
    /**
     * Column resize. Actual only if the settings is enabled.
     */
    columnResize?: boolean;
    /**
     * An on-select callback. Actual if selectable.
     * @since 9.1.0
     */
    onSelect?: (models: Model[]) => void;
    /**
     * Force settings
     * @since 9.2.0
     */
    forceSettings?: boolean;
    /**
     * Force select all result.
     * @since 9.2.0
     */
    forceAllResultSelectable?: boolean;
    /**
     * Where item for select all result.
     * @since 9.2.0
     */
    allResultWhereItem?: WhereItem;
    /**
     * To store settings.
     * @since 10.0.0
     * @default true
     */
    storeSettings?: boolean;
}

/**
 * @internal
 */
export interface ListBaseRecordViewSchema<TLayout> {
    /**
     * A collection.
     */
    collection: Collection;
    /**
     * Options.
     */
    options: ListBaseRecordViewOptions<TLayout>;
    /**
     * A model for related lists.
     *
     * @internal
     */
    model?: Model;
}

/**
 * @internal
 */
abstract class ListBaseRecordView<
    TLayout extends any,
    S extends ListBaseRecordViewSchema<TLayout>,
> extends View<S> {

    constructor(options: S['options'] & {collection: S['collection']}) {
        super(options);
    }

    protected template: string = 'record/list'

    /**
     * A type. Can be 'list', 'listSmall'.
     */
    protected type: string = 'list'

    protected name: string = 'list'

    /**
     * If true checkboxes will be shown. Can be overridden by an option parameter.
     */
    protected checkboxes: boolean = true

    /**
     * If true clicking on the record link will trigger the 'select' event with model passed.
     * Can be overridden by an option parameter.
     */
    protected selectable: boolean = false

    /**
     * A row-actions view. A dropdown on the right side.
     */
    protected rowActionsView: string | null = 'views/record/row-actions/default'

    /**
     * Disable row-actions. Can be overridden by an option parameter.
     */
    protected rowActionsDisabled: boolean = false

    /**
     * An entity type. Set automatically.
     */
    protected entityType: string | null = null

    /**
     * A scope. Set automatically.
     */
    protected scope: string | null = null

    /**
     * @internal
     */
    protected _internalLayoutType: string = 'list-row'

    /**
     * A selector to a list container.
     */
    protected listContainerEl: string = '.list > table > tbody'

    /**
     * To show number of records. Can be overridden by an option parameter.
     */
    protected showCount: boolean = true

    protected rowActionsColumnWidth: number = 25

    protected checkboxColumnWidth: number = 40

    /**
     * A button list.
     */
    protected buttonList: Button[] = []

    /**
     * A dropdown item list. Can be overridden by an option parameter.
     */
    protected dropdownItemList: DropdownItem[] = []

    /**
     * Disable the header. Can be overridden by an option parameter.
     */
    protected headerDisabled: boolean = false

    /**
     * Disable mass actions. Can be overridden by an option parameter.
     */
    protected massActionsDisabled: boolean = false

    /**
     * Disable a portal layout usage. Can be overridden by an option parameter.
     */
    protected portalLayoutDisabled: boolean = false

    /**
     * Mandatory select attributes. Can be overridden by an option parameter.
     * Attributes to be selected regardless being in the layout.
     */
    protected mandatorySelectAttributeList: string[] | null = null

    /**
     * A layout name. If null, a value from `type` property will be used.
     * Can be overridden by an option parameter.
     */
    protected layoutName: string | null = null

    /**
     * A scope name for layout loading. If null, an entity type of collection will be used.
     * Can be overridden by an option parameter.
     */
    protected layoutScope: string | null = null

    /**
     * To disable field-level access check for a layout.
     * Can be overridden by an option parameter.
     */
    protected layoutAclDisabled: boolean = false

    /**
     * A setup-handler type.
     */
    protected setupHandlerType: string = 'record/list'

    /**
     * @internal
     */
    private checkboxesDisabled: boolean = false

    /**
     * Force displaying the top bar even if empty. Can be overridden by an option parameter.
     */
    protected forceDisplayTopBar: boolean = false

    /**
     * Where to display the pagination. Can be overridden by an option parameter.
     */
    protected pagination: boolean = false

    /**
     * To display a table header with column names. Can be overridden by an option parameter.
     */
    protected header: boolean = true

    /**
     * A show-more button. Can be overridden by an option parameter.
     */
    protected showMore: boolean = true

    /**
     * Column resize.
     * @since 9.0.0
     */
    protected columnResize = true

    /**
     * A mass-action list.
     */
    protected massActionList: string[] = [
        'remove',
        'merge',
        'massUpdate',
        'export',
    ]

    /**
     * A mass-action list available when selecting all results.
     */
    protected checkAllResultMassActionList: string[] = [
        'remove',
        'massUpdate',
        'export',
    ]

    /**
     * A forced mass-action list.
     */
    protected forcedCheckAllResultMassActionList: string[] | null = null

    /**
     * Disable quick-detail (viewing a record in modal)
     */
    protected quickDetailDisabled: boolean = false

    /**
     * Disable quick-edit (editing a record in modal)
     */
    protected quickEditDisabled: boolean = false

    /**
     * Force settings.
     */
    protected forceSettings: boolean = false

    /**
     * Disable settings.
     */
    protected settingsDisabled: boolean = false

    /**
     * A list layout. Can be overridden by an option parameter.
     * If null, then will be loaded from the backend (using the `layoutName` value).
     */
    protected listLayout: TLayout | null = null

    protected multiListLayout: Record<string, TLayout> | null = null

    private _internalLayout: any = null

    /**
     * A list of record IDs currently selected. Only for reading.
     */
    protected checkedList: string[]

    /**
     * Whether all results currently selected. Only for reading.
     */
    protected allResultIsChecked: boolean = false

    /**
     * Disable the ability to select all results. Can be overridden by an option parameter.
     */
    protected checkAllResultDisabled: boolean = false

    /**
     * Disable buttons. Can be overridden by an option parameter.
     */
    protected buttonsDisabled: boolean = false

    /**
     * Disable edit. Can be overridden by an option parameter.
     */
    protected editDisabled: boolean = false

    /**
     * Disable remove. Can be overridden by an option parameter.
     */
    protected removeDisabled: boolean = false

    /**
     * Disable a stick-bar. Can be overridden by an option parameter.
     */
    protected stickyBarDisabled: boolean = false

    /**
     * To show sticky bar regardless of scrolling.
     */
    protected forceStickyBar: boolean = false

    /**
     * Disable the follow/unfollow mass action.
     */
    protected massFollowDisabled: boolean = false

    /**
     * Disable the print-pdf mass action.
     */
    protected massPrintPdfDisabled: boolean = false

    /**
     * Disable the convert-currency mass action.
     */
    protected massConvertCurrencyDisabled: boolean = false

    /**
     * Disable mass-update.
     */
    protected massUpdateDisabled: boolean = false

    /**
     * Disable export.
     */
    protected exportDisabled: boolean = false

    /**
     * Disable merge.
     */
    protected mergeDisabled: boolean = false

    /**
     * Disable the no-data label (when no result).
     */
    protected noDataDisabled: boolean = false

    /**
     * Disable pagination.
     */
    protected paginationDisabled: boolean = false

    private _focusedCheckbox: HTMLInputElement | null = null

    private $selectAllCheckbox: JQuery

    private _disabledCheckboxes: boolean = false

    private massActionDefs: Record<string, MassActionItem>

    /**
     * Data to pass to record views.
     *
     * @since 9.0.0
     */
    protected rootData: Record<string, unknown>

    /**
     * @internal
     */
    protected _additionalRowActionList: any[]

    private _columnResizeHelper: import('helpers/record/list/column-resize').default

    /**
     * @since 9.1.1
     */
    protected collectionEventSyncList: string[]

    private noAllResultMassActions: boolean

    /**
     * @since 10.0.0
     */
    protected starredAttribute: string = 'isStarred'

    /**
     * @type {boolean}
     * @since 10.0.0
     */
    protected hasStars: boolean

    private _stickyBarHelper: StickyBarHelper | null = null

    /**
     * @internal
     */
    protected displayTotalCount: boolean

    private _fontSizeFactor: any;
    private _renderEmpty: boolean;

    /**
     * @internal
     */
    private rowList: string[]

    private noRebuild: boolean | null

    /**
     * @internal
     */
    protected layoutLoadCallbackList: ((layout: TLayout) => void)[]

    /**
     * @internal
     */
    protected layoutIsBeingLoaded: boolean

    /**
     * @internal
     */
    protected _listSettingsHelper: ListSettingsHelper

    private _cachedFilteredListLayout: TLayout
    private _cachedScopeForbiddenFieldList: string[]

    /**
     * @internal
     */
    protected _rowActionHandlers: Record<string, any>

    focusOnList() {
        const element = this.element.querySelector<HTMLElement>('.list');

        if (!element) {
            return;
        }

        element.focus({preventScroll: true});
    }

    private goToPage(page: 'first' | 'last' | 'next' | 'previous' | 'current') {
        Ui.notifyWait();

        const onSync = () => {
            Ui.notify();

            this.trigger('after:paginate');
            this.focusOnList();
        };

        if (page === 'current') {
            this.collection.fetch().then(() => onSync());
            this.deactivate();

            return;
        }

        if (page === 'next') {
            this.collection.nextPage().then(() => onSync());
        } else if (page === 'previous') {
            this.collection.previousPage().then(() => onSync());
        } else if (page === 'last') {
            this.collection.lastPage().then(() => onSync());
        } else if (page === 'first') {
            this.collection.firstPage().then(() => onSync());
        }

        this.trigger('paginate');
        this.deactivate();
    }

    private checkboxClick(element: HTMLInputElement, checked: boolean) {
        const id = element.dataset.id as string;

        if (checked) {
            this._checkRecord(id as string, element);

            return;
        }

        this._uncheckRecord(id as string, element);
    }

    protected async resetCustomOrder() {
        this.collection.offset = 0;
        this.collection.resetOrderToDefault();
        this.collection.trigger('order-changed');

        await this.collection.fetch();

        this.trigger('sort', {
            orderBy: this.collection.orderBy,
            order: this.collection.order,
        });
    }

    protected toggleSort(orderBy: string) {
        let asc = true;

        if (orderBy === this.collection.orderBy && this.collection.order === 'asc') {
            asc = false;
        }

        const order = asc ? 'asc' : 'desc';

        Ui.notifyWait();

        const maxSizeLimit = this.getConfig().get('recordListMaxSizeLimit') || 200;

        while (this.collection.length > maxSizeLimit) {
            this.collection.pop();
        }

        this.collection.offset = 0;

        this.collection
            .sort(orderBy, order)
            .then(() => {
                Ui.notify(false);

                this.trigger('sort', {orderBy: orderBy, order: order});
            })

        this.collection.trigger('order-changed');

        this.deactivate();
    }

    /**
     * @internal
     */
    toShowStickyBar(): boolean {
        return this.getCheckedIds().length > 0 || this.isAllResultChecked() || this.pagination;
    }

    private initStickyBar() {
        this._stickyBarHelper = new StickyBarHelper(this, {
            force: this.forceStickyBar,
        });
    }

    protected showActions() {
        this.$el.find('.actions-button').removeClass('hidden');

        if (
            !this.options.stickyBarDisabled &&
            !this.stickyBarDisabled &&
            this.massActionList.length
        ) {
            if (!this._stickyBarHelper) {
                this.initStickyBar();
            }
        }
    }

    protected hideActions() {
        this.$el.find('.actions-button').addClass('hidden');

        if (this._stickyBarHelper && (!this.pagination || this.forceStickyBar)) {
            this._stickyBarHelper.hide();
        }
    }

    protected selectAllHandler(isChecked: boolean = false) {
        this.checkedList = [];

        if (isChecked) {
            this.$el.find('input.record-checkbox').prop('checked', true);

            this.showActions();

            this.collection.models.forEach(model => {
                if (model.id) {
                    this.checkedList.push(model.id);
                }
            });

            this.$el.find('.list > table tbody tr').addClass('active');
        } else {
            if (this.allResultIsChecked) {
                this.unselectAllResult();
            }

            this.$el.find('input.record-checkbox').prop('checked', false);
            this.hideActions();
            this.$el.find('.list > table tbody tr').removeClass('active');
        }

        this.trigger('check');
    }

    protected data(): Record<string, any> {
        const moreCount = this.collection.total - this.collection.length - this.collection.offset;
        let checkAllResultDisabled = this.checkAllResultDisabled;

        if (!this.massActionsDisabled) {
            if (!this.checkAllResultMassActionList.length) {
                checkAllResultDisabled = true;
            }
        }

        const displayTotalCount = this.displayTotalCount && this.collection.total > 0 && !this.pagination;

        let topBar = this.forceDisplayTopBar ||
            this.collection.length && (
                this.pagination ||
                this.checkboxes ||
                (this.buttonList.length && !this.buttonsDisabled) ||
                (this.dropdownItemList.length && !this.buttonsDisabled) ||
                displayTotalCount
            );

        if (!topBar && this.pagination && !this.collection.length && this.collection.offset > 0) {
            topBar = true;
        }

        if (this.forceStickyBar) {
            topBar = false;
        }

        const checkboxes = this.checkboxes && this.massActionList.length;

        const displayActionsButtonGroup = checkboxes || this.buttonList.length ||
            this.dropdownItemList.length;

        const hasStickyBar = this.forceStickyBar || displayActionsButtonGroup || this.pagination;

        const noDataDisabled = this.noDataDisabled || this._renderEmpty;

        const rowDataList = this.rowList ?
            this.rowList.map(id => {
                return {
                    id: id,
                    isStarred: this.hasStars && this.collection.get(id) ?
                        this.collection.get(id)?.attributes[this.starredAttribute] :
                        false,
                };
            }) : [];

        const checkboxColumnWidth = (this.checkboxColumnWidth * this._fontSizeFactor).toString() + 'px';

        return {
            scope: this.scope,
            collectionLength: this.collection.models.length,
            entityType: this.entityType,
            header: this.header,
            hasColumnResize: this._hasColumnResize(),
            headerDefs: this._getHeaderDefs(),
            hasPagination: this.hasPagination(),
            showMoreActive: this.collection.hasMore(),
            showMoreEnabled: this.showMore,
            showCount: this.showCount && this.collection.total > 0,
            moreCount: moreCount,
            checkboxes: this.checkboxes,
            massActionDataList: this.getMassActionDataList(),
            rowList: this.rowList, // For bc.
            rowDataList: rowDataList,
            topBar: topBar,
            checkAllResultDisabled: checkAllResultDisabled,
            buttonList: this.buttonList,
            dropdownItemList: this.dropdownItemList,
            displayTotalCount: displayTotalCount,
            displayActionsButtonGroup: displayActionsButtonGroup,
            totalCountFormatted: this.getNumberUtil().formatInt(this.collection.total),
            moreCountFormatted: this.getNumberUtil().formatInt(moreCount),
            checkboxColumnWidth: checkboxColumnWidth,
            noDataDisabled: noDataDisabled,
            hasStickyBar: hasStickyBar,
            selectable: this.selectable,
        };
    }

    protected init() {
        this.type = this.options.type || this.type;

        this.listLayout = this.options.listLayout || this.listLayout || null;
        this.multiListLayout = this.options.multiListLayout || this.multiListLayout || null;

        this.layoutName = this.options.layoutName || this.layoutName || this.type;
        this.layoutScope = this.options.layoutScope || this.layoutScope;
        this.layoutAclDisabled = this.options.layoutAclDisabled || this.layoutAclDisabled;
        this.headerDisabled = this.options.headerDisabled || this.headerDisabled;
        this.noDataDisabled = this.options.noDataDisabled || this.noDataDisabled;

        if (!this.collectionEventSyncList) {
            this.collectionEventSyncList = [];
        } else {
            this.collectionEventSyncList = [...this.collectionEventSyncList];
        }

        if (!this.headerDisabled) {
            this.header = _.isUndefined(this.options.header) ? this.header : this.options.header;
        } else {
            this.header = false;
        }

        this.pagination = this.options.pagination == null ? this.pagination : this.options.pagination;

        if (this.paginationDisabled) {
            this.pagination = false;
        }

        if (this.options.columnResize !== undefined) {
            this.columnResize = this.options.columnResize;
        }

        this.checkboxes = _.isUndefined(this.options.checkboxes) ? this.checkboxes :
            this.options.checkboxes;
        this.selectable = _.isUndefined(this.options.selectable) ? this.selectable :
            this.options.selectable;

        this.checkboxesDisabled = this.options.checkboxes === false;

        this.rowActionsView = _.isUndefined(this.options.rowActionsView) ?
            this.rowActionsView :
            this.options.rowActionsView;

        this.showMore = _.isUndefined(this.options.showMore) ? this.showMore : this.options.showMore;

        this.massActionsDisabled = this.options.massActionsDisabled || this.massActionsDisabled;
        this.portalLayoutDisabled = this.options.portalLayoutDisabled || this.portalLayoutDisabled;

        if (this.massActionsDisabled && !this.selectable) {
            this.checkboxes = false;
        }

        this.rowActionsDisabled = this.options.rowActionsDisabled || this.rowActionsDisabled;

        this.dropdownItemList = Utils.cloneDeep(
            this.options.dropdownItemList || this.dropdownItemList);

        if ('buttonsDisabled' in this.options) {
            this.buttonsDisabled = this.options.buttonsDisabled as boolean;
        }

        if ('checkAllResultDisabled' in this.options) {
            this.checkAllResultDisabled = this.options.checkAllResultDisabled as boolean;
        }

        this.rootData = this.options.rootData || {};

        this._fontSizeFactor = this.getThemeManager().getFontSizeFactor();
    }

    /**
     * Get a record scope (not necessarily matching the entity type).
     *
     * @param {string} id A record ID.
     */
    getModelScope(id: string): string | null {
        // noinspection BadExpressionStatementJS
        id;

        return this.scope;
    }

    /**
     * Select all results.
     */
    selectAllResult() {
        this.allResultIsChecked = true;

        this.hideActions();

        this.$el.find('input.record-checkbox').prop('checked', true).attr('disabled', 'disabled');
        this.$selectAllCheckbox.prop('checked', true);

        this.massActionList.forEach(item => {
            if (!this.checkAllResultMassActionList.includes(item)) {
                this.$el
                    .find(`div.list-buttons-container .actions-menu li a.mass-action[data-action="${item}"]`)
                    .parent()
                    .addClass('hidden');
            }
        });

        if (this.checkAllResultMassActionList.length) {
            this.showActions();
        }

        this.$el.find('.list > table tbody tr').removeClass('active');

        this.trigger('select-all-results');
    }

    /**
     * Unselect all results.
     */
    unselectAllResult() {
        this.allResultIsChecked = false;

        this.$el.find('input.record-checkbox').prop('checked', false).removeAttr('disabled');
        this.$selectAllCheckbox.prop('checked', false);

        this.massActionList.forEach(item => {
            if (
                !this.checkAllResultMassActionList.includes(item) &&
                !(this.massActionDefs[item] || {}).hidden
            ) {
                this.$el
                    .find(`div.list-buttons-container .actions-menu li a.mass-action[data-action="${item}"]`)
                    .parent()
                    .removeClass('hidden');
            }
        });
    }

    protected deactivate() {
        this.element?.querySelectorAll('.pagination a').forEach(element => {
            element.classList.add('disabled');
        });

        this.element?.querySelectorAll('a.sort').forEach(element => {
            element.classList.add('disabled');
        });
    }

    /**
     * Process export.
     *
     * @param [data]
     * @param [url='Export'] An API URL.
     * @param [fieldList] A field list.
     */
    async export(data?: Record<string, unknown>, url?: string, fieldList?: string[]) {
        if (!data) {
            data = {
                entityType: this.entityType,
            };

            if (this.allResultIsChecked) {
                data.where = this.getWhereForAllResult();
                data.searchParams = this.collection.data || null;
                data.searchData = this.collection.data || {}; // for bc;
            } else {
                data.ids = this.checkedList;
            }
        }

        url = url || 'Export';

        const o = {
            scope: this.entityType,
        } as any;

        if (fieldList) {
            o.fieldList = fieldList;
        } else {
            const layoutFieldList: string[] = [];

            if (Array.isArray(this.listLayout)) {
                this.listLayout.forEach((item: any) => {
                    if (item && item.name) {
                        layoutFieldList.push(item.name);
                    }
                });
            }

            o.fieldList = layoutFieldList;
        }

        const helper = new ExportHelper(this);
        const idle = this.allResultIsChecked && helper.checkIsIdle(this.collection.total);

        const proceedDownload = (attachmentId: string) => {
            window.location.href = `${this.getBasePath()}?entryPoint=download&id=${attachmentId}`;
        };

        const view = await this.createView('dialogExport', 'views/export/modals/export', o);

        this.listenToOnce(view, 'proceed', async (dialogData: any) => {
            if (!dialogData.exportAllFields) {
                data.attributeList = dialogData.attributeList;
                data.fieldList = dialogData.fieldList;
            }

            data.idle = idle;
            data.format = dialogData.format;
            data.params = dialogData.params;

            Ui.notify(this.translate('pleaseWait', 'messages'));

            const response = await Ajax.postRequest(url, data, {timeout: 0}) as {id?: string, exportId?: string};

            Ui.notify();

            if (response.exportId) {
                const view = await helper.process(response.exportId)

                this.listenToOnce(view, 'download', id => proceedDownload(id));

                return;
            }

            if (!response.id) {
                throw new Error("No attachment-id.");
            }

            proceedDownload(response.id);
        });

        await view.render();
    }

    /**
     * Process a mass-action.
     *
     * @param {string} name An action.
     */
    private massAction(name: string) {
        const defs = this.massActionDefs[name] || {};

        const handler = defs.handler;

        if (handler) {
            const method = defs.actionFunction || 'action' + Utils.upperCaseFirst(name);

            const data = {
                entityType: this.entityType,
                action: name,
                params: this.getMassActionSelectionPostData(),
            };

            Espo.loader.require(handler, Handler => {
                const handler = new Handler(this);

                handler[method].call(handler, data);
            });

            return;
        }

        const bypassConfirmation = defs.bypassConfirmation || false;
        const confirmationMsg = defs.confirmationMessage || 'confirmation';
        const acl = defs.acl;
        const aclScope = defs.aclScope;

        const proceed = async () => {
            if (acl || aclScope) {
                if (!this.getAcl().check(aclScope || this.scope, acl)) {
                    Ui.error(this.translate('Access denied'));

                    return;
                }
            }

            const idList: string[] = [];
            const data: Record<string, any> = {};

            if (this.allResultIsChecked) {
                data.where = this.getWhereForAllResult();
                data.searchParams = this.collection.data || {};
                data.selectData = data.searchData; // for bc;
                data.byWhere = true; // for bc
            } else {
                data.idList = idList; // for bc
                data.ids = idList;
            }

            for (const i in this.checkedList) {
                idList.push(this.checkedList[i]);
            }

            data.entityType = this.entityType;

            const waitMessage = defs.waitMessage || 'pleaseWait';

            Ui.notify(this.translate(waitMessage, 'messages', this.scope));

            const url = defs.url;

            if (!url) {
                throw new Error('No collection URL.');
            }

            const result: any = await Ajax.postRequest(url, data);

            const successMessage = result.successMessage || defs.successMessage || 'done';

            await this.collection.fetch();

            let message = this.translate(successMessage, 'messages', this.scope);

            if ('count' in result) {
                message = message.replace('{count}', result.count);
            }

            Ui.success(message);
        };

        if (!bypassConfirmation) {
            this.confirm({message: this.translate(confirmationMsg, 'messages', this.scope)})
                .then(() => proceed());

            return;
        }

        proceed();
    }

    /**
     * Get the where clause for all result.
     *
     * @since 9.2.0
     */
    getWhereForAllResult(): WhereItem[] {
        const where = [...this.collection.getWhere()];

        if (this.options.allResultWhereItem) {
            where.push(this.options.allResultWhereItem);
        }

        return where;
    }

    private getMassActionSelectionPostData(): Record<string, any> {
        const data = {} as any;

        if (this.allResultIsChecked) {
            data.where = this.getWhereForAllResult();
            data.searchParams = this.collection.data || {};
            data.selectData = this.collection.data || {}; // for bc;
            data.byWhere = true; // for bc;
        } else {
            data.ids = [];

            for (const i in this.checkedList) {
                data.ids.push(this.checkedList[i]);
            }
        }

        return data;
    }

    // noinspection JSUnusedGlobalSymbols
    protected async massActionRecalculateFormula() {
        let ids : string[] | null = null;

        const allResultIsChecked = this.allResultIsChecked;

        if (!allResultIsChecked) {
            ids = this.checkedList;
        }

        await this.confirm({
            message: this.translate('recalculateFormulaConfirmation', 'messages'),
            confirmText: this.translate('Yes'),
        });

        Ui.notify(this.translate('pleaseWait', 'messages'));

        const params = this.getMassActionSelectionPostData();
        const helper = new MassActionHelper(this);
        const idle = !!params.searchParams && helper.checkIsIdle(this.collection.total);

        let result = await Ajax.postRequest('MassAction', {
            entityType: this.entityType,
            action: 'recalculateFormula',
            params: params,
            idle: idle,
        });

        result = result || {};

        const final = async () => {
            await this.collection.fetch();

            Ui.success(this.translate('Done'));

            if (allResultIsChecked) {
                this.selectAllResult();

                return;
            }

            ids?.forEach(id => this.checkRecord(id));
        };

        if (result.id) {
            const view = await helper.process(result.id, 'recalculateFormula')

            this.listenToOnce(view, 'close:success', () => final());

            return;
        }

        await final();
    }

    // noinspection JSUnusedGlobalSymbols
    protected async massActionRemove() {
        if (!this.getAcl().check(this.entityType, 'delete')) {
            Ui.error(this.translate('Access denied'));

            return false;
        }

        await this.confirm({
            message: this.translate('removeSelectedRecordsConfirmation', 'messages', this.scope),
            confirmText: this.translate('Remove'),
        });

        Ui.notifyWait();

        const helper = new MassActionHelper(this);
        const params = this.getMassActionSelectionPostData();
        const idle = !!params.searchParams && helper.checkIsIdle(this.collection.total);

        let result = await Ajax.postRequest('MassAction', {
            entityType: this.entityType,
            action: 'delete',
            params: params,
            idle: idle,
        });

        result = result || {};

        const afterAllResult = (count: number) => {
            if (!count) {
                Ui.warning(this.translate('noRecordsRemoved', 'messages'));

                return;
            }

            this.unselectAllResult();

            this.collection.fetch()
                .then(() => {
                    const msg = count === 1 ? 'massRemoveResultSingle' : 'massRemoveResult';

                    Ui.success(this.translate(msg, 'messages')
                        .replace('{count}', count.toString()));
                });

            this.collection.trigger('after:mass-remove');

            Ui.notify(false);
        };

        if (result.id) {
            const view = await helper.process(result.id, 'delete')

            this.listenToOnce(view, 'close:success', result => afterAllResult(result.count));

            return;
        }

        const count = result.count;

        if (this.allResultIsChecked) {
            afterAllResult(count);

            return;
        }

        const idsRemoved: string[] = result.ids ?? [];

        if (!count) {
            Ui.warning(this.translate('noRecordsRemoved', 'messages'));

            return;
        }

        idsRemoved.forEach(id => {
            Ui.notify(false);

            this.collection.trigger('model-removing', id);
            this.removeRecordFromList(id);
            this.uncheckRecord(id, true);
        });

        if (this.$selectAllCheckbox.prop('checked')) {
            this.$selectAllCheckbox.prop('checked', false);

            if (this.collection.hasMore()) {
                this.showMoreRecords({skipNotify: true});
            }
        }

        this.collection.trigger('after:mass-remove');

        const showSuccess = () => {
            const msgKey = count === 1 ? 'massRemoveResultSingle' : 'massRemoveResult';
            const msg = this.translate(msgKey, 'messages').replace('{count}', count);

            Ui.success(msg);
        }

        showSuccess();
    }

    // noinspection JSUnusedGlobalSymbols
    protected async massActionPrintPdf() {
        const maxCount = this.getConfig().get('massPrintPdfMaxCount');

        if (maxCount) {
            if (this.checkedList.length > maxCount) {
                const msg = this.translate('massPrintPdfMaxCountError', 'messages')
                    .replace('{maxCount}', maxCount.toString());

                Ui.error(msg);

                return;
            }
        }

        const idList: string[] = [];

        for (const i in this.checkedList) {
            idList.push(this.checkedList[i]);
        }

        const view = await this.createView('pdfTemplate', 'views/modals/select-template', {
            entityType: this.entityType,
        });

        this.listenToOnce(view, 'select', async (templateModel) => {
            this.clearView('pdfTemplate');

            Ui.notifyWait();

            const result = await Ajax.postRequest('Pdf/action/massPrint', {
                idList: idList,
                entityType: this.entityType,
                templateId: templateModel.id,
            }, {timeout: 0});

            Ui.notify(false);

            window.open(`?entryPoint=download&id=${result.id}`, '_blank');
        });

        await view.render();
    }

    // noinspection JSUnusedGlobalSymbols
    protected async massActionFollow() {
        const count = this.checkedList.length;

        const confirmMsg = this.translate('confirmMassFollow', 'messages')
            .replace('{count}', count.toString());

        await this.confirm({
            message: confirmMsg,
            confirmText: this.translate('Follow'),
        });

        Ui.notify(this.translate('pleaseWait', 'messages'));

        const result = await Ajax.postRequest('MassAction', {
            action: 'follow',
            entityType: this.entityType,
            params: this.getMassActionSelectionPostData(),
        });

        const resultCount = result.count || 0;

        let msg = 'massFollowResult';

        if (resultCount) {
            if (resultCount === 1) {
                msg += 'Single';
            }

            msg = this.translate(msg, 'messages')
                .replace('{count}', resultCount.toString());

            Ui.success(msg);

            return;
        }

        Ui.warning(this.translate('massFollowZeroResult', 'messages'));
    }

    // noinspection JSUnusedGlobalSymbols
    protected async massActionUnfollow() {
        const count = this.checkedList.length;

        const confirmMsg = this.translate('confirmMassUnfollow', 'messages')
            .replace('{count}', count.toString());

        await this.confirm({
            message: confirmMsg,
            confirmText: this.translate('Yes'),
        });

        Ui.notify(this.translate('pleaseWait', 'messages'));

        const params = this.getMassActionSelectionPostData();
        const helper = new MassActionHelper(this);
        const idle = !!params.searchParams && helper.checkIsIdle(this.collection.total);

        const result = await Ajax.postRequest('MassAction', {
            action: 'unfollow',
            entityType: this.entityType,
            params: params,
            idle: idle,
        });

        const final = (count: number) => {
            let msg = 'massUnfollowResult';

            if (!count) {
                Ui.warning(
                    this.translate('massUnfollowZeroResult', 'messages')
                );
            }

            if (count === 1) {
                msg += 'Single';
            }

            msg = this.translate(msg, 'messages')
                .replace('{count}', count.toString());

            Ui.success(msg);
        };

        if (result.id) {
            const view = await helper.process(result.id, 'unfollow');

            this.listenToOnce(view, 'close:success', result => final(result.count));

            return;
        }

        final(result.count || 0);
    }

    // noinspection JSUnusedGlobalSymbols
    protected massActionMerge() {
        if (!this.getAcl().check(this.entityType, 'edit')) {
            Ui.error(this.translate('Access denied'));

            return false;
        }

        if (this.checkedList.length < 2) {
            Ui.error(this.translate('select2OrMoreRecords', 'messages'));

            return;
        }

        if (this.checkedList.length > 4) {
            const msg = this.translate('selectNotMoreThanNumberRecords', 'messages')
                .replace('{number}', '4');

            Ui.error(msg);

            return;
        }

        this.checkedList.sort();

        const url = '#' + this.entityType + '/merge/ids=' + this.checkedList.join(',');

        this.getRouter().navigate(url, {trigger: false});

        this.getRouter().dispatch(this.entityType, 'merge', {
            ids: this.checkedList.join(','),
            collection: this.collection,
        });
    }

    // noinspection JSUnusedGlobalSymbols
    async massActionMassUpdate() {
        if (!this.getAcl().check(this.entityType, 'edit')) {
            Ui.error(this.translate('Access denied'));

            return false;
        }

        Ui.notifyWait();

        let ids: string[] | null = null;

        const allResultIsChecked = this.allResultIsChecked;

        if (!allResultIsChecked) {
            ids = this.checkedList;
        }

        const viewName =
            this.getMetadata().get(['clientDefs', this.entityType ?? '', 'modalViews', 'massUpdate']) ||
            'views/modals/mass-update';

        const view = await this.createView<MassUpdateModalView>('massUpdate', viewName, {
            scope: this.scope,
            entityType: this.entityType,
            ids: ids,
            where: this.getWhereForAllResult(),
            searchParams: this.collection.data,
            byWhere: this.allResultIsChecked,
            totalCount: this.collection.total,
        });

        this.listenToOnce(view, 'after:update', async o => {
            if (o.idle) {
                this.listenToOnce(view, 'close', async () => {
                    await this.collection.fetch();

                    if (allResultIsChecked) {
                        this.selectAllResult();

                        return;
                    }

                    ids?.forEach(id => this.checkRecord(id));
                });

                return;
            }

            view.close();

            const count = o.count;

            await this.collection.fetch();

            if (count) {
                const msgKey = count === 1 ? 'massUpdateResultSingle' : 'massUpdateResult';
                const message = this.translate(msgKey, 'messages').replace('{count}', count);

                Ui.success(message);
            } else {
                Ui.warning(this.translate('noRecordsUpdated', 'messages'));
            }

            if (allResultIsChecked) {
                this.selectAllResult();

                return;
            }

            ids?.forEach(id => this.checkRecord(id));
        });

        Ui.notify();

        await view.render();
    }

    // noinspection JSUnusedGlobalSymbols
    protected massActionExport() {
        if (this.getConfig().get('exportDisabled') && !this.getUser().isAdmin()) {
            return;
        }

        this.export();
    }

    // noinspection JSUnusedGlobalSymbols
    protected async massActionUnlink() {
        if (!this.collection.url) {
            throw new Error('No collection URL.');
        }

        await this.confirm({
            message: this.translate('unlinkSelectedRecordsConfirmation', 'messages'),
            confirmText: this.translate('Unlink'),
        });

        Ui.notifyWait();

        await Ajax.deleteRequest(this.collection.url, {ids: this.checkedList})

        Ui.success(this.translate('Unlinked'));

        this.collection.fetch();
        (this.model ?? this.collection.parentModel)?.trigger('after:unrelate');
    }

    // noinspection JSUnusedGlobalSymbols
    protected async massActionConvertCurrency() {
        let ids: string[] | null = null;

        const allResultIsChecked = this.allResultIsChecked;

        if (!allResultIsChecked) {
            ids = this.checkedList;
        }

        const view = await this.createView('modalConvertCurrency', 'views/modals/mass-convert-currency', {
            entityType: this.entityType,
            ids: ids,
            where: this.getWhereForAllResult(),
            searchParams: this.collection.data,
            byWhere: this.allResultIsChecked,
            totalCount: this.collection.total,
        });

        this.listenToOnce(view, 'after:update', async o => {
            if (o.idle) {
                this.listenToOnce(view, 'close', async () => {
                    await this.collection.fetch()

                    if (allResultIsChecked) {
                        this.selectAllResult();

                        return;
                    }

                    ids?.forEach(id => this.checkRecord(id));
                });

                return;
            }

            const count = o.count;

            await this.collection.fetch();

            if (count) {
                let msg = 'massUpdateResult';

                if (count === 1) {
                    msg = 'massUpdateResultSingle';
                }

                Ui.success(this.translate(msg, 'messages').replace('{count}', count));
            } else {
                Ui.warning(this.translate('noRecordsUpdated', 'messages'));
            }

            if (allResultIsChecked) {
                this.selectAllResult();

                return;
            }

            ids?.forEach(id => this.checkRecord(id));
        });

        await view.render();
    }

    /**
     * Add a mass action.
     *
     * @param item An action.
     * @param [allResult] To make available for all-result.
     * @param [toBeginning] Add to the beginning of the list.
     */
    protected addMassAction(
        item: string | MassActionItem,
        allResult: boolean = false,
        toBeginning: boolean = false,
    ) {
        if (typeof item !== 'string') {
            const name = item.name;

            if (!name) {
                console.warn(`Cannot add mass action. No 'name'.`, item);

                return;
            }

            this.massActionDefs[name] = {...this.massActionDefs[name], ...item};

            item = name as MassActionItem;
        }

        toBeginning ?
            this.massActionList.unshift(item as string) :
            this.massActionList.push(item as string);

        if (allResult && !this.noAllResultMassActions) {
            toBeginning ?
                this.checkAllResultMassActionList.unshift(item as string) :
                this.checkAllResultMassActionList.push(item as string);
        }

        if (!this.checkboxesDisabled) {
            this.checkboxes = true;
        }
    }

    /**
     * Remove a mass action.
     *
     * @param item An action.
     */
    removeMassAction(item: string) {
        let index = this.massActionList.indexOf(item);

        if (~index) {
            this.massActionList.splice(index, 1);
        }

        index = this.checkAllResultMassActionList.indexOf(item);

        if (~index) {
            this.checkAllResultMassActionList.splice(index, 1);
        }
    }

    /**
     * Remove an all-result mass action.
     *
     * @param  item An action.
     */
    removeAllResultMassAction(item: string) {
        const index = this.checkAllResultMassActionList.indexOf(item);

        if (~index) {
            this.checkAllResultMassActionList.splice(index, 1);
        }
    }

    protected setup() {
        this.setupEventHandlers();

        this.checkedList = [];

        if (typeof this.collection === 'undefined') {
            throw new Error('Collection has not been injected into views/record/list view.');
        }

        this.layoutLoadCallbackList = [];

        this.entityType = this.collection.entityType || null;
        this.scope = this.options.scope || this.entityType;

        this.massActionList = Utils.clone(this.massActionList);
        this.checkAllResultMassActionList = Utils.clone(this.checkAllResultMassActionList);
        this.buttonList = Utils.clone(this.buttonList);

        this.mandatorySelectAttributeList = Utils.clone(
            this.options.mandatorySelectAttributeList || this.mandatorySelectAttributeList || []
        );

        this.forceStickyBar = this.options.forceStickyBar || this.forceStickyBar;

        this.editDisabled = this.options.editDisabled || this.editDisabled ||
            this.getMetadata().get(['clientDefs', this.scope ?? '', 'editDisabled']);

        this.removeDisabled = this.options.removeDisabled || this.removeDisabled ||
            this.getMetadata().get(['clientDefs', this.scope ?? '', 'removeDisabled']);

        this.setupMassActions();

        if (this.selectable) {
            this.addHandler('click', '.list a.link', (e, target) => {
                e.preventDefault();

                const id = target.dataset.id;

                if (id) {
                    this.selectModel(id);
                }

                e.stopPropagation();
            });
        }

        if ('showCount' in this.options) {
            this.showCount = this.options.showCount as boolean;
        }

        this.displayTotalCount = this.showCount && this.getConfig().get('displayListViewRecordCount');

        if ('displayTotalCount' in this.options) {
            this.displayTotalCount = this.options.displayTotalCount as boolean;
        }

        this.forceDisplayTopBar = this.options.forceDisplayTopBar || this.forceDisplayTopBar;

        if (!this.massActionList.length && !this.selectable) {
            this.checkboxes = false;
        }

        if (this.options.forceSettings) {
            this.forceSettings = true;
        }

        this.hasStars = this.hasStars ?? this.getMetadata().get(`scopes.${this.entityType}.stars`) ?? false;

        if (
            this.getUser().isPortal() &&
            !this.portalLayoutDisabled &&
            this.scope &&
            this.getMetadata().get(['clientDefs', this.scope, 'additionalLayouts', this.layoutName + 'Portal'])
        ) {
            this.layoutName += 'Portal';
        }

        this.setupRowActionDefs();
        this.setupSettings();

        this.wait(
            this.getHelper().processSetupHandlers(this, this.setupHandlerType)
        );

        this.listenTo(this.collection, 'sync', (_c, _r, options) => {
            this._renderEmpty = false;

            options = options || {};

            if (options.previousDataList) {
                const currentDataList = this.collection.models.map(model => {
                    return Utils.cloneDeep(model.attributes);
                });

                if (
                    _.isEqual(currentDataList, options.previousDataList) &&
                    options.previousTotal === this.collection.total
                ) {
                    return;
                }
            }

            if (this.noRebuild) {
                this.noRebuild = null;

                return;
            }

            if (options.noRebuild) {
                this.noRebuild = null;

                return;
            }

            this.checkedList = [];
            this.allResultIsChecked = false;

            this.buildRowsAndRender();
        });

        this.checkedList = [];

        if (!this.options.skipBuildRows) {
            this.buildRows();
        }

        if (this.pagination) {
            this.createView('pagination', 'views/record/list-pagination', {
                collection: this.collection,
                displayTotalCount: this.displayTotalCount,
                recordView: this,
            });

            this.createView('paginationSticky', 'views/record/list-pagination', {
                collection: this.collection,
                displayTotalCount: this.displayTotalCount,
                recordView: this,
            });

            this.on('request-page', /** string */page => {
                if (this.collection.isBeingFetched()) {
                    return;
                }

                if (page === 'next' && !this.collection.hasNextPage()) {
                    return;
                }

                if (page === 'previous' && !this.collection.hasPreviousPage()) {
                    return;
                }

                this.goToPage(page);
            });
        }

        this._renderEmpty = this.options.skipBuildRows ?? false;

        if (this.columnResize && this._listSettingsHelper) {
            this._columnResizeHelper = new ListColumnResizeHelper(this, this._listSettingsHelper);
        }

        if (this.hasStars) {
            this.listenTo(this.collection, 'change:' + this.starredAttribute, (model) => {
                const rowView = this.getRowView(model.id);

                const element = rowView?.element;

                if (!element) {
                    return;
                }

                model.attributes[this.starredAttribute] ?
                    element.classList.add('starred') :
                    element.classList.remove('starred');
            });
        }
    }

    protected setupEventHandlers() {
        this.addHandler('auxclick', 'a.link', (e, target) => {
            if (!(e instanceof MouseEvent)) {
                throw new Error();
            }

            const isCombination = e.button === 1 && (e.ctrlKey || e.metaKey);

            if (!isCombination) {
                return;
            }

            const id = target.dataset.id;

            if (!id) {
                return;
            }

            if (this.quickDetailDisabled) {
                return;
            }

            const menu = target.parentElement?.closest(`[data-id="${id}"]`)
                ?.querySelector(`ul.list-row-dropdown-menu[data-id="${id}"]`);

            const quickView = menu?.querySelector(`a[data-action="quickView"]`);

            if (menu && !quickView) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            this.actionQuickView({id: id});
        });

        this.addHandler('mousedown', 'a.sort', (e) => e.preventDefault());
        this.addHandler('click', 'a.sort', (_, target) => this.toggleSort(target.dataset.name as string));

        this.addHandler('click', '.pagination a[data-page]', (_, target) => {
            const page = target.dataset.page;

            if (target.parentElement?.classList.contains('disabled')) {
                return;
            }

            this.goToPage(page as any);
        });

        this.addHandler('mousedown', 'input.record-checkbox', () => {
            const activeElement = document.activeElement;

            this._focusedCheckbox = null;

            if (
                activeElement instanceof HTMLInputElement &&
                activeElement.classList.contains('record-checkbox')
            ) {
                this._focusedCheckbox = activeElement;
            }
        });

        this.addHandler('click', 'input.record-checkbox', (e, target) => {
            if (
                !(e instanceof MouseEvent) ||
                !(target instanceof HTMLInputElement)
            ) {
                throw new Error();
            }

            if (this._disabledCheckboxes) {
                return;
            }

            const from = this._focusedCheckbox;

            if (!e.shiftKey || !from) {
                this.checkboxClick(target, target.checked);

                return;
            }

            const checkboxes = Array.from(this.element.querySelectorAll<HTMLInputElement>('input.record-checkbox'));

            const start = checkboxes.indexOf(target);
            const end = checkboxes.indexOf(from);
            const checked = from.checked;

            checkboxes
                .slice(Math.min(start, end), Math.max(start, end) + 1)
                .forEach(element => {
                    element.checked = checked;

                    this.checkboxClick(element, checked);
                });
        });

        this.addHandler('click', 'input.select-all', (_, target) => {
            if (this._disabledCheckboxes) {
                return;
            }

            if (!(target instanceof HTMLInputElement)) {
                throw new Error();
            }

            this.selectAllHandler(target.checked);
        });

        this.addHandler('click', '.action', (e, target) => {
            Utils.handleAction(this, e as MouseEvent, target, {
                actionItems: [...this.buttonList, ...this.dropdownItemList],
                className: 'list-action-item',
            });
        });

        this.addHandler('click', '.checkbox-dropdown [data-action="selectAllResult"]', () => {
            if (this._disabledCheckboxes) {
                return;
            }

            this.selectAllResult();
        });

        this.addHandler('click', '.actions-menu a.mass-action', (e, target) => {
            const action = target.dataset.action as string;
            const method = 'massAction' + Utils.upperCaseFirst(action);

            e.preventDefault();
            e.stopPropagation();

            const parent = target.closest('.dropdown-menu')?.parentElement;

            const toggle = parent?.querySelector('.actions-button[data-toggle="dropdown"]');

            if (toggle) {
                // @ts-ignore
                $(toggle).dropdown('toggle')
                    .focus();
            }

            if (method in this) {
                // @ts-ignore
                this[method]();

                return;
            }

            this.massAction(action);
        });

        this.addHandler('click', 'a.reset-custom-order', () => this.resetCustomOrder());

        this.addHandler('click', 'a.link', (e, target) => {
            if (!(e instanceof MouseEvent)) {
                throw new Error();
            }

            if (e.ctrlKey || e.metaKey || e.shiftKey) {
                return;
            }

            e.stopPropagation();

            if (!this.scope || this.selectable) {
                return;
            }

            e.preventDefault();

            this.processLinkClick(target.dataset.id as string);
        });

        this.addHandler('click', '[data-action="showMore"]', (_e, target) => {
            if (target.dataset.ownerCid && target.dataset.ownerCid !== this.cid) {
                return;
            }

            this.showMoreRecords();
            this.focusOnList();
        })
    }

    /**
     * @since 9.1.0
     */
    protected buildRowsAndRender() {
        let modalView: View | null;
        const modalKey = 'modal';

        if (this.hasView(modalKey) && this.getView(modalKey)?.isRendered()) {
            modalView = this.getView(modalKey);

            this.unchainView(modalKey);
        }

        this.buildRows(async () => {
            await this.reRender({force: true});

            if (modalView) {
                this.setView(modalKey, modalView);
            }
        });
    }

    private processLinkClick(id: string) {
        const scope = this.getModelScope(id);

        const collection = this.collection.clone({withModels: true});

        const options = {
            id: id,
            model: collection.get(id),
        } as Record<string, any>;

        if (this.collectionEventSyncList) {
            this.listenTo(collection, 'all', (event, ...parameters) => {
                if (this.collectionEventSyncList.includes(event)) {
                    this.collection.trigger(event, ...parameters);
                }
            });
        }

        this.listenTo(collection, 'model-sync', (/** Model */m, /** Record */o) => {
            if (o.action === 'destroy') {
                this.removeRecordFromList(m.id);
            }

            const model = this.collection.get(m.id);

            if (!model) {
                return;
            }

            if (o.action === 'set' || o.action === 'fetch' || o.action === 'save') {
                model.setMultiple(m.getClonedAttributes(), o);
            }
        });

        this.listenTo(collection, 'sync', (c, r, /** Record */o) => {
            if (!o.more) {
                return;
            }

            const moreModels = collection.models.slice(this.collection.length);

            this.collection.add(moreModels);
            this.collection.total = collection.total;
            this.collection.lengthCorrection = collection.lengthCorrection;

            this.collection.trigger('sync', c, r, o);
        });

        if (this.options.keepCurrentRootUrl) {
            options.rootUrl = this.getRouter().getCurrentUrl();
        }

        options.rootData = this.rootData;

        this.getRouter().navigate(`#${scope}/view/${id}`, {trigger: false});
        this.getRouter().dispatch(scope, 'view', options);
    }

    private selectModel(id: string) {
        const model = this.collection.get(id);

        if (!model) {
            return;
        }

        if (this.checkboxes) {
            this.trigger('select', [model]);

            return;
        }

        this.trigger('select', model);

        if (this.options.onSelect) {
            this.options.onSelect([model]);
        }
    }

    protected onRemove() {
        this.destroyStickyBar();
    }

    protected afterRender() {
        this.destroyStickyBar();

        this.$selectAllCheckbox = this.$el.find('input.select-all');

        if (this.allResultIsChecked) {
            this.selectAllResult();
        }
        else if (this.checkedList.length) {
            this.checkedList.forEach(id => {
                this.checkRecord(id);
            });
        }

        if (this.pagination && this.$el.find('.list-buttons-container').length) {
            this.initStickyBar();
        }

        if (this._disabledCheckboxes) {
            this.disableCheckboxes();
        }
    }

    private destroyStickyBar() {
        if (this._stickyBarHelper) {
            this._stickyBarHelper.destroy();
        }

        this._stickyBarHelper = null;
    }

    private setupMassActions() {
        if (this.massActionsDisabled || !this.checkboxes) {
            this.massActionList = [];
            this.checkAllResultMassActionList = [];
            this.massActionDefs = {};

            return;
        }

        if (!this.entityType || !this.getAcl().checkScope(this.entityType, 'delete')) {
            this.removeMassAction('remove');
            this.removeMassAction('merge');
        }

        if (
            this.removeDisabled ||
            this.getMetadata().get(['clientDefs', this.scope ?? '_', 'massRemoveDisabled'])
        ) {
            this.removeMassAction('remove');
        }

        if (!this.entityType || !this.getAcl().checkScope(this.entityType, 'edit')) {
            this.removeMassAction('massUpdate');
            this.removeMassAction('merge');
        }

        if (
            this.getMetadata().get(['clientDefs', this.scope ?? '_', 'mergeDisabled']) ||
            this.mergeDisabled
        ) {
            this.removeMassAction('merge');
        }

        this.massActionDefs = {
            remove: {groupIndex: 0},
            merge: {groupIndex: 0},
            massUpdate: {groupIndex: 0},
            export: {groupIndex: 2},
            follow: {groupIndex: 4},
            unfollow: {groupIndex: 4},
            convertCurrency: {groupIndex: 6},
            printPdf: {groupIndex: 8},
            ...this.getMetadata().get(['clientDefs', 'Global', 'massActionDefs']) || {},
            ...this.getMetadata().get(['clientDefs', this.scope ?? '_', 'massActionDefs']) || {},
        };

        const metadataMassActionList = [
            ...this.getMetadata().get(['clientDefs', 'Global', 'massActionList']) || [],
            ...this.getMetadata().get(['clientDefs', this.scope ?? '_', 'massActionList']) || [],
        ].filter((it, i, self) => self.indexOf(it) === i);

        const metadataCheckAllMassActionList = [
            ...this.getMetadata().get(['clientDefs', 'Global', 'checkAllResultMassActionList']) || [],
            ...this.getMetadata().get(['clientDefs', this.scope ?? '_', 'checkAllResultMassActionList']) || [],
        ].filter((it, i, self) => self.indexOf(it) === i);

        metadataMassActionList.forEach(item => {
            const defs = this.massActionDefs[item] || {};

            if (
                !Utils.checkActionAvailability(this.getHelper(), defs) ||
                this.entityType && !Utils.checkActionAccess(this.getAcl(), this.entityType, defs)
            ) {
                return;
            }

            this.massActionList.push(item);
        });

        this.noAllResultMassActions = this.collection.url !== this.entityType && !this.options.forceAllResultSelectable;

        this.checkAllResultMassActionList = this.checkAllResultMassActionList
            .filter(item => this.massActionList.includes(item));

        metadataCheckAllMassActionList.forEach(item => {
            if (this.noAllResultMassActions || !this.massActionList.includes(item)) {
                return;
            }

            const defs = this.massActionDefs[item] ?? {};

            if (
                !Utils.checkActionAvailability(this.getHelper(), defs) ||
                !Utils.checkActionAccess(this.getAcl(), this.entityType, defs)
            ) {
                return;
            }

            this.checkAllResultMassActionList.push(item);
        });

        metadataMassActionList
            .concat(metadataCheckAllMassActionList)
            .forEach(action => {
                const defs: any = this.massActionDefs[action] || {};

                if (!defs.initFunction || !defs.handler) {
                    return;
                }

                const viewObject = this;

                this.wait(
                    new Promise(resolve => {
                        Espo.loader.require(defs.handler, Handler => {
                            const handler = new Handler(viewObject);

                            // @ts-ignore
                            handler[defs.initFunction].call(handler);

                            resolve(undefined);
                        });
                    })
                );
            });

        if (
            this.getConfig().get('exportDisabled') && !this.getUser().isAdmin() ||
            this.getAcl().getPermissionLevel('exportPermission') === 'no' ||
            this.scope && this.getMetadata().get(['clientDefs', this.scope, 'exportDisabled']) ||
            this.exportDisabled
        ) {
            this.removeMassAction('export');
        }

        if (
            this.getAcl().getPermissionLevel('massUpdatePermission') !== 'yes' ||
            this.editDisabled ||
            this.massUpdateDisabled ||
            this.scope && this.getMetadata().get(['clientDefs', this.scope, 'massUpdateDisabled'])
        ) {
            this.removeMassAction('massUpdate');
        }

        if (
            this.scope &&
            this.entityType &&
            (
                (
                    !this.massFollowDisabled &&
                    this.getMetadata().get(['scopes', this.entityType, 'stream']) &&
                    this.getAcl().check(this.entityType, 'stream')
                ) ||
                this.getMetadata().get(['clientDefs', this.scope, 'massFollowDisabled'])
            )
        ) {
            this.addMassAction('follow');
            this.addMassAction('unfollow', true);
        }

        if (
            this.entityType &&
            !this.massPrintPdfDisabled &&
            (this.getHelper().getAppParam('templateEntityTypeList') || []).includes(this.entityType)
        ) {
            this.addMassAction('printPdf');
        }

        if (this.options.unlinkMassAction && this.collection) {
            this.addMassAction('unlink', false, true);
        }

        if (
            this.scope &&
            this.entityType &&
            !this.massConvertCurrencyDisabled &&
            !this.getMetadata().get(['clientDefs', this.scope, 'convertCurrencyDisabled']) &&
            this.getConfig().get('currencyList').length > 1 &&
            this.getAcl().checkScope(this.scope, 'edit') &&
            this.getAcl().getPermissionLevel('massUpdatePermission') === 'yes'
        ) {
            const currencyFieldList = this.getFieldManager().getEntityTypeFieldList(this.entityType, {
                type: 'currency',
                acl: 'edit',
            });

            if (currencyFieldList.length) {
                this.addMassAction('convertCurrency', true);
            }
        }

        this.setupMassActionItems();

        if (
            this.getUser().isAdmin() &&
            this.entityType &&
            this.getMetadata().get(['formula', this.entityType, 'beforeSaveCustomScript'])
        ) {
            this.addMassAction('recalculateFormula', true);
        }

        if (this.noAllResultMassActions) {
            Utils.clone(this.checkAllResultMassActionList).forEach(item => {
                this.removeAllResultMassAction(item);
            });
        }

        if (this.forcedCheckAllResultMassActionList) {
            this.checkAllResultMassActionList = Utils.clone(this.forcedCheckAllResultMassActionList);
        }

        if (this.getAcl().getPermissionLevel('massUpdatePermission') !== 'yes') {
            this.removeAllResultMassAction('remove');
        }

        Utils.clone(this.massActionList).forEach(item => {
            const propName = 'massAction' + Utils.upperCaseFirst(item) + 'Disabled';

            if (
                // @ts-ignore
                this[propName] ||
                this.options[propName]
            ) {
                this.removeMassAction(item);
            }
        });
    }

    protected setupMassActionItems() {}

    /**
     * @internal
     */
    private filterListLayout(listLayout: TLayout): TLayout {
        if (this._cachedFilteredListLayout) {
            return this._cachedFilteredListLayout;
        }

        if (!Array.isArray(listLayout)) {
            return listLayout;
        }

        let forbiddenFieldList: string[] = [];

        if (this.entityType) {
            forbiddenFieldList = this._cachedScopeForbiddenFieldList =
                this._cachedScopeForbiddenFieldList ||
                this.getAcl().getScopeForbiddenFieldList(this.entityType, 'read');
        }

        if (this.layoutAclDisabled) {
            forbiddenFieldList = [];
        }

        const filteredListLayout = Utils.cloneDeep(listLayout);

        const deleteIndexes = [];

        for (const [i, item] of listLayout.entries()) {
            if (item.name && forbiddenFieldList.includes(item.name)) {
                item.customLabel = '';
                item.notSortable = true;

                deleteIndexes.push(i)
            }
        }

        deleteIndexes
            .reverse()
            .forEach(index => filteredListLayout.splice(index, 1));

        const fieldDefs = this.getMetadata().get(`entityDefs.${this.entityType}.fields`) || {} as
            Record<string, Record<string, any>>;

        filteredListLayout.forEach((item: any) => {
            if (!item || !item.name || !fieldDefs[item.name]) {
                return;
            }

            if (fieldDefs[item.name].orderDisabled) {
                item.notSortable = true;
            }
        });

        this._cachedFilteredListLayout = filteredListLayout;

        return this._cachedFilteredListLayout;
    }

    /**
     * @internal
     */
    protected _loadListLayout(callback: (layout: TLayout) => void) {
        this.layoutLoadCallbackList.push(callback);

        if (this.layoutIsBeingLoaded) {
            return;
        }

        this.layoutIsBeingLoaded = true;

        const layoutName = this.layoutName;
        const layoutScope = this.layoutScope || this.collection.entityType;

        this.getHelper().layoutManager.get(layoutScope, layoutName, (listLayout: any) => {
            const filteredListLayout = this.filterListLayout(listLayout);

            this.layoutLoadCallbackList.forEach(callbackItem => {
                callbackItem(filteredListLayout);

                this.layoutLoadCallbackList = [];
                this.layoutIsBeingLoaded = false;
            });
        });
    }

    /**
     * Get a select-attribute list.
     *
     * @param [callback] A callback. For bc.
     */
    async getSelectAttributeList(callback?: any): Promise<string[] | null> {
        callback ??= () => {};

        if (this.scope === null) {
            callback(null);

            return null;
        }

        if (!this.listLayout) {
            await new Promise(resolve => {
                this._loadListLayout(listLayout => {
                    this.listLayout = listLayout;

                    resolve(undefined);
                });
            });
        }

        const attributeList = this.fetchAttributeListFromLayout();

        if (this.mandatorySelectAttributeList) {
            attributeList.push(...this.mandatorySelectAttributeList);
        }

        callback(attributeList);

        return attributeList;
    }

    protected fetchAttributeListFromLayout(): string[] {
        if (!this.entityType) {
            return [];
        }

        const selectProvider = new SelectProvider();

        return selectProvider.getFromLayout(this.entityType, this.listLayout, this._listSettingsHelper);
    }

    private _hasColumnResize(): boolean {
        return this._listSettingsHelper ? this._listSettingsHelper.getColumnResize() : false;
    }

    private _getHeaderDefs(): any[] {
        const resize = this._hasColumnResize();

        const widthMap = this._listSettingsHelper ? this._listSettingsHelper.getColumnWidthMap() : {};

        // noinspection JSIncompatibleTypesComparison
        if (!this.listLayout || !Array.isArray(this.listLayout)) {
            return [];
        }

        let emptyWidthMet = false;

        const visibleColumns = this.listLayout
            .filter((it: any) => {
                if (!it) {
                    return false;
                }

                if (!this._listSettingsHelper && it.hidden) {
                    return false;
                }

                if (!this._listSettingsHelper) {
                    return true;
                }

                if (it.name && this._listSettingsHelper.isColumnHidden(it.name, it.hidden)) {
                    return false;
                }

                return true;
            })
            .map((it: any) => ({...it}));

        const defs: Record<string, any>[] = [];

        for (const col of visibleColumns) {
            let width: string | false = false;
            let widthPercent = null;
            let isResized = false;

            const itemName = col.name;

            if (itemName && (itemName in widthMap)) {
                const widthItem = widthMap[itemName];

                width = widthItem.value + widthItem.unit;

                if (widthItem.unit === '%') {
                    widthPercent = widthItem.value;
                }

                isResized = true;
            } else if ('width' in col && col.width !== null) {
                width = col.width + '%';

                widthPercent = col.width;
            } else if ('widthPx' in col) {
                width = (col.widthPx * this._fontSizeFactor).toString() + 'px';
            } else {
                emptyWidthMet = true;
            }

            const label = col.label || itemName;

            const item: Record<string, any> = {
                name: itemName,
                isSortable: !(col.notSortable || false),
                width: width,
                align: ('align' in col) ? col.align : false,
                resizable: resize && width && visibleColumns.length > 1,
                resizeOnRight: resize && width && !emptyWidthMet,
                widthPercent: widthPercent,
                isResized: isResized,
            };

            if ('customLabel' in col) {
                item.customLabel = col.customLabel;
                item.hasCustomLabel = true;
                item.label = item.customLabel;
            } else {
                item.label = this.translate(label, 'fields', this.collection.entityType);
            }

            if (col.noLabel) {
                item.label = null;
            }

            if (item.isSortable) {
                item.isSorted = this.collection.orderBy === itemName;

                if (item.isSorted) {
                    item.isDesc = this.collection.order === 'desc' ;
                }
            }

            defs.push(item);
        }

        {
            const emptyWidth = 3.0;
            let sum = 0.0;
            let sumResized = 0.0;
            let countEmpty = 0;

            for (const item of defs) {
                if (item.widthPercent === null) {
                    sum += emptyWidth;
                    countEmpty ++;

                    continue;
                }

                sum += item.widthPercent;

                if (item.isResized) {
                    sumResized += item.widthPercent;
                }
            }

            if (emptyWidthMet && sum > 100) {
                const space = 5;
                const factor = (100 - countEmpty * emptyWidth - space - sumResized) / (sum);

                for (const item of defs) {
                    if (item.widthPercent === null || item.isResized) {
                        continue;
                    }

                    item.widthPercent = item.widthPercent * factor;

                    item.width = item.widthPercent.toString() + '%';
                }
            }
        }

        const isCustomSorted =
            this.collection.orderBy !== this.collection.defaultOrderBy ||
            this.collection.order !== this.collection.defaultOrder;

        if (this.rowActionsView && !this.rowActionsDisabled || isCustomSorted) {
            let html = null;

            if (isCustomSorted) {
                const a = document.createElement('a');
                a.role = 'button';
                a.tabIndex = 0;
                a.classList.add('reset-custom-order');
                a.title = this.translate('Reset');
                a.append(
                    (() => {
                        const span = document.createElement('span');
                        span.className = 'fas fa-times fa-sm';

                        return span;
                    })()
                );

                html = a.outerHTML;
            }

            const width = (this._fontSizeFactor * this.rowActionsColumnWidth).toString() + 'px';

            defs.push({
                width: width,
                html: html,
                className: 'action-cell',
            });
        }

        return defs;
    }

    /**
     * @internal
     */
    protected abstract _convertLayout(listLayout: TLayout, model?: Model): any;

    /**
     * Select a record.
     *
     * @param id An ID.
     * @param [isSilent] Do not trigger the `check` event.
     */
    checkRecord(id: string, isSilent: boolean = false) {
        this._checkRecord(id, undefined, isSilent);
    }

    private _checkRecord(id: string, target: HTMLInputElement | null = null, isSilent: boolean = false) {
        if (this._disabledCheckboxes) {
            return;
        }

        if (!this.collection.get(id)) {
            return;
        }

        target ??= this.element.querySelector<HTMLInputElement>(`.record-checkbox[data-id="${id}"]`);

        if (target) {
            target.checked = true;
            target.closest<HTMLElement>('tr')?.classList.add('active');
        }

        const index = this.checkedList.indexOf(id);

        if (index === -1) {
            this.checkedList.push(id);
        }

        this.handleAfterCheck(isSilent);
    }

    /**
     * Unselect a record.
     *
     * @param id An ID.
     * @param [isSilent] Do not trigger the `check` event.
     */
    uncheckRecord(id: string, isSilent: boolean = false) {
        this._uncheckRecord(id, null, isSilent)
    }

    private _uncheckRecord(id: string, target: HTMLInputElement | null = null, isSilent: boolean = false) {
        target ??= this.element.querySelector<HTMLInputElement>(`.record-checkbox[data-id="${id}"]`);

        if (target) {
            target.checked = false;
            target.closest<HTMLElement>('tr')?.classList.remove('active');
        }

        const index = this.checkedList.indexOf(id);

        if (index !== -1) {
            this.checkedList.splice(index, 1);
        }

        this.handleAfterCheck(isSilent);
    }

    protected handleAfterCheck(isSilent: boolean = false) {
        if (this.checkedList.length) {
            this.showActions();
        } else {
            this.hideActions();
        }

        if (this.checkedList.length === this.collection.models.length) {
            this.$el.find('.select-all').prop('checked', true);
        } else {
            this.$el.find('.select-all').prop('checked', false);
        }

        if (!isSilent) {
            this.trigger('check');
        }
    }

    /**
     * Get row-actions defs.
     */
    protected getRowActionsDefs(): Record<string, unknown> {
        const options = {
            defs: {
                params: {},
            },
            additionalActionList: this._additionalRowActionList || [],
            scope: this.scope,
        } as any;

        if (this.options.rowActionsOptions) {
            for (const item in this.options.rowActionsOptions) {
                options[item] = this.options.rowActionsOptions[item];
            }
        }

        return {
            columnName: 'buttons',
            name: 'buttonsField',
            view: this.rowActionsView,
            options: options,
        };
    }

    /**
     * Is all-result is checked.
     */
    isAllResultChecked(): boolean {
        return this.allResultIsChecked;
    }

    /**
     * Get checked record IDs.
     */
    getCheckedIds(): string[] {
        return Utils.clone(this.checkedList);
    }

    /**
     * Get selected models.
     */
    getSelected(): Model[] {
        const list: Model[] = [];

        this.element.querySelectorAll<HTMLElement>('input.record-checkbox:checked').forEach(element => {
            const id = element.dataset.id as string;

            const model = this.collection.get(id);

            if (model) {
                list.push(model);
            }
        });

        return list;
    }

    protected getInternalLayoutForModel(callback: (layout: any) => void, model: Model) {
        const scope = model.entityType!;

        if (this._internalLayout === null) {
            this._internalLayout = {};
        }

        if (!(scope in this._internalLayout)) {
            const layout = this.multiListLayout?.[scope];

            if (!layout) {
                throw new Error(`No layout for '${scope}'.`);
            }

            this._internalLayout[scope] = this._convertLayout(layout, model);
        }

        callback(this._internalLayout[scope]);
    }

    protected getInternalLayout(callback: (layout: any) => void, model: Model) {
        if (this.scope === null && this.multiListLayout) {
            if (!model) {
                callback(null);

                return;
            }

            this.getInternalLayoutForModel(callback, model);

            return;
        }

        if (this._internalLayout !== null) {
            callback(this._internalLayout);

            return;
        }

        if (this.listLayout !== null) {
            this._internalLayout = this._convertLayout(this.listLayout);

            callback(this._internalLayout);

            return;
        }

        this._loadListLayout(listLayout => {
            this.listLayout = listLayout;
            this._internalLayout = this._convertLayout(listLayout);

            callback(this._internalLayout);
        });
    }

    /**
     * Compose a cell selector for a layout item.
     *
     * @param model A model.
     * @param item An item.
     */
    protected getCellSelector(
        model: Model,
        item: Record<string, any> & {columnName: string},
    ): string {
        return `${this.getSelector()} ${this.getRowSelector(model.id!)} .cell[data-name="${item.columnName}"]`;
    }

    /**
     * @internal
     */
    protected prepareInternalLayout(internalLayout: any, model: Model) {
        (internalLayout as any[]).forEach(item => {
            // @todo Revise whether has any effect.
            //     Has to be in options instead? item.options.fullSelector;
            item.fullSelector = this.getCellSelector(model, item);

            if (this.header && item.options && item.options.defs) {
                item.options.defs.width = undefined;
                item.options.defs.widthPx = undefined;
            }
        });
    }

    /**
     * Get a row view.
     *
     * @since 10.0.0
     */
    protected getRowView(id: string): View | null {
        return this.getView<View>(id);
    }

    /**
     * Build a row.
     *
     * @param i An index.
     * @param model A model.
     * @param [callback] A callback.
     */
    protected buildRow(i: number, model: Model, callback?: (view: View) => void) {
        const key = model.id ?? i.toString();

        this.rowList.push(key);

        this.getInternalLayout(internalLayout => {
            internalLayout = Utils.cloneDeep(internalLayout);

            this.prepareInternalLayout(internalLayout, model);

            const acl = {
                edit: this.getAcl().checkModel(model, 'edit') && !this.editDisabled,
                delete: this.getAcl().checkModel(model, 'delete') && !this.removeDisabled,
            };

            this.createView(key, 'views/base', {
                model: model,
                acl: acl,
                rowActionHandlers: this._rowActionHandlers || {},
                selector: this.getRowSelector(key),
                optionsToPass: ['acl', 'rowActionHandlers'],
                layoutDefs: {
                    type: this._internalLayoutType,
                    layout: internalLayout,
                },
                setViewBeforeCallback: this.options.skipBuildRows && !this.isRendered(),
            }, callback);
        }, model);
    }

    /**
     * Build rows.
     *
     * @param [callback] A callback.
     */
    buildRows(callback?: () => void) {
        this.checkedList = [];

        this.rowList = [];

        if (this.collection.length <= 0) {
            if (typeof callback === 'function') {
                callback();

                this.trigger('after:build-rows');
            }

            return;
        }

        this.wait(true);

        const modelList = this.collection.models;
        let counter = 0;

        modelList.forEach((model, i) => {
            this.buildRow(i, model, () => {
                counter++;

                if (counter !== modelList.length) {
                    return;
                }

                if (typeof callback === 'function') {
                    callback();
                }

                this.wait(false);
                this.trigger('after:build-rows');
            });
        });
    }

    /**
     * Show more records.
     *
     * @param [options]
     * @param [collection]
     * @param [$list]
     * @param [$showMore]
     * @param [callback] A callback.
     *
     * @internal
     */
    protected showMoreRecords(
        options?: {skipNotify?: boolean},
        collection?: Collection,
        $list?: JQuery,
        $showMore?: JQuery,
        callback?: () => void,
    ) {

        collection = collection || this.collection;
        $showMore =  $showMore || this.$el.find(`.show-more[data-owner-cid="${this.cid}"]`);
        $list = $list || this.$el.find(this.listContainerEl);
        options = options || {};

        const $container = this.$el.find('.list');

        $showMore?.children('a').addClass('disabled');

        if (!options.skipNotify) {
            Ui.notifyWait();
        }

        const lengthBefore = collection.length;

        const final = () => {
            $showMore?.parent().append($showMore);

            if (collection.hasMore()) {
                const moreCount = collection.total - collection.offset -
                    collection.length - collection.lengthCorrection;

                this.$el.find('.more-count')
                    .text(this.getNumberUtil().formatInt(moreCount));

                $showMore?.removeClass('hidden');
                $container.addClass('has-show-more');
            } else {
                $showMore?.remove();
                $container.removeClass('has-show-more');
            }

            $showMore?.children('a').removeClass('disabled');

            if (this.allResultIsChecked) {
                this.$el
                    .find('input.record-checkbox')
                    .attr('disabled', 'disabled')
                    .prop('checked', true);
            }

            if (!options.skipNotify) {
                Ui.notify(false);
            }

            if (callback) {
                callback.call(this);
            }

            this.trigger('after:show-more', lengthBefore);
        };

        const initialCount = collection.length;

        const success = () => {
            if (!options.skipNotify) {
                Ui.notify(false);
            }

            $showMore?.addClass('hidden');
            $container.removeClass('has-show-more');

            const rowCount = collection.length - initialCount;
            let rowsReady = 0;

            if (collection.length <= initialCount) {
                final();
            }

            for (let i = initialCount; i < collection.length; i++) {
                const model = collection.at(i)!;

                this.buildRow(i, model, view => {
                    const model = view.model!;

                    const existingRow = this.getDomRowItem(model.id!);

                    if (existingRow) {
                        existingRow.remove();
                    }

                    $list?.append(this.getRowContainerHtml(model.id!));

                    view.render()
                        .then(() => {
                            rowsReady++;

                            if (rowsReady === rowCount) {
                                final();
                            }
                        });
                });
            }

            this.noRebuild = true;
        };

        const onUpdate = (_c: any, o: any) => {
            if (o.changes.merged.length) {
                collection.lengthCorrection += o.changes.merged.length;
            }
        };

        this.listenToOnce(collection, 'update', onUpdate);

        // If using promise callback, then need to pass `noRebuild: true`.
        collection.fetch({
            success: success,
            remove: false,
            more: true,
        }).catch(() => this.stopListening(collection, 'update', onUpdate));
    }

    protected getDomRowItem(id: string): HTMLElement | null {
        // noinspection BadExpressionStatementJS
        id;

        return null;
    }

    /**
     * Compose a row-container HTML.
     *
     * @param {string} id A record ID.
     * @return {string} HTML.
     */
    protected getRowContainerHtml(id: string): string {
        const tr = document.createElement('tr');
        tr.dataset.id = id;
        tr.classList.add('list-row');

        return tr.outerHTML;
    }

    protected async actionQuickView(data: {id?: string, scope?: string}) {
        data = data || {};

        const id = data.id;

        if (!id) {
            console.error("No id.");

            return;
        }

        let model = null;

        if (this.collection) {
            model = this.collection.get(id);
        }

        let scope: string | null | undefined = data.scope;

        if (!scope && model) {
            scope = model.entityType;
        }

        if (!scope) {
            scope = this.scope;
        }

        if (!scope) {
            console.error("No scope.");

            return;
        }

        if (this.quickDetailDisabled) {
            this.getRouter().navigate(`#${scope}/view/${id}`, {trigger: true});

            return;
        }

        const rootUrl = this.options.keepCurrentRootUrl ? this.getRouter().getCurrentUrl() : undefined;

        const helper = new RecordModal();

        await helper.showDetail(this, {
            id: id,
            entityType: scope,
            model: model ?? null,
            rootUrl: rootUrl,
            editDisabled: this.quickEditDisabled,
            beforeSave: m => {
                if (!model) {
                    // @todo Revise.
                    return;
                }

                this.trigger('before:save', m);
            },
            afterSave: m => {
                if (!model) {
                    return;
                }

                this.trigger('after:save', m);
            },
            afterDestroy: m => {
                if (!model) {
                    return;
                }

                this.removeRecordFromList(m.id!);
            },
        });
    }

    // noinspection JSUnusedGlobalSymbols
    protected async actionQuickEdit(data?: Record<string, any>) {
        data = data || {};

        const id = data.id;

        if (!id) {
            console.error("No id.");

            return;
        }

        let model = null;

        if (this.collection) {
            model = this.collection.get(id);
        }

        let scope = data.scope;

        if (!scope && model) {
            scope = model.entityType;
        }

        if (!scope) {
            scope = this.scope;
        }

        if (!scope) {
            console.error("No scope.");

            return;
        }

        if (!this.quickEditDisabled) {
            const helper = new RecordModal();

            const rootUrl = this.options.keepCurrentRootUrl ? this.getRouter().getCurrentUrl() : undefined;

            await helper.showEdit(this, {
                entityType: scope,
                id: id,
                model: model ?? null,
                fullFormDisabled: data.noFullForm,
                rootUrl: rootUrl,
                beforeSave: m => {
                    this.trigger('before:save', m);
                },
                afterSave: m => {
                    const model = this.collection.get(m.id!);

                    if (model) {
                        model.setMultiple(m.getClonedAttributes(), {sync: true});
                    }

                    this.trigger('after:save', m);
                },
                returnDispatchParams: {
                    controller: scope,
                    action: null,
                    options: {
                        isReturn: true,
                    },
                },
            });

            return;
        }

        const options = {
            id: id,
            model: this.collection.get(id),
            returnUrl: this.getRouter().getCurrentUrl(),
            returnDispatchParams: {
                controller: scope,
                action: null,
                options: {
                    isReturn: true,
                }
            },
        } as any;

        if (this.options.keepCurrentRootUrl) {
            options.rootUrl = this.getRouter().getCurrentUrl();
        }

        this.getRouter().navigate(`#${scope}/edit/${id}`, {trigger: false});
        this.getRouter().dispatch(scope, 'edit', options);
    }

    /**
     * Compose a row selector.
     */
    protected getRowSelector(id: string): string {
        return `tr.list-row[data-id="${id}"]`;
    }

    // noinspection JSUnusedGlobalSymbols
    protected async actionQuickRemove(data?: {id?: string}): Promise<void> {
        data = data || {};

        const id = data.id;

        if (!id) {
            return;
        }

        const model = this.collection.get(id);

        if (!model) {
            throw new Error("No model.");
        }

        const index = this.collection.indexOf(model);



        if (!this.getAcl().checkModel(model, 'delete')) {
            Ui.error(this.translate('Access denied'));

            return;
        }

        await this.confirm({
            message: this.translate('removeRecordConfirmation', 'messages', this.scope),
            confirmText: this.translate('Remove'),
        });

        this.collection.trigger('model-removing', id);
        this.collection.remove(model);

        Ui.notifyWait();

        try {
            await model.destroy({wait: true, fromList: true});
        } catch (e) {
            if (!this.collection.models.includes(model)) {
                this.collection.add(model, {at: index});
            }

            return;
        }

        Ui.success(this.translate('Removed'));

        this.trigger('after:delete', model);
        this.removeRecordFromList(id);
    }

    /**
     * @param id An ID.
     */
    protected removeRecordFromList(id: string) {
        if (this.collection.total > 0) {
            this.collection.total--;

            this.collection.trigger('update-total');
        }

        this.collection.remove(id);

        this.$el.find('.total-count-span').text(this.collection.total.toString());

        let index = this.checkedList.indexOf(id);

        if (index !== -1) {
            this.checkedList.splice(index, 1);
        }

        const key = id;

        this.clearView(key);

        index = this.rowList.indexOf(key);

        if (~index) {
            this.rowList.splice(index, 1);
        }

        this.removeRowHtml(id);
    }

    /**
     * @param {string} id An ID.
     */
    protected removeRowHtml(id: string) {
        this.$el.find(this.getRowSelector(id)).remove();

        if (
            this.collection.length === 0 &&
            (this.collection.total === 0 || this.collection.total === -2)
        ) {
            this.reRender();
        }
    }

    /**
     * @param id An ID.
     */
    isIdChecked(id: string): boolean {
        return this.checkedList.indexOf(id) !== -1;
    }

    protected setupRowActionDefs() {
        this._rowActionHandlers = {};

        const list = this.options.additionalRowActionList;

        if (!list) {
            return;
        }

        this._additionalRowActionList = list;

        const defs = this.getMetadata().get(`clientDefs.${this.scope}.rowActionDefs`) || {};

        const promiseList = list.map(async action => {
            const itemDefs = defs[action] || {};

            if (!itemDefs.handler) {
                return Promise.resolve();
            }

            const Handler: any = await Espo.loader.requirePromise(itemDefs.handler);

            this._rowActionHandlers[action] = new Handler(this);

            return true;
        });

        this.wait(Promise.all(promiseList));
    }

    // noinspection JSUnusedGlobalSymbols
    protected actionRowAction(data: {actualAction: string, id: string}) {
        const action = data.actualAction;
        const id = data.id;

        if (!action) {
            return;
        }

        const handler: {process: (model: Model, action: string) => any} = (this._rowActionHandlers || {})[action];

        if (!handler) {
            console.warn(`No handler for action ${action}.`);

            return;
        }

        const model = this.collection.get(id);

        if (!model) {
            return;
        }

        handler.process(model, action);
    }

    /**
     * @todo Move to `views/record/list`.
     */
    protected setupSettings() {
        if (!this.options.settingsEnabled || !this.collection.entityType || !this.layoutName || !this.entityType) {
            return;
        }

        if (
            (
                !this.forceSettings &&
                !this.getMetadata().get(`scopes.${this.entityType}.object`)
            ) ||
            this.getConfig().get('listViewSettingsDisabled')
        ) {
            return;
        }

        if (this.settingsDisabled) {
            return;
        }

        this._listSettingsHelper = this.options.settingsHelper ?? new ListSettingsHelper(
            this.entityType,
            this.layoutName,
            this.getUser().id!,
            {
                useStorage: this.options.storeSettings ?? true,
            }
        );

        const view = new RecordListSettingsView({
            layoutProvider: () => this.listLayout as any,
            helper: this._listSettingsHelper,
            entityType: this.entityType,
            columnResize: this.columnResize,
            onChange: (options) => this.afterSettingsChange(options),
        });

        this.assignView('settings', view, '.settings-container');
    }

    /**
     * @todo Move to `views/record/list`.
     */
    protected async afterSettingsChange(
        options: import('views/record/list/settings').RecordListSettingsViewOnChangeOptions,
    ) {
        if (options.action === 'toggleColumnResize') {
            await this.reRender();

            return;
        }

        if (options.action === 'toggleColumn' || options.action === 'resetToDefault') {
            const selectAttributes = await this.getSelectAttributeList();

            if (selectAttributes) {
                this.collection.data.select = selectAttributes.join(',');
            }
        }

        if (
            options.action === 'toggleColumn' &&
            !this._listSettingsHelper.getHiddenColumnMap()[options.column!] &&
            this._columnResizeHelper
        ) {
            const helper = new ListColumnWidthControlHelper({
                view: this,
                helper: this._listSettingsHelper,
                layoutProvider: () => this.listLayout as any,
            });

            helper.adjust();
        }

        this._internalLayout = null;

        Ui.notifyWait();

        await this.collection.fetch();

        Ui.notify();
    }

    /**
     * Whether the pagination is enabled.
     *
     * @return {boolean}
     */
    hasPagination(): boolean {
        return this.pagination;
    }

    /**
     * Hide a mass action. Requires re-render.
     *
     * @param name An action name.
     * @since 8.4.0
     */
    protected hideMassAction(name: string) {
        if (!this.massActionDefs[name]) {
            this.massActionDefs[name] = {};
        }

        this.massActionDefs[name].hidden = true;
    }

    /**
     * Show a mass action. Requires re-render.
     *
     * @param name An action name.
     * @since 8.4.0
     */
    protected showMassAction(name: string) {
        if (!this.massActionDefs[name]) {
            this.massActionDefs[name] = {};
        }

        this.massActionDefs[name].hidden = false;
    }

    private getMassActionDataList(): ({name: string, hidden: boolean} | false)[] {
        const groups: string[][] = [];

        this.massActionList.forEach(action => {
            const item = this.massActionDefs[action];

            // For bc.
            // @ts-ignore
            if (item === false) {
                return;
            }

            const index = (!item || item.groupIndex === undefined ? 9999 : item.groupIndex) + 100;

            if (groups[index] === undefined) {
                groups[index] = [];
            }

            groups[index].push(action);
        });

        const list: (string | false)[] = [];

        groups.forEach(subList => {
            subList.forEach(it => list.push(it));

            list.push(false);
        });

        return list.map(name => {
            if (name === false) {
                return false;
            }

            return {
                name,
                hidden: (this.massActionDefs[name] || {}).hidden,
            };
        }) as any[];
    }

    /**
     * Uncheck all.
     *
     * @since 8.4.0
     */
    uncheckAll() {
        if (this.allResultIsChecked) {
            this.unselectAllResult();
        }

        this.checkedList.forEach(id => this.uncheckRecord(id));
    }

    /**
     * To temporarily disable checkboxes.
     *
     * @since 8.4.0
     */
    disableCheckboxes() {
        if (!this.checkboxes) {
            return;
        }

        this._disabledCheckboxes = true;

        this.uncheckAll();

        this.$el.find('input.record-checkbox').attr('disabled', 'disabled');

        if (this.$selectAllCheckbox) {
            this.$selectAllCheckbox.attr('disabled', 'disabled');
        }
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * To enabled temporarily disabled checkboxes.
     *
     * @since 8.4.0
     */
    enableCheckboxes() {
        if (!this.checkboxes) {
            return;
        }

        this._disabledCheckboxes = false;

        this.$el.find('input.record-checkbox').removeAttr('disabled');

        if (this.$selectAllCheckbox) {
            this.$selectAllCheckbox.removeAttr('disabled');
        }
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Checkboxes are disabled.
     *
     * @since 9.0.1
     */
    checkboxesAreDisabled(): boolean {
        return this._disabledCheckboxes || !this.checkboxes;
    }

    /**
     * Rebuild the internal layout.
     *
     * @since 8.4.0
     */
    rebuild(): Promise<void> {
        return new Promise(resolve => {
            this._internalLayout = null;

            this.buildRows(() => resolve(undefined));
        })
    }
}

export default ListBaseRecordView;

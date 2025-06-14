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

/** @module views/record/kanban */

import ListRecordView from 'views/record/list';
import RecordModal from 'helpers/record-modal';

/**
 * A kanban record view.
 */
class KanbanRecordView extends ListRecordView {

    template = 'record/kanban'

    itemViewName = 'views/record/kanban-item'
    rowActionsView = 'views/record/row-actions/default-kanban'

    type = 'kanban'
    name = 'kanban'

    showCount = true
    headerDisabled = false
    layoutName = 'kanban'
    portalLayoutDisabled = false
    minColumnWidthPx = 220
    showMore = true
    quickDetailDisabled = false
    quickEditDisabled = false
    _internalLayout = null
    buttonsDisabled = false
    backDragStarted = true
    paginationDisabled = true
    columnResize = false

    /**
     * @private
     * @type {{
     *     list: Record[],
     *     total: number,
     *     name: string,
     *     label?: string,
     *     style?: string|null,
     * }[]}
     */
    groupRawDataList

    /**
     * @private
     * @type {import('collection').default[]}
     */
    subCollectionList

    /**
     * A button list.
     *
     * @protected
     * @type {module:views/record/list~button[]}
     */
    buttonList = []

    /**
     * @private
     * @type {import('collection').default}
     */
    seedCollection

    /**
     * Layout item definitions.
     *
     * @typedef module:views/record/kanban~layoutItemDefs
     * @type {Object}
     * @property {string} name A name (usually a field name).
     * @property {string} [view] An overridden field view name.
     * @property {boolean} [link] To use `listLink` mode (link to the detail view).
     * @property {'left'|'right'} [align] An alignment.
     * @property {boolean} [isLarge] Large.
     * @property {boolean} [isMuted] Muted.
     * @property {boolean} [hidden] Hidden by default.
     */

    /**
     * Kanban view options.
     *
     * @typedef {Record} module:views/record/kanban~options
     * @property {import('collection').default} collection A collection.
     * @property {module:views/record/kanban~layoutItemDefs[]} [listLayout] A layout.
     * @property {boolean} [keepCurrentRootUrl] Keep a current root URL.
     * @property {string|'kanban'} [type] A type.
     * @property {boolean} [rowActionsDisabled] Disable row actions.
     * @property {boolean} [buttonsDisabled] Disable buttons.
     * @property {Record} [rowActionsOptions] Row-actions options.
     * @property {string[]} [additionalRowActionList] Additional row-action list.
     * @property {import('helpers/list/settings').default} [settingsHelper] A settings helper.
     * @property {string} [layoutName] A layout name.
     * @property {boolean} [skipBuildRows] Do not build rows on initialization. Use when the collection will be fetched
     *    afterward.
     * @property {boolean} [rowActionsDisabled] Disable row actions.
     * @property {boolean} [displayTotalCount] Display total count.
     * @property {boolean} [showCount] To show a record count.
     * @property {boolean} [topBarDisabled] Disable the top bar.
     * @property {function(string, string[]): Promise} [onGroupOrder] On group order function.
     * @property {function(string): Promise<Record>} [getCreateAttributes] Get create attributes function.
     * @property {function(import('model').default): Promise} [groupChangeSaveHandler] Handles record saving after drop.
     * @property {function(string)} [createActionHandler] A create handler.
     * @property {string} [statusField] A status field.
     * @property {boolean} [canChangeGroup] Can change group.
     * @property {boolean} [canCreate] Can create.
     * @property {boolean} [canReOrder] Can re-order.
     * @property {boolean} [moveOverRowAction] Enable a move-over row action.
     */

    /**
     * @param {module:views/record/kanban~options} options Options.
     */
    constructor(options) {
        super(options);

        /** @private */
        this.onGroupOrder = options.onGroupOrder;
        /** @private */
        this.getCreateAttributes = options.getCreateAttributes;
        /** @private */
        this.createActionHandler = options.createActionHandler;
        /** @private */
        this.groupChangeSaveHandler = options.groupChangeSaveHandler;
    }

    events = {
        /** @this KanbanRecordView */
        'click a.link': function (e) {
            if (e.ctrlKey || e.metaKey || e.shiftKey) {
                return;
            }

            e.stopPropagation();

            if (!this.scope || this.selectable) {
                return;
            }

            e.preventDefault();

            const id = $(e.currentTarget).data('id');
            const model = this.collection.get(id);

            const scope = this.getModelScope(id);

            const options = {
                id: id,
                model: model,
            };

            if (this.options.keepCurrentRootUrl) {
                options.rootUrl = this.getRouter().getCurrentUrl();
            }

            this.getRouter().navigate('#' + scope + '/view/' + id, {trigger: false});
            this.getRouter().dispatch(scope, 'view', options);
        },
        /** @this KanbanRecordView */
        'click [data-action="groupShowMore"]': function (e) {
            const $target = $(e.currentTarget);

            const group = $target.data('name');

            this.groupShowMore(group);
        },
        /** @this KanbanRecordView */
        'click .action': function (e) {
            Espo.Utils.handleAction(this, e.originalEvent, e.currentTarget, {
                actionItems: [...this.buttonList],
                className: 'list-action-item',
            });
        },
        /** @this KanbanRecordView */
        'mouseenter th.group-header': function (e) {
            if (!this.isCreatable) {
                return;
            }

            const group = $(e.currentTarget).attr('data-name');

            this.showPlus(group);
        },
        /** @this KanbanRecordView */
        'mouseleave th.group-header': function (e) {
            const group = $(e.currentTarget).attr('data-name');

            this.hidePlus(group);
        },
        /** @this KanbanRecordView */
        'click [data-action="createInGroup"]': function (e) {
            const group = $(e.currentTarget).attr('data-group');

            this.actionCreateInGroup(group);
        },
        /** @this KanbanRecordView */
        'mousedown .kanban-columns td': function (e) {
            if ($(e.originalEvent.target).closest('.item').length) {
                return;
            }

            this.initBackDrag(e.originalEvent);
        },
        /** @this KanbanRecordView */
        'auxclick a.link': function (e) {
            const isCombination = e.button === 1 && (e.ctrlKey || e.metaKey);

            if (!isCombination) {
                return;
            }

            const $target = $(e.currentTarget);

            const id = $target.attr('data-id');

            if (!id) {
                return;
            }

            if (this.quickDetailDisabled) {
                return;
            }

            const $quickView = $target.parent().closest(`[data-id="${id}"]`)
                .find(`ul.list-row-dropdown-menu[data-id="${id}"] a[data-action="quickView"]`);

            if (!$quickView.length) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            this.actionQuickView({id: id});
        },
    }

    // noinspection JSCheckFunctionSignatures
    data() {
        const topBar = !this.options.topBarDisabled && (
            this.displayTotalCount ||
            this.buttonList.length &&
            !this.buttonsDisabled ||
            !!this._listSettingsHelper
        );

        // noinspection JSValidateTypes
        return {
            scope: this.scope,
            header: this.header,
            topBar: topBar,
            showCount: this.showCount && this.collection.total > 0,
            buttonList: this.buttonList,
            displayTotalCount: this.displayTotalCount && this.collection.total >= 0 && !this._renderEmpty,
            totalCount: this.collection.total,
            groupDataList: this.groupDataList,
            minTableWidthPx: this.minColumnWidthPx * this.groupDataList.length,
            isEmptyList: this.collection.models.length === 0,
            totalCountFormatted: this.getNumberUtil().formatInt(this.collection.total),
            noDataDisabled: this._renderEmpty,
        };
    }

    init() {
        /** @type {module:views/record/list~columnDefs[]|null} */
        this.listLayout = this.options.listLayout || this.listLayout;
        this.type = this.options.type || this.type;

        this.layoutName = this.options.layoutName || this.layoutName || this.type;

        this.rowActionsView = _.isUndefined(this.options.rowActionsView) ?
            this.rowActionsView :
            this.options.rowActionsView;

        if (this.massActionsDisabled && !this.selectable) {
            this.checkboxes = false;
        }

        this.rowActionsDisabled = this.options.rowActionsDisabled || this.rowActionsDisabled;

        if ('buttonsDisabled' in this.options) {
            this.buttonsDisabled = this.options.buttonsDisabled;
        }
    }

    /** @inheritDoc */
    getModelScope(id) {
        return this.scope;
    }

    /** @inheritDoc */
    setup() {
        if (typeof this.collection === 'undefined') {
            throw new Error('Collection has not been injected into Record.List view.');
        }

        this.listenTo(this.collection, 'sync', (c, response) => {
            this.subCollectionList = undefined;

            // noinspection JSUnresolvedReference
            this.groupRawDataList = response.groups;
        });

        this.layoutLoadCallbackList = [];

        this.entityType = this.collection.entityType || null;
        this.scope = this.options.scope || this.entityType;

        this.buttonList = Espo.Utils.clone(this.buttonList);

        if ('showCount' in this.options) {
            this.showCount = this.options.showCount;
        }

        this.displayTotalCount = this.showCount && this.getConfig().get('displayListViewRecordCount');

        this.minColumnWidthPx = this.getConfig().get('kanbanMinColumnWidth') || this.minColumnWidthPx;

        if ('displayTotalCount' in this.options) {
            this.displayTotalCount = this.options.displayTotalCount;
        }

        if (
            this.getUser().isPortal() &&
            !this.portalLayoutDisabled &&
            this.getMetadata().get(['clientDefs', this.scope, 'additionalLayouts', this.layoutName + 'Portal'])
        ) {
            this.layoutName += 'Portal';
        }

        if ('canReOrder' in this.options) {
            this.orderDisabled = !this.options.canReOrder;
        } else {
            this.orderDisabled = this.getMetadata().get(['scopes', this.scope, 'kanbanOrderDisabled']);

            if (this.getUser().isPortal()) {
                this.orderDisabled = true;
            }
        }

        this.statusField = this.options.statusField || this.getMetadata().get(['scopes', this.scope, 'statusField']);

        if (!this.statusField) {
            throw new Error(`No status field for entity type '${this.scope}'.`);
        }

        this.seedCollection = this.collection.clone();
        this.seedCollection.reset();
        this.seedCollection.url = this.scope;
        this.seedCollection.maxSize = this.collection.maxSize;
        this.seedCollection.entityType = this.collection.entityType;
        this.seedCollection.orderBy = this.collection.defaultOrderBy;
        this.seedCollection.order = this.collection.defaultOrder;

        this.setupRowActionDefs();
        this.setupSettings();

        this.listenTo(this.collection, 'sync', () => {
            this._renderEmpty = false;

            this.buildRowsAndRender();
        });

        this.collection.listenTo(
            this.collection,
            'change:' + this.statusField,
            this.onChangeGroup.bind(this),
            this
        );

        this.buildRows();

        this.on('remove', () => {
            $(window).off('resize.kanban-a-' + this.cid);
            $(window).off('scroll.kanban-' + this.cid);
            $(window).off('resize.kanban-' + this.cid);
        });

        if ('canChangeGroup' in this.options) {
            this.statusFieldIsEditable = this.options.canChangeGroup;
        } else {
            this.statusFieldIsEditable =
                this.getAcl().checkScope(this.entityType, 'edit') &&
                !this.getAcl().getScopeForbiddenFieldList(this.entityType, 'edit').includes(this.statusField) &&
                !this.getMetadata().get(['clientDefs', this.scope, 'editDisabled']) &&
                !this.getMetadata().get(['entityDefs', this.entityType, 'fields', this.statusField, 'readOnly']);
        }

        if ('canCreate' in this.options) {
            this.isCreatable = this.options.canCreate;
        } else {
            this.isCreatable = this.statusFieldIsEditable &&
                this.getAcl().check(this.entityType, 'create') &&
                !this.getMetadata().get(`clientDefs.${this.scope}.createDisabled`);
        }

        /** @private */
        this.moveOverRowAction = true;

        if ('moveOverRowAction' in this.options) {
            this.moveOverRowAction = this.options.moveOverRowAction;
        }

        this._renderEmpty = this.options.skipBuildRows;

        this.wait(
            this.getHelper().processSetupHandlers(this, 'record/kanban')
        );

        /**
         * @private
         * @type {boolean}
         */
        this.hasStars = this.getMetadata().get(`scopes.${this.entityType}.stars`) || false;
    }

    afterRender() {
        const $window = $(window);

        this.$listKanban = this.$el.find('.list-kanban');
        this.$content = $('#content');

        this.$groupColumnList = this.$listKanban.find('.group-column-list');

        this.$container = this.$el.find('.list-kanban-container');

        $window.off('resize.kanban-a-' + this.cid);
        $window.on('resize.kanban-a-' + this.cid, () => this.adjustMinHeight());

        this.$container.on('scroll', () => this.syncHeadScroll());

        this.adjustMinHeight();

        if (this.statusFieldIsEditable) {
            this.initSortable();
        }

        this.initStickableHeader();

        this.$showMore = this.$el.find('.group-column .show-more');

        this.plusElementMap = {};

        this.groupDataList.forEach(item => {
            const value = CSS.escape(item.name);

            this.plusElementMap[item.name] = this.$el.find(`.kanban-head .create-button[data-group="${value}"]`);
        });
    }

    /**
     * @private
     */
    initStickableHeader() {
        const $container = this.$headContainer = this.$el.find('.kanban-head-container');
        const topBarHeight = (this.getThemeManager().getParam('navbarHeight') || 30) *
            this.getThemeManager().getFontSizeFactor();

        const screenWidthXs = this.getThemeManager().getParam('screenWidthXs');

        const $middle = this.$el.find('.kanban-columns-container');
        const $window = $(window);

        const $block = $('<div>')
            .addClass('kanban-head-placeholder')
            .html('&nbsp;')
            .hide()
            .insertAfter($container);

        $window.off('scroll.kanban-' + this.cid);
        $window.on('scroll.kanban-' + this.cid, () => {
            controlSticking();
        });

        $window.off('resize.kanban-' + this.cid);
        $window.on('resize.kanban-' + this.cid, () => controlSticking());

        const controlSticking = () => {
            const width = $middle.width();

            if ($(window.document).width() < screenWidthXs) {
                $container.removeClass('sticked');
                $container.css('width', '');
                $block.hide();
                $container.show();

                $container.get(0).scrollLeft = 0;
                $container.children().css('width', '');

                return;
            }

            const stickTop = this.$listKanban.offset().top - topBarHeight;

            const edge = $middle.offset().top + $middle.outerHeight(true);
            const scrollTop = $window.scrollTop();

            if (scrollTop < edge) {
                if (scrollTop > stickTop) {
                    const containerWidth = this.$container.width() - 3;

                    $container.children().css('width', width);

                    $container.css('width', containerWidth + 'px');

                    if (!$container.hasClass('sticked')) {
                        $container.addClass('sticked');
                        $block.show();
                    }
                } else {
                    $container.css('width', '');

                    if ($container.hasClass('sticked')) {
                        $container.removeClass('sticked');
                        $block.hide();
                    }
                }

                $container.show();

                this.syncHeadScroll();

                return;
            }

            $container.css('width', width + 'px');
            $container.hide();

            $block.show();

            $container.get(0).scrollLeft = 0;
            $container.children().css('width', '');
        };
    }

    /**
     * @private
     */
    initSortable() {
        const $list = this.$groupColumnList;

        $list.find('> .item').on('touchstart', (e) => {
            e.originalEvent.stopPropagation();
        });

        const orderDisabled = this.orderDisabled;

        const $groupColumnList = this.$el.find('.group-column-list');

        $list.sortable({
            distance: 10,
            connectWith: '.group-column-list',
            cancel: '.btn-group *',
            containment: this.getSelector(),
            scroll: false,
            over: function () {
                $(this).addClass('drop-hover');
            },
            out: function () {
                $(this).removeClass('drop-hover');
            },
            sort: (e) => {
                if (!this.blockScrollControl) {
                    this.controlHorizontalScroll(e.originalEvent);
                }
            },
            start: (e, ui) => {
                $groupColumnList.addClass('drop-active');

                $list.sortable('refreshPositions');

                $(ui.item)
                    .find('.btn-group.open > .dropdown-toggle')
                    .parent()
                    .removeClass('open');

                this.draggedGroupFrom = $(ui.item).closest('.group-column-list').data('name');
                this.$showMore.addClass('hidden');

                this.sortIsStarted = true;
                this.sortWasCentered = false;

                this.$draggable = ui.item;
            },
            stop: (e, ui) => {
                this.blockScrollControl = false;
                this.sortIsStarted = false;
                this.$draggable = null;

                const $item = $(ui.item);

                this.$el.find('.group-column-list').removeClass('drop-active');

                const group = $item.closest('.group-column-list').data('name');
                const id = $item.data('id');

                const draggedGroupFrom = this.draggedGroupFrom;

                this.draggedGroupFrom = null;

                this.$showMore.removeClass('hidden');

                if (group !== draggedGroupFrom) {
                    const model = this.collection.get(id);

                    if (!model) {
                        $list.sortable('cancel');

                        return;
                    }

                    const attributes = {};

                    attributes[this.statusField] = group;

                    this.handleAttributesOnGroupChange(model, attributes, group);

                    $list.sortable('disable');

                    const processSave = async () =>{
                        if (this.groupChangeSaveHandler) {
                            model.set(attributes, {isDrop: true});

                            return this.groupChangeSaveHandler(model);
                        }

                        return model.save(attributes, {
                            patch: true,
                            isDrop: true,
                        });
                    };

                    processSave()
                        .then(() => {
                            Espo.Ui.success(this.translate('Saved'));

                            $list.sortable('destroy');

                            this.initSortable();

                            this.moveModelBetweenGroupCollections(model, draggedGroupFrom, group);

                            if (!orderDisabled) {
                                this.reOrderGroup(group);
                                this.storeGroupOrder(group);
                            }

                            this.rebuildGroupDataList();
                        })
                        .catch(() => {
                            $list.sortable('cancel');
                            $list.sortable('enable');
                        });

                    return;
                }

                if (orderDisabled) {
                    $list.sortable('cancel');
                    $list.sortable('enable');

                    return;
                }

                this.reOrderGroup(group);
                this.storeGroupOrder(group);
                this.rebuildGroupDataList();
            },
        });
    }

    /**
     * @param {string} group
     * @param {string} [id] Prepend. To be used after save.
     * @return {Promise}
     */
    storeGroupOrder(group, id) {
        const ids = this.getGroupOrderFromDom(group);

        if (id) {
            ids.unshift(id);
        }

        if (this.onGroupOrder) {
            return this.onGroupOrder(group, ids);
        }

        return Espo.Ajax.putRequest('Kanban/order', {
            entityType: this.entityType,
            group: group,
            ids: ids,
        });
    }

    /**
     * @private
     * @param {string} group
     * @return {string[]}
     */
    getGroupOrderFromDom(group) {
        const ids = [];

        const $group = this.$el.find('.group-column-list[data-name="' + group + '"]');

        $group.children().each((i, el) => {
            ids.push($(el).data('id'));
        });

        return ids;
    }

    /**
     * @param {string} group
     */
    reOrderGroup(group) {
        const groupCollection = this.getGroupCollection(group);
        const ids = this.getGroupOrderFromDom(group);

        const modelMap = {};

        groupCollection.models.forEach((m) => {
            modelMap[m.id] = m;
        });

        while (groupCollection.models.length) {
            groupCollection.pop({silent: true});
        }

        ids.forEach(id => {
            const model = modelMap[id];

            if (!model) {
                return;
            }

            groupCollection.add(model, {silent: true});
        });
    }

    /**
     * @private
     */
    rebuildGroupDataList() {
        this.groupDataList.forEach(item => {
            item.dataList = [];

            for (const model of item.collection.models) {
                item.dataList.push({
                    key: model.id,
                    id: model.id,
                });
            }
        });
    }

    /**
     * @private
     * @param {import('model').default} model
     * @param {string} groupFrom
     * @param {string} groupTo
     */
    moveModelBetweenGroupCollections(model, groupFrom, groupTo) {
        let collection = this.getGroupCollection(groupFrom);

        if (!collection) {
            return;
        }

        collection.remove(model.id, {silent: true});

        collection = this.getGroupCollection(groupTo);

        if (!collection) {
            return;
        }

        collection.add(model, {silent: true});
    }

    handleAttributesOnGroupChange(model, attributes, group) {}

    adjustMinHeight() {
        if (
            this.collection.models.length === 0 ||
            !this.$container
        ) {
            return;
        }

        let height = this.getHelper()
            .calculateContentContainerHeight(this.$el.find('.kanban-columns-container'));

        const containerEl = this.$container.get(0);

        if (containerEl && containerEl.scrollWidth > containerEl.clientWidth) {
            height -= 18;
        }

        if (height < 100) {
            height = 100;
        }

        this.$listKanban.find('td.group-column').css({
            minHeight: height + 'px',
        });
    }

    getListLayout(callback) {
        if (this.listLayout) {
            callback.call(this, this.listLayout);

            return;
        }

        this._loadListLayout((listLayout) => {
            this.listLayout = listLayout;
            callback.call(this, listLayout);
        });
    }

    async getSelectAttributeList(callback) {
        const attributeList = await super.getSelectAttributeList();

        if (!attributeList) {
            return null;
        }

        if (!attributeList.includes(this.statusField)) {
            attributeList.push(this.statusField);
        }

        if (callback) {
            // For bc.
            callback(attributeList);
        }

        return attributeList;
    }

    buildRows(callback) {
        let groupList = this.groupRawDataList;

        if (this.subCollectionList && groupList) {
            this.subCollectionList.forEach((collection, i) => {
                const group = groupList[i];

                if (!group) {
                    console.warn("No group.", collection);

                    return;
                }

                group.list = collection.models.map(model => model.getClonedAttributes());
                group.total = collection.total;
            });
        }

        if (!groupList) {
            groupList = [];
        }

        this.collection.reset();

        /** @type {import('collection').default[]} */
        this.subCollectionList = [];

        this.wait(true);

        /**
         * @type {{
         *     name: string,
         *     label: string,
         *     style: string,
         *     hasShowMore: boolean,
         *     collection: import('collection').default,
         *     dataList: Record[],
         * }[]}
         */
        this.groupDataList = [];

        let count = 0;
        let loadedCount = 0;

        this.getListLayout(listLayout => {
            this.listLayout = listLayout;

            groupList.forEach(item => {
                const collection = this.seedCollection.clone();

                this.listenTo(collection, 'destroy', (model, attributes, o) => {
                    if (o.fromList) {
                        return;
                    }

                    this.removeRecordFromList(model.id);
                });

                collection.total = item.total;
                collection.url = this.collection.url;
                collection.where = this.collection.where;
                collection.entityType = this.seedCollection.entityType;
                collection.maxSize = this.seedCollection.maxSize;
                collection.orderBy = this.seedCollection.orderBy;
                collection.order = this.seedCollection.order;

                collection.whereAdditional = [
                    {
                        field: this.statusField,
                        type: 'equals',
                        value: item.name,
                    }
                ];

                collection.data.groupName = item.name;
                collection.add(item.list);

                this.subCollectionList.push(collection);
                this.collection.add(collection.models);

                const itemDataList = [];

                collection.models.forEach(model => {
                    count ++;

                    itemDataList.push({
                        key: model.id,
                        id: model.id,
                    });
                });

                const style = item.style ||
                    this.getMetadata().get(`entityDefs.${this.scope}.fields.${this.statusField}.style.${item.name}`);

                const label = item.label ||
                    this.getLanguage().translateOption(item.name, this.statusField, this.scope);

                const o = {
                    name: item.name,
                    label: label,
                    dataList: itemDataList,
                    collection: collection,
                    hasShowMore: collection.total > collection.length || collection.total === -1,
                    style: style,
                };

                this.groupDataList.push(o);
            });

            if (count === 0) {
                this.wait(false);

                if (callback) {
                    callback();
                }

                return;
            }

            this.groupDataList.forEach(groupItem => {
                groupItem.dataList.forEach((item, j) => {
                    const model = groupItem.collection.get(item.id);

                    this.buildRow(j, model, () => {
                        loadedCount++;

                        if (loadedCount === count) {
                            this.wait(false);

                            if (callback) {
                                callback();
                            }
                        }
                    });
                });
            });
        });
    }

    buildRow(i, model, callback) {
        const key = model.id;

        const hiddenMap = this._listSettingsHelper ?
            this._listSettingsHelper.getHiddenColumnMap() : {};

        const itemLayout = this.listLayout.filter(item => {
            const name = item.name;

            if (!name) {
                return true;
            }

            if (hiddenMap[name]) {
                return false;
            }

            if (item.hidden && !(name in hiddenMap)) {
                return false;
            }

            return true;
        });

        this.createView(key, this.itemViewName, {
            model: model,
            selector: `.item[data-id="${model.id}"]`,
            itemLayout:  itemLayout,
            rowActionsDisabled: this.rowActionsDisabled,
            rowActionsView: this.rowActionsView,
            rowActionHandlers: this._rowActionHandlers || {},
            setViewBeforeCallback: this.options.skipBuildRows && !this.isRendered(),
            statusFieldIsEditable: this.statusFieldIsEditable,
            moveOverRowAction: this.moveOverRowAction,
            additionalRowActionList: this._additionalRowActionList,
            scope: this.scope,
            hasStars: this.hasStars,
        }, callback);
    }

    /**
     * @param {string} id
     */
    removeRecordFromList(id) {
        this.collection.remove(id);

        if (this.collection.total > 0) {
            this.collection.total--;
        }

        this.collection.trigger('update-total');

        this.totalCount = this.collection.total;

        this.$el.find('.total-count-span').text(this.totalCount.toString());

        this.clearView(id);

        this.$el.find('.item[data-id="'+id+'"]').remove();

        this.subCollectionList.forEach(collection => {
            if (collection.get(id)) {
                collection.remove(id);
            }
        });

        for (const groupItem of this.groupDataList) {
            for (let j = 0; j < groupItem.dataList.length; j++) {
                const item = groupItem.dataList[j];

                if (item.id !== id) {
                    continue;
                }

                groupItem.dataList.splice(j, 1);

                if (groupItem.collection.total > 0) {
                    groupItem.collection.total--;
                }

                groupItem.hasShowMore = groupItem.collection.total > groupItem.collection.length ||
                    groupItem.collection.total === -1;

                break;
            }
        }
    }

    /**
     * @protected
     * @param {import('model').default} model A model.
     * @param {string} value A group.
     * @param {Record} o Options.
     */
    onChangeGroup(model, value, o) {
        const id = model.id;
        const group = model.get(this.statusField);

        this.subCollectionList.forEach(collection => {
            if (collection.get(id)) {
                collection.remove(id);

                if (collection.total > 0) {
                    collection.total--;
                }
            }
        });

        let dataItem;

        for (const groupItem of this.groupDataList) {
            for (let j = 0; j < groupItem.dataList.length; j++) {
                const item = groupItem.dataList[j];

                if (item.id === id) {
                    dataItem = item;
                    groupItem.dataList.splice(j, 1);

                    break;
                }
            }
        }

        if (!group) {
            return;
        }

        if (o.isDrop) {
            return;
        }

        for (const groupItem of this.groupDataList) {
            if (groupItem.name !== group) {
                continue;
            }

            groupItem.collection.unshift(model);
            groupItem.collection.total++;

            if (dataItem) {
                groupItem.dataList.unshift(dataItem);

                groupItem.hasShowMore = groupItem.collection.total > groupItem.collection.length ||
                    groupItem.collection.total === -1;
            }
        }

        const $item = this.$el.find('.item[data-id="' + id + '"]');
        const $column = this.$el.find('.group-column[data-name="' + group + '"] .group-column-list');

        if ($column.length) {
            $column.prepend($item);
        } else {
            $item.remove();
        }

        if (!this.orderDisabled) {
            this.storeGroupOrder(group);
        }
    }

    groupShowMore(group) {
        let groupItem;

        for (const i in this.groupDataList) {
            groupItem = this.groupDataList[i];

            if (groupItem.name === group) {
                break;
            }

            groupItem = null;
        }

        if (!groupItem) {
            return;
        }

        const collection = groupItem.collection;

        const $list = this.$el.find('.group-column-list[data-name="' + group + '"]');
        const $showMore = this.$el.find('.group-column[data-name="' + group + '"] .show-more');

        collection.data.select = this.collection.data.select;

        this.showMoreRecords({}, collection, $list, $showMore, () => {
            this.noRebuild = false;

            collection.models.forEach((model) => {
                if (this.collection.get(model.id)) {
                    return;
                }

                this.collection.add(model);

                groupItem.dataList.push({
                    key: model.id,
                    id: model.id,
                });
            });
        });
    }

    getDomRowItem(id) {
        return this.$el.find('.item[data-id="'+id+'"]');
    }

    getRowContainerHtml(id) {
        return $('<div>')
            .attr('data-id', id)
            .addClass('item')
            .get(0).outerHTML;
    }

    // noinspection JSUnusedGlobalSymbols
    actionMoveOver(data) {
        const model = this.collection.get(data.id);

        this.createView('moveOverDialog', 'views/modals/kanban-move-over', {
            model: model,
            statusField: this.statusField,
        }, view => {
            view.render();
        });
    }

    /**
     *
     * @param {string} group
     * @return {module:collection}
     */
    getGroupCollection(group) {
        let collection = null;

        this.subCollectionList.forEach(itemCollection => {
            if (itemCollection.data.groupName === group) {
                collection = itemCollection;
            }
        });

        return collection;
    }

    /**
     * @param {string} group
     */
    showPlus(group) {
        const $el = this.plusElementMap[group];

        if (!$el) {
            return;
        }

        $el.removeClass('hidden');
    }

    /**
     * @param {string} group
     */
    hidePlus(group) {
        const $el = this.plusElementMap[group];

        if (!$el) {
            return;
        }

        $el.addClass('hidden');
    }

    /**
     * @param {string} group
     */
    async actionCreateInGroup(group) {
        if (this.createActionHandler) {
            this.createActionHandler(group);

            return;
        }

        const getCreateAttributes = () => {
            if (this.getCreateAttributes) {
                return this.getCreateAttributes(group);
            }

            return Promise.resolve({[this.statusField]: group});
        };

        const attributes = await getCreateAttributes();

        const helper = new RecordModal();

        await helper.showCreate(this, {
            attributes: attributes ,
            entityType: this.scope,
            afterSave: async model => {
                if (this.orderDisabled) {
                    await this.collection.fetch({maxSize: this.collection.maxSize});

                    return;
                }

                await this.storeGroupOrder(group, model.id)
                await this.collection.fetch({maxSize: this.collection.maxSize});
            },
            beforeRender: view => {
                view.getRecordView().setFieldReadOnly(this.statusField, true);
            },
        });
    }

    /**
     * @private
     * @param {MouseEvent} e
     */
    initBackDrag(e) {
        this.backDragStarted = true;

        const containerEl = this.$container.get(0);

        containerEl.style.cursor = 'grabbing';
        containerEl.style.userSelect = 'none';

        const $document = $(document);

        const startLeft = containerEl.scrollLeft;
        const startX = e.clientX;

        $document.on(`mousemove.${this.cid}`, e => {
            // noinspection JSUnresolvedReference
            const dx = e.originalEvent.clientX - startX;

            containerEl.scrollLeft = startLeft - dx;

            this.syncHeadScroll();
        });

        $document.one('mouseup.' + this.cid, () => {
            this.stopBackDrag();
        });
    }

    stopBackDrag() {
        this.$container.get(0).style.cursor = 'default';
        this.$container.get(0).style.userSelect = 'none';

        $(document).off('mousemove.' + this.cid);
    }

    syncHeadScroll() {
        if (!this.$headContainer.hasClass('sticked')) {
            return;
        }

        this.$headContainer.get(0).scrollLeft = this.$container.get(0).scrollLeft;
    }

    controlHorizontalScroll(e) {
        if (!this.sortIsStarted) {
            return;
        }

        if (!this.$draggable) {
            return;
        }

        const draggableRect = this.$draggable.get(0).getBoundingClientRect();

        const itemLeft = draggableRect.left;
        const itemRight = draggableRect.right;

        const containerEl = this.$container.get(0);

        const rect = containerEl.getBoundingClientRect();

        const marginSens = 70;
        let step = 2;
        const interval = 5;
        const marginSensStepRatio = 4;
        const stepRatio = 3;

        const isRight = rect.right - marginSens < itemRight &&
            containerEl.scrollLeft + containerEl.offsetWidth < containerEl.scrollWidth;

        const isLeft = rect.left + marginSens > itemLeft &&
            containerEl.scrollLeft > 0;

        this.$groupColumnList.sortable('refreshPositions');

        if (isRight && this.sortWasCentered) {
            const margin = rect.right - itemRight;

            if (margin < marginSens / marginSensStepRatio) {
                step *= stepRatio;
            }

            const stepActual = Math.min(step, containerEl.offsetWidth - containerEl.scrollLeft);

            containerEl.scrollLeft = containerEl.scrollLeft + stepActual;

            this.syncHeadScroll();

            if (containerEl.scrollLeft + containerEl.offsetWidth === containerEl.scrollWidth) {
                this.blockScrollControl = false;

                return;
            }

            this.blockScrollControl = true;

            setTimeout(() => this.controlHorizontalScroll(e), interval);

            return;
        }

        if (isLeft && this.sortWasCentered) {
            const margin = -(rect.left - itemLeft);

            if (margin < marginSens / marginSensStepRatio) {
                step *= stepRatio;
            }

            const stepActual = Math.min(step, containerEl.scrollLeft);

            containerEl.scrollLeft = containerEl.scrollLeft - stepActual;

            this.syncHeadScroll();

            if (containerEl.scrollLeft === 0) {
                this.blockScrollControl = false;

                return;
            }

            this.blockScrollControl = true;

            setTimeout(() => this.controlHorizontalScroll(e), interval);

            return;
        }

        if (this.blockScrollControl && !isLeft && !isRight) {
            this.blockScrollControl = false;
        }

        if (!isLeft && !isRight) {
            this.sortWasCentered = true;
        }
    }

    /** @inheritDoc */
    async afterSettingsChange(options) {
        this._internalLayout = null;

        if (options.action === 'toggleColumn' || options.action === 'resetToDefault') {
            const selectAttributes = await this.getSelectAttributeList();

            if (selectAttributes) {
                this.collection.data.select = selectAttributes.join(',');
            }
        }

        Espo.Ui.notifyWait();

        await this.collection.fetch({maxSize: this.collection.maxSize});

        Espo.Ui.notify(false)
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Set can create.
     *
     * @param {boolean} canCreate
     * @sinc 8.4.0
     */
    setCanCreate(canCreate) {
        this.isCreatable = canCreate;
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Set can re-order.
     *
     * @param {boolean} canReOrder
     * @sinc 8.4.0
     */
    setCanReOrder(canReOrder) {
        this.orderDisabled = !canReOrder;
    }
}

export default KanbanRecordView;

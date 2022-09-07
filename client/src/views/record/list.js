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

define('views/record/list', ['view', 'helpers/mass-action', 'helpers/export', 'helpers/record-modal'],
function (Dep, MassActionHelper, ExportHelper, RecordModal) {

    /**
     * A record-list view. Renders and processes list items, actions.
     *
     * @class
     * @name Class
     * @extends module:view.Class
     * @memberOf module:views/record/list
     *
     * @todo Document all options.
     */
    return Dep.extend(/** @lends module:views/record/list.Class# */{

        /**
         * A row action.
         *
         * @typedef {Object} module:views/record/list~rowAction
         *
         * @property {string} action An action.
         * @property {string} [label] A label.
         * @property {string} [link] A link.
         * @property {Object.<string,string|number|boolean>} [data] Data attributes.
         */

        /**
         * @inheritDoc
         */
        template: 'record/list',

        /**
         * A type. Can be 'list', 'listSmall'.
         */
        type: 'list',

        /**
         * @inheritDoc
         */
        name: 'list',

        /**
         * A presentation type.
         */
        presentationType: 'table',

        /**
         * If true checkboxes will be shown. Can be overridden by an option parameter.
         *
         * @protected
         */
        checkboxes: true,

        /**
         * If true clicking on the record link will trigger 'select' event with model passed.
         * Can be overridden by an option parameter.
         */
        selectable: false,

        /**
         * A row-actions view. A dropdown on the right side.
         *
         * @protected
         * @type {string}
         */
        rowActionsView: 'views/record/row-actions/default',

        /**
         * Disable row-actions. Can be overridden by an option parameter.
         */
        rowActionsDisabled: false,

        /**
         * An entity type. Set automatically.
         *
         * @type {string|null}
         */
        entityType: null,

        /**
         * A scope. Set automatically.
         *
         * @type {string|null}
         */
        scope: null,

        /**
         * @protected
         */
        _internalLayoutType: 'list-row',

        /**
         * A selector to a list container.
         *
         * @protected
         */
        listContainerEl: '.list > table > tbody',

        /**
         * To show number of records. Can be overridden by an option parameter.
         *
         * @protected
         */
        showCount: true,

        /**
         * @protected
         */
        rowActionsColumnWidth: 25,

        /**
         * @protected
         */
        checkboxColumnWidth: 40,

        /**
         * @protected
         */
        minColumnWidth: 100,

        /**
         * A button. Handled by a class method `action{Name}`.
         *
         * @typedef {Object} module:views/record/list~button
         *
         * @property {string} name A name.
         * @property {string} label A label. To be translated in a current scope.
         * @property {'default'|'danger'|'warning'|'success'} [style] A style.
         * @property {boolean} [hidden] Hidden.
         */

        /**
         * A button list.
         *
         * @protected
         * @type {module:views/record/list~button[]}
         */
        buttonList: [],

        /**
         * A dropdown item. Handled by a class method `action{Name}`.
         *
         * @typedef {Object} module:views/record/list~dropdownItem
         *
         * @property {string} name A name.
         * @property {string} [label] A label. To be translated in a current scope.
         * @property {string} [html] An HTML.
         * @property {boolean} [hidden] Hidden.
         */

        /**
         * A dropdown item list. Can be overridden by an option parameter.
         *
         * @protected
         * @type {module:views/record/list~dropdownItem[]}
         */
        dropdownItemList: [],

        /**
         * Disable a header. Can be overridden by an option parameter.
         *
         * @protected
         */
        headerDisabled: false,

        /**
         * Disable mass actions. Can be overridden by an option parameter.
         *
         * @protected
         */
        massActionsDisabled: false,

        /**
         * Disable a portal layout usage. Can be overridden by an option parameter.
         *
         * @protected
         */
        portalLayoutDisabled: false,

        /**
         * Mandatory select attributes. Can be overridden by an option parameter.
         * Attributes to be selected regardless being on a layout.
         *
         * @protected
         * @type {string[]|null}
         */
        mandatorySelectAttributeList: null,

        /**
         * A layout name. If null, a value from `type` property will be used.
         * Can be overridden by an option parameter.
         *
         * @protected
         * @type {string|null}
         */
        layoutName: null,

        /**
         * A scope name for layout loading. If null, an entity type of collection will be used.
         * Can be overridden by an option parameter.
         *
         * @protected
         * @type {string|null}
         */
        layoutScope: null,

        /**
         * To disable field-level access check for a layout.
         * Can be overridden by an option parameter.
         *
         * @protected
         */
        layoutAclDisabled: false,

        /**
         * A setup-handler type.
         *
         * @protected
         */
        setupHandlerType: 'record/list',

        /**
         * @internal
         * @private
         */
        checkboxesDisabled: false,

        /**
         * Where to display the pagination. Can be overridden by an option parameter.
         *
         * @protected
         * @type {'top'|'bottom'|boolean|null}
         */
        pagination: false,

        /**
         * To display a table header with column names. Can be overridden by an option parameter.
         *
         * @protected
         * @type {boolean}
         */
        header: true,

        /**
         * A show-more button. Can be overridden by an option parameter.
         *
         * @protected
         */
        showMore: true,

        /**
         * A mass-action list.
         *
         * @protected
         * @type {string[]}
         */
        massActionList: [
            'remove',
            'merge',
            'massUpdate',
            'export',
        ],

        /**
         * A mass-action list available when selecting all results.
         *
         * @protected
         * @type {string[]}
         */
        checkAllResultMassActionList: [
            'remove',
            'massUpdate',
            'export',
        ],

        /**
         * Disable quick-detail (viewing a record in modal)
         *
         * @protected
         */
        quickDetailDisabled: false,

        /**
         * Disable quick-edit (editing a record in modal)
         *
         * @protected
         */
        quickEditDisabled: false,

        /**
         * A list layout. Can be overridden by an option parameter.
         * If null, then will be loaded from the backend (using the `layoutName` value).
         *
         * @protected
         * @type {Object[]|null}
         */
        listLayout: null,

        /**
         * @private
         */
        _internalLayout: null,

        /**
         * A list of record IDs currently selected. Only for reading.
         *
         * @protected
         * @type {string[]|null}
         */
        checkedList: null,

        /**
         * Whether all results currently selected. Only for reading.
         *
         * @protected
         */
        allResultIsChecked: false,

        /**
         * Disable the ability to select all results. Can be overridden by an option parameter.
         *
         * @protected
         */
        checkAllResultDisabled: false,

        /**
         * Disable buttons. Can be overridden by an option parameter.
         *
         * @protected
         */
        buttonsDisabled: false,

        /**
         * Disable edit. Can be overridden by an option parameter.
         *
         * @protected
         */
        editDisabled: false,

        /**
         * Disable remove. Can be overridden by an option parameter.
         *
         * @protected
         */
        removeDisabled: false,

        /**
         * Disable a stick-bar. Can be overridden by an option parameter.
         *
         * @protected
         */
        stickedBarDisabled: false,

        /**
         * Disable the follow/unfollow mass action.
         *
         * @protected
         */
        massFollowDisabled: false,

        /**
         * Disable the print-pdf mass action.
         *
         * @protected
         */
        massPrintPdfDisabled: false,

        /**
         * Disable the convert-currency mass action.
         *
         * @protected
         */
        massConvertCurrencyDisabled: false,

        /**
         * Disable mass-update.
         *
         * @protected
         */
        massUpdateDisabled: false,

        /**
         * Disable export.
         *
         * @protected
         */
        exportDisabled: false,

        /**
         * Disable merge.
         *
         * @protected
         */
        mergeDisabled: false,

        /**
         * Disable a no-data label (when no result).
         *
         * @protected
         */
        noDataDisabled: false,

        /**
         * @private
         */
        _$focusedCheckbox: null,

        /**
         * @inheritDoc
         */
        events: {
            /**
             * @param {JQueryKeyEventObject} e
             * @this module:views/record/list.Class
             */
            'click a.link': function (e) {
                if (e.ctrlKey || e.metaKey || e.shiftKey) {
                    return;
                }

                e.stopPropagation();

                if (!this.scope || this.selectable) {
                    return;
                }

                e.preventDefault();

                let id = $(e.currentTarget).attr('data-id');
                let model = this.collection.get(id);
                let scope = this.getModelScope(id);

                let options = {
                    id: id,
                    model: model,
                };

                if (this.options.keepCurrentRootUrl) {
                    options.rootUrl = this.getRouter().getCurrentUrl();
                }

                this.getRouter().navigate('#' + scope + '/view/' + id, {trigger: false});
                this.getRouter().dispatch(scope, 'view', options);
            },
            /**
             * @param {JQueryMouseEventObject} e
             * @this module:views/record/list.Class
             */
            'auxclick a.link': function (e) {
                let isCombination = e.button === 1 && (e.ctrlKey || e.metaKey);

                if (!isCombination) {
                    return;
                }

                let $target = $(e.currentTarget);

                let id = $target.attr('data-id');

                if (!id) {
                    return;
                }

                if (this.quickDetailDisabled) {
                    return;
                }

                let $menu = $target.parent().closest(`[data-id="${id}"]`)
                    .find(`ul.list-row-dropdown-menu[data-id="${id}"]`);

                let $quickView = $menu.find(`a[data-action="quickView"]`);

                if ($menu.length && !$quickView.length) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();

                this.actionQuickView({id: id});
            },
            /**
             * @this module:views/record/list.Class
             */
            'click [data-action="showMore"]': function () {
                this.showMoreRecords();
            },
            /**
             * @param {JQueryKeyEventObject} e
             * @this module:views/record/list.Class
             */
            'click a.sort': function (e) {
                let field = $(e.currentTarget).data('name');

                this.toggleSort(field);
            },
            /**
             * @param {JQueryKeyEventObject} e
             * @this module:views/record/list.Class
             */
            'click .pagination a': function (e) {
                let page = $(e.currentTarget).data('page');

                if ($(e.currentTarget).parent().hasClass('disabled')) {
                    return;
                }

                Espo.Ui.notify(this.translate('loading', 'messages'));

                this.collection.once('sync', () => {
                    Espo.Ui.notify(false);
                });

                if (page === 'current') {
                    this.collection.fetch();
                }
                else {
                    this.collection[page + 'Page'].call(this.collection);
                    this.trigger('paginate');
                }

                this.deactivate();
            },
            /**
             * @param {JQueryKeyEventObject} e
             * @this module:views/record/list.Class
             */
            'mousedown input.record-checkbox': function (e) {
                let $focused = $(document.activeElement);

                this._$focusedCheckbox = null;

                if (
                    $focused.length &&
                    $focused.get(0).tagName === 'INPUT' &&
                    $focused.hasClass('record-checkbox')
                ) {
                    this._$focusedCheckbox = $focused;
                }
            },
            /**
             * @param {JQueryKeyEventObject} e
             * @this module:views/record/list.Class
             */
            'click input.record-checkbox': function (e) {
                let $target = $(e.currentTarget);

                let $from = this._$focusedCheckbox;

                if (e.shiftKey && $from) {
                    let $checkboxes = this.$el.find('input.record-checkbox');
                    let start = $checkboxes.index($target);
                    let end = $checkboxes.index($from);
                    let checked = $from.prop('checked');

                    $checkboxes.slice(Math.min(start, end), Math.max(start, end) + 1).each((i, el) => {
                        let $el = $(el);

                        $el.prop('checked', checked);
                        this.checkboxClick($el, checked);
                    });

                    return;
                }

                this.checkboxClick($target, $target.is(':checked'));
            },
            /**
             * @param {JQueryKeyEventObject} e
             * @this module:views/record/list.Class
             */
            'click .select-all': function (e) {
                this.selectAllHandler(e.currentTarget.checked);
            },
            /**
             * @param {JQueryKeyEventObject} e
             * @this module:views/record/list.Class
             */
            'click .action': function (e) {
                Espo.Utils.handleAction(this, e);
            },
            /**
             * @this module:views/record/list.Class
             */
            'click .checkbox-dropdown [data-action="selectAllResult"]': function () {
                this.selectAllResult();
            },
            /**
             * @param {JQueryKeyEventObject} e
             * @this module:views/record/list.Class
             */
            'click .actions-menu a.mass-action': function (e) {
                let $el = $(e.currentTarget);

                let action = $el.data('action');
                let method = 'massAction' + Espo.Utils.upperCaseFirst(action);

                if (method in this) {
                    this[method]();

                    return;
                }

                this.massAction(action);
            },
            /**
             * @this module:views/record/list.Class
             */
            'click a.reset-custom-order': function () {
                this.resetCustomOrder();
            },
        },

        /**
         * @param {JQuery} $checkbox
         * @param {boolean} checked
         * @private
         */
        checkboxClick: function ($checkbox, checked) {
            let id = $checkbox.attr('data-id');

            if (checked) {
                this.checkRecord(id, $checkbox);

                return;
            }

            this.uncheckRecord(id, $checkbox);
        },

        resetCustomOrder: function () {
            this.collection.resetOrderToDefault();
            this.collection.trigger('order-changed');

            this.collection
                .fetch()
                .then(() => {
                    this.trigger('sort', {
                        orderBy: this.collection.orderBy,
                        order: this.collection.order,
                    });
                })
        },

        /**
         * @param {string} orderBy
         * @protected
         */
        toggleSort: function (orderBy) {
            let asc = true;

            if (orderBy === this.collection.orderBy && this.collection.order === 'asc') {
                asc = false;
            }

            let order = asc ? 'asc' : 'desc';

            Espo.Ui.notify(this.translate('loading', 'messages'));

            let maxSizeLimit = this.getConfig().get('recordListMaxSizeLimit') || 200;

            while (this.collection.length > maxSizeLimit) {
                this.collection.pop();
            }

            this.collection
                .sort(orderBy, order)
                .then(() => {
                    Espo.Ui.notify(false);

                    this.trigger('sort', {orderBy: orderBy, order: order});
                })

            this.collection.trigger('order-changed');

            this.deactivate();
        },

        /**
         * @protected
         */
        initStickedBar: function () {
            let $stickedBar = this.$stickedBar = this.$el.find('.sticked-bar');
            let $middle = this.$el.find('> .list');

            let $window = $(window);
            let $scrollable = $window;
            let $navbarRight = $('#navbar .navbar-right');

            this.on('render', () => {
                this.$stickedBar = null;
            });

            let isModal = !!this.$el.closest('.modal-body').length;

            let screenWidthXs = this.getThemeManager().getParam('screenWidthXs');
            let navbarHeight = this.getThemeManager().getParam('navbarHeight');

            let isSmallWindow = $(window.document).width() < screenWidthXs;

            let getOffsetTop = (element) => {
                let offsetTop = 0;

                let withHeader = !isSmallWindow && !isModal;

                do {
                    if (element.classList.contains('modal-body')) {
                        break;
                    }

                    if (!isNaN(element.offsetTop)) {
                        offsetTop += element.offsetTop;
                    }

                    element = element.offsetParent;
                } while (element);

                if (withHeader) {
                    offsetTop -= navbarHeight;
                }

                return offsetTop;
            };

            if (isModal) {
                $scrollable = this.$el.closest('.modal-body');
                $navbarRight = $scrollable.parent().find('.modal-footer');
            }

            let middleTop = getOffsetTop($middle.get(0));
            let buttonsTop =  getOffsetTop(this.$el.find('.list-buttons-container').get(0));

            $scrollable.off('scroll.list-' + this.cid);
            $scrollable.on('scroll.list-' + this.cid, () => controlSticking());

            $window.off('resize.list-' + this.cid);
            $window.on('resize.list-' + this.cid, () => controlSticking());

            this.on('check', () => {
                if (this.checkedList.length === 0 && !this.allResultIsChecked) {
                    return;
                }

                controlSticking();
            });

            this.once('remove', () => {
                $scrollable.off('scroll.list-' + this.cid);
                $window.off('resize.list-' + this.cid);
            });

            let controlSticking = () => {
                if (this.checkedList.length === 0 && !this.allResultIsChecked) {
                    return;
                }

                let scrollTop = $scrollable.scrollTop();

                let stickTop = buttonsTop;
                let edge = middleTop + $middle.outerHeight(true);

                if (isSmallWindow && $('#navbar .navbar-body').hasClass('in')) {
                    return;
                }

                if (scrollTop >= edge) {
                    $stickedBar.removeClass('hidden');
                    $navbarRight.addClass('has-sticked-bar');

                    return;
                }

                if (scrollTop > stickTop) {
                    $stickedBar.removeClass('hidden');
                    $navbarRight.addClass('has-sticked-bar');

                    return;
                }

                $stickedBar.addClass('hidden');
                $navbarRight.removeClass('has-sticked-bar');
            };
        },

        /**
         * @protected
         */
        showActions: function () {
            this.$el.find('.actions-button').removeClass('hidden');

            if (
                !this.options.stickedBarDisabled &&
                !this.stickedBarDisabled &&
                this.massActionList.length
            ) {
                if (!this.$stickedBar) {
                    this.initStickedBar();
                }
            }
        },

        /**
         * @protected
         */
        hideActions: function () {
            this.$el.find('.actions-button').addClass('hidden');

            if (this.$stickedBar) {
                this.$stickedBar.addClass('hidden');
            }
        },

        /**
         * @protected
         */
        selectAllHandler: function (isChecked) {
            this.checkedList = [];

            if (isChecked) {
                this.$el.find('input.record-checkbox').prop('checked', true);

                this.showActions();

                this.collection.models.forEach((model) => {
                    this.checkedList.push(model.id);
                });

                this.$el.find('.list > table tbody tr').addClass('active');
            }
            else {
                if (this.allResultIsChecked) {
                    this.unselectAllResult();
                }

                this.$el.find('input.record-checkbox').prop('checked', false);
                this.hideActions();
                this.$el.find('.list > table tbody tr').removeClass('active');
            }

            this.trigger('check');
        },

        /**
         * @inheritDoc
         */
        data: function () {
            var paginationTop = this.pagination === 'both' ||
                this.pagination === 'top';

            var paginationBottom = this.pagination === 'both' ||
                this.pagination === true ||
                this.pagination === 'bottom';

            var moreCount = this.collection.total - this.collection.length;

            var checkAllResultDisabled = this.checkAllResultDisabled;

            if (!this.massActionsDisabled) {
                if (!this.checkAllResultMassActionList.length) {
                    checkAllResultDisabled = true;
                }
            }

            var topBar =
                paginationTop ||
                this.checkboxes ||
                (this.buttonList.length && !this.buttonsDisabled) ||
                (this.dropdownItemList.length && !this.buttonsDisabled) ||
                this.forceDisplayTopBar;

            return {
                scope: this.scope,
                entityType: this.entityType,
                header: this.header,
                headerDefs: this._getHeaderDefs(),
                paginationEnabled: this.pagination,
                paginationTop: paginationTop,
                paginationBottom: paginationBottom,
                showMoreActive: this.collection.total > this.collection.length ||
                    this.collection.total === -1,
                showMoreEnabled: this.showMore,
                showCount: this.showCount && this.collection.total > 0,
                moreCount: moreCount,
                checkboxes: this.checkboxes,
                massActionList: this.massActionList,
                rowList: this.rowList,
                topBar: topBar,
                bottomBar: paginationBottom,
                checkAllResultDisabled: checkAllResultDisabled,
                buttonList: this.buttonList,
                dropdownItemList: this.dropdownItemList,
                displayTotalCount: this.displayTotalCount && this.collection.total > 0,
                displayActionsButtonGroup: this.checkboxes ||
                    this.massActionList || this.buttonList.length || this.dropdownItemList.length,
                totalCountFormatted: this.getNumberUtil().formatInt(this.collection.total),
                moreCountFormatted: this.getNumberUtil().formatInt(moreCount),
                checkboxColumnWidth: this.checkboxColumnWidth,
                noDataDisabled: this.noDataDisabled,
            };
        },

        /**
         * @inheritDoc
         */
        init: function () {
            this.type = this.options.type || this.type;
            this.listLayout = this.options.listLayout || this.listLayout;
            this.layoutName = this.options.layoutName || this.layoutName || this.type;
            this.layoutScope = this.options.layoutScope || this.layoutScope;
            this.layoutAclDisabled = this.options.layoutAclDisabled || this.layoutAclDisabled;
            this.headerDisabled = this.options.headerDisabled || this.headerDisabled;
            this.noDataDisabled = this.options.noDataDisabled || this.noDataDisabled;

            if (!this.headerDisabled) {
                this.header = _.isUndefined(this.options.header) ? this.header : this.options.header;
            } else {
                this.header = false;
            }

            this.pagination = _.isUndefined(this.options.pagination) || this.options.pagination === null ?
                this.pagination :
                this.options.pagination;

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

            this.dropdownItemList = Espo.Utils.cloneDeep(
                this.options.dropdownItemList || this.dropdownItemList);

            if ('buttonsDisabled' in this.options) {
                this.buttonsDisabled = this.options.buttonsDisabled;
            }

            if ('checkAllResultDisabled' in this.options) {
                this.checkAllResultDisabled = this.options.checkAllResultDisabled;
            }
        },

        /**
         * Get a record entity type (scope).
         *
         * @param {string} id A record ID.
         * @return {string}
         */
        getModelScope: function (id) {
            return this.scope;
        },

        /**
         * Select all results.
         */
        selectAllResult: function () {
            this.allResultIsChecked = true;

            this.hideActions();

            this.$el.find('input.record-checkbox').prop('checked', true).attr('disabled', 'disabled');
            this.$el.find('input.select-all').prop('checked', true);

            this.massActionList.forEach(item => {
                if (!~this.checkAllResultMassActionList.indexOf(item)) {
                    this.$el
                        .find(
                            'div.list-buttons-container .actions-menu li a.mass-action[data-action="'+item+'"]'
                        )
                        .parent()
                        .addClass('hidden');
                }
            });

            if (this.checkAllResultMassActionList.length) {
                this.showActions();
            }

            this.$el.find('.list > table tbody tr').removeClass('active');

            this.trigger('select-all-results');
        },

        /**
         * Unselect all results.
         */
        unselectAllResult: function () {
            this.allResultIsChecked = false;

            this.$el.find('input.record-checkbox').prop('checked', false).removeAttr('disabled');
            this.$el.find('input.select-all').prop('checked', false);

            this.massActionList.forEach(item => {
                if (!~this.checkAllResultMassActionList.indexOf(item)) {
                    this.$el
                        .find('div.list-buttons-container .actions-menu ' +
                            'li a.mass-action[data-action="'+item+'"]')
                        .parent()
                        .removeClass('hidden');
                }
            });
        },

        /**
         * @protected
         */
        deactivate: function () {
            if (this.$el) {
                this.$el.find(".pagination li").addClass('disabled');
                this.$el.find("a.sort").addClass('disabled');
            }
        },

        /**
         * Process export.
         *
         * @param {Object<string,*>} [data]
         * @param {string} [url='Export'] An API URL.
         * @param {string[]} [fieldList] A field list.
         */
        export: function (data, url, fieldList) {
            if (!data) {
                data = {
                    entityType: this.entityType,
                };

                if (this.allResultIsChecked) {
                    data.where = this.collection.getWhere();
                    data.searchParams = this.collection.data || null;
                    data.searchData = this.collection.data || {}; // for bc;
                }
                else {
                    data.ids = this.checkedList;
                }
            }

            url = url || 'Export';

            var o = {
                scope: this.entityType
            };

            if (fieldList) {
                o.fieldList = fieldList;
            }
            else {
                var layoutFieldList = [];

                (this.listLayout || []).forEach((item) => {
                    if (item.name) {
                        layoutFieldList.push(item.name);
                    }
                });

                o.fieldList = layoutFieldList;
            }

            let helper = new ExportHelper(this);
            let idle = this.allResultIsChecked && helper.checkIsIdle(this.collection.total);

            let proceedDownload = (attachmentId) => {
                window.location = this.getBasePath() + '?entryPoint=download&id=' + attachmentId;
            };

            this.createView('dialogExport', 'views/export/modals/export', o, (view) => {
                view.render();

                this.listenToOnce(view, 'proceed', (dialogData) => {
                    if (!dialogData.exportAllFields) {
                        data.attributeList = dialogData.attributeList;
                        data.fieldList = dialogData.fieldList;
                    }

                    data.idle = idle;
                    data.format = dialogData.format;

                    Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

                    Espo.Ajax
                        .postRequest(url, data, {timeout: 0})
                        .then(response => {
                            Espo.Ui.notify(false);

                            if (response.exportId) {
                                helper
                                    .process(response.exportId)
                                    .then(view => {
                                        this.listenToOnce(view, 'download', id => {
                                            proceedDownload(id);
                                        });
                                    });

                                return;
                            }

                            if (!response.id) {
                                throw new Error("No attachment-id.");
                            }

                            window.location = this.getBasePath() + '?entryPoint=download&id=' + response.id;

                            proceedDownload(response.id);
                        });
                });
            });
        },

        /**
         * Process a mass-action.
         *
         * @param {string} name An action.
         */
        massAction: function (name) {
            var defs = this.getMetadata().get(['clientDefs', this.scope, 'massActionDefs', name]) || {};

            var handler = defs.handler;

            if (handler) {
                var method = 'action' + Espo.Utils.upperCaseFirst(name);

                var data = {
                    entityType: this.entityType,
                    action: name,
                    params: this.getMassActionSelectionPostData(),
                };

                require(handler, (Handler) => {
                    var handler = new Handler(this);

                    handler[method].call(handler, data);
                });

                return;
            }

            var bypassConfirmation = defs.bypassConfirmation || false;
            var confirmationMsg = defs.confirmationMessage || 'confirmation';
            var acl = defs.acl;
            var aclScope = defs.aclScope;

            var proceed = function () {
                if (acl || aclScope) {
                    if (!this.getAcl().check(aclScope || this.scope, acl)) {
                        this.notify('Access denied', 'error');

                        return;
                    }
                }

                var idList = [];
                var data = {};

                if (this.allResultIsChecked) {
                    data.where = this.collection.getWhere();
                    data.searchParams =  this.collection.data || {};
                    data.selectData = data.searchData; // for bc;
                    data.byWhere = true; // for bc
                }
                else {
                    data.idList = idList; // for bc
                    data.ids = idList;
                }

                for (var i in this.checkedList) {
                    idList.push(this.checkedList[i]);
                }

                data.entityType = this.entityType;

                var waitMessage = this.getMetadata().get(
                    ['clientDefs', this.scope, 'massActionDefs', name, 'waitMessage']
                ) || 'pleaseWait';

                Espo.Ui.notify(this.translate(waitMessage, 'messages', this.scope));

                var url = this.getMetadata().get(['clientDefs', this.scope, 'massActionDefs', name, 'url']);

                this.ajaxPostRequest(url, data)
                    .then((result)=> {
                        var successMessage = result.successMessage ||
                            this.getMetadata().get(
                            ['clientDefs', this.scope, 'massActionDefs', name, 'successMessage']
                        ) || 'done';

                        this.collection
                            .fetch()
                            .then(() => {
                                var message = this.translate(successMessage, 'messages', this.scope);

                                if ('count' in result) {
                                    message = message.replace('{count}', result.count);
                                }

                                Espo.Ui.success(message);
                            });
                    });
            };

            if (!bypassConfirmation) {
                this.confirm(this.translate(confirmationMsg, 'messages', this.scope), proceed, this);
            }
            else {
                proceed.call(this);
            }
        },

        getMassActionSelectionPostData: function () {
            var data = {};

            if (this.allResultIsChecked) {
                data.where = this.collection.getWhere();
                data.searchParams = this.collection.data || {};
                data.selectData = this.collection.data || {}; // for bc;
                data.byWhere = true; // for bc;
            }
            else {
                data.ids = [];

                for (var i in this.checkedList) {
                    data.ids.push(this.checkedList[i]);
                }
            }

            return data;
        },

        massActionRecalculateFormula: function () {
            var ids = false;

            var allResultIsChecked = this.allResultIsChecked;

            if (!allResultIsChecked) {
                ids = this.checkedList;
            }

            this.confirm({
                message: this.translate('recalculateFormulaConfirmation', 'messages'),
                confirmText: this.translate('Yes'),
            }, () => {
                Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

                let params = this.getMassActionSelectionPostData();
                let helper = new MassActionHelper(this);
                let idle = !!params.searchParams && helper.checkIsIdle(this.collection.total);

                Espo.Ajax.postRequest('MassAction', {
                    entityType: this.entityType,
                    action: 'recalculateFormula',
                    params: params,
                    idle: idle,
                })
                    .then((result) => {
                        result = result || {};

                        let final = () => {
                            this.collection
                                .fetch()
                                .then(() => {
                                    Espo.Ui.success(this.translate('Done'));

                                    if (allResultIsChecked) {
                                        this.selectAllResult();

                                        return;
                                    }

                                    ids.forEach((id) => {
                                        this.checkRecord(id);
                                    });
                                });
                        };

                        if (result.id) {
                            helper
                                .process(result.id, 'recalculateFormula')
                                .then(view => {
                                    this.listenToOnce(view, 'close:success', () => final());
                                });

                            return;
                        }

                        final();
                    });
            });
        },

        massActionRemove: function () {
            if (!this.getAcl().check(this.entityType, 'delete')) {
                this.notify('Access denied', 'error');

                return false;
            }

            this.confirm({
                message: this.translate('removeSelectedRecordsConfirmation', 'messages', this.scope),
                confirmText: this.translate('Remove'),
            }, () => {
                this.notify('Removing...');

                let helper = new MassActionHelper(this);

                let params = this.getMassActionSelectionPostData();

                let idle = !!params.searchParams && helper.checkIsIdle(this.collection.total);

                Espo.Ajax.postRequest('MassAction', {
                    entityType: this.entityType,
                    action: 'delete',
                    params: params,
                    idle: idle,
                })
                .then(result => {
                    result = result || {};

                    let afterAllResult = (count) => {
                        if (!count) {
                            Espo.Ui.warning(this.translate('noRecordsRemoved', 'messages'));

                            return;
                        }

                        this.unselectAllResult();

                        this.listenToOnce(this.collection, 'sync', () => {
                            var msg = 'massRemoveResult';

                            if (count === 1) {
                                msg = 'massRemoveResultSingle';
                            }

                            Espo.Ui.success(this.translate(msg, 'messages').replace('{count}', count));
                        });

                        this.collection.fetch();

                        Espo.Ui.notify(false);
                    };

                    if (result.id) {
                        helper
                            .process(result.id, 'delete')
                            .then(view => {
                                this.listenToOnce(view, 'close:success', result =>
                                    afterAllResult(result.count));
                            });

                        return;
                    }

                    var count = result.count;

                    if (this.allResultIsChecked) {
                        afterAllResult(count);

                        return;
                    }

                    var idsRemoved = result.ids || [];

                    if (!count) {
                        Espo.Ui.warning(this.translate('noRecordsRemoved', 'messages'));

                        return;
                    }

                    idsRemoved.forEach((id) => {
                        Espo.Ui.notify(false);

                        this.collection.trigger('model-removing', id);
                        this.removeRecordFromList(id);
                        this.uncheckRecord(id, null, true);
                    });

                    var msg = 'massRemoveResult';

                    if (count === 1) {
                        msg = 'massRemoveResultSingle';
                    }

                    Espo.Ui.success(this.translate(msg, 'messages').replace('{count}', count));
                });
            });
        },

        massActionPrintPdf: function () {
            var maxCount = this.getConfig().get('massPrintPdfMaxCount');

            if (maxCount) {
                if (this.checkedList.length > maxCount) {
                    var msg = this.translate('massPrintPdfMaxCountError', 'messages')
                        .replace('{maxCount}', maxCount.toString());

                    Espo.Ui.error(msg);

                    return;
                }
            }

            var idList = [];

            for (var i in this.checkedList) {
                idList.push(this.checkedList[i]);
            }

            this.createView('pdfTemplate', 'views/modals/select-template', {
                entityType: this.entityType,
            }, (view) => {
                view.render();

                this.listenToOnce(view, 'select', (templateModel) => {
                    this.clearView('pdfTemplate');

                    Espo.Ui.notify(this.translate('loading', 'messages'));

                    this.ajaxPostRequest(
                            'Pdf/action/massPrint',
                            {
                                idList: idList,
                                entityType: this.entityType,
                                templateId: templateModel.id,
                            },
                            {timeout: 0}
                        )
                        .then((result) => {
                            Espo.Ui.notify(false);

                            window.open('?entryPoint=download&id=' + result.id, '_blank');
                        });
                });
            });
        },

        massActionFollow: function () {
            var count = this.checkedList.length;

            var confirmMsg = this.translate('confirmMassFollow', 'messages')
                .replace('{count}', count.toString());

            this.confirm({
                message: confirmMsg,
                confirmText: this.translate('Follow'),
            }, () => {
                Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

                this.ajaxPostRequest('MassAction', {
                    action: 'follow',
                    entityType: this.entityType,
                    params: this.getMassActionSelectionPostData(),
                })
                    .then((result) => {
                            var resultCount = result.count || 0;

                            var msg = 'massFollowResult';

                            if (resultCount) {
                                if (resultCount === 1) {
                                    msg += 'Single';
                                }

                                Espo.Ui.success(
                                    this.translate(msg, 'messages').replace('{count}', resultCount)
                                );
                            }
                            else {
                                Espo.Ui.warning(
                                    this.translate('massFollowZeroResult', 'messages')
                                );
                            }
                    });
            });
        },

        massActionUnfollow: function () {
            var count = this.checkedList.length;

            var confirmMsg = this.translate('confirmMassUnfollow', 'messages')
                .replace('{count}', count.toString());

            this.confirm({
                message: confirmMsg,
                confirmText: this.translate('Unfollow'),
            }, () => {
                Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

                let params = this.getMassActionSelectionPostData();
                let helper = new MassActionHelper(this);
                let idle = !!params.searchParams && helper.checkIsIdle(this.collection.total);

                this.ajaxPostRequest('MassAction', {
                    action: 'unfollow',
                    entityType: this.entityType,
                    params: params,
                    idle: idle,
                })
                    .then(result => {
                        let final = (count) => {
                            var msg = 'massUnfollowResult';

                            if (!count) {
                                Espo.Ui.warning(
                                    this.translate('massUnfollowZeroResult', 'messages')
                                );
                            }

                            if (count === 1) {
                                msg += 'Single';
                            }

                            Espo.Ui.success(
                                this.translate(msg, 'messages').replace('{count}', count)
                            );
                        };

                        if (result.id) {
                            helper
                                .process(result.id, 'unfollow')
                                .then(view => {
                                    this.listenToOnce(view, 'close:success', result => final(result.count));
                                });

                            return;
                        }

                        final(result.count || 0);
                    });
            });
        },

        massActionMerge: function () {
            if (!this.getAcl().check(this.entityType, 'edit')) {
                this.notify('Access denied', 'error');

                return false;
            }

            if (this.checkedList.length < 2) {
                this.notify('Select 2 or more records', 'error');

                return;
            }
            if (this.checkedList.length > 4) {
                this.notify('Select not more than 4 records', 'error');

                return;
            }
            this.checkedList.sort();

            var url = '#' + this.entityType + '/merge/ids=' + this.checkedList.join(',');

            this.getRouter().navigate(url, {trigger: false});

            this.getRouter().dispatch(this.entityType, 'merge', {
                ids: this.checkedList.join(','),
                collection: this.collection,
            });
        },

        massActionMassUpdate: function () {
            if (!this.getAcl().check(this.entityType, 'edit')) {
                this.notify('Access denied', 'error');

                return false;
            }

            Espo.Ui.notify(this.translate('loading', 'messages'));

            var ids = false;

            var allResultIsChecked = this.allResultIsChecked;

            if (!allResultIsChecked) {
                ids = this.checkedList;
            }

            var viewName = this.getMetadata()
                    .get(['clientDefs', this.entityType, 'modalViews', 'massUpdate']) ||
                'views/modals/mass-update';

            this.createView('massUpdate', viewName, {
                scope: this.scope,
                entityType: this.entityType,
                ids: ids,
                where: this.collection.getWhere(),
                searchParams: this.collection.data,
                byWhere: this.allResultIsChecked,
                totalCount: this.collection.total,
            }, (view) => {
                view.render();

                view.notify(false);

                this.listenToOnce(view, 'after:update', (o) => {
                    if (o.idle) {
                        this.listenToOnce(view, 'close', () => {
                            this.collection
                                .fetch()
                                .then(() => {
                                    if (allResultIsChecked) {
                                        this.selectAllResult();

                                        return;
                                    }

                                    ids.forEach((id) => {
                                        this.checkRecord(id);
                                    });
                                });
                        });

                        return;
                    }

                    view.close();

                    let count = o.count;

                    this.collection
                        .fetch()
                        .then(() => {
                            if (count) {
                                var msg = 'massUpdateResult';

                                if (count === 1) {
                                    msg = 'massUpdateResultSingle';
                                }

                                Espo.Ui.success(this.translate(msg, 'messages').replace('{count}', count));
                            }
                            else {
                                Espo.Ui.warning(this.translate('noRecordsUpdated', 'messages'));
                            }

                            if (allResultIsChecked) {
                                this.selectAllResult();

                                return;
                            }

                            ids.forEach((id) => {
                                this.checkRecord(id);
                            });
                        });
                });
            });
        },

        massActionExport: function () {
            if (!this.getConfig().get('exportDisabled') || this.getUser().isAdmin()) {
                this.export();
            }
        },

        massActionUnlink: function () {
            this.confirm({
                message: this.translate('unlinkSelectedRecordsConfirmation', 'messages'),
                confirmText: this.translate('Unlink'),
            }, () => {
                this.notify('Unlinking...');

                Espo.Ajax.deleteRequest(this.collection.url, {
                    ids: this.checkedList,
                }).then(() => {
                    this.notify('Unlinked', 'success');

                    this.collection.fetch();

                    this.model.trigger('after:unrelate');
                });
            });
        },

        massActionConvertCurrency: function () {
            var ids = false;

            var allResultIsChecked = this.allResultIsChecked;

            if (!allResultIsChecked) {
                ids = this.checkedList;
            }

            this.createView('modalConvertCurrency', 'views/modals/mass-convert-currency', {
                entityType: this.entityType,
                ids: ids,
                where: this.collection.getWhere(),
                searchParams: this.collection.data,
                byWhere: this.allResultIsChecked,
                totalCount: this.collection.total,
            }, (view) => {
                view.render();

                this.listenToOnce(view, 'after:update', o => {
                    if (o.idle) {
                        this.listenToOnce(view, 'close', () => {
                            this.collection
                                .fetch()
                                .then(() => {
                                    if (allResultIsChecked) {
                                        this.selectAllResult();

                                        return;
                                    }

                                    ids.forEach((id) => {
                                        this.checkRecord(id);
                                    });
                                });
                        });

                        return;
                    }

                    let count = o.count;

                    this.collection
                        .fetch()
                        .then(() => {
                            if (count) {
                                var msg = 'massUpdateResult';

                                if (count === 1) {
                                    msg = 'massUpdateResultSingle';
                                }

                                Espo.Ui.success(this.translate(msg, 'messages').replace('{count}', count));
                            }
                            else {
                                Espo.Ui.warning(this.translate('noRecordsUpdated', 'messages'));
                            }

                            if (allResultIsChecked) {
                                this.selectAllResult();

                                return;
                            }

                            ids.forEach((id) => {
                                this.checkRecord(id);
                            });
                        });
                });
            });
        },

        removeMassAction: function (item) {
            let index = this.massActionList.indexOf(item);

            if (~index) {
                this.massActionList.splice(index, 1);
            }

            index = this.checkAllResultMassActionList.indexOf(item);

            if (~index) {
                this.checkAllResultMassActionList.splice(index, 1);
            }
        },

        addMassAction: function (item, allResult, toBeginning) {
            let method = toBeginning ? 'unshift' : 'push';

            this.massActionList[method](item);

            if (allResult) {
                this.checkAllResultMassActionList[method](item);
            }

            if (!this.checkboxesDisabled) {
                this.checkboxes = true;
            }
        },

        removeAllResultMassAction: function (item) {
            let index = this.checkAllResultMassActionList.indexOf(item);

            if (~index) {
                this.checkAllResultMassActionList.splice(index, 1);
            }
        },

        /**
         * @inheritDoc
         */
        setup: function () {
            if (typeof this.collection === 'undefined') {
                throw new Error('Collection has not been injected into views/record/list view.');
            }

            this.layoutLoadCallbackList = [];

            this.entityType = this.collection.name || null;
            this.scope = this.options.scope || this.entityType;

            this.massActionList = Espo.Utils.clone(this.massActionList);
            this.buttonList = Espo.Utils.clone(this.buttonList);

            this.mandatorySelectAttributeList = Espo.Utils.clone(
                this.options.mandatorySelectAttributeList || this.mandatorySelectAttributeList || []
            );

            this.editDisabled = this.options.editDisabled || this.editDisabled ||
                this.getMetadata().get(['clientDefs', this.scope, 'editDisabled']);

            this.removeDisabled = this.options.removeDisabled || this.removeDisabled ||
                this.getMetadata().get(['clientDefs', this.scope, 'removeDisabled']);

            if (!this.getAcl().checkScope(this.entityType, 'delete')) {
                this.removeMassAction('remove');
                this.removeMassAction('merge');
            }

            if (this.removeDisabled) {
                this.removeMassAction('remove');
            }

            if (!this.getAcl().checkScope(this.entityType, 'edit')) {
                this.removeMassAction('massUpdate');
                this.removeMassAction('merge');
            }

            if (
                this.getMetadata().get(['clientDefs', this.scope, 'mergeDisabled']) ||
                this.mergeDisabled
            ) {
                this.removeMassAction('merge');
            }

            let metadataMassActionList = (
                    this.getMetadata().get(['clientDefs', this.scope, 'massActionList']) || []
                ).concat(
                    this.getMetadata().get(['clientDefs', 'Global', 'massActionList']) || []
                );

            metadataMassActionList.forEach(item => {
                var defs = this.getMetadata().get(['clientDefs', this.scope, 'massActionDefs', item]) || {};

                if (!Espo.Utils.checkActionAvailability(this.getHelper(), defs)) {
                    return;
                }

                if (!Espo.Utils.checkActionAccess(this.getAcl(), null, defs)) {
                    return;
                }

                this.massActionList.push(item);
            });

            let checkAllResultMassActionList = [];

            this.checkAllResultMassActionList.forEach(item => {
                if (~this.massActionList.indexOf(item)) {
                    checkAllResultMassActionList.push(item);
                }
            });

            this.checkAllResultMassActionList = checkAllResultMassActionList;

            let metadataCheckkAllMassActionList = (
                    this.getMetadata().get(['clientDefs', this.scope, 'checkAllResultMassActionList']) || []
                ).concat(
                    this.getMetadata().get(['clientDefs', 'Global', 'checkAllResultMassActionList']) || []
                );

            metadataCheckkAllMassActionList.forEach(item => {
                if (this.collection.url !== this.entityType) {
                    return;
                }

                if (~this.massActionList.indexOf(item)) {
                    let defs = this.getMetadata()
                        .get(['clientDefs', this.scope, 'massActionDefs', item]) || {};

                    if (!Espo.Utils.checkActionAvailability(this.getHelper(), defs)) {
                        return;
                    }

                    if (!Espo.Utils.checkActionAccess(this.getAcl(), null, defs)) {
                        return;
                    }

                    this.checkAllResultMassActionList.push(item);
                }
            });

            metadataMassActionList
                .concat(metadataCheckkAllMassActionList)
                .forEach(action => {
                    let defs = this.getMetadata()
                           .get(['clientDefs', this.scope, 'massActionDefs', action]) || {};

                    if (!defs.initFunction || !defs.handler) {
                        return;
                    }

                    var viewObject = this;

                    this.wait(
                        new Promise((resolve) => {
                            require(defs.handler, (Handler) => {
                                var handler = new Handler(viewObject);

                                handler[defs.initFunction].call(handler);

                                resolve();
                            });
                        })
                    );
                });

            if (
                this.getConfig().get('exportDisabled') && !this.getUser().isAdmin() ||
                this.getAcl().get('exportPermission') === 'no' ||
                this.getMetadata().get(['clientDefs', this.scope, 'exportDisabled']) ||
                this.exportDisabled
            ) {
                this.removeMassAction('export');
            }

            if (
                this.getAcl().get('massUpdatePermission') !== 'yes' ||
                this.editDisabled ||
                this.massUpdateDisabled ||
                this.getMetadata().get(['clientDefs', this.scope, 'massUpdateDisabled'])
            ) {
                this.removeMassAction('massUpdate');
            }

            if (
                !this.massFollowDisabled &&
                this.getMetadata().get(['scopes', this.entityType, 'stream']) &&
                this.getAcl().check(this.entityType, 'stream') ||
                this.getMetadata().get(['clientDefs', this.scope, 'massFollowDisabled'])
            ) {
                this.addMassAction('follow');
                this.addMassAction('unfollow', true);
            }

            if (
                !this.massPrintPdfDisabled &&
                ~(this.getHelper().getAppParam('templateEntityTypeList') || []).indexOf(this.entityType)
            ) {
                this.addMassAction('printPdf');
            }

            if (this.options.unlinkMassAction && this.collection) {
                this.addMassAction('unlink', false, true);
            }

            if (
                !this.massConvertCurrencyDisabled &&
                !this.getMetadata().get(['clientDefs', this.scope, 'convertCurrencyDisabled']) &&
                this.getConfig().get('currencyList').length > 1 &&
                this.getAcl().checkScope(this.scope, 'edit') &&
                this.getAcl().get('massUpdatePermission') === 'yes'
            ) {
                let currencyFieldList = this.getFieldManager().getEntityTypeFieldList(this.entityType, {
                    type: 'currency',
                    acl: 'edit',
                });

                if (currencyFieldList.length)
                    this.addMassAction('convertCurrency', true);
            }

            this.setupMassActionItems();

            if (this.getUser().isAdmin()) {
                if (this.getMetadata().get(['formula', this.entityType, 'beforeSaveCustomScript'])) {
                    this.addMassAction('recalculateFormula', true);
                }
            }

            if (this.collection.url !== this.entityType) {
                Espo.Utils.clone(this.checkAllResultMassActionList).forEach((item) => {
                    this.removeAllResultMassAction(item);
                });
            }

            if (this.forcedCheckAllResultMassActionList) {
                this.checkAllResultMassActionList = this.forcedCheckAllResultMassActionList;
            }

            if (this.getAcl().get('massUpdatePermission') !== 'yes') {
                this.removeAllResultMassAction('remove');
            }

            Espo.Utils.clone(this.massActionList).forEach((item) => {
                var propName = 'massAction' + Espo.Utils.upperCaseFirst(item) + 'Disabled';

                if (this[propName] || this.options[propName]) {
                    this.removeMassAction(item);
                }
            });

            if (this.selectable) {
                this.events['click .list a.link'] = (e) => {
                    e.preventDefault();

                    var id = $(e.target).attr('data-id');

                    if (id) {
                        var model = this.collection.get(id);

                        if (this.checkboxes) {
                            var list = [];

                            list.push(model);

                            this.trigger('select', list);
                        }
                        else {
                            this.trigger('select', model);
                        }
                    }

                    e.stopPropagation();
                };
            }

            if ('showCount' in this.options) {
                this.showCount = this.options.showCount;
            }

            this.displayTotalCount = this.showCount && this.getConfig().get('displayListViewRecordCount');

            if ('displayTotalCount' in this.options) {
                this.displayTotalCount = this.options.displayTotalCount;
            }

            this.forceDisplayTopBar = this.options.forceDisplayTopBar || this.forceDisplayTopBar;

            if (this.massActionsDisabled) {
                this.massActionList = [];
            }

            if (!this.massActionList.length && !this.selectable) {
                this.checkboxes = false;
            }

            if (this.getUser().isPortal() && !this.portalLayoutDisabled) {
                if (this.getMetadata().get(
                    ['clientDefs', this.scope, 'additionalLayouts', this.layoutName + 'Portal'])
                ) {
                    this.layoutName += 'Portal';
                }
            }

            this.getHelper().processSetupHandlers(this, this.setupHandlerType);

            this.listenTo(this.collection, 'sync', (c, r, options) => {
                if (this.hasView('modal') && this.getView('modal').isRendered()) {
                    return;
                }

                options = options || {};

                if (options.previousDataList) {
                    let currentDataList = this.collection.models.map(model => {
                        return Espo.Utils.cloneDeep(model.attributes);
                    });

                    if (_.isEqual(currentDataList, options.previousDataList)) {
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

                this.buildRows(() => {
                    this.render();
                });
            });

            this.checkedList = [];

            if (!this.options.skipBuildRows) {
                this.buildRows();
            }
        },

        afterRender: function () {
            if (this.allResultIsChecked) {
                this.selectAllResult();
            }
            else {
                if (this.checkedList.length) {
                    this.checkedList.forEach((id) => {
                        this.checkRecord(id);
                    });
                }
            }
        },

        /**
         * @protected
         */
        setupMassActionItems: function () {},

        /**
         * @protected
         */
        filterListLayout: function (listLayout) {
            if (this._cachedFilteredListLayout) {
                return this._cachedFilteredListLayout;
            }

            var forbiddenFieldList = this._cachedScopeForbiddenFieldList =
                this._cachedScopeForbiddenFieldList ||
                this.getAcl().getScopeForbiddenFieldList(this.entityType, 'read');

            if (this.layoutAclDisabled) {
                forbiddenFieldList = [];
            }

            if (!forbiddenFieldList.length) {
                this._cachedFilteredListLayout = listLayout;

                return this._cachedFilteredListLayout;
            }

            var filteredListLayout = Espo.Utils.clone(listLayout);

            for (var i in listLayout) {
                var name = listLayout[i].name;

                if (name && ~forbiddenFieldList.indexOf(name)) {
                    filteredListLayout[i].customLabel = '';
                    filteredListLayout[i].notSortable = true;
                }
            }

            this._cachedFilteredListLayout = filteredListLayout;

            return this._cachedFilteredListLayout;
        },

        /**
         * @protected
         * @param {function(Object[]):void} callback A callback.
         * @private
         */
        _loadListLayout: function (callback) {
            this.layoutLoadCallbackList.push(callback);

            if (this.layoutIsBeingLoaded) return;

            this.layoutIsBeingLoaded = true;

            var layoutName = this.layoutName;

            var layoutScope = this.layoutScope || this.collection.name;

            this._helper.layoutManager.get(layoutScope, layoutName, (listLayout) => {
                var filteredListLayout = this.filterListLayout(listLayout);

                this.layoutLoadCallbackList.forEach((callbackItem) => {
                    callbackItem(filteredListLayout);

                    this.layoutLoadCallbackList = [];

                    this.layoutIsBeingLoaded = false;
                });
            });
        },

        /**
         * Get a select-attribute list.
         *
         * @param {function(string[]):void} callback A callback.
         */
        getSelectAttributeList: function (callback) {
            if (this.scope === null || this.rowHasOwnLayout) {
                callback(null);

                return;
            }

            if (this.listLayout) {
                var attributeList = this.fetchAttributeListFromLayout();

                callback(attributeList);

                return;
            }

            this._loadListLayout((listLayout) => {
                this.listLayout = listLayout;

                let attributeList = this.fetchAttributeListFromLayout();

                if (this.mandatorySelectAttributeList) {
                    attributeList = attributeList.concat(this.mandatorySelectAttributeList);
                }

                callback(attributeList);
            });
        },

        /**
         * @protected
         * @return {Object[]}
         */
        fetchAttributeListFromLayout: function () {
            var list = [];

            this.listLayout.forEach((item) => {
                if (!item.name) {
                    return;
                }

                var field = item.name;

                var fieldType = this.getMetadata().get(['entityDefs', this.scope, 'fields', field, 'type']);

                if (!fieldType) {
                    return;
                }

                this.getFieldManager()
                    .getEntityTypeFieldAttributeList(this.scope, field)
                    .forEach((attribute) => {
                        list.push(attribute);
                    });
            });

            return list;
        },

        /**
         * @protected
         */
        _getHeaderDefs: function () {
            var defs = [];

            for (var i in this.listLayout) {
                var width = false;

                if ('width' in this.listLayout[i] && this.listLayout[i].width !== null) {
                    width = this.listLayout[i].width + '%';
                }
                else if ('widthPx' in this.listLayout[i]) {
                    width = this.listLayout[i].widthPx;
                }

                var itemName = this.listLayout[i].name;

                var item = {
                    name: itemName,
                    isSortable: !(this.listLayout[i].notSortable || false),
                    width: width,
                    align: ('align' in this.listLayout[i]) ? this.listLayout[i].align : false,
                };

                if ('customLabel' in this.listLayout[i]) {
                    item.customLabel = this.listLayout[i].customLabel;
                    item.hasCustomLabel = true;
                    item.label = item.customLabel;
                }
                else {
                    item.label = this.translate(itemName, 'fields', this.collection.entityType);
                }

                if (this.listLayout[i].noLabel) {
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

            let isCustomSorted =
                this.collection.orderBy !== this.collection.defaultOrderBy ||
                this.collection.order !== this.collection.defaultOrder;

            if (this.rowActionsView && !this.rowActionsDisabled || isCustomSorted) {
                let html = null;

                if (isCustomSorted) {
                    html =
                        $('<a>')
                            .attr('role', 'button')
                            .attr('tabindex', '0')
                            .addClass('reset-custom-order')
                            .attr('title', this.translate('Reset'))
                            .append(
                                $('<span>').addClass('fas fa-times fa-sm')
                            )
                            .get(0).outerHTML
                }

                defs.push({
                    width: this.rowActionsColumnWidth,
                    html: html,
                    className: 'action-cell',
                });
            }

            return defs;
        },

        /**
         * @protected
         */
        _convertLayout: function (listLayout, model) {
            model = model || this.collection.model.prototype;

            var layout = [];

            if (this.checkboxes) {
                layout.push({
                    name: 'r-checkboxField',
                    columnName: 'r-checkbox',
                    template: 'record/list-checkbox'
                });
            }

            for (var i in listLayout) {
                var col = listLayout[i];
                var type = col.type || model.getFieldType(col.name) || 'base';

                if (!col.name) {
                    continue;
                }

                var item = {
                    columnName: col.name,
                    name: col.name + 'Field',
                    view: col.view ||
                        model.getFieldParam(col.name, 'view') ||
                        this.getFieldManager().getViewName(type),
                    options: {
                        defs: {
                            name: col.name,
                            params: col.params || {}
                        },
                        mode: 'list'
                    }
                };

                if (col.width) {
                    item.options.defs.width = col.width;
                }

                if (col.widthPx) {
                    item.options.defs.widthPx = col.widthPx;
                }

                if (col.link) {
                    item.options.mode = 'listLink';
                }
                if (col.align) {
                    item.options.defs.align = col.align;
                }

                layout.push(item);
            }
            if (this.rowActionsView && !this.rowActionsDisabled) {
                layout.push(this.getRowActionsDefs());
            }

            return layout;
        },

        /**
         * Select a record.
         *
         * @param {string} id An ID.
         * @param {JQuery} [$target]
         * @param {boolean} [isSilent] Do not trigger the `check` event.
         */
        checkRecord: function (id, $target, isSilent) {
            $target = $target || this.$el.find('.record-checkbox[data-id="' + id + '"]');

            if (!$target.length) {
                return;
            }

            $target.get(0).checked = true;

            var index = this.checkedList.indexOf(id);

            if (index === -1) {
                this.checkedList.push(id);
            }

            $target.closest('tr').addClass('active');

            this.handleAfterCheck(isSilent);
        },

        /**
         * Unselect a record.
         *
         * @param {string} id An ID.
         * @param {JQuery} [$target]
         * @param {boolean} [isSilent] Do not trigger the `check` event.
         */
        uncheckRecord: function (id, $target, isSilent) {
            $target = $target || this.$el.find('.record-checkbox[data-id="' + id + '"]');

            if ($target.get(0)) {
                $target.get(0).checked = false;
            }

            var index = this.checkedList.indexOf(id);

            if (index !== -1) {
                this.checkedList.splice(index, 1);
            }

            if ($target.get(0)) {
                $target.closest('tr').removeClass('active');
            }

            this.handleAfterCheck(isSilent);
        },

        /**
         * @protected
         * @param {boolean} [isSilent]
         */
        handleAfterCheck: function (isSilent) {
            if (this.checkedList.length) {
                this.showActions();
            }
            else {
                this.hideActions();
            }

            if (this.checkedList.length === this.collection.models.length) {
                this.$el.find('.select-all').prop('checked', true);
            }
            else {
                this.$el.find('.select-all').prop('checked', false);
            }

            if (!isSilent) {
                this.trigger('check');
            }
        },

        /**
         * Get row-actions defs.
         *
         * @return {Object}
         */
        getRowActionsDefs: function () {
            var options = {
                defs: {
                    params: {}
                }
            };

            if (this.options.rowActionsOptions) {
                for (var item in this.options.rowActionsOptions) {
                    options[item] = this.options.rowActionsOptions[item];
                }
            }

            return {
                columnName: 'buttons',
                name: 'buttonsField',
                view: this.rowActionsView,
                options: options
            };
        },

        /**
         * Get selected models.
         *
         * @return {module:model[]}
         */
        getSelected: function () {
            var list = [];

            this.$el.find('input.record-checkbox:checked').each((i, el) => {

                var id = $(el).attr('data-id');

                var model = this.collection.get(id);

                list.push(model);
            });

            return list;
        },

        /**
         * @protected
         */
        getInternalLayoutForModel: function (callback, model) {
            var scope = model.name;

            if (this._internalLayout === null) {
                this._internalLayout = {};
            }

            if (!(scope in this._internalLayout)) {
                this._internalLayout[scope] = this._convertLayout(this.listLayout[scope], model);
            }

            callback(this._internalLayout[scope]);
        },

        /**
         * @protected
         */
        getInternalLayout: function (callback, model) {
            if (this.scope === null || this.rowHasOwnLayout) {
                if (!model) {
                    callback(null);

                    return;
                }
                else {
                    this.getInternalLayoutForModel(callback, model);

                    return;
                }
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

            this._loadListLayout((listLayout) => {
                this.listLayout = listLayout;
                this._internalLayout = this._convertLayout(listLayout);

                callback(this._internalLayout);
            });
        },

        /**
         * Compose a cell selector for a layout item.
         *
         * @param {module:model} model A model.
         * @param {Object} item An item.
         * @return {string}
         */
        getItemEl: function (model, item) {
            return this.options.el + ' tr[data-id="' + model.id + '"] ' +
                'td.cell[data-name="' + item.columnName + '"]';
        },

        prepareInternalLayout: function (internalLayout, model) {
            internalLayout.forEach((item) => {
                item.el = this.getItemEl(model, item);
            });
        },

        /**
         * Build a row.
         *
         * @param {number} i An index.
         * @param {module:model} model A model.
         * @param {function(module:view):void} [callback] A callback.
         */
        buildRow: function (i, model, callback) {
            var key = model.id;

            this.rowList.push(key);

            this.getInternalLayout((internalLayout) => {
                internalLayout = Espo.Utils.cloneDeep(internalLayout);

                this.prepareInternalLayout(internalLayout, model);

                var acl =  {
                    edit: this.getAcl().checkModel(model, 'edit') && !this.editDisabled,
                    delete: this.getAcl().checkModel(model, 'delete') && !this.removeDisabled,
                };

                this.createView(key, 'views/base', {
                    model: model,
                    acl: acl,
                    el: this.options.el + ' .list-row[data-id="'+key+'"]',
                    optionsToPass: ['acl'],
                    noCache: true,
                    _layout: {
                        type: this._internalLayoutType,
                        layout: internalLayout
                    },
                    name: this.type + '-' + model.name,
                    setViewBeforeCallback: this.options.skipBuildRows && !this.isRendered(),
                }, callback);
            }, model);
        },

        /**
         * Build rows.
         *
         * @param {function():void} [callback] A callback.
         */
        buildRows: function (callback) {
            this.checkedList = [];

            this.rowList = [];

            if (this.collection.length <= 0) {
                if (typeof callback === 'function') {
                    callback();

                    this.trigger('after:build-rows');
                }

                return;
            }

            var iteration = 0;
            var repeatCount = !this.pagination ? 1 : 2;

            var callbackWrapped = function () {
                iteration++;

                if (iteration === repeatCount) {
                    if (typeof callback === 'function') {
                        callback();
                    }
                }
            };

            this.wait(true);

            var modelList = this.collection.models;

            var count = modelList.length;

            var builtCount = 0;

            modelList.forEach((model) => {
                this.buildRow(iteration, model, () => {
                    builtCount++;

                    if (builtCount === count) {
                        callbackWrapped();

                        this.wait(false);

                        this.trigger('after:build-rows');
                    }
                });
            });

            if (this.pagination) {
                this.createView('pagination', 'views/record/list-pagination', {
                    collection: this.collection,
                }, callbackWrapped);
            }
        },

        /**
         * Show more records.
         *
         * @param {module:collection.Class} [collection]
         * @param {JQuery} [$list]
         * @param {JQuery} [$showMore]
         * @param {function(): void} [callback] A callback.
         */
        showMoreRecords: function (collection, $list, $showMore, callback) {
            collection = collection || this.collection;

            $showMore =  $showMore || this.$el.find('.show-more');

            $list = $list || this.$el.find(this.listContainerEl);

            $showMore.children('a').addClass('disabled');

            Espo.Ui.notify(this.translate('loading', 'messages'));

            var final = () => {
                $showMore.parent().append($showMore);

                if (
                    collection.total > collection.length + collection.lengthCorrection ||
                    collection.total === -1
                ) {
                    let moreCount = collection.total - collection.length - collection.lengthCorrection;
                    let moreCountString = this.getNumberUtil().formatInt(moreCount);

                    this.$el.find('.more-count').text(moreCountString);

                    $showMore.removeClass('hidden');
                }
                else {
                    $showMore.remove();
                }

                $showMore.children('a').removeClass('disabled');

                if (this.allResultIsChecked) {
                    this.$el
                        .find('input.record-checkbox')
                        .attr('disabled', 'disabled')
                        .prop('checked', true);
                }

                Espo.Ui.notify(false);

                if (callback) {
                    callback.call(this);
                }
            };

            var initialCount = collection.length;

            var success = () => {
                Espo.Ui.notify(false);

                $showMore.addClass('hidden');

                var rowCount = collection.length - initialCount;
                var rowsReady = 0;

                if (collection.length <= initialCount) {
                    final();
                }

                for (var i = initialCount; i < collection.length; i++) {
                    var model = collection.at(i);

                    this.buildRow(i, model, (view) => {
                        var model = view.model;

                        view.getHtml((html) => {
                            var $row = $(this.getRowContainerHtml(model.id));

                            $row.append(html);

                            var $existingRowItem = this.getDomRowItem(model.id);

                            if ($existingRowItem && $existingRowItem.length) {
                                $existingRowItem.remove();
                            }

                            $list.append($row);

                            rowsReady++;

                            if (rowsReady === rowCount) {
                                final();
                            }

                            view._afterRender();

                            if (view.options.el) {
                                view.setElement(view.options.el);
                            }
                        });
                    });
                }

                this.noRebuild = true;
            };

            this.listenToOnce(collection, 'update', (collection, o) => {
                if (o.changes.merged.length) {
                    collection.lengthCorrection += o.changes.merged.length;
                }
            });

            // If using promise callback, then need to pass `noRebuild: true`.
            collection.fetch({
                success: success,
                remove: false,
                more: true,
            });
        },

        getDomRowItem: function (id) {
            return null;
        },

        /**
         * Compose a row-container HTML.
         *
         * @param {string} id A record ID.
         * @return {string} HTML.
         */
        getRowContainerHtml: function (id) {
            return $('<tr>')
                .attr('data-id', id)
                .addClass('list-row')
                .get(0).outerHTML;
        },

        actionQuickView: function (data) {
            data = data || {};

            let id = data.id;

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
                scope = model.name;
            }

            if (!scope) {
                scope = this.scope;
            }

            if (!scope) {
                console.error("No scope.");

                return;
            }

            if (this.quickDetailDisabled) {
                this.getRouter().navigate('#' + scope + '/view/' + id, {trigger: true});

                return;
            }

            let helper = new RecordModal(this.getMetadata(), this.getAcl());

            helper
                .showDetail(this, {
                    id: id,
                    scope: scope,
                    model: model,
                    rootUrl: this.options.keepCurrentRootUrl ?
                        this.getRouter().getCurrentUrl() : null,
                    editDisabled: this.quickEditDisabled,
                })
                .then(view => {
                    if (!model) {
                        return;
                    }

                    this.listenTo(view, 'after:save', model => {
                        this.trigger('after:save', model);
                    });
                });
        },

        actionQuickEdit: function (data) {
            data = data || {};

            let id = data.id;

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
                scope = model.name;
            }

            if (!scope) {
                scope = this.scope;
            }

            if (!scope) {
                console.error("No scope.");

                return;
            }

            let viewName = this.getMetadata().get(['clientDefs', scope, 'modalViews', 'edit']) ||
                'views/modals/edit';

            if (!this.quickEditDisabled) {
                Espo.Ui.notify(this.translate('loading', 'messages'));

                let options = {
                    scope: scope,
                    id: id,
                    model: model,
                    fullFormDisabled: data.noFullForm,
                    returnUrl: this.getRouter().getCurrentUrl(),
                    returnDispatchParams: {
                        controller: scope,
                        action: null,
                        options: {
                            isReturn: true,
                        },
                    },
                };

                if (this.options.keepCurrentRootUrl) {
                    options.rootUrl = this.getRouter().getCurrentUrl();
                }

                this.createView('modal', viewName, options, (view) => {
                    view.once('after:render', () => {
                        Espo.Ui.notify(false);
                    });

                    view.render();

                    this.listenToOnce(view, 'remove', () => {
                        this.clearView('modal');
                    });

                    this.listenToOnce(view, 'after:save', (m) => {
                        var model = this.collection.get(m.id);

                        if (model) {
                            model.set(m.getClonedAttributes());
                        }

                        this.trigger('after:save', m);
                    });
                });

                return;
            }

            let options = {
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
            };

            if (this.options.keepCurrentRootUrl) {
                options.rootUrl = this.getRouter().getCurrentUrl();
            }

            this.getRouter().navigate('#' + scope + '/edit/' + id, {trigger: false});
            this.getRouter().dispatch(scope, 'edit', options);
        },

        /**
         * Compose a row selector.
         *
         * @param {string} id A record ID.
         * @return {string}
         */
        getRowSelector: function (id) {
            return 'tr[data-id="' + id + '"]';
        },

        actionQuickRemove: function (data) {
            data = data || {};

            let id = data.id;

            if (!id) {
                return;
            }

            let model = this.collection.get(id);

            if (!this.getAcl().checkModel(model, 'delete')) {
                Espo.Ui.error(this.translate('Access denied'));

                return;
            }

            this.confirm({
                message: this.translate('removeRecordConfirmation', 'messages', this.scope),
                confirmText: this.translate('Remove'),
            }, () => {
                this.collection.trigger('model-removing', id);
                this.collection.remove(model);

                this.notify('Removing...');

                model
                    .destroy({wait: true, fromList: true})
                    .then(() => {
                        Espo.Ui.success(this.translate('Removed'));

                        this.removeRecordFromList(id);
                    })
                    .catch(() => {
                        this.collection.push(model);
                    });
            });
        },

        /**
         * @protected
         * @param {string} id An ID.
         */
        removeRecordFromList: function (id) {
            this.collection.remove(id);

            if (this.collection.total > 0) {
                this.collection.total--;
            }

            this.$el.find('.total-count-span').text(this.collection.total.toString());

            let index = this.checkedList.indexOf(id);

            if (index !== -1) {
                this.checkedList.splice(index, 1);
            }

            this.removeRowHtml(id);

            let key = id;

            this.clearView(key);

            index = this.rowList.indexOf(key);

            if (~index) {
                this.rowList.splice(index, 1);
            }
        },

        /**
         * @protected
         * @param {string} id An ID.
         */
        removeRowHtml: function (id) {
            this.$el.find(this.getRowSelector(id)).remove();

            if (
                this.collection.length === 0 &&
                (this.collection.total === 0 || this.collection.total === -2)
            ) {
                this.reRender();
            }
        },

        getTableMinWidth: function () {
            if (!this.listLayout) {
                return;
            }

            var totalWidth = 0;
            var totalWidthPx = 0;
            var emptyCount = 0;
            var columnCount = 0;

            this.listLayout.forEach((item) => {
                columnCount ++;

                if (item.widthPx) {
                    totalWidthPx += item.widthPx;

                    return;
                }

                if (item.width) {
                    totalWidth += item.width;

                    return;
                }

                emptyCount ++;
            });

            if (this.rowActionsView && !this.rowActionsDisabled) {
                totalWidthPx += this.rowActionsColumnWidth;
            }

            if (this.checkboxes) {
                totalWidthPx += this.checkboxColumnWidth;
            }

            var minWidth;

            if (totalWidth >= 100) {
                minWidth = columnCount * this.minColumnWidth;
            }
            else {
                minWidth = (totalWidthPx + this.minColumnWidth * emptyCount) / (1 - totalWidth / 100);
                minWidth = Math.round(minWidth);
            }

            return minWidth;
        },
    });
});

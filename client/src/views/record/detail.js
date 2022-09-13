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

define('views/record/detail', ['views/record/base', 'view-record-helper', 'helpers/action-item-setup'],
function (Dep, ViewRecordHelper, ActionItemSetup) {

    /**
     * A detail record view.
     *
     * @class
     * @name Class
     * @extends module:views/record/base.Class
     * @memberOf module:views/record/detail
     */
    return Dep.extend(/** @lends module:views/record/detail.Class# */{

        /**
         * @inheritDoc
         */
        template: 'record/detail',

        /**
         * @inheritDoc
         */
        type: 'detail',

        /**
         * Not used.
         *
         * @deprecated
         * @protected
         */
        name: 'detail',

        /**
         * A layout name. Can be overridden by an option parameter.
         *
         * @protected
         * @type {string}
         */
        layoutName: 'detail',

        /**
         * A layout. If null, then will be loaded from the backend (using the `layoutName` value).
         * Can be overridden by an option parameter.
         *
         * @protected
         * @type {Object[]|null}
         * @todo Define panelDefs type.
         */
        detailLayout: null,

        /**
         * A fields mode.
         *
         * @protected
         * @type {'detail'|'edit'|'list'}
         */
        fieldsMode: 'detail',

        /**
         * A current mode. Only for reading.
         *
         * @protected
         * @type {'detail'|'edit'}
         */
        mode: 'detail',

        /**
         * @private
         */
        gridLayout: null,

        /**
         * Disable buttons. Can be overridden by an option parameter.
         *
         * @protected
         * @type {boolean}
         */
        buttonsDisabled: false,

        /**
         * Is record new. Only for reading.
         *
         * @protected
         */
        isNew: false,

        /**
         * A button. Handled by an `action{Name}` method.
         *
         * @typedef module:views/record/detail~button
         *
         * @property {string} name A name.
         * @property {string} [label] A label.
         * @property {string} [html] An HTML.
         * @property {string} [text] A text.
         * @property {'default'|'danger'|'success'|'warning'} [style] A style.
         * @property {boolean} [hidden] Hidden.
         * @property {string} [title] A title (not translatable).
         * @property {boolean} [disabled] Disabled.
         */

        /**
         * A dropdown item. Handled by an `action{Name}` method.
         *
         * @typedef module:views/record/detail~dropdownItem
         *
         * @property {string} name A name.
         * @property {string} [label] A label.
         * @property {string} [html] An HTML.
         * @property {string} [text] A text.
         * @property {boolean} [hidden] Hidden.
         * @property {Object.<string,string>} [data] Data attributes.
         * @property {string} [title] A title (not translatable).
         * @property {boolean} [disabled] Disabled.
         */

        /**
         * A button list.
         *
         * @protected
         * @type {module:views/record/detail~button[]}
         */
        buttonList: [
            {
                name: 'edit',
                label: 'Edit',
                title: 'Ctrl+Space',
            },
        ],

        /**
         * A dropdown item list.
         *
         * @protected
         * @type {module:views/record/detail~dropdownItem[]}
         */
        dropdownItemList: [
            {
                name: 'delete',
                label: 'Remove',
            },
        ],

        /**
         * A button list for edit mode.
         *
         * @protected
         * @type {module:views/record/detail~button[]}
         */
        buttonEditList: [
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
        ],

        /**
         * A dropdown item list for edit mode.
         *
         * @protected
         * @type {module:views/record/detail~dropdownItem[]}
         */
        dropdownEditItemList: [],

        /**
         * All action items disabled;
         *
         * @protected
         */
        allActionItemsDisabled: false,

        /**
         * An DOM element ID. Only for reading.
         *
         * @private
         * @type {string}
         */
        id: null,

        /**
         * A return-URL. Can be overridden by an option parameter.
         *
         * @protected
         * @type {string|null}
         */
        returnUrl: null,

        /**
         * A return dispatch params. Can be overridden by an option parameter.
         *
         * @protected
         * @type {Object|null}
         */
        returnDispatchParams: null,

        /**
         * A middle view name.
         *
         * @protected
         */
        middleView: 'views/record/detail-middle',

        /**
         * A side view name.
         *
         * @protected
         */
        sideView: 'views/record/detail-side',

        /**
         * A bottom view name.
         *
         * @protected
         */
        bottomView: 'views/record/detail-bottom',

        /**
         * Disable a side view. Can be overridden by an option parameter.
         *
         * @protected
         */
        sideDisabled: false,

        /**
         * Disable a bottom view. Can be overridden by an option parameter.
         *
         * @protected
         */
        bottomDisabled: false,

        /**
         * @protected
         */
        gridLayoutType: 'record',

        /**
         * Disable edit mode. Can be overridden by an option parameter.
         *
         * @protected
         */
        editModeDisabled: false,

        /**
         * Disable navigate (prev, next) buttons. Can be overridden by an option parameter.
         *
         * @protected
         */
        navigateButtonsDisabled: false,

        /**
         * Read-only. Can be overridden by an option parameter.
         */
        readOnly: false,

        /**
         * Middle view expanded to full width (no side view).
         * Can be overridden by an option parameter.
         *
         * @protected
         */
        isWide: false,

        /**
         * Enable a duplicate action.
         *
         * @protected
         */
        duplicateAction: true,

        /**
         * Enable a self-assign action.
         *
         * @protected
         */
        selfAssignAction: false,

        /**
         * Enable a print-pdf action.
         *
         * @protected
         */
        printPdfAction: true,

        /**
         * Enable a convert-currency action.
         *
         * @protected
         */
        convertCurrencyAction: true,

        /**
         * Enable a save-and-continue-editing action.
         *
         * @protected
         */
        saveAndContinueEditingAction: true,

        /**
         * Disable the inline-edit. Can be overridden by an option parameter.
         *
         * @protected
         */
        inlineEditDisabled: false,

        /**
         * Disable a portal layout usage. Can be overridden by an option parameter.
         *
         * @protected
         */
        portalLayoutDisabled: false,

        /**
         * A panel soft-locked type.
         *
         * @typedef {'default'|'acl'|'delimiter'|'dynamicLogic'
         * } module:views/record/detail~panelSoftLockedType
         */

        /**
         * @private
         * @type {module:views/record/detail~panelSoftLockedType[]}
         */
        panelSoftLockedTypeList: [
            'default',
            'acl',
            'delimiter',
            'dynamicLogic',
        ],

        /**
         * Dynamic logic. Can be overridden by an option parameter.
         *
         * @protected
         * @type {Object}
         */
        dynamicLogicDefs: {},

        /**
         * Disable confirm leave-out processing.
         *
         * @protected
         */
        confirmLeaveDisabled: false,

        /**
         * @protected
         */
        setupHandlerType: 'record/detail',

        /**
         * @protected
         */
        currentMiddleTab: 0,

        /**
         * @protected
         * @type {Object.<string,*>|null}
         */
        middlePanelDefs: null,

        /**
         * @protected
         * @type {Object.<string,*>[]|null}
         */
        middlePanelDefsList: null,

        /**
         * @protected
         * @type {JQuery|null}
         */
        $middle: null,

        /**
         * @protected
         * @type {JQuery|null}
         */
        $bottom: null,

        /**
         * @private
         * @type {JQuery|null}
         */
        $detailButtonContainer: null,

        /**
         * A Ctrl+Enter shortcut action.
         *
         * @protected
         * @type {?string}
         */
        shortcutKeyCtrlEnterAction: 'save',

        /**
         * A shortcut-key => action map.
         *
         * @protected
         * @type {?Object.<string,string|function (JQueryKeyEventObject): void>}
         */
        shortcutKeys: {
            'Control+Enter': function (e) {
                this.handleShortcutKeyCtrlEnter(e);
            },
            'Control+Alt+Enter': function (e) {
                this.handleShortcutKeyCtrlAltEnter(e);
            },
            'Control+KeyS': function (e) {
                this.handleShortcutKeyCtrlS(e);
            },
            'Control+Space': function (e) {
                this.handleShortcutKeyCtrlSpace(e);
            },
            'Escape': function (e) {
                this.handleShortcutKeyEscape(e);
            },
            'Control+Backslash': function (e) {
                this.handleShortcutKeyControlBackslash(e);
            },
            'Control+ArrowLeft': function (e) {
                this.handleShortcutKeyControlArrowLeft(e);
            },
            'Control+ArrowRight': function (e) {
                this.handleShortcutKeyControlArrowRight(e);
            },
        },

        /**
         * @inheritDoc
         */
        events: {
            'click .button-container .action': function (e) {
                Espo.Utils.handleAction(this, e);
            },
            /** @this module:views/record/detail.Class */
            'click [data-action="showMoreDetailPanels"]': function () {
                this.showMoreDetailPanels();
            },
            /** @this module:views/record/detail.Class */
            'click .middle-tabs > button': function (e) {
                let tab = $(e.currentTarget).attr('data-tab');

                this.selectMiddleTab(parseInt(tab));
            },
        },

        /**
         * An `edit` action.
         */
        actionEdit: function () {
            if (!this.editModeDisabled) {
                this.setEditMode();

                this.focusOnFirstDiv();
                $(window).scrollTop(0);

                return;
            }

            var options = {
                id: this.model.id,
                model: this.model,
            };

            if (this.options.rootUrl) {
                options.rootUrl = this.options.rootUrl;
            }

            this.getRouter().navigate('#' + this.scope + '/edit/' + this.model.id, {trigger: false});
            this.getRouter().dispatch(this.scope, 'edit', options);
        },

        actionDelete: function () {
            this.delete();
        },

        /**
         * A `save` action.
         *
         * @param {{options?: module:views/record/base~saveOptions}} [data] Data.
         */
        actionSave: function (data) {
            data = data || {};

            var modeBeforeSave = this.mode;

            this.save(data.options)
                .catch(reason => {
                    if (modeBeforeSave === 'edit' && reason === 'error') {
                        this.setEditMode();
                    }
                });

            if (!this.lastSaveCancelReason || this.lastSaveCancelReason === 'notModified') {
                this.setDetailMode();

                this.focusOnFirstDiv();
                $(window).scrollTop(0);
            }
        },

        actionCancelEdit: function () {
            this.cancelEdit();

            this.focusOnFirstDiv();
            $(window).scrollTop(0);
        },

        focusOnFirstDiv: function () {
            let element = this.$el.find('> div').get(0);

            if (element) {
                element.focus({preventScroll: true});
            }
        },

        /**
         * A `save-and-continue-editing` action.
         */
        actionSaveAndContinueEditing: function (data) {
            data = data || {};

            this.save(data.options)
                .catch(() => {});
        },

        /**
         * A `self-assign` action.
         */
        actionSelfAssign: function () {
            var attributes = {
                assignedUserId: this.getUser().id,
                assignedUserName: this.getUser().get('name'),
            };

            if ('getSelfAssignAttributes' in this) {
                var attributesAdditional = this.getSelfAssignAttributes();

                if (attributesAdditional) {
                    for (let i in attributesAdditional) {
                        attributes[i] = attributesAdditional[i];
                    }
                }
            }

            this.model
                .save(attributes, {patch: true})
                .then(() => {
                    Espo.Ui.success(this.translate('Self-Assigned'));
                });
        },

        /**
         * A `convert-currency` action.
         */
        actionConvertCurrency: function () {
            this.createView('modalConvertCurrency', 'views/modals/convert-currency', {
                entityType: this.entityType,
                model: this.model,
            }, view => {
                view.render();

                this.listenToOnce(view, 'after:update', attributes => {
                    var isChanged = false;

                    for (let a in attributes) {
                        if (attributes[a] !== this.model.get(a)) {
                            isChanged = true;

                            break;
                        }
                    }

                    if (!isChanged) {
                        Espo.Ui.warning(this.translate('notUpdated', 'messages'));

                        return;
                    }

                    this.model
                        .fetch()
                        .then(() => {
                            Espo.Ui.success(this.translate('done', 'messages'));
                        });
                });
            });
        },

        /**
         * Compose attribute values for a self-assignment.
         *
         * @protected
         * @return {Object.<string,*>|null}
         */
        getSelfAssignAttributes: function () {
            return null;
        },

        /**
         * Set up action items.
         *
         * @protected
         */
        setupActionItems: function () {
            if (this.model.isNew()) {
                this.isNew = true;

                this.removeActionItem('delete');
            }
            else if (this.getMetadata().get(['clientDefs', this.scope, 'removeDisabled'])) {
                this.removeActionItem('delete');
            }

            if (this.duplicateAction) {
                if (
                    this.getAcl().check(this.entityType, 'create') &&
                    !this.getMetadata().get(['clientDefs', this.scope, 'duplicateDisabled'])
                ) {
                    this.addDropdownItem({
                        'label': 'Duplicate',
                        'name': 'duplicate'
                    });
                }
            }

            if (this.selfAssignAction) {
                if (
                    this.getAcl().check(this.entityType, 'edit') &&
                    !~this.getAcl().getScopeForbiddenFieldList(this.entityType).indexOf('assignedUser') &&
                    !this.getUser().isPortal()
                ) {
                    if (this.model.has('assignedUserId')) {
                        this.dropdownItemList.push({
                            'label': 'Self-Assign',
                            'name': 'selfAssign',
                            'hidden': !!this.model.get('assignedUserId')
                        });

                        this.listenTo(this.model, 'change:assignedUserId', () => {
                            if (!this.model.get('assignedUserId')) {
                                this.showActionItem('selfAssign');
                            }
                            else {
                                this.hideActionItem('selfAssign');
                            }
                        });
                    }
                }
            }

            if (this.type === this.TYPE_DETAIL && this.printPdfAction) {
                var printPdfAction = true;

                if (
                    !~(this.getHelper().getAppParam('templateEntityTypeList') || [])
                        .indexOf(this.entityType)
                ) {
                    printPdfAction = false;
                }

                if (printPdfAction) {
                    this.dropdownItemList.push({
                        'label': 'Print to PDF',
                        'name': 'printPdf',
                    });
                }
            }

            if (this.type === this.TYPE_DETAIL && this.convertCurrencyAction) {
                if (
                    this.getAcl().check(this.entityType, 'edit') &&
                    !this.getMetadata().get(['clientDefs', this.scope, 'convertCurrencyDisabled'])
                ) {
                    var currencyFieldList = this.getFieldManager()
                        .getEntityTypeFieldList(this.entityType, {
                            type: 'currency',
                            acl: 'edit',
                        });

                    if (currencyFieldList.length) {
                        this.addDropdownItem({
                            label: 'Convert Currency',
                            name: 'convertCurrency',
                        });
                    }
                }
            }

            if (
                this.type === this.TYPE_DETAIL &&
                this.getMetadata().get(['scopes', this.scope, 'hasPersonalData'])
            ) {
                if (this.getAcl().get('dataPrivacyPermission') === 'yes') {
                    this.dropdownItemList.push({
                        'label': 'View Personal Data',
                        'name': 'viewPersonalData'
                    });
                }
            }

            if (this.type === 'detail' && this.getMetadata().get(['scopes', this.scope, 'stream'])) {
                this.addDropdownItem({
                    label: 'View Followers',
                    name: 'viewFollowers'
                });
            }

            if (this.type === 'detail') {
                /** @var {module:helpers/action-item-setup.Class} */
                let actionItemSetup = new ActionItemSetup(
                    this.getMetadata(),
                    this.getHelper(),
                    this.getAcl(),
                    this.getLanguage()
                );

                actionItemSetup.setup(
                    this,
                    this.type,
                    promise => this.wait(promise),
                    item => this.addDropdownItem(item),
                    name => this.showActionItem(name),
                    name => this.hideActionItem(name)
                );

                if (this.saveAndContinueEditingAction) {
                    this.dropdownEditItemList.push({
                        name: 'saveAndContinueEditing',
                        label: 'Save & Continue Editing',
                        title: 'Ctrl+S',
                    });
                }
            }
        },

        /**
         * Disable action items.
         */
        disableActionItems: function () {
            this.disableButtons();
        },

        /**
         * Enable action items.
         */
        enableActionItems: function () {
            this.enableButtons();
        },

        /**
         * Hide a button or dropdown action item.
         *
         * @param {string} name A name.
         */
        hideActionItem: function (name) {
            for (let item of this.buttonList) {
                if (item.name === name) {
                    item.hidden = true;

                    break;
                }
            }

            for (let item of this.dropdownItemList) {
                if (item.name === name) {
                    item.hidden = true;

                    break;
                }
            }

            for (let item of this.dropdownEditItemList) {
                if (item.name === name) {
                    item.hidden = true;

                    break;
                }
            }

            for (let item of this.buttonEditList) {
                if (item.name === name) {
                    item.hidden = true;

                    break;
                }
            }

            if (this.isRendered()) {
                this.$detailButtonContainer
                    .find('li > .action[data-action="'+name+'"]')
                    .parent()
                    .addClass('hidden');

                this.$detailButtonContainer
                    .find('button.action[data-action="'+name+'"]')
                    .addClass('hidden');

                if (this.isDropdownItemListEmpty()) {
                    this.$dropdownItemListButton.addClass('hidden');
                }

                if (this.isDropdownEditItemListEmpty()) {
                    this.$dropdownEditItemListButton.addClass('hidden');
                }

                this.adjustButtons();
            }
        },

        /**
         * Show a button or dropdown action item.
         *
         * @param {string} name A name.
         */
        showActionItem: function (name) {
            for (let item of this.buttonList) {
                if (item.name === name) {
                    item.hidden = false;

                    break;
                }
            }

            for (let item of this.dropdownItemList) {
                if (item.name === name) {
                    item.hidden = false;

                    break;
                }
            }

            for (let item of this.dropdownEditItemList) {
                if (item.name === name) {
                    item.hidden = false;

                    break;
                }
            }

            for (let item of this.buttonEditList) {
                if (item.name === name) {
                    item.hidden = false;

                    break;
                }
            }

            if (this.isRendered()) {
                this.$detailButtonContainer
                    .find('li > .action[data-action="'+name+'"]')
                    .parent()
                    .removeClass('hidden');

                this.$detailButtonContainer
                    .find('button.action[data-action="'+name+'"]')
                    .removeClass('hidden');

                if (!this.isDropdownItemListEmpty()) {
                    this.$dropdownItemListButton.removeClass('hidden');
                }

                if (!this.isDropdownEditItemListEmpty()) {
                    this.$dropdownItemListButton.removeClass('hidden');
                }

                this.adjustButtons();
            }
        },

        /**
         * Disable a button or dropdown action item.
         *
         * @param {string} name A name.
         */
        disableActionItem: function (name) {
            for (let item of this.buttonList) {
                if (item.name === name) {
                    item.disabled = true;

                    break;
                }
            }

            for (let item of this.dropdownItemList) {
                if (item.name === name) {
                    item.disabled = true;

                    break;
                }
            }

            for (let item of this.dropdownEditItemList) {
                if (item.name === name) {
                    item.disabled = true;

                    break;
                }
            }

            for (let item of this.buttonEditList) {
                if (item.name === name) {
                    item.disabled = true;

                    break;
                }
            }

            if (this.isRendered()) {
                this.$detailButtonContainer
                    .find('li > .action[data-action="'+name+'"]')
                    .parent()
                    .addClass('disabled')
                    .attr('disabled', 'disabled');

                this.$detailButtonContainer
                    .find('button.action[data-action="'+name+'"]')
                    .addClass('disabled')
                    .attr('disabled', 'disabled');
            }
        },

        /**
         * Enable a button or dropdown action item.
         *
         * @param {string} name A name.
         */
        enableActionItem: function (name) {
            for (let item of this.buttonList) {
                if (item.name === name) {
                    item.disabled = false;

                    break;
                }
            }

            for (let item of this.dropdownItemList) {
                if (item.name === name) {
                    item.disabled = false;

                    break;
                }
            }

            for (let item of this.dropdownEditItemList) {
                if (item.name === name) {
                    item.disabled = false;

                    break;
                }
            }

            for (let item of this.buttonEditList) {
                if (item.name === name) {
                    item.disabled = false;

                    break;
                }
            }

            if (this.isRendered()) {
                this.$detailButtonContainer
                    .find('li > .action[data-action="'+name+'"]')
                    .parent()
                    .removeClass('disabled')
                    .removeAttr('disabled');

                this.$detailButtonContainer
                    .find('button.action[data-action="'+name+'"]')
                    .removeClass('disabled')
                    .removeAttr('disabled');
            }
        },

        /**
         * Whether an action item is visible and not disabled.
         *
         * @param {string} name An action item name.
         */
        hasAvailableActionItem: function (name) {
            if (this.allActionItemsDisabled) {
                return false;
            }

            if (this.type === this.TYPE_DETAIL && this.mode === this.MODE_EDIT) {
                let hasButton = this.buttonEditList
                    .findIndex(item => item.name === name && !item.disabled && !item.hidden) !== -1;

                if (hasButton) {
                    return true;
                }

                return this.dropdownEditItemList
                    .findIndex(item => item.name === name && !item.disabled && !item.hidden) !== -1;
            }

            let hasButton = this.buttonList
                .findIndex(item => item.name === name && !item.disabled && !item.hidden) !== -1;

            if (hasButton) {
                return true;
            }

            return this.dropdownItemList
                .findIndex(item => item.name === name && !item.disabled && !item.hidden) !== -1;
        },

        /**
         * Show a panel.
         *
         * @param {string} name A panel name.
         * @param {module:views/record/detail~panelSoftLockedType} [softLockedType='default']
         *   A soft-locked type.
         */
        showPanel: function (name, softLockedType) {
            if (this.recordHelper.getPanelStateParam(name, 'hiddenLocked')) {
                return;
            }

            softLockedType = softLockedType || 'default';

            this.recordHelper
                .setPanelStateParam(name,
                    'hidden' + Espo.Utils.upperCaseFirst(softLockedType) + 'Locked', false);

            if (softLockedType === 'dynamicLogic') {
                if (this.recordHelper.getPanelStateParam(name, 'hidden') === false) {
                    return;
                }
            }

            for (let i = 0; i < this.panelSoftLockedTypeList.length; i++) {
                let iType = this.panelSoftLockedTypeList[i];

                if (iType === softLockedType) {
                    continue;
                }

                let iParam = 'hidden' +  Espo.Utils.upperCaseFirst(iType) + 'Locked';

                if (this.recordHelper.getPanelStateParam(name, iParam)) {
                    return;
                }
            }

            let middleView = this.getView('middle');

            if (middleView) {
                middleView.showPanelInternal(name);
            }

            let bottomView = this.getView('bottom');

            if (bottomView) {
                if ('showPanel' in bottomView) {
                    bottomView.showPanel(name);
                }
            }
            else if (this.bottomView) {
                this.once('ready', () => {
                    let view = this.getView('bottom');

                    if (view) {
                        if ('processShowPanel' in view) {
                            view.processShowPanel(name);

                            return;
                        }

                        if ('showPanel' in view) {
                            view.showPanel(name);
                        }
                    }
                });
            }

            let sideView = this.getView('side');

            if (sideView) {
                if ('showPanel' in sideView) {
                    sideView.showPanel(name);
                }
            }
            else if (this.sideView) {
                this.once('ready', () => {
                    let view = this.getView('side');

                    if (view) {
                        if ('processShowPanel' in view) {
                            view.processShowPanel(name);

                            return;
                        }

                        if ('showPanel' in view) {
                            view.showPanel(name);
                        }
                    }
                });
            }

            this.recordHelper.setPanelStateParam(name, 'hidden', false);

            if (this.middlePanelDefs[name]) {
                this.controlMiddleTabVisibilityShow(this.middlePanelDefs[name].tabNumber);

                this.adjustMiddlePanels();
            }

            this.recordHelper.trigger('panel-show');
        },

        /**
         * Hide a panel.
         *
         * @param {string} name A panel name.
         * @param {boolean} [locked=false] Won't be able to un-hide.
         * @param {module:views/record/detail~panelSoftLockedType} [softLockedType='default']
         *   A soft-locked type.
         */
        hidePanel: function (name, locked, softLockedType) {
            softLockedType = softLockedType || 'default';

            if (locked) {
                this.recordHelper.setPanelStateParam(name, 'hiddenLocked', true);
            }

            if (softLockedType) {
                this.recordHelper
                    .setPanelStateParam(name,
                        'hidden' + Espo.Utils.upperCaseFirst(softLockedType) + 'Locked', true);
            }

            if (softLockedType === 'dynamicLogic') {
                if (this.recordHelper.getPanelStateParam(name, 'hidden') === true) {
                    return;
                }
            }

            let middleView = this.getView('middle');

            if (middleView) {
                middleView.hidePanelInternal(name);
            }

            let bottomView = this.getView('bottom');

            if (bottomView) {
                if ('hidePanel' in bottomView) {
                    bottomView.hidePanel(name);
                }
            }
            else if (this.bottomView) {
                this.once('ready', () => {
                    let view = this.getView('bottom');

                    if (view) {
                        if ('processHidePanel' in view) {
                            view.processHidePanel(name);

                            return;
                        }

                        if ('hidePanel' in view) {
                            view.hidePanel(name);
                        }
                    }
                });
            }

            let sideView = this.getView('side');

            if (sideView) {
                if ('hidePanel' in sideView) {
                    sideView.hidePanel(name);
                }
            }
            else if (this.sideView) {
                this.once('ready', () => {
                    let view = this.getView('side');

                    if (view) {
                        if ('processHidePanel' in view) {
                            view.processHidePanel(name);

                            return;
                        }

                        if ('hidePanel' in view) {
                            view.hidePanel(name);
                        }
                    }
                });
            }

            this.recordHelper.setPanelStateParam(name, 'hidden', true);

            if (this.middlePanelDefs[name]) {
                this.controlMiddleTabVisibilityHide(this.middlePanelDefs[name].tabNumber);

                this.adjustMiddlePanels();
            }
        },

        afterRender: function () {
            this.$middle = this.$el.find('.middle');

            if (this.bottomView) {
                this.$bottom = this.$el.find('.bottom');
            }

            this.initElementReferences();

            this.adjustMiddlePanels();
            this.adjustButtons();

            this.initStickableButtonsContainer();
            this.initFieldsControlBehaviour();
        },

        initFieldsControlBehaviour: function () {
            let fields = this.getFieldViews();

            let fieldInEditMode = null;

            for (let field in fields) {
                let fieldView = fields[field];

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
        },

        initStickableButtonsContainer: function () {
            let $containers = this.$el.find('.detail-button-container');
            let $container = this.$el.find('.detail-button-container.record-buttons');

            if (!$container.length) {
                return;
            }

            let navbarHeight = this.getThemeManager().getParam('navbarHeight');
            let screenWidthXs = this.getThemeManager().getParam('screenWidthXs');

            let isSmallScreen = $(window.document).width() < screenWidthXs;

            let getOffsetTop = (/** JQuery */$element) => {
                let element = $element.get(0);

                let value = -3;

                while (element) {
                    value += !isNaN(element.offsetTop) ? element.offsetTop : 0;

                    element = element.offsetParent;
                }

                if (isSmallScreen) {
                    return value;
                }

                return value - navbarHeight;
            };

            let stickTop = getOffsetTop($container);
            let blockHeight = $container.outerHeight();

            let $block = $('<div>')
                .css('height', blockHeight + 'px')
                .html('&nbsp;')
                .hide()
                .insertAfter($container);

            let $middle = this.getView('middle').$el;
            let $window = $(window);
            let $navbarRight = $('#navbar .navbar-right');

            if (this.stickButtonsFormBottomSelector) {
                var $bottom = this.$el.find(this.stickButtonsFormBottomSelector);

                if ($bottom.length) {
                    $middle = $bottom;
                }
            }

            $window.off('scroll.detail-' + this.numId);

            $window.on('scroll.detail-' + this.numId, () => {
                let edge = $middle.position().top + $middle.outerHeight(false) - blockHeight;
                let scrollTop = $window.scrollTop();

                if (scrollTop >= edge && !this.stickButtonsContainerAllTheWay) {
                    $containers.hide();
                    $navbarRight.removeClass('has-sticked-bar');
                    $block.show();

                    return;
                }

                if (isSmallScreen && $('#navbar .navbar-body').hasClass('in')) {
                    return;
                }

                if (scrollTop > stickTop) {
                    if (!$containers.hasClass('stick-sub')) {
                        $containers.addClass('stick-sub');
                        $block.show();

                        /*$('.popover').each((i, el) => {
                            let $el = $(el);
                            $el.css('top', ($el.position().top - blockHeight) + 'px');
                        });*/
                    }

                    $navbarRight.addClass('has-sticked-bar');

                    $containers.show();

                    return;
                }

                if ($containers.hasClass('stick-sub')) {
                    $containers.removeClass('stick-sub');
                    $navbarRight.removeClass('has-sticked-bar');
                    $block.hide();

                    /*$('.popover').each((i, el) => {
                        let $el = $(el);
                        $el.css('top', ($el.position().top + blockHeight) + 'px');
                    });*/
                }

                $containers.show();
            });
        },

        fetch: function () {
            let data = Dep.prototype.fetch.call(this);

            if (this.hasView('side')) {
                let view = this.getView('side');

                if ('fetch' in view) {
                    data = _.extend(data, view.fetch());
                }
            }

            if (this.hasView('bottom')) {
                let view = this.getView('bottom');

                if ('fetch' in view) {
                    data = _.extend(data, view.fetch());
                }
            }

            return data;
        },

        setEditMode: function () {
            this.trigger('before:set-edit-mode');

            this.inlineEditModeIsOn = false;

            this.$el.find('.record-buttons').addClass('hidden');
            this.$el.find('.edit-buttons').removeClass('hidden');

            return new Promise(resolve => {
                let fields = this.getFieldViews(true);

                let promiseList = [];

                for (let field in fields) {
                    let fieldView = fields[field];

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
                this.trigger('after:mode-change');

                Promise.all(promiseList).then(() => resolve());
            });
        },

        setDetailMode: function () {
            this.trigger('before:set-detail-mode');

            this.$el.find('.edit-buttons').addClass('hidden');
            this.$el.find('.record-buttons').removeClass('hidden');

            this.inlineEditModeIsOn = false;

            return new Promise(resolve => {
                let fields = this.getFieldViews(true);

                let promiseList = [];

                for (let field in fields) {
                    let fieldView = fields[field];

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
                this.trigger('after:mode-change');

                Promise.all(promiseList).then(() => resolve());
            });
        },

        cancelEdit: function () {
            this.resetModelChanges();

            this.setDetailMode();
            this.setIsNotChanged();
        },

        resetModelChanges: function () {
            var skipReRender = true;

            if (this.updatedAttributes) {
                this.attributes = this.updatedAttributes;
                this.updatedAttributes = null;

                skipReRender = false;
            }

            var attributes = this.model.attributes;

            for (let attr in attributes) {
                if (!(attr in this.attributes)) {
                    this.model.unset(attr);
                }
            }

            this.model.set(this.attributes, {skipReRender: skipReRender});
        },

        delete: function () {
            this.confirm({
                message: this.translate('removeRecordConfirmation', 'messages', this.scope),
                confirmText: this.translate('Remove'),
            }, () => {
                this.trigger('before:delete');
                this.trigger('delete');

                this.notify('Removing...');

                var collection = this.model.collection;

                this.model
                    .destroy({wait: true})
                    .then(() => {
                        if (collection) {
                            if (collection.total > 0) {
                                collection.total--;
                            }
                        }

                        this.notify('Removed', 'success');
                        this.trigger('after:delete');
                        this.exit('delete');
                    });
            });
        },

        /**
         * Get field views.
         *
         * @param {boolean} [withHidden] With hidden.
         * @return {Object.<string,module:views/fields/base.Class>}
         */
        getFieldViews: function (withHidden) {
            let fields = {};

            if (this.hasView('middle')) {
                if ('getFieldViews' in this.getView('middle')) {
                    _.extend(fields, Espo.Utils.clone(this.getView('middle').getFieldViews(withHidden)));
                }
            }

            if (this.hasView('side')) {
                if ('getFieldViews' in this.getView('side')) {
                    _.extend(fields, this.getView('side').getFieldViews(withHidden));
                }
            }

            if (this.hasView('bottom')) {
                if ('getFieldViews' in this.getView('bottom')) {
                    _.extend(fields, this.getView('bottom').getFieldViews(withHidden));
                }
            }

            return fields;
        },

        /**
         * Get a field view.
         *
         * @param {string} name A field name.
         * @return {module:views/fields/base.Class|null}
         */
        getFieldView: function (name) {
            let view;

            if (this.hasView('middle')) {
                view = (this.getView('middle').getFieldViews(true) || {})[name];
            }

            if (!view && this.hasView('side')) {
                view = (this.getView('side').getFieldViews(true) || {})[name];
            }

            if (!view && this.hasView('bottom')) {
                view = (this.getView('bottom').getFieldViews(true) || {})[name];
            }

            return view || null;
        },

        // @todo Remove.
        handleDataBeforeRender: function (data) {},

        data: function () {
            var navigateButtonsEnabled = !this.navigateButtonsDisabled && !!this.model.collection;

            var previousButtonEnabled = false;
            var nextButtonEnabled = false;

            if (navigateButtonsEnabled) {
                if (this.indexOfRecord > 0) {
                    previousButtonEnabled = true;
                }

                if (this.indexOfRecord < this.model.collection.total - 1) {
                    nextButtonEnabled = true;
                }
                else {
                    if (this.model.collection.total === -1) {
                        nextButtonEnabled = true;
                    }
                    else if (this.model.collection.total === -2) {
                        if (this.indexOfRecord < this.model.collection.length - 1) {
                            nextButtonEnabled = true;
                        }
                    }
                }

                if (!previousButtonEnabled && !nextButtonEnabled) {
                    navigateButtonsEnabled = false;
                }
            }

            let hasMiddleTabs = this.hasMiddleTabs();
            let middleTabDataList = hasMiddleTabs ? this.getMiddleTabDataList() : [];

            return {
                scope: this.scope,
                entityType: this.entityType,
                buttonList: this.buttonList,
                buttonEditList: this.buttonEditList,
                dropdownItemList: this.dropdownItemList,
                dropdownEditItemList: this.dropdownEditItemList,
                dropdownItemListEmpty: this.isDropdownItemListEmpty(),
                dropdownEditItemListEmpty: this.isDropdownEditItemListEmpty(),
                buttonsDisabled: this.buttonsDisabled,
                name: this.name,
                id: this.id,
                isWide: this.isWide,
                isSmall: this.type === 'editSmall' || this.type === 'detailSmall',
                navigateButtonsEnabled: navigateButtonsEnabled,
                previousButtonEnabled: previousButtonEnabled,
                nextButtonEnabled: nextButtonEnabled,
                hasMiddleTabs: hasMiddleTabs,
                middleTabDataList: middleTabDataList,
            };
        },

        init: function () {
            this.entityType = this.model.name;
            this.scope = this.options.scope || this.entityType;

            this.layoutName = this.options.layoutName || this.layoutName;
            this.detailLayout = this.options.detailLayout || this.detailLayout;

            this.type = this.options.type || this.type;

            this.buttons = this.options.buttons || this.buttons;
            this.buttonList = this.options.buttonList || this.buttonList;
            this.dropdownItemList = this.options.dropdownItemList || this.dropdownItemList;

            this.buttonList = Espo.Utils.cloneDeep(this.buttonList);
            this.buttonEditList = Espo.Utils.cloneDeep(this.buttonEditList);
            this.dropdownItemList = Espo.Utils.cloneDeep(this.dropdownItemList);
            this.dropdownEditItemList = Espo.Utils.cloneDeep(this.dropdownEditItemList);

            this.returnAfterCreate = this.options.returnAfterCreate;

            this.returnUrl = this.options.returnUrl || this.returnUrl;
            this.returnDispatchParams = this.options.returnDispatchParams || this.returnDispatchParams;

            this.exit = this.options.exit || this.exit;

            if (this.shortcutKeys) {
                this.shortcutKeys = Espo.Utils.cloneDeep(this.shortcutKeys);
            }
        },

        isDropdownItemListEmpty: function () {
            if (this.dropdownItemList.length === 0) {
                return true;
            }

            var isEmpty = true;

            this.dropdownItemList.forEach(item => {
                if (!item.hidden) {
                    isEmpty = false;
                }
            });

            return isEmpty;
        },

        isDropdownEditItemListEmpty: function () {
            if (this.dropdownEditItemList.length === 0) {
                return true;
            }

            var isEmpty = true;

            this.dropdownEditItemList.forEach(item => {
                if (!item.hidden) {
                    isEmpty = false;
                }
            });

            return isEmpty;
        },

        setup: function () {
            if (typeof this.model === 'undefined') {
                throw new Error('Model has not been injected into record view.');
            }

            /** @type {module:view-record-helper.Class} */
            this.recordHelper = new ViewRecordHelper(this.defaultFieldStates, this.defaultFieldStates);

            this._initInlineEditSave();

            var collection = this.collection = this.model.collection;

            if (collection) {
                this.listenTo(this.model, 'destroy', () => {
                    collection.remove(this.model.id);
                    collection.trigger('sync', {});
                });

                if ('indexOfRecord' in this.options) {
                    this.indexOfRecord = this.options.indexOfRecord;
                } else {
                    this.indexOfRecord = collection.indexOf(this.model);
                }
            }

            /** @type {Object.<string,*>|null} */
            this.middlePanelDefs = {};

            /** @type {Object.<string,*>[]} */
            this.middlePanelDefsList = [];

            if (this.getUser().isPortal() && !this.portalLayoutDisabled) {
                if (
                    this.getMetadata().get(
                        ['clientDefs', this.scope, 'additionalLayouts', this.layoutName + 'Portal']
                    )
                ) {
                    this.layoutName += 'Portal';
                }
            }

            this.numId = Math.floor((Math.random() * 10000) + 1);

            // For testing purpose.
            $(window).on('fetch-record.' + this.cid, () => this.handleRecordUpdate());

            this.once('remove', () => {
                if (this.isChanged) {
                    this.resetModelChanges();
                }
                this.setIsNotChanged();

                $(window).off('scroll.detail-' + this.numId);
                $(window).off('fetch-record.' + this.cid);
            });

            this.id = Espo.Utils.toDom(this.entityType) + '-' +
                Espo.Utils.toDom(this.type) + '-' + this.numId;

            this.isNew = this.model.isNew();

            if (!this.editModeDisabled) {
                if ('editModeDisabled' in this.options) {
                    this.editModeDisabled = this.options.editModeDisabled;
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
                this.isWide = this.options.isWide;
            }

            if ('sideView' in this.options) {
                this.sideView = this.options.sideView;
            }

            if ('bottomView' in this.options) {
                this.bottomView = this.options.bottomView;
            }

            this.sideDisabled = this.options.sideDisabled || this.sideDisabled;
            this.bottomDisabled = this.options.bottomDisabled || this.bottomDisabled;

            this.readOnly = this.options.readOnly || this.readOnly;

            if (!this.readOnly && !this.isNew) {
                this.readOnly = this.getMetadata()
                    .get(['clientDefs', this.scope, 'editDisabled']) || false;
            }

            if (this.getMetadata().get(['clientDefs', this.scope, 'createDisabled'])) {
                this.duplicateAction = false;
            }

            this.readOnlyLocked = this.readOnly;

            this.inlineEditDisabled = this.inlineEditDisabled ||
                this.getMetadata().get(['clientDefs', this.scope, 'inlineEditDisabled']) ||
                false;

            this.inlineEditDisabled = this.options.inlineEditDisabled || this.inlineEditDisabled;
            this.navigateButtonsDisabled = this.options.navigateButtonsDisabled ||
                this.navigateButtonsDisabled;
            this.portalLayoutDisabled = this.options.portalLayoutDisabled || this.portalLayoutDisabled;
            this.dynamicLogicDefs = this.options.dynamicLogicDefs || this.dynamicLogicDefs;

            this.accessControlDisabled = this.options.accessControlDisabled || this.accessControlDisabled;

            this.setupActionItems();
            this.setupBeforeFinal();

            this.on('after:render', () => {
                this.initElementReferences();
            });

            if (
                !this.isNew &&
                this.getConfig().get('useWebSocket') &&
                this.getMetadata().get(['scopes', this.entityType, 'object'])
            ) {
                this.subscribeToWebSocket();

                this.once('remove', () => {
                    if (this.isSubscribedToWebSocked) {
                        this.unsubscribeFromWebSocket();
                    }
                });
            }

            this.getHelper().processSetupHandlers(this, this.setupHandlerType);

            this.initInlideEditDynamicWithLogicInteroperability();

            this.forcePatchAttributeDependencyMap = this.getMetadata()
                .get(['clientDefs', this.scope, 'forcePatchAttributeDependencyMap']) || {};
        },

        setupBeforeFinal: function () {
            if (!this.accessControlDisabled) {
                this.manageAccess();
            }

            this.attributes = this.model.getClonedAttributes();

            if (this.options.attributes) {
                this.model.set(this.options.attributes);
            }

            this.listenTo(this.model, 'sync', () => {
                this.attributes = this.model.getClonedAttributes();
            });

            this.listenTo(this.model, 'change', (m, o) => {
                if (o.sync) {
                    for (let attribute in m.attributes) {
                        if (!m.hasChanged(attribute)) {
                            continue;
                        }

                        this.attributes[attribute] = Espo.Utils.cloneDeep(
                            m.get(attribute)
                        );
                    }

                    return;
                }

                if (this.mode === 'edit' || this.inlineEditModeIsOn) {
                    this.setIsChanged();
                }
            });

            var dependencyDefs = Espo.Utils.clone(
                this.getMetadata().get(['clientDefs', this.model.name, 'formDependency']) || {}
            );

            this.dependencyDefs = _.extend(dependencyDefs, this.dependencyDefs);

            this.initDependancy();

            var dynamicLogic = Espo.Utils.clone(
                this.getMetadata().get(['clientDefs', this.model.name, 'dynamicLogic']) || {}
            );

            this.dynamicLogicDefs = _.extend(dynamicLogic, this.dynamicLogicDefs);

            this.initDynamicLogic();
            this.setupFieldLevelSecurity();
            this.initDynamicHandler();
        },

        _initInlineEditSave: function () {
            this.listenTo(this.recordHelper, 'inline-edit-save', (field, o) => {
                this.inlineEditSave(field, o);
            });
        },

        /**
         * @param {string} field
         * @param {module:views/record/base~saveOptions} [options]
         */
        inlineEditSave: function (field, options) {
            let view = this.getFieldView(field);

            if (!view) {
                throw new Error(`No field '${field}'.`);
            }

            options = _.extend({
                inline: true,
                field: field,
                afterValidate: () => {
                    if (options.bypassClose) {
                        return;
                    }

                    view.inlineEditClose(true)
                },
            }, options || {});

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

                        view.inlineEdit();
                    }
                });
        },

        initInlideEditDynamicWithLogicInteroperability: function () {
            let blockEdit = false;

            let process = (type, field) => {
                if (!this.inlineEditModeIsOn || this.editModeDisabled) {
                    return;
                }

                if (blockEdit) {
                    return;
                }

                if (type === 'required') {
                    let fieldView = this.getFieldView(field);

                    if (fieldView.validateRequired) {
                        fieldView.suspendValidationMessage();

                        try {
                            if (!fieldView.validateRequired()) {
                                return;
                            }
                        }
                        catch (e) {}
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

            this.on('set-field-required', (field) => process('required', field));
            this.on('set-field-option-list', (field) => process('options', field));
            this.on('reset-field-option-list', (field) => process('options', field));
        },

        initDynamicHandler: function () {
            var dynamicHandlerClassName = this.dynamicHandlerClassName ||
                this.getMetadata().get(['clientDefs', this.scope, 'dynamicHandler']);

            var init = function (dynamicHandler) {
                this.listenTo(this.model, 'change', (model, o) => {
                    if ('onChange' in dynamicHandler) {
                        dynamicHandler.onChange.call(dynamicHandler, model, o);
                    }

                    var changedAttributes = model.changedAttributes();

                    for (let attribute in changedAttributes) {
                        var methodName = 'onChange' + Espo.Utils.upperCaseFirst(attribute);

                        if (methodName in dynamicHandler) {
                            dynamicHandler[methodName]
                                .call(dynamicHandler, model, changedAttributes[attribute], o);
                        }
                    }
                });

                if ('init' in dynamicHandler) {
                    dynamicHandler.init();
                }
            }.bind(this);

            if (dynamicHandlerClassName) {
                this.wait(
                    new Promise(resolve => {
                        require(dynamicHandlerClassName, (DynamicHandler) => {
                            var dynamicHandler = this.dynamicHandler = new DynamicHandler(this);

                            init(dynamicHandler);

                            resolve();
                        });
                    })
                );
            }

            var handlerList = this.getMetadata().get(['clientDefs', this.scope, 'dynamicHandlerList']) || [];

            if (handlerList.length) {
                var self = this;

                var promiseList = [];

                handlerList.forEach((className) => {
                    promiseList.push(
                        new Promise(resolve => {
                            require(className, (DynamicHandler) => {
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
        },

        setupFinal: function () {
            this.build();

            if (this.shortcutKeys && this.options.shortcutKeysEnabled) {
                this.events['keydown.record-detail'] = e => {
                    let key = Espo.Utils.getKeyFromKeyEvent(e);

                    if (typeof this.shortcutKeys[key] === 'function') {
                        this.shortcutKeys[key].call(this, e);

                        return;
                    }

                    let actionName = this.shortcutKeys[key];

                    if (!actionName) {
                        return;
                    }

                    e.preventDefault();
                    e.stopPropagation();

                    let methodName = 'action' + Espo.Utils.upperCaseFirst(actionName);

                    this[methodName]();
                };
            }

            if (!this.options.focusForCreate) {
                this.once('after:render', () => this.focusOnFirstDiv());
            }
        },

        setIsChanged: function () {
            this.isChanged = true;

            if (this.confirmLeaveDisabled) {
                return;
            }

            this.setConfirmLeaveOut(true);
        },

        setIsNotChanged: function () {
            this.isChanged = false;

            if (this.confirmLeaveDisabled) {
                return;
            }

            this.setConfirmLeaveOut(false);
        },

        switchToModelByIndex: function (indexOfRecord) {
            var collection = this.model.collection || this.collection;

            if (!collection) {
                return;
            }

            var model = collection.at(indexOfRecord);

            if (!model) {
                throw new Error("Model is not found in collection by index.");
            }

            var id = model.id;

            var scope = model.name || this.scope;

            var url;

            if (this.mode === 'edit') {
                url = '#' + scope + '/edit/' + id;
            } else {
                url = '#' + scope + '/view/' + id;
            }

            this.getRouter().navigate('#' + scope + '/view/' + id, {trigger: false});

            this.getRouter().dispatch(scope, 'view', {
                id: id,
                model: model,
                indexOfRecord: indexOfRecord,
                rootUrl: this.options.rootUrl,
            });
        },

        actionPrevious: function () {
            this.model.abortLastFetch();

            var collection;

            if (!this.model.collection) {
                collection = this.collection;

                if (!collection) {
                    return;
                }

                this.indexOfRecord--;

                if (this.indexOfRecord < 0) {
                    this.indexOfRecord = 0;
                }
            }
            else {
                collection = this.model.collection;
            }

            if (!(this.indexOfRecord > 0)) {
                return;
            }

            var indexOfRecord = this.indexOfRecord - 1;

            this.switchToModelByIndex(indexOfRecord);
        },

        actionNext: function () {
            this.model.abortLastFetch();

            var collection;

            if (!this.model.collection) {
                collection = this.collection;

                if (!collection) {
                    return;
                }

                this.indexOfRecord--;

                if (this.indexOfRecord < 0) {
                    this.indexOfRecord = 0;
                }
            }
            else {
                collection = this.model.collection;
            }

            if (!(this.indexOfRecord < collection.total - 1) && collection.total >= 0) {
                return;
            }

            if (collection.total === -2 && this.indexOfRecord >= collection.length - 1) {
                return;
            }

            var indexOfRecord = this.indexOfRecord + 1;

            if (indexOfRecord <= collection.length - 1) {
                this.switchToModelByIndex(indexOfRecord);
            }
            else {
                collection
                    .fetch({
                        more: true,
                        remove: false,
                    })
                    .then(() => {
                        this.switchToModelByIndex(indexOfRecord);
                    });
            }
        },

        actionViewPersonalData: function () {
            this.createView('viewPersonalData', 'views/personal-data/modals/personal-data', {
                model: this.model
            }, function (view) {
                view.render();

                this.listenToOnce(view, 'erase', () => {
                    this.clearView('viewPersonalData');
                    this.model.fetch();
                });
            });
        },

        actionViewFollowers: function (data) {
            var viewName = this.getMetadata().get(
                    ['clientDefs', this.model.name, 'relationshipPanels', 'followers', 'viewModalView']
                ) ||
                this.getMetadata().get(['clientDefs', 'User', 'modalViews', 'relatedList']) ||
                'views/modals/followers-list';

            var selectDisabled =
                !this.getUser().isAdmin() &&
                this.getAcl().get('followerManagementPermission') === 'no' &&
                this.getAcl().get('portalPermission') === 'no';

            var options = {
                model: this.model,
                link: 'followers',
                scope: 'User',
                title: this.translate('Followers'),
                filtersDisabled: true,
                url: this.model.entityType + '/' + this.model.id + '/followers',
                createDisabled: true,
                selectDisabled: selectDisabled,
                rowActionsView: 'views/user/record/row-actions/relationship-followers',
            };

            if (data.viewOptions) {
                for (let item in data.viewOptions) {
                    options[item] = data.viewOptions[item];
                }
            }

            Espo.Ui.notify(this.translate('loading', 'messages'));

            this.createView('modalRelatedList', viewName, options, (view) => {
                Espo.Ui.notify(false);

                view.render();

                this.listenTo(view, 'action', (action, data, e) => {
                    var method = 'action' + Espo.Utils.upperCaseFirst(action);

                    if (typeof this[method] === 'function') {
                        this[method](data, e);

                        e.preventDefault();
                    }
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
        },

        actionPrintPdf: function () {
            this.createView('pdfTemplate', 'views/modals/select-template', {
                entityType: this.model.name,
            }, (view) => {
                view.render();

                this.listenToOnce(view, 'select', (model) => {
                    this.clearView('pdfTemplate');

                    window.open(
                        '?entryPoint=pdf&entityType=' +
                        this.model.name+'&entityId=' +
                        this.model.id+'&templateId=' + model.id, '_blank'
                    );
                });
            });
        },

        afterSave: function () {
            if (this.isNew) {
                this.notify('Created', 'success');
            }
            else {
                this.notify('Saved', 'success');
            }

            this.enableActionItems();

            this.setIsNotChanged();

            setTimeout(() => {
                this.unblockUpdateWebSocket();
            }, this.blockUpdateWebSocketPeriod || 500);
        },

        beforeSave: function () {
            this.notify('Saving...');

            this.blockUpdateWebSocket();
        },

        beforeBeforeSave: function () {
            this.disableActionItems();
        },

        afterSaveError: function () {
            this.enableActionItems();
        },

        afterNotModified: function () {
            var msg = this.translate('notModified', 'messages');

            Espo.Ui.warning(msg, 'warning');

            this.enableActionItems();
            this.setIsNotChanged();
        },

        afterNotValid: function () {
            this.notify('Not valid', 'error');

            this.enableActionItems();
        },

        errorHandlerDuplicate: function (duplicates) {
            this.notify(false);

            this.createView('duplicate', 'views/modals/duplicate', {
                scope: this.entityType,
                duplicates: duplicates,
                model: this.model,
            }, (view) => {
                view.render();

                this.listenToOnce(view, 'save', () => {
                    this.actionSave({
                        options: {
                            headers: {
                                'X-Skip-Duplicate-Check': 'true',
                            }
                        }
                    });
                });
            });
        },

        errorHandlerModified: function (data, options) {
            Espo.Ui.notify(false);

            var versionNumber = data.versionNumber;

            var values = data.values || {};

            var attributeList = Object.keys(values);

            var diffAttributeList = [];

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

            this.createView(
                'dialog',
                'views/modals/resolve-save-conflict',
                {
                    model: this.model,
                    attributeList: diffAttributeList,
                    currentAttributes: Espo.Utils.cloneDeep(this.model.attributes),
                    originalAttributes: Espo.Utils.cloneDeep(this.attributes),
                    actualAttributes: Espo.Utils.cloneDeep(values),
                }
            )
            .then(view => {
                view.render();

                this.listenTo(view, 'resolve', () => {
                    this.model.set('versionNumber', versionNumber, {silent: true});
                    this.attributes.versionNumber = versionNumber;

                    for (let attribute in values) {
                        this.setInitialAttributeValue(attribute, values[attribute]);
                    }
                });
            });
        },

        /**
         * Get the middle-view.
         *
         * @return {module:views/record/detail-middle.Class}
         */
        getMiddleView: function () {
            return this.getView('middle');
        },

        setReadOnly: function () {
            if (!this.readOnlyLocked) {
                this.readOnly = true;
            }

            var bottomView = this.getView('bottom');

            if (bottomView && 'setReadOnly' in bottomView) {
                bottomView.setReadOnly();
            }

            var sideView = this.getView('side');

            if (sideView && 'setReadOnly' in sideView) {
                sideView.setReadOnly();
            }

            this.getFieldList().forEach((field) => {
                this.setFieldReadOnly(field);
            });
        },

        setNotReadOnly: function (onlyNotSetAsReadOnly) {
            if (!this.readOnlyLocked) {
                this.readOnly = false;
            }

            var bottomView = this.getView('bottom');

            if (bottomView && 'setNotReadOnly' in bottomView) {
                bottomView.setNotReadOnly(onlyNotSetAsReadOnly);
            }

            var sideView = this.getView('side');

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
        },

        manageAccessEdit: function (second) {
            if (this.isNew) return;

            var editAccess = this.getAcl().checkModel(this.model, 'edit', true);

            if (!editAccess || this.readOnlyLocked) {
                this.readOnly = true;

                this.hideActionItem('edit');

                if (this.duplicateAction) {
                    this.hideActionItem('duplicate');
                }

                if (this.selfAssignAction) {
                    this.hideActionItem('selfAssign');
                }
            } else {
                this.showActionItem('edit');

                if (this.duplicateAction) {
                    this.showActionItem('duplicate');
                }

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
                        }
                        else {
                            this.on('ready', () => this.setNotReadOnly(true));
                        }
                    }

                    this.readOnly = false;
                }
            }

            if (editAccess === null) {
                this.listenToOnce(this.model, 'sync', () => {
                    this.manageAccessEdit(true);
                });
            }
        },

        manageAccessDelete: function (second) {
            if (this.isNew) {
                return;
            }

            var deleteAccess = this.getAcl().checkModel(this.model, 'delete', true);

            if (!deleteAccess) {
                this.hideActionItem('delete');
            } else {
                this.showActionItem('delete');
            }

            if (deleteAccess === null) {
                this.listenToOnce(this.model, 'sync', () => {
                    this.manageAccessDelete(true);
                });
            }
        },

        manageAccessStream: function (second) {
            if (this.isNew) {
                return;
            }

            if (
                ~['no', 'own'].indexOf(this.getAcl().getLevel('User', 'read'))
                &&
                this.getAcl().get('portalPermission') === 'no'
            ) {
                this.hideActionItem('viewFollowers');

                return;
            }

            var streamAccess = this.getAcl().checkModel(this.model, 'stream', true);

            if (!streamAccess) {
                this.hideActionItem('viewFollowers');
            } else {
                this.showActionItem('viewFollowers');
            }

            if (streamAccess === null) {
                this.listenToOnce(this.model, 'sync', () => {
                    this.manageAccessStream(true);
                });
            }
        },

        manageAccess: function () {
            this.manageAccessEdit();
            this.manageAccessDelete();
            this.manageAccessStream();
        },

        addButton: function (o, toBeginning) {
            let method = toBeginning ? 'unshift' : 'push';

            let name = o.name;

            if (!name) {
                return;
            }

            for (let item of this.buttonList) {
                if (item.name === name) {
                    return;
                }
            }

            this.buttonList[method](o);
        },

        addDropdownItem: function (o, toBeginning) {
            let method = toBeginning ? 'unshift' : 'push';

            if (!o) {
                this.dropdownItemList[method](false);

                return;
            }

            let name = o.name;

            if (!name) {
                return;
            }

            for (let item of this.dropdownItemList) {
                if (item.name === name) {
                    return;
                }
            }

            this.dropdownItemList[method](o);
        },

        addButtonEdit: function (o, toBeginning) {
            let method = toBeginning ? 'unshift' : 'push';

            let name = o.name;

            if (!name) {
                return;
            }

            for (let item of this.buttonEditList) {
                if (item.name === name) {
                    return;
                }
            }

            this.buttonEditList[method](o);
        },

        /**
         * @deprecated Use `enableActionItems`.
         */
        enableButtons: function () {
            this.allActionItemsDisabled = false;

            this.$el.find(".button-container .actions-btn-group .action")
                .removeAttr('disabled')
                .removeClass('disabled');

            this.$el.find(".button-container .actions-btn-group .dropdown-toggle")
                .removeAttr('disabled')
                .removeClass('disabled');

            this.buttonList
                .filter(item => item.disabled)
                .forEach(item => {
                    this.$detailButtonContainer
                        .find(`button.action[data-action="${item.name}"]`)
                        .addClass('disabled')
                        .attr('disabled', 'disabled');
                });

            this.buttonEditList
                .filter(item => item.disabled)
                .forEach(item => {
                    this.$detailButtonContainer
                        .find(`button.action[data-action="${item.name}"]`)
                        .addClass('disabled')
                        .attr('disabled', 'disabled');
                });

            this.dropdownItemList
                .filter(item => item.disabled)
                .forEach(item => {
                    this.$detailButtonContainer
                        .find(`li > .action[data-action="${item.name}"]`)
                        .parent()
                        .addClass('disabled')
                        .attr('disabled', 'disabled');
                });

            this.dropdownEditItemList
                .filter(item => item.disabled)
                .forEach(item => {
                    this.$detailButtonContainer
                        .find(`li > .action[data-action="${item.name}"]`)
                        .parent()
                        .addClass('disabled')
                        .attr('disabled', 'disabled');
                });
        },

        /**
         * @deprecated Use `disableActionItems`.
         */
        disableButtons: function () {
            this.allActionItemsDisabled = true;

            this.$el.find(".button-container .actions-btn-group .action")
                .attr('disabled', 'disabled')
                .addClass('disabled');

            this.$el.find(".button-container .actions-btn-group .dropdown-toggle")
                .attr('disabled', 'disabled')
                .addClass('disabled');
        },

        /**
         * Remove a button or dropdown item.
         *
         * @param {string} name A name.
         */
        removeActionItem: function (name) {
            this.removeButton(name);
        },

        /**
         * @deprecated Use `removeActionItem`.
         *
         * @param {string} name A name.
         */
        removeButton: function (name) {
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

            if (this.isRendered()) {
            	this.$el.find('.detail-button-container .action[data-action="'+name+'"]').remove();
            }
        },

        /**
         * Convert a detail layout to an internal layout.
         *
         * @protected
         * @param {Object[]} simplifiedLayout A detail layout.
         * @return {Object[]}
         */
        convertDetailLayout: function (simplifiedLayout) {
            let layout = [];
            let el = this.options.el || '#' + (this.id);

            this.panelFieldListMap = {};

            let tabNumber = -1;

            for (let p = 0; p < simplifiedLayout.length; p++) {
                let item = simplifiedLayout[p];

                let panel = {};

                let tabBreak = item.tabBreak || p === 0;

                if (tabBreak) {
                    tabNumber++;
                }

                if ('customLabel' in item) {
                    panel.label = item.customLabel;

                    if (panel.label) {
                        panel.label = this.getLanguage()
                            .translate(panel.label, 'panelCustomLabels', this.entityType);
                    }
                } else {
                    panel.label = item.label || null;

                    if (panel.label) {
                        panel.label = this.getLanguage()
                            .translate(panel.label, 'labels', this.entityType);
                    }
                }

                panel.name = item.name || 'panel-' + p.toString();
                panel.style = item.style || 'default';
                panel.rows = [];
                panel.tabNumber = tabNumber;

                this.middlePanelDefs[panel.name] = {
                    name: panel.name,
                    style: panel.style,
                    tabNumber: panel.tabNumber,
                    tabBreak: tabBreak,
                    tabLabel: item.tabLabel,
                };

                this.middlePanelDefsList.push(this.middlePanelDefs[panel.name]);

                if (item.dynamicLogicVisible && this.dynamicLogic) {
                    this.dynamicLogic.addPanelVisibleCondition(panel.name, item.dynamicLogicVisible);
                }

                if (simplifiedLayout[p].dynamicLogicStyled && this.dynamicLogic) {
                    this.dynamicLogic.addPanelStyledCondition(panel.name, item.dynamicLogicStyled);
                }

                if (item.hidden && tabNumber === 0) {
                    panel.hidden = true;

                    this.hidePanel(panel.name);

                    this.underShowMoreDetailPanelList = this.underShowMoreDetailPanelList || [];
                    this.underShowMoreDetailPanelList.push(panel.name);
                }

                let lType = 'rows';

                if (item.columns) {
                    lType = 'columns';

                    panel.columns = [];
                }

                if (panel.name) {
                    this.panelFieldListMap[panel.name] = [];
                }

                for (const [i, itemI] of item[lType].entries()) {
                    let row = [];

                    for (const cellDefs of itemI) {
                        if (cellDefs === false) {
                            row.push(false);

                            continue;
                        }

                        if (!cellDefs.name) {
                            continue;
                        }

                        let name = cellDefs.name;

                        if (panel.name) {
                            this.panelFieldListMap[panel.name].push(name);
                        }

                        let type = cellDefs.type || this.model.getFieldType(name) || 'base';

                        let viewName = cellDefs.view ||
                            this.model.getFieldParam(name, 'view') ||
                            this.getFieldManager().getViewName(type);

                        let o = {
                            el: el + ' .middle .field[data-name="' + name + '"]',
                            defs: {
                                name: name,
                                params: cellDefs.params || {},
                            },
                            mode: this.fieldsMode,
                        };

                        if (this.readOnly) {
                            o.readOnly = true;
                        }

                        if (cellDefs.readOnly) {
                            o.readOnly = true;
                            o.readOnlyLocked = true;
                        }

                        if (this.readOnlyLocked) {
                            o.readOnlyLocked = true;
                        }

                        if (this.inlineEditDisabled || cellDefs.inlineEditDisabled) {
                            o.inlineEditDisabled = true;
                        }

                        let fullWidth = cellDefs.fullWidth || false;

                        if (!fullWidth) {
                            if (item[lType][i].length === 1) {
                                fullWidth = true;
                            }
                        }

                        if (this.recordHelper.getFieldStateParam(name, 'hidden')) {
                            o.disabled = true;
                        }

                        if (this.recordHelper.getFieldStateParam(name, 'hiddenLocked')) {
                            o.disabledLocked = true;
                        }

                        if (this.recordHelper.getFieldStateParam(name, 'readOnly')) {
                            o.readOnly = true;
                        }

                        if (!o.readOnlyLocked && this.recordHelper.getFieldStateParam(name, 'readOnlyLocked')) {
                            o.readOnlyLocked = true;
                        }

                        if (this.recordHelper.getFieldStateParam(name, 'required') !== null) {
                            o.defs.params = o.defs.params || {};
                            o.defs.params.required = this.recordHelper.getFieldStateParam(name, 'required');
                        }

                        if (this.recordHelper.hasFieldOptionList(name)) {
                            o.customOptionList = this.recordHelper.getFieldOptionList(name);
                        }

                        o.validateCallback = () => this.validateField(name);

                        o.recordHelper = this.recordHelper;

                        if (cellDefs.options) {
                            for (let optionName in cellDefs.options) {
                                if (typeof o[optionName] !== 'undefined') {
                                    continue;
                                }

                                o[optionName] = cellDefs.options[optionName];
                            }
                        }

                        let cell = {
                            name: name + 'Field',
                            view: viewName,
                            field: name,
                            el: el + ' .middle .field[data-name="' + name + '"]',
                            fullWidth: fullWidth,
                            options: o,
                        };

                        if ('labelText' in cellDefs) {
                            o.labelText = cellDefs.labelText;
                            cell.customLabel = cellDefs.labelText;
                        }

                        if ('customLabel' in cellDefs) {
                            cell.customLabel = cellDefs.customLabel;
                        }

                        if ('label' in cellDefs) {
                            cell.label = cellDefs.label;
                        }

                        if ('customCode' in cellDefs) {
                            cell.customCode = cellDefs.customCode;
                        }

                        if ('noLabel' in cellDefs) {
                            cell.noLabel = cellDefs.noLabel;
                        }

                        if ('span' in cellDefs) {
                            cell.span = cellDefs.span;
                        }

                        row.push(cell);
                    }

                    panel[lType].push(row);
                }

                layout.push(panel);
            }

            return layout;
        },

        /**
         * @private
         * @param {function(Object[]): void}callback
         */
        getGridLayout: function (callback) {
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

            this.getHelper().layoutManager.get(this.model.name, this.layoutName, detailLayout => {
                if (typeof this.modifyDetailLayout === 'function') {
                    detailLayout = Espo.Utils.cloneDeep(detailLayout);

                    this.modifyDetailLayout(detailLayout);
                }

                this.detailLayout = detailLayout;

                this.gridLayout = {
                    type: this.gridLayoutType,
                    layout: this.convertDetailLayout(this.detailLayout),
                };

                callback(this.gridLayout);
            });
        },

        /**
         * Create a side view.
         *
         * @protected
         */
        createSideView: function () {
            var el = this.options.el || '#' + (this.id);

            this.createView('side', this.sideView, {
                model: this.model,
                scope: this.scope,
                el: el + ' .side',
                type: this.type,
                readOnly: this.readOnly,
                inlineEditDisabled: this.inlineEditDisabled,
                recordHelper: this.recordHelper,
                recordViewObject: this,
            });
        },

        /**
         * Create a middle view.
         *
         * @protected
         */
        createMiddleView: function (callback) {
            var el = this.options.el || '#' + (this.id);

            this.waitForView('middle');

            this.getGridLayout((layout) => {
                this.createView('middle', this.middleView, {
                    model: this.model,
                    scope: this.scope,
                    type: this.type,
                    _layout: layout,
                    el: el + ' .middle',
                    layoutData: {
                        model: this.model,
                    },
                    recordHelper: this.recordHelper,
                    recordViewObject: this,
                    panelFieldListMap: this.panelFieldListMap,
                }, callback);
            });
        },

        /**
         * Create a bottom view.
         *
         * @protected
         */
        createBottomView: function () {
            var el = this.options.el || '#' + (this.id);

            this.createView('bottom', this.bottomView, {
                model: this.model,
                scope: this.scope,
                el: el + ' .bottom',
                readOnly: this.readOnly,
                type: this.type,
                inlineEditDisabled: this.inlineEditDisabled,
                recordHelper: this.recordHelper,
                recordViewObject: this,
                portalLayoutDisabled: this.portalLayoutDisabled,
            });
        },

        /**
         * Create views.
         *
         * @protected
         * @param {function(module:views/record/detail-middle): void} [callback]
         */
        build: function (callback) {
            if (!this.sideDisabled && this.sideView) {
                this.createSideView();
            }

            if (this.middleView) {
                this.createMiddleView(callback);
            }

            if (!this.bottomDisabled && this.bottomView) {
                this.createBottomView();
            }
        },

        /**
         * Called after create.
         *
         * @return {boolean} True if redirecting is processed.
         */
        exitAfterCreate: function () {
            if (!this.returnAfterCreate && this.model.id) {
                var url = '#' + this.scope + '/view/' + this.model.id;

                this.getRouter().navigate(url, {trigger: false});

                this.getRouter().dispatch(this.scope, 'view', {
                    id: this.model.id,
                    rootUrl: this.options.rootUrl,
                    model: this.model,
                });

                return true;
            }

            return false;
        },

        /**
         * Called after save or cancel. By default, redirects a page. Can be overridden in options.
         *
         * @param {string} after Name of an action (`save`, `cancel,` etc.) after which #exit is invoked.
         */
        exit: function (after) {
            if (after) {
                var methodName = 'exitAfter' + Espo.Utils.upperCaseFirst(after);

                if (methodName in this) {
                    var result = this[methodName]();

                    if (result) {
                        return;
                    }
                }
            }

            var url;
            var options;

            if (this.returnUrl) {
                url = this.returnUrl;
            }
            else {
                if (after === 'delete') {
                    url = this.options.rootUrl || '#' + this.scope;

                    this.getRouter().navigate(url, {trigger: false});

                    this.getRouter().dispatch(this.scope, null, {
                        isReturn: true
                    });

                    return;
                }
                if (this.model.id) {
                    url = '#' + this.scope + '/view/' + this.model.id;

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
                }
                else {
                    url = this.options.rootUrl || '#' + this.scope;
                }
            }

            if (this.returnDispatchParams) {
                var controller = this.returnDispatchParams.controller;
                var action = this.returnDispatchParams.action;
                options = this.returnDispatchParams.options || {};

                this.getRouter().navigate(url, {trigger: false});
                this.getRouter().dispatch(controller, action, options);

                return;
            }

            this.getRouter().navigate(url, {trigger: true});
        },

        subscribeToWebSocket: function () {
            var topic = 'recordUpdate.' + this.entityType + '.' + this.model.id;

            this.recordUpdateWebSocketTopic = topic;
            this.isSubscribedToWebSocked = true;

            this.getHelper().webSocketManager.subscribe(topic, (t, data) => {
                this.handleRecordUpdate();
            });
        },

        unsubscribeFromWebSocket: function () {
            if (!this.isSubscribedToWebSocked) {
                return;
            }

            this.getHelper().webSocketManager.unsubscribe(this.recordUpdateWebSocketTopic);
        },

        handleRecordUpdate: function () {
            if (this.updateWebSocketIsBlocked) {
                return;
            }

            if (this.inlineEditModeIsOn || this.mode === 'edit') {
                var m = this.model.clone();

                m.fetch().then(() => {
                    if (this.inlineEditModeIsOn || this.mode === 'edit') {
                        this.updatedAttributes = Espo.Utils.cloneDeep(m.attributes);
                    }
                });

                return;
            }

            this.model.fetch({highlight: true});
        },

        blockUpdateWebSocket: function (toUnblock) {
            this.updateWebSocketIsBlocked = true;

            if (toUnblock) {
                setTimeout(() => {
                    this.unblockUpdateWebSocket();
                }, this.blockUpdateWebSocketPeriod || 500);
            }
        },

        unblockUpdateWebSocket: function () {
            this.updateWebSocketIsBlocked = false;
        },

        /**
         * Show more detail panels.
         */
        showMoreDetailPanels: function () {
            this.hidePanel('showMoreDelimiter');

            this.underShowMoreDetailPanelList.forEach(item => {
                this.showPanel(item);
            });
        },

        /**
         * @protected
         * @return {Number}
         */
        getMiddleTabCount: function () {
            if (!this.hasMiddleTabs()) {
                return 0;
            }

            let count = 1;

            (this.detailLayout || []).forEach(item => {
                if (item.tabBreak) {
                    count ++;
                }
            });

            return count;
        },

        /**
         * @protected
         * @return {boolean}
         */
        hasMiddleTabs: function () {
            if (typeof this._hasMiddleTabs !== 'undefined') {
                return this._hasMiddleTabs;
            }

            if (!this.detailLayout) {
                return false;
            }

            for (let item of this.detailLayout) {
                if (item.tabBreak) {
                    this._hasMiddleTabs = true;

                    return true;
                }
            }

            this._hasMiddleTabs = false;

            return false;
        },

        /**
         * @protected
         * @return {{label: string}[]}
         */
        getMiddleTabDataList: function () {
            let currentTab = this.currentMiddleTab;

            let panelDataList = this.middlePanelDefsList;

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
                    }
                    else if (label.substring(0, 7) === '$label:') {
                        label = this.translate(label.substring(7), 'labels', this.scope);
                    }
                    else if (label[0] === '$') {
                        label = this.translate(label.substring(1), 'tabs', this.scope);
                    }

                    return {
                        label: label,
                        isActive: currentTab === i,
                        hidden: hidden,
                    };
                });
        },

        /**
         * Select a tab.
         *
         * @protected
         * @param {Number} tab
         */
        selectMiddleTab: function (tab) {
            this.currentMiddleTab = tab;

            $('.popover.in').removeClass('in');

            this.$el.find('.middle-tabs > button').removeClass('active');
            this.$el.find(`.middle-tabs > button[data-tab="${tab}"]`).addClass('active');

            this.$el.find('.middle > .panel[data-tab]').addClass('tab-hidden');
            this.$el.find(`.middle > .panel[data-tab="${tab}"]`).removeClass('tab-hidden');

            this.recordHelper.trigger('panel-show');

            this.adjustMiddlePanels();
        },

        /**
         * @inheritDoc
          */
        onInvalid: function (invalidFieldList) {
            if (!this.hasMiddleTabs()) {
                return;
            }

            let tabList = [];

            for (let field of invalidFieldList) {
                let view = this.getMiddleView().getFieldView(field);

                if (!view) {
                    continue;
                }

                let tabString = view.$el
                    .closest('.panel.tab-hidden')
                    .attr('data-tab');

                let tab = parseInt(tabString);

                if (tabList.indexOf(tab) !== -1) {
                    continue;
                }

                tabList.push(tab);
            }

            if (!tabList.length) {
                return;
            }

            let $tabs = this.$el.find('.middle-tabs');

            tabList.forEach(tab => {
                let $tab = $tabs.find(`> [data-tab="${tab.toString()}"]`);

                $tab.addClass('invalid');

                $tab.one('click', () => {
                    $tab.removeClass('invalid');
                });
            })
        },

        /**
         * @private
         */
        controlMiddleTabVisibilityShow: function (tab) {
            if (!this.hasMiddleTabs() || tab === 0) {
                return;
            }

            if (this.isBeingRendered()) {
                this.once('after:render', () => this.controlMiddleTabVisibilityShow(tab));

                return;
            }

            this.$el.find(`.middle-tabs > [data-tab="${tab.toString()}"]`).removeClass('hidden');
        },

        /**
         * @private
         */
        controlMiddleTabVisibilityHide: function (tab) {
            if (!this.hasMiddleTabs() || tab === 0) {
                return;
            }

            if (this.isBeingRendered()) {
                this.once('after:render', () => this.controlMiddleTabVisibilityHide(tab));

                return;
            }

            let panelList = this.middlePanelDefsList.filter(panel => panel.tabNumber === tab);

            let allIsHidden = panelList
                .findIndex(panel => !this.recordHelper.getPanelStateParam(panel.name, 'hidden')) === -1;

            if (!allIsHidden) {
                return;
            }

            let $tab = this.$el.find(`.middle-tabs > [data-tab="${tab.toString()}"]`);

            $tab.addClass('hidden');

            if (this.currentMiddleTab === tab) {
                this.selectMiddleTab(0);
            }
        },

        /**
         * @private
         */
        adjustMiddlePanels: function () {
            if (!this.isRendered() || !this.$middle.length) {
                return;
            }

            let $panels = this.$middle.find('> .panel');
            let $bottomPanels = this.$bottom ? this.$bottom.find('> .panel') : null;

            $panels
                .removeClass('first')
                .removeClass('last')
                .removeClass('in-middle');

            let $visiblePanels = $panels.filter(`:not(.tab-hidden):not(.hidden)`)

            $visiblePanels.each((i, el) => {
                let $el = $(el);

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
        },

        /**
         * @private
         */
        adjustButtons: function () {
            let $buttons = this.$detailButtonContainer.filter('.record-buttons').find('button.btn');

            $buttons
                .removeClass('radius-left')
                .removeClass('radius-right');

            let $buttonsVisible = $buttons.filter('button:not(.hidden)');

            $buttonsVisible.first().addClass('radius-left');
            $buttonsVisible.last().addClass('radius-right');

            this.adjustEditButtons();
        },

        /**
         * @private
         */
        adjustEditButtons: function () {
            let $buttons = this.$detailButtonContainer.filter('.edit-buttons').find('button.btn');

            $buttons
                .removeClass('radius-left')
                .removeClass('radius-right');

            let $buttonsVisible = $buttons.filter('button:not(.hidden)');

            $buttonsVisible.first().addClass('radius-left');
            $buttonsVisible.last().addClass('radius-right');
        },

        /**
         * @private
         */
        initElementReferences() {
            if (this.$detailButtonContainer && this.$detailButtonContainer.length) {
                return;
            }

            this.$detailButtonContainer = this.$el.find('.detail-button-container');

            this.$dropdownItemListButton = this.$detailButtonContainer
                .find('.dropdown-item-list-button');

            this.$dropdownEditItemListButton = this.$detailButtonContainer
                .find('.dropdown-edit-item-list-button');
        },

        /**
         * @protected
         */
        focusForEdit: function () {
            this.$el
                .find('.field:not(.hidden) .form-control:not([disabled])')
                .first()
                .focus();
        },

        /**
         * @protected
         */
        focusForCreate: function () {
            this.$el
                .find('.form-control:not([disabled])')
                .first()
                .focus();
        },

        /**
         * @protected
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyCtrlEnter: function (e) {
            let action = this.shortcutKeyCtrlEnterAction;

            if (this.inlineEditModeIsOn || this.buttonsDisabled || !action) {
                return;
            }

            if (this.mode !== this.MODE_EDIT) {
                return;
            }

            if (!this.hasAvailableActionItem(action)) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            let methodName = 'action' + Espo.Utils.upperCaseFirst(action);

            this[methodName]();
        },

        /**
         * @protected
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyCtrlS: function (e) {
            if (this.inlineEditModeIsOn || this.buttonsDisabled) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

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
        },

        /**
         * @protected
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyCtrlSpace: function (e) {
            if (this.inlineEditModeIsOn || this.buttonsDisabled) {
                return;
            }

            if (this.type !== this.TYPE_DETAIL || this.mode !== this.MODE_DETAIL) {
                return;
            }

            if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') {
                return;
            }

            if (!this.hasAvailableActionItem('edit')) {
                return;
            }

            $(e.currentTarget)

            e.preventDefault();
            e.stopPropagation();

            this.actionEdit();

            if (!this.editModeDisabled) {
                setTimeout(() => this.focusForEdit(), 200);
            }
        },

        /**
         * @protected
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyEscape: function (e) {
            if (this.inlineEditModeIsOn || this.buttonsDisabled) {
                return;
            }

            if (this.type !== this.TYPE_DETAIL || this.mode !== this.MODE_EDIT) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            // Fetching a currently edited form element.
            this.model.set(this.fetch());

            if (this.isChanged) {
                this.confirm(this.translate('confirmLeaveOutMessage', 'messages'))
                    .then(() => this.actionCancelEdit());

                return;
            }

            this.actionCancelEdit();
        },

        /**
         * @protected
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyCtrlAltEnter: function (e) {},

        /**
         * @public
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyControlBackslash: function (e) {
            if (!this.hasMiddleTabs()) {
                return;
            }

            let $buttons = this.$el.find('.middle-tabs > button:not(.hidden)');

            if ($buttons.length === 1) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            let index = $buttons.toArray().findIndex(el => $(el).hasClass('active'));

            index++;

            if (index >= $buttons.length) {
                index = 0;
            }

            let $tab = $($buttons.get(index));

            let tab = parseInt($tab.attr('data-tab'));

            this.selectMiddleTab(tab);

            if (this.mode === this.MODE_EDIT) {
                setTimeout(() => {
                    this.$middle
                        .find(`.panel[data-tab="${tab}"] .cell:not(.hidden)`)
                        .first()
                        .focus();
                }, 50);

                return;
            }

            this.$el
                .find(`.middle-tabs button[data-tab="${tab}"]`)
                .focus();
        },

        /**
         * @protected
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyControlArrowLeft: function (e) {
            if (this.inlineEditModeIsOn || this.buttonsDisabled) {
                return;
            }

            if (this.navigateButtonsDisabled) {
                return;
            }

            if (this.type !== this.TYPE_DETAIL || this.mode !== this.MODE_DETAIL) {
                return;
            }

            let $button = this.$el.find('button[data-action="previous"]');

            if (!$button.length || $button.hasClass('disabled')) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            this.actionPrevious();
        },

        /**
         * @protected
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyControlArrowRight: function (e) {
            if (this.inlineEditModeIsOn || this.buttonsDisabled) {
                return;
            }

            if (this.navigateButtonsDisabled) {
                return;
            }

            if (this.type !== this.TYPE_DETAIL || this.mode !== this.MODE_DETAIL) {
                return;
            }

            let $button = this.$el.find('button[data-action="next"]');

            if (!$button.length || $button.hasClass('disabled')) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            this.actionNext();
        },
    });
});

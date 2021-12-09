/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/record/detail', ['views/record/base', 'view-record-helper'], function (Dep, ViewRecordHelper) {

    return Dep.extend({

        template: 'record/detail',

        type: 'detail',

        name: 'detail',

        layoutName: 'detail',

        fieldsMode: 'detail',

        mode: 'detail',

        gridLayout: null,

        detailLayout: null,

        buttonsDisabled: false,

        scope: null,

        isNew: false,

        buttonList: [
            {
                name: 'edit',
                label: 'Edit',
            }
        ],

        dropdownItemList: [
            {
                name: 'delete',
                label: 'Remove'
            }
        ],

        buttonEditList: [
            {
                name: 'save',
                label: 'Save',
                style: 'primary',
                edit: true
            },
            {
                name: 'cancelEdit',
                label: 'Cancel',
                edit: true
            }
        ],

        dropdownEditItemList: [],

        id: null,

        returnUrl: null,

        returnDispatchParams: null,

        middleView: 'views/record/detail-middle',

        sideView: 'views/record/detail-side',

        bottomView: 'views/record/detail-bottom',

        sideDisabled: false,

        bottomDisabled: false,

        editModeDisabled: false,

        navigateButtonsDisabled: false,

        readOnly: false,

        isWide: false,

        dependencyDefs: {},

        duplicateAction: true,

        selfAssignAction: false,

        inlineEditDisabled: false,

        printPdfAction: true,

        portalLayoutDisabled: false,

        convertCurrencyAction: true,

        saveAndContinueEditingAction: true,

        panelSoftLockedTypeList: ['default', 'acl', 'delimiter', 'dynamicLogic'],

        confirmLeaveDisabled: false,

        setupHandlerType: 'record/detail',

        events: {
            'click .button-container .action': function (e) {
                Espo.Utils.handleAction(this, e);
            },
            'click [data-action="showMoreDetailPanels"]': function () {
                this.showMoreDetailPanels();
            },
        },

        actionEdit: function () {
            if (!this.editModeDisabled) {
                this.setEditMode();

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

        actionSave: function (data) {
            data = data || {};

            var modeBeforeSave = this.mode;

            this.save(data.options)
                .catch((reason) => {
                    if (modeBeforeSave === 'edit' && reason === 'error') {
                        this.setEditMode();
                    }
                });

            if (!this.lastSaveCancelReason || this.lastSaveCancelReason === 'notModified') {
                this.setDetailMode();

                $(window).scrollTop(0);
            }
        },

        actionCancelEdit: function () {
            this.cancelEdit();

            $(window).scrollTop(0);
        },

        actionSaveAndContinueEditing: function (data) {
            data = data || {};

            this.save(data.options)
                .catch(() => {});
        },

        actionSelfAssign: function () {
            var attributes = {
                assignedUserId: this.getUser().id,
                assignedUserName: this.getUser().get('name'),
            };

            if ('getSelfAssignAttributes' in this) {
                var attributesAdditional = this.getSelfAssignAttributes();

                if (attributesAdditional) {
                    for (var i in attributesAdditional) {
                        attributes[i] = attributesAdditional[i];
                    }
                }
            }

            this.model.save(attributes, {
                patch: true,
            }).then(function () {
                Espo.Ui.success(this.translate('Self-Assigned'));
            }.bind(this));
        },

        actionConvertCurrency: function () {
            this.createView('modalConvertCurrency', 'views/modals/convert-currency', {
                entityType: this.entityType,
                model: this.model,
            }, function (view) {
                view.render();

                this.listenToOnce(view, 'after:update', function (attributes) {
                    var isChanged = false;

                    for (var a in attributes) {
                        if (attributes[a] !== this.model.get(a)) {
                            isChanged = true;

                            break;
                        }
                    }

                    if (!isChanged) {
                        Espo.Ui.warning(this.translate('notUpdated', 'messages'));

                        return;
                    }

                    this.model.fetch().then(function () {
                        Espo.Ui.success(this.translate('done', 'messages'));
                    }.bind(this));
                }, this);
            });
        },

        getSelfAssignAttributes: function () {
        },

        setupActionItems: function () {
            if (this.model.isNew()) {
                this.isNew = true;

                this.removeButton('delete');
            }
            else if (this.getMetadata().get(['clientDefs', this.scope, 'removeDisabled'])) {
                this.removeButton('delete');
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
                    this.getAcl().check(this.entityType, 'edit')
                    &&
                    !~this.getAcl().getScopeForbiddenFieldList(this.entityType).indexOf('assignedUser')
                    &&
                    !this.getUser().isPortal()
                ) {
                    if (this.model.has('assignedUserId')) {
                        this.dropdownItemList.push({
                            'label': 'Self-Assign',
                            'name': 'selfAssign',
                            'hidden': !!this.model.get('assignedUserId')
                        });

                        this.listenTo(this.model, 'change:assignedUserId', function () {
                            if (!this.model.get('assignedUserId')) {
                                this.showActionItem('selfAssign');
                            }
                            else {
                                this.hideActionItem('selfAssign');
                            }
                        }, this);
                    }
                }
            }

            if (this.type === 'detail' && this.printPdfAction) {
                var printPdfAction = true;

                if (!~(this.getHelper().getAppParam('templateEntityTypeList') || []).indexOf(this.entityType)) {
                    printPdfAction = false;
                }

                if (printPdfAction) {
                    this.dropdownItemList.push({
                        'label': 'Print to PDF',
                        'name': 'printPdf',
                    });
                }
            }

            if (this.type === 'detail' && this.convertCurrencyAction) {
                if (
                    this.getAcl().check(this.entityType, 'edit')
                    &&
                    !this.getMetadata().get(['clientDefs', this.scope, 'convertCurrencyDisabled'])
                ) {
                    var currencyFieldList = this.getFieldManager().getEntityTypeFieldList(this.entityType, {
                        type: 'currency',
                        acl: 'edit',
                    });

                    if (currencyFieldList.length) {
                        this.addDropdownItem({
                            label: 'Convert Currency',
                            name: 'convertCurrency'
                        });
                    }
                }
            }

            if (this.type === 'detail' && this.getMetadata().get(['scopes', this.scope, 'hasPersonalData'])) {
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
                this.additionalActionsDefs = {};

                var additionalActionList = [];

                var actionDefsList = (
                        this.getMetadata().get(['clientDefs', this.scope, this.type + 'ActionList']) || []
                    ).concat(
                        this.getMetadata().get(['clientDefs', 'Global', this.type + 'ActionList']) || []
                    );

                actionDefsList.forEach(function (item) {
                    if (typeof item === 'string') {
                        item = {
                            name: item,
                        };
                    }

                    var item = Espo.Utils.clone(item);
                    var name = item.name;

                    if (!item.label) {
                        item.html = this.translate(name, 'actions', this.scope);
                    }

                    this.addDropdownItem(item);

                    if (!Espo.Utils.checkActionAvailability(this.getHelper(), item)) {
                        return;
                    }

                    additionalActionList.push(item);

                    var viewObject = this;

                    var data = item.data || {};

                    var handler = item.handler || data.handler;

                    if (item.initFunction && handler) {
                        this.wait(
                            new Promise(function (resolve) {
                                require(handler, function (Handler) {
                                    var handler = new Handler(viewObject);

                                    handler[item.initFunction].call(handler);

                                    resolve();
                                });
                            })
                        );
                    }

                    if (!Espo.Utils.checkActionAccess(this.getAcl(), this.model, item, true)) {
                        item.hidden = true;
                    }
                }, this);

                if (additionalActionList.length) {
                    this.listenTo(this.model, 'sync', function () {
                        additionalActionList.forEach(function (item) {
                            if (Espo.Utils.checkActionAccess(this.getAcl(), this.model, item, true)) {
                                this.showActionItem(item.name);
                            }
                            else {
                                this.hideActionItem(item.name);
                            }
                        }, this);
                    }, this);
                }

                if (this.saveAndContinueEditingAction) {
                    this.dropdownEditItemList.push({
                        name: 'saveAndContinueEditing',
                        label: 'Save & Continue Editing',
                    });
                }
            }
        },

        disableActionItems: function () {
            this.disableButtons();
        },

        enableActionItems: function () {
            this.enableButtons();
        },

        hideActionItem: function (name) {
            for (var i in this.buttonList) {
                if (this.buttonList[i].name === name) {
                    this.buttonList[i].hidden = true;

                    break;
                }
            }
            for (var i in this.dropdownItemList) {
                if (this.dropdownItemList[i].name === name) {
                    this.dropdownItemList[i].hidden = true;

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
            }
        },

        showActionItem: function (name) {
            for (var i in this.buttonList) {
                if (this.buttonList[i].name === name) {
                    this.buttonList[i].hidden = false;

                    break;
                }
            }

            for (var i in this.dropdownItemList) {
                if (this.dropdownItemList[i].name === name) {
                    this.dropdownItemList[i].hidden = false;

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
            }
        },

        showPanel: function (name, softLockedType) {
            if (this.recordHelper.getPanelStateParam(name, 'hiddenLocked')) {
                return;
            }

            softLockedType = softLockedType || 'default';

            this.recordHelper
                .setPanelStateParam(name, 'hidden' + Espo.Utils.upperCaseFirst(softLockedType) + 'Locked', false);

            if (softLockedType === 'dynamicLogic') {
                if (this.recordHelper.getPanelStateParam(name, 'hidden') === false) {
                    return;
                }
            }

            for (var i = 0; i < this.panelSoftLockedTypeList.length; i++) {
                var iType = this.panelSoftLockedTypeList[i];

                if (iType === softLockedType) {
                    continue;
                }

                var iParam = 'hidden' +  Espo.Utils.upperCaseFirst(iType) + 'Locked';

                if (this.recordHelper.getPanelStateParam(name, iParam)) {
                    return;
                }
            }

            var middleView = this.getView('middle');

            if (middleView) {
                middleView.showPanelInternal(name);
            }

            var bottomView = this.getView('bottom');

            if (bottomView) {
                if ('showPanel' in bottomView) {
                    bottomView.showPanel(name);
                }
            }
            else if (this.bottomView) {
                this.once('after:render', function () {
                    var bottomView = this.getView('bottom');

                    if (bottomView && 'showPanel' in bottomView) {
                        bottomView.showPanel(name);
                    }
                }, this);
            }

            var sideView = this.getView('side');

            if (sideView) {
                if ('showPanel' in sideView) {
                    sideView.showPanel(name);
                }
            }
            else if (this.sideView) {
                this.once('after:render', function () {
                    var sideView = this.getView('side');

                    if (sideView && 'showPanel' in sideView) {
                        sideView.showPanel(name);
                    }
                }, this);
            }

            this.recordHelper.setPanelStateParam(name, 'hidden', false);
        },

        hidePanel: function (name, locked, softLockedType) {
            softLockedType = softLockedType || 'default';

            if (locked) {
                this.recordHelper.setPanelStateParam(name, 'hiddenLocked', true);
            }

            if (softLockedType) {
                this.recordHelper
                    .setPanelStateParam(name, 'hidden' + Espo.Utils.upperCaseFirst(softLockedType) + 'Locked', true);
            }

            if (softLockedType === 'dynamicLogic') {
                if (this.recordHelper.getPanelStateParam(name, 'hidden') === true) {
                    return;
                }
            }

            var middleView = this.getView('middle');

            if (middleView) {
                middleView.hidePanelInternal(name);
            }

            var bottomView = this.getView('bottom');

            if (bottomView) {
                if ('hidePanel' in bottomView) {
                    bottomView.hidePanel(name);
                }
            }
            else if (this.bottomView) {
                this.once('after:render', function () {
                    var bottomView = this.getView('bottom');

                    if (bottomView && 'showPanel' in bottomView) {
                        bottomView.hidePanel(name);
                    }
                }, this);
            }

            var sideView = this.getView('side');

            if (sideView) {
                if ('hidePanel' in sideView) {
                    sideView.hidePanel(name);
                }
            }
            else if (this.sideView) {
                this.once('after:render', function () {
                    var sideView = this.getView('side');

                    if (sideView && 'hidePanel' in sideView) {
                        sideView.hidePanel(name);
                    }
                }, this);
            }

            this.recordHelper.setPanelStateParam(name, 'hidden', true);
        },

        afterRender: function () {
            this.initStickableButtonsContainer();
            this.initFieldsControlBehaviour();
        },

        initFieldsControlBehaviour: function () {
            var fields = this.getFieldViews();

            var fieldInEditMode = null;

            for (var field in fields) {
                var fieldView = fields[field];

                this.listenTo(fieldView, 'edit', (view) => {
                    if (fieldInEditMode && fieldInEditMode.mode === 'edit') {
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

                this.listenTo(fieldView, 'after:inline-edit-off', () => {
                    if (this.updatedAttributes) {
                        this.resetModelChanges();
                    }
                });
            }
        },

        initStickableButtonsContainer: function () {
           var $container = this.$el.find('.detail-button-container');

            var stickTop = this.getThemeManager().getParam('stickTop') || 62;
            var blockHeight = this.getThemeManager().getParam('blockHeight') || 21;

            var $block = $('<div>')
                .css('height', blockHeight + 'px')
                .html('&nbsp;')
                .hide()
                .insertAfter($container);

            var $middle = this.getView('middle').$el;
            var $window = $(window);

            if (this.stickButtonsFormBottomSelector) {
                var $bottom = this.$el.find(this.stickButtonsFormBottomSelector);

                if ($bottom.length) {
                    $middle = $bottom;
                }
            }

            var screenWidthXs = this.getThemeManager().getParam('screenWidthXs');

            $window.off('scroll.detail-' + this.numId);

            $window.on('scroll.detail-' + this.numId, function (e) {
                if ($(window.document).width() < screenWidthXs) {
                    $container.removeClass('stick-sub');

                    $block.hide();
                    $container.show();

                    return;
                }

                var edge = $middle.position().top + $middle.outerHeight(true);
                var scrollTop = $window.scrollTop();

                if (scrollTop < edge || this.stickButtonsContainerAllTheWay) {
                    if (scrollTop > stickTop) {
                        if (!$container.hasClass('stick-sub')) {
                            $container.addClass('stick-sub');
                            $block.show();

                            var $p = $('.popover');

                            $p.each(function (i, el) {
                                $el = $(el);
                                $el.css('top', ($el.position().top - blockHeight) + 'px');
                            });
                        }
                    }
                    else {
                        if ($container.hasClass('stick-sub')) {
                            $container.removeClass('stick-sub');
                            $block.hide();

                            var $p = $('.popover');
                            $p.each(function (i, el) {
                                $el = $(el);
                                $el.css('top', ($el.position().top + blockHeight) + 'px');
                            });
                        }
                    }

                    $container.show();
                }
                else {
                    $container.hide();
                    $block.show();
                }
            }.bind(this));
        },

        fetch: function () {
            var data = Dep.prototype.fetch.call(this);

            if (this.hasView('side')) {
                var view = this.getView('side');

                if ('fetch' in view) {
                    data = _.extend(data, view.fetch());
                }
            }

            if (this.hasView('bottom')) {
                var view = this.getView('bottom');

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

            var fields = this.getFieldViews(true);

            for (var field in fields) {
                var fieldView = fields[field];

                if (!fieldView.readOnly) {
                    if (fieldView.mode === 'edit') {
                        fieldView.fetchToModel();
                        fieldView.removeInlineEditLinks();
                        fieldView.setIsInlineEditMode(false);
                    }

                    fieldView.setMode('edit');
                    fieldView.render();
                }
            }
            this.mode = 'edit';

            this.trigger('after:set-edit-mode');
        },

        setDetailMode: function () {
            this.trigger('before:set-detail-mode');

            this.$el.find('.edit-buttons').addClass('hidden');
            this.$el.find('.record-buttons').removeClass('hidden');

            this.inlineEditModeIsOn = false;

            var fields = this.getFieldViews(true);

            for (var field in fields) {
                var fieldView = fields[field];

                if (fieldView.mode !== 'detail') {
                    if (fieldView.mode === 'edit') {
                        fieldView.trigger('inline-edit-off', {
                            all: true,
                        });
                    }

                    fieldView.setMode('detail');
                    fieldView.render();
                }
            }

            this.mode = 'detail';
            this.trigger('after:set-detail-mode');
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

            for (var attr in attributes) {
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
            }, function () {
                this.trigger('before:delete');
                this.trigger('delete');

                this.notify('Removing...');

                var collection = this.model.collection;

                this.model.destroy({
                    wait: true,
                    error: function () {
                        this.notify('Error occurred', 'error');
                    }.bind(this),
                    success: function () {
                        if (collection) {
                            if (collection.total > 0) {
                                collection.total--;
                            }
                        }

                        this.notify('Removed', 'success');
                        this.trigger('after:delete');
                        this.exit('delete');
                    }.bind(this),
                });
            }, this);
        },

        getFieldViews: function (withHidden) {
            var fields = {};

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

        getFieldView: function (name) {
            var view;

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

        // TODO remove
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

            return {
                scope: this.scope,
                entityType: this.entityType,
                buttonList: this.buttonList,
                buttonEditList: this.buttonEditList,
                dropdownItemList: this.dropdownItemList,
                dropdownEditItemList: this.dropdownEditItemList,
                dropdownItemListEmpty: this.isDropdownItemListEmpty(),
                buttonsDisabled: this.buttonsDisabled,
                name: this.name,
                id: this.id,
                isWide: this.isWide,
                isSmall: this.type === 'editSmall' || this.type === 'detailSmall',
                navigateButtonsEnabled: navigateButtonsEnabled,
                previousButtonEnabled: previousButtonEnabled,
                nextButtonEnabled: nextButtonEnabled,
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

            this.buttonList = _.clone(this.buttonList);
            this.buttonEditList = _.clone(this.buttonEditList);
            this.dropdownItemList = _.clone(this.dropdownItemList);
            this.dropdownEditItemList = _.clone(this.dropdownEditItemList);

            this.returnUrl = this.options.returnUrl || this.returnUrl;
            this.returnDispatchParams = this.options.returnDispatchParams || this.returnDispatchParams;

            this.exit = this.options.exit || this.exit;

            Bull.View.prototype.init.call(this);
        },

        isDropdownItemListEmpty: function () {
            if (this.dropdownItemList.length === 0) {
                return true;
            }

            var isEmpty = true;
            this.dropdownItemList.forEach(function (item) {
                if (!item.hidden) {
                    isEmpty = false;
                }
            }, this);

            return isEmpty;
        },

        setup: function () {
            if (typeof this.model === 'undefined') {
                throw new Error('Model has not been injected into record view.');
            }

            this.recordHelper = new ViewRecordHelper(this.defaultFieldStates, this.defaultFieldStates);

            var collection = this.collection = this.model.collection;

            if (collection) {
                this.listenTo(this.model, 'destroy', function () {
                    collection.remove(this.model.id);

                    collection.trigger('sync');
                }, this);

                if ('indexOfRecord' in this.options) {
                    this.indexOfRecord = this.options.indexOfRecord;
                } else {
                    this.indexOfRecord = collection.indexOf(this.model);
                }
            }

            if (this.getUser().isPortal() && !this.portalLayoutDisabled) {
                if (
                    this.getMetadata().get(
                        ['clientDefs', this.scope, 'additionalLayouts', this.layoutName + 'Portal']
                    )
                ) {
                    this.layoutName += 'Portal';
                }
            }

            this.once('remove', function () {
                if (this.isChanged) {
                    this.resetModelChanges();
                }
                this.setIsNotChanged();

                $(window).off('scroll.detail-' + this.numId);
            }, this);

            this.numId = Math.floor((Math.random() * 10000) + 1);

            this.id = Espo.Utils.toDom(this.entityType) + '-' + Espo.Utils.toDom(this.type) + '-' + this.numId;

            if (_.isUndefined(this.events)) {
                this.events = {};
            }

            if (!this.editModeDisabled) {
                if ('editModeDisabled' in this.options) {
                    this.editModeDisabled = this.options.editModeDisabled;
                }
            }

            this.buttonsDisabled = this.options.buttonsDisabled || this.buttonsDisabled;

            // for backward compatibility
            // TODO remove in 5.6.0
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
            if (!this.readOnly) {
                this.readOnly = this.getMetadata().get(['clientDefs', this.scope, 'editDisabled']) || this.readOnly;
            }
            this.readOnlyLocked = this.readOnly;

            this.inlineEditDisabled = this.inlineEditDisabled ||
                this.getMetadata().get(['clientDefs', this.scope, 'inlineEditDisabled']) ||
                false;

            this.inlineEditDisabled = this.options.inlineEditDisabled || this.inlineEditDisabled;
            this.navigateButtonsDisabled = this.options.navigateButtonsDisabled || this.navigateButtonsDisabled;
            this.portalLayoutDisabled = this.options.portalLayoutDisabled || this.portalLayoutDisabled;
            this.dynamicLogicDefs = this.options.dynamicLogicDefs || this.dynamicLogicDefs;

            this.accessControlDisabled = this.options.accessControlDisabled || this.accessControlDisabled;

            this.setupActionItems();
            this.setupBeforeFinal();

            this.on('after:render', function () {
                this.$detailButtonContainer = this.$el.find('.detail-button-container');
                this.$dropdownItemListButton = this.$detailButtonContainer.find('.dropdown-item-list-button');
            }, this);

            if (
                !this.isNew &&
                this.getConfig().get('useWebSocket') &&
                this.getMetadata().get(['scopes', this.entityType, 'object'])
            ) {
                this.subscribeToWebSocket();

                this.once('remove', function () {
                    if (this.isSubscribedToWebSocked) {
                        this.unsubscribeFromWebSocket();
                    }
                }.bind(this));
            }

            this.getHelper().processSetupHandlers(this, this.setupHandlerType);

            this.initInlideEditDynamicWithLogicInteroperability();
        },

        setupBeforeFinal: function () {
            if (!this.accessControlDisabled) {
                this.manageAccess();
            }

            this.attributes = this.model.getClonedAttributes();

            if (this.options.attributes) {
                this.model.set(this.options.attributes);
            }

            this.listenTo(this.model, 'sync', (m, o, o1) => {
                this.attributes = this.model.getClonedAttributes();
            });

            this.listenTo(this.model, 'change', () => {
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
                        fieldView.suspendValidatinMessage();

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
                this.listenTo(this.model, 'change', function (model, o) {
                    if ('onChange' in dynamicHandler) {
                        dynamicHandler.onChange.call(dynamicHandler, model, o);
                    }

                    var changedAttributes = model.changedAttributes();

                    for (var attribute in changedAttributes) {
                        var methodName = 'onChange' + Espo.Utils.upperCaseFirst(attribute);

                        if (methodName in dynamicHandler) {
                            dynamicHandler[methodName].call(dynamicHandler, model, changedAttributes[attribute], o);
                        }
                    }
                }, this);

                if ('init' in dynamicHandler) {
                    dynamicHandler.init();
                }
            }.bind(this);

            if (dynamicHandlerClassName) {
                this.wait(
                    new Promise(
                        function (resolve) {
                            require(dynamicHandlerClassName, function (DynamicHandler) {
                                var dynamicHandler = this.dynamicHandler = new DynamicHandler(this);
                                init(dynamicHandler);

                                resolve();
                            }.bind(this));
                        }.bind(this)
                    )
                );
            }

            var handlerList = this.getMetadata().get(['clientDefs', this.scope, 'dynamicHandlerList']) || [];

            if (handlerList.length) {
                var self = this;

                var promiseList = [];

                handlerList.forEach(function (className, i) {
                    promiseList.push(
                        new Promise(
                            function (resolve) {
                                require(className, function (DynamicHandler) {
                                    resolve(new DynamicHandler(self));
                                });
                            }
                        )
                    );
                });

                this.wait(
                    Promise.all(promiseList).then(
                        function (list) {
                            list.forEach(function (dynamicHandler) {
                                init(dynamicHandler);
                            });
                        }
                    )
                );
            }
        },

        setupFinal: function () {
            this.build();
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
                collection.fetch({
                    more: true,
                    remove: false,
                }).then(function () {
                    this.switchToModelByIndex(indexOfRecord);
                }.bind(this));
            }
        },

        actionViewPersonalData: function () {
            this.createView('viewPersonalData', 'views/personal-data/modals/personal-data', {
                model: this.model
            }, function (view) {
                view.render();

                this.listenToOnce(view, 'erase', function () {
                    this.clearView('viewPersonalData');
                    this.model.fetch();
                }, this);
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
                for (var item in data.viewOptions) {
                    options[item] = data.viewOptions[item];
                }
            }

            Espo.Ui.notify(this.translate('loading', 'messages'));

            this.createView('modalRelatedList', viewName, options, function (view) {
                Espo.Ui.notify(false);

                view.render();

                this.listenTo(view, 'action', function (action, data, e) {
                    var method = 'action' + Espo.Utils.upperCaseFirst(action);

                    if (typeof this[method] === 'function') {
                        this[method](data, e);

                        e.preventDefault();
                    }
                }, this);

                this.listenToOnce(view, 'close', function () {
                    this.clearView('modalRelatedList');
                }, this);
            });
        },

        actionPrintPdf: function () {
            this.createView('pdfTemplate', 'views/modals/select-template', {
                entityType: this.model.name,
            }, function (view) {
                view.render();

                this.listenToOnce(view, 'select', function (model) {
                    this.clearView('pdfTemplate');

                    window.open(
                        '?entryPoint=pdf&entityType=' +
                        this.model.name+'&entityId=' +
                        this.model.id+'&templateId=' + model.id, '_blank'
                    );
                }, this);
            });
        },

        afterSave: function () {
            if (this.isNew) {
                this.notify('Created', 'success');
            }
            else {
                this.notify('Saved', 'success');
            }

            this.enableButtons();

            this.setIsNotChanged();

            setTimeout(function () {
                this.unblockUpdateWebSocket();
            }.bind(this), this.blockUpdateWebSocketPeriod || 500);
        },

        beforeSave: function () {
            this.notify('Saving...');

            this.blockUpdateWebSocket();
        },

        beforeBeforeSave: function () {
            this.disableButtons();
        },

        afterSaveError: function () {
            this.enableButtons();
        },

        afterNotModified: function () {
            var msg = this.translate('notModified', 'messages');

            Espo.Ui.warning(msg, 'warning');

            this.enableButtons();
        },

        afterNotValid: function () {
            this.notify('Not valid', 'error');

            this.enableButtons();
        },

        errorHandlerDuplicate: function (duplicates) {
            this.notify(false);

            this.createView('duplicate', 'views/modals/duplicate', {
                scope: this.entityType,
                duplicates: duplicates,
                model: this.model,
            }, function (view) {
                view.render();

                this.listenToOnce(view, 'save', function () {
                    this.actionSave({
                        options: {
                            headers: {
                                'X-Skip-Duplicate-Check': 'true',
                            }
                        }
                    });
                }.bind(this));
            }.bind(this));
        },

        errorHandlerModified: function (data) {
            Espo.Ui.notify(false);

            var versionNumber = data.versionNumber;

            var values = data.values || {};

            var attributeList = Object.keys(values);

            var diffAttributeList = [];

            attributeList.forEach(function (attribute) {
                if (this.attributes[attribute] !== values[attribute]) {
                    diffAttributeList.push(attribute);
                }
            }, this);

            if (diffAttributeList.length === 0) {
                this.model.set('versionNumber', versionNumber, {silent: true});
                this.attributes.versionNumber = versionNumber;

                this.actionSave();

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
            .then(
                function (view) {
                    view.render();

                    this.listenTo(view, 'resolve', function () {
                        this.model.set('versionNumber', versionNumber, {silent: true});
                        this.attributes.versionNumber = versionNumber;

                        for (let attribute in values) {
                            this.setInitalAttributeValue(attribute, values[attribute]);
                        }
                    }, this);
                }.bind(this)
            );
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

            this.getFieldList().forEach(function (field) {
                this.setFieldReadOnly(field);
            }, this);
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

            this.getFieldList().forEach(function (field) {
                if (onlyNotSetAsReadOnly) {
                    if (this.recordHelper.getFieldStateParam(field, 'readOnly')) return;
                }

                this.setFieldNotReadOnly(field);
            }, this);
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
                        this.setNotReadOnly(true);
                    }

                    this.readOnly = false;
                }
            }

            if (editAccess === null) {
                this.listenToOnce(this.model, 'sync', function () {
                    this.manageAccessEdit(true);
                }, this);
            }
        },

        manageAccessDelete: function (second) {
            if (this.isNew) return;

            var deleteAccess = this.getAcl().checkModel(this.model, 'delete', true);

            if (!deleteAccess) {
                this.hideActionItem('delete');
            } else {
                this.showActionItem('delete');
            }

            if (deleteAccess === null) {
                this.listenToOnce(this.model, 'sync', function () {
                    this.manageAccessDelete(true);
                }, this);
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
                this.listenToOnce(this.model, 'sync', function () {
                    this.manageAccessStream(true);
                }, this);
            }
        },

        manageAccess: function () {
            this.manageAccessEdit();
            this.manageAccessDelete();
            this.manageAccessStream();
        },

        addButton: function (o) {
            var name = o.name;

            if (!name) {
                return;
            }

            for (var i in this.buttonList) {
                if (this.buttonList[i].name === name) {
                    return;
                }
            }

            this.buttonList.push(o);
        },

        addDropdownItem: function (o, toBeginning) {
            var method = toBeginning ? 'unshift' : 'push';

            if (!o) {
                this.dropdownItemList[method](false);

                return;
            }
            var name = o.name;

            if (!name) {
                return;
            }

            for (var i in this.dropdownItemList) {
                if (this.dropdownItemList[i].name === name) {
                    return;
                }
            }
            this.dropdownItemList[method](o);
        },

        enableButtons: function () {
            this.$el.find(".button-container .actions-btn-group .action")
                .removeAttr('disabled')
                .removeClass('disabled');

            this.$el.find(".button-container .actions-btn-group .dropdown-toggle")
                .removeAttr('disabled')
                .removeClass('disabled');
        },

        disableButtons: function () {
            this.$el.find(".button-container .actions-btn-group .action")
                .attr('disabled', 'disabled')
                .addClass('disabled');

            this.$el.find(".button-container .actions-btn-group .dropdown-toggle")
                .attr('disabled', 'disabled')
                .addClass('disabled');
        },

        removeButton: function (name) {
            for (var i in this.buttonList) {
                if (this.buttonList[i].name === name) {
                    this.buttonList.splice(i, 1);

                    break;
                }
            }

            for (var i in this.dropdownItemList) {
                if (this.dropdownItemList[i].name === name) {
                    this.dropdownItemList.splice(i, 1);

                    break;
                }
            }

            if (this.isRendered()) {
            	this.$el.find('.detail-button-container .action[data-action="'+name+'"]').remove();
            }
        },

        convertDetailLayout: function (simplifiedLayout) {
            var layout = [];

            var el = this.options.el || '#' + (this.id);

            this.panelFieldListMap = {};

            for (var p in simplifiedLayout) {
                var panel = {};

                if ('customLabel' in simplifiedLayout[p]) {
                    panel.label = simplifiedLayout[p].customLabel;

                    if (panel.label) {
                        panel.label = this.getLanguage()
                            .translate(panel.label, 'panelCustomLabels', this.entityType);
                    }
                } else {
                    panel.label = simplifiedLayout[p].label || null;

                    if (panel.label) {
                        panel.label = this.getLanguage()
                            .translate(panel.label, 'labels', this.entityType);
                    }
                }

                panel.name = simplifiedLayout[p].name || null;
                panel.style = simplifiedLayout[p].style || 'default';
                panel.rows = [];

                if (
                    simplifiedLayout[p].dynamicLogicVisible ||
                    simplifiedLayout[p].dynamicLogicStyled
                ) {
                    if (!panel.name) {
                        panel.name = 'panel-' + p.toString();
                    }
                }

                if (simplifiedLayout[p].dynamicLogicVisible) {
                    if (this.dynamicLogic) {
                        this.dynamicLogic.addPanelVisibleCondition(
                            panel.name, simplifiedLayout[p].dynamicLogicVisible
                        );
                    }
                }

                if (simplifiedLayout[p].dynamicLogicStyled) {
                    if (this.dynamicLogic) {
                        this.dynamicLogic.addPanelStyledCondition(
                            panel.name, simplifiedLayout[p].dynamicLogicStyled
                        );
                    }
                }

                if (simplifiedLayout[p].hidden) {
                    panel.hidden = true;
                    panel.name = panel.name || 'panel-' + p.toString();

                    this.hidePanel(panel.name);

                    this.underShowMoreDetailPanelList = this.underShowMoreDetailPanelList || [];
                    this.underShowMoreDetailPanelList.push(panel.name);
                }

                var lType = 'rows';

                if (simplifiedLayout[p].columns) {
                    lType = 'columns';

                    panel.columns = [];
                }

                if (panel.name) {
                    this.panelFieldListMap[panel.name] = [];
                }

                for (var i in simplifiedLayout[p][lType]) {
                    var row = [];

                    for (var j in simplifiedLayout[p][lType][i]) {
                        var cellDefs = simplifiedLayout[p][lType][i][j];

                        if (cellDefs === false) {
                            row.push(false);

                            continue;
                        }

                        if (!cellDefs.name) {
                            continue;
                        }

                        var name = cellDefs.name;

                        if (panel.name) {
                            this.panelFieldListMap[panel.name].push(name);
                        }

                        var type = cellDefs.type || this.model.getFieldType(name) || 'base';

                        var viewName = cellDefs.view ||
                            this.model.getFieldParam(name, 'view') ||
                            this.getFieldManager().getViewName(type);

                        var o = {
                            el: el + ' .middle .field[data-name="' + name + '"]',
                            defs: {
                                name: name,
                                params: cellDefs.params || {},
                            },
                            mode: this.fieldsMode
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

                        var fullWidth = cellDefs.fullWidth || false;

                        if (!fullWidth) {
                            if (simplifiedLayout[p][lType][i].length === 1) {
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

                        var cell = {
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

        getGridLayout: function (callback) {
            if (this.gridLayout !== null) {
                callback(this.gridLayout);

                return;
            }

            var gridLayoutType = this.gridLayoutType || 'record';

            if (this.detailLayout) {
                this.gridLayout = {
                    type: gridLayoutType,
                    layout: this.convertDetailLayout(this.detailLayout),
                };
                callback(this.gridLayout);

                return;
            }

            this._helper.layoutManager.get(this.model.name, this.layoutName, function (simpleLayout) {
                if (typeof this.modifyDetailLayout === 'function') {
                    var simpleLayout = Espo.Utils.cloneDeep(simpleLayout);

                    this.modifyDetailLayout(simpleLayout);
                }

                this.gridLayout = {
                    type: gridLayoutType,
                    layout: this.convertDetailLayout(simpleLayout),
                };

                callback(this.gridLayout);
            }.bind(this));
        },

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

        createMiddleView: function (callback) {
            var el = this.options.el || '#' + (this.id);

            this.waitForView('middle');

            this.getGridLayout(function (layout) {
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
            }.bind(this));
        },

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

        exitAfterCreate: function () {
            if (this.model.id) {
                var url = '#' + this.scope + '/view/' + this.model.id;

                this.getRouter().navigate(url, {trigger: false});

                this.getRouter().dispatch(this.scope, 'view', {
                    id: this.model.id,
                    rootUrl: this.options.rootUrl,
                    model: this.model,
                });

                return true;
            }
        },


        /**
         * Called after save or cancel.
         * By default redirects page. Can be orverridden in options.
         * @param {String} after Name of action (save, cancel, etc.) after which #exit is invoked.
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

                        var options = {
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
                var options = this.returnDispatchParams.options || {};

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

            this.getHelper().webSocketManager.subscribe(topic, function (t, data) {
                this.handleRecordUpdate();
            }.bind(this))
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

                m.fetch().then(
                    function () {
                        if (this.inlineEditModeIsOn || this.mode === 'edit') {
                            this.updatedAttributes = Espo.Utils.cloneDeep(m.attributes);
                        }
                    }.bind(this)
                );
            } else {
                this.model.fetch({highlight: true});
            }
        },

        blockUpdateWebSocket: function (toUnblock) {
            this.updateWebSocketIsBlocked = true;

            if (toUnblock) {
                setTimeout(function () {
                    this.unblockUpdateWebSocket();
                }.bind(this), this.blockUpdateWebSocketPeriod || 500);
            }
        },

        unblockUpdateWebSocket: function () {
            this.updateWebSocketIsBlocked = false;
        },

        showMoreDetailPanels: function () {
            this.hidePanel('showMoreDelimiter');

            this.underShowMoreDetailPanelList.forEach(function (item) {
                this.showPanel(item)
            }, this);
        },

    });
});

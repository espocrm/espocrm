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

/** @module views/record/panels-container */

import View from 'view';

/**
 * A panel container view. For bottom and side views.
 */
class PanelsContainerRecordView extends View {

    /** @private */
    panelSoftLockedTypeList = ['default', 'acl', 'delimiter', 'dynamicLogic']

    /**
     * A panel.
     *
     * @typedef {Object} module:views/record/panels-container~panel
     *
     * @property {string} name A name.
     * @property {boolean} [hidden] Hidden.
     * @property {string} [label] A label.
     * @property {'default'|'success'|'danger'|'warning'} [style] A style.
     * @property {string} [titleHtml] A title HTML.
     * @property {boolean} [notRefreshable] Not refreshable.
     * @property {boolean} [isForm] If for a form.
     * @property {module:views/record/panels-container~button[]} [buttonList] Buttons.
     * @property {module:views/record/panels-container~action[]} [actionList] Dropdown actions.
     * @property {string} [view] A view name.
     * @property {Object.<string, *>} [Options] A view options.
     * @property {boolean} [sticked] To stick to an upper panel.
     * @property {number} [tabNumber] A tab number.
     * @property {string} [aclScope] A scope to check access to.
     * @property {Espo.Utils~AccessDefs[]} [accessDataList] Access control defs.
     * @property {Record} [dynamicLogicVisible] Visibility rules.
     * @property {Record} [dynamicLogicStyled] Style rules.
     * @property {Record} [options] Options.
     * @property {boolean} [disabled] Disabled.
     */

    /**
     * A button. Handled by an `action{Action}` method or a click handler.
     *
     * @typedef {Object} module:views/record/panels-container~button
     *
     * @property {string} [action] An action.
     * @property {string} [name] A name. Required if a handler is used.
     * @property {boolean} [hidden] Hidden.
     * @property {string} [label] A label. Translatable.
     * @property {string} [html] A HTML.
     * @property {string} [text] A text.
     * @property {string} [title] A title (on hover). Translatable.
     * @property {Object.<string, (string|number|boolean)>} [data] Data attributes.
     * @property {string} [handler] A handler.
     * @property {string} [actionFunction] An action function.
     * @property {function()} [onClick] A click event.
     */

    /**
     * An action. Handled by an `action{Action}` method or a click handler.
     *
     * @typedef {Object} module:views/record/panels-container~action
     *
     * @property {string} [action] An action.
     * @property {string} [name] A name. Required if a handler is used.
     * @property {string} [link] A link URL.
     * @property {boolean} [hidden] Hidden.
     * @property {string} [label] A label. Translatable.
     * @property {string} [html] A HTML.
     * @property {string} [text] A text.
     * @property {Object.<string, (string|number|boolean)>} [data] Data attributes.
     * @property {string} [handler] A handler.
     * @property {string} [actionFunction] An action function.
     * @property {function()} [onClick] A click event.
     */

    /**
     * A panel list.
     *
     * @protected
     * @type {module:views/record/panels-container~panel[]}
     */
    panelList = null

    /** @private */
    hasTabs = false

    /**
     * @private
     * @type {Object.<string,*>[]|null}
     */
    tabDataList = null

    /**
     * @protected
     */
    currentTab = 0

    /**
     * @protected
     * @type {string}
     */
    scope = ''

    /**
     * @protected
     * @type {string}
     */
    entityType =  ''

    /**
     * @protected
     * @type {string}
     */
    name =  ''

    /**
     * A mode.
     *
     * @type 'detail'|'edit'
     */
    mode = 'detail'

    data() {
        const tabDataList = this.hasTabs ? this.getTabDataList() : [];

        return {
            panelList: this.panelList,
            scope: this.scope,
            entityType: this.entityType,
            tabDataList: tabDataList,
        };
    }

    events = {
        'click .action': function (e) {
            const $target = $(e.currentTarget);
            const panel = $target.data('panel');

            if (!panel) {
                return;
            }

            const panelView = this.getView(panel);

            if (!panelView) {
                return;
            }

            let actionItems;

            if (
                typeof panelView.getButtonList === 'function' &&
                typeof panelView.getActionList === 'function'
            ) {
                actionItems = [...panelView.getButtonList(), ...panelView.getActionList()];
            }

            Espo.Utils.handleAction(panelView, e.originalEvent, e.currentTarget, {
                actionItems: actionItems,
                className: 'panel-action',
            });

            // @todo Check data. Maybe pass cloned data with unset params.

            /*
            let action = $target.data('action');
            let data = $target.data();

            if (action && panel) {
                let method = 'action' + Espo.Utils.upperCaseFirst(action);
                let d = _.clone(data);

                delete d['action'];
                delete d['panel'];

                let view = this.getView(panel);

                if (view && typeof view[method] == 'function') {
                    view[method].call(view, d, e);
                }
            }*/
        },
        'click .panels-show-more-delimiter [data-action="showMorePanels"]': 'actionShowMorePanels',
        /** @this module:views/record/panels-container */
        'click .tabs > button': function (e) {
            const tab = parseInt($(e.currentTarget).attr('data-tab'));

            this.selectTab(tab);
        },
    }

    afterRender() {
        this.adjustPanels();
    }

    adjustPanels() {
        if (!this.isRendered()) {
            return;
        }

        const $panels = this.$el.find('> .panel');

        $panels
            .removeClass('first')
            .removeClass('last')
            .removeClass('in-middle');

        const $visiblePanels = $panels.filter(`:not(.tab-hidden):not(.hidden)`);

        const groups = [];
        let currentGroup = [];
        let inTab = false;

        $visiblePanels.each((i, el) => {
            const $el = $(el);

            let breakGroup = false;

            if (
                !breakGroup &&
                this.hasTabs &&
                !inTab &&
                $el.attr('data-tab') !== '-1'
            ) {
                inTab = true;
                breakGroup = true;
            }

            if (!breakGroup && !$el.hasClass('sticked')) {
                breakGroup = true;
            }

            if (breakGroup) {
                if (i !== 0) {
                    groups.push(currentGroup);
                }

                currentGroup = [];
            }

            currentGroup.push($el);

            if (i === $visiblePanels.length - 1) {
                groups.push(currentGroup);
            }
        });

        groups.forEach(group => {
            group.forEach(($el, i) => {
                if (i === group.length - 1) {
                    if (i === 0) {
                        return;
                    }

                    $el.addClass('last')

                    return;
                }

                if (i === 0 && group.length) {
                    $el.addClass('first')

                    return;
                }

                $el.addClass('in-middle');
            });
        });
    }

    /**
     * Set read-only.
     */
    setReadOnly() {
        this.readOnly = true;
    }

    /**
     * Set not read-only.
     */
    setNotReadOnly(onlyNotSetAsReadOnly) {
        this.readOnly = false;

        if (!onlyNotSetAsReadOnly) {
            return;
        }

        this.panelList.forEach(item => {
            this.applyAccessToActions(item.buttonList);
            this.applyAccessToActions(item.actionList);

            this.whenRendered().then(() => {
                if (this.getPanelActionsView(item.name)) {
                    this.getPanelActionsView(item.name).reRender();
                }
            })
        });
    }

    /**
     * @private
     * @param {Object[]} actionList
     */
    applyAccessToActions(actionList) {
        if (!actionList) {
            return;
        }

        actionList.forEach(item => {
            if (!Espo.Utils.checkActionAvailability(this.getHelper(), item)) {
                item.hidden = true;

                return;
            }

            const access = Espo.Utils.checkActionAccess(this.getAcl(), this.model, item, true);

            if (access) {
                if (item.isHiddenByAcl) {
                    item.isHiddenByAcl = false;
                    item.hidden = false;
                    delete item.hiddenByAclSoft;
                }
            } else if (!item.hidden) {
                item.isHiddenByAcl = true;
                item.hidden = true;
                delete item.hiddenByAclSoft;

                if (access === null) {
                    item.hiddenByAclSoft = true;
                }
            }
        });
    }

    /**
     * Set up panel views.
     *
     * @protected
     */
    setupPanelViews() {
        this.panelList.forEach(p => {
            const name = p.name;

            let options = {
                model: this.model,
                panelName: name,
                selector: `.panel[data-name="${name}"] > .panel-body`,
                defs: p,
                mode: this.mode,
                recordHelper: this.recordHelper,
                inlineEditDisabled: this.inlineEditDisabled,
                readOnly: this.readOnly,
                disabled: p.hidden || false,
                recordViewObject: this.recordViewObject,
                dataObject: this.options.dataObject,
            };

            options = _.extend(options, p.options);

            this.createView(name, p.view, options, (view) => {
                let hasSoftHidden = false;

                if ('getActionList' in view) {
                    p.actionList = view.getActionList();

                    this.applyAccessToActions(p.actionList);

                    // noinspection JSUnresolvedReference
                    if (p.actionList.find(it => it.hiddenByAclSoft)) {
                        hasSoftHidden = true;
                    }
                }

                if ('getButtonList' in view) {
                    p.buttonList = view.getButtonList();
                    this.applyAccessToActions(p.buttonList);

                    // noinspection JSUnresolvedReference
                    if (p.buttonList.find(it => it.hiddenByAclSoft)) {
                        hasSoftHidden = true;
                    }
                }

                if (hasSoftHidden) {
                    this.listenToOnce(this.model, 'sync', () => {
                        this.applyAccessToActions(p.actionList);
                        this.applyAccessToActions(p.buttonList);

                        view.whenRendered().then(() => {
                            if (this.getPanelActionsView(name)) {
                                this.getPanelActionsView(name).reRender();
                            }
                        })
                    });
                }

                if (view.titleHtml) {
                    p.titleHtml = view.titleHtml;
                }
                else {
                    if (p.label) {
                        p.title = this.translate(p.label, 'labels', this.scope);
                    }
                    else {
                        p.title = view.title;
                    }
                }

                // @todo Use name_Actions.
                this.createView(name + 'Actions', 'views/record/panel-actions', {
                    selector: `.panel[data-name="${p.name}"] > .panel-heading > .panel-actions-container`,
                    model: this.model,
                    defs: p,
                    scope: this.scope,
                    entityType: this.entityType,
                });
            });
        });
    }

    /**
     * @param {string} name
     * @return {import('views/record/panel-actions')}
     */
    getPanelActionsView(name) {
        return this.getView(name + 'Actions');
    }

    /**
     * Set up panels.
     *
     * @protected
     */
    setupPanels() {}

    /**
     * Get field views.
     *
     * @param {boolean} [withHidden] With hidden.
     * @return {Object.<string, module:views/fields/base>}
     */
    getFieldViews(withHidden) {
        let fields = {};

        this.panelList.forEach(p => {
            const panelView = this.getPanelView(p.name);

            if ((!panelView.disabled || withHidden) && ('getFieldViews' in panelView)) {
                fields = _.extend(fields, panelView.getFieldViews());
            }
        });

        return fields;
    }

    /**
     * Fetch.
     *
     * @return {Object.<string, *>}
     */
    fetch() {
        let data = {};

        this.panelList.forEach(p => {
            const panelView = this.getPanelView(p.name);

            if (!panelView.disabled && ('fetch' in panelView)) {
                data = _.extend(data, panelView.fetch());
            }
        });

        return data;
    }

    /**
     * @private
     * @param name
     * @return {import('views/record/panels/side').default|import('views/record/panels/side').default|null}
     */
    getPanelView(name) {
        return this.getView(name);
    }

    /**
     * @param {string} name
     * @return {boolean}
     */
    hasPanel(name) {
        return !!this.panelList.find(item => item.name === name);
    }

    /**
     * @internal
     *
     * @param {string} name
     * @param [callback] Not to be used.
     * @param {boolean} [wasShown]
     */
    processShowPanel(name, callback, wasShown = false) {
        if (this.recordHelper.getPanelStateParam(name, 'hidden')) {
            return;
        }

        if (!this.hasPanel(name)) {
            return;
        }

        this.panelList.filter(item => item.name === name).forEach(item => {
            item.hidden = false;

            if (typeof item.tabNumber !== 'undefined') {
                this.controlTabVisibilityShow(item.tabNumber);
            }
        });

        this.showPanelFinalize(name, callback, wasShown);
    }

    /**
     * @internal
     *
     * @param {string} name
     * @param [callback] Not to be used.
     */
    processHidePanel(name, callback) {
        if (!this.recordHelper.getPanelStateParam(name, 'hidden')) {
            return;
        }

        if (!this.hasPanel(name)) {
            return;
        }

        this.panelList.filter(item => item.name === name).forEach(item => {
            item.hidden = true;

            if (typeof item.tabNumber !== 'undefined') {
                this.controlTabVisibilityHide(item.tabNumber);
            }
        });

        this.hidePanelFinalize(name, callback);
    }

    /**
     * @private
     *
     * @param {string} name
     * @param [callback] Not to be used.
     * @param {boolean} [wasShown]
     */
    async showPanelFinalize(name, callback, wasShown) {
        const process = wasRendered => {
            const view = this.getPanelView(name);

            if (view) {
                if (view.element) {
                    const panelElement = view.element.closest('.panel');

                    if (panelElement) {
                        panelElement.classList.remove('hidden');
                    }
                }

                view.disabled = false;

                view.trigger('show');
                view.trigger('panel-show-propagated');

                if (wasRendered && !wasShown && view.getFieldViews) {
                    const fields = view.getFieldViews();

                    if (fields) {
                        for (const i in fields) {
                            fields[i].reRender();
                        }
                    }
                }
            }

            if (typeof callback === 'function') {
                callback.call(this);
            }
        };

        if (this.isRendered()) {
            process(true);

            this.adjustPanels();

            return;
        }

        await this.whenRendered();

        process();
    }

    /**
     * @private
     *
     * @param {string} name
     * @param [callback] Not to be used.
     */
    async hidePanelFinalize(name, callback) {
        if (this.isRendered()) {
            const view = this.getPanelView(name);

            if (view) {
                if (view.element) {
                    const panelElement = view.element.closest('.panel');

                    if (panelElement) {
                        panelElement.classList.add('hidden');
                    }
                }

                view.disabled = true;

                view.trigger('hide');
            }

            if (typeof callback === 'function') {
                callback();
            }

            this.adjustPanels();

            return;
        }

        if (typeof callback === 'function') {
            await this.whenRendered();

            callback();
        }
    }

    /**
     * @param {string} name
     * @param {string} [softLockedType]
     * @param [callback] Not to be used.
     */
    showPanel(name, softLockedType, callback) {
        if (this.recordHelper.getPanelStateParam(name, 'hiddenLocked')) {
            return;
        }

        if (softLockedType) {
            const param = 'hidden' + Espo.Utils.upperCaseFirst(softLockedType) + 'Locked';

            this.recordHelper.setPanelStateParam(name, param, false);

            for (let i = 0; i < this.panelSoftLockedTypeList.length; i++) {
                const iType = this.panelSoftLockedTypeList[i];

                if (iType === softLockedType) {
                    continue;
                }

                const iParam = 'hidden' + Espo.Utils.upperCaseFirst(iType) + 'Locked';

                if (this.recordHelper.getPanelStateParam(name, iParam)) {
                    return;
                }
            }
        }

        const wasShown = this.recordHelper.getPanelStateParam(name, 'hidden') === false;

        this.recordHelper.setPanelStateParam(name, 'hidden', false);

        this.processShowPanel(name, callback, wasShown);
    }

    /**
     * @param {string} name
     * @param {boolean} [locked=false]
     * @param {string} [softLockedType]
     * @param [callback] Not to be used.
     */
    hidePanel(name, locked, softLockedType, callback) {
        this.recordHelper.setPanelStateParam(name, 'hidden', true);

        if (locked) {
            this.recordHelper.setPanelStateParam(name, 'hiddenLocked', true);
        }

        if (softLockedType) {
            const param = 'hidden' + Espo.Utils.upperCaseFirst(softLockedType) + 'Locked';

            this.recordHelper.setPanelStateParam(name, param, true);
        }

        this.processHidePanel(name, callback);
    }

    /**
     * @internal
     * @protected
     * @param {Object} layoutData
     */
    alterPanels(layoutData) {
        layoutData = layoutData || this.layoutData || {};

        const tabBreakIndexList = [];

        const tabDataList = [];

        for (const name in layoutData) {
            const item = layoutData[name];

            if (name === '_delimiter_') {
                this.panelList.push({
                    name: name,
                });
            }

            if (item.tabBreak) {
                tabBreakIndexList.push(item.index);

                tabDataList.push({
                    index: item.index,
                    label: item.tabLabel,
                })
            }
        }

        /**
         * @private
         * @type {Object.<string, *>[]}
         */
        this.tabDataList = tabDataList.sort((v1, v2) => v1.index - v2.index);

        this.panelList = this.panelList.filter(item => {
            return !this.recordHelper.getPanelStateParam(item.name, 'hiddenLocked');
        });

        const newList = [];

        this.panelList.forEach((item, i) => {
            item.index = ('index' in item) ? item.index : i;

            let allowedInLayout = false;

            if (item.name) {
                const itemData = layoutData[item.name] || {};

                if (itemData.disabled) {
                    return;
                }

                if (layoutData[item.name]) {
                    allowedInLayout = true;
                }

                for (const i in itemData) {
                    item[i] = itemData[i];
                }
            }

            if (item.disabled && !allowedInLayout) {
                return;
            }

            item.tabNumber = tabBreakIndexList.length -
                tabBreakIndexList.slice().reverse().findIndex(index => item.index > index) - 1;

            if (item.tabNumber === tabBreakIndexList.length) {
                item.tabNumber = -1;
            }

            newList.push(item);
        });

        newList.sort((v1, v2) => v1.index - v2.index);

        const firstTabIndex = newList.findIndex(item => item.tabNumber !== -1);

        /*let firstNonHiddenTabIndex = newList.findIndex(item => item.tabNumber !== -1 && !item.hidden);

        if (firstNonHiddenTabIndex < 0) {
            firstNonHiddenTabIndex = firstTabIndex;
        }*/

        if (firstTabIndex !== -1) {
            newList[firstTabIndex].isTabsBeginning = true;

            this.hasTabs = true;
            this.currentTab = newList[firstTabIndex].tabNumber;

            this.panelList
                .filter(item => item.tabNumber !== -1 && item.tabNumber !== this.currentTab)
                .forEach(item => {
                    item.tabHidden = true;
                });

            this.panelList.forEach((item, i) => {
                if (
                    item.tabNumber !== -1 &&
                    (i === 0 || this.panelList[i - 1].tabNumber !== item.tabNumber)
                ) {
                    item.sticked = false;
                }
            });
        }

        this.panelList = newList;

        if (this.recordViewObject && this.recordViewObject.dynamicLogic) {
            const dynamicLogic = this.recordViewObject.dynamicLogic;

            this.panelList.forEach(item => {
                if (item.dynamicLogicVisible) {
                    dynamicLogic.addPanelVisibleCondition(item.name, item.dynamicLogicVisible);

                    if (this.recordHelper.getPanelStateParam(item.name, 'hidden')) {
                        item.hidden = true;
                    }
                }

                if (item.style && item.style !== 'default' && item.dynamicLogicStyled) {
                    dynamicLogic.addPanelStyledCondition(item.name, item.dynamicLogicStyled);
                }
            });
        }

        if (
            this.hasTabs &&
            this.options.isReturn &&
            this.isStoredTabForThisRecord()
        ) {
            this.selectStoredTab();
        }
    }

    /**
     * @protected
     */
    setupPanelsFinal() {
        let afterDelimiter = false;
        let rightAfterDelimiter = false;

        let index = -1;

        this.panelList.forEach((p, i) => {
            if (p.name === '_delimiter_') {
                afterDelimiter = true;
                rightAfterDelimiter = true;
                index = i;

                return;
            }

            if (afterDelimiter) {
                p.hidden = true;
                p.hiddenAfterDelimiter = true;

                this.recordHelper.setPanelStateParam(p.name, 'hidden', true);
                this.recordHelper.setPanelStateParam(p.name, 'hiddenDelimiterLocked', true);
            }

            if (rightAfterDelimiter) {
                p.isRightAfterDelimiter = true;
                rightAfterDelimiter = false;
            }
        });

        if (~index) {
            this.panelList.splice(index, 1);
        }

        this.panelsAreSet = true;

        this.trigger('panels-set');
    }

    actionShowMorePanels() {
        this.panelList.forEach(/** module:views/record/panels-container~panel & Record*/p => {
            if (!p.hiddenAfterDelimiter) {
                return;
            }

            delete p.isRightAfterDelimiter;

            this.showPanel(p.name, 'delimiter');
        });

        this.$el.find('.panels-show-more-delimiter').remove();
    }

    /**
     * @internal
     * @protected
     * @param {function} callback
     */
    onPanelsReady(callback) {
        Promise.race([
            new Promise(resolve => {
                if (this.panelsAreSet) {
                    resolve();
                }
            }),
            new Promise(resolve => {
                this.once('panels-set', resolve);
            })
        ]).then(() => {
            callback.call(this);
        });
    }

    /**
     * @private
     * @return {{
     *     label: string,
     *     isActive: boolean,
     *     hidden: boolean,
     * }[]}
     */
    getTabDataList() {
        return this.tabDataList.map((item, i) => {
            let label = item.label;

            if (!label) {
                label = (i + 1).toString();
            } else if (label[0] === '$') {
                label = this.translate(label.substring(1), 'tabs', this.scope);
            }

            const hidden = this.panelList
                .filter(panel => panel.tabNumber === i)
                .findIndex(panel => !this.recordHelper.getPanelStateParam(panel.name, 'hidden')) === -1;

            return {
                label: label,
                isActive: i === this.currentTab,
                hidden: hidden,
            };
        });
    }

    /**
     * @private
     * @param {number} tab
     * @param {boolean} [doNotStore]
     */
    selectTab(tab, doNotStore = false) {
        this.currentTab = tab;

        if (this.isRendered()) {
            $('body > .popover').remove();

            this.$el.find('.tabs > button').removeClass('active');
            this.$el.find(`.tabs > button[data-tab="${tab}"]`).addClass('active');

            this.$el.find('.panel[data-tab]:not([data-tab="-1"])').addClass('tab-hidden');
            this.$el.find(`.panel[data-tab="${tab}"]`).removeClass('tab-hidden');
        }

        this.adjustPanels();

        this.panelList
            .filter(item => item.tabNumber === tab && item.name)
            .forEach(item => {
                const view = this.getPanelView(item.name);

                if (view) {
                    view.trigger('tab-show');

                    view.propagateEvent('panel-show-propagated');
                }

                item.tabHidden = false;
            });

        this.panelList
            .filter(item => item.tabNumber !== tab && item.name)
            .forEach(item => {
                const view = this.getPanelView(item.name);

                if (view) {
                    view.trigger('tab-hide');
                }

                if (item.tabNumber > -1) {
                    item.tabHidden = true;
                }
            });

        if (doNotStore) {
            return;
        }

        this.storeTab();
    }

    /** @private */
    storeTab() {
        const key = `tab_${this.name}`;
        const keyRecord = `tab_${this.name}_record`;

        this.getSessionStorage().set(key, this.currentTab);
        this.getSessionStorage().set(keyRecord, `${this.entityType}_${this.model.id}`);
    }

    /** @private */
    isStoredTabForThisRecord() {
        const keyRecord = `tab_${this.name}_record`;

        return this.getSessionStorage().get(keyRecord) === `${this.entityType}_${this.model.id}`;
    }

    /** @private */
    selectStoredTab() {
        const key = `tab_${this.name}`;

        const tab = this.getSessionStorage().get(key);

        if (tab > 0) {
            this.selectTab(tab);
        }
    }

    /**
     * @private
     * @param {number} tab
     */
    async controlTabVisibilityShow(tab) {
        if (!this.hasTabs) {
            return;
        }

        await this.whenRendered();

        if (this.element) {
            const tabElement = this.element.querySelector(`.tabs > [data-tab="${tab.toString()}"]`);

            if (tabElement) {
                tabElement.classList.remove('hidden');
            }
        }
    }

    /**
     * @private
     * @param {number} tab
     */
    async controlTabVisibilityHide(tab) {
        if (!this.hasTabs) {
            return;
        }

        await this.whenRendered();

        const panelList = this.panelList.filter(panel => panel.tabNumber === tab);

        const allHidden = panelList
            .findIndex(panel => !this.recordHelper.getPanelStateParam(panel.name, 'hidden')) === -1;

        if (!allHidden) {
            return;
        }

        if (this.element) {
            const tabElement = this.element.querySelector(`.tabs > [data-tab="${tab.toString()}"]`);

            if (tabElement) {
                tabElement.classList.add('hidden');
            }
        }

        if (this.currentTab === tab) {
            const firstVisiblePanel = this.panelList
                .find(panel => panel.tabNumber > -1 && !panel.hidden);

            const firstVisibleTab = firstVisiblePanel ?
                firstVisiblePanel.tabNumber : 0;

            this.selectTab(firstVisibleTab, true);
        }
    }

    /**
     * @protected
     */
    setupInitial() {
        // Handles the situation when the first tab is hidden.
        this.listenToOnce(this.model, 'sync', async (m, r, /** Record */o) => {
            if (o.action !== 'fetch') {
                return;
            }

            setTimeout(async () =>  {
                await this.whenRendered();

                if (!this.hasTabs) {
                    return;
                }

                const firstVisiblePanel = this.panelList.find(p => !p.hidden && p.tabNumber > -1);

                if (!firstVisiblePanel) {
                    return;
                }

                if (firstVisiblePanel.tabNumber > this.currentTab) {
                    this.selectTab(firstVisiblePanel.tabNumber, true);
                }
            }, 1);
        });
    }
}

export default PanelsContainerRecordView;

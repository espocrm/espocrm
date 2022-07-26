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

define('views/record/panels-container', ['view'], function (Dep) {

    /**
     * A detail record view.
     *
     * @class
     * @name Class
     * @extends module:view.Class
     * @memberOf module:views/record/panels-container
     */
    return Dep.extend(/** @lends module:views/record/panels-container.Class# */{

        /**
         * @private
         */
        panelSoftLockedTypeList: ['default', 'acl', 'delimiter', 'dynamicLogic'],

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
         * @property {Object.<string,*>} [Options] A view options.
         * @property {boolean} [sticked] To stick to an upper panel.
         * @property {Number} [tabNumber]
         */

        /**
         * A button.
         *
         * @typedef {Object} module:views/record/panels-container~button
         *
         * @property {string} action An action.
         * @property {boolean} [hidden] Hidden.
         * @property {string} [label] A label. Translatable.
         * @property {string} [html] A HTML.
         * @property {string} [title] A title (on hover). Translatable.
         * @property {Object.<string,(string|number|boolean)>} [data] Data attributes.
         */

        /**
         * An action.
         *
         * @typedef {Object} module:views/record/panels-container~action
         *
         * @property {string} [action] An action.
         * @property {string} [link] A link URL.
         * @property {boolean} [hidden] Hidden.
         * @property {string} [label] A label. Translatable.
         * @property {string} [html] A HTML.
         * @property {Object.<string,(string|number|boolean)>} [data] Data attributes.
         */

        /**
         * A panel list.
         *
         * @protected
         * @type {module:views/record/panels-container~panel[]}
         */
        panelList: null,

        /**
         * @private
         */
        hasTabs: false,

        /**
         * @private
         * @type {Object.<string,*>[]|null}
         */
        tabDataList: null,

        /**
         * @protected
         */
        currentTab: 0,

        data: function () {
            let tabDataList = this.hasTabs ? this.getTabDataList() : [];

            return {
                panelList: this.panelList,
                scope: this.scope,
                entityType: this.entityType,
                tabDataList: tabDataList,
            };
        },

        events: {
            'click .action': function (e) {
                var $target = $(e.currentTarget);
                var action = $target.data('action');
                var panel = $target.data('panel');
                var data = $target.data();

                if (action && panel) {
                    var method = 'action' + Espo.Utils.upperCaseFirst(action);
                    var d = _.clone(data);

                    delete d['action'];
                    delete d['panel'];

                    var view = this.getView(panel);

                    if (view && typeof view[method] == 'function') {
                        view[method].call(view, d, e);
                    }
                }
            },
            'click .panels-show-more-delimiter [data-action="showMorePanels"]': 'actionShowMorePanels',
            /** @this module:views/record/panels-container.Class */
            'click .tabs > button': function (e) {
                let tab = $(e.currentTarget).attr('data-tab');

                this.selectTab(parseInt(tab));
            },
        },

        afterRender: function () {
            this.adjustPanels();
        },

        adjustPanels: function () {
            if (!this.isRendered()) {
                return;
            }

            let $panels = this.$el.find('> .panel');

            $panels
                .removeClass('first')
                .removeClass('last')
                .removeClass('in-middle');

            let $visiblePanels = $panels.filter(`:not(.tab-hidden):not(.hidden)`);

            let groups = [];
            let currentGroup = [];
            let inTab = false;

            $visiblePanels.each((i, el) => {
                let $el = $(el);

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
        },

        /**
         * Set read-only.
         */
        setReadOnly: function () {
            this.readOnly = true;
        },

        /**
         * Set not read-only.
         */
        setNotReadOnly: function (onlyNotSetAsReadOnly) {
            this.readOnly = false;

            if (onlyNotSetAsReadOnly) {
                this.panelList.forEach(item => {
                    this.applyAccessToActions(item.buttonList);
                    this.applyAccessToActions(item.actionList);

                    if (this.isRendered()) {
                        var actionsView = this.getView(item.actionsViewKey);

                        if (actionsView) {
                            actionsView.reRender();
                        }
                    }
                });
            }
        },

        /**
         * @private
         * @param {Object[]} actionList
         */
        applyAccessToActions: function (actionList) {
            if (!actionList) {
                return;
            }

            actionList.forEach(item => {
                if (!Espo.Utils.checkActionAvailability(this.getHelper(), item)) {
                    item.hidden = true;

                    return;
                }

                if (Espo.Utils.checkActionAccess(this.getAcl(), this.model, item, true)) {
                    if (item.isHiddenByAcl) {
                        item.isHiddenByAcl = false;
                        item.hidden = false;
                    }
                }
                else {
                    if (!item.hidden) {
                        item.isHiddenByAcl = true;
                        item.hidden = true;
                    }
                }
            });
        },

        /**
         * Set up panel views.
         *
         * @protected
         */
        setupPanelViews: function () {
            this.panelList.forEach(p => {
                var name = p.name;

                var options = {
                    model: this.model,
                    panelName: name,
                    el: this.options.el + ' .panel[data-name="' + name + '"] > .panel-body',
                    defs: p,
                    mode: this.mode,
                    recordHelper: this.recordHelper,
                    inlineEditDisabled: this.inlineEditDisabled,
                    readOnly: this.readOnly,
                    disabled: p.hidden || false,
                    recordViewObject: this.recordViewObject,
                };

                options = _.extend(options, p.options);

                this.createView(name, p.view, options, (view) => {
                    if ('getActionList' in view) {
                        p.actionList = view.getActionList();

                        this.applyAccessToActions(p.actionList);
                    }

                    if ('getButtonList' in view) {
                        p.buttonList = view.getButtonList();
                        this.applyAccessToActions(p.buttonList);
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

                    this.createView(name + 'Actions', 'views/record/panel-actions', {
                        el: this.getSelector() +
                            '.panel[data-name="'+p.name+'"] > .panel-heading > .panel-actions-container',
                        model: this.model,
                        defs: p,
                        scope: this.scope,
                        entityType: this.entityType,
                    });
                });
            });
        },

        /**
         * Set up panels.
         *
         * @protected
         */
        setupPanels: function () {},

        getFieldViews: function (withHidden) {
            var fields = {};

            this.panelList.forEach(p => {
                var panelView = this.getView(p.name);

                if ((!panelView.disabled || withHidden) && 'getFieldViews' in panelView) {
                    fields = _.extend(fields, panelView.getFieldViews());
                }
            });

            return fields;
        },

        getFields: function () {
            return this.getFieldViews();
        },

        fetch: function () {
            var data = {};

            this.panelList.forEach((p) => {
                var panelView = this.getView(p.name);

                if (!panelView.disabled && 'fetch' in panelView) {
                    data = _.extend(data, panelView.fetch());
                }
            });

            return data;
        },

        showPanel: function (name, softLockedType, callback) {
            if (this.recordHelper.getPanelStateParam(name, 'hiddenLocked')) {
                return;
            }

            if (softLockedType) {
                this.recordHelper
                    .setPanelStateParam(name, 'hidden' + Espo.Utils.upperCaseFirst(softLockedType) + 'Locked',
                        false);
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

            let wasShown = this.recordHelper.getPanelStateParam(name, 'hidden') === false;

            this.recordHelper.setPanelStateParam(name, 'hidden', false);

            var isFound = false;

            this.panelList.forEach(d => {
                if (d.name === name) {
                    d.hidden = false;
                    isFound = true;

                    this.controlTabVisibilityShow(d.tabNumber);
                }
            });

            if (!isFound) {
                return;
            }

            if (this.isRendered()) {
                var view = this.getView(name);

                if (view) {
                    view.$el.closest('.panel').removeClass('hidden');

                    view.disabled = false;
                    view.trigger('show');

                    if (!wasShown && view.getFieldViews) {
                        var fields = view.getFieldViews();

                        if (fields) {
                            for (let i in fields) {
                                fields[i].reRender();
                            }
                        }
                    }
                }

                if (typeof callback === 'function') {
                    callback.call(this);
                }

                this.adjustPanels();

                return;
            }

            this.once('after:render', () => {
                var view = this.getView(name);

                if (view) {
                    view.$el.closest('.panel').removeClass('hidden');
                    view.disabled = false;
                    view.trigger('show');
                }

                if (typeof callback === 'function') {
                    callback.call(this);
                }
            });
        },

        hidePanel: function (name, locked, softLockedType, callback) {
            this.recordHelper.setPanelStateParam(name, 'hidden', true);

            if (locked) {
                this.recordHelper.setPanelStateParam(name, 'hiddenLocked', true);
            }

            if (softLockedType) {
                this.recordHelper.setPanelStateParam(
                     name,
                     'hidden' + Espo.Utils.upperCaseFirst(softLockedType) + 'Locked',
                     true
                );
            }

            var isFound = false;

            this.panelList.forEach(d => {
                if (d.name === name) {
                    d.hidden = true;
                    isFound = true;

                    this.controlTabVisibilityHide(d.tabNumber);
                }
            });

            if (!isFound) {
                return;
            }

            if (this.isRendered()) {
                var view = this.getView(name);

                if (view) {
                    view.$el.closest('.panel').addClass('hidden');
                    view.disabled = true;
                    view.trigger('hide');
                }

                if (typeof callback === 'function') {
                    callback.call(this);
                }

                this.adjustPanels();

                return;
            }

            if (typeof callback === 'function') {
                this.once('after:render', () => {
                    callback.call(this);
                });
            }
        },

        alterPanels: function (layoutData) {
            layoutData = layoutData || this.layoutData || {};

            let tabBreakIndexList = [];

            let tabDataList = [];

            for (let name in layoutData) {
                let item = layoutData[name];

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
             * @type {Object.<string,*>[]}
             */
            this.tabDataList = tabDataList.sort((v1, v2) => v1.index - v2.index);

            let newList = [];

            this.panelList.forEach((item, i) => {
                item.index = ('index' in item) ? item.index : i;

                let allowedInLayout = false;

                if (item.name) {
                    var itemData = layoutData[item.name] || {};

                    if (itemData.disabled) {
                        return;
                    }

                    if (layoutData[item.name]) {
                        allowedInLayout = true;
                    }

                    for (let i in itemData) {
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

            let firstTabIndex = newList.findIndex(item => item.tabNumber !== -1);

            if (firstTabIndex !== -1) {
                newList[firstTabIndex].isTabsBeginning = true;
                this.hasTabs = true;
                this.currentTab = newList[firstTabIndex].tabNumber;

                this.panelList
                    .filter(item => item.tabNumber !== -1 && item.tabNumber !== this.currentTab)
                    .forEach(item => {
                        item.tabHidden = true;
                    })
            }

            this.panelList = newList;

            if (this.recordViewObject && this.recordViewObject.dynamicLogic) {
                var dynamicLogic = this.recordViewObject.dynamicLogic;

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
        },

        setupPanelsFinal: function () {
            var afterDelimiter = false;
            var rightAfterDelimiter = false;

            var index = -1;

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

            this.panelList = this.panelList.filter((p) => {
                return !this.recordHelper.getPanelStateParam(p.name, 'hiddenLocked');
            });

            this.panelsAreSet = true;

            this.trigger('panels-set');
        },

        actionShowMorePanels: function () {
            this.panelList.forEach(p => {
                if (!p.hiddenAfterDelimiter) {
                    return;
                }

                delete p.isRightAfterDelimiter;

                this.showPanel(p.name, 'delimiter');
            });

            this.$el.find('.panels-show-more-delimiter').remove();
        },

        onPanelsReady: function (callback) {
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
        },

        getTabDataList: function () {
            return this.tabDataList.map((item, i) => {
                let label = item.label;

                if (!label) {
                    label = (i + 1).toString();
                }
                else if (label[0] === '$') {
                    label = this.translate(label.substring(1), 'tabs', this.scope);
                }

                let hidden = this.panelList
                    .filter(panel => panel.tabNumber === i)
                    .findIndex(panel => !this.recordHelper.getPanelStateParam(panel.name, 'hidden')) === -1;

                return {
                    label: label,
                    isActive: i === this.currentTab,
                    hidden: hidden,
                };
            });
        },

        selectTab: function (tab) {
            this.currentTab = tab;

            $('body > .popover').remove();

            this.$el.find('.tabs > button').removeClass('active');
            this.$el.find(`.tabs > button[data-tab="${tab}"]`).addClass('active');

            this.$el.find('.panel[data-tab]:not([data-tab="-1"])').addClass('tab-hidden');
            this.$el.find(`.panel[data-tab="${tab}"]`).removeClass('tab-hidden');

            this.adjustPanels();
        },

        /**
         * @private
         */
        controlTabVisibilityShow: function (tab) {
            if (!this.hasTabs) {
                return;
            }

            if (this.isBeingRendered()) {
                this.once('after:render', () => this.controlTabVisibilityShow(tab));

                return;
            }

            this.$el.find(`.tabs > [data-tab="${tab.toString()}"]`).removeClass('hidden');
        },

        /**
         * @private
         */
        controlTabVisibilityHide: function (tab) {
            if (!this.hasTabs) {
                return;
            }

            if (this.isBeingRendered()) {
                this.once('after:render', () => this.controlTabVisibilityHide(tab));

                return;
            }

            let panelList = this.panelList.filter(panel => panel.tabNumber === tab);

            let allIsHidden = panelList
                .findIndex(panel => !this.recordHelper.getPanelStateParam(panel.name, 'hidden')) === -1;

            if (!allIsHidden) {
                return;
            }

            let $tab = this.$el.find(`.tabs > [data-tab="${tab.toString()}"]`);

            $tab.addClass('hidden');

            if (this.currentTab === tab) {
                this.selectTab(0);
            }
        },
    });
});

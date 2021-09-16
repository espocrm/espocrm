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

define('views/record/panels-container', 'view', function (Dep) {

    return Dep.extend({

        panelSoftLockedTypeList: ['default', 'acl', 'delimiter', 'dynamicLogic'],

        data: function () {
            return {
                panelList: this.panelList,
                scope: this.scope,
                entityType: this.entityType,
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
        },

        setReadOnly: function () {
            this.readOnly = true;
        },

        setNotReadOnly: function (onlyNotSetAsReadOnly) {
            this.readOnly = false;

            if (onlyNotSetAsReadOnly) {
                this.panelList.forEach((item) => {
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

        applyAccessToActions: function (actionList) {
            if (!actionList) {
                return;
            }

            actionList.forEach((item) => {
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

        setupPanelViews: function () {
            this.panelList.forEach((p) => {
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

        setupPanels: function () {},

        getFieldViews: function (withHidden) {
            var fields = {};

            this.panelList.forEach((p) => {
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
                    .setPanelStateParam(name, 'hidden' + Espo.Utils.upperCaseFirst(softLockedType) + 'Locked', false);
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

            this.panelList.forEach((d) => {
                if (d.name === name) {
                    d.hidden = false;
                    isFound = true;
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
                            for (var i in fields) {
                                fields[i].reRender();
                            }
                        }
                    }
                }

                if (typeof callback === 'function') {
                    callback.call(this);
                }

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

            this.panelList.forEach((d) => {
                if (d.name === name) {
                    d.hidden = true;
                    isFound = true;
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

            for (var n in layoutData) {
                if (n === '_delimiter_') {
                    this.panelList.push({
                        name: n,
                    });
                }
            }

            var newList = [];

            this.panelList.forEach((item, i) => {
                item.index = ('index' in item) ? item.index : i;

                var allowedInLayout = false;

                if (item.name) {
                    var itemData = layoutData[item.name] || {};

                    if (itemData.disabled) {
                        return;
                    }

                    if (layoutData[item.name]) {
                        allowedInLayout = true;
                    }

                    for (var i in itemData) {
                        item[i] = itemData[i];
                    }
                }

                if (item.disabled && !allowedInLayout) {
                    return;
                }

                newList.push(item);
            });

            newList.sort((v1, v2) => {
                return v1.index - v2.index;
            });

            this.panelList = newList;

            if (this.recordViewObject && this.recordViewObject.dynamicLogic) {
                var dynamicLogic = this.recordViewObject.dynamicLogic;

                this.panelList.forEach((item) => {
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

    });
});

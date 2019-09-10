/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

        data: function () {
            return {
                panelList: this.panelList,
                scope: this.scope,
                entityType: this.entityType
            };
        },

        events: {
            'click .action': function (e) {
                var $target = $(e.currentTarget);
                var action = $target.data('action');
                var panel = $target.data('panel');
                var data = $target.data();
                if (action) {
                    var method = 'action' + Espo.Utils.upperCaseFirst(action);
                    var d = _.clone(data);
                    delete d['action'];
                    delete d['panel'];
                    var view = this.getView(panel);
                    if (view && typeof view[method] == 'function') {
                        view[method].call(view, d, e);
                    }
                }
            }
        },

        setReadOnly: function () {
            this.readOnly = true;
        },

        setNotReadOnly: function (onlyNotSetAsReadOnly) {
            this.readOnly = false;

            if (onlyNotSetAsReadOnly) {
                this.panelList.forEach(function (item) {
                    this.applyAccessToActions(item.buttonList);
                    this.applyAccessToActions(item.actionList);

                    if (this.isRendered()) {
                        var actionsView = this.getView(item.actionsViewKey);
                        if (actionsView) {
                            actionsView.reRender();
                        }
                    }
                }, this);
            }
        },

        applyAccessToActions: function (actionList) {
            if (!actionList) return;
            actionList.forEach(function (item) {
                if (!Espo.Utils.checkActionAvailability(this.getHelper(), item)) {
                    item.hidden = true;
                    return;
                }
                if (Espo.Utils.checkActionAccess(this.getAcl(), this.model, item, true)) {
                    if (item.isHiddenByAcl) {
                        item.isHiddenByAcl = false;
                        item.hidden = false;
                    }
                } else {
                    if (!item.hidden) {
                        item.isHiddenByAcl = true;
                        item.hidden = true;
                    }
                }
            }, this);
        },

        setupPanelViews: function () {
            this.panelList.forEach(function (p) {

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
                    recordViewObject: this.recordViewObject
                };
                options = _.extend(options, p.options);
                this.createView(name, p.view, options, function (view) {
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
                    } else {
                        if (p.label) {
                            p.title = this.translate(p.label, 'labels', this.scope);
                        } else {
                            p.title = view.title;
                        }
                    }

                    this.createView(name + 'Actions', 'views/record/panel-actions', {
                        el: this.getSelector() + '.panel[data-name="'+p.name+'"] > .panel-heading > .panel-actions-container',
                        model: this.model,
                        defs: p,
                        scope: this.scope,
                        entityType: this.entityType
                    });
                }, this);
            }, this);
        },

        setupPanels: function () {},

        getFieldViews: function (withHidden) {
            var fields = {};
            this.panelList.forEach(function (p) {
                var panelView = this.getView(p.name);
                if ((!panelView.disabled || withHidden) && 'getFieldViews' in panelView) {
                    fields = _.extend(fields, panelView.getFieldViews());
                }
            }, this);
            return fields;
        },

        getFields: function () {
            return this.getFieldViews();
        },

        fetch: function () {
            var data = {};

            this.panelList.forEach(function (p) {
                var panelView = this.getView(p.name);
                if (!panelView.disabled && 'fetch' in panelView) {
                    data = _.extend(data, panelView.fetch());
                }
            }, this);
            return data;
        },

        showPanel: function (name, callback) {
            this.recordHelper.setPanelStateParam(name, 'hidden', false);

            var isFound = false;
            this.panelList.forEach(function (d) {
                if (d.name == name) {
                    d.hidden = false;
                    isFound = true;
                }
            }, this);
            if (!isFound) return;

            if (this.isRendered()) {
                var view = this.getView(name);
                if (view) {
                    view.$el.closest('.panel').removeClass('hidden');
                    view.disabled = false;
                    view.trigger('show');
                }
                if (callback) {
                    callback.call(this);
                }
            } else {
                if (callback) {
                    this.once('after:render', function () {
                        callback.call(this);
                    }, this);
                }
            }
        },

        hidePanel: function (name, callback) {
            this.recordHelper.setPanelStateParam(name, 'hidden', true);

            var isFound = false;
            this.panelList.forEach(function (d) {
                if (d.name == name) {
                    d.hidden = true;
                    isFound = true;
                }
            }, this);
            if (!isFound) return;

            if (this.isRendered()) {
                var view = this.getView(name);
                if (view) {
                    view.$el.closest('.panel').addClass('hidden');
                    view.disabled = true;
                    view.trigger('hide');
                }
                if (callback) {
                    callback.call(this);
                }
            } else {
                if (callback) {
                    this.once('after:render', function () {
                        callback.call(this);
                    }, this);
                }
            }
        }

    });
});

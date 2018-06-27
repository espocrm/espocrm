/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

Espo.define('views/record/detail-bottom', ['view'], function (Dep) {

    return Dep.extend({

        template: 'record/bottom',

        mode: 'detail',

        streamPanel: true,

        relationshipPanels: true,

        readOnly: false,

        portalLayoutDisabled: false,

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

        setupPanels: function () {
            var scope = this.scope;

            this.panelList = Espo.Utils.clone(this.getMetadata().get('clientDefs.' + scope + '.bottomPanels.' + this.type) || this.panelList || []);

            if (this.streamPanel && this.getMetadata().get('scopes.' + scope + '.stream')) {
                this.setupStreamPanel();
            }
        },

        setupStreamPanel: function () {
            var streamAllowed = this.getAcl().checkModel(this.model, 'stream', true);
            if (streamAllowed === null) {
                this.listenToOnce(this.model, 'sync', function () {
                    streamAllowed = this.getAcl().checkModel(this.model, 'stream', true);
                    if (streamAllowed) {
                        this.showPanel('stream', function () {
                            this.getView('stream').collection.fetch();
                        });
                    }
                }, this);
            }
            if (streamAllowed !== false) {
                this.panelList.push({
                    "name":"stream",
                    "label":"Stream",
                    "view":"views/stream/panel",
                    "sticked": true,
                    "hidden": !streamAllowed,
                    "order": 2
                });
            }
        },

        setupPanelViews: function () {
            this.panelList.forEach(function (p) {
                var name = p.name;
                this.createView(name, p.view, {
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
                }, function (view) {
                    if ('getActionList' in view) {
                        p.actionList = this.filterActions(view.getActionList());
                    }
                    if ('getButtonList' in view) {
                        p.buttonList = this.filterActions(view.getButtonList());
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
                }, this);
            }, this);
        },

        init: function () {
            this.recordHelper = this.options.recordHelper;
            this.scope = this.entityType = this.model.name;

            this.readOnlyLocked = this.options.readOnlyLocked || this.readOnly;
            this.readOnly = this.options.readOnly || this.readOnly;
            this.inlineEditDisabled = this.options.inlineEditDisabled || this.inlineEditDisabled;

            this.portalLayoutDisabled = this.options.portalLayoutDisabled || this.portalLayoutDisabled;

            this.recordViewObject = this.options.recordViewObject;
        },

        setup: function () {
            this.type = this.mode;
            if ('type' in this.options) {
                this.type = this.options.type;
            }

            this.panelList = [];

            this.setupPanels();

            this.wait(true);

            Promise.all([
                new Promise(function (resolve) {
                    if (this.relationshipPanels) {
                        this.loadRelationshipsLayout(function () {
                            resolve();
                        });
                    } else {
                        resolve();
                    }
                }.bind(this))
            ]).then(function () {
                this.panelList = this.panelList.filter(function (p) {
                    if (p.aclScope) {
                        if (!this.getAcl().checkScope(p.aclScope)) {
                            return;
                        }
                    }
                    if (p.accessDataList) {
                        if (!Espo.Utils.checkAccessDataList(p.accessDataList, this.getAcl(), this.getUser())) {
                            return false;
                        }
                    }
                    return true;
                }, this);

                if (this.relationshipPanels) {
                    this.setupRelationshipPanels();
                }

                this.panelList = this.panelList.map(function (p) {
                    var item = Espo.Utils.clone(p);
                    if (this.recordHelper.getPanelStateParam(p.name, 'hidden') !== null) {
                        item.hidden = this.recordHelper.getPanelStateParam(p.name, 'hidden');
                    } else {
                        this.recordHelper.setPanelStateParam(p.name, item.hidden || false);
                    }
                    return item;
                }, this);

                this.panelList.sort(function(item1, item2) {
                    var order1 = item1.order || 0;
                    var order2 = item2.order || 0;
                    return order1 - order2;
                });

                this.setupPanelViews();
                this.wait(false);

            }.bind(this));
        },

        loadRelationshipsLayout: function (callback) {
            var layoutName = 'relationships';
            if (this.getUser().isPortal() && !this.portalLayoutDisabled) {
                if (this.getMetadata().get(['clientDefs', this.scope, 'additionalLayouts', layoutName + 'Portal'])) {
                    layoutName += 'Portal';
                }
            }
            this._helper.layoutManager.get(this.model.name, layoutName, function (layout) {
                this.relationshipsLayout = layout;
                callback.call(this);
            }.bind(this));
        },

        setupRelationshipPanels: function () {
            var scope = this.scope;

            var scopesDefs = this.getMetadata().get('scopes') || {};

            var panelList = this.relationshipsLayout;
            panelList.forEach(function (item) {
                var p;
                if (typeof item == 'string' || item instanceof String) {
                    p = {name: item};
                } else {
                    p = Espo.Utils.clone(item || {});
                }
                if (!p.name) {
                    return;
                }

                var name = p.name;

                var links = (this.model.defs || {}).links || {};
                if (!(name in links)) {
                    return;
                }

                var foreignScope = links[name].entity;

                if ((scopesDefs[foreignScope] || {}).disabled) return;

                if (!this.getAcl().check(foreignScope, 'read')) {
                    return;
                }

                var defs = this.getMetadata().get('clientDefs.' + scope + '.relationshipPanels.' + name) || {};
                defs = Espo.Utils.clone(defs);

                for (var i in defs) {
                    if (i in p) continue;
                    p[i] = defs[i];
                }

                if (!p.view) {
                    p.view = 'views/record/panels/relationship';
                }

                p.order = 5;

                if (this.recordHelper.getPanelStateParam(p.name, 'hidden') !== null) {
                    p.hidden = this.recordHelper.getPanelStateParam(p.name, 'hidden');
                } else {
                    this.recordHelper.setPanelStateParam(p.name, p.hidden || false);
                }

                this.panelList.push(p);
            }, this);
        },

        filterActions: function (actions) {
            var filtered = [];
            actions.forEach(function (item) {
                if (Espo.Utils.checkActionAccess(this.getAcl(), this.model, item)) {
                    filtered.push(item);
                }
            }.bind(this));
            return filtered;
        },

        getFieldViews: function (withHidden) {
            var fields = {};
            this.panelList.forEach(function (p) {
                var panelView = this.getView(p.name);
                if ((!panelView.disabled || withHidden)  && 'getFieldViews' in panelView) {
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
        }
    });
});

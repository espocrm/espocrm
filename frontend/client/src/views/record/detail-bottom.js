/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('views/record/detail-bottom', 'view', function (Dep) {

    return Dep.extend({

        template: 'record/bottom',

        mode: 'detail',

        streamPanel: true,

        relationshipPanels: true,

        data: function () {
            return {
                panelList: this.panelList
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

            var panelList = Espo.Utils.clone(this.getMetadata().get('clientDefs.' + scope + '.bottomPanels.' + this.mode) || []);

            if (this.streamPanel && this.getMetadata().get('scopes.' + scope + '.stream')) {
                var streamAllowed = this.getAcl().checkModel(this.model, 'stream');
                if (streamAllowed === null) {
                    this.listenToOnce(this.model, 'sync', function () {
                        streamAllowed = this.getAcl().checkModel(this.model, 'stream');
                        if (streamAllowed) {
                            this.showPanel('stream', function () {
                                this.getView('stream').collection.fetch();
                            });
                        }
                    }, this);
                }
                if (streamAllowed !== false) {
                    panelList.push({
                        "name":"stream",
                        "label":"Stream",
                        "view":"views/stream/panel",
                        "sticked": true,
                        "hidden": !streamAllowed
                    });
                }
            }

            panelList.forEach(function (p) {
                this.panelList.push(p);
            }, this);
        },

        setupPanelViews: function () {
            this.panelList.forEach(function (p) {
                var name = p.name;
                this.createView(name, p.view, {
                    model: this.model,
                    panelName: name,
                    el: this.options.el + ' .panel-body-' + Espo.Utils.toDom(name),
                    defs: p,
                    mode: this.mode
                }, function (view) {
                    if ('getActionList' in view) {
                        p.actionList = this.filterActions(view.getActionList());
                    }
                    if ('getButtonList' in view) {
                        p.buttonList = this.filterActions(view.getButtonList());
                    }
                    if (p.label) {
                        p.title = this.translate(p.label, 'labels', this.scope);
                    } else {
                        p.title = view.title;
                    }
                }.bind(this));
            }, this);
        },

        setup: function () {
            this.panelList = [];
            this.scope = this.model.name;

            this.setupPanels();

            var panelList = [];
            this.panelList.forEach(function (p) {
                if (p.aclScope) {
                    if (!this.getAcl().checkScope(p.aclScope)) {
                        return;
                    }
                }
                panelList.push(p);
            }, this);
            this.panelList = panelList;

            this.setupPanelViews();

            if (this.relationshipPanels) {
                this.setupRelationshipPanels();
            }
        },

        setupRelationshipPanels: function () {
            var scope = this.scope;

            var scopesDefs = this.getMetadata().get('scopes') || {};

            this.wait(true);
            this._helper.layoutManager.get(this.model.name, 'relationships', function (layout) {
                var panelList = layout;
                panelList.forEach(function (item) {
                    var p;
                    if (typeof item == 'string' || item instanceof String) {
                        p = {name: item};
                    } else {
                        p = item || {};
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

                    this.panelList.push(p);

                    var defs = this.getMetadata().get('clientDefs.' + scope + '.relationshipPanels.' + name) || {};

                    var viewName = defs.view || 'views/record/panels/relationship';

                    this.createView(name, viewName, {
                        model: this.model,
                        panelName: name,
                        defs: defs,
                        el: this.options.el + ' .panel-body-' + Espo.Utils.toDom(p.name)
                    }, function (view) {
                        if ('getActionList' in view) {
                            p.actionList = this.filterActions(view.getActionList());
                        }
                        if ('getButtonList' in view) {
                            p.buttonList = this.filterActions(view.getButtonList());
                        }
                        p.title = view.title;
                    }.bind(this));
                }.bind(this));

                this.wait(false);
            }.bind(this));
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

        getFields: function () {
            var fields = {};
            this.panelList.forEach(function (p) {
                var panel = this.getView(p.name);
                if ('getFields' in panel) {
                    fields = _.extend(fields, panel.getFields());
                }
            }, this);
            return fields;
        },

        fetch: function () {
            var data = {};

            this.panelList.forEach(function (p) {
                var panel = this.getView(p.name);
                if ('fetch' in panel) {
                    data = _.extend(data, panel.fetch());
                }
            }, this);
            return data;
        },

    });
});



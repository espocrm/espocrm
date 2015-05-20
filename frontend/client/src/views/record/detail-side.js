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
 ************************************************************************/
    Espo.define('Views.Record.DetailSide', 'View', function (Dep) {

    return Dep.extend({

        template: 'record.side',

        mode: 'detail',

        readOnly: false,

        inlineEditDisabled: false,

        panelList: [
            {
                name: 'default',
                label: false,
                view: 'Record.Panels.DefaultSide',
                options: {
                    fieldList: [
                        {
                            name: 'assignedUser',
                            view: 'Fields.AssignedUser'
                        },
                        {
                            name: 'teams',
                            view: 'Fields.Teams'
                        }
                    ],
                    mode: 'detail',
                }
            }
        ],

        data: function () {
            return {
                panelList: this.panelList,
                scope: this.scope,
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
                        view[method].call(view, d);
                    }
                }
            },
        },

        init: function () {
            this.panelList = this.options.panelList || this.panelList;
            this.scope = this.options.model.name;
            this.panelList = _.clone(this.panelList);
            if (!this.readOnly) {
                if ('readOnly' in this.options)    {
                    this.readOnly = this.options.readOnly;
                }
            }
            if (!this.inlineEditDisabled) {
                if ('inlineEditDisabled' in this.options) {
                    this.inlineEditDisabled = this.options.inlineEditDisabled;
                }
            }
        },

        setupPanels: function () {

        },

        setup: function () {
            this.type = this.mode;
            if ('type' in this.options) {
                this.type = this.options.type;
            }

            this.setupPanels();

            var additionalPanels = this.getMetadata().get('clientDefs.' + this.scope + '.sidePanels.' + this.type) || [];
            additionalPanels.forEach(function (panel) {
                this.panelList.push(panel);
            }.bind(this));

            this.panelList.forEach(function (p) {
                var o = {
                    model: this.options.model,
                    el: this.options.el + ' .panel-body-' + p.name,
                    readOnly: this.readOnly,
                    inlineEditDisabled: this.inlineEditDisabled,
                    mode: this.mode
                };
                o = _.extend(o, p.options);
                this.createView(p.name, p.view, o, function (view) {
                    if ('getButtonList' in view) {
                        p.buttonList = this.filterActions(view.getButtonList());
                    }
                    if ('getActionList' in view) {
                        p.actionList = this.filterActions(view.getActionList());
                    }
                    if (p.label) {
                        p.title = this.translate(p.label, 'labels', this.scope);
                    } else {
                        p.title = view.title;
                    }
                }.bind(this));
            }.bind(this));
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

        filterActions: function (actions) {
            var filtered = [];
            actions.forEach(function (item) {
                if (Espo.Utils.checkActionAccess(this.getAcl(), this.model, item)) {
                    filtered.push(item);
                }
            }, this);
            return filtered;
        },
    });
});


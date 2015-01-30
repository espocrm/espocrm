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

        panels: [
            {
                name: 'default',
                label: false,
                view: 'Record.Panels.DefaultSide',
                options: {
                    fields: [
                        {
                            name: 'assignedUser',
                            view: 'Fields.UserWithAvatar'
                        },
                        'teams'
                    ],
                    mode: 'detail',
                }
            }
        ],

        data: function () {
            return {
                panels: this.panels,
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
            this.panels = this.options.panels || this.panels;
            this.scope = this.options.model.name;
            this.panels = _.clone(this.panels);
            if ('readOnly' in this.options)    {
                this.readOnly = this.options.readOnly;
            }
        },

        setup: function () {
            var additionalPanels = this.getMetadata().get('clientDefs.' + this.scope + '.sidePanels.' + this.mode) || [];
            additionalPanels.forEach(function (panel) {
                this.panels.push(panel);
            }.bind(this));

            this.panels.forEach(function (p) {
                var o = {
                    model: this.options.model,
                    el: this.options.el + ' .panel-body-' + p.name,
                    readOnly: this.readOnly
                };
                o = _.extend(o, p.options);
                this.createView(p.name, p.view, o, function (view) {
                    if ('getActions' in view) {
                        p.actions = this.filterActions(view.getActions());
                    }
                }.bind(this));
            }.bind(this));
        },

        getFields: function () {
            var fields = {};
            this.panels.forEach(function (p) {
                var panel = this.getView(p.name);
                if ('getFields' in panel) {
                    fields = _.extend(fields, panel.getFields());
                }
            }, this);
            return fields;
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


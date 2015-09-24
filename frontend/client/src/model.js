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
Espo.define('model', [], function () {

    var Model = Backbone.Model.extend({

        name: null,

        dateTime: null,

        _user: null,

        defs: null,

        initialize: function () {
            this.urlRoot = this.urlRoot || this.name;

            this.defs = this.defs || {};
            this.defs.fields = this.defs.fields || {};
            this.defs.links = this.defs.links || {};

            Backbone.Model.prototype.initialize.call(this);
        },

        url: function () {
            var base =
            _.result(this, 'urlRoot') ||
            _.result(this.collection, 'url') ||
            urlError();
            if (this.isNew()) return base;
            var id = this.id;
            return base.replace(/[^\/]$/, '$&/') + encodeURIComponent(id);
        },

        isNew: function () {
            return !this.id;
        },

        setDefs: function (defs) {
            this.defs = defs || {};
            this.defs.fields = this.defs.fields || {};
        },

        getClonedAttributes: function () {
            var attributes = {};
            for (var name in this.attributes) {
                // TODO maybe use cloneDeep method ???
                attributes[name] = Espo.Utils.cloneDeep(this.attributes[name]);
            }
            return attributes;
        },

        populateDefaults: function () {
            var defaultHash = {};
            if ('fields' in this.defs) {
                for (var field in this.defs.fields) {
                    var defaultValue = this.getFieldParam(field, 'default');

                    if (defaultValue != null) {
                        var defaultValue = this._getDefaultValue(defaultValue);
                        defaultHash[field] = defaultValue;
                    }
                }
            }

            var user = this.getUser();
            if (user) {
                if (this.hasField('assignedUser')) {
                    this.set('assignedUserId', this.getUser().id);
                    this.set('assignedUserName', this.getUser().get('name'));
                }
                var defaultTeamId = this.getUser().get('defaultTeamId');
                if (defaultTeamId) {
                    if (this.hasField('teams') && !this.getFieldParam('teams', 'default')) {
                        defaultHash['teamsIds'] = [defaultTeamId];
                        defaultHash['teamsNames'] = {};
                        defaultHash['teamsNames'][defaultTeamId] = this.getUser().get('defaultTeamName')
                    }
                }
            }

            defaultHash = Espo.Utils.cloneDeep(defaultHash);

            this.set(defaultHash, {silent: true});
        },

        _getDefaultValue: function (defaultValue) {
            if (typeof defaultValue == 'string' && defaultValue.indexOf('javascript:') === 0 ) {
                var code = defaultValue.substring(11);
                defaultValue = (new Function( "with(this) { " + code + "}")).call(this);
            }
            return defaultValue;
        },

        setRelate: function (data) {

            var setRelate = function (options) {
                var link = options.link;
                var model = options.model;
                if (!link || !model) {
                    throw new Error('Bad related options');
                }
                var type = this.defs.links[link].type;
                switch (type) {
                    case 'belongsToParent':
                        this.set(link + 'Id', model.id);
                        this.set(link + 'Type', model.name);
                        this.set(link + 'Name', model.get('name'));
                        break;
                    case 'belongsTo':
                        this.set(link + 'Id', model.id);
                        this.set(link + 'Name', model.get('name'));
                        break;
                    case 'hasMany':
                        var ids = [];
                        ids.push(model.id);
                        var names = {};
                        names[model.id] = model.get('name');
                        this.set(link + 'Ids', ids);
                        this.set(link + 'Names', names);
                        break;
                }
            }.bind(this);

            if (Object.prototype.toString.call(data) === '[object Array]') {
                data.forEach(function (options) {
                    setRelate(options);
                }.bind(this));
            } else {
                setRelate(data);
            }
        },

        getFieldType: function (field) {
            if (('defs' in this) && ('fields' in this.defs) && (field in this.defs.fields)) {
                return this.defs.fields[field].type || null;
            }
            return null;
        },

        getFieldParam: function (field, param) {
            if (('defs' in this) && ('fields' in this.defs) && (field in this.defs.fields)) {
                if (param in this.defs.fields[field]) {
                    return this.defs.fields[field][param];
                }
            }
            return null;
        },

        getLinkParam: function (link, param) {
            if (('defs' in this) && ('links' in this.defs) && (link in this.defs.links)) {
                if (param in this.defs.links[link]) {
                    return this.defs.links[link][param];
                }
            }
            return null;
        },

        isFieldReadOnly: function (field) {
            return this.getFieldParam(field, 'readOnly') || false;
        },

        isRequired: function (field) {
            return this.getFieldParam(field, 'required') || false;
        },

        getTeamIds: function () {
            return this.get('teamsIds') || [];
        },

        getDateTime: function () {
            return this.dateTime;
        },

        getUser: function () {
            return this._user;
        },

        hasField: function (field) {
            return ('defs' in this) && ('fields' in this.defs) && (field in this.defs.fields);
        },

        isEditable: function () {
            return true;
        },

        isRemovable: function () {
            return true;
        }
    });

    return Model;

});

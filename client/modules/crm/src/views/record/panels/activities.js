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

define(
    'crm:views/record/panels/activities',
    ['views/record/panels/relationship', 'multi-collection'], function (Dep, MultiCollection) {

    return Dep.extend({

        name: 'activities',

        orderBy: 'dateStart',

        serviceName: 'Activities',

        order: 'desc',

        rowActionsView: 'crm:views/record/row-actions/activities',

        relatedListFiltersDisabled: true,

        actionList: [
            {
                action: 'composeEmail',
                label: 'Compose Email',
                acl: 'create',
                aclScope: 'Email'
            }
        ],

        listLayout: {},

        defaultListLayout: {
            rows: [
                [
                    {name: 'ico', view: 'crm:views/fields/ico'},
                    {name: 'name', link: true, view: 'views/event/fields/name-for-history'}
                ],
                [
                    {name: 'assignedUser'},
                    {name: 'dateStart'}
                ]
            ]
        },

        setup: function () {
            this.scopeList = this.getConfig().get(this.name + 'EntityList') || [];

            this.listLayout = Espo.Utils.cloneDeep(this.listLayout);
            this.actionList = Espo.Utils.cloneDeep(this.actionList);

            this.defs.create = true;

            this.createAvailabilityHash = {};
            this.entityTypeLinkMap = {};
            this.createEntityTypeStatusMap = {};

            this.setupActionList();
            this.setupFinalActionList();

            this.setupSorting();

            var actionList = [];

            this.actionList.forEach(function (o) {
                if (o.aclScope) {
                    if (this.getMetadata().get(['scopes', o.aclScope, 'disabled'])) {
                        return;
                    }
                }

                actionList.push(o);
            }, this);

            this.actionList = actionList;

            this.scopeList.forEach(function (item) {
                if (!(item in this.listLayout)) {
                    this.listLayout[item] = this.defaultListLayout;
                }
            }, this);

            this.url = this.serviceName + '/' + this.model.name + '/' + this.model.id + '/' + this.name;

            this.seeds = {};

            this.wait(true);

            var i = 0;

            this.scopeList.forEach(function (scope) {
                this.getModelFactory().getSeed(scope, function (seed) {
                    this.seeds[scope] = seed;

                    i++;

                    if (i == this.scopeList.length) {
                        this.wait(false);
                    }
                }.bind(this));
            }.bind(this));

            if (this.scopeList.length == 0) {
                this.wait(false);
            }

            this.filterList = [];

            this.scopeList.forEach(function (item) {
                if (!this.getAcl().check(item)) {
                    return;
                }

                if (!this.getAcl().check(item, 'read')) {
                    return;
                }

                if (this.getMetadata().get(['scopes', item, 'disabled'])) {
                    return;
                }

                this.filterList.push(item);
            }, this);

            if (this.filterList.length) {
                this.filterList.unshift('all');
            }

            if (this.filterList && this.filterList.length) {
                this.filter = this.getStoredFilter();
            }

            this.setupFilterActions();

            this.setupTitle();

            this.collection = new MultiCollection();
            this.collection.seeds = this.seeds;
            this.collection.url = this.url;
            this.collection.orderBy = this.orderBy;
            this.collection.order = this.order;
            this.collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;

            this.setFilter(this.filter);

            this.once('show', function () {
                if (!this.isRendered() && !this.isBeingRendered()) {
                    this.collection.fetch();
                }
            }, this);
        },

        translateFilter: function (name) {
            if (name === 'all') {
                return this.translate(name, 'presetFilters');
            }

            return this.translate(name, 'scopeNamesPlural');
        },

        isCreateAvailable: function (scope) {
            return this.createAvailabilityHash[scope];
        },

        setupActionList: function () {
            this.scopeList.forEach(function (scope) {
                if (!this.getMetadata().get(['clientDefs', scope, 'activityDefs', this.name + 'Create'])) {
                    return;
                }

                if (!this.getAcl().checkScope(scope, 'create')) {
                    return;
                }

                var o = {
                    action: 'createActivity',
                    html: this.translate((this.name === 'history' ? 'Log' : 'Schedule') + ' ' + scope, 'labels', scope),
                    data: {},
                    acl: 'create',
                    aclScope: scope,
                };

                var link = this.getMetadata().get(['clientDefs', scope, 'activityDefs', 'link'])

                if (link) {
                    o.data.link = link;

                    this.entityTypeLinkMap[scope] = link;

                    if (!this.model.hasLink(link)) {
                        return;
                    }

                } else {
                    o.data.scope = scope;
                    if (
                        this.model.name !== 'User'
                        &&
                        !this.checkParentTypeAvailability(scope, this.model.name)
                    ) {
                        return;
                    }
                }

                this.createAvailabilityHash[scope] = true;

                o.data = o.data || {};
                if (!o.data.status) {
                    var statusList = this.getMetadata().get(['scopes', scope, this.name + 'StatusList']);
                    if (statusList && statusList.length) {
                        o.data.status = statusList[0];
                    }
                }
                this.createEntityTypeStatusMap[scope] = o.data.status;
                this.actionList.push(o);
            }, this);

        },

        setupFinalActionList: function () {
            this.scopeList.forEach(function (scope, i) {
                if (i === 0 && this.actionList.length) {
                    this.actionList.push(false);
                }

                if (!this.getAcl().checkScope(scope, 'read')) {
                    return;
                }

                var o = {
                    action: 'viewRelatedList',
                    html: this.translate('View List') + ' &middot; ' + this.translate(scope, 'scopeNamesPlural') + '',
                    data: {
                        scope: scope
                    },
                    acl: 'read',
                    aclScope: scope,
                };

                this.actionList.push(o);
            }, this);
        },

        setFilter: function (filter) {
            this.filter = filter;

            this.collection.data.entityType = null;

            if (filter && filter !== 'all') {
                this.collection.data.entityType = this.filter;
            }
        },

        afterRender: function () {
            this.listenToOnce(this.collection, 'sync', function () {
                this.createView('list', 'views/record/list-expanded', {
                    el: this.getSelector() + ' > .list-container',
                    pagination: false,
                    type: 'listRelationship',
                    rowActionsView: this.rowActionsView,
                    checkboxes: false,
                    collection: this.collection,
                    listLayout: this.listLayout
                }, function (view) {
                    view.render();

                    this.listenTo(view, 'after:save', function (m) {
                        this.fetchActivities();
                        this.fetchHistory();
                    }, this);
                }, this);
            }, this);

            if (!this.disabled) {
                this.collection.fetch();
            }
            else {
                this.once('show', function () {
                    this.collection.fetch();
                }, this);
            }
        },

        fetchHistory: function () {
            var parentView = this.getParentView();

            if (parentView) {
                if (parentView.hasView('history')) {
                    var collection = parentView.getView('history').collection;

                    if (collection) {
                        collection.fetch();
                    }
                }
            }
        },

        fetchActivities: function () {
            var parentView = this.getParentView();

            if (parentView) {
                if (parentView.hasView('activities')) {
                    var collection = parentView.getView('activities').collection;
                    if (collection) {
                        collection.fetch();
                    }
                }
            }
        },

        fetchActivitiesAndHistory: function () {

        },

        getCreateActivityAttributes: function (scope, data, callback) {
            data = data || {};

            var attributes = {
                status: data.status,
            };

            if (this.model.name == 'User') {
                if (this.model.isPortal()) {
                    attributes.usersIds = [this.model.id];

                    var usersIdsNames = {};
                    usersIdsNames[this.model.id] = this.model.get('name')
                    attributes.usersIdsNames = usersIdsNames;
                }
                else {
                    attributes.assignedUserId = this.model.id;
                    attributes.assignedUserName = this.model.get('name');
                }
            }
            else {
                if (this.model.name == 'Contact') {
                    if (this.model.get('accountId') && !this.getConfig().get('b2cMode')) {
                        attributes.parentType = 'Account',
                        attributes.parentId = this.model.get('accountId');
                        attributes.parentName = this.model.get('accountName');
                        if (
                            scope &&
                            !this.getMetadata().get(['entityDefs', scope, 'links', 'contacts']) &&
                            !this.getMetadata().get(['entityDefs', scope, 'links', 'contact'])
                        ) {
                            delete attributes.parentType;
                            delete attributes.parentId;
                            delete attributes.parentName;
                        }
                    }
                }
                else if (this.model.name == 'Lead') {
                    attributes.parentType = 'Lead',
                    attributes.parentId = this.model.id;
                    attributes.parentName = this.model.get('name');
                }

                if (this.model.name != 'Account' && this.model.has('contactsIds')) {
                    attributes.contactsIds = this.model.get('contactsIds');
                    attributes.contactsNames = this.model.get('contactsNames');
                }

                if (scope) {
                    if (!attributes.parentId) {
                        if (this.checkParentTypeAvailability(scope, this.model.name)) {
                            attributes.parentType = this.model.name;
                            attributes.parentId = this.model.id;
                            attributes.parentName = this.model.get('name');
                        }
                    }
                    else {
                        if (attributes.parentType && !this.checkParentTypeAvailability(scope, attributes.parentType)) {
                            attributes.parentType = null;
                            attributes.parentId = null;
                            attributes.parentName = null;
                        }
                    }
                }
            }

            callback.call(this, Espo.Utils.cloneDeep(attributes));
        },

        checkParentTypeAvailability: function (scope, parentType) {
            return ~(
                this.getMetadata().get(['entityDefs', scope, 'fields', 'parent', 'entityList']) || []
            ).indexOf(parentType);
        },

        actionCreateRelated: function (data) {
            data.link = this.entityTypeLinkMap[data.scope];

            if (this.createEntityTypeStatusMap[data.scope]) {
                data.status = this.createEntityTypeStatusMap[data.scope];
            }
            this.actionCreateActivity(data);
        },

        actionCreateActivity: function (data) {
            var link = data.link;
            var foreignLink;
            var scope;

            if (link) {
                var scope = this.model.getLinkParam(link, 'entity');
                var foreignLink = this.model.getLinkParam(link, 'foreign');
            }
            else {
                scope = data.scope;
            }

            var o = {
                scope: scope
            };

            if (link) {
                o.relate = {
                    model: this.model,
                    link: foreignLink
                };
            }

            this.notify('Loading...');

            var viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.edit') ||
                'views/modals/edit';

            this.getCreateActivityAttributes(scope, data, function (attributes) {
                o.attributes = attributes;

                this.createView('quickCreate', viewName, o , function (view) {
                    view.render();
                    view.notify(false);
                    this.listenToOnce(view, 'after:save', function () {
                        this.model.trigger('after:relate');
                        this.collection.fetch();
                        this.fetchHistory();
                    }, this);
                }, this);
            });

        },

        getComposeEmailAttributes: function (scope, data, callback) {
            data = data || {};
            var attributes = {
                status: 'Draft',
                to: this.model.get('emailAddress')
            };

            if (this.model.name == 'Contact') {
                if (this.getConfig().get('b2cMode')) {
                    attributes.parentType = 'Contact';
                    attributes.parentName = this.model.get('name');
                    attributes.parentId = this.model.id;
                }
                else {
                    if (this.model.get('accountId')) {
                        attributes.parentType = 'Account',
                        attributes.parentId = this.model.get('accountId');
                        attributes.parentName = this.model.get('accountName');
                    }
                }
            }
            else if (this.model.name == 'Lead') {
                attributes.parentType = 'Lead',
                attributes.parentId = this.model.id
                attributes.parentName = this.model.get('name');
            }

            if (~['Contact', 'Lead', 'Account'].indexOf(this.model.name) && this.model.get('emailAddress')) {
                attributes.nameHash = {};
                attributes.nameHash[this.model.get('emailAddress')] = this.model.get('name');
            }

            if (scope) {
                if (!attributes.parentId) {
                    if (this.checkParentTypeAvailability(scope, this.model.name)) {
                        attributes.parentType = this.model.name;
                        attributes.parentId = this.model.id;
                        attributes.parentName = this.model.get('name');
                    }
                }
                else {
                    if (attributes.parentType && !this.checkParentTypeAvailability(scope, attributes.parentType)) {
                        attributes.parentType = null;
                        attributes.parentId = null;
                        attributes.parentName = null;
                    }
                }
            }

            var emailKeepParentTeamsEntityList = this.getConfig().get('emailKeepParentTeamsEntityList') || [];

            if (
                attributes.parentType
                &&
                attributes.parentType === this.model.name
                &&
                ~emailKeepParentTeamsEntityList.indexOf(attributes.parentType)
                && this.model.get('teamsIds') && this.model.get('teamsIds').length
            ) {
                attributes.teamsIds = Espo.Utils.clone(this.model.get('teamsIds'));
                attributes.teamsNames = Espo.Utils.clone(this.model.get('teamsNames') || {});

                var defaultTeamId = this.getUser().get('defaultTeamId');

                if (defaultTeamId && !~attributes.teamsIds.indexOf(defaultTeamId)) {
                    attributes.teamsIds.push(defaultTeamId);
                    attributes.teamsNames[defaultTeamId] = this.getUser().get('defaultTeamName');
                }

                attributes.teamsIds = attributes.teamsIds.filter(function (teamId) {
                    return this.getAcl().checkTeamAssignmentPermission(teamId);
                }, this);
            }
            callback.call(this, attributes);
        },

        actionComposeEmail: function (data) {
            var self = this;
            var link = 'emails';
            var scope = 'Email';

            var relate = null;
            if ('emails' in this.model.defs['links']) {
                relate = {
                    model: this.model,
                    link: this.model.defs['links']['emails'].foreign
                };
            }

            this.notify('Loading...');

            this.getComposeEmailAttributes(scope, data, function (attributes) {
                this.createView('quickCreate', 'views/modals/compose-email', {
                    relate: relate,
                    attributes: attributes
                }, function (view) {
                    view.render();
                    view.notify(false);
                    this.listenToOnce(view, 'after:save', function () {
                        this.collection.fetch();
                        this.model.trigger('after:relate');
                        this.fetchHistory();
                    }, this);
                }, this);
            });

        },

        actionRefresh: function () {
            this.collection.fetch();
        },

        actionSetHeld: function (data) {
            var id = data.id;

            if (!id) {
                return;
            }

            var model = this.collection.get(id);

            model.save({
                status: 'Held'
            }, {
                patch: true,
                success: function () {
                    this.collection.fetch();
                    this.fetchHistory();
                }.bind(this)
            });
        },

        actionSetNotHeld: function (data) {
            var id = data.id;

            if (!id) {
                return;
            }

            var model = this.collection.get(id);

            model.save({
                status: 'Not Held'
            }, {
                patch: true,
                success: function () {
                    this.collection.fetch();
                    this.fetchHistory();
                }.bind(this)
            });
        },

        actionViewRelatedList: function (data) {
            data.url = 'Activities/' + this.model.name + '/' +
                this.model.id + '/' + this.name + '/list/' + data.scope;

            data.title = this.translate(this.defs.label) +
            ' @right ' + this.translate(data.scope, 'scopeNamesPlural');

            data.viewOptions = data.viewOptions || {};
            data.viewOptions.massUnlinkDisabled = true;

            Dep.prototype.actionViewRelatedList.call(this, data);
        }
    });
});

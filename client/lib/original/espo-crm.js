define("modules/crm/views/meeting/fields/attendees", ["exports", "views/fields/link-multiple-with-role"], function (_exports, _linkMultipleWithRole) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _linkMultipleWithRole = _interopRequireDefault(_linkMultipleWithRole);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _linkMultipleWithRole.default {
    columnName = 'status';
    roleFieldIsForeign = false;
    emptyRoleValue = 'None';
  }
  _exports.default = _default;
});

define("modules/crm/views/calendar/fields/teams", ["exports", "views/fields/link-multiple"], function (_exports, _linkMultiple) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _linkMultiple = _interopRequireDefault(_linkMultiple);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class CalendarSharedViewTeamsFieldView extends _linkMultiple.default {
    foreignScope = 'Team';
    getSelectBoolFilterList() {
      if (this.getAcl().getPermissionLevel('userCalendar') === 'team') {
        return ['onlyMy'];
      }
    }
  }
  _exports.default = CalendarSharedViewTeamsFieldView;
});

define("modules/crm/knowledge-base-helper", ["exports", "ajax"], function (_exports, _ajax) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _ajax = _interopRequireDefault(_ajax);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  /**
   * @todo Move to modules/crm/helpers.
   */
  class KnowledgeBaseHelper {
    /**
     * @param {module:language} language
     */
    constructor(language) {
      this.language = language;
    }
    getAttributesForEmail(model, attributes, callback) {
      attributes = attributes || {};
      attributes.body = model.get('body');
      if (attributes.name) {
        attributes.name = attributes.name + ' ';
      } else {
        attributes.name = '';
      }
      attributes.name += this.language.translate('KnowledgeBaseArticle', 'scopeNames') + ': ' + model.get('name');
      _ajax.default.postRequest('KnowledgeBaseArticle/action/getCopiedAttachments', {
        id: model.id,
        parentType: 'Email',
        field: 'attachments'
      }).then(data => {
        attributes.attachmentsIds = data.ids;
        attributes.attachmentsNames = data.names;
        attributes.isHtml = true;
        callback(attributes);
      });
    }
  }
  var _default = _exports.default = KnowledgeBaseHelper;
});

define("modules/crm/views/task/record/list", ["exports", "views/record/list"], function (_exports, _list) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _list = _interopRequireDefault(_list);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _list.default {
    rowActionsView = 'crm:views/task/record/row-actions/default';
    actionSetCompleted(data) {
      const id = data.id;
      if (!id) {
        return;
      }
      const model = this.collection.get(id);
      if (!model) {
        return;
      }
      Espo.Ui.notify(this.translate('saving', 'messages'));
      model.save({
        status: 'Completed'
      }, {
        patch: true
      }).then(() => {
        Espo.Ui.success(this.translate('Saved'));
        this.collection.fetch();
      });
    }
  }
  _exports.default = _default;
});

define("modules/crm/views/record/panels/tasks", ["exports", "views/record/panels/relationship", "helpers/record/create-related"], function (_exports, _relationship, _createRelated) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _relationship = _interopRequireDefault(_relationship);
  _createRelated = _interopRequireDefault(_createRelated);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class TasksRelationshipPanelView extends _relationship.default {
    name = 'tasks';
    entityType = 'Task';
    filterList = ['all', 'actual', 'completed'];
    orderBy = 'createdAt';
    orderDirection = 'desc';
    rowActionsView = 'crm:views/record/row-actions/tasks';
    buttonList = [{
      action: 'createTask',
      title: 'Create Task',
      acl: 'create',
      aclScope: 'Task',
      html: '<span class="fas fa-plus"></span>'
    }];
    actionList = [{
      label: 'View List',
      action: 'viewRelatedList'
    }];
    listLayout = {
      rows: [[{
        name: 'name',
        link: true
      }], [{
        name: 'isOverdue'
      }, {
        name: 'assignedUser'
      }, {
        name: 'dateEnd',
        soft: true
      }, {
        name: 'status'
      }]]
    };
    setup() {
      this.parentScope = this.model.entityType;
      this.link = 'tasks';
      this.panelName = 'tasksSide';
      this.defs.create = true;
      if (this.parentScope === 'Account') {
        this.link = 'tasksPrimary';
      }
      this.url = this.model.entityType + '/' + this.model.id + '/' + this.link;
      this.setupSorting();
      if (this.filterList && this.filterList.length) {
        this.filter = this.getStoredFilter();
      }
      this.setupFilterActions();
      this.setupTitle();
      this.wait(true);
      this.getCollectionFactory().create('Task', collection => {
        this.collection = collection;
        collection.seeds = this.seeds;
        collection.url = this.url;
        collection.orderBy = this.defaultOrderBy;
        collection.order = this.defaultOrder;
        collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;
        this.setFilter(this.filter);
        this.wait(false);
      });
      this.once('show', () => {
        if (!this.isRendered() && !this.isBeingRendered()) {
          this.collection.fetch();
        }
      });
      let events = `update-related:${this.link} update-all`;
      if (this.parentScope === 'Account') {
        events += ' update-related:tasks';
      }
      this.listenTo(this.model, events, () => this.collection.fetch());
    }
    afterRender() {
      this.createView('list', 'views/record/list-expanded', {
        selector: '> .list-container',
        pagination: false,
        type: 'listRelationship',
        rowActionsView: this.defs.rowActionsView || this.rowActionsView,
        checkboxes: false,
        collection: this.collection,
        listLayout: this.listLayout,
        skipBuildRows: true
      }, view => {
        view.getSelectAttributeList(selectAttributeList => {
          if (selectAttributeList) {
            this.collection.data.select = selectAttributeList.join(',');
          }
          if (!this.disabled) {
            this.collection.fetch();
            return;
          }
          this.once('show', () => this.collection.fetch());
        });
      });
    }
    actionCreateRelated() {
      this.actionCreateTask();
    }
    actionCreateTask() {
      let link = this.link;
      if (this.parentScope === 'Account') {
        link = 'tasks';
      }
      const helper = new _createRelated.default(this);
      helper.process(this.model, link);
    }

    // noinspection JSUnusedGlobalSymbols
    actionComplete(data) {
      const id = data.id;
      if (!id) {
        return;
      }
      const model = this.collection.get(id);
      model.save({
        status: 'Completed'
      }, {
        patch: true
      }).then(() => this.collection.fetch());
    }
    actionViewRelatedList(data) {
      data.viewOptions = data.viewOptions || {};
      data.viewOptions.massUnlinkDisabled = true;
      super.actionViewRelatedList(data);
    }
  }
  _exports.default = TasksRelationshipPanelView;
});

define("modules/crm/views/record/panels/activities", ["exports", "views/record/panels/relationship", "multi-collection", "helpers/record-modal"], function (_exports, _relationship, _multiCollection, _recordModal) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _relationship = _interopRequireDefault(_relationship);
  _multiCollection = _interopRequireDefault(_multiCollection);
  _recordModal = _interopRequireDefault(_recordModal);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class ActivitiesPanelView extends _relationship.default {
    name = 'activities';
    orderBy = 'dateStart';
    serviceName = 'Activities';
    order = 'desc';
    rowActionsView = 'crm:views/record/row-actions/activities';
    relatedListFiltersDisabled = true;
    buttonMaxCount = null;

    /**
     * @type {Array<module:views/record/panels-container~action|false>}
     */
    actionList = [{
      action: 'composeEmail',
      label: 'Compose Email',
      acl: 'create',
      aclScope: 'Email'
    }];
    listLayout = {};
    defaultListLayout = {
      rows: [[{
        name: 'ico',
        view: 'crm:views/fields/ico'
      }, {
        name: 'name',
        link: true,
        view: 'views/event/fields/name-for-history'
      }], [{
        name: 'dateStart',
        soft: true
      }, {
        name: 'assignedUser'
      }]]
    };
    BUTTON_MAX_COUNT = 3;
    setup() {
      this.scopeList = this.getConfig().get(this.name + 'EntityList') || [];
      this.buttonMaxCount = this.getConfig().get('activitiesCreateButtonMaxCount');
      if (typeof this.buttonMaxCount === 'undefined') {
        this.buttonMaxCount = this.BUTTON_MAX_COUNT;
      }
      this.listLayout = Espo.Utils.cloneDeep(this.listLayout);
      this.defs.create = true;
      this.createAvailabilityHash = {};
      this.entityTypeLinkMap = {};
      this.createEntityTypeStatusMap = {};
      this.setupActionList();
      this.setupFinalActionList();
      this.setupSorting();
      this.scopeList.forEach(item => {
        if (!(item in this.listLayout)) {
          this.listLayout[item] = this.defaultListLayout;
        }
      });
      this.url = this.serviceName + '/' + this.model.entityType + '/' + this.model.id + '/' + this.name;
      this.seeds = {};
      this.wait(true);
      let i = 0;
      this.scopeList.forEach(scope => {
        this.getModelFactory().create(scope, seed => {
          this.seeds[scope] = seed;
          i++;
          if (i === this.scopeList.length) {
            this.wait(false);
          }
        });
      });
      if (this.scopeList.length === 0) {
        this.wait(false);
      }
      this.filterList = [];

      /*this.scopeList.forEach(item => {
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
      });*/

      if (this.filterList.length) {
        this.filterList.unshift('all');
      }
      if (this.filterList && this.filterList.length) {
        this.filter = this.getStoredFilter();
      }
      this.setupFilterActions();
      this.setupTitle();
      this.collection = new _multiCollection.default();
      this.collection.seeds = this.seeds;
      this.collection.url = this.url;
      this.collection.orderBy = this.orderBy;
      this.collection.order = this.order;
      this.collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;
      let events = `update-related:activities update-all`;
      for (const entityType of this.scopeList) {
        const link = this.entityTypeLinkMap[entityType];
        if (!link) {
          continue;
        }
        events += ` update-related:${link}`;
      }
      if (this.name === 'history') {
        events += ' update-related:emails';
      }
      this.listenTo(this.model, events, () => this.collection.fetch());
      this.setFilter(this.filter);
      this.once('show', () => {
        if (!this.isRendered() && !this.isBeingRendered()) {
          this.collection.fetch();
        }
      });
    }
    translateFilter(name) {
      if (name === 'all') {
        return this.translate(name, 'presetFilters');
      }
      return this.translate(name, 'scopeNamesPlural');
    }
    isCreateAvailable(scope) {
      return this.createAvailabilityHash[scope];
    }
    setupActionList() {
      if (this.name === 'activities' && this.buttonMaxCount) {
        this.buttonList.push({
          action: 'composeEmail',
          title: 'Compose Email',
          acl: 'create',
          aclScope: 'Email',
          html: $('<span>').addClass(this.getMetadata().get(['clientDefs', 'Email', 'iconClass'])).get(0).outerHTML
        });
      }
      this.scopeList.forEach(scope => {
        if (!this.getMetadata().get(['clientDefs', scope, 'activityDefs', this.name + 'Create'])) {
          return;
        }
        if (!this.getAcl().checkScope(scope, 'create')) {
          return;
        }
        const label = (this.name === 'history' ? 'Log' : 'Schedule') + ' ' + scope;
        const o = {
          action: 'createActivity',
          text: this.translate(label, 'labels', scope),
          data: {},
          acl: 'create',
          aclScope: scope
        };
        const link = this.getMetadata().get(['clientDefs', scope, 'activityDefs', 'link']);
        if (link) {
          o.data.link = link;
          this.entityTypeLinkMap[scope] = link;
          if (!this.model.hasLink(link)) {
            return;
          }
        } else {
          o.data.scope = scope;
          if (this.model.entityType !== 'User' && !this.checkParentTypeAvailability(scope, this.model.entityType)) {
            return;
          }
        }
        this.createAvailabilityHash[scope] = true;
        o.data = o.data || {};
        if (!o.data.status) {
          const statusList = this.getMetadata().get(['scopes', scope, this.name + 'StatusList']);
          if (statusList && statusList.length) {
            o.data.status = statusList[0];
          }
        }
        this.createEntityTypeStatusMap[scope] = o.data.status;
        this.actionList.push(o);
        if (this.name === 'activities' && this.buttonList.length < this.buttonMaxCount) {
          const ob = Espo.Utils.cloneDeep(o);
          const iconClass = this.getMetadata().get(['clientDefs', scope, 'iconClass']);
          if (iconClass) {
            ob.title = label;
            ob.html = $('<span>').addClass(iconClass).get(0).outerHTML;
            this.buttonList.push(ob);
          }
        }
      });
    }
    setupFinalActionList() {
      this.scopeList.forEach((scope, i) => {
        if (i === 0 && this.actionList.length) {
          this.actionList.push(false);
        }
        if (!this.getAcl().checkScope(scope, 'read')) {
          return;
        }
        const o = {
          action: 'viewRelatedList',
          html: $('<span>').append($('<span>').text(this.translate(scope, 'scopeNamesPlural'))).get(0).innerHTML,
          data: {
            scope: scope
          },
          acl: 'read',
          aclScope: scope
        };
        this.actionList.push(o);
      });
    }
    setFilter(filter) {
      this.filter = filter;
      this.collection.data.entityType = null;
      if (filter && filter !== 'all') {
        this.collection.data.entityType = this.filter;
      }
    }
    afterRender() {
      const afterFetch = () => {
        this.createView('list', 'views/record/list-expanded', {
          selector: '> .list-container',
          pagination: false,
          type: 'listRelationship',
          rowActionsView: this.rowActionsView,
          checkboxes: false,
          collection: this.collection,
          listLayout: this.listLayout
        }, view => {
          view.render();
          this.listenTo(view, 'after:save', () => {
            this.model.trigger('update-related:activities');
          });
        });
      };
      if (!this.disabled) {
        this.collection.fetch().then(() => afterFetch());
      } else {
        this.once('show', () => {
          this.collection.fetch().then(() => afterFetch());
        });
      }
    }
    getCreateActivityAttributes(scope, data, callback) {
      data = data || {};
      const attributes = {
        status: data.status
      };
      if (this.model.entityType === 'User') {
        const model = /** @type {module:models/user} */this.model;
        if (model.isPortal()) {
          attributes.usersIds = [model.id];
          const usersIdsNames = {};
          usersIdsNames[model.id] = model.get('name');
          attributes.usersIdsNames = usersIdsNames;
        } else {
          attributes.assignedUserId = model.id;
          attributes.assignedUserName = model.get('name');
        }
      } else {
        if (this.model.entityType === 'Contact') {
          if (this.model.get('accountId') && !this.getConfig().get('b2cMode')) {
            attributes.parentType = 'Account';
            attributes.parentId = this.model.get('accountId');
            attributes.parentName = this.model.get('accountName');
            if (scope && !this.getMetadata().get(['entityDefs', scope, 'links', 'contacts']) && !this.getMetadata().get(['entityDefs', scope, 'links', 'contact'])) {
              delete attributes.parentType;
              delete attributes.parentId;
              delete attributes.parentName;
            }
          }
        } else if (this.model.entityType === 'Lead') {
          attributes.parentType = 'Lead';
          attributes.parentId = this.model.id;
          attributes.parentName = this.model.get('name');
        }
        if (this.model.entityType !== 'Account' && this.model.has('contactsIds')) {
          attributes.contactsIds = this.model.get('contactsIds');
          attributes.contactsNames = this.model.get('contactsNames');
        }
        if (scope) {
          if (!attributes.parentId) {
            if (this.checkParentTypeAvailability(scope, this.model.entityType)) {
              attributes.parentType = this.model.entityType;
              attributes.parentId = this.model.id;
              attributes.parentName = this.model.get('name');
            }
          } else {
            if (attributes.parentType && !this.checkParentTypeAvailability(scope, attributes.parentType)) {
              attributes.parentType = null;
              attributes.parentId = null;
              attributes.parentName = null;
            }
          }
        }
      }
      callback.call(this, Espo.Utils.cloneDeep(attributes));
    }
    checkParentTypeAvailability(scope, parentType) {
      return (this.getMetadata().get(['entityDefs', scope, 'fields', 'parent', 'entityList']) || []).includes(parentType);
    }

    // noinspection JSUnusedGlobalSymbols
    actionCreateRelated(data) {
      data.link = this.entityTypeLinkMap[data.scope];
      if (this.createEntityTypeStatusMap[data.scope]) {
        data.status = this.createEntityTypeStatusMap[data.scope];
      }
      this.actionCreateActivity(data);
    }

    /**
     * @protected
     * @param {Record} data
     */
    actionCreateActivity(data) {
      const link = data.link;
      let foreignLink;
      let scope;
      if (link) {
        scope = this.model.getLinkParam(link, 'entity');
        foreignLink = this.model.getLinkParam(link, 'foreign');
      } else {
        scope = data.scope;
      }
      Espo.Ui.notifyWait();
      this.getCreateActivityAttributes(scope, data, attributes => {
        const helper = new _recordModal.default();
        helper.showCreate(this, {
          entityType: scope,
          relate: link ? {
            model: this.model,
            link: foreignLink
          } : undefined,
          attributes: attributes,
          afterSave: () => {
            this.model.trigger(`update-related:${link}`);
            this.model.trigger('after:relate');
          }
        });
      });
    }
    getComposeEmailAttributes(scope, data, callback) {
      const attributes = {
        status: 'Draft',
        to: this.model.get('emailAddress')
      };
      if (this.model.entityType === 'Contact') {
        if (this.getConfig().get('b2cMode')) {
          attributes.parentType = 'Contact';
          attributes.parentName = this.model.get('name');
          attributes.parentId = this.model.id;
        } else if (this.model.get('accountId')) {
          attributes.parentType = 'Account';
          attributes.parentId = this.model.get('accountId');
          attributes.parentName = this.model.get('accountName');
        }
      } else if (this.model.entityType === 'Lead') {
        attributes.parentType = 'Lead';
        attributes.parentId = this.model.id;
        attributes.parentName = this.model.get('name');
      }
      if (['Contact', 'Lead', 'Account'].includes(this.model.entityType) && this.model.get('emailAddress')) {
        attributes.nameHash = {};
        attributes.nameHash[this.model.get('emailAddress')] = this.model.get('name');
      }
      if (scope) {
        if (!attributes.parentId) {
          if (this.checkParentTypeAvailability(scope, this.model.entityType)) {
            attributes.parentType = this.model.entityType;
            attributes.parentId = this.model.id;
            attributes.parentName = this.model.get('name');
          }
        } else if (attributes.parentType && !this.checkParentTypeAvailability(scope, attributes.parentType)) {
          attributes.parentType = null;
          attributes.parentId = null;
          attributes.parentName = null;
        }
      }
      const emailKeepParentTeamsEntityList = this.getConfig().get('emailKeepParentTeamsEntityList') || [];
      if (attributes.parentType && attributes.parentType === this.model.entityType && emailKeepParentTeamsEntityList.includes(attributes.parentType) && this.model.get('teamsIds') && this.model.get('teamsIds').length) {
        attributes.teamsIds = Espo.Utils.clone(this.model.get('teamsIds'));
        attributes.teamsNames = Espo.Utils.clone(this.model.get('teamsNames') || {});
        const defaultTeamId = this.getUser().get('defaultTeamId');
        if (defaultTeamId && !attributes.teamsIds.includes(defaultTeamId)) {
          attributes.teamsIds.push(defaultTeamId);
          attributes.teamsNames[defaultTeamId] = this.getUser().get('defaultTeamName');
        }
        attributes.teamsIds = attributes.teamsIds.filter(teamId => {
          return this.getAcl().checkTeamAssignmentPermission(teamId);
        });
      }
      if (this.model.attributes.accountId && this.model.getFieldType('account') === 'link' && this.model.getLinkParam('account', 'entity') === 'Account') {
        attributes.accountId = this.model.attributes.accountId;
        attributes.accountName = this.model.attributes.accountName;
      }
      if (!attributes.to && this.isBasePlus()) {
        Espo.Ui.notifyWait();
        Espo.Ajax.getRequest(`Activities/${this.model.entityType}/${this.model.id}/composeEmailAddressList`).then(/** Record[] */list => {
          if (!list.length) {
            callback.call(this, attributes);
            return;
          }
          attributes.to = '';
          attributes.nameHash = {};
          list.forEach(item => {
            attributes.to += item.emailAddress + ';';
            attributes.nameHash[item.emailAddress] = item.name;
          });
          Espo.Ui.notify(false);
          callback.call(this, attributes);
        });
        return;
      }
      callback.call(this, attributes);
    }

    // noinspection JSUnusedGlobalSymbols
    actionComposeEmail(data) {
      const scope = 'Email';
      let relate = null;
      if ('emails' in this.model.defs['links']) {
        relate = {
          model: this.model,
          link: this.model.defs['links']['emails'].foreign
        };
      }
      Espo.Ui.notifyWait();
      this.getComposeEmailAttributes(scope, data, attributes => {
        this.createView('quickCreate', 'views/modals/compose-email', {
          relate: relate,
          attributes: attributes
        }, view => {
          view.render();
          view.notify(false);
          this.listenToOnce(view, 'after:save', () => {
            this.model.trigger(`update-related:emails`);
            this.model.trigger('after:relate');
          });
        });
      });
    }
    actionSetHeld(data) {
      const id = data.id;
      if (!id) {
        return;
      }
      const model = this.collection.get(id);
      model.save({
        status: 'Held'
      }, {
        patch: true
      }).then(() => {
        this.model.trigger(`update-related:activities`);
      });
    }
    actionSetNotHeld(data) {
      const id = data.id;
      if (!id) {
        return;
      }
      const model = this.collection.get(id);
      model.save({
        status: 'Not Held'
      }, {
        patch: true
      }).then(() => {
        this.model.trigger(`update-related:activities`);
      });
    }
    actionViewRelatedList(data) {
      data.url = `Activities/${this.model.entityType}/${this.model.id}/${this.name}/list/${data.scope}`;
      data.title = this.translate(this.defs.label) + ' @right ' + this.translate(data.scope, 'scopeNamesPlural');
      const viewOptions = /** @type {Record.<string, *>} */data.viewOptions || {};
      const fullFormUrl = `#${this.model.entityType}/${this.name}/${this.model.id}/${data.scope}`;
      viewOptions.massUnlinkDisabled = true;
      viewOptions.fullFormUrl = fullFormUrl;
      viewOptions.createDisabled = true;
      data.viewOptions = viewOptions;
      super.actionViewRelatedList(data);
    }

    /**
     * @protected
     * @return {boolean}
     */
    isBasePlus() {
      const type = this.getMetadata().get(`scopes.${this.model.entityType}.type`);
      return type === 'BasePlus';
    }
  }
  var _default = _exports.default = ActivitiesPanelView;
});

define("modules/crm/views/meeting/detail", ["exports", "views/detail", "moment"], function (_exports, _detail, _moment) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _detail = _interopRequireDefault(_detail);
  _moment = _interopRequireDefault(_moment);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class MeetingDetailView extends _detail.default {
    cancellationPeriod = '8 hours';
    setup() {
      super.setup();
      this.setupStatuses();
      this.addMenuItem('buttons', {
        name: 'sendInvitations',
        text: this.translate('Send Invitations', 'labels', 'Meeting'),
        acl: 'edit',
        hidden: true,
        onClick: () => this.actionSendInvitations()
      });
      this.addMenuItem('dropdown', {
        name: 'sendCancellation',
        text: this.translate('Send Cancellation', 'labels', 'Meeting'),
        acl: 'edit',
        hidden: true,
        onClick: () => this.actionSendCancellation()
      });
      this.addMenuItem('buttons', {
        name: 'setAcceptanceStatus',
        text: '',
        hidden: true,
        onClick: () => this.actionSetAcceptanceStatus()
      });
      this.setupCancellationPeriod();
      this.controlSendInvitationsButton();
      this.controlAcceptanceStatusButton();
      this.controlSendCancellationButton();
      this.listenTo(this.model, 'sync', () => {
        this.controlSendInvitationsButton();
        this.controlSendCancellationButton();
      });
      this.listenTo(this.model, 'sync', () => this.controlAcceptanceStatusButton());
    }
    setupStatuses() {
      this.canceledStatusList = this.getMetadata().get(`scopes.${this.entityType}.canceledStatusList`) || [];
      this.notActualStatusList = [...(this.getMetadata().get(`scopes.${this.entityType}.completedStatusList`) || []), ...this.canceledStatusList];
    }
    setupCancellationPeriod() {
      this.cancellationPeriodAmount = 0;
      this.cancellationPeriodUnits = 'hours';
      const cancellationPeriod = this.getConfig().get('eventCancellationPeriod') || this.cancellationPeriod;
      if (!cancellationPeriod) {
        return;
      }
      const arr = cancellationPeriod.split(' ');
      this.cancellationPeriodAmount = parseInt(arr[0]);
      this.cancellationPeriodUnits = arr[1] ?? 'hours';
    }
    controlAcceptanceStatusButton() {
      if (!this.model.has('status')) {
        return;
      }
      if (!this.model.has('usersIds')) {
        return;
      }
      if (this.notActualStatusList.includes(this.model.get('status'))) {
        this.hideHeaderActionItem('setAcceptanceStatus');
        return;
      }
      if (!this.model.getLinkMultipleIdList('users').includes(this.getUser().id)) {
        this.hideHeaderActionItem('setAcceptanceStatus');
        return;
      }
      const acceptanceStatus = this.model.getLinkMultipleColumn('users', 'status', this.getUser().id);
      let text;
      let style = 'default';
      if (acceptanceStatus && acceptanceStatus !== 'None') {
        text = this.getLanguage().translateOption(acceptanceStatus, 'acceptanceStatus', this.model.entityType);
        style = this.getMetadata().get(['entityDefs', this.model.entityType, 'fields', 'acceptanceStatus', 'style', acceptanceStatus]);
      } else {
        text = this.translate('Acceptance', 'labels', 'Meeting');
      }
      let iconHtml = '';
      if (style) {
        const iconClass = {
          'success': 'fas fa-check-circle',
          'danger': 'fas fa-times-circle',
          'warning': 'fas fa-question-circle'
        }[style];
        iconHtml = $('<span>').addClass(iconClass).addClass('text-' + style).get(0).outerHTML;
      }
      this.updateMenuItem('setAcceptanceStatus', {
        text: text,
        iconHtml: iconHtml,
        hidden: false
      });
    }
    controlSendInvitationsButton() {
      let show = true;
      if (this.notActualStatusList.includes(this.model.get('status'))) {
        show = false;
      }
      if (show && !this.getAcl().checkModel(this.model, 'edit')) {
        show = false;
      }
      if (show) {
        const userIdList = this.model.getLinkMultipleIdList('users');
        const contactIdList = this.model.getLinkMultipleIdList('contacts');
        const leadIdList = this.model.getLinkMultipleIdList('leads');
        if (!contactIdList.length && !leadIdList.length && !userIdList.length) {
          show = false;
        }
        /*else if (
            !contactIdList.length &&
            !leadIdList.length &&
            userIdList.length === 1 &&
            userIdList[0] === this.getUser().id &&
            this.model.getLinkMultipleColumn('users', 'status', this.getUser().id) === 'Accepted'
        ) {
            show = false;
        }*/
      }
      if (show) {
        const dateEnd = this.model.get('dateEnd');
        if (dateEnd && this.getDateTime().toMoment(dateEnd).isBefore(_moment.default.now())) {
          show = false;
        }
      }
      show ? this.showHeaderActionItem('sendInvitations') : this.hideHeaderActionItem('sendInvitations');
    }
    controlSendCancellationButton() {
      let show = this.canceledStatusList.includes(this.model.get('status'));
      if (show) {
        const dateEnd = this.model.get('dateEnd');
        if (dateEnd && this.getDateTime().toMoment(dateEnd).add(this.cancellationPeriodAmount, this.cancellationPeriodUnits).isBefore(_moment.default.now())) {
          show = false;
        }
      }
      if (show) {
        const userIdList = this.model.getLinkMultipleIdList('users');
        const contactIdList = this.model.getLinkMultipleIdList('contacts');
        const leadIdList = this.model.getLinkMultipleIdList('leads');
        if (!contactIdList.length && !leadIdList.length && !userIdList.length) {
          show = false;
        }
      }
      show ? this.showHeaderActionItem('sendCancellation') : this.hideHeaderActionItem('sendCancellation');
    }
    actionSendInvitations() {
      Espo.Ui.notifyWait();
      this.createView('dialog', 'crm:views/meeting/modals/send-invitations', {
        model: this.model
      }).then(view => {
        Espo.Ui.notify(false);
        view.render();
        this.listenToOnce(view, 'sent', () => this.model.fetch());
      });
    }
    actionSendCancellation() {
      Espo.Ui.notifyWait();
      this.createView('dialog', 'crm:views/meeting/modals/send-cancellation', {
        model: this.model
      }).then(view => {
        Espo.Ui.notify(false);
        view.render();
        this.listenToOnce(view, 'sent', () => this.model.fetch());
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionSetAcceptanceStatus() {
      this.createView('dialog', 'crm:views/meeting/modals/acceptance-status', {
        model: this.model
      }, view => {
        view.render();
        this.listenTo(view, 'set-status', status => {
          this.disableMenuItem('setAcceptanceStatus');
          Espo.Ui.notifyWait();
          Espo.Ajax.postRequest(this.model.entityType + '/action/setAcceptanceStatus', {
            id: this.model.id,
            status: status
          }).then(() => {
            this.model.fetch().then(() => {
              Espo.Ui.notify(false);
              this.enableMenuItem('setAcceptanceStatus');
            });
          }).catch(() => this.enableMenuItem('setAcceptanceStatus'));
        });
      });
    }
  }
  var _default = _exports.default = MeetingDetailView;
});

define("modules/crm/views/meeting/record/list", ["exports", "views/record/list"], function (_exports, _list) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _list = _interopRequireDefault(_list);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _list.default {
    rowActionsView = 'modules/crm/views/meeting/record/row-actions/default';
    setup() {
      super.setup();
      if (this.getAcl().checkScope(this.entityType, 'edit') && this.getAcl().checkField(this.entityType, 'status', 'edit')) {
        this.massActionList.push('setHeld');
        this.massActionList.push('setNotHeld');
      }
    }

    /**
     * @protected
     * @param {Record} data
     */
    async actionSetHeld(data) {
      const id = data.id;
      if (!id) {
        return;
      }
      const model = this.collection.get(id);
      if (!model) {
        return;
      }
      Espo.Ui.notify(this.translate('saving', 'messages'));
      await model.save({
        status: 'Held'
      }, {
        patch: true
      });
      Espo.Ui.success(this.translate('Saved'));
    }

    /**
     * @protected
     * @param {Record} data
     */
    async actionSetNotHeld(data) {
      const id = data.id;
      if (!id) {
        return;
      }
      const model = this.collection.get(id);
      if (!model) {
        return;
      }
      Espo.Ui.notify(this.translate('saving', 'messages'));
      await model.save({
        status: 'Not Held'
      }, {
        patch: true
      });
      Espo.Ui.success(this.translate('Saved'));
    }

    // noinspection JSUnusedGlobalSymbols
    async massActionSetHeld() {
      const data = {};
      data.ids = this.checkedList;
      Espo.Ui.notify(this.translate('saving', 'messages'));
      await Espo.Ajax.postRequest(`${this.collection.entityType}/action/massSetHeld`, data);
      Espo.Ui.success(this.translate('Saved'));
      await this.collection.fetch();
      data.ids.forEach(id => {
        if (this.collection.get(id)) {
          this.checkRecord(id);
        }
      });
    }

    // noinspection JSUnusedGlobalSymbols
    async massActionSetNotHeld() {
      const data = {};
      data.ids = this.checkedList;
      Espo.Ui.notify(this.translate('saving', 'messages'));
      await Espo.Ajax.postRequest(`${this.collection.entityType}/action/massSetNotHeld`, data);
      Espo.Ui.success(this.translate('Saved'));
      await this.collection.fetch();
      data.ids.forEach(id => {
        if (this.collection.get(id)) {
          this.checkRecord(id);
        }
      });
    }
  }
  _exports.default = _default;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/mass-email/record/edit', ['views/record/edit'], function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.initFieldsControl();
        },

        initFieldsControl: function () {
            this.listenTo(this.model, 'change:smtpAccount', (model, value, o) => {
                if (!o.ui) {
                    return;
                }

                if (!value || value === 'system') {
                    this.model.set('fromAddress', this.getConfig().get('outboundEmailFromAddress') || '');
                    this.model.set('fromName', this.getConfig().get('outboundEmailFromName') || '');

                    return;
                }

                var smtpAccountView = this.getFieldView('smtpAccount');

                if (!smtpAccountView) {
                    return;
                }

                if (!smtpAccountView.loadedOptionAddresses) {
                    return;
                }

                if (!smtpAccountView.loadedOptionAddresses[value]) {
                    return;
                }

                this.model.set('fromAddress', smtpAccountView.loadedOptionAddresses[value]);
                this.model.set('fromName', smtpAccountView.loadedOptionFromNames[value]);
            });
        },
    });
});

define("modules/crm/views/mass-email/modals/send-test", ["exports", "views/modal", "model", "views/record/edit-for-modal", "views/fields/link-multiple"], function (_exports, _modal, _model, _editForModal, _linkMultiple) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _modal = _interopRequireDefault(_modal);
  _model = _interopRequireDefault(_model);
  _editForModal = _interopRequireDefault(_editForModal);
  _linkMultiple = _interopRequireDefault(_linkMultiple);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class MassEmailSendTestModalView extends _modal.default {
    // language=Handlebars
    templateContent = `
        <div class="record-container no-side-margin">{{{record}}}</div>
    `;

    /**
     * @private
     * @type {EditForModalRecordView}
     */
    recordView;

    /**
     * @private
     * @type {Model}
     */
    formModel;

    /**
     * @param {{model: import('model').default}} options
     */
    constructor(options) {
      super(options);
      this.model = options.model;
    }
    setup() {
      super.setup();
      this.headerText = this.translate('Send Test', 'labels', 'MassEmail');
      const formModel = this.formModel = new _model.default();
      formModel.set('usersIds', [this.getUser().id]);
      const usersNames = {};
      usersNames[this.getUser().id] = this.getUser().get('name');
      formModel.set('usersNames', usersNames);
      this.recordView = new _editForModal.default({
        model: formModel,
        detailLayout: [{
          rows: [[{
            view: new _linkMultiple.default({
              name: 'users',
              labelText: this.translate('users', 'links', 'TargetList'),
              mode: 'edit',
              params: {
                entity: 'User'
              }
            })
          }, false], [{
            view: new _linkMultiple.default({
              name: 'contacts',
              labelText: this.translate('contacts', 'links', 'TargetList'),
              mode: 'edit',
              params: {
                entity: 'Contact'
              }
            })
          }, false], [{
            view: new _linkMultiple.default({
              name: 'leads',
              labelText: this.translate('leads', 'links', 'TargetList'),
              mode: 'edit',
              params: {
                entity: 'Lead'
              }
            })
          }, false], [{
            view: new _linkMultiple.default({
              name: 'accounts',
              labelText: this.translate('accounts', 'links', 'TargetList'),
              mode: 'edit',
              params: {
                entity: 'Account'
              }
            })
          }, false]]
        }]
      });
      this.assignView('record', this.recordView);
      this.buttonList.push({
        name: 'sendTest',
        label: 'Send Test',
        style: 'danger',
        onClick: () => this.actionSendTest()
      });
      this.buttonList.push({
        name: 'cancel',
        label: 'Cancel',
        onClick: () => this.actionClose()
      });
    }
    actionSendTest() {
      const list = [];
      if (Array.isArray(this.formModel.attributes.usersIds)) {
        this.formModel.attributes.usersIds.forEach(id => {
          list.push({
            id: id,
            type: 'User'
          });
        });
      }
      if (Array.isArray(this.formModel.attributes.contactsIds)) {
        this.formModel.attributes.contactsIds.forEach(id => {
          list.push({
            id: id,
            type: 'Contact'
          });
        });
      }
      if (Array.isArray(this.formModel.attributes.leadsIds)) {
        this.formModel.attributes.leadsIds.forEach(id => {
          list.push({
            id: id,
            type: 'Lead'
          });
        });
      }
      if (Array.isArray(this.formModel.attributes.accountsIds)) {
        this.formModel.attributes.accountsIds.forEach(id => {
          list.push({
            id: id,
            type: 'Account'
          });
        });
      }
      if (list.length === 0) {
        Espo.Ui.error(this.translate('selectAtLeastOneTarget', 'messages', 'MassEmail'));
        return;
      }
      this.disableButton('sendTest');
      Espo.Ui.notifyWait();
      Espo.Ajax.postRequest('MassEmail/action/sendTest', {
        id: this.model.id,
        targetList: list
      }).then(() => {
        Espo.Ui.success(this.translate('testSent', 'messages', 'MassEmail'));
        this.close();
      }).catch(() => {
        this.enableButton('sendTest');
      });
    }
  }
  _exports.default = MassEmailSendTestModalView;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/dashlets/options/chart', ['views/dashlets/options/base'], function (Dep) {

    return Dep.extend({

        setupBeforeFinal: function () {
            this.listenTo(this.model, 'change:dateFilter', this.controlDateFilter);
            this.controlDateFilter();
        },

        controlDateFilter: function () {
            if (this.model.get('dateFilter') === 'between') {
                this.showField('dateFrom');
                this.showField('dateTo');
            } else {
                this.hideField('dateFrom');
                this.hideField('dateTo');
            }
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/contact/record/detail', ['views/record/detail'], function (Dep) {

    return Dep.extend({});
});

define("modules/crm/views/call/record/list", ["exports", "views/record/list"], function (_exports, _list) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _list = _interopRequireDefault(_list);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _list.default {
    rowActionsView = 'modules/crm/views/call/record/row-actions/default';
    setup() {
      super.setup();
      if (this.getAcl().checkScope(this.entityType, 'edit') && this.getAcl().checkField(this.entityType, 'status', 'edit')) {
        this.massActionList.push('setHeld');
        this.massActionList.push('setNotHeld');
      }
    }

    /**
     * @protected
     * @param {Record} data
     */
    async actionSetHeld(data) {
      const id = data.id;
      if (!id) {
        return;
      }
      const model = this.collection.get(id);
      if (!model) {
        return;
      }
      Espo.Ui.notify(this.translate('saving', 'messages'));
      await model.save({
        status: 'Held'
      }, {
        patch: true
      });
      Espo.Ui.success(this.translate('Saved'));
    }

    /**
     * @protected
     * @param {Record} data
     */
    async actionSetNotHeld(data) {
      const id = data.id;
      if (!id) {
        return;
      }
      const model = this.collection.get(id);
      if (!model) {
        return;
      }
      Espo.Ui.notify(this.translate('saving', 'messages'));
      await model.save({
        status: 'Not Held'
      }, {
        patch: true
      });
      Espo.Ui.success(this.translate('Saved'));
    }

    // noinspection JSUnusedGlobalSymbols
    async massActionSetHeld() {
      const data = {};
      data.ids = this.checkedList;
      Espo.Ui.notify(this.translate('saving', 'messages'));
      await Espo.Ajax.postRequest(`${this.collection.entityType}/action/massSetHeld`, data);
      Espo.Ui.success(this.translate('Saved'));
      await this.collection.fetch();
      data.ids.forEach(id => {
        if (this.collection.get(id)) {
          this.checkRecord(id);
        }
      });
    }

    // noinspection JSUnusedGlobalSymbols
    async massActionSetNotHeld() {
      const data = {};
      data.ids = this.checkedList;
      Espo.Ui.notify(this.translate('saving', 'messages'));
      await Espo.Ajax.postRequest(`${this.collection.entityType}/action/massSetNotHeld`, data);
      Espo.Ui.success(this.translate('Saved'));
      await this.collection.fetch();
      data.ids.forEach(id => {
        if (this.collection.get(id)) {
          this.checkRecord(id);
        }
      });
    }
  }
  _exports.default = _default;
});

define("modules/crm/views/call/fields/contacts", ["exports", "modules/crm/views/meeting/fields/attendees"], function (_exports, _attendees) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _attendees = _interopRequireDefault(_attendees);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _attendees.default {
    getAttributeList() {
      return [...super.getAttributeList(), 'phoneNumbersMap'];
    }
    getDetailLinkHtml(id, name) {
      const html = super.getDetailLinkHtml(id, name);
      const key = this.foreignScope + '_' + id;
      const phoneNumbersMap = this.model.get('phoneNumbersMap') || {};
      if (!(key in phoneNumbersMap)) {
        return html;
      }
      const number = phoneNumbersMap[key];
      const $item = $(html);

      // @todo Format phone number.

      $item.append(' ', $('<span>').addClass('text-muted middle-dot'), ' ', $('<a>').attr('href', 'tel:' + number).attr('data-phone-number', number).attr('data-action', 'dial').addClass('small').text(number));
      return $('<div>').append($item).get(0).outerHTML;
    }
  }
  _exports.default = _default;
});

define("modules/crm/views/calendar/modals/edit-view", ["exports", "views/modal", "model", "views/record/edit-for-modal", "views/fields/enum", "views/fields/varchar", "crm:views/calendar/fields/teams"], function (_exports, _modal, _model, _editForModal, _enum, _varchar, _teams) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _modal = _interopRequireDefault(_modal);
  _model = _interopRequireDefault(_model);
  _editForModal = _interopRequireDefault(_editForModal);
  _enum = _interopRequireDefault(_enum);
  _varchar = _interopRequireDefault(_varchar);
  _teams = _interopRequireDefault(_teams);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class CalendarEditViewModal extends _modal.default {
    // language=Handlebars
    templateContent = `
        <div class="record-container no-side-margin">{{{record}}}</div>
    `;
    className = 'dialog dialog-record';

    /**
     * @private
     * @type {EditForModalRecordView}
     */
    recordView;

    /**
     * @param {{
     *     afterSave?: function({id: string}): void,
     *     afterRemove?: function(): void,
     *     id?: string,
     * }} options
     */
    constructor(options) {
      super();
      this.options = options;
    }
    setup() {
      const id = this.options.id;
      this.buttonList = [{
        name: 'cancel',
        label: 'Cancel',
        onClick: () => this.actionCancel()
      }];
      this.isNew = !id;
      const calendarViewDataList = this.getPreferences().get('calendarViewDataList') || [];
      if (this.isNew) {
        this.buttonList.unshift({
          name: 'save',
          label: 'Create',
          style: 'danger',
          onClick: () => this.actionSave()
        });
      } else {
        this.dropdownItemList.push({
          name: 'remove',
          label: 'Remove',
          onClick: () => this.actionRemove()
        });
        this.buttonList.unshift({
          name: 'save',
          label: 'Save',
          style: 'primary',
          onClick: () => this.actionSave()
        });
      }
      const model = new _model.default();
      model.name = 'CalendarView';
      const modelData = {};
      if (!this.isNew) {
        calendarViewDataList.forEach(item => {
          if (id === item.id) {
            modelData.teamsIds = item.teamIdList || [];
            modelData.teamsNames = item.teamNames || {};
            modelData.id = item.id;
            modelData.name = item.name;
            modelData.mode = item.mode;
          }
        });
      } else {
        modelData.name = this.translate('Shared', 'labels', 'Calendar');
        let foundCount = 0;
        calendarViewDataList.forEach(item => {
          if (item.name.indexOf(modelData.name) === 0) {
            foundCount++;
          }
        });
        if (foundCount) {
          modelData.name += ' ' + foundCount;
        }
        modelData.id = id;
        modelData.teamsIds = this.getUser().get('teamsIds') || [];
        modelData.teamsNames = this.getUser().get('teamsNames') || {};
      }
      model.set(modelData);
      this.recordView = new _editForModal.default({
        model: model,
        detailLayout: [{
          rows: [[{
            view: new _varchar.default({
              name: 'name',
              labelText: this.translate('name', 'fields'),
              params: {
                required: true
              }
            })
          }, {
            view: new _enum.default({
              name: 'mode',
              labelText: this.translate('mode', 'fields', 'DashletOptions'),
              params: {
                translation: 'DashletOptions.options.mode',
                options: this.getMetadata().get('clientDefs.Calendar.sharedViewModeList') || []
              }
            })
          }], [{
            view: new _teams.default({
              name: 'teams',
              labelText: this.translate('teams', 'fields'),
              params: {
                required: true
              }
            })
          }, false]]
        }]
      });
      this.assignView('record', this.recordView);
      if (this.isNew) {
        this.headerText = this.translate('Create Shared View', 'labels', 'Calendar');
      } else {
        this.headerText = this.translate('Edit Shared View', 'labels', 'Calendar') + ' · ' + modelData.name;
      }
    }
    async actionSave() {
      if (this.recordView.validate()) {
        return;
      }
      const modelData = this.recordView.fetch();
      const calendarViewDataList = this.getPreferences().get('calendarViewDataList') || [];
      const data = {
        name: modelData.name,
        teamIdList: modelData.teamsIds,
        teamNames: modelData.teamsNames,
        mode: modelData.mode,
        id: undefined
      };
      if (this.isNew) {
        data.id = Math.random().toString(36).substring(2, 12);
        calendarViewDataList.push(data);
      } else {
        data.id = this.getView('record').model.id;
        calendarViewDataList.forEach((item, i) => {
          if (item.id === data.id) {
            calendarViewDataList[i] = data;
          }
        });
      }
      Espo.Ui.notify(this.translate('saving', 'messages'));
      this.disableButton('save');
      this.disableButton('remove');
      try {
        await this.getPreferences().save({
          calendarViewDataList: calendarViewDataList
        }, {
          patch: true
        });
      } catch (e) {
        this.enableButton('remove');
        this.enableButton('save');
        return;
      }
      Espo.Ui.notify();
      this.trigger('after:save', data);
      if (this.options.afterSave) {
        this.options.afterSave(data);
      }
      this.close();
    }
    async actionRemove() {
      await this.confirm(this.translate('confirmation', 'messages'));
      this.disableButton('save');
      this.disableButton('remove');
      const id = this.options.id;
      if (!id) {
        return;
      }
      const newCalendarViewDataList = [];
      const calendarViewDataList = this.getPreferences().get('calendarViewDataList') || [];
      calendarViewDataList.forEach(item => {
        if (item.id !== id) {
          newCalendarViewDataList.push(item);
        }
      });
      Espo.Ui.notifyWait();
      try {
        await this.getPreferences().save({
          calendarViewDataList: newCalendarViewDataList
        }, {
          patch: true
        });
      } catch (e) {
        this.enableButton('remove');
        this.enableButton('save');
        return;
      }
      Espo.Ui.notify();
      this.trigger('after:remove');
      if (this.options.afterRemove) {
        this.options.afterRemove();
      }
      this.close();
    }
  }
  _exports.default = CalendarEditViewModal;
});

define("modules/crm/views/calendar/fields/users", ["exports", "views/fields/link-multiple"], function (_exports, _linkMultiple) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _linkMultiple = _interopRequireDefault(_linkMultiple);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class CalendarUsersFieldView extends _linkMultiple.default {
    foreignScope = 'User';
    sortable = true;
    getSelectBoolFilterList() {
      if (this.getAcl().getPermissionLevel('userCalendar') === 'team') {
        return ['onlyMyTeam'];
      }
    }
    getSelectPrimaryFilterName() {
      return 'active';
    }
  }
  var _default = _exports.default = CalendarUsersFieldView;
});

define("modules/crm/acl/meeting", ["exports", "acl"], function (_exports, _acl) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _acl = _interopRequireDefault(_acl);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class MeetingAcl extends _acl.default {
    // noinspection JSUnusedGlobalSymbols
    checkModelRead(model, data, precise) {
      return this._checkModelCustom('read', model, data, precise);
    }

    // noinspection JSUnusedGlobalSymbols
    checkModelStream(model, data, precise) {
      return this._checkModelCustom('stream', model, data, precise);
    }
    _checkModelCustom(action, model, data, precise) {
      let result = this.checkModel(model, data, action, precise);
      if (result) {
        return true;
      }
      if (data === false) {
        return false;
      }
      let d = data || {};
      if (d[action] === 'no') {
        return false;
      }
      if (model.has('usersIds')) {
        if (~(model.get('usersIds') || []).indexOf(this.getUser().id)) {
          return true;
        }
      } else if (precise) {
        return null;
      }
      return result;
    }
  }
  var _default = _exports.default = MeetingAcl;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/user/record/panels/tasks', ['crm:views/record/panels/tasks'], function (Dep) {

    return Dep.extend({

        listLayout: {
            rows: [
                [
                    {
                        name: 'name',
                        link: true,
                    },
                    {
                        name: 'isOverdue',
                    }
                ],
                [
                    {name: 'status'},
                    {name: 'dateEnd'},
                ],
            ]
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.getMetadata().get(['entityDefs', 'Task', 'fields', 'assignedUsers'])) {
                var foreignLink = this.getMetadata().get(['entityDefs', 'Task', 'links', 'assignedUsers', 'foreign']);

                if (foreignLink) {
                    this.link = foreignLink;
                }
            }
        },
    });
});

define("modules/crm/views/task/list", ["exports", "views/list"], function (_exports, _list) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _list = _interopRequireDefault(_list);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _list.default {}
  _exports.default = _default;
  ;
});

define("modules/crm/views/task/detail", ["exports", "views/detail"], function (_exports, _detail) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _detail = _interopRequireDefault(_detail);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _detail.default {}
  _exports.default = _default;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/task/record/list-expanded',
['views/record/list-expanded', 'crm:views/task/record/list'], function (Dep, List) {

    return Dep.extend({

        rowActionsView: 'crm:views/task/record/row-actions/default',

        actionSetCompleted: function (data) {
            List.prototype.actionSetCompleted.call(this, data);
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/task/record/detail', ['views/record/detail'], function (Dep) {

    return Dep.extend({

        duplicateAction: true,

        setupActionItems: function () {
            Dep.prototype.setupActionItems.call(this);
            if (this.getAcl().checkModel(this.model, 'edit')) {
                if (
                    !~['Completed', 'Canceled'].indexOf(this.model.get('status')) &&
                    this.getAcl().checkField(this.entityType, 'status', 'edit')
                ) {
                    this.dropdownItemList.push({
                        'label': 'Complete',
                        'name': 'setCompleted'
                    });
                }

                this.listenToOnce(this.model, 'sync', function () {
                    if (~['Completed', 'Canceled'].indexOf(this.model.get('status'))) {
                        this.removeButton('setCompleted');
                    }
                }, this);
            }
        },

        manageAccessEdit: function (second) {
            Dep.prototype.manageAccessEdit.call(this, second);

            if (second) {
                if (!this.getAcl().checkModel(this.model, 'edit', true)) {
                    this.hideActionItem('setCompleted');
                }
            }
        },

        actionSetCompleted: function () {
            this.model.save({status: 'Completed'}, {patch: true})
                .then(() => Espo.Ui.success(this.translate('Saved')));

        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/task/record/row-actions/default', ['views/record/row-actions/view-and-edit'], function (Dep) {

    return Dep.extend({

        getActionList: function () {
            var actionList = Dep.prototype.getActionList.call(this);

            if (this.options.acl.edit && !~['Completed', 'Canceled'].indexOf(this.model.get('status'))) {
                actionList.push({
                    action: 'setCompleted',
                    label: 'Complete',
                    data: {
                        id: this.model.id
                    },
                    groupIndex: 1,
                });
            }

            if (this.options.acl.delete) {
                actionList.push({
                    action: 'quickRemove',
                    label: 'Remove',
                    data: {
                        id: this.model.id,
                        scope: this.model.entityType
                    },
                    groupIndex: 0,
                });
            }

            return actionList;
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/task/record/row-actions/dashlet', ['views/record/row-actions/view-and-edit'], function (Dep) {

    return Dep.extend({

        getActionList: function () {
            var actionList = Dep.prototype.getActionList.call(this);

            if (this.options.acl.edit && !~['Completed', 'Canceled'].indexOf(this.model.get('status'))) {
                actionList.push({
                    action: 'setCompleted',
                    label: 'Complete',
                    data: {
                        id: this.model.id
                    },
                    groupIndex: 1,
                });
            }

            if (this.options.acl.delete) {
                actionList.push({
                    action: 'quickRemove',
                    label: 'Remove',
                    data: {
                        id: this.model.id,
                        scope: this.model.entityType
                    },
                    groupIndex: 0,
                });
            }

            return actionList;
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/task/modals/detail', ['views/modals/detail'], function (Dep) {

    return Dep.extend({});
});

define("modules/crm/views/task/fields/tasks", ["exports", "views/fields/link-multiple-with-status"], function (_exports, _linkMultipleWithStatus) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _linkMultipleWithStatus = _interopRequireDefault(_linkMultipleWithStatus);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _linkMultipleWithStatus.default {
    setup() {
      super.setup();
      this.canceledStatusList = this.getMetadata().get(`scopes.Task.canceledStatusList`) || [];
    }
  }
  _exports.default = _default;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/task/fields/priority-for-dashlet', ['views/fields/enum'], function (Dep) {

    return Dep.extend({

        data: function () {
            var data = Dep.prototype.data.call(this);

            if (!data.style || data.style === 'default') {
                data.isNotEmpty = false;
            }

            return data;
        },
    });
});

define("modules/crm/views/task/fields/is-overdue", ["exports", "views/fields/base", "moment"], function (_exports, _base, _moment) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _base = _interopRequireDefault(_base);
  _moment = _interopRequireDefault(_moment);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  // noinspection JSUnusedGlobalSymbols
  class _default extends _base.default {
    readOnly = true;
    templateContent = `
        {{~#if isOverdue}}
        <span class="label label-danger">{{translate "overdue" scope="Task"}}</span>
        {{/if~}}
    `;
    data() {
      let isOverdue = false;
      if (['Completed', 'Canceled'].indexOf(this.model.get('status')) === -1) {
        if (this.model.has('dateEnd')) {
          if (!this.isDate()) {
            const value = this.model.get('dateEnd');
            if (value) {
              const d = this.getDateTime().toMoment(value);
              const now = _moment.default.tz(this.getDateTime().timeZone || 'UTC');
              if (d.unix() < now.unix()) {
                isOverdue = true;
              }
            }
          } else {
            const value = this.model.get('dateEndDate');
            if (value) {
              const d = _moment.default.utc(value + ' 23:59', this.getDateTime().internalDateTimeFormat);
              const now = this.getDateTime().getNowMoment();
              if (d.unix() < now.unix()) {
                isOverdue = true;
              }
            }
          }
        }
      }
      return {
        isOverdue: isOverdue
      };
    }
    setup() {
      this.mode = 'detail';
    }
    isDate() {
      const dateValue = this.model.get('dateEnd');
      if (dateValue) {
        return true;
      }
      return false;
    }
  }
  _exports.default = _default;
});

define("modules/crm/views/task/fields/date-end", ["exports", "views/fields/datetime-optional", "moment"], function (_exports, _datetimeOptional, _moment) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _datetimeOptional = _interopRequireDefault(_datetimeOptional);
  _moment = _interopRequireDefault(_moment);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class TaskDateEndFieldView extends _datetimeOptional.default {
    isEnd = true;
    getAttributeList() {
      return [...super.getAttributeList(), 'status'];
    }
    data() {
      const data = super.data();
      const status = this.model.attributes.status;
      if (!status || this.notActualStatusList.includes(status)) {
        return data;
      }
      if (this.mode === this.MODE_DETAIL || this.mode === this.MODE_LIST) {
        if (this.isDateInPast()) {
          data.isOverdue = true;
        } else if (this.isDateToday()) {
          data.style = 'warning';
        }
      }
      if (data.isOverdue) {
        data.style = 'danger';
      }
      return data;
    }
    setup() {
      super.setup();
      this.notActualStatusList = [...(this.getMetadata().get(`scopes.${this.entityType}.completedStatusList`) || []), ...(this.getMetadata().get(`scopes.${this.entityType}.canceledStatusList`) || [])];
      if (this.isEditMode() || this.isDetailMode()) {
        this.on('change', () => {
          if (!this.model.get('dateEnd') && this.model.get('reminders')) {
            this.model.set('reminders', []);
          }
        });
      }
    }

    /**
     * @private
     * @return {boolean}
     */
    isDateInPast() {
      if (this.isDate()) {
        const value = this.model.get(this.nameDate);
        if (value) {
          const d = _moment.default.tz(value + ' 23:59', this.getDateTime().getTimeZone());
          const now = this.getDateTime().getNowMoment();
          if (d.unix() < now.unix()) {
            return true;
          }
        }
      }
      const value = this.model.get(this.name);
      if (value) {
        const d = this.getDateTime().toMoment(value);
        const now = (0, _moment.default)().tz(this.getDateTime().timeZone || 'UTC');
        if (d.unix() < now.unix()) {
          return true;
        }
      }
      return false;
    }

    /**
     * @private
     * @return {boolean}
     */
    isDateToday() {
      if (!this.isDate()) {
        return false;
      }
      return this.getDateTime().getToday() === this.model.attributes[this.nameDate];
    }
  }
  var _default = _exports.default = TaskDateEndFieldView;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/target-list/record/detail', ['views/record/detail'], function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'after:relate', () => {
                this.model.fetch();
            });

            this.listenTo(this.model, 'after:unrelate', () => {
                this.model.fetch();
            });
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/target-list/record/row-actions/opted-out', ['views/record/row-actions/default'], function (Dep) {

    return Dep.extend({

        getActionList: function () {
            return [
                {
                    action: 'cancelOptOut',
                    text: this.translate('Cancel Opt-Out', 'labels', 'TargetList'),
                    data: {
                        id: this.model.id,
                        type: this.model.entityType,
                    },
                },
            ];
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/target-list/record/row-actions/default', ['views/record/row-actions/relationship'], function (Dep) {

    return Dep.extend({

        getActionList: function () {
            const list = Dep.prototype.getActionList.call(this);

            if (this.options.acl.edit) {
                if (this.model.get('targetListIsOptedOut')) {
                    list.push({
                        action: 'cancelOptOut',
                        text: this.translate('Cancel Opt-Out', 'labels', 'TargetList'),
                        data: {
                            id: this.model.id,
                            type: this.model.entityType,
                        },
                    });
                } else {
                    list.push({
                        action: 'optOut',
                        text: this.translate('Opt-Out', 'labels', 'TargetList'),
                        data: {
                            id: this.model.id,
                            type: this.model.entityType,
                        },
                    });
                }
            }

            return list;
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/target-list/record/panels/relationship', ['views/record/panels/relationship'], function (Dep) {

    return Dep.extend({

        fetchOnModelAfterRelate: true,

        actionOptOut: function (data) {
            this.confirm(this.translate('confirmation', 'messages'), () => {
                Espo.Ajax
                    .postRequest('TargetList/action/optOut', {
                        id: this.model.id,
                        targetId: data.id,
                        targetType: data.type,
                    })
                    .then(() => {
                        this.collection.fetch();
                        Espo.Ui.success(this.translate('Done'));
                        this.model.trigger('opt-out');
                    });
            });
        },

        actionCancelOptOut: function (data) {
            this.confirm(this.translate('confirmation', 'messages'), () => {
                Espo.Ajax
                    .postRequest('TargetList/action/cancelOptOut', {
                        id: this.model.id,
                        targetId: data.id,
                        targetType: data.type,
                    })
                    .then(() => {
                        this.collection.fetch();
                        Espo.Ui.success(this.translate('Done'));

                        this.collection.fetch();
                        this.model.trigger('cancel-opt-out');
                    });
            });
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/target-list/record/panels/opted-out',  ['views/record/panels/relationship', 'multi-collection'],
function (Dep, MultiCollection) {

    return Dep.extend({

        name: 'optedOut',

        template: 'crm:target-list/record/panels/opted-out',

        scopeList: ['Contact', 'Lead', 'User', 'Account'],

        data: function () {
            return {
                currentTab: this.currentTab,
                scopeList: this.scopeList,
            };
        },

        getStorageKey: function () {
            return 'target-list-opted-out-' + this.model.entityType + '-' + this.name;
        },

        setup: function () {
            this.seeds = {};

            let linkList = this.getMetadata().get(['scopes', 'TargetList', 'targetLinkList']) || [];

            this.scopeList = [];

            linkList.forEach(link => {
                let entityType = this.getMetadata().get(['entityDefs', 'TargetList', 'links', link, 'entity']);

                if (entityType) {
                    this.scopeList.push(entityType);
                }
            });

            this.listLayout = {};

            this.scopeList.forEach(scope => {
                this.listLayout[scope] = {
                    rows: [
                        [
                            {
                                name: 'name',
                                link: true,
                            }
                        ]
                    ]
                };
            });

            if (this.scopeList.length) {
                this.wait(true);

                var i = 0;

                this.scopeList.forEach(scope => {
                    this.getModelFactory().create(scope, seed => {
                        this.seeds[scope] = seed;

                        i++;

                        if (i === this.scopeList.length) {
                            this.wait(false);
                        }
                    });
                });
            }

            this.listenTo(this.model, 'opt-out', () => {
                this.actionRefresh();
            });

            this.listenTo(this.model, 'cancel-opt-out', () => {
                this.actionRefresh();
            });
        },

        afterRender: function () {
            var url = 'TargetList/' + this.model.id + '/' + this.name;

            this.collection = new MultiCollection();
            this.collection.seeds = this.seeds;
            this.collection.url = url;

            this.collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;

            this.listenToOnce(this.collection, 'sync', () => {
                this.createView('list', 'views/record/list-expanded', {
                    selector: '> .list-container',
                    pagination: false,
                    type: 'listRelationship',
                    rowActionsView: 'crm:views/target-list/record/row-actions/opted-out',
                    checkboxes: false,
                    collection: this.collection,
                    listLayout: this.listLayout,
                }, view => {
                    view.render();
                });
            });

            this.collection.fetch();
        },

        actionRefresh: function () {
            this.collection.fetch();
        },

        actionCancelOptOut: function (data) {
            this.confirm(this.translate('confirmation', 'messages'), () => {
                Espo.Ajax.postRequest('TargetList/action/cancelOptOut', {
                    id: this.model.id,
                    targetId: data.id,
                    targetType: data.type,
                }).then(() => {
                    this.collection.fetch();
                });
            });
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/target-list/fields/target-status', ['views/fields/base'], function (Dep) {

    return Dep.extend({

        getValueForDisplay: function () {
            if (this.model.get('isOptedOut')) {
                return this.getLanguage().translateOption('Opted Out', 'targetStatus', 'TargetList');
            }

            return this.getLanguage().translateOption('Listed', 'targetStatus', 'TargetList');
        }
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/target-list/fields/including-action-list', ['views/fields/multi-enum'], function (Dep) {

    return Dep.extend({

        setupOptions: function () {
            this.params.options = this.getMetadata().get('entityDefs.CampaignLogRecord.fields.action.options') || [];
            this.translatedOptions = {};

            this.params.options.forEach(item => {
                this.translatedOptions[item] = this.getLanguage().translateOption(item, 'action', 'CampaignLogRecord');
            });
        },
    });
});

define("modules/crm/views/stream/notes/event-confirmation", ["exports", "views/stream/note"], function (_exports, _note) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _note = _interopRequireDefault(_note);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class EventConfirmationNoteView extends _note.default {
    // language=Handlebars
    templateContent = `
        {{#unless noEdit}}
        <div class="pull-right right-container cell-buttons">
        {{{right}}}
        </div>
        {{/unless}}

        <div class="stream-head-container">
            <div class="pull-left">
                {{{avatar}}}
            </div>
            <div class="stream-head-text-container">
                {{#if iconHtml}}{{{iconHtml}}}{{/if}}
                <span class="text-muted message">{{{message}}}</span>
            </div>
        </div>
        <div class="stream-post-container">
            <span class="{{statusIconClass}} text-{{style}}"></span>
        </div>
        <div class="stream-date-container">
            <a class="text-muted small" href="#Note/view/{{model.id}}">{{{createdAt}}}</a>
        </div>
    `;
    data() {
      const statusIconClass = {
        'success': 'fas fa-check fa-sm',
        'danger': 'fas fa-times fa-sm',
        'warning': 'fas fa-question fa-sm'
      }[this.style] || '';
      return {
        ...super.data(),
        statusText: this.statusText,
        style: this.style,
        statusIconClass: statusIconClass,
        iconHtml: this.getIconHtml()
      };
    }
    init() {
      if (this.getUser().isAdmin()) {
        this.isRemovable = true;
      }
      super.init();
    }
    setup() {
      this.inviteeType = this.model.get('relatedType');
      this.inviteeId = this.model.get('relatedId');
      this.inviteeName = this.model.get('relatedName');
      const data = this.model.get('data') || {};
      const status = data.status || 'Tentative';
      this.style = data.style || 'default';
      this.statusText = this.getLanguage().translateOption(status, 'acceptanceStatus', 'Meeting');
      this.messageName = 'eventConfirmation' + status;
      if (this.isThis) {
        this.messageName += 'This';
      }
      this.messageData['invitee'] = $('<a>').attr('href', '#' + this.inviteeType + '/view/' + this.inviteeId).attr('data-id', this.inviteeId).attr('data-scope', this.inviteeType).text(this.inviteeName);
      this.createMessage();
    }
  }

  // noinspection JSUnusedGlobalSymbols
  var _default = _exports.default = EventConfirmationNoteView;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/record/list-activities-dashlet',
['views/record/list-expanded', 'crm:views/meeting/record/list', 'crm:views/task/record/list'],
function (Dep, MeetingList, TaskList) {

    return Dep.extend({

        actionSetHeld: function (data) {
            MeetingList.prototype.actionSetHeld.call(this, data);
        },

        actionSetNotHeld: function (data) {
            MeetingList.prototype.actionSetNotHeld.call(this, data);
        },

        actionSetCompleted: function (data) {
            TaskList.prototype.actionSetCompleted.call(this, data);
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/record/row-actions/tasks', ['views/record/row-actions/relationship-no-unlink'], function (Dep) {

    return Dep.extend({

        getActionList: function () {
            var list = [{
                action: 'quickView',
                label: 'View',
                data: {
                    id: this.model.id
                },
                link: '#' + this.model.entityType + '/view/' + this.model.id,
                groupIndex: 0,
            }];

            if (this.options.acl.edit) {
                list.push({
                    action: 'quickEdit',
                    label: 'Edit',
                    data: {
                        id: this.model.id
                    },
                    link: '#' + this.model.entityType + '/edit/' + this.model.id,
                    groupIndex: 0,
                });

                if (!~['Completed', 'Canceled'].indexOf(this.model.get('status'))) {
                    list.push({
                        action: 'Complete',
                        text: this.translate('Complete', 'labels', 'Task'),
                        data: {
                            id: this.model.id
                        },
                        groupIndex: 1,
                    });
                }
            }

            if (this.options.acl.delete) {
                list.push({
                    action: 'removeRelated',
                    label: 'Remove',
                    data: {
                        id: this.model.id
                    },
                    groupIndex: 0,
                });
            }

            return list;
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/record/row-actions/relationship-target', ['views/record/row-actions/relationship-unlink-only'], function (Dep) {

    return Dep.extend({

        getActionList: function () {
            var list = Dep.prototype.getActionList.call(this);

            if (this.options.acl.edit) {
                if (this.model.get('isOptedOut')) {
                    list.push({
                        action: 'cancelOptOut',
                        text: this.translate('Cancel Opt-Out', 'labels', 'TargetList'),
                        data: {
                            id: this.model.id
                        },
                        groupIndex: 1,
                    });
                } else {
                    list.push({
                        action: 'optOut',
                        text: this.translate('Opt-Out', 'labels', 'TargetList'),
                        data: {
                            id: this.model.id
                        },
                        groupIndex: 1,
                    });
                }
            }

            return list;
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/record/row-actions/history', ['views/record/row-actions/relationship'], function (Dep) {

    return Dep.extend({

        getActionList: function () {
            var list = [{
                action: 'quickView',
                label: 'View',
                data: {
                    id: this.model.id
                },
                link: '#' + this.model.entityType + '/view/' + this.model.id,
                groupIndex: 0,
            }];

            if (this.model.entityType === 'Email') {
                list.push({
                    action: 'reply',
                    text: this.translate('Reply', 'labels', 'Email'),
                    data: {
                        id: this.model.id
                    },
                    groupIndex: 1,
                });
            }

            if (this.options.acl.edit) {
                list = list.concat([
                    {
                        action: 'quickEdit',
                        label: 'Edit',
                        data: {
                            id: this.model.id
                        },
                        link: '#' + this.model.entityType + '/edit/' + this.model.id,
                        groupIndex: 0,
                    },
                ]);
            }

            if (this.options.acl.delete) {
                list.push({
                    action: 'removeRelated',
                    label: 'Remove',
                    data: {
                        id: this.model.id
                    },
                    groupIndex: 0,
                });
            }

            return list;
        },

    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/record/row-actions/activities', ['views/record/row-actions/relationship'], function (Dep) {

    return Dep.extend({

        getActionList: function () {
            var list = [{
                action: 'quickView',
                label: 'View',
                data: {
                    id: this.model.id,
                },
                link: '#' + this.model.entityType + '/view/' + this.model.id,
                groupIndex: 0,
            }];

            if (this.options.acl.edit) {
                list.push({
                    action: 'quickEdit',
                    label: 'Edit',
                    data: {
                        id: this.model.id,
                    },
                    link: '#' + this.model.entityType + '/edit/' + this.model.id,
                    groupIndex: 0,
                });

                if (this.model.entityType === 'Meeting' || this.model.entityType === 'Call') {
                    list.push({
                        action: 'setHeld',
                        text: this.translate('Set Held', 'labels', 'Meeting'),
                        data: {
                            id: this.model.id,
                        },
                        groupIndex: 1,
                    });

                    list.push({
                        action: 'setNotHeld',
                        text: this.translate('Set Not Held', 'labels', 'Meeting'),
                        data: {
                            id: this.model.id,
                        },
                        groupIndex: 1,
                    });
                }
            }

            if (this.options.acl.delete) {
                list.push({
                    action: 'removeRelated',
                    label: 'Remove',
                    data: {
                        id: this.model.id,
                    },
                    groupIndex: 0,
                });
            }

            return list;
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/record/row-actions/activities-dashlet', ['views/record/row-actions/view-and-edit'], function (Dep) {

    return Dep.extend({

        getActionList: function () {
            var actionList = Dep.prototype.getActionList.call(this);

            var scope = this.model.entityType;

            actionList.forEach(function (item) {
                item.data = item.data || {};
                item.data.scope = this.model.entityType;
            }, this);

            if (scope === 'Task') {
                if (this.options.acl.edit && !~['Completed', 'Canceled'].indexOf(this.model.get('status'))) {
                    actionList.push({
                        action: 'setCompleted',
                        label: 'Complete',
                        data: {
                            id: this.model.id
                        },
                        groupIndex: 1,
                    });
                }
            } else {
                if (this.options.acl.edit && !~['Held', 'Not Held'].indexOf(this.model.get('status'))) {
                    actionList.push({
                        action: 'setHeld',
                        label: 'Set Held',
                        data: {
                            id: this.model.id,
                            scope: this.model.entityType
                        },
                        groupIndex: 1,
                    });
                    actionList.push({
                        action: 'setNotHeld',
                        label: 'Set Not Held',
                        data: {
                            id: this.model.id,
                            scope: this.model.entityType
                        },
                        groupIndex: 1,
                    });
                }
            }

            if (this.options.acl.edit) {
                actionList.push({
                    action: 'quickRemove',
                    label: 'Remove',
                    data: {
                        id: this.model.id,
                        scope: this.model.entityType
                    },
                    groupIndex: 0,
                });
            }

            return actionList;
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/record/panels/target-lists', ['views/record/panels/relationship'], function (Dep) {

    return Dep.extend({

        actionOptOut: function (data) {
            this.confirm(this.translate('confirmation', 'messages'), () => {
                Espo.Ajax
                    .postRequest('TargetList/action/optOut', {
                        id: data.id,
                        targetId: this.model.id,
                        targetType: this.model.entityType,
                    })
                    .then(() => {
                        this.collection.fetch();
                        Espo.Ui.success(this.translate('Done'));
                        this.model.trigger('opt-out');
                    });
            });
        },

        actionCancelOptOut: function (data) {
            this.confirm(this.translate('confirmation', 'messages'), () => {
                Espo.Ajax
                    .postRequest('TargetList/action/cancelOptOut', {
                        id: data.id,
                        targetId: this.model.id,
                        targetType: this.model.entityType,
                    })
                    .then(() => {
                        this.collection.fetch();
                        Espo.Ui.success(this.translate('Done'));
                        this.model.trigger('cancel-opt-out');
                    });
            });
        },
    });
});

define("modules/crm/views/record/panels/history", ["exports", "crm:views/record/panels/activities", "email-helper", "helpers/record-modal"], function (_exports, _activities, _emailHelper, _recordModal) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _activities = _interopRequireDefault(_activities);
  _emailHelper = _interopRequireDefault(_emailHelper);
  _recordModal = _interopRequireDefault(_recordModal);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class HistoryPanelView extends _activities.default {
    name = 'history';
    orderBy = 'dateStart';
    orderDirection = 'desc';
    rowActionsView = 'crm:views/record/row-actions/history';
    actionList = [];
    listLayout = {
      'Email': {
        rows: [[{
          name: 'ico',
          view: 'crm:views/fields/ico'
        }, {
          name: 'name',
          link: true
        }], [{
          name: 'dateSent',
          soft: true
        }, {
          name: 'from'
        }, {
          name: 'hasAttachment',
          view: 'views/email/fields/has-attachment'
        }]]
      }
    };
    where = {
      scope: false
    };
    setupActionList() {
      super.setupActionList();
      this.actionList.push({
        action: 'archiveEmail',
        label: 'Archive Email',
        acl: 'create',
        aclScope: 'Email'
      });
    }
    getArchiveEmailAttributes(scope, data, callback) {
      const attributes = {
        dateSent: this.getDateTime().getNow(15),
        status: 'Archived',
        from: this.model.get('emailAddress'),
        to: this.getUser().get('emailAddress')
      };
      if (this.model.entityType === 'Contact') {
        if (this.getConfig().get('b2cMode')) {
          attributes.parentType = 'Contact';
          attributes.parentName = this.model.get('name');
          attributes.parentId = this.model.id;
        } else {
          if (this.model.get('accountId')) {
            attributes.parentType = 'Account';
            attributes.parentId = this.model.get('accountId');
            attributes.parentName = this.model.get('accountName');
          }
        }
      } else if (this.model.entityType === 'Lead') {
        attributes.parentType = 'Lead';
        attributes.parentId = this.model.id;
        attributes.parentName = this.model.get('name');
      }
      attributes.nameHash = {};
      attributes.nameHash[this.model.get('emailAddress')] = this.model.get('name');
      if (scope) {
        if (!attributes.parentId) {
          if (this.checkParentTypeAvailability(scope, this.model.entityType)) {
            attributes.parentType = this.model.entityType;
            attributes.parentId = this.model.id;
            attributes.parentName = this.model.get('name');
          }
        } else {
          if (attributes.parentType && !this.checkParentTypeAvailability(scope, attributes.parentType)) {
            attributes.parentType = null;
            attributes.parentId = null;
            attributes.parentName = null;
          }
        }
      }
      callback.call(this, attributes);
    }

    // noinspection JSUnusedGlobalSymbols
    actionArchiveEmail(data) {
      const scope = 'Email';
      let relate = null;
      if (this.model.hasLink('emails')) {
        relate = {
          model: this.model,
          link: this.model.getLinkParam('emails', 'foreign')
        };
      }
      this.getArchiveEmailAttributes(scope, data, attributes => {
        const helper = new _recordModal.default();
        helper.showCreate(this, {
          entityType: 'Email',
          attributes: attributes,
          relate: relate,
          afterSave: () => {
            this.collection.fetch();
            this.model.trigger('after:relate');
          }
        });
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionReply(data) {
      const id = data.id;
      if (!id) {
        return;
      }
      const emailHelper = new _emailHelper.default();
      Espo.Ui.notifyWait();
      this.getModelFactory().create('Email').then(model => {
        model.id = id;
        model.fetch().then(() => {
          const attributes = emailHelper.getReplyAttributes(model, data, this.getPreferences().get('emailReplyToAllByDefault'));
          const viewName = this.getMetadata().get('clientDefs.Email.modalViews.compose') || 'views/modals/compose-email';
          return this.createView('quickCreate', viewName, {
            attributes: attributes,
            focusForCreate: true
          });
        }).then(view => {
          view.render();
          this.listenToOnce(view, 'after:save', () => {
            this.collection.fetch();
            this.model.trigger('after:relate');
          });
          Espo.Ui.notify(false);
        });
      });
    }
  }
  var _default = _exports.default = HistoryPanelView;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/opportunity/detail', ['views/detail'], function (Dep) {

    /** Left for bc. */
    return Dep.extend({});
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/opportunity/record/list', ['views/record/list'], function (Dep) {

    return Dep.extend({

    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/opportunity/record/kanban', ['views/record/kanban'], function (Dep) {

    return Dep.extend({

        handleAttributesOnGroupChange: function (model, attributes, group) {
            if (this.statusField !== 'stage') {
                return;
            }

            var probability = this.getMetadata()
                .get(['entityDefs', 'Opportunity', 'fields', 'stage', 'probabilityMap', group]);

            probability = parseInt(probability);
            attributes['probability'] = probability;
        },
    });
});

define("modules/crm/views/opportunity/record/edit", ["exports", "views/record/edit"], function (_exports, _edit) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _edit = _interopRequireDefault(_edit);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class OpportunityEditRecordView extends _edit.default {}
  var _default = _exports.default = OpportunityEditRecordView;
});

define("modules/crm/views/opportunity/record/edit-small", ["exports", "views/record/edit-small"], function (_exports, _editSmall) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _editSmall = _interopRequireDefault(_editSmall);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class OpportunityEditSmallRecordView extends _editSmall.default {}
  var _default = _exports.default = OpportunityEditSmallRecordView;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/opportunity/record/panels/activities', ['crm:views/record/panels/activities'], function (Dep) {

    return Dep.extend({

        getComposeEmailAttributes: function (scope, data, callback) {
            data = data || {};

            Espo.Ui.notifyWait();

            Dep.prototype.getComposeEmailAttributes.call(this, scope, data, (attributes) => {
                Espo.Ajax.getRequest('Opportunity/action/emailAddressList?id=' + this.model.id).then(list => {
                    attributes.to = '';
                    attributes.cc = '';
                    attributes.nameHash = {};

                    list.forEach(item => {
                        attributes.to += item.emailAddress + ';';
                        attributes.nameHash[item.emailAddress] = item.name;
                    });

                    Espo.Ui.notify(false);

                    callback.call(this, attributes);

                });
            })
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/opportunity/fields/stage', ['views/fields/enum'], function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.probabilityMap = this.getMetadata().get('entityDefs.Opportunity.fields.stage.probabilityMap') || {};

            if (this.mode !== 'list') {
                this.on('change', () => {
                    var probability = this.probabilityMap[this.model.get(this.name)];

                    if (probability !== null && probability !== undefined) {
                        this.model.set('probability', probability);
                    }
                });
            }
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/opportunity/fields/lead-source', ['views/fields/enum'], function (Dep) {

    return Dep.extend({});
});

define("modules/crm/views/opportunity/fields/last-stage", ["exports", "views/fields/enum"], function (_exports, _enum) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _enum = _interopRequireDefault(_enum);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  // noinspection JSUnusedGlobalSymbols
  class _default extends _enum.default {
    setup() {
      /** @type {string[]} */
      const optionList = this.getMetadata().get('entityDefs.Opportunity.fields.stage.options', []);

      /** @type {Record.<string, number|null>} */
      const probabilityMap = this.getMetadata().get('entityDefs.Opportunity.fields.stage.probabilityMap', {});
      this.params.options = [];
      optionList.forEach(item => {
        if (!probabilityMap[item]) {
          return;
        }
        if (probabilityMap[item] === 100) {
          return;
        }
        this.params.options.push(item);
      });
      this.params.translation = 'Opportunity.options.stage';
      super.setup();
    }
  }
  _exports.default = _default;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/opportunity/fields/contacts',
['views/fields/link-multiple-with-columns-with-primary'], function (Dep) {

    /** Left for bc. */
    return Dep.extend({});
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/opportunity/fields/contact-role', ['views/fields/enum'], function (Dep) {

    return Dep.extend({

        searchTypeList: ['anyOf', 'noneOf'],
    });
});

define("modules/crm/views/opportunity/admin/field-manager/fields/probability-map", ["exports", "views/fields/base", "jquery"], function (_exports, _base, _jquery) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _base = _interopRequireDefault(_base);
  _jquery = _interopRequireDefault(_jquery);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  // noinspection JSUnusedGlobalSymbols
  class _default extends _base.default {
    editTemplateContent = `
        <div class="list-group link-container no-input">
            {{#each stageList}}
                <div class="list-group-item form-inline">
                    <div style="display: inline-block; width: 100%;">
                        <input
                            class="role form-control input-sm pull-right"
                            data-name="{{./this}}" value="{{prop ../values this}}"
                        >
                        <div>{{./this}}</div>
                    </div>
                    <br class="clear: both;">
                </div>
            {{/each}}
        </div>
    `;
    setup() {
      super.setup();
      this.listenTo(this.model, 'change:options', function (m, v, o) {
        const probabilityMap = this.model.get('probabilityMap') || {};
        if (o.ui) {
          (this.model.get('options') || []).forEach(item => {
            if (!(item in probabilityMap)) {
              probabilityMap[item] = 50;
            }
          });
          this.model.set('probabilityMap', probabilityMap);
        }
        this.reRender();
      });
    }
    data() {
      const data = {};
      const values = this.model.get('probabilityMap') || {};
      data.stageList = this.model.get('options') || [];
      data.values = values;
      return data;
    }
    fetch() {
      const data = {
        probabilityMap: {}
      };
      (this.model.get('options') || []).forEach(item => {
        data.probabilityMap[item] = parseInt((0, _jquery.default)(this.element).find(`input[data-name="${item}"]`).val());
      });
      return data;
    }
    afterRender() {
      (0, _jquery.default)(this.element).find('input').on('change', () => {
        this.trigger('change');
      });
    }
  }
  _exports.default = _default;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/notification/items/event-attendee', ['views/notification/items/base'], function (Dep) {

    return Dep.extend({

        messageName: 'eventAttendee',

        templateContent: `
            <div class="stream-head-container">
                <div class="pull-left">{{{avatar}}}</div>
                <div class="stream-head-text-container">
                    <span class="text-muted message">{{{message}}}</span>
                </div>
            </div>
            <div class="stream-date-container">
                <span class="text-muted small">{{{createdAt}}}</span>
            </div>
        `,

        setup: function () {
            let data = this.model.get('data') || {};

            this.userId = data.userId;

            this.messageData['entityType'] = this.translateEntityType(data.entityType);

            this.messageData['entity'] =
                $('<a>')
                    .attr('href', '#' + data.entityType + '/view/' + data.entityId)
                    .attr('data-id', data.entityId)
                    .attr('data-scope', data.entityType)
                    .text(data.entityName);

            this.messageData['user'] =
                $('<a>')
                    .attr('href', '#User/view/' + data.userId)
                    .attr('data-id', data.userId)
                    .attr('data-scope', 'User')
                    .text(data.userName);

            this.createMessage();
        },
    });
});

define("modules/crm/views/meeting/popup-notification", ["exports", "views/popup-notification"], function (_exports, _popupNotification) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _popupNotification = _interopRequireDefault(_popupNotification);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class MeetingPopupNotificationView extends _popupNotification.default {
    template = 'crm:meeting/popup-notification';
    type = 'event';
    style = 'primary';
    closeButton = true;
    setup() {
      if (!this.notificationData.entityType) {
        return;
      }
      const promise = this.getModelFactory().create(this.notificationData.entityType, model => {
        const field = this.notificationData.dateField;
        const fieldType = model.getFieldParam(field, 'type') || 'base';
        const viewName = this.getFieldManager().getViewName(fieldType);
        model.set(this.notificationData.attributes);
        this.createView('date', viewName, {
          model: model,
          mode: 'detail',
          selector: `.field[data-name="${field}"]`,
          name: field,
          readOnly: true
        });
      });
      this.wait(promise);
    }
    data() {
      return {
        header: this.translate(this.notificationData.entityType, 'scopeNames'),
        dateField: this.notificationData.dateField,
        ...super.data()
      };
    }
    onCancel() {
      Espo.Ajax.postRequest('Activities/action/removePopupNotification', {
        id: this.notificationId
      });
    }
  }
  var _default = _exports.default = MeetingPopupNotificationView;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/meeting/record/list-expanded',
['views/record/list-expanded', 'crm:views/meeting/record/list'], function (Dep, List) {

    return Dep.extend({

        actionSetHeld: function (data) {
            List.prototype.actionSetHeld.call(this, data);
        },

        actionSetNotHeld: function (data) {
            List.prototype.actionSetNotHeld.call(this, data);
        },
    });
});

define("modules/crm/views/meeting/record/edit-small", ["exports", "views/record/edit"], function (_exports, _edit) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _edit = _interopRequireDefault(_edit);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _edit.default {}
  _exports.default = _default;
});

define("modules/crm/views/meeting/record/detail", ["exports", "views/record/detail"], function (_exports, _detail) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _detail = _interopRequireDefault(_detail);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class MeetingDetailRecordView extends _detail.default {
    duplicateAction = true;
    setupActionItems() {
      super.setupActionItems();
      if (!this.getAcl().checkModel(this.model, 'edit')) {
        return;
      }
      if (['Held', 'Not Held'].includes(this.model.get('status')) || !this.getAcl().checkField(this.entityType, 'status', 'edit')) {
        return;
      }
      const historyStatusList = this.getMetadata().get(`scopes.${this.entityType}.historyStatusList`) || [];
      if (!historyStatusList.includes('Held') || !historyStatusList.includes('Not Held')) {
        return;
      }
      this.dropdownItemList.push({
        'label': 'Set Held',
        'name': 'setHeld',
        onClick: () => this.actionSetHeld()
      });
      this.dropdownItemList.push({
        'label': 'Set Not Held',
        'name': 'setNotHeld',
        onClick: () => this.actionSetNotHeld()
      });
    }
    manageAccessEdit(second) {
      super.manageAccessEdit(second);
      if (second && !this.getAcl().checkModel(this.model, 'edit', true)) {
        this.hideActionItem('setHeld');
        this.hideActionItem('setNotHeld');
      }
    }
    actionSetHeld() {
      this.model.save({
        status: 'Held'
      }, {
        patch: true
      }).then(() => {
        Espo.Ui.success(this.translate('Saved'));
        this.removeActionItem('setHeld');
        this.removeActionItem('setNotHeld');
      });
    }
    actionSetNotHeld() {
      this.model.save({
        status: 'Not Held'
      }, {
        patch: true
      }).then(() => {
        Espo.Ui.success(this.translate('Saved'));
        this.removeActionItem('setHeld');
        this.removeActionItem('setNotHeld');
      });
    }
  }
  var _default = _exports.default = MeetingDetailRecordView;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/meeting/record/row-actions/default', ['views/record/row-actions/view-and-edit'], function (Dep) {

    return Dep.extend({

        getActionList: function () {
            var actionList = Dep.prototype.getActionList.call(this);

            actionList.forEach(item => {
                item.data = item.data || {};
                item.data.scope = this.model.entityType;
            });

            if (
                this.options.acl.edit &&
                !['Held', 'Not Held'].includes(this.model.get('status')) &&
                this.getAcl().checkField(this.model.entityType, 'status', 'edit')
            ) {
                actionList.push({
                    action: 'setHeld',
                    label: 'Set Held',
                    data: {
                        id: this.model.id,
                        scope: this.model.entityType,
                    },
                    groupIndex: 1,
                });

                actionList.push({
                    action: 'setNotHeld',
                    label: 'Set Not Held',
                    data: {
                        id: this.model.id,
                        scope: this.model.entityType,
                    },
                    groupIndex: 1,
                });
            }

            if (this.options.acl.delete) {
                actionList.push({
                    action: 'quickRemove',
                    label: 'Remove',
                    data: {
                        id: this.model.id,
                        scope: this.model.entityType,
                    },
                    groupIndex: 0,
                });
            }

            return actionList;
        }
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/meeting/record/row-actions/dashlet', ['views/record/row-actions/view-and-edit'], function (Dep) {

    return Dep.extend({

        getActionList: function () {
            var actionList = Dep.prototype.getActionList.call(this);

            actionList.forEach(item => {
                item.data = item.data || {};
                item.data.scope = this.model.entityType;
            });

            if (
                this.options.acl.edit &&
                !['Held', 'Not Held'].includes(this.model.get('status')) &&
                this.getAcl().checkField(this.model.entityType, 'status', 'edit')
            ) {
                actionList.push({
                    action: 'setHeld',
                    label: 'Set Held',
                    data: {
                        id: this.model.id,
                        scope: this.model.entityType,
                    },
                    groupIndex: 1,
                });

                actionList.push({
                    action: 'setNotHeld',
                    label: 'Set Not Held',
                    data: {
                        id: this.model.id,
                        scope: this.model.entityType,
                    },
                    groupIndex: 1,
                });
            }

            if (this.options.acl.delete) {
                actionList.push({
                    action: 'quickRemove',
                    label: 'Remove',
                    data: {
                        id: this.model.id,
                        scope: this.model.entityType,
                    },
                    groupIndex: 0,
                });
            }

            return actionList;
        }
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/meeting/record/panels/scheduler', ['views/record/panels/bottom'], function (Dep) {

    return Dep.extend({

        templateContent: '<div class="scheduler-container no-margin">{{{scheduler}}}</div>',

        setup: function () {
            Dep.prototype.setup.call(this);

            var viewName = this.getMetadata().get(['clientDefs', this.scope, 'schedulerView']) ||
                'crm:views/scheduler/scheduler';

            this.createView('scheduler', viewName, {
                selector: '.scheduler-container',
                notToRender: true,
                model: this.model,
            });

            this.once('after:render', () => {
                if (this.disabled) {
                    return;
                }

                this.getView('scheduler').render();
                this.getView('scheduler').notToRender = false;
            });

            if (this.defs.disabled) {
                this.once('show', () => {
                    this.getView('scheduler').render();
                    this.getView('scheduler').notToRender = false;
                });
            }
        },

        actionRefresh: function () {
            this.getView('scheduler').reRender();
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/meeting/record/panels/attendees', ['views/record/panels/side'], function (Dep) {

    return Dep.extend({

        setupFields: function () {
            this.fieldList = [];

            this.fieldList.push('users');

            if (this.getAcl().check('Contact') && !this.getMetadata().get('scopes.Contact.disabled')) {
                this.fieldList.push('contacts');
            }

            if (this.getAcl().check('Lead') && !this.getMetadata().get('scopes.Lead.disabled')) {
                this.fieldList.push('leads');
            }
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/meeting/modals/send-invitations', ['views/modal', 'collection'], function (Dep, Collection) {
    /**
     * @module crm_views/meeting/modals/send-invitations
     */

    /**
     * @class
     * @name Class
     * @extends module:views/modal
     * @memberOf module:crm_views/meeting/modals/send-invitations
     */
    return Dep.extend(/** @lends module:crm_views/meeting/modals/send-invitations.Class# */{

        backdrop: true,

        templateContent: `
            <div class="margin-bottom">
                <p>{{message}}</p>
            </div>
            <div class="list-container">{{{list}}}</div>
        `,

        data: function () {
            return {
                message: this.translate('sendInvitationsToSelectedAttendees', 'messages', 'Meeting'),
            };
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.shortcutKeys = {};
            this.shortcutKeys['Control+Enter'] = e => {
                if (!this.hasAvailableActionItem('send')) {
                    return;
                }

                e.preventDefault();

                this.actionSend();
            };

            this.$header = $('<span>').append(
                $('<span>')
                    .text(this.translate(this.model.entityType, 'scopeNames')),
                ' <span class="chevron-right"></span> ',
                $('<span>')
                    .text(this.model.get('name')),
                ' <span class="chevron-right"></span> ',
                $('<span>')
                    .text(this.translate('Send Invitations', 'labels', 'Meeting'))
            );

            this.addButton({
                label: 'Send',
                name: 'send',
                style: 'danger',
                disabled: true,
            });

            this.addButton({
                label: 'Cancel',
                name: 'cancel',
            });

            this.collection = new Collection();
            this.collection.url = this.model.entityType + `/${this.model.id}/attendees`;

            this.wait(
                this.collection.fetch()
                    .then(() => {
                        Espo.Utils.clone(this.collection.models).forEach(model => {
                            model.entityType = model.get('_scope');

                            if (!model.get('emailAddress')) {
                                this.collection.remove(model.id);
                            }
                        });

                        return this.createView('list', 'views/record/list', {
                            selector: '.list-container',
                            collection: this.collection,
                            rowActionsDisabled: true,
                            massActionsDisabled: true,
                            checkAllResultDisabled: true,
                            selectable: true,
                            buttonsDisabled: true,
                            listLayout: [
                                {
                                    name: 'name',
                                    customLabel: this.translate('name', 'fields'),
                                    notSortable: true,
                                },
                                {
                                    name: 'acceptanceStatus',
                                    width: 40,
                                    customLabel: this.translate('acceptanceStatus', 'fields', 'Meeting'),
                                    notSortable: true,
                                    view: 'views/fields/enum',
                                    params: {
                                        options: this.model.getFieldParam('acceptanceStatus', 'options'),
                                        style: this.model.getFieldParam('acceptanceStatus', 'style'),
                                    },
                                },
                            ],
                        })
                    })
                    .then(view => {
                        this.collection.models
                            .filter(model => {
                                let status = model.get('acceptanceStatus');

                                return !status || status === 'None';
                            })
                            .forEach(model => {
                                this.getListView().checkRecord(model.id);
                            });

                        this.listenTo(view, 'check', () => this.controlSendButton());

                        this.controlSendButton();
                    })
            );
        },

        controlSendButton: function () {
            this.getListView().checkedList.length ?
                this.enableButton('send') :
                this.disableButton('send');
        },

        /**
         * @return {module:views/record/list}
         */
        getListView: function () {
            return this.getView('list');
        },

        actionSend: function () {
            this.disableButton('send');

            Espo.Ui.notifyWait();

            let targets = this.getListView().checkedList.map(id => {
                return {
                    entityType: this.collection.get(id).entityType,
                    id: id,
                };
            });

            Espo.Ajax
                .postRequest(this.model.entityType + '/action/sendInvitations', {
                    id: this.model.id,
                    targets: targets,
                })
                .then(result => {
                    result ?
                        Espo.Ui.success(this.translate('Sent')) :
                        Espo.Ui.warning(this.translate('nothingHasBeenSent', 'messages', 'Meeting'));

                    this.trigger('sent');

                    this.close();
                })
                .catch(() => {
                    this.enableButton('send');
                });
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/meeting/modals/send-cancellation', ['views/modal', 'collection'], function (Dep, Collection) {
    /**
     * @module crm_views/meeting/modals/send-cancellation
     */

    /**
     * @class
     * @name Class
     * @extends module:views/modal
     * @memberOf module:crm_views/meeting/modals/send-cancellation
     */
    return Dep.extend(/** @lends module:crm_views/meeting/modals/send-cancellation.Class# */{

        backdrop: true,

        templateContent: `
            <div class="margin-bottom">
                <p>{{message}}</p>
            </div>
            <div class="list-container">{{{list}}}</div>
        `,

        data: function () {
            return {
                message: this.translate('sendCancellationsToSelectedAttendees', 'messages', 'Meeting'),
            };
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.shortcutKeys = {};
            this.shortcutKeys['Control+Enter'] = e => {
                if (!this.hasAvailableActionItem('send')) {
                    return;
                }

                e.preventDefault();

                this.actionSend();
            };

            this.$header = $('<span>').append(
                $('<span>')
                    .text(this.translate(this.model.entityType, 'scopeNames')),
                ' <span class="chevron-right"></span> ',
                $('<span>')
                    .text(this.model.get('name')),
                ' <span class="chevron-right"></span> ',
                $('<span>')
                    .text(this.translate('Send Cancellation', 'labels', 'Meeting'))
            );

            this.addButton({
                label: 'Send',
                name: 'send',
                style: 'danger',
                disabled: true,
            });

            this.addButton({
                label: 'Cancel',
                name: 'cancel',
            });

            this.collection = new Collection();
            this.collection.url = this.model.entityType + `/${this.model.id}/attendees`;

            this.wait(
                this.collection.fetch()
                    .then(() => {
                        Espo.Utils.clone(this.collection.models).forEach(model => {
                            model.entityType = model.get('_scope');

                            if (!model.get('emailAddress')) {
                                this.collection.remove(model.id);
                            }
                        });

                        return this.createView('list', 'views/record/list', {
                            selector: '.list-container',
                            collection: this.collection,
                            rowActionsDisabled: true,
                            massActionsDisabled: true,
                            checkAllResultDisabled: true,
                            selectable: true,
                            buttonsDisabled: true,
                            listLayout: [
                                {
                                    name: 'name',
                                    customLabel: this.translate('name', 'fields'),
                                    notSortable: true,
                                },
                                {
                                    name: 'acceptanceStatus',
                                    width: 40,
                                    customLabel: this.translate('acceptanceStatus', 'fields', 'Meeting'),
                                    notSortable: true,
                                    view: 'views/fields/enum',
                                    params: {
                                        options: this.model.getFieldParam('acceptanceStatus', 'options'),
                                        style: this.model.getFieldParam('acceptanceStatus', 'style'),
                                    },
                                },
                            ],
                        })
                    })
                    .then(view => {
                        this.collection.models
                            .filter(model => {
                                if (model.id === this.getUser().id && model.entityType === 'User') {
                                    return false;
                                }

                                return true;
                            })
                            .forEach(model => {
                                this.getListView().checkRecord(model.id);
                            });

                        this.listenTo(view, 'check', () => this.controlSendButton());

                        this.controlSendButton();
                    })
            );
        },

        controlSendButton: function () {
            this.getListView().checkedList.length ?
                this.enableButton('send') :
                this.disableButton('send');
        },

        /**
         * @return {module:views/record/list}
         */
        getListView: function () {
            return this.getView('list');
        },

        actionSend: function () {
            this.disableButton('send');

            Espo.Ui.notifyWait();

            let targets = this.getListView().checkedList.map(id => {
                return {
                    entityType: this.collection.get(id).entityType,
                    id: id,
                };
            });

            Espo.Ajax
                .postRequest(this.model.entityType + '/action/sendCancellation', {
                    id: this.model.id,
                    targets: targets,
                })
                .then(result => {
                    result ?
                        Espo.Ui.success(this.translate('Sent')) :
                        Espo.Ui.warning(this.translate('nothingHasBeenSent', 'messages', 'Meeting'));

                    this.trigger('sent');

                    this.close();
                })
                .catch(() => {
                    this.enableButton('send');
                });
        },
    });
});

define("modules/crm/views/meeting/modals/detail", ["exports", "moment", "views/modals/detail"], function (_exports, _moment, _detail) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _moment = _interopRequireDefault(_moment);
  _detail = _interopRequireDefault(_detail);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class MeetingModalDetailView extends _detail.default {
    duplicateAction = true;
    setup() {
      super.setup();
      this.setupStatuses();
    }
    setupStatuses() {
      if (this.notActualStatusList) {
        return;
      }
      this.notActualStatusList = [...(this.getMetadata().get(`scopes.${this.entityType}.completedStatusList`) || []), ...(this.getMetadata().get(`scopes.${this.entityType}.canceledStatusList`) || [])];
    }
    setupAfterModelCreated() {
      super.setupAfterModelCreated();
      const buttonData = this.getAcceptanceButtonData();
      this.addButton({
        name: 'setAcceptanceStatus',
        html: buttonData.html,
        hidden: this.hasAcceptanceStatusButton(),
        style: buttonData.style,
        className: 'btn-text',
        pullLeft: true,
        onClick: () => this.actionSetAcceptanceStatus()
      }, 'cancel');
      if (!this.getAcl().getScopeForbiddenFieldList(this.model.entityType).includes('status')) {
        this.addDropdownItem({
          name: 'setHeld',
          text: this.translate('Set Held', 'labels', this.model.entityType),
          hidden: true
        });
        this.addDropdownItem({
          name: 'setNotHeld',
          text: this.translate('Set Not Held', 'labels', this.model.entityType),
          hidden: true
        });
      }
      this.addDropdownItem({
        name: 'sendInvitations',
        text: this.translate('Send Invitations', 'labels', 'Meeting'),
        hidden: !this.isSendInvitationsToBeDisplayed(),
        onClick: () => this.actionSendInvitations()
      });
      this.initAcceptanceStatus();
      this.on('switch-model', (model, previousModel) => {
        this.stopListening(previousModel, 'sync');
        this.initAcceptanceStatus();
      });
      this.on('after:save', () => {
        if (this.hasAcceptanceStatusButton()) {
          this.showAcceptanceButton();
        } else {
          this.hideAcceptanceButton();
        }
        if (this.isSendInvitationsToBeDisplayed()) {
          this.showActionItem('sendInvitations');
        } else {
          this.hideActionItem('sendInvitations');
        }
      });
      this.listenTo(this.model, 'sync', () => {
        if (this.isSendInvitationsToBeDisplayed()) {
          this.showActionItem('sendInvitations');
          return;
        }
        this.hideActionItem('sendInvitations');
      });
      this.listenTo(this.model, 'after:save', () => {
        if (this.isSendInvitationsToBeDisplayed()) {
          this.showActionItem('sendInvitations');
          return;
        }
        this.hideActionItem('sendInvitations');
      });
    }
    controlRecordButtonsVisibility() {
      super.controlRecordButtonsVisibility();
      this.controlStatusActionVisibility();
    }
    controlStatusActionVisibility() {
      this.setupStatuses();
      if (this.getAcl().check(this.model, 'edit') && !this.notActualStatusList.includes(this.model.get('status'))) {
        this.showActionItem('setHeld');
        this.showActionItem('setNotHeld');
        return;
      }
      this.hideActionItem('setHeld');
      this.hideActionItem('setNotHeld');
    }
    initAcceptanceStatus() {
      if (this.hasAcceptanceStatusButton()) {
        this.showAcceptanceButton();
      } else {
        this.hideAcceptanceButton();
      }
      this.listenTo(this.model, 'sync', () => {
        if (this.hasAcceptanceStatusButton()) {
          this.showAcceptanceButton();
        } else {
          this.hideAcceptanceButton();
        }
      });
    }

    /**
     *
     * @return {{
     *     style: 'default'|'danger'|'success'|'warning'|'info',
     *     html: string,
     *     text: string,
     * }}
     */
    getAcceptanceButtonData() {
      const acceptanceStatus = this.model.getLinkMultipleColumn('users', 'status', this.getUser().id);
      let text;
      let style = 'default';
      let iconHtml = null;
      if (acceptanceStatus && acceptanceStatus !== 'None') {
        text = this.getLanguage().translateOption(acceptanceStatus, 'acceptanceStatus', this.model.entityType);
        style = this.getMetadata().get(['entityDefs', this.model.entityType, 'fields', 'acceptanceStatus', 'style', acceptanceStatus]);
        if (style) {
          const iconClass = {
            'success': 'fas fa-check-circle',
            'danger': 'fas fa-times-circle',
            'warning': 'fas fa-question-circle'
          }[style];
          iconHtml = $('<span>').addClass(iconClass).addClass('text-' + style).get(0).outerHTML;
        }
      } else {
        text = typeof acceptanceStatus !== 'undefined' ? this.translate('Acceptance', 'labels', 'Meeting') : ' ';
      }
      let html = this.getHelper().escapeString(text);
      if (iconHtml) {
        html = iconHtml + ' ' + html;
      }
      return {
        style: style,
        text: text,
        html: html
      };
    }
    showAcceptanceButton() {
      this.showActionItem('setAcceptanceStatus');
      if (!this.isRendered()) {
        this.once('after:render', this.showAcceptanceButton, this);
        return;
      }
      const data = this.getAcceptanceButtonData();
      const $button = this.$el.find('.modal-footer [data-name="setAcceptanceStatus"]');
      $button.html(data.html);
      $button.removeClass('btn-default');
      $button.removeClass('btn-success');
      $button.removeClass('btn-warning');
      $button.removeClass('btn-info');
      $button.removeClass('btn-primary');
      $button.removeClass('btn-danger');
      $button.addClass('btn-' + data.style);
    }
    hideAcceptanceButton() {
      this.hideActionItem('setAcceptanceStatus');
    }
    hasAcceptanceStatusButton() {
      if (!this.model.has('status')) {
        return false;
      }
      if (!this.model.has('usersIds')) {
        return false;
      }
      if (this.notActualStatusList.includes(this.model.get('status'))) {
        return false;
      }
      if (!~this.model.getLinkMultipleIdList('users').indexOf(this.getUser().id)) {
        return false;
      }
      return true;
    }
    actionSetAcceptanceStatus() {
      this.createView('dialog', 'crm:views/meeting/modals/acceptance-status', {
        model: this.model
      }, view => {
        view.render();
        this.listenTo(view, 'set-status', status => {
          this.hideAcceptanceButton();
          Espo.Ui.notifyWait();
          Espo.Ajax.postRequest(this.model.entityType + '/action/setAcceptanceStatus', {
            id: this.model.id,
            status: status
          }).then(() => {
            this.model.fetch().then(() => {
              Espo.Ui.notify(false);
              setTimeout(() => {
                this.$el.find(`button[data-name="setAcceptanceStatus"]`).focus();
              }, 50);
            });
          });
        });
      });
    }
    actionSetHeld() {
      this.model.save({
        status: 'Held'
      });
      this.trigger('after:save', this.model);
    }
    actionSetNotHeld() {
      this.model.save({
        status: 'Not Held'
      });
      this.trigger('after:save', this.model);
    }
    isSendInvitationsToBeDisplayed() {
      if (this.notActualStatusList.includes(this.model.get('status'))) {
        return false;
      }
      const dateEnd = this.model.get('dateEnd');
      if (dateEnd && this.getDateTime().toMoment(dateEnd).isBefore(_moment.default.now())) {
        return false;
      }
      if (!this.getAcl().checkModel(this.model, 'edit')) {
        return false;
      }
      const userIdList = this.model.getLinkMultipleIdList('users');
      const contactIdList = this.model.getLinkMultipleIdList('contacts');
      const leadIdList = this.model.getLinkMultipleIdList('leads');
      if (!contactIdList.length && !leadIdList.length && !userIdList.length) {
        return false;
      }
      return true;
    }
    actionSendInvitations() {
      Espo.Ui.notifyWait();
      this.createView('dialog', 'crm:views/meeting/modals/send-invitations', {
        model: this.model
      }).then(view => {
        Espo.Ui.notify(false);
        view.render();
        this.listenToOnce(view, 'sent', () => this.model.fetch());
      });
    }
  }
  var _default = _exports.default = MeetingModalDetailView;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/meeting/modals/acceptance-status', ['views/modal'], function (Dep) {

    return Dep.extend({

        backdrop: true,

        templateContent: `
            <div class="margin-bottom">
            <p>{{viewObject.message}}</p>
            </div>
            <div>
                {{#each viewObject.statusDataList}}
                <div class="margin-bottom">
                    <div>
                        <button
                            class="action btn btn-{{style}} btn-x-wide"
                            type="button"
                            data-action="setStatus"
                            data-status="{{name}}"
                        >
                        {{label}}
                        </button>
                        {{#if selected}}<span class="check-icon fas fa-check" style="vertical-align: middle; margin: 0 10px;"></span>{{/if}}
                    </div>
                </div>
                {{/each}}
            </div>
        `,

        setup: function () {
            Dep.prototype.setup.call(this);

            this.$header = $('<span>').append(
                $('<span>').text(this.translate(this.model.entityType, 'scopeNames')),
                ' <span class="chevron-right"></span> ',
                $('<span>').text(this.model.get('name')),
                ' <span class="chevron-right"></span> ',
                $('<span>').text(this.translate('Acceptance', 'labels', 'Meeting'))
            );

            let statusList = this.getMetadata()
                .get(['entityDefs', this.model.entityType, 'fields', 'acceptanceStatus', 'options']) || [];

            this.statusDataList = [];

            statusList.filter(item => item !== 'None').forEach(item => {
                let o = {
                    name: item,
                    style: this.getMetadata()
                        .get(['entityDefs', this.model.entityType, 'fields', 'acceptanceStatus', 'style', item]) ||
                        'default',
                    label: this.getLanguage().translateOption(item, 'acceptanceStatus', this.model.entityType),
                    selected: this.model.getLinkMultipleColumn('users', 'status', this.getUser().id) === item,
                };

                this.statusDataList.push(o);
            });

            this.message = this.translate('selectAcceptanceStatus', 'messages', 'Meeting')
        },

        actionSetStatus: function (data) {
            this.trigger('set-status', data.status);
            this.close();
        },
    });
});

define("modules/crm/views/meeting/fields/users", ["exports", "modules/crm/views/meeting/fields/attendees"], function (_exports, _attendees) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _attendees = _interopRequireDefault(_attendees);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _attendees.default {
    selectPrimaryFilterName = 'active';
    init() {
      this.assignmentPermission = this.getAcl().getPermissionLevel('assignmentPermission');
      if (this.assignmentPermission === 'no') {
        this.readOnly = true;
      }
      super.init();
    }
    getSelectBoolFilterList() {
      if (this.assignmentPermission === 'team') {
        return ['onlyMyTeam'];
      }
    }

    /**
     * @private
     * @param {string} id
     * @return {string}
     */
    getIconHtml(id) {
      return this.getHelper().getAvatarHtml(id, 'small', 18, 'avatar-link');
    }

    /**
     * @inheritDoc
     */
    prepareEditItemElement(id, name) {
      const itemElement = super.prepareEditItemElement(id, name);
      const avatarHtml = this.getHelper().getAvatarHtml(id, 'small', 18, 'avatar-link');
      if (avatarHtml) {
        const img = new DOMParser().parseFromString(avatarHtml, 'text/html').body.childNodes[0];
        const nameElement = itemElement.children[0].querySelector('.link-item-name');
        if (nameElement) {
          nameElement.prepend(img);
        }
      }
      return itemElement;
    }
  }
  _exports.default = _default;
});

define("modules/crm/views/meeting/fields/reminders", ["exports", "ui/select", "moment", "views/fields/base"], function (_exports, _select, _moment, _base) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _select = _interopRequireDefault(_select);
  _moment = _interopRequireDefault(_moment);
  _base = _interopRequireDefault(_base);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class MeetingRemindersField extends _base.default {
    detailTemplate = 'crm:meeting/fields/reminders/detail';
    listTemplate = 'crm:meeting/fields/reminders/detail';
    editTemplate = 'crm:meeting/fields/reminders/edit';

    /**
     * @private
     * @type {string}
     */
    dateField = 'dateStart';

    /**
     * @private
     * @type {boolean}
     */
    isDateTimeOptional;

    /**
     * @private
     * @type {number}
     */
    minAllDaySeconds = 120 * 60;
    getAttributeList() {
      return [this.name];
    }
    setup() {
      this.addActionHandler('addReminder', () => this.actionAddReminder());
      this.addActionHandler('removeReminder', (e, target) => {
        const element = target.closest('.reminder');
        const index = Array.from(element.parentElement.childNodes).indexOf(element);
        this.removeReminder(index);
      });
      this.setupReminderList();
      this.listenTo(this.model, 'change:' + this.name, () => {
        this.reminderList = Espo.Utils.cloneDeep(this.model.get(this.name) || []);
      });
      this.typeList = Espo.Utils.clone(this.getMetadata().get('entityDefs.Reminder.fields.type.options') || []);
      this.secondsList = Espo.Utils.clone(this.getMetadata().get('entityDefs.Reminder.fields.seconds.options') || []);
      this.dateField = this.model.getFieldParam(this.name, 'dateField') || this.dateField;
      this.listenTo(this.model, 'change:' + this.dateField, () => {
        if (this.isEditMode()) {
          this.reRender();
        }
      });
      this.isDateTimeOptional = this.model.getFieldParam(this.dateField, 'type') === 'datetimeOptional';
    }

    /**
     * @private
     */
    setupReminderList() {
      if (this.model.isNew() && !this.model.get(this.name) && this.model.entityType !== 'Preferences') {
        let param = 'defaultReminders';
        if (this.model.entityType === 'Task') {
          param = 'defaultRemindersTask';
        }
        this.reminderList = this.getPreferences().get(param) || [];
      } else {
        this.reminderList = this.model.get(this.name) || [];
      }
      this.reminderList = Espo.Utils.cloneDeep(this.reminderList);
    }
    afterRender() {
      if (this.isEditMode()) {
        this.$container = this.$el.find('.reminders-container');
        this.reminderList.forEach(item => {
          this.addItemHtml(item);
        });
      }
    }
    focusOnButton() {
      // noinspection JSUnresolvedReference
      this.$el.find('button[data-action="addReminder"]').get(0).focus({
        preventScroll: true
      });
    }

    /**
     * @private
     * @param {string} type
     * @param {number} index
     */
    updateType(type, index) {
      this.reminderList[index].type = type;
      this.trigger('change');
    }

    /**
     * @private
     * @param {number} seconds
     * @param {number} index
     */
    updateSeconds(seconds, index) {
      this.reminderList[index].seconds = seconds;
      this.trigger('change');
    }

    /**
     * @private
     * @param {{type: string, seconds: number}} item
     */
    addItemHtml(item) {
      const $item = $('<div>').addClass('input-group').addClass('reminder');
      const $type = $('<select>').attr('name', 'type').attr('data-name', 'type').addClass('form-control');
      this.typeList.forEach(type => {
        const $o = $('<option>').attr('value', type).text(this.getLanguage().translateOption(type, 'reminderTypes'));
        $type.append($o);
      });
      $type.val(item.type).addClass('radius-left');
      $type.on('change', () => {
        this.updateType($type.val(), $type.closest('.reminder').index());
      });
      const $seconds = $('<select>').attr('name', 'seconds').attr('data-name', 'seconds').addClass('form-control radius-right');
      const limitDate = this.model.get(this.dateField) ? this.getDateTime().toMoment(this.model.get(this.dateField)) : null;

      /** @var {number[]} secondsList */
      let secondsList = Espo.Utils.clone(this.secondsList);
      if (this.isDateTimeOptional && this.model.attributes[this.dateField + 'Date']) {
        secondsList = secondsList.filter(seconds => !seconds || seconds >= this.minAllDaySeconds);
      }
      if (!secondsList.includes(item.seconds)) {
        secondsList.push(item.seconds);
      }
      secondsList.filter(seconds => {
        return seconds === item.seconds || !limitDate || this.isBefore(seconds, limitDate);
      }).sort((a, b) => a - b).forEach(seconds => {
        const $o = $('<option>').attr('value', seconds).text(this.stringifySeconds(seconds));
        $seconds.append($o);
      });
      $seconds.val(item.seconds);
      $seconds.on('change', () => {
        const seconds = parseInt($seconds.val());
        const index = $seconds.closest('.reminder').index();
        this.updateSeconds(seconds, index);
      });
      const $remove = $('<button>').addClass('btn').addClass('btn-link').css('margin-left', '5px').attr('type', 'button').attr('data-action', 'removeReminder').html('<span class="fas fa-times"></span>');
      $item.append($('<div class="input-group-item">').append($type)).append($('<div class="input-group-item">').append($seconds)).append($('<div class="input-group-btn">').append($remove));
      this.$container.append($item);
      _select.default.init($type, {});
      _select.default.init($seconds, {
        sortBy: '$score',
        sortDirection: 'desc',
        /**
         * @param {string} search
         * @param {{value: string}} item
         * @return {number}
         */
        score: (search, item) => {
          const num = parseInt(item.value);
          const searchNum = parseInt(search);
          if (isNaN(searchNum)) {
            return 0;
          }
          const numOpposite = Number.MAX_SAFE_INTEGER - num;
          if (searchNum === 0 && num === 0) {
            return numOpposite;
          }
          if (searchNum * 60 === num) {
            return numOpposite;
          }
          if (searchNum * 60 * 60 === num) {
            return numOpposite;
          }
          if (searchNum * 60 * 60 * 24 === num) {
            return numOpposite;
          }
          return 0;
        },
        load: (item, callback) => {
          const num = parseInt(item);
          if (isNaN(num) || num < 0) {
            return;
          }
          if (num > 59) {
            return;
          }
          const list = [];
          const mSeconds = num * 60;
          if (!this.isBefore(mSeconds, limitDate)) {
            return;
          }
          list.push({
            value: mSeconds.toString(),
            text: this.stringifySeconds(mSeconds)
          });
          if (num <= 24) {
            const hSeconds = num * 3600;
            if (this.isBefore(hSeconds, limitDate)) {
              list.push({
                value: hSeconds.toString(),
                text: this.stringifySeconds(hSeconds)
              });
            }
          }
          if (num <= 30) {
            const dSeconds = num * 3600 * 24;
            if (this.isBefore(dSeconds, limitDate)) {
              list.push({
                value: dSeconds.toString(),
                text: this.stringifySeconds(dSeconds)
              });
            }
          }
          callback(list);
        }
      });
    }

    /**
     * @private
     * @param {number} seconds
     * @param {moment.Moment} limitDate
     * @return {boolean}
     */
    isBefore(seconds, limitDate) {
      return _moment.default.utc().add(seconds, 'seconds').isBefore(limitDate);
    }

    /**
     * @private
     * @param {number} totalSeconds
     * @return {string}
     */
    stringifySeconds(totalSeconds) {
      if (!totalSeconds) {
        return this.translate('on time', 'labels', 'Meeting');
      }
      let d = totalSeconds;
      const days = Math.floor(d / 86400);
      d = d % 86400;
      const hours = Math.floor(d / 3600);
      d = d % 3600;
      const minutes = Math.floor(d / 60);
      const seconds = d % 60;
      const parts = [];
      if (days) {
        parts.push(days + '' + this.getLanguage().translate('d', 'durationUnits'));
      }
      if (hours) {
        parts.push(hours + '' + this.getLanguage().translate('h', 'durationUnits'));
      }
      if (minutes) {
        parts.push(minutes + '' + this.getLanguage().translate('m', 'durationUnits'));
      }
      if (seconds) {
        parts.push(seconds + '' + this.getLanguage().translate('s', 'durationUnits'));
      }
      return parts.join(' ') + ' ' + this.translate('before', 'labels', 'Meeting');
    }

    /**
     * @private
     * @param {{type: string, seconds: number}} item
     * @return {string}
     */
    getDetailItemHtml(item) {
      return $('<div>').append($('<span>').text(this.getLanguage().translateOption(item.type, 'reminderTypes')), ' ', $('<span>').text(this.stringifySeconds(item.seconds))).get(0).outerHTML;
    }
    getValueForDisplay() {
      if (this.isDetailMode() || this.isListMode()) {
        let html = '';
        this.reminderList.forEach(item => {
          html += this.getDetailItemHtml(item);
        });
        return html;
      }
    }
    fetch() {
      const data = {};
      data[this.name] = Espo.Utils.cloneDeep(this.reminderList);
      return data;
    }

    /**
     * @private
     */
    actionAddReminder() {
      const type = this.getMetadata().get('entityDefs.Reminder.fields.type.default');
      const seconds = this.getMetadata().get('entityDefs.Reminder.fields.seconds.default') || 0;
      const item = {
        type: type,
        seconds: seconds
      };
      this.reminderList.push(item);
      this.addItemHtml(item);
      this.trigger('change');
      this.focusOnButton();
    }

    /**
     * @private
     * @param {number} index
     */
    removeReminder(index) {
      const element = this.element.querySelectorAll('.reminder')[index] ?? null;
      if (!element) {
        return;
      }
      element.parentElement.removeChild(element);
      this.reminderList.splice(index, 1);
      this.focusOnButton();
    }
  }
  var _default = _exports.default = MeetingRemindersField;
});

define("modules/crm/views/meeting/fields/date-start", ["exports", "views/fields/datetime-optional", "moment"], function (_exports, _datetimeOptional, _moment) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _datetimeOptional = _interopRequireDefault(_datetimeOptional);
  _moment = _interopRequireDefault(_moment);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class DateStartMeetingFieldView extends _datetimeOptional.default {
    emptyTimeInInlineEditDisabled = true;
    setup() {
      super.setup();
      this.noneOption = this.translate('All-Day', 'labels', 'Meeting');
      this.notActualStatusList = [...(this.getMetadata().get(`scopes.${this.entityType}.completedStatusList`) || []), ...(this.getMetadata().get(`scopes.${this.entityType}.canceledStatusList`) || [])];
    }
    getAttributeList() {
      return [...super.getAttributeList(), 'dateEnd', 'dateEndDate', 'status'];
    }
    data() {
      let style;
      const status = this.model.get('status');
      if (status && !this.notActualStatusList.includes(status) && (this.mode === this.MODE_DETAIL || this.mode === this.MODE_LIST)) {
        if (this.isDateInPast('dateEnd')) {
          style = 'danger';
        } else if (this.isDateInPast('dateStart', true)) {
          style = 'warning';
        }
      }

      // noinspection JSValidateTypes
      return {
        ...super.data(),
        style: style
      };
    }

    /**
     * @private
     * @param {string} field
     * @param {boolean} [isFrom]
     * @return {boolean}
     */
    isDateInPast(field, isFrom) {
      if (this.isDate()) {
        const value = this.model.get(field + 'Date');
        if (value) {
          const timeValue = isFrom ? value + ' 00:00' : value + ' 23:59';
          const d = _moment.default.tz(timeValue, this.getDateTime().getTimeZone());
          const now = this.getDateTime().getNowMoment();
          if (d.unix() < now.unix()) {
            return true;
          }
        }
        return false;
      }
      const value = this.model.get(field);
      if (value) {
        const d = this.getDateTime().toMoment(value);
        const now = (0, _moment.default)().tz(this.getDateTime().timeZone || 'UTC');
        if (d.unix() < now.unix()) {
          return true;
        }
      }
      return false;
    }
    afterRender() {
      super.afterRender();
      if (this.isEditMode()) {
        this.controlTimePartVisibility();
      }
    }
    fetch() {
      const data = super.fetch();
      if (data[this.nameDate]) {
        data.isAllDay = true;
      } else {
        data.isAllDay = false;
      }
      return data;
    }
    controlTimePartVisibility() {
      if (!this.isEditMode()) {
        return;
      }
      if (!this.isInlineEditMode()) {
        return;
      }
      if (this.model.get('isAllDay')) {
        this.$time.addClass('hidden');
        this.$el.find('.time-picker-btn').addClass('hidden');
      } else {
        this.$time.removeClass('hidden');
        this.$el.find('.time-picker-btn').removeClass('hidden');
      }
    }
  }
  var _default = _exports.default = DateStartMeetingFieldView;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/meeting/fields/date-end', ['views/fields/datetime-optional'], function (Dep) {

    return Dep.extend({

        validateAfterAllowSameDay: true,
        emptyTimeInInlineEditDisabled: true,
        noneOptionIsHidden: true,
        isEnd: true,

        setup: function () {
            Dep.prototype.setup.call(this);

            this.isAllDayValue = this.model.get('isAllDay');

            this.listenTo(this.model, 'change:isAllDay', (model, value, o) => {
                if (!o.ui) {
                    return;
                }

                if (!this.isEditMode()) {
                    return;
                }

                if (this.isAllDayValue === undefined && !value) {
                    this.isAllDayValue = value;

                    return;
                }

                this.isAllDayValue = value;

                if (value) {
                    this.$time.val(this.noneOption);
                } else {
                    let dateTime = this.model.get('dateStart');

                    if (!dateTime) {
                        dateTime = this.getDateTime().getNow(5);
                    }

                    let m = this.getDateTime().toMoment(dateTime);
                    dateTime = m.format(this.getDateTime().getDateTimeFormat());

                    let index = dateTime.indexOf(' ');
                    let time = dateTime.substring(index + 1);

                    if (this.model.get('dateEnd')) {
                        this.$time.val(time);
                    }
                }

                this.trigger('change');
                this.controlTimePartVisibility();
            });
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.isEditMode()) {
                this.controlTimePartVisibility();
            }
        },

        controlTimePartVisibility: function () {
            if (!this.isEditMode()) {
                return;
            }

            if (this.model.get('isAllDay')) {
                this.$time.addClass('hidden');
                this.$el.find('.time-picker-btn').addClass('hidden');

                return;
            }

            this.$time.removeClass('hidden');
            this.$el.find('.time-picker-btn').removeClass('hidden');
        },
    });
});

define("modules/crm/views/meeting/fields/contacts", ["exports", "modules/crm/views/meeting/fields/attendees"], function (_exports, _attendees) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _attendees = _interopRequireDefault(_attendees);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _attendees.default {}
  _exports.default = _default;
});

define("modules/crm/views/meeting/fields/acceptance-status", ["exports", "views/fields/enum"], function (_exports, _enum) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _enum = _interopRequireDefault(_enum);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _enum.default {
    searchTypeList = ['anyOf', 'noneOf'];
    fetchSearch() {
      let data = super.fetchSearch();
      if (data && data.data.type === 'noneOf' && data.value && data.value.length > 1) {
        data.value = [data.value[0]];
      }
      return data;
    }
  }
  _exports.default = _default;
});

define("modules/crm/views/mass-email/detail", ["exports", "views/detail", "crm:views/mass-email/modals/send-test"], function (_exports, _detail, _sendTest) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _detail = _interopRequireDefault(_detail);
  _sendTest = _interopRequireDefault(_sendTest);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _detail.default {
    setup() {
      super.setup();
      if (['Draft', 'Pending'].includes(this.model.attributes.status) && this.getAcl().checkModel(this.model, 'edit')) {
        this.addMenuItem('buttons', {
          label: 'Send Test',
          action: 'sendTest',
          acl: 'edit',
          onClick: () => this.actionSendTest()
        });
      }
    }
    async actionSendTest() {
      const view = new _sendTest.default({
        model: this.model
      });
      await this.assignView('modal', view);
      await view.render();
    }
  }
  _exports.default = _default;
});

define("modules/crm/views/mass-email/record/list-for-campaign", ["exports", "views/record/list", "crm:views/mass-email/modals/send-test"], function (_exports, _list, _sendTest) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _list = _interopRequireDefault(_list);
  _sendTest = _interopRequireDefault(_sendTest);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _list.default {
    // noinspection JSUnusedGlobalSymbols
    async actionSendTest(data) {
      const id = data.id;
      const model = this.collection.get(id);
      if (!model) {
        return;
      }
      const view = new _sendTest.default({
        model: model
      });
      await this.assignView('modal', view);
      await view.render();
    }
  }
  _exports.default = _default;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/mass-email/record/edit-small',
['views/record/edit-small', 'crm:views/mass-email/record/edit'], function (Dep, Edit) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);
            Edit.prototype.initFieldsControl.call(this);
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/mass-email/record/detail', ['views/record/detail'], function (Dep) {

    return Dep.extend({

        duplicateAction: true,

        bottomView: 'crm:views/mass-email/record/detail-bottom',
    });
});


/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/mass-email/record/detail-bottom', ['views/record/detail-bottom'], function (Dep) {

    return Dep.extend({

        setupPanels: function () {
            Dep.prototype.setupPanels.call(this);

            this.panelList.unshift({
                name: 'queueItems',
                label: this.translate('queueItems', 'links', 'MassEmail'),
                view: 'views/record/panels/relationship',
                select: false,
                create: false,
                layout: 'listForMassEmail',
                rowActionsView: 'views/record/row-actions/empty',
                filterList: ['all', 'pending', 'sent', 'failed'],
            });
        },

        afterRender: function () {
            Dep.prototype.setupPanels.call(this);
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/mass-email/record/row-actions/for-campaign',
['views/record/row-actions/relationship-no-unlink'], function (Dep) {

    return Dep.extend({

        getActionList: function () {
            var actionList = Dep.prototype.getActionList.call(this);

            if (this.options.acl.edit && !~['Complete'].indexOf(this.model.get('status'))) {
                actionList.unshift({
                    action: 'sendTest',
                    label: 'Send Test',
                    data: {
                        id: this.model.id,
                    }
                });
            }

            return actionList;
        }
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/mass-email/fields/smtp-account', ['views/lead-capture/fields/smtp-account'], function (Dep) {

    return Dep.extend({

        dataUrl: 'MassEmail/action/smtpAccountDataList',
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/mass-email/fields/from-address', ['views/fields/varchar'], function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.model.isNew() && !this.model.has('fromAddress')) {
                this.model.set('fromAddress', this.getConfig().get('outboundEmailFromAddress'));
            }

            if (this.model.isNew() && !this.model.has('fromName')) {
                this.model.set('fromName', this.getConfig().get('outboundEmailFromName'));
            }
        },
    });
});

define("modules/crm/views/mass-email/fields/email-template", ["exports", "views/fields/link"], function (_exports, _link) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _link = _interopRequireDefault(_link);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _link.default {
    getCreateAttributes() {
      return {
        oneOff: true
      };
    }
  }
  _exports.default = _default;
});

define("modules/crm/views/lead/detail", ["exports", "views/detail"], function (_exports, _detail) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _detail = _interopRequireDefault(_detail);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class LeadDetailView extends _detail.default {
    setup() {
      super.setup();
      this.addMenuItem('buttons', {
        name: 'convert',
        action: 'convert',
        label: 'Convert',
        acl: 'edit',
        hidden: !this.isConvertable(),
        onClick: () => this.actionConvert()
      });
      this.listenTo(this.model, 'sync', () => {
        this.isConvertable() ? this.showHeaderActionItem('convert') : this.hideHeaderActionItem('convert');
      });
    }
    isConvertable() {
      const notActualList = [...(this.getMetadata().get(`entityDefs.Lead.fields.status.notActualOptions`) || []), 'Converted'];
      return !notActualList.includes(this.model.get('status')) && this.model.has('status');
    }
    actionConvert() {
      this.getRouter().navigate(`${this.model.entityType}/convert/${this.model.id}`, {
        trigger: true
      });
    }
  }
  var _default = _exports.default = LeadDetailView;
});

define("modules/crm/views/lead/convert", ["exports", "views/main"], function (_exports, _main) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _main = _interopRequireDefault(_main);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class ConvertLeadView extends _main.default {
    template = 'crm:lead/convert';
    data() {
      return {
        scopeList: this.scopeList,
        scope: this.scope
      };
    }
    setup() {
      this.scope = 'Lead';
      this.addHandler('change', 'input.check-scope', (e, /** HTMLInputElement */target) => {
        const scope = target.dataset.scope;
        const $div = this.$el.find(`.edit-container-${Espo.Utils.toDom(scope)}`);
        if (target.checked) {
          $div.removeClass('hide');
        } else {
          $div.addClass('hide');
        }
      });
      this.addActionHandler('convert', () => this.convert());
      this.addActionHandler('cancel', () => {
        this.getRouter().navigate(`#Lead/view/${this.id}`, {
          trigger: true
        });
      });
      this.createView('header', 'views/header', {
        model: this.model,
        fullSelector: '#main > .header',
        scope: this.scope,
        fontSizeFlexible: true
      });
      this.wait(true);
      this.id = this.options.id;
      Espo.Ui.notifyWait();
      this.getModelFactory().create('Lead', model => {
        this.model = model;
        model.id = this.id;
        this.listenToOnce(model, 'sync', () => this.build());
        model.fetch();
      });
    }
    build() {
      const scopeList = this.scopeList = [];
      (this.getMetadata().get('entityDefs.Lead.convertEntityList') || []).forEach(scope => {
        if (scope === 'Account' && this.getConfig().get('b2cMode')) {
          return;
        }
        if (this.getMetadata().get(['scopes', scope, 'disabled'])) {
          return;
        }
        if (this.getAcl().check(scope, 'create')) {
          scopeList.push(scope);
        }
      });
      let i = 0;
      const ignoreAttributeList = ['createdAt', 'modifiedAt', 'modifiedById', 'modifiedByName', 'createdById', 'createdByName'];
      if (scopeList.length === 0) {
        this.wait(false);
        return;
      }
      Espo.Ajax.postRequest('Lead/action/getConvertAttributes', {
        id: this.model.id
      }).then(data => {
        scopeList.forEach(scope => {
          this.getModelFactory().create(scope, model => {
            model.populateDefaults();
            model.set(data[scope] || {}, {
              silent: true
            });
            const convertEntityViewName = this.getMetadata().get(['clientDefs', scope, 'recordViews', 'edit']) || 'views/record/edit';
            this.createView(scope, convertEntityViewName, {
              model: model,
              fullSelector: '#main .edit-container-' + Espo.Utils.toDom(scope),
              buttonsPosition: false,
              buttonsDisabled: true,
              layoutName: 'detailConvert',
              exit: () => {}
            }, () => {
              i++;
              if (i === scopeList.length) {
                this.wait(false);
                Espo.Ui.notify(false);
              }
            });
          });
        });
      });
    }
    convert() {
      const scopeList = [];
      this.scopeList.forEach(scope => {
        /** @type {HTMLInputElement} */
        const el = this.$el.find(`input[data-scope="${scope}"]`).get(0);
        if (el && el.checked) {
          scopeList.push(scope);
        }
      });
      if (scopeList.length === 0) {
        Espo.Ui.error(this.translate('selectAtLeastOneRecord', 'messages'));
        return;
      }
      this.getRouter().confirmLeaveOut = false;
      let notValid = false;
      scopeList.forEach(scope => {
        const editView = /** @type {import('views/record/edit').default} */this.getView(scope);
        editView.setConfirmLeaveOut(false);
        editView.model.set(editView.fetch());
        notValid = editView.validate() || notValid;
      });
      const data = {
        id: this.model.id,
        records: {}
      };
      scopeList.forEach(scope => {
        data.records[scope] = this.getView(scope).model.attributes;
      });
      const process = data => {
        this.$el.find('[data-action="convert"]').addClass('disabled');
        Espo.Ui.notifyWait();
        Espo.Ajax.postRequest('Lead/action/convert', data).then(() => {
          this.getRouter().confirmLeaveOut = false;
          this.getRouter().navigate('#Lead/view/' + this.model.id, {
            trigger: true
          });
          Espo.Ui.notify(this.translate('Converted', 'labels', 'Lead'));
        }).catch(xhr => {
          Espo.Ui.notify(false);
          this.$el.find('[data-action="convert"]').removeClass('disabled');
          if (xhr.status !== 409) {
            return;
          }
          if (xhr.getResponseHeader('X-Status-Reason') !== 'duplicate') {
            return;
          }
          let response = null;
          try {
            response = JSON.parse(xhr.responseText);
          } catch (e) {
            console.error('Could not parse response header.');
            return;
          }
          xhr.errorIsHandled = true;
          this.createView('duplicate', 'views/modals/duplicate', {
            duplicates: response
          }, view => {
            view.render();
            this.listenToOnce(view, 'save', () => {
              data.skipDuplicateCheck = true;
              process(data);
            });
          });
        });
      };
      if (notValid) {
        Espo.Ui.error(this.translate('Not valid'));
        return;
      }
      process(data);
    }
    getHeader() {
      const headerIconHtml = this.getHeaderIconHtml();
      const scopeLabel = this.getLanguage().translate(this.model.entityType, 'scopeNamesPlural');
      const $root = $('<span>').append($('<a>').attr('href', '#Lead').text(scopeLabel));
      if (headerIconHtml) {
        $root.prepend(headerIconHtml);
      }
      const name = this.model.get('name') || this.model.id;
      const url = `#${this.model.entityType}/view/${this.model.id}`;
      const $name = $('<a>').attr('href', url).addClass('action').append($('<span>').text(name));
      return this.buildHeaderHtml([$root, $name, $('<span>').text(this.translate('convert', 'labels', 'Lead'))]);
    }
  }
  var _default = _exports.default = ConvertLeadView;
});

define("modules/crm/views/lead/record/detail", ["exports", "views/record/detail"], function (_exports, _detail) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _detail = _interopRequireDefault(_detail);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _detail.default {
    selfAssignAction = true;
    sideView = 'crm:views/lead/record/detail-side';
    getSelfAssignAttributes() {
      if (this.model.attributes.status === 'New') {
        const options = this.getMetadata().get(['entityDefs', 'Lead', 'fields', 'status', 'options']) || [];
        if (options.includes('Assigned')) {
          return {
            'status': 'Assigned'
          };
        }
      }
      return {};
    }
  }
  _exports.default = _default;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/lead/record/detail-side', ['views/record/detail-side'], function (Dep) {

    return Dep.extend({

        setupPanels: function () {},
    });
});


/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/lead/record/panels/converted-to', ['views/record/panels/side'], function (Dep) {

    return Dep.extend({

        setupFields: function () {
            this.fieldList = [];

            if (this.getAcl().check('Account') && !this.getMetadata().get('scopes.Account.disabled')) {
                this.fieldList.push('createdAccount');
            }

            if (this.getAcl().check('Contact') && !this.getMetadata().get('scopes.Contact.disabled')) {
                this.fieldList.push('createdContact');
            }

            if (this.getAcl().check('Opportunity') && !this.getMetadata().get('scopes.Opportunity.disabled')) {
                this.fieldList.push('createdOpportunity');
            }
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/lead/fields/industry', ['views/fields/enum'], function (Dep) {

    return Dep.extend({});
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/lead/fields/created-opportunity', ['views/fields/link'], function (Dep) {

    return Dep.extend({

        getSelectFilters: function () {
            if (this.model.get('createdAccountId')) {
                return {
                    'account': {
                        type: 'equals',
                        attribute: 'accountId',
                        value: this.model.get('createdAccountId'),
                        data: {
                            type: 'is',
                            nameValue: this.model.get('createdAccountName'),
                        },
                    }
                };
            }
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/lead/fields/created-contact', ['views/fields/link'], function (Dep) {

    return Dep.extend({

        getSelectFilters: function () {
            if (this.model.get('createdAccountId')) {
                return {
                    'account': {
                        type: 'equals',
                        attribute: 'accountId',
                        value: this.model.get('createdAccountId'),
                        data: {
                            type: 'is',
                            nameValue: this.model.get('createdAccountName')
                        }
                    }
                };
            }
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/lead/fields/acceptance-status', ['views/fields/enum-column'], function (Dep) {

    return Dep.extend({

        searchTypeList: ['anyOf', 'noneOf'],

        setup: function () {
            this.params.options = this.getMetadata().get('entityDefs.Meeting.fields.acceptanceStatus.options');
            this.params.translation = 'Meeting.options.acceptanceStatus';

            Dep.prototype.setup.call(this);
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/knowledge-base-article/list', ['views/list-with-categories'], function (Dep) {

    return Dep.extend({

        categoryScope: 'KnowledgeBaseCategory',
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/knowledge-base-article/record/list', ['views/record/list'], function (Dep) {

    return Dep.extend({});
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/knowledge-base-article/record/edit', ['views/record/edit'], function (Dep) {

    return Dep.extend({

        saveAndContinueEditingAction: true,

    });
});

define("modules/crm/views/knowledge-base-article/record/edit-quick", ["exports", "views/record/edit"], function (_exports, _edit) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _edit = _interopRequireDefault(_edit);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _edit.default {}
  _exports.default = _default;
});

define("modules/crm/views/knowledge-base-article/record/detail", ["exports", "modules/crm/knowledge-base-helper", "views/record/detail"], function (_exports, _knowledgeBaseHelper, _detail) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _knowledgeBaseHelper = _interopRequireDefault(_knowledgeBaseHelper);
  _detail = _interopRequireDefault(_detail);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class KnowledgeBaseRecordDetailView extends _detail.default {
    saveAndContinueEditingAction = true;
    setup() {
      super.setup();
      if (this.getUser().isPortal()) {
        this.sideDisabled = true;
      }
      if (this.getAcl().checkScope('Email', 'create')) {
        this.dropdownItemList.push({
          'label': 'Send in Email',
          'name': 'sendInEmail'
        });
      }
      if (this.getUser().isPortal() && !this.getAcl().checkScope(this.scope, 'edit') && !this.model.getLinkMultipleIdList('attachments').length) {
        this.hideField('attachments');
        this.listenToOnce(this.model, 'sync', () => {
          if (this.model.getLinkMultipleIdList('attachments').length) {
            this.showField('attachments');
          }
        });
      }
    }

    // noinspection JSUnusedGlobalSymbols
    actionSendInEmail() {
      Espo.Ui.notifyWait();
      const helper = new _knowledgeBaseHelper.default(this.getLanguage());
      helper.getAttributesForEmail(this.model, {}, attributes => {
        const viewName = this.getMetadata().get('clientDefs.Email.modalViews.compose') || 'views/modals/compose-email';
        this.createView('composeEmail', viewName, {
          attributes: attributes,
          selectTemplateDisabled: true,
          signatureDisabled: true
        }, view => {
          Espo.Ui.notify(false);
          view.render();
        });
      });
    }
  }
  var _default = _exports.default = KnowledgeBaseRecordDetailView;
});

define("modules/crm/views/knowledge-base-article/record/detail-quick", ["exports", "views/record/detail"], function (_exports, _detail) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _detail = _interopRequireDefault(_detail);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _detail.default {}
  _exports.default = _default;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/knowledge-base-article/modals/select-records',
['views/modals/select-records-with-categories'], function (Dep) {

    return Dep.extend({

        categoryScope: 'KnowledgeBaseCategory',
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/knowledge-base-article/fields/status', ['views/fields/enum'], function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            var publishDateWasSet = false;

            this.on('change', () => {
                if (this.model.get('status') === 'Published') {
                    if (!this.model.get('publishDate')) {
                        publishDateWasSet = true;

                        this.model.set('publishDate', this.getDateTime().getToday());
                    }
                } else {
                    if (publishDateWasSet) {
                        this.model.set('publishDate', null);
                    }
                }
            });
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/knowledge-base-article/fields/language', ['views/fields/enum'], function (Dep) {

    return Dep.extend({

        setupOptions: function () {
            this.params.options = Espo.Utils.clone(this.getMetadata().get(['app', 'language', 'list']) || []);
            this.params.options.unshift('');
            this.translatedOptions = Espo.Utils.clone(this.getLanguage().translate('language', 'options') || {});
            this.translatedOptions[''] = this.translate('Any', 'labels', 'KnowledgeBaseArticle')
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/fields/ico', ['views/fields/base'], function (Dep) {

    return Dep.extend({

        // language=Handlebars
        templateContent: `{{! ~}}
            <span
                class="{{iconClass}} text-muted action icon"
                style="cursor: pointer"
                title="{{viewLabel}}"
                data-action="quickView"
                data-id="{{id}}"
                {{#if notRelationship}}data-scope="{{scope}}"{{/if}}
            ></span>
        {{~!}}`,

        data: function () {
            return {
                notRelationship: this.params.notRelationship,
                viewLabel: this.translate('View'),
                id: this.model.id,
                scope: this.model.entityType,
                iconClass: this.getMetadata().get(['clientDefs', this.model.entityType, 'iconClass']) ||
                    'far fa-calendar-times',
            };
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/event-confirmation/confirmation', ['view'], function (Dep) {

    return Dep.extend({

        template: 'crm:event-confirmation/confirmation',

        data: function () {
            let style = this.actionData.style || 'default';

            return {
                actionData: this.actionData,
                style: style,
                dateStart: this.actionData.dateStart ?
                    this.convertDateTime(this.actionData.dateStart) : null,
                sentDateStart: this.actionData.sentDateStart ?
                    this.convertDateTime(this.actionData.sentDateStart) : null,
                dateStartChanged: this.actionData.sentDateStart &&
                    this.actionData.dateStart !== this.actionData.sentDateStart,
                actionDataList: this.getActionDataList(),
            };
        },

        setup: function () {
            this.actionData = this.options.actionData;
        },

        getActionDataList: function () {
            let actionMap = {
                'Accepted': 'accept',
                'Declined': 'decline',
                'Tentative': 'tentative',
            };

            let statusList = ['Accepted', 'Tentative', 'Declined'];

            if (!statusList.includes(this.actionData.status)) {
                return null;
            }

            let url = window.location.href.replace('action=' + actionMap[this.actionData.status], 'action={action}');

            return statusList.map(item => {
                let active = item === this.actionData.status;

                return {
                    active: active,
                    link: active ? '' : url.replace('{action}', actionMap[item]),
                    label: this.actionData.statusTranslation[item],
                };
            });
        },

        convertDateTime: function (value) {
            let timezone = this.getConfig().get('timeZone');

            let m = this.getDateTime().toMoment(value)
                .tz(timezone);

            return m.format(this.getDateTime().getDateTimeFormat()) + ' ' +
                m.format('Z z');
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/email-queue-item/list', ['views/list'], function (Dep) {

    return Dep.extend({

        createButton: false,
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/email-queue-item/record/list', ['views/record/list'], function (Dep) {

    return Dep.extend({

        rowActionsView: 'views/record/row-actions/remove-only',
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/document/list', ['views/list-with-categories'], function (Dep) {

    return Dep.extend({

        categoryScope: 'DocumentFolder',
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/document/modals/select-records', ['views/modals/select-records-with-categories'], function (Dep) {

    return Dep.extend({

        categoryScope: 'DocumentFolder',
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/document/fields/name', ['views/fields/varchar'], function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.model.isNew()) {
                this.listenTo(this.model, 'change:fileName', () => {
                    this.model.set('name', this.model.get('fileName'));
                });
            }
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/document/fields/file', ['views/fields/file'], function (Dep) {

    return Dep.extend({

        getValueForDisplay: function () {
            if (this.isListMode()) {
                let name = this.model.get(this.nameName);
                let id = this.model.get(this.idName);

                if (!id) {
                    return '';
                }

                return $('<a>')
                    .attr('title', name)
                    .attr('href', this.getBasePath() + '?entryPoint=download&id=' + id)
                    .attr('target', '_BLANK')
                    .append(
                        $('<span>').addClass('fas fa-paperclip small')
                    )
                    .get(0).outerHTML;
            }

            return Dep.prototype.getValueForDisplay.call(this);
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/dashlets/tasks', ['views/dashlets/abstract/record-list'], function (Dep) {

    return Dep.extend({

        listView: 'crm:views/task/record/list-expanded',
        rowActionsView: 'crm:views/task/record/row-actions/dashlet',
    });
});


/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/dashlets/meetings', ['views/dashlets/abstract/record-list'], function (Dep) {

    return Dep.extend({

        name: 'Meetings',
        scope: 'Meeting',
        listView: 'crm:views/meeting/record/list-expanded',
        rowActionsView: 'crm:views/meeting/record/row-actions/dashlet',
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/dashlets/calls', ['views/dashlets/abstract/record-list'], function (Dep) {

    return Dep.extend({

        name: 'Calls',
        scope: 'Call',
        listView: 'crm:views/call/record/list-expanded',
        rowActionsView: 'crm:views/call/record/row-actions/dashlet',
    });
});


define("modules/crm/views/dashlets/calendar", ["exports", "views/dashlets/abstract/base"], function (_exports, _base) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _base = _interopRequireDefault(_base);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class CalendarDashletView extends _base.default {
    name = 'Calendar';
    noPadding = true;
    templateContent = `<div class="calendar-container">{{{calendar}}}</div>`;
    afterRender() {
      const mode = this.getOption('mode');
      if (mode === 'timeline') {
        const userList = [];
        const userIdList = this.getOption('usersIds') || [];
        const userNames = this.getOption('usersNames') || {};
        userIdList.forEach(id => {
          userList.push({
            id: id,
            name: userNames[id] || id
          });
        });
        const viewName = this.getMetadata().get(['clientDefs', 'Calendar', 'timelineView']) || 'crm:views/calendar/timeline';
        this.createView('calendar', viewName, {
          selector: '> .calendar-container',
          header: false,
          calendarType: 'shared',
          userList: userList,
          enabledScopeList: this.getOption('enabledScopeList'),
          suppressLoadingAlert: true
        }, view => {
          view.render();
        });
        return;
      }
      let teamIdList = null;
      if (['basicWeek', 'month', 'basicDay'].includes(mode)) {
        teamIdList = this.getOption('teamsIds');
      }
      const viewName = this.getMetadata().get(['clientDefs', 'Calendar', 'calendarView']) || 'crm:views/calendar/calendar';
      this.createView('calendar', viewName, {
        mode: mode,
        selector: '> .calendar-container',
        header: false,
        enabledScopeList: this.getOption('enabledScopeList'),
        containerSelector: this.getSelector(),
        teamIdList: teamIdList,
        scrollToNowSlots: 3,
        suppressLoadingAlert: true
      }, view => {
        this.listenTo(view, 'view', () => {
          if (this.getOption('mode') === 'month') {
            const title = this.getOption('title');
            const $container = $('<span>').append($('<span>').text(title), ' <span class="chevron-right"></span> ', $('<span>').text(view.getTitle()));
            const $headerSpan = this.$el.closest('.panel').find('.panel-heading > .panel-title > span');
            $headerSpan.html($container.get(0).innerHTML);
          }
        });
        view.render();
        this.on('resize', () => {
          setTimeout(() => view.adjustSize(), 50);
        });
      });
    }
    setupActionList() {
      this.actionList.unshift({
        name: 'viewCalendar',
        text: this.translate('View Calendar', 'labels', 'Calendar'),
        url: '#Calendar',
        iconHtml: '<span class="far fa-calendar-alt"></span>',
        onClick: () => this.actionViewCalendar()
      });
    }
    setupButtonList() {
      if (this.getOption('mode') !== 'timeline') {
        this.buttonList.push({
          name: 'previous',
          html: '<span class="fas fa-chevron-left"></span>',
          onClick: () => this.actionPrevious()
        });
        this.buttonList.push({
          name: 'next',
          html: '<span class="fas fa-chevron-right"></span>',
          onClick: () => this.actionNext()
        });
      }
    }

    /**
     * @return {
     *     import('modules/crm/views/calendar/calendar').default |
     *     import('modules/crm/views/calendar/timeline').default
     * }
     */
    getCalendarView() {
      return this.getView('calendar');
    }
    actionRefresh() {
      const view = this.getCalendarView();
      if (!view) {
        return;
      }
      view.actionRefresh();
    }
    autoRefresh() {
      const view = this.getCalendarView();
      if (!view) {
        return;
      }
      view.actionRefresh({
        suppressLoadingAlert: true
      });
    }
    actionNext() {
      const view = this.getCalendarView();
      if (!view) {
        return;
      }
      view.actionNext();
    }
    actionPrevious() {
      const view = this.getCalendarView();
      if (!view) {
        return;
      }
      view.actionPrevious();
    }
    actionViewCalendar() {
      this.getRouter().navigate('#Calendar', {
        trigger: true
      });
    }
  }
  var _default = _exports.default = CalendarDashletView;
});

define("modules/crm/views/dashlets/activities", ["exports", "views/dashlets/abstract/base", "multi-collection", "helpers/record-modal"], function (_exports, _base, _multiCollection, _recordModal) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _base = _interopRequireDefault(_base);
  _multiCollection = _interopRequireDefault(_multiCollection);
  _recordModal = _interopRequireDefault(_recordModal);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class ActivitiesDashletView extends _base.default {
    name = 'Activities';

    // language=Handlebars
    templateContent = '<div class="list-container">{{{list}}}</div>';
    rowActionsView = 'crm:views/record/row-actions/activities-dashlet';
    defaultListLayout = {
      rows: [[{
        name: 'ico',
        view: 'crm:views/fields/ico',
        params: {
          notRelationship: true
        }
      }, {
        name: 'name',
        link: true
      }], [{
        name: 'dateStart',
        soft: true
      }, {
        name: 'parent'
      }]]
    };
    listLayoutEntityTypeMap = {
      Task: {
        rows: [[{
          name: 'ico',
          view: 'crm:views/fields/ico',
          params: {
            notRelationship: true
          }
        }, {
          name: 'name',
          link: true
        }], [{
          name: 'status'
        }, {
          name: 'dateEnd',
          soft: true
        }, {
          name: 'priority',
          view: 'crm:views/task/fields/priority-for-dashlet'
        }, {
          name: 'parent'
        }]]
      }
    };
    setup() {
      this.seeds = {};
      this.scopeList = this.getOption('enabledScopeList') || [];
      this.listLayout = {};
      this.scopeList.forEach(item => {
        if (item in this.listLayoutEntityTypeMap) {
          this.listLayout[item] = this.listLayoutEntityTypeMap[item];
          return;
        }
        this.listLayout[item] = this.defaultListLayout;
      });
      this.wait(true);
      let i = 0;
      this.scopeList.forEach(scope => {
        this.getModelFactory().create(scope, seed => {
          this.seeds[scope] = seed;
          i++;
          if (i === this.scopeList.length) {
            this.wait(false);
          }
        });
      });
      this.scopeList.slice(0).reverse().forEach(scope => {
        if (this.getAcl().checkScope(scope, 'create')) {
          this.actionList.unshift({
            name: 'createActivity',
            text: this.translate('Create ' + scope, 'labels', scope),
            iconHtml: '<span class="fas fa-plus"></span>',
            url: '#' + scope + '/create',
            data: {
              scope: scope
            }
          });
        }
      });
    }
    afterRender() {
      this.collection = new _multiCollection.default();
      this.collection.seeds = this.seeds;
      this.collection.url = 'Activities/upcoming';
      this.collection.maxSize = this.getOption('displayRecords') || this.getConfig().get('recordsPerPageSmall') || 5;
      this.collection.data.entityTypeList = this.scopeList;
      this.collection.data.futureDays = this.getOption('futureDays');
      if (this.getOption('includeShared')) {
        this.collection.data.includeShared = true;
      }
      this.listenToOnce(this.collection, 'sync', () => {
        this.createView('list', 'crm:views/record/list-activities-dashlet', {
          selector: '> .list-container',
          pagination: false,
          type: 'list',
          rowActionsView: this.rowActionsView,
          checkboxes: false,
          collection: this.collection,
          listLayout: this.listLayout
        }, view => {
          view.render();
        });
      });
      this.collection.fetch();
    }
    actionRefresh() {
      this.refreshInternal();
    }
    autoRefresh() {
      this.refreshInternal({
        skipNotify: true
      });
    }

    /**
     * @private
     * @param {{skipNotify?: boolean}} [options]
     * @return {Promise<void>}
     */
    async refreshInternal() {
      let options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
      if (!options.skipNotify) {
        Espo.Ui.notifyWait();
      }
      await this.collection.fetch({
        previousTotal: this.collection.total,
        previousDataList: this.collection.models.map(model => {
          return Espo.Utils.cloneDeep(model.attributes);
        })
      });
      if (!options.skipNotify) {
        Espo.Ui.notify();
      }
    }

    // noinspection JSUnusedGlobalSymbols
    actionCreateActivity(data) {
      const scope = data.scope;
      const attributes = {};
      this.populateAttributesAssignedUser(scope, attributes);
      const helper = new _recordModal.default();
      helper.showCreate(this, {
        entityType: scope,
        attributes: attributes,
        afterSave: () => {
          this.actionRefresh();
        }
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionCreateMeeting() {
      const attributes = {};
      this.populateAttributesAssignedUser('Meeting', attributes);
      const helper = new _recordModal.default();
      helper.showCreate(this, {
        entityType: 'Meeting',
        attributes: attributes,
        afterSave: () => {
          this.actionRefresh();
        }
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionCreateCall() {
      const attributes = {};
      this.populateAttributesAssignedUser('Call', attributes);
      const helper = new _recordModal.default();
      helper.showCreate(this, {
        entityType: 'Call',
        attributes: attributes,
        afterSave: () => {
          this.actionRefresh();
        }
      });
    }
    populateAttributesAssignedUser(scope, attributes) {
      if (this.getMetadata().get(['entityDefs', scope, 'fields', 'assignedUsers'])) {
        attributes['assignedUsersIds'] = [this.getUser().id];
        attributes['assignedUsersNames'] = {};
        attributes['assignedUsersNames'][this.getUser().id] = this.getUser().get('name');
      } else {
        attributes['assignedUserId'] = this.getUser().id;
        attributes['assignedUserName'] = this.getUser().get('name');
      }
    }
  }
  var _default = _exports.default = ActivitiesDashletView;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/dashlets/options/sales-pipeline', ['crm:views/dashlets/options/chart'], function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.getAcl().getLevel('Opportunity', 'read') === 'own') {
                this.hideField('team');
            }
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/dashlets/options/calendar', ['views/dashlets/options/base'], function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.manageFields();
            this.listenTo(this.model, 'change:mode', this.manageFields, this);
        },


        init: function () {
            Dep.prototype.init.call(this);

            this.fields.enabledScopeList.options = this.getConfig().get('calendarEntityList') || [];
        },

        manageFields: function (model, value, o) {
            if (this.model.get('mode') === 'timeline') {
                this.showField('users');
            } else {
                this.hideField('users');
            }

            if (
                this.getAcl().getPermissionLevel('userCalendar') !== 'no'
                &&
                ~['basicWeek', 'month', 'basicDay'].indexOf(this.model.get('mode'))
            ) {
                this.showField('teams');
            } else {
                if (o && o.ui) {
                    this.model.set('teamsIds', []);
                }

                this.hideField('teams');
            }
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/dashlets/options/activities', ['views/dashlets/options/base'], function (Dep) {

    return Dep.extend({

        init: function () {
            Dep.prototype.init.call(this);

            var entityTypeList = [];
            var activitiesEntityList = Espo.Utils.clone(this.getConfig().get('activitiesEntityList') || []);

            activitiesEntityList.push('Task');

            activitiesEntityList.forEach(item => {
                if (this.getMetadata().get(['scopes', item, 'disabled'])) {
                    return;
                }

                if (!this.getAcl().checkScope(item)) {
                    return;
                }

                entityTypeList.push(item);
            });

            this.fields.enabledScopeList.options = entityTypeList;
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/dashlets/options/sales-pipeline/fields/team', ['views/fields/link'], function (Dep) {

    return Dep.extend({

        getSelectBoolFilterList: function () {
            if (this.getAcl().getLevel('Opportunity', 'read') === 'team') {
                return ['onlyMy'];
            }
        },
    });

});

define("modules/crm/views/contact/detail", ["exports", "views/detail"], function (_exports, _detail) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _detail = _interopRequireDefault(_detail);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  /** Left for bc. */
  class _default extends _detail.default {}
  _exports.default = _default;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/contact/record/detail-small', ['views/record/detail-small', 'crm:views/contact/record/detail'], function (Dep, Detail) {

    return Dep.extend({

    });
});

define("modules/crm/views/contact/modals/select-for-portal-user", ["exports", "views/modals/select-records"], function (_exports, _selectRecords) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _selectRecords = _interopRequireDefault(_selectRecords);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class SelectForPortalUserModalView extends _selectRecords.default {
    /**
     * @param {
     *     module:views/modals/select-records~Options &
     *     {onSkip: function()}
     * } options
     */
    constructor(options) {
      super(options);
      this.options = options;
    }
    setup() {
      super.setup();
      this.buttonList.unshift({
        name: 'skip',
        text: this.translate('Proceed w/o Contact', 'labels', 'User'),
        onClick: () => this.actionSkip()
      });
    }
    actionSkip() {
      this.options.onSkip();
      this.close();
    }
  }
  _exports.default = SelectForPortalUserModalView;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/contact/fields/title', ['views/fields/varchar'], function (Dep) {

    return Dep.extend({

        setupOptions: function () {
            this.params.options = Espo.Utils.clone(
                this.getMetadata().get('entityDefs.Account.fields.contactRole.options') || []
            );
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/contact/fields/opportunity-role', ['views/fields/enum'], function (Dep) {

    return Dep.extend({

        searchTypeList: ['anyOf', 'noneOf'],
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/contact/fields/name-for-account', ['views/fields/person-name'], function (Dep) {

    return Dep.extend({

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.mode === 'listLink') {
                if (this.model.get('accountIsInactive')) {
                    this.$el.find('a').css('text-decoration', 'line-through');
                }
            }
        },

        getAttributeList: function () {
            return ['name', 'accountIsInactive'];
        },
    });
});

define("modules/crm/views/contact/fields/accounts", ["exports", "views/fields/link-multiple-with-columns"], function (_exports, _linkMultipleWithColumns) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _linkMultipleWithColumns = _interopRequireDefault(_linkMultipleWithColumns);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class AccountsFieldView extends _linkMultipleWithColumns.default {
    getAttributeList() {
      const list = super.getAttributeList();
      list.push('accountId');
      list.push('accountName');
      list.push('title');
      return list;
    }
    setup() {
      super.setup();
      this.events['click [data-action="switchPrimary"]'] = e => {
        const $target = $(e.currentTarget);
        const id = $target.data('id');
        if (!$target.hasClass('active')) {
          this.$el.find('button[data-action="switchPrimary"]').removeClass('active').children().addClass('text-muted');
          $target.addClass('active').children().removeClass('text-muted');
          this.setPrimaryId(id);
        }
      };
      this.primaryIdFieldName = 'accountId';
      this.primaryNameFieldName = 'accountName';
      this.primaryRoleFieldName = 'title';
      this.primaryId = this.model.get(this.primaryIdFieldName);
      this.primaryName = this.model.get(this.primaryNameFieldName);
      this.listenTo(this.model, 'change:' + this.primaryIdFieldName, () => {
        this.primaryId = this.model.get(this.primaryIdFieldName);
        this.primaryName = this.model.get(this.primaryNameFieldName);
      });
      if (this.isEditMode() || this.isDetailMode()) {
        this.events['click a[data-action="setPrimary"]'] = e => {
          const id = $(e.currentTarget).data('id');
          this.setPrimaryId(id);
          this.reRender();
        };
      }
    }
    setPrimaryId(id) {
      this.primaryId = id;
      if (id) {
        this.primaryName = this.nameHash[id];
      } else {
        this.primaryName = null;
      }
      this.trigger('change');
    }
    renderLinks() {
      if (this.primaryId) {
        this.addLinkHtml(this.primaryId, this.primaryName);
      }
      this.ids.forEach(id => {
        if (id !== this.primaryId) {
          this.addLinkHtml(id, this.nameHash[id]);
        }
      });
    }
    getValueForDisplay() {
      if (this.isDetailMode() || this.isListMode()) {
        const itemList = [];
        if (this.primaryId) {
          itemList.push(this.getDetailLinkHtml(this.primaryId, this.primaryName));
        }
        this.ids.forEach(id => {
          if (id !== this.primaryId) {
            itemList.push(this.getDetailLinkHtml(id));
          }
        });
        return itemList.map(item => {
          return $('<div>').addClass('link-multiple-item').html(item).get(0).outerHTML;
        }).join('');
      }
    }
    getDetailLinkHtml(id, name) {
      const html = super.getDetailLinkHtml(id, name);
      if (this.getColumnValue(id, 'isInactive')) {
        const $el = $('<div>').html(html);
        $el.find('a').css('text-decoration', 'line-through');
        return $el.get(0).innerHTML;
      }
      return html;
    }
    afterAddLink(id) {
      super.afterAddLink(id);
      if (this.ids.length === 1) {
        this.primaryId = id;
        this.primaryName = this.nameHash[id];
      }
      this.controlPrimaryAppearance();
    }
    afterDeleteLink(id) {
      super.afterDeleteLink(id);
      if (this.ids.length === 0) {
        this.primaryId = null;
        this.primaryName = null;
        return;
      }
      if (id === this.primaryId) {
        this.primaryId = this.ids[0];
        this.primaryName = this.nameHash[this.primaryId];
      }
      this.controlPrimaryAppearance();
    }
    controlPrimaryAppearance() {
      this.$el.find('li.set-primary-list-item').removeClass('hidden');
      if (this.primaryId) {
        this.$el.find('li.set-primary-list-item[data-id="' + this.primaryId + '"]').addClass('hidden');
      }
    }
    addLinkHtml(id, name) {
      name = name || id;
      if (this.isSearchMode()) {
        return super.addLinkHtml(id, name);
      }
      const $el = super.addLinkHtml(id, name);
      const isPrimary = id === this.primaryId;
      const $a = $('<a>').attr('role', 'button').attr('tabindex', '0').attr('data-action', 'setPrimary').attr('data-id', id).text(this.translate('Set Primary', 'labels', 'Account'));
      const $li = $('<li>').addClass('set-primary-list-item').attr('data-id', id).append($a);
      if (isPrimary || this.ids.length === 1) {
        $li.addClass('hidden');
      }
      $el.find('ul.dropdown-menu').append($li);
      if (this.getColumnValue(id, 'isInactive')) {
        $el.find('div.link-item-name').css('text-decoration', 'line-through');
      }
    }
    fetch() {
      const data = super.fetch();
      data[this.primaryIdFieldName] = this.primaryId;
      data[this.primaryNameFieldName] = this.primaryName;
      data[this.primaryRoleFieldName] = (this.columns[this.primaryId] || {}).role || null;

      // noinspection JSUnresolvedReference
      data.accountIsInactive = (this.columns[this.primaryId] || {}).isInactive || false;
      if (!this.primaryId) {
        data[this.primaryRoleFieldName] = null;
        data.accountIsInactive = null;
      }
      return data;
    }
  }
  var _default = _exports.default = AccountsFieldView;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/contact/fields/account', ['views/fields/link'], function (Dep) {

    return Dep.extend({

        getAttributeList: function () {
            var list = Dep.prototype.getAttributeList.call(this);

            list.push('accountIsInactive');

            return list;
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.mode === 'list' || this.mode === 'detail') {
                if (this.model.get('accountIsInactive')) {
                    this.$el.find('a').css('textDecoration', 'line-through');
                }
            }
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/contact/fields/account-role', ['views/fields/varchar'], function (Dep) {

    return Dep.extend({

        detailTemplate: 'crm:contact/fields/account-role/detail',
        listTemplate: 'crm:contact/fields/account-role/detail',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:title', () => {
                this.model.set('accountRole', this.model.get('title'));
            });
        },

        getAttributeList: function () {
            var list = Dep.prototype.getAttributeList.call(this);

            list.push('title');
            list.push('accountIsInactive');

            return list;
        },

        data: function () {
            var data = Dep.prototype.data.call(this);

            if (this.model.has('accountIsInactive')) {
                data.accountIsInactive = this.model.get('accountIsInactive');
            }

            return data;
        }
    });
});

define("modules/crm/views/case/record/detail", ["exports", "views/record/detail"], function (_exports, _detail) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _detail = _interopRequireDefault(_detail);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _detail.default {
    selfAssignAction = true;
    getSelfAssignAttributes() {
      if (this.model.attributes.status === 'New') {
        const options = this.getMetadata().get(['entityDefs', 'Case', 'fields', 'status', 'options']) || [];
        if (options.includes('Assigned')) {
          return {
            status: 'Assigned'
          };
        }
      }
      return {};
    }
  }
  _exports.default = _default;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/case/record/panels/activities', ['crm:views/record/panels/activities'], function (Dep) {

    return Dep.extend({

        getComposeEmailAttributes: function (scope, data, callback) {
            data = data || {};

            Espo.Ui.notifyWait();

            Dep.prototype.getComposeEmailAttributes.call(this, scope, data, attributes => {
                attributes.name = '[#' + this.model.get('number') + '] ' + this.model.get('name');

                Espo.Ajax.getRequest('Case/action/emailAddressList?id=' + this.model.id).then(list => {
                    attributes.to = '';
                    attributes.cc = '';
                    attributes.nameHash = {};

                    list.forEach((item, i) => {
                        if (i === 0) {
                            attributes.to += item.emailAddress + ';';
                        } else {
                            attributes.cc += item.emailAddress + ';';
                        }

                        attributes.nameHash[item.emailAddress] = item.name;
                    });

                    Espo.Ui.notify(false);

                    callback.call(this, attributes);
                });
            })
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/campaign-tracking-url/record/edit', ['views/record/edit'], function (Dep) {

    return Dep.extend({
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/campaign-tracking-url/record/edit-small', ['views/record/edit-small'], function (Dep) {

    return Dep.extend({
    });
});


/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/campaign-log-record/fields/data', ['views/fields/base'], function (Dep) {

    return Dep.extend({

        listTemplate: 'crm:campaign-log-record/fields/data/detail',

    	getValueForDisplay: function () {
    		let action = this.model.get('action');

    		switch (action) {
    			case 'Sent':
                case 'Opened':
                    if (
                        this.model.get('objectId') &&
                        this.model.get('objectType') &&
                        this.model.get('objectName')
                    ) {
                        return $('<a>')
                            .attr('href', '#' + this.model.get('objectType') + '/view/' + this.model.get('objectId'))
                            .text(this.model.get('objectName'))
                            .get(0).outerHTML;
                    }

                    return $('<span>')
                        .text(this.model.get('stringData') || '')
                        .get(0).outerHTML;

    			case 'Clicked':
                    if (
                        this.model.get('objectId') &&
                        this.model.get('objectType') &&
                        this.model.get('objectName')
                    ) {
                        return $('<a>')
                            .attr('href', '#' + this.model.get('objectType') + '/view/' + this.model.get('objectId'))
                            .text(this.model.get('objectName'))
                            .get(0).outerHTML;
                    }

                    return $('<span>')
                        .text(this.model.get('stringData') || '')
                        .get(0).outerHTML;

                case 'Opted Out':
                    return $('<span>')
                        .text(this.model.get('stringData') || '')
                        .addClass('text-danger')
                        .get(0).outerHTML;

                case 'Bounced':
                    let emailAddress = this.model.get('stringData');
                    let type = this.model.get('stringAdditionalData');

                    let typeLabel = type === 'Hard' ?
                        this.translate('hard', 'labels', 'Campaign') :
                        this.translate('soft', 'labels', 'Campaign')

                    return $('<span>')
                        .append(
                            $('<span>')
                                .addClass('label label-default')
                                .text(typeLabel),
                            ' ',
                            $('<s>')
                                .text(emailAddress)
                                .addClass(type === 'Hard' ? 'text-danger' : '')
                        )
                        .get(0).outerHTML;
    		}

    		return '';
    	},
    });
});

define("modules/crm/views/campaign/unsubscribe", ["exports", "view"], function (_exports, _view) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _view = _interopRequireDefault(_view);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class CampaignUnsubscribeView extends _view.default {
    template = 'crm:campaign/unsubscribe';
    data() {
      return {
        isSubscribed: this.isSubscribed,
        inProcess: this.inProcess
      };
    }
    setup() {
      super.setup();
      this.actionData = /** @type {Record} */this.options.actionData;
      this.isSubscribed = this.actionData.isSubscribed;
      this.inProcess = false;
      const endpointUrl = this.actionData.hash && this.actionData.emailAddress ? `Campaign/unsubscribe/${this.actionData.emailAddress}/${this.actionData.hash}` : `Campaign/unsubscribe/${this.actionData.queueItemId}`;
      this.addActionHandler('subscribe', () => {
        Espo.Ui.notifyWait();
        this.inProcess = true;
        this.reRender();
        Espo.Ajax.deleteRequest(endpointUrl).then(() => {
          this.isSubscribed = true;
          this.inProcess = false;
          this.reRender().then(() => {
            const message = this.translate('subscribedAgain', 'messages', 'Campaign');
            Espo.Ui.notify(message, 'success', 0, {
              closeButton: true
            });
          });
        }).catch(() => {
          this.inProcess = false;
          this.reRender();
        });
      });
      this.addActionHandler('unsubscribe', () => {
        Espo.Ui.notifyWait();
        this.inProcess = true;
        this.reRender();
        Espo.Ajax.postRequest(endpointUrl).then(() => {
          Espo.Ui.success(this.translate('unsubscribed', 'messages', 'Campaign'), {
            closeButton: true
          });
          this.isSubscribed = false;
          this.inProcess = false;
          this.reRender().then(() => {
            const message = this.translate('unsubscribed', 'messages', 'Campaign');
            Espo.Ui.notify(message, 'success', 0, {
              closeButton: true
            });
          });
        }).catch(() => {
          this.inProcess = false;
          this.reRender();
        });
      });
    }
  }
  var _default = _exports.default = CampaignUnsubscribeView;
});

define("modules/crm/views/campaign/tracking-url", ["exports", "view"], function (_exports, _view) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _view = _interopRequireDefault(_view);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _view.default {
    // language=Handlebars
    templateContent = `
        <div class="container content">
            <div class="block-center-md">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="complex-text">{{complexText message}}</div>
                    </div>
                </div>
            </div>
        </div>
    `;
    data() {
      // noinspection JSUnresolvedReference
      return {
        message: this.options.message
      };
    }
  }
  _exports.default = _default;
});

define("modules/crm/views/campaign/detail", ["exports", "views/detail"], function (_exports, _detail) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _detail = _interopRequireDefault(_detail);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _detail.default {}
  _exports.default = _default;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/campaign/record/detail', ['views/record/detail'], function (Dep) {

    return Dep.extend({

        duplicateAction: true,

        setupActionItems: function () {
            Dep.prototype.setupActionItems.call(this);
            this.dropdownItemList.push({
                'label': 'Generate Mail Merge PDF',
                'name': 'generateMailMergePdf',
                'hidden': !this.isMailMergeAvailable()
            });

            this.listenTo(this.model, 'change', function () {
                if (this.isMailMergeAvailable()) {
                    this.showActionItem('generateMailMergePdf');
                } else {
                    this.hideActionItem('generateMailMergePdf');
                }
            }, this);
        },

        afterRender: function () {
        	Dep.prototype.afterRender.call(this);
        },

        isMailMergeAvailable: function () {
            if (this.model.get('type') !== 'Mail') {
                return false;
            }

            if (!this.model.get('targetListsIds') || !this.model.get('targetListsIds').length) {
                return false;
            }

            if (
                !this.model.get('leadsTemplateId') &&
                !this.model.get('contactsTemplateId') &&
                !this.model.get('accountsTemplateId') &&
                !this.model.get('usersTemplateId')
            ) {
                return false;
            }

            return true;
        },

        actionGenerateMailMergePdf: function () {
            this.createView('dialog', 'crm:views/campaign/modals/mail-merge-pdf', {
                model: this.model,
            }, function (view) {
                view.render();

                this.listenToOnce(view, 'proceed', (link) => {
                    this.clearView('dialog');

                    Espo.Ui.notifyWait();

                    Espo.Ajax.postRequest(`Campaign/${this.model.id}/generateMailMerge`, {link: link})
                        .then(response => {
                            Espo.Ui.notify(false);

                            window.open('?entryPoint=download&id=' + response.id, '_blank');
                        });
                });
            });
        },
    });
});

define("modules/crm/views/campaign/record/panels/campaign-stats", ["exports", "views/record/panels/side"], function (_exports, _side) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _side = _interopRequireDefault(_side);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  // noinspection JSUnusedGlobalSymbols
  class _default extends _side.default {
    controlStatsFields() {
      const type = this.model.attributes.type;
      let fieldList;
      switch (type) {
        case 'Email':
        case 'Newsletter':
          fieldList = ['sentCount', 'openedCount', 'clickedCount', 'optedOutCount', 'bouncedCount', 'leadCreatedCount', 'optedInCount', 'revenue'];
          break;
        case 'Informational Email':
          fieldList = ['sentCount', 'bouncedCount'];
          break;
        case 'Web':
          fieldList = ['leadCreatedCount', 'optedInCount', 'revenue'];
          break;
        case 'Television':
        case 'Radio':
          fieldList = ['leadCreatedCount', 'revenue'];
          break;
        case 'Mail':
          fieldList = ['sentCount', 'leadCreatedCount', 'optedInCount', 'revenue'];
          break;
        default:
          fieldList = ['leadCreatedCount', 'revenue'];
      }
      if (!this.getConfig().get('massEmailOpenTracking')) {
        const i = fieldList.indexOf('openedCount');
        if (i > -1) {
          fieldList.splice(i, 1);
        }
      }
      this.statsFieldList.forEach(item => {
        this.options.recordViewObject.hideField(item);
      });
      fieldList.forEach(item => {
        this.options.recordViewObject.showField(item);
      });
      if (!this.getAcl().checkScope('Lead')) {
        this.options.recordViewObject.hideField('leadCreatedCount', true);
      }
      if (!this.getAcl().checkScope('Opportunity')) {
        this.options.recordViewObject.hideField('revenue', true);
      }
    }
    setupFields() {
      this.fieldList = ['sentCount', 'openedCount', 'clickedCount', 'optedOutCount', 'bouncedCount', 'leadCreatedCount', 'optedInCount', 'revenue'];
      this.statsFieldList = this.fieldList;
    }
    setup() {
      super.setup();
      this.controlStatsFields();
      this.listenTo(this.model, 'change:type', () => this.controlStatsFields());
    }
  }
  _exports.default = _default;
});

define("modules/crm/views/campaign/record/panels/campaign-log-records", ["exports", "views/record/panels/relationship", "helpers/record-modal"], function (_exports, _relationship, _recordModal) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _relationship = _interopRequireDefault(_relationship);
  _recordModal = _interopRequireDefault(_recordModal);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  // noinspection JSUnusedGlobalSymbols
  class CampaignLogRecordsPanelView extends _relationship.default {
    filterList = ["all", "sent", "opened", "optedOut", "bounced", "clicked", "optedIn", "leadCreated"];
    setup() {
      if (this.getAcl().checkScope('TargetList', 'create')) {
        this.actionList.push({
          action: 'createTargetList',
          label: 'Create Target List'
        });
      }
      this.filterList = Espo.Utils.clone(this.filterList);
      if (!this.getConfig().get('massEmailOpenTracking')) {
        const i = this.filterList.indexOf('opened');
        if (i >= 0) {
          this.filterList.splice(i, 1);
        }
      }
      super.setup();
    }
    actionCreateTargetList() {
      const attributes = {
        sourceCampaignId: this.model.id,
        sourceCampaignName: this.model.attributes.name
      };
      if (!this.collection.data.primaryFilter) {
        attributes.includingActionList = [];
      } else {
        const status = Espo.Utils.upperCaseFirst(this.collection.data.primaryFilter).replace(/([A-Z])/g, ' $1');
        attributes.includingActionList = [status];
      }
      const helper = new _recordModal.default();
      helper.showCreate(this, {
        entityType: 'TargetList',
        attributes: attributes,
        fullFormDisabled: true,
        layoutName: 'createFromCampaignLog',
        afterSave: () => {
          Espo.Ui.success(this.translate('Done'));
        },
        beforeRender: view => {
          view.getRecordView().setFieldRequired('includingActionList');
        }
      });
    }
  }
  _exports.default = CampaignLogRecordsPanelView;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/campaign/modals/mail-merge-pdf', ['views/modal', 'ui/select'],
function (Dep, /** module:ui/select */ Select) {

    return Dep.extend({

        template: 'crm:campaign/modals/mail-merge-pdf',

        data: function () {
            return {
                linkList: this.linkList,
            };
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.headerText = this.translate('Generate Mail Merge PDF', 'labels', 'Campaign');

            var linkList = ['contacts', 'leads', 'accounts', 'users'];
            this.linkList = [];

            linkList.forEach(link => {
                if (!this.model.get(link + 'TemplateId')) {
                    return;
                }

                let targetEntityType = this.getMetadata()
                    .get(['entityDefs', 'TargetList', 'links', link, 'entity']);

                if (!this.getAcl().checkScope(targetEntityType)) {
                    return;
                }

                this.linkList.push(link);
            });

            this.buttonList.push({
                name: 'proceed',
                label: 'Proceed',
                style: 'danger'
            });

            this.buttonList.push({
                name: 'cancel',
                label: 'Cancel'
            });
        },

        afterRender: function () {
            Select.init(this.$el.find('.field[data-name="link"] select'));
        },

        actionProceed: function () {
            let link = this.$el.find('.field[data-name="link"] select').val();

            this.trigger('proceed', link);
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/campaign/fields/template', ['views/fields/link'], function (Dep) {

    return Dep.extend({

        createDisabled: true,

        getSelectFilters: function () {
            return {
                entityType: {
                    type: 'in',
                    value: [
                        this.getMetadata().get(['entityDefs', 'Campaign', 'fields', this.name, 'targetEntityType'])
                    ],
                }
            };
        }
    });
});

define("modules/crm/views/campaign/fields/int-with-percentage", ["exports", "views/fields/int"], function (_exports, _int) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _int = _interopRequireDefault(_int);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  // noinspection JSUnusedGlobalSymbols
  class _default extends _int.default {
    setup() {
      this.percentageField = this.name.substr(0, this.name.length - 5) + 'Percentage';
      super.setup();
    }
    getAttributeList() {
      const list = super.getAttributeList();
      if (this.model.hasField(this.percentageField)) {
        list.push(this.percentageField);
      }
      return list;
    }
    getValueForDisplay() {
      const percentageFieldName = this.percentageField;
      let value = this.model.get(this.name);
      const percentageValue = this.model.get(percentageFieldName);
      if (percentageValue != null && percentageValue) {
        value += ' ' + '(' + this.model.get(percentageFieldName) + '%)';
      }
      return value;
    }
  }
  _exports.default = _default;
});

define("modules/crm/views/call/detail", ["exports", "modules/crm/views/meeting/detail"], function (_exports, _detail) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _detail = _interopRequireDefault(_detail);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _detail.default {}
  _exports.default = _default;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/call/record/list-expanded', ['views/record/list-expanded', 'crm:views/call/record/list'],
function (Dep, List) {

    return Dep.extend({

        actionSetHeld: function (data) {
            List.prototype.actionSetHeld.call(this, data);
        },

        actionSetNotHeld: function (data) {
            List.prototype.actionSetNotHeld.call(this, data);
        },
    });
});

define("modules/crm/views/call/record/edit-small", ["exports", "views/record/edit"], function (_exports, _edit) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _edit = _interopRequireDefault(_edit);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _edit.default {}
  _exports.default = _default;
});

define("modules/crm/views/call/record/detail", ["exports", "views/record/detail"], function (_exports, _detail) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _detail = _interopRequireDefault(_detail);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _detail.default {
    duplicateAction = true;
    setupActionItems() {
      super.setupActionItems();
      if (this.getAcl().checkModel(this.model, 'edit') && this.getAcl().checkField(this.entityType, 'status', 'edit')) {
        if (!['Held', 'Not Held'].includes(this.model.attributes.status)) {
          this.dropdownItemList.push({
            label: 'Set Held',
            name: 'setHeld',
            onClick: () => this.actionSetHeld()
          });
          this.dropdownItemList.push({
            label: 'Set Not Held',
            name: 'setNotHeld',
            onClick: () => this.actionSetNotHeld()
          });
        }
      }
    }
    manageAccessEdit(second) {
      super.manageAccessEdit(second);
      if (second) {
        if (!this.getAcl().checkModel(this.model, 'edit', true)) {
          this.hideActionItem('setHeld');
          this.hideActionItem('setNotHeld');
        }
      }
    }
    actionSetHeld() {
      this.model.save({
        status: 'Held'
      }, {
        patch: true
      }).then(() => {
        Espo.Ui.success(this.translate('Saved'));
        this.removeActionItem('setHeld');
        this.removeActionItem('setNotHeld');
      });
    }
    actionSetNotHeld() {
      this.model.save({
        status: 'Not Held'
      }, {
        patch: true
      }).then(() => {
        Espo.Ui.success(this.translate('Saved'));
        this.removeActionItem('setHeld');
        this.removeActionItem('setNotHeld');
      });
    }
  }
  _exports.default = _default;
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/call/record/row-actions/default', ['views/record/row-actions/view-and-edit'], function (Dep) {

    return Dep.extend({

        getActionList: function () {
            const actionList = Dep.prototype.getActionList.call(this);

            if (
                this.options.acl.edit &&
                !['Held', 'Not Held'].includes(this.model.get('status')) &&
                this.getAcl().checkField(this.model.entityType, 'status', 'edit')
            ) {
                actionList.push({
                    action: 'setHeld',
                    label: 'Set Held',
                    data: {
                        id: this.model.id
                    },
                    groupIndex: 1,
                });
                actionList.push({
                    action: 'setNotHeld',
                    label: 'Set Not Held',
                    data: {
                        id: this.model.id
                    },
                    groupIndex: 1,
                });
            }

            if (this.options.acl.delete) {
                actionList.push({
                    action: 'quickRemove',
                    label: 'Remove',
                    data: {
                        id: this.model.id,
                        scope: this.model.entityType
                    },
                    groupIndex: 0,
                });
            }

            return actionList;
        }
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

define('crm:views/call/record/row-actions/dashlet', ['views/record/row-actions/view-and-edit'], function (Dep) {

    return Dep.extend({

        getActionList: function () {
            var actionList = Dep.prototype.getActionList.call(this);

            if (
                this.options.acl.edit &&
                !['Held', 'Not Held'].includes(this.model.get('status')) &&
                this.getAcl().checkField(this.model.entityType, 'status', 'edit')
            ) {
                actionList.push({
                    action: 'setHeld',
                    label: 'Set Held',
                    data: {
                        id: this.model.id
                    },
                    groupIndex: 1,
                });

                actionList.push({
                    action: 'setNotHeld',
                    label: 'Set Not Held',
                    data: {
                        id: this.model.id
                    },
                    groupIndex: 1,
                });
            }

            if (this.options.acl.delete) {
                actionList.push({
                    action: 'quickRemove',
                    label: 'Remove',
                    data: {
                        id: this.model.id,
                        scope: this.model.entityType
                    },
                    groupIndex: 0,
                });
            }

            return actionList;
        }
    });
});

define("modules/crm/views/call/fields/leads", ["exports", "modules/crm/views/call/fields/contacts"], function (_exports, _contacts) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _contacts = _interopRequireDefault(_contacts);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _contacts.default {}
  _exports.default = _default;
});

define("modules/crm/views/call/fields/date-start", ["exports", "views/fields/datetime", "moment"], function (_exports, _datetime, _moment) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _datetime = _interopRequireDefault(_datetime);
  _moment = _interopRequireDefault(_moment);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class DateStartCallFieldView extends _datetime.default {
    setup() {
      super.setup();
      this.notActualStatusList = [...(this.getMetadata().get(`scopes.${this.entityType}.completedStatusList`) || []), ...(this.getMetadata().get(`scopes.${this.entityType}.canceledStatusList`) || [])];
    }
    getAttributeList() {
      return [...super.getAttributeList(), 'dateEnd', 'status'];
    }
    data() {
      let style;
      const status = this.model.get('status');
      if (status && !this.notActualStatusList.includes(status) && (this.mode === this.MODE_DETAIL || this.mode === this.MODE_LIST)) {
        if (this.isDateInPast('dateEnd')) {
          style = 'danger';
        } else if (this.isDateInPast('dateStart')) {
          style = 'warning';
        }
      }

      // noinspection JSValidateTypes
      return {
        ...super.data(),
        style: style
      };
    }

    /**
     * @private
     * @param {string} field
     * @return {boolean}
     */
    isDateInPast(field) {
      const value = this.model.get(field);
      if (value) {
        const d = this.getDateTime().toMoment(value);
        const now = (0, _moment.default)().tz(this.getDateTime().timeZone || 'UTC');
        if (d.unix() < now.unix()) {
          return true;
        }
      }
      return false;
    }
  }
  var _default = _exports.default = DateStartCallFieldView;
});

define("modules/crm/views/calendar/mode-buttons", ["exports", "view"], function (_exports, _view) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _view = _interopRequireDefault(_view);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  /** @module modules/crm/views/calendar/mode-buttons */

  class CalendarModeButtons extends _view.default {
    template = 'crm:calendar/mode-buttons';
    visibleModeListCount = 3;
    data() {
      const scopeFilterList = Espo.Utils.clone(this.scopeList);
      scopeFilterList.unshift('all');
      const scopeFilterDataList = [];
      this.scopeList.forEach(scope => {
        const o = {
          scope: scope
        };
        if (!this.getCalendarParentView().enabledScopeList.includes(scope)) {
          o.disabled = true;
        }
        scopeFilterDataList.push(o);
      });
      return {
        mode: this.mode,
        visibleModeDataList: this.getVisibleModeDataList(),
        hiddenModeDataList: this.getHiddenModeDataList(),
        scopeFilterDataList: scopeFilterDataList,
        isCustomViewAvailable: this.isCustomViewAvailable,
        hasMoreItems: this.isCustomViewAvailable,
        hasWorkingTimeCalendarLink: this.getAcl().checkScope('WorkingTimeCalendar')
      };
    }

    /**
     * @return {
     *     import('modules/crm/views/calendar/calendar').default|
     *     import('modules/crm/views/calendar/timeline').default
     * }
     */
    getCalendarParentView() {
      // noinspection JSValidateTypes
      return this.getParentView();
    }
    setup() {
      this.isCustomViewAvailable = this.options.isCustomViewAvailable;
      this.modeList = this.options.modeList;
      this.scopeList = this.options.scopeList;
      this.mode = this.options.mode;
    }

    /**
     * @param {boolean} [originalOrder]
     * @return {Object.<string, *>[]}
     */
    getModeDataList(originalOrder) {
      const list = [];
      this.modeList.forEach(name => {
        const o = {
          mode: name,
          label: this.translate(name, 'modes', 'Calendar'),
          labelShort: this.translate(name, 'modes', 'Calendar').substring(0, 2)
        };
        list.push(o);
      });
      if (this.isCustomViewAvailable) {
        (this.getPreferences().get('calendarViewDataList') || []).forEach(item => {
          item = Espo.Utils.clone(item);
          item.mode = 'view-' + item.id;
          item.label = item.name;
          item.labelShort = (item.name || '').substring(0, 2);
          list.push(item);
        });
      }
      if (originalOrder) {
        return list;
      }
      let currentIndex = -1;
      list.forEach((item, i) => {
        if (item.mode === this.mode) {
          currentIndex = i;
        }
      });
      return list;
    }
    getVisibleModeDataList() {
      const fullList = this.getModeDataList();
      const current = fullList.find(it => it.mode === this.mode);
      const list = fullList.slice(0, this.visibleModeListCount);
      if (current && !list.find(it => it.mode === this.mode)) {
        list.push(current);
      }
      return list;
    }
    getHiddenModeDataList() {
      const fullList = this.getModeDataList();
      const list = [];
      fullList.forEach((o, i) => {
        if (i < this.visibleModeListCount) {
          return;
        }
        list.push(o);
      });
      return list;
    }
  }
  var _default = _exports.default = CalendarModeButtons;
});

define("modules/crm/views/calendar/calendar-page", ["exports", "view", "crm:views/calendar/modals/edit-view", "di", "helpers/site/shortcut-manager", "helpers/util/debounce", "web-socket-manager", "utils"], function (_exports, _view, _editView, _di, _shortcutManager, _debounce, _webSocketManager, _utils) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _view = _interopRequireDefault(_view);
  _editView = _interopRequireDefault(_editView);
  _shortcutManager = _interopRequireDefault(_shortcutManager);
  _debounce = _interopRequireDefault(_debounce);
  _webSocketManager = _interopRequireDefault(_webSocketManager);
  _utils = _interopRequireDefault(_utils);
  var _staticBlock;
  let _init_shortcutManager, _init_extra_shortcutManager, _init_webSocketManager, _init_extra_webSocketManager;
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  function _applyDecs(e, t, n, r, o, i) { var a, c, u, s, f, l, p, d = Symbol.metadata || Symbol.for("Symbol.metadata"), m = Object.defineProperty, h = Object.create, y = [h(null), h(null)], v = t.length; function g(t, n, r) { return function (o, i) { n && (i = o, o = e); for (var a = 0; a < t.length; a++) i = t[a].apply(o, r ? [i] : []); return r ? i : o; }; } function b(e, t, n, r) { if ("function" != typeof e && (r || void 0 !== e)) throw new TypeError(t + " must " + (n || "be") + " a function" + (r ? "" : " or undefined")); return e; } function applyDec(e, t, n, r, o, i, u, s, f, l, p) { function d(e) { if (!p(e)) throw new TypeError("Attempted to access private element on non-instance"); } var h = [].concat(t[0]), v = t[3], w = !u, D = 1 === o, S = 3 === o, j = 4 === o, E = 2 === o; function I(t, n, r) { return function (o, i) { return n && (i = o, o = e), r && r(o), P[t].call(o, i); }; } if (!w) { var P = {}, k = [], F = S ? "get" : j || D ? "set" : "value"; if (f ? (l || D ? P = { get: _setFunctionName(function () { return v(this); }, r, "get"), set: function (e) { t[4](this, e); } } : P[F] = v, l || _setFunctionName(P[F], r, E ? "" : F)) : l || (P = Object.getOwnPropertyDescriptor(e, r)), !l && !f) { if ((c = y[+s][r]) && 7 !== (c ^ o)) throw Error("Decorating two elements with the same name (" + P[F].name + ") is not supported yet"); y[+s][r] = o < 3 ? 1 : o; } } for (var N = e, O = h.length - 1; O >= 0; O -= n ? 2 : 1) { var T = b(h[O], "A decorator", "be", !0), z = n ? h[O - 1] : void 0, A = {}, H = { kind: ["field", "accessor", "method", "getter", "setter", "class"][o], name: r, metadata: a, addInitializer: function (e, t) { if (e.v) throw new TypeError("attempted to call addInitializer after decoration was finished"); b(t, "An initializer", "be", !0), i.push(t); }.bind(null, A) }; if (w) c = T.call(z, N, H), A.v = 1, b(c, "class decorators", "return") && (N = c);else if (H.static = s, H.private = f, c = H.access = { has: f ? p.bind() : function (e) { return r in e; } }, j || (c.get = f ? E ? function (e) { return d(e), P.value; } : I("get", 0, d) : function (e) { return e[r]; }), E || S || (c.set = f ? I("set", 0, d) : function (e, t) { e[r] = t; }), N = T.call(z, D ? { get: P.get, set: P.set } : P[F], H), A.v = 1, D) { if ("object" == typeof N && N) (c = b(N.get, "accessor.get")) && (P.get = c), (c = b(N.set, "accessor.set")) && (P.set = c), (c = b(N.init, "accessor.init")) && k.unshift(c);else if (void 0 !== N) throw new TypeError("accessor decorators must return an object with get, set, or init properties or undefined"); } else b(N, (l ? "field" : "method") + " decorators", "return") && (l ? k.unshift(N) : P[F] = N); } return o < 2 && u.push(g(k, s, 1), g(i, s, 0)), l || w || (f ? D ? u.splice(-1, 0, I("get", s), I("set", s)) : u.push(E ? P[F] : b.call.bind(P[F])) : m(e, r, P)), N; } function w(e) { return m(e, d, { configurable: !0, enumerable: !0, value: a }); } return void 0 !== i && (a = i[d]), a = h(null == a ? null : a), f = [], l = function (e) { e && f.push(g(e)); }, p = function (t, r) { for (var i = 0; i < n.length; i++) { var a = n[i], c = a[1], l = 7 & c; if ((8 & c) == t && !l == r) { var p = a[2], d = !!a[3], m = 16 & c; applyDec(t ? e : e.prototype, a, m, d ? "#" + p : _toPropertyKey(p), l, l < 2 ? [] : t ? s = s || [] : u = u || [], f, !!t, d, r, t && d ? function (t) { return _checkInRHS(t) === e; } : o); } } }, p(8, 0), p(0, 0), p(8, 1), p(0, 1), l(u), l(s), c = f, v || w(e), { e: c, get c() { var n = []; return v && [w(e = applyDec(e, [t], r, e.name, 5, n)), g(n, 1)]; } }; }
  function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
  function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
  function _setFunctionName(e, t, n) { "symbol" == typeof t && (t = (t = t.description) ? "[" + t + "]" : ""); try { Object.defineProperty(e, "name", { configurable: !0, value: n ? n + " " + t : t }); } catch (e) {} return e; }
  function _checkInRHS(e) { if (Object(e) !== e) throw TypeError("right-hand side of 'in' should be an object, got " + (null !== e ? typeof e : "null")); return e; }
  class CalendarPage extends _view.default {
    template = 'crm:calendar/calendar-page';

    //el = '#main'

    fullCalendarModeList = ['month', 'agendaWeek', 'agendaDay', 'basicWeek', 'basicDay', 'listWeek'];
    events = {
      /** @this CalendarPage */
      'click [data-action="createCustomView"]': function () {
        this.createCustomView();
      },
      /** @this CalendarPage */
      'click [data-action="editCustomView"]': function () {
        this.editCustomView();
      }
    };

    /**
     * @private
     * @type {ShortcutManager}
     */
    shortcutManager = _init_shortcutManager(this);

    /**
     * @private
     * @type {DebounceHelper|null}
     */
    webSocketDebounceHelper = (_init_extra_shortcutManager(this), null);

    /**
     * @private
     * @type {number}
     */
    webSocketDebounceInterval = 500;

    /**
     * @private
     * @type {number}
     */
    webSocketBlockInterval = 1000;

    /**
     * @private
     * @type {WebSocketManager}
     */
    webSocketManager = _init_webSocketManager(this);

    /**
     * A shortcut-key => action map.
     *
     * @protected
     * @type {?Object.<string, function (KeyboardEvent): void>}
     */
    shortcutKeys = (_init_extra_webSocketManager(this), {
      /** @this CalendarPage */
      'Home': function (e) {
        this.handleShortcutKeyHome(e);
      },
      /** @this CalendarPage */
      'Numpad7': function (e) {
        this.handleShortcutKeyHome(e);
      },
      /** @this CalendarPage */
      'Numpad4': function (e) {
        this.handleShortcutKeyArrowLeft(e);
      },
      /** @this CalendarPage */
      'Numpad6': function (e) {
        this.handleShortcutKeyArrowRight(e);
      },
      /** @this CalendarPage */
      'ArrowLeft': function (e) {
        this.handleShortcutKeyArrowLeft(e);
      },
      /** @this CalendarPage */
      'ArrowRight': function (e) {
        this.handleShortcutKeyArrowRight(e);
      },
      /** @this CalendarPage */
      'Control+ArrowLeft': function (e) {
        this.handleShortcutKeyArrowLeft(e);
      },
      /** @this CalendarPage */
      'Control+ArrowRight': function (e) {
        this.handleShortcutKeyArrowRight(e);
      },
      /** @this CalendarPage */
      'Minus': function (e) {
        this.handleShortcutKeyMinus(e);
      },
      /** @this CalendarPage */
      'Equal': function (e) {
        this.handleShortcutKeyPlus(e);
      },
      /** @this CalendarPage */
      'NumpadSubtract': function (e) {
        this.handleShortcutKeyMinus(e);
      },
      /** @this CalendarPage */
      'NumpadAdd': function (e) {
        this.handleShortcutKeyPlus(e);
      },
      /** @this CalendarPage */
      'Digit1': function (e) {
        this.handleShortcutKeyDigit(e, 1);
      },
      /** @this CalendarPage */
      'Digit2': function (e) {
        this.handleShortcutKeyDigit(e, 2);
      },
      /** @this CalendarPage */
      'Digit3': function (e) {
        this.handleShortcutKeyDigit(e, 3);
      },
      /** @this CalendarPage */
      'Digit4': function (e) {
        this.handleShortcutKeyDigit(e, 4);
      },
      /** @this CalendarPage */
      'Digit5': function (e) {
        this.handleShortcutKeyDigit(e, 5);
      },
      /** @this CalendarPage */
      'Digit6': function (e) {
        this.handleShortcutKeyDigit(e, 6);
      },
      /** @this CalendarPage */
      'Control+Space': function (e) {
        this.handleShortcutKeyControlSpace(e);
      }
    });

    /**
     * @param {{
     *     userId?: string,
     *     userName?: string|null,
     *     mode?: string|null,
     *     date?: string|null,
     * }} options
     */
    constructor(options) {
      super(options);
      this.options = options;
    }
    setup() {
      this.mode = this.mode || this.options.mode || null;
      this.date = this.date || this.options.date || null;
      if (!this.mode) {
        this.mode = this.getStorage().get('state', 'calendarMode') || null;
        if (this.mode && this.mode.indexOf('view-') === 0) {
          const viewId = this.mode.slice(5);
          const calendarViewDataList = this.getPreferences().get('calendarViewDataList') || [];
          let isFound = false;
          calendarViewDataList.forEach(item => {
            if (item.id === viewId) {
              isFound = true;
            }
          });
          if (!isFound) {
            this.mode = null;
          }
          if (this.options.userId) {
            this.mode = null;
          }
        }
      }
      this.shortcutManager.add(this, this.shortcutKeys);
      this.on('remove', () => {
        this.shortcutManager.remove(this);
      });
      if (!this.mode || ~this.fullCalendarModeList.indexOf(this.mode) || this.mode.indexOf('view-') === 0) {
        this.setupCalendar();
      } else if (this.mode === 'timeline') {
        this.setupTimeline();
      }
      this.initWebSocket();
    }

    /**
     * @private
     */
    initWebSocket() {
      if (this.options.userId && this.getUser().id !== this.options.userId) {
        return;
      }
      this.webSocketDebounceHelper = new _debounce.default({
        interval: this.webSocketDebounceInterval,
        blockInterval: this.webSocketBlockInterval,
        handler: () => this.handleWebSocketUpdate()
      });
      if (!this.webSocketManager.isEnabled()) {
        const testHandler = () => this.webSocketDebounceHelper.process();
        this.on('remove', () => window.removeEventListener('calendar-update', testHandler));

        // For testing purpose.
        window.addEventListener('calendar-update', testHandler);
        return;
      }
      this.webSocketManager.subscribe('calendarUpdate', () => this.webSocketDebounceHelper.process());
      this.on('remove', () => this.webSocketManager.unsubscribe('calendarUpdate'));
    }

    /**
     * @private
     */
    handleWebSocketUpdate() {
      var _this$getCalendarView;
      (_this$getCalendarView = this.getCalendarView()) === null || _this$getCalendarView === void 0 || _this$getCalendarView.actionRefresh({
        suppressLoadingAlert: true
      });
    }

    /**
     * @private
     */
    onSave() {
      if (!this.webSocketDebounceHelper) {
        return;
      }
      this.webSocketDebounceHelper.block();
    }
    afterRender() {
      this.$el.focus();
    }
    updateUrl(trigger) {
      let url = '#Calendar/show';
      if (this.mode || this.date) {
        url += '/';
      }
      if (this.mode) {
        url += 'mode=' + this.mode;
      }
      if (this.date) {
        url += '&date=' + this.date;
      }
      if (this.options.userId) {
        url += '&userId=' + this.options.userId;
        if (this.options.userName) {
          url += '&userName=' + encodeURIComponent(this.options.userName);
        }
      }
      this.getRouter().navigate(url, {
        trigger: trigger
      });
    }

    /**
     * @private
     */
    setupCalendar() {
      const viewName = this.getMetadata().get(['clientDefs', 'Calendar', 'calendarView']) || 'crm:views/calendar/calendar';
      this.createView('calendar', viewName, {
        date: this.date,
        userId: this.options.userId,
        userName: this.options.userName,
        mode: this.mode,
        fullSelector: '#main > .calendar-container',
        onSave: () => this.onSave()
      }, view => {
        let initial = true;
        this.listenTo(view, 'view', (date, mode) => {
          this.date = date;
          this.mode = mode;
          if (!initial) {
            this.updateUrl();
          }
          initial = false;
        });
        this.listenTo(view, 'change:mode', (mode, refresh) => {
          this.mode = mode;
          if (!this.options.userId) {
            this.getStorage().set('state', 'calendarMode', mode);
          }
          if (refresh) {
            this.updateUrl(true);
            return;
          }
          if (!~this.fullCalendarModeList.indexOf(mode)) {
            this.updateUrl(true);
          }
          this.$el.focus();
        });
      });
    }

    /**
     * @private
     */
    setupTimeline() {
      const viewName = this.getMetadata().get(['clientDefs', 'Calendar', 'timelineView']) || 'crm:views/calendar/timeline';
      this.createView('calendar', viewName, {
        date: this.date,
        userId: this.options.userId,
        userName: this.options.userName,
        fullSelector: '#main > .calendar-container',
        onSave: () => this.onSave()
      }, view => {
        let initial = true;
        this.listenTo(view, 'view', (date, mode) => {
          this.date = date;
          this.mode = mode;
          if (!initial) {
            this.updateUrl();
          }
          initial = false;
        });
        this.listenTo(view, 'change:mode', mode => {
          this.mode = mode;
          if (!this.options.userId) {
            this.getStorage().set('state', 'calendarMode', mode);
          }
          this.updateUrl(true);
        });
      });
    }
    updatePageTitle() {
      this.setPageTitle(this.translate('Calendar', 'scopeNames'));
    }
    async createCustomView() {
      const view = new _editView.default({
        afterSave: data => {
          this.mode = `view-${data.id}`;
          this.date = null;
          this.updateUrl(true);
        }
      });
      await this.assignView('modal', view);
      await view.render();
    }
    async editCustomView() {
      const viewId = this.getCalendarView().viewId;
      if (!viewId) {
        return;
      }
      const view = new _editView.default({
        id: viewId,
        afterSave: () => {
          this.getCalendarView().setupMode();
          this.getCalendarView().reRender();
        },
        afterRemove: () => {
          this.mode = null;
          this.date = null;
          this.updateUrl(true);
        }
      });
      await this.assignView('modal', view);
      await view.render();
    }

    /**
     * @private
     * @return {import('./calendar').default|import('./timeline').default}
     */
    getCalendarView() {
      return this.getView('calendar');
    }

    /**
     * @private
     * @param {KeyboardEvent} e
     */
    handleShortcutKeyHome(e) {
      if (_utils.default.isKeyEventInTextInput(e)) {
        return;
      }
      e.preventDefault();
      this.getCalendarView().actionToday();
    }

    /**
     * @private
     * @param {KeyboardEvent} e
     */
    handleShortcutKeyArrowLeft(e) {
      if (_utils.default.isKeyEventInTextInput(e)) {
        return;
      }
      if (e.target instanceof HTMLElement && e.target.parentElement instanceof HTMLLIElement) {
        return;
      }
      e.preventDefault();
      this.getCalendarView().actionPrevious();
    }

    /**
     * @private
     * @param {KeyboardEvent} e
     */
    handleShortcutKeyArrowRight(e) {
      if (_utils.default.isKeyEventInTextInput(e)) {
        return;
      }
      if (e.target instanceof HTMLElement && e.target.parentElement instanceof HTMLLIElement) {
        return;
      }
      e.preventDefault();
      this.getCalendarView().actionNext();
    }

    /**
     * @private
     * @param {KeyboardEvent} e
     */
    handleShortcutKeyMinus(e) {
      if (_utils.default.isKeyEventInTextInput(e)) {
        return;
      }
      if (!this.getCalendarView().actionZoomOut) {
        return;
      }
      e.preventDefault();
      this.getCalendarView().actionZoomOut();
    }

    /**
     * @private
     * @param {KeyboardEvent} e
     */
    handleShortcutKeyPlus(e) {
      if (_utils.default.isKeyEventInTextInput(e)) {
        return;
      }
      if (!this.getCalendarView().actionZoomIn) {
        return;
      }
      e.preventDefault();
      this.getCalendarView().actionZoomIn();
    }

    /**
     * @private
     * @param {KeyboardEvent} e
     * @param {Number} digit
     */
    handleShortcutKeyDigit(e, digit) {
      if (_utils.default.isKeyEventInTextInput(e)) {
        return;
      }
      const modeList = this.getCalendarView().hasView('modeButtons') ? this.getCalendarView().getModeButtonsView().getModeDataList(true).map(item => item.mode) : this.getCalendarView().modeList;
      const mode = modeList[digit - 1];
      if (!mode) {
        return;
      }
      e.preventDefault();
      if (mode === this.mode) {
        this.getCalendarView().actionRefresh();
        return;
      }
      this.getCalendarView().selectMode(mode);
    }

    /**
     * @private
     * @param {KeyboardEvent} e
     */
    handleShortcutKeyControlSpace(e) {
      if (_utils.default.isKeyEventInTextInput(e)) {
        return;
      }
      if (!this.getCalendarView().createEvent) {
        return;
      }
      e.preventDefault();
      this.getCalendarView().createEvent();
    }
    static #_ = _staticBlock = () => [_init_shortcutManager, _init_extra_shortcutManager, _init_webSocketManager, _init_extra_webSocketManager] = _applyDecs(this, [], [[(0, _di.inject)(_shortcutManager.default), 0, "shortcutManager"], [(0, _di.inject)(_webSocketManager.default), 0, "webSocketManager"]], 0, void 0, _view.default).e;
  }

  // noinspection JSUnusedGlobalSymbols
  _staticBlock();
  var _default = _exports.default = CalendarPage;
});

define("modules/crm/views/calendar/modals/shared-options", ["exports", "views/modal", "model", "views/record/edit-for-modal", "crm:views/calendar/fields/users"], function (_exports, _modal, _model, _editForModal, _users) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _modal = _interopRequireDefault(_modal);
  _model = _interopRequireDefault(_model);
  _editForModal = _interopRequireDefault(_editForModal);
  _users = _interopRequireDefault(_users);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class TimelineSharedOptionsModalView extends _modal.default {
    className = 'dialog dialog-record';
    templateContent = `
        <div class="record-container no-side-margin">{{{record}}}</div>
    `;

    /**
     * @private
     * @type {EditForModalRecordView}
     */
    recordView;

    /**
     *
     * @param {{
     *     users: {id: string, name: string}[],
     *     onApply: function({
     *         users: {id: string, name: string}[],
     *     }),
     * }} options
     */
    constructor(options) {
      super(options);
      this.options = options;
    }
    setup() {
      this.buttonList = [{
        name: 'save',
        label: 'Save',
        style: 'primary',
        onClick: () => this.actionSave()
      }, {
        name: 'cancel',
        label: 'Cancel',
        onClick: () => this.actionClose()
      }];
      this.headerText = this.translate('timeline', 'modes', 'Calendar') + ' · ' + this.translate('Shared Mode Options', 'labels', 'Calendar');
      const users = this.options.users;
      const userIdList = [];
      const userNames = {};
      users.forEach(item => {
        userIdList.push(item.id);
        userNames[item.id] = item.name;
      });
      this.model = new _model.default({
        usersIds: userIdList,
        usersNames: userNames
      });
      this.recordView = new _editForModal.default({
        model: this.model,
        detailLayout: [{
          rows: [[{
            view: new _users.default({
              name: 'users'
            })
          }, false]]
        }]
      });
      this.assignView('record', this.recordView);
    }

    /**
     * @private
     */
    actionSave() {
      const data = this.recordView.processFetch();
      if (this.recordView.validate()) {
        return;
      }

      /** @type {{id: string, name: string}[]} */
      const users = [];
      const userIds = this.model.attributes.usersIds || [];
      userIds.forEach(id => {
        users.push({
          id: id,
          name: (data.usersNames || {})[id] || id
        });
      });
      this.options.onApply({
        users: users
      });
      this.close();
    }
  }
  _exports.default = TimelineSharedOptionsModalView;
});

define("modules/crm/views/calendar/modals/edit", ["exports", "views/modals/edit"], function (_exports, _edit) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _edit = _interopRequireDefault(_edit);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class CalenderEditModalView extends _edit.default {
    template = 'crm:calendar/modals/edit';
    scopeList = ['Meeting', 'Call', 'Task'];
    data() {
      return {
        scopeList: this.scopeList,
        scope: this.scope,
        isNew: !this.id
      };
    }
    additionalEvents = {
      /** @this CalenderEditModalView */
      'change .scope-switcher input[name="scope"]': function () {
        Espo.Ui.notifyWait();
        const prevScope = this.scope;
        const scope = $('.scope-switcher input[name="scope"]:checked').val();
        this.scope = scope;
        this.getModelFactory().create(this.scope, model => {
          model.populateDefaults();
          let attributes = this.getRecordView().fetch();
          attributes = {
            ...attributes,
            ...this.getRecordView().model.getClonedAttributes()
          };
          this.filterAttributesForEntityType(attributes, scope, prevScope);
          model.set(attributes);
          this.model = model;
          this.createRecordView(model, view => {
            view.render();
            view.notify(false);
          });
          this.handleAccess(model);
        });
      }
    };

    /**
     * @param {Record} attributes
     * @param {string} entityType
     * @param {string} previousEntityType
     */
    filterAttributesForEntityType(attributes, entityType, previousEntityType) {
      if (entityType === 'Task' || previousEntityType === 'Task') {
        delete attributes.reminders;
      }
      this.getHelper().fieldManager.getEntityTypeFieldList(entityType, {
        type: 'enum'
      }).forEach(field => {
        if (!(field in attributes)) {
          return;
        }
        const options = this.getMetadata().get(['entityDefs', entityType, 'fields', field, 'options']) || [];
        const value = attributes[field];
        if (!~options.indexOf(value)) {
          delete attributes[field];
        }
      });
    }
    createRecordView(model, callback) {
      if (!this.id && !this.dateIsChanged) {
        if (this.options.dateStart && this.options.dateEnd) {
          this.model.set('dateStart', this.options.dateStart);
          this.model.set('dateEnd', this.options.dateEnd);
        }
        if (this.options.allDay) {
          const allDayScopeList = this.getMetadata().get('clientDefs.Calendar.allDayScopeList') || [];
          if (~allDayScopeList.indexOf(this.scope)) {
            this.model.set('dateStart', null);
            this.model.set('dateEnd', null);
            this.model.set('dateStartDate', null);
            this.model.set('dateEndDate', this.options.dateEndDate);
            if (this.options.dateEndDate !== this.options.dateStartDate) {
              this.model.set('dateStartDate', this.options.dateStartDate);
            }
          } else if (this.getMetadata().get(['entityDefs', this.scope, 'fields', 'dateStartDate'])) {
            this.model.set('dateStart', null);
            this.model.set('dateEnd', null);
            this.model.set('dateStartDate', this.options.dateStartDate);
            this.model.set('dateEndDate', this.options.dateEndDate);
            this.model.set('isAllDay', true);
          } else {
            this.model.set('isAllDay', false);
            this.model.set('dateStartDate', null);
            this.model.set('dateEndDate', null);
          }
        }
      }
      this.listenTo(this.model, 'change:dateStart', (m, value, o) => {
        if (o.ui) {
          this.dateIsChanged = true;
        }
      });
      this.listenTo(this.model, 'change:dateEnd', (m, value, o) => {
        if (o.ui || o.updatedByDuration) {
          this.dateIsChanged = true;
        }
      });
      super.createRecordView(model, callback);
    }
    handleAccess(model) {
      if (this.id && !this.getAcl().checkModel(model, 'edit') || !this.id && !this.getAcl().checkModel(model, 'create')) {
        this.hideButton('save');
        this.hideButton('fullForm');
        this.$el.find('button[data-name="save"]').addClass('hidden');
        this.$el.find('button[data-name="fullForm"]').addClass('hidden');
      } else {
        this.showButton('save');
        this.showButton('fullForm');
      }
      if (!this.getAcl().checkModel(model, 'delete')) {
        this.hideButton('remove');
      } else {
        this.showButton('remove');
      }
    }
    afterRender() {
      super.afterRender();
      if (this.hasView('edit')) {
        const model = this.getView('edit').model;
        if (model) {
          this.handleAccess(model);
        }
      }
    }
    setup() {
      this.events = {
        ...this.additionalEvents,
        ...this.events
      };
      this.scopeList = Espo.Utils.clone(this.options.scopeList || this.scopeList);
      this.enabledScopeList = this.options.enabledScopeList || this.scopeList;
      if (!this.options.id && !this.options.scope) {
        const scopeList = [];
        this.scopeList.forEach(scope => {
          if (this.getAcl().check(scope, 'create')) {
            if (~this.enabledScopeList.indexOf(scope)) {
              scopeList.push(scope);
            }
          }
        });
        this.scopeList = scopeList;
        const calendarDefaultEntity = scopeList[0];
        if (calendarDefaultEntity && ~this.scopeList.indexOf(calendarDefaultEntity)) {
          this.options.scope = calendarDefaultEntity;
        } else {
          this.options.scope = this.scopeList[0] || null;
        }
        if (this.scopeList.length === 0) {
          this.remove();
          return;
        }
      }
      super.setup();
      if (!this.id) {
        this.$header = $('<a>').attr('title', this.translate('Full Form')).attr('role', 'button').attr('data-action', 'fullForm').addClass('action').text(this.translate('Create', 'labels', 'Calendar'));
      }
      if (this.id) {
        this.buttonList.splice(1, 0, {
          name: 'remove',
          text: this.translate('Remove'),
          onClick: () => this.actionRemove()
        });
      }
      this.once('after:save', () => {
        this.$el.find('.scope-switcher').remove();
      });
    }
    actionRemove() {
      const model = this.getView('edit').model;
      this.confirm(this.translate('removeRecordConfirmation', 'messages'), () => {
        const $buttons = this.dialog.$el.find('.modal-footer button');
        $buttons.addClass('disabled');
        model.destroy().then(() => {
          this.trigger('after:delete', model);
          this.dialog.close();
        }).catch(() => {
          $buttons.removeClass('disabled');
        });
      });
    }
  }
  var _default = _exports.default = CalenderEditModalView;
});

define("modules/crm/views/admin/entity-manager/fields/status-list", ["exports", "views/fields/multi-enum"], function (_exports, _multiEnum) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _multiEnum = _interopRequireDefault(_multiEnum);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _multiEnum.default {
    setupOptions() {
      const entityType = this.model.get('name');
      const options = this.getMetadata().get(['entityDefs', entityType, 'fields', 'status', 'options']) || [];
      this.params.options = [...options];
      this.params.translation = `${entityType}.options.status`;
    }
  }
  _exports.default = _default;
});

define("modules/crm/views/activities/list", ["exports", "views/list-related"], function (_exports, _listRelated) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _listRelated = _interopRequireDefault(_listRelated);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class ActivitiesListView extends _listRelated.default {
    createButton = false;
    unlinkDisabled = true;
    filtersDisabled = true;
    allResultDisabled = true;
    setup() {
      this.rowActionsView = 'views/record/row-actions/default';
      super.setup();
      this.type = this.options.type;
    }
    getHeader() {
      const name = this.model.get('name') || this.model.id;
      const recordUrl = `#${this.scope}/view/${this.model.id}`;
      const $name = $('<a>').attr('href', recordUrl).addClass('font-size-flexible title').text(name).css('user-select', 'none');
      if (this.model.get('deleted')) {
        $name.css('text-decoration', 'line-through');
      }
      const headerIconHtml = this.getHelper().getScopeColorIconHtml(this.foreignScope);
      const scopeLabel = this.getLanguage().translate(this.scope, 'scopeNamesPlural');
      let $root = $('<span>').text(scopeLabel);
      if (!this.rootLinkDisabled) {
        $root = $('<span>').append($('<a>').attr('href', '#' + this.scope).addClass('action').attr('data-action', 'navigateToRoot').text(scopeLabel));
      }
      $root.css('user-select', 'none');
      if (headerIconHtml) {
        $root.prepend(headerIconHtml);
      }
      const linkLabel = this.type === 'history' ? this.translate('History') : this.translate('Activities');
      const $link = $('<span>').text(linkLabel);
      $link.css('user-select', 'none');
      const $target = $('<span>').text(this.translate(this.foreignScope, 'scopeNamesPlural'));
      $target.css('user-select', 'none').css('cursor', 'pointer').attr('data-action', 'fullRefresh').attr('title', this.translate('clickToRefresh', 'messages'));
      return this.buildHeaderHtml([$root, $name, $link, $target]);
    }

    /**
     * @inheritDoc
     */
    updatePageTitle() {
      this.setPageTitle(this.translate(this.foreignScope, 'scopeNamesPlural'));
    }
  }
  _exports.default = ActivitiesListView;
});

define("modules/crm/views/account/detail", ["exports", "views/detail"], function (_exports, _detail) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _detail = _interopRequireDefault(_detail);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  /** Left for bc. */
  class _default extends _detail.default {}
  _exports.default = _default;
});

define("modules/crm/views/account/fields/shipping-address", ["exports", "views/fields/address"], function (_exports, _address) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _address = _interopRequireDefault(_address);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class _default extends _address.default {
    copyFrom = 'billingAddress';
    setup() {
      super.setup();
      this.addActionHandler('copyFromBilling', () => this.copy());
      this.attributePartList = this.getMetadata().get(['fields', 'address', 'actualFields']) || [];
      this.allAddressAttributeList = [];
      this.attributePartList.forEach(part => {
        this.allAddressAttributeList.push(this.copyFrom + Espo.Utils.upperCaseFirst(part));
        this.allAddressAttributeList.push(this.name + Espo.Utils.upperCaseFirst(part));
      });
      this.listenTo(this.model, 'change', () => {
        let isChanged = false;
        for (const attribute of this.allAddressAttributeList) {
          if (this.model.hasChanged(attribute)) {
            isChanged = true;
            break;
          }
        }
        if (!isChanged) {
          return;
        }
        if (!this.isEditMode() || !this.isRendered() || !this.copyButtonElement) {
          return;
        }
        if (this.toShowCopyButton()) {
          this.copyButtonElement.classList.remove('hidden');
        } else {
          this.copyButtonElement.classList.add('hidden');
        }
      });
    }
    afterRender() {
      super.afterRender();
      if (this.mode === this.MODE_EDIT && this.element) {
        const label = this.translate('Copy Billing', 'labels', 'Account');
        const button = this.copyButtonElement = document.createElement('button');
        button.classList.add('btn', 'btn-default', 'btn-sm', 'action');
        button.textContent = label;
        button.setAttribute('data-action', 'copyFromBilling');
        if (!this.toShowCopyButton()) {
          button.classList.add('hidden');
        }
        this.element.append(button);
      }
    }

    /**
     * @private
     */
    copy() {
      const fieldFrom = this.copyFrom;
      Object.keys(this.getMetadata().get('fields.address.fields') || {}).forEach(attr => {
        const destField = this.name + Espo.Utils.upperCaseFirst(attr);
        const sourceField = fieldFrom + Espo.Utils.upperCaseFirst(attr);
        this.model.set(destField, this.model.get(sourceField));
      });
    }

    /**
     * @private
     * @return {boolean}
     */
    toShowCopyButton() {
      let billingIsNotEmpty = false;
      let shippingIsNotEmpty = false;
      this.attributePartList.forEach(part => {
        const attribute1 = this.copyFrom + Espo.Utils.upperCaseFirst(part);
        if (this.model.get(attribute1)) {
          billingIsNotEmpty = true;
        }
        const attribute2 = this.name + Espo.Utils.upperCaseFirst(part);
        if (this.model.get(attribute2)) {
          shippingIsNotEmpty = true;
        }
      });
      return billingIsNotEmpty && !shippingIsNotEmpty;
    }
  }
  _exports.default = _default;
});

define("modules/crm/view-setup-handlers/document/record-list-drag-n-drop", ["exports", "underscore", "bullbone"], function (_exports, _underscore, _bullbone) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _underscore = _interopRequireDefault(_underscore);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  const Handler = function (view) {
    this.view = view;
  };
  _underscore.default.extend(Handler.prototype, {
    process: function () {
      this.listenTo(this.view, 'after:render', () => this.initDragDrop());
      this.listenTo(this.view, 'remove', () => this.disable());
    },
    disable: function () {
      const $el = this.view.$el.parent();
      /** @type {Element} */
      const el = $el.get(0);
      $el.off('drop');
      if (!el) {
        return;
      }
      if (!this.onDragoverBind) {
        return;
      }
      el.removeEventListener('dragover', this.onDragoverBind);
      el.removeEventListener('dragenter', this.onDragenterBind);
      el.removeEventListener('dragleave', this.onDragleaveBind);
    },
    initDragDrop: function () {
      this.disable();
      const $el = this.view.$el.parent();
      const el = $el.get(0);
      $el.on('drop', e => {
        e.preventDefault();
        e.stopPropagation();
        e = e.originalEvent;
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length === 1 && this.dropEntered) {
          this.removeDrop();
          this.create(e.dataTransfer.files[0]);
          return;
        }
        this.removeDrop($el);
      });
      this.dropEntered = false;
      this.onDragoverBind = this.onDragover.bind(this);
      this.onDragenterBind = this.onDragenter.bind(this);
      this.onDragleaveBind = this.onDragleave.bind(this);
      el.addEventListener('dragover', this.onDragoverBind);
      el.addEventListener('dragenter', this.onDragenterBind);
      el.addEventListener('dragleave', this.onDragleaveBind);
    },
    renderDrop: function () {
      this.dropEntered = true;
      const $backdrop = $('<div class="dd-backdrop">').css('pointer-events', 'none').append('<span class="fas fa-paperclip"></span>').append(' ').append($('<span>').text(this.view.getLanguage().translate('Create Document', 'labels', 'Document')));
      this.view.$el.append($backdrop);
    },
    removeDrop: function () {
      this.view.$el.find('> .dd-backdrop').remove();
      this.dropEntered = false;
    },
    create: function (file) {
      this.view.actionQuickCreate().then(view => {
        const fileView = view.getRecordView().getFieldView('file');
        if (!fileView) {
          const msg = "No 'file' field on the layout.";
          Espo.Ui.error(msg);
          console.error(msg);
          return;
        }
        if (fileView.isRendered()) {
          fileView.uploadFile(file);
          return;
        }
        this.listenToOnce(fileView, 'after:render', () => {
          fileView.uploadFile(file);
        });
      });
    },
    /**
     * @param {DragEvent} e
     */
    onDragover: function (e) {
      e.preventDefault();
    },
    /**
     * @param {DragEvent} e
     */
    onDragenter: function (e) {
      e.preventDefault();
      if (!e.dataTransfer.types || !e.dataTransfer.types.length) {
        return;
      }
      if (!~e.dataTransfer.types.indexOf('Files')) {
        return;
      }
      if (!this.dropEntered) {
        this.renderDrop();
      }
    },
    /**
     * @param {DragEvent} e
     */
    onDragleave: function (e) {
      e.preventDefault();
      if (!this.dropEntered) {
        return;
      }
      let fromElement = e.fromElement || e.relatedTarget;
      if (fromElement && $.contains(this.view.$el.parent().get(0), fromElement)) {
        return;
      }
      if (fromElement && fromElement.parentNode && fromElement.parentNode.toString() === '[object ShadowRoot]') {
        return;
      }
      this.removeDrop();
    }
  });
  Object.assign(Handler.prototype, _bullbone.Events);

  // noinspection JSUnusedGlobalSymbols
  var _default = _exports.default = Handler;
});

define("modules/crm/handlers/task/reminders-handler", ["exports", "bullbone"], function (_exports, _bullbone) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  /**
   * @mixes Bull.Events
   */
  class RemindersHandler {
    /**
     * @param {import('views/record/detail').default} view
     */
    constructor(view) {
      this.view = view;
      this.model = view.model;
      this.user = this.view.getUser();
      this.ignoreStatusList = [...(this.view.getMetadata().get(['scopes', this.view.entityType, 'completedStatusList']) || []), ...(this.view.getMetadata().get(['scopes', this.view.entityType, 'canceledStatusList']) || [])];
    }
    process() {
      this.control();
      this.listenTo(this.model, 'change', () => {
        if (!this.model.hasChanged('assignedUserId') && !this.model.hasChanged('assignedUsersIds') && !this.model.hasChanged('dateEnd') && !this.model.hasChanged('dateEndDate') && !this.model.hasChanged('status')) {
          return;
        }
        this.control();
      });
    }
    control() {
      if (!this.model.attributes.dateEnd && !this.model.attributes.dateEndDate) {
        this.view.hideField('reminders');
        return;
      }

      /** @type {string[]} */
      const assignedUsersIds = this.model.attributes.assignedUsersIds || [];
      if (!this.ignoreStatusList.includes(this.model.attributes.status) && (this.model.attributes.assignedUserId === this.user.id || assignedUsersIds.includes(this.user.id))) {
        this.view.showField('reminders');
        return;
      }
      this.view.hideField('reminders');
    }
  }
  Object.assign(RemindersHandler.prototype, _bullbone.Events);

  // noinspection JSUnusedGlobalSymbols
  var _default = _exports.default = RemindersHandler;
});

define("modules/crm/handlers/task/menu", ["exports", "action-handler"], function (_exports, _actionHandler) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _actionHandler = _interopRequireDefault(_actionHandler);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class TaskMenuHandler extends _actionHandler.default {
    complete() {
      const model = this.view.model;
      model.save({
        status: 'Completed'
      }, {
        patch: true
      }).then(() => {
        Espo.Ui.success(this.view.getLanguage().translateOption('Completed', 'status', 'Task'));
      });
    }

    // noinspection JSUnusedGlobalSymbols
    isCompleteAvailable() {
      const status = this.view.model.get('status');
      const view = /** @type {module:views/detail} */this.view;
      if (view.getRecordView().isEditMode()) {
        return false;
      }

      /** @type {string[]} */
      const notActualStatuses = this.view.getMetadata().get('entityDefs.Task.fields.status.notActualOptions') || [];
      return !notActualStatuses.includes(status);
    }
  }
  var _default = _exports.default = TaskMenuHandler;
});

define("modules/crm/handlers/task/detail-actions", ["exports", "action-handler"], function (_exports, _actionHandler) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _actionHandler = _interopRequireDefault(_actionHandler);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class DetailActions extends _actionHandler.default {
    complete() {
      const model = this.view.model;
      model.save({
        status: 'Completed'
      }, {
        patch: true
      }).then(() => {
        Espo.Ui.success(this.view.getLanguage().translateOption('Completed', 'status', 'Task'));
      });
    }

    // noinspection JSUnusedGlobalSymbols
    isCompleteAvailable() {
      const status = this.view.model.get('status');

      /** @type {string[]} */
      const notActualStatuses = this.view.getMetadata().get('entityDefs.Task.fields.status.notActualOptions') || [];
      return !notActualStatuses.includes(status);
    }
  }
  var _default = _exports.default = DetailActions;
});

define("modules/crm/handlers/opportunity/defaults-preparator", ["exports", "handlers/model/defaults-preparator", "metadata", "di"], function (_exports, _defaultsPreparator, _metadata, _di) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _defaultsPreparator = _interopRequireDefault(_defaultsPreparator);
  _metadata = _interopRequireDefault(_metadata);
  var _staticBlock;
  let _init_metadata, _init_extra_metadata;
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  function _applyDecs(e, t, n, r, o, i) { var a, c, u, s, f, l, p, d = Symbol.metadata || Symbol.for("Symbol.metadata"), m = Object.defineProperty, h = Object.create, y = [h(null), h(null)], v = t.length; function g(t, n, r) { return function (o, i) { n && (i = o, o = e); for (var a = 0; a < t.length; a++) i = t[a].apply(o, r ? [i] : []); return r ? i : o; }; } function b(e, t, n, r) { if ("function" != typeof e && (r || void 0 !== e)) throw new TypeError(t + " must " + (n || "be") + " a function" + (r ? "" : " or undefined")); return e; } function applyDec(e, t, n, r, o, i, u, s, f, l, p) { function d(e) { if (!p(e)) throw new TypeError("Attempted to access private element on non-instance"); } var h = [].concat(t[0]), v = t[3], w = !u, D = 1 === o, S = 3 === o, j = 4 === o, E = 2 === o; function I(t, n, r) { return function (o, i) { return n && (i = o, o = e), r && r(o), P[t].call(o, i); }; } if (!w) { var P = {}, k = [], F = S ? "get" : j || D ? "set" : "value"; if (f ? (l || D ? P = { get: _setFunctionName(function () { return v(this); }, r, "get"), set: function (e) { t[4](this, e); } } : P[F] = v, l || _setFunctionName(P[F], r, E ? "" : F)) : l || (P = Object.getOwnPropertyDescriptor(e, r)), !l && !f) { if ((c = y[+s][r]) && 7 !== (c ^ o)) throw Error("Decorating two elements with the same name (" + P[F].name + ") is not supported yet"); y[+s][r] = o < 3 ? 1 : o; } } for (var N = e, O = h.length - 1; O >= 0; O -= n ? 2 : 1) { var T = b(h[O], "A decorator", "be", !0), z = n ? h[O - 1] : void 0, A = {}, H = { kind: ["field", "accessor", "method", "getter", "setter", "class"][o], name: r, metadata: a, addInitializer: function (e, t) { if (e.v) throw new TypeError("attempted to call addInitializer after decoration was finished"); b(t, "An initializer", "be", !0), i.push(t); }.bind(null, A) }; if (w) c = T.call(z, N, H), A.v = 1, b(c, "class decorators", "return") && (N = c);else if (H.static = s, H.private = f, c = H.access = { has: f ? p.bind() : function (e) { return r in e; } }, j || (c.get = f ? E ? function (e) { return d(e), P.value; } : I("get", 0, d) : function (e) { return e[r]; }), E || S || (c.set = f ? I("set", 0, d) : function (e, t) { e[r] = t; }), N = T.call(z, D ? { get: P.get, set: P.set } : P[F], H), A.v = 1, D) { if ("object" == typeof N && N) (c = b(N.get, "accessor.get")) && (P.get = c), (c = b(N.set, "accessor.set")) && (P.set = c), (c = b(N.init, "accessor.init")) && k.unshift(c);else if (void 0 !== N) throw new TypeError("accessor decorators must return an object with get, set, or init properties or undefined"); } else b(N, (l ? "field" : "method") + " decorators", "return") && (l ? k.unshift(N) : P[F] = N); } return o < 2 && u.push(g(k, s, 1), g(i, s, 0)), l || w || (f ? D ? u.splice(-1, 0, I("get", s), I("set", s)) : u.push(E ? P[F] : b.call.bind(P[F])) : m(e, r, P)), N; } function w(e) { return m(e, d, { configurable: !0, enumerable: !0, value: a }); } return void 0 !== i && (a = i[d]), a = h(null == a ? null : a), f = [], l = function (e) { e && f.push(g(e)); }, p = function (t, r) { for (var i = 0; i < n.length; i++) { var a = n[i], c = a[1], l = 7 & c; if ((8 & c) == t && !l == r) { var p = a[2], d = !!a[3], m = 16 & c; applyDec(t ? e : e.prototype, a, m, d ? "#" + p : _toPropertyKey(p), l, l < 2 ? [] : t ? s = s || [] : u = u || [], f, !!t, d, r, t && d ? function (t) { return _checkInRHS(t) === e; } : o); } } }, p(8, 0), p(0, 0), p(8, 1), p(0, 1), l(u), l(s), c = f, v || w(e), { e: c, get c() { var n = []; return v && [w(e = applyDec(e, [t], r, e.name, 5, n)), g(n, 1)]; } }; }
  function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
  function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
  function _setFunctionName(e, t, n) { "symbol" == typeof t && (t = (t = t.description) ? "[" + t + "]" : ""); try { Object.defineProperty(e, "name", { configurable: !0, value: n ? n + " " + t : t }); } catch (e) {} return e; }
  function _checkInRHS(e) { if (Object(e) !== e) throw TypeError("right-hand side of 'in' should be an object, got " + (null !== e ? typeof e : "null")); return e; }
  // noinspection JSUnusedGlobalSymbols
  class _Class extends _defaultsPreparator.default {
    constructor() {
      super(...arguments);
      _init_extra_metadata(this);
    }
    /**
     * @private
     * @type {Metadata}
     */
    metadata = _init_metadata(this);
    prepare(model) {
      const probabilityMap = this.metadata.get('entityDefs.Opportunity.fields.stage.probabilityMap') || {};
      const stage = model.attributes.stage;
      const attributes = {};
      if (stage in probabilityMap) {
        attributes.probability = probabilityMap[stage];
      }
      return Promise.resolve(attributes);
    }
    static #_ = _staticBlock = () => [_init_metadata, _init_extra_metadata] = _applyDecs(this, [], [[(0, _di.inject)(_metadata.default), 0, "metadata"]], 0, void 0, _defaultsPreparator.default).e;
  }
  _exports.default = _Class;
  _staticBlock();
});

define("modules/crm/handlers/opportunity/contacts-create", ["exports", "handlers/create-related"], function (_exports, _createRelated) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _createRelated = _interopRequireDefault(_createRelated);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class ContactsCreateHandler extends _createRelated.default {
    getAttributes(model) {
      const attributes = {};
      if (model.get('accountId')) {
        attributes['accountsIds'] = [model.get('accountId')];
      }
      return Promise.resolve(attributes);
    }
  }
  var _default = _exports.default = ContactsCreateHandler;
});

define("modules/crm/handlers/knowledge-base-article/send-in-email", ["exports", "handlers/row-action"], function (_exports, _rowAction) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _rowAction = _interopRequireDefault(_rowAction);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class SendInEmailHandler extends _rowAction.default {
    isAvailable(model, action) {
      return this.view.getAcl().checkScope('Email', 'create');
    }
    process(model, action) {
      const parentModel = this.view.getParentView().model;
      const modelFactory = this.view.getModelFactory();
      const collectionFactory = this.view.getCollectionFactory();
      Espo.Ui.notifyWait();
      model.fetch().then(() => {
        return new Promise(resolve => {
          if (parentModel.get('contactsIds') && parentModel.get('contactsIds').length) {
            collectionFactory.create('Contact', contactList => {
              const contactListFinal = [];
              contactList.url = 'Case/' + parentModel.id + '/contacts';
              contactList.fetch().then(() => {
                contactList.forEach(contact => {
                  if (contact.id === parentModel.get('contactId')) {
                    contactListFinal.unshift(contact);
                  } else {
                    contactListFinal.push(contact);
                  }
                });
                resolve(contactListFinal);
              });
            });
            return;
          }
          if (parentModel.get('accountId')) {
            modelFactory.create('Account', account => {
              account.id = parentModel.get('accountId');
              account.fetch().then(() => resolve([account]));
            });
            return;
          }
          if (parentModel.get('leadId')) {
            modelFactory.create('Lead', lead => {
              lead.id = parentModel.get('leadId');
              lead.fetch().then(() => resolve([lead]));
            });
            return;
          }
          resolve([]);
        });
      }).then(list => {
        const attributes = {
          parentType: 'Case',
          parentId: parentModel.id,
          parentName: parentModel.get('name'),
          name: '[#' + parentModel.get('number') + ']'
        };
        attributes.to = '';
        attributes.cc = '';
        attributes.nameHash = {};
        list.forEach((model, i) => {
          if (model.get('emailAddress')) {
            if (i === 0) {
              attributes.to += model.get('emailAddress') + ';';
            } else {
              attributes.cc += model.get('emailAddress') + ';';
            }
            attributes.nameHash[model.get('emailAddress')] = model.get('name');
          }
        });
        Espo.loader.require('crm:knowledge-base-helper', Helper => {
          const helper = new Helper(this.view.getLanguage());
          helper.getAttributesForEmail(model, attributes, attributes => {
            const viewName = this.view.getMetadata().get('clientDefs.Email.modalViews.compose') || 'views/modals/compose-email';
            this.view.createView('composeEmail', viewName, {
              attributes: attributes,
              selectTemplateDisabled: true,
              signatureDisabled: true
            }, view => {
              Espo.Ui.notify(false);
              view.render();
              this.view.listenToOnce(view, 'after:send', () => {
                parentModel.trigger('after:relate');
              });
            });
          });
        });
      }).catch(() => {
        Espo.Ui.notify(false);
      });
    }
  }
  var _default = _exports.default = SendInEmailHandler;
});

define("modules/crm/handlers/knowledge-base-article/move", ["exports", "handlers/row-action"], function (_exports, _rowAction) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _rowAction = _interopRequireDefault(_rowAction);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class MoveActionHandler extends _rowAction.default {
    isAvailable(model, action) {
      return model.collection && model.collection.orderBy === 'order' && model.collection.order === 'asc';
    }
    process(model, action) {
      if (action === 'moveToTop') {
        this.moveToTop(model);
        return;
      }
      if (action === 'moveToBottom') {
        this.moveToBottom(model);
        return;
      }
      if (action === 'moveUp') {
        this.moveUp(model);
        return;
      }
      if (action === 'moveDown') {
        this.moveDown(model);
      }
    }
    moveToTop(model) {
      const index = this.collection.indexOf(model);
      if (index === 0) {
        return;
      }
      Espo.Ui.notifyWait();
      Espo.Ajax.postRequest('KnowledgeBaseArticle/action/moveToTop', {
        id: model.id,
        whereGroup: this.collection.getWhere()
      }).then(() => {
        this.collection.fetch().then(() => Espo.Ui.notify(false));
      });
    }
    moveUp(model) {
      const index = this.collection.indexOf(model);
      if (index === 0) {
        return;
      }
      Espo.Ui.notifyWait();
      Espo.Ajax.postRequest('KnowledgeBaseArticle/action/moveUp', {
        id: model.id,
        whereGroup: this.collection.getWhere()
      }).then(() => {
        this.collection.fetch().then(() => Espo.Ui.notify(false));
      });
    }
    moveDown(model) {
      const index = this.collection.indexOf(model);
      if (index === this.collection.length - 1 && this.collection.length === this.collection.total) {
        return;
      }
      Espo.Ui.notifyWait();
      Espo.Ajax.postRequest('KnowledgeBaseArticle/action/moveDown', {
        id: model.id,
        whereGroup: this.collection.getWhere()
      }).then(() => {
        this.collection.fetch().then(() => Espo.Ui.notify(false));
      });
    }
    moveToBottom(model) {
      const index = this.collection.indexOf(model);
      if (index === this.collection.length - 1 && this.collection.length === this.collection.total) {
        return;
      }
      Espo.Ui.notifyWait();
      Espo.Ajax.postRequest('KnowledgeBaseArticle/action/moveToBottom', {
        id: model.id,
        whereGroup: this.collection.getWhere()
      }).then(() => {
        this.collection.fetch().then(() => Espo.Ui.notify(false));
      });
    }
  }
  var _default = _exports.default = MoveActionHandler;
});

define("modules/crm/handlers/event/reminders-handler", ["exports", "bullbone"], function (_exports, _bullbone) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  /**
   * @mixes Bull.Events
   */
  class RemindersHandler {
    /**
     * @param {import('views/record/detail').default} view
     */
    constructor(view) {
      this.view = view;
      /** @type {import('model').default} */
      this.model = view.model;
      /** @type {import('models/user').default} */
      this.user = this.view.getUser();
      this.ignoreStatusList = [...(this.view.getMetadata().get(['scopes', this.view.entityType, 'completedStatusList']) || []), ...(this.view.getMetadata().get(['scopes', this.view.entityType, 'canceledStatusList']) || [])];
    }
    process() {
      this.control();
      this.listenTo(this.model, 'change', () => {
        if (!this.model.hasChanged('assignedUserId') && !this.model.hasChanged('usersIds') && !this.model.hasChanged('assignedUsersIds') && !this.model.hasChanged('status')) {
          return;
        }
        this.control();
      });
    }
    control() {
      const usersIds = /** @type {string[]} */this.model.get('usersIds') || [];
      const assignedUsersIds = /** @type {string[]} */this.model.get('assignedUsersIds') || [];
      if (!this.ignoreStatusList.includes(this.model.get('status')) && (this.model.get('assignedUserId') === this.user.id || usersIds.includes(this.user.id) || assignedUsersIds.includes(this.user.id))) {
        this.view.showField('reminders');
        return;
      }
      this.view.hideField('reminders');
    }
  }
  Object.assign(RemindersHandler.prototype, _bullbone.Events);

  // noinspection JSUnusedGlobalSymbols
  var _default = _exports.default = RemindersHandler;
});

define("modules/crm/handlers/case/detail-actions", ["exports", "action-handler"], function (_exports, _actionHandler) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _actionHandler = _interopRequireDefault(_actionHandler);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class CaseDetailActionHandler extends _actionHandler.default {
    close() {
      const model = this.view.model;
      model.save({
        status: 'Closed'
      }, {
        patch: true
      }).then(() => {
        Espo.Ui.success(this.view.translate('Closed', 'labels', 'Case'));
      });
    }
    reject() {
      const model = this.view.model;
      model.save({
        status: 'Rejected'
      }, {
        patch: true
      }).then(() => {
        Espo.Ui.success(this.view.translate('Rejected', 'labels', 'Case'));
      });
    }

    // noinspection JSUnusedGlobalSymbols
    isCloseAvailable() {
      return this.isStatusAvailable('Closed');
    }

    // noinspection JSUnusedGlobalSymbols
    isRejectAvailable() {
      return this.isStatusAvailable('Rejected');
    }
    isStatusAvailable(status) {
      const model = this.view.model;
      const acl = this.view.getAcl();
      const metadata = this.view.getMetadata();

      /** @type {string[]} */
      const notActualStatuses = metadata.get('entityDefs.Case.fields.status.notActualOptions') || [];
      if (notActualStatuses.includes(model.get('status'))) {
        return false;
      }
      if (!acl.check(model, 'edit')) {
        return false;
      }
      if (!acl.checkField(model.entityType, 'status', 'edit')) {
        return false;
      }
      const statusList = metadata.get(['entityDefs', 'Case', 'fields', 'status', 'options']) || [];
      if (!statusList.includes(status)) {
        return false;
      }
      return true;
    }
  }
  var _default = _exports.default = CaseDetailActionHandler;
});

define("modules/crm/handlers/campaign/mass-emails-create", ["exports", "handlers/create-related"], function (_exports, _createRelated) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _createRelated = _interopRequireDefault(_createRelated);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class MassEmailsCreateHandler extends _createRelated.default {
    getAttributes(model) {
      return Promise.resolve({
        name: model.get('name') + ' ' + this.viewHelper.dateTime.getToday()
      });
    }
  }
  var _default = _exports.default = MassEmailsCreateHandler;
});

define("modules/crm/controllers/unsubscribe", ["exports", "controller"], function (_exports, _controller) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _controller = _interopRequireDefault(_controller);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class UnsubscribeController extends _controller.default {
    // noinspection JSUnusedGlobalSymbols
    actionUnsubscribe(data) {
      const viewName = data.view || 'crm:views/campaign/unsubscribe';
      this.entire(viewName, {
        actionData: data.actionData,
        template: data.template
      }, view => {
        view.render();
      });
    }
  }
  var _default = _exports.default = UnsubscribeController;
});

define("modules/crm/controllers/tracking-url", ["exports", "controller"], function (_exports, _controller) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _controller = _interopRequireDefault(_controller);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class TrackingUrlController extends _controller.default {
    // noinspection JSUnusedGlobalSymbols
    actionDisplayMessage(data) {
      const viewName = data.view || 'crm:views/campaign/tracking-url';
      this.entire(viewName, {
        message: data.message,
        template: data.template
      }, view => {
        view.render();
      });
    }
  }
  var _default = _exports.default = TrackingUrlController;
});

define("modules/crm/controllers/task", ["exports", "controllers/record"], function (_exports, _record) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _record = _interopRequireDefault(_record);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class TaskController extends _record.default {
    actionCreate(options) {
      const attributes = {
        ...options.attributes
      };
      if (options.emailId) {
        attributes.emailId = options.emailId;
        options.attributes = attributes;
      }
      super.actionCreate(options);
    }
  }
  _exports.default = TaskController;
});

define("modules/crm/controllers/lead", ["exports", "controllers/record"], function (_exports, _record) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _record = _interopRequireDefault(_record);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class LeadController extends _record.default {
    // noinspection JSUnusedGlobalSymbols
    actionConvert(id) {
      this.main('crm:views/lead/convert', {
        id: id
      });
    }
  }
  var _default = _exports.default = LeadController;
});

define("modules/crm/controllers/event-confirmation", ["exports", "controller"], function (_exports, _controller) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _controller = _interopRequireDefault(_controller);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class EventConfirmationController extends _controller.default {
    // noinspection JSUnusedGlobalSymbols
    actionConfirmEvent(actionData) {
      const viewName = this.getMetadata().get(['clientDefs', 'EventConfirmation', 'confirmationView']) || 'crm:views/event-confirmation/confirmation';
      this.entire(viewName, {
        actionData: actionData
      }, view => {
        view.render();
      });
    }
  }

  // noinspection JSUnusedGlobalSymbols
  var _default = _exports.default = EventConfirmationController;
});

define("modules/crm/controllers/calendar", ["exports", "controller"], function (_exports, _controller) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _controller = _interopRequireDefault(_controller);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class CalendarController extends _controller.default {
    checkAccess() {
      if (this.getAcl().check('Calendar')) {
        return true;
      }
      return false;
    }

    // noinspection JSUnusedGlobalSymbols
    actionShow(options) {
      this.actionIndex(options);
    }
    actionIndex(options) {
      this.handleCheckAccess('');
      this.main('crm:views/calendar/calendar-page', {
        date: options.date,
        mode: options.mode,
        userId: options.userId,
        userName: options.userName
      });
    }
  }
  var _default = _exports.default = CalendarController;
});

define("modules/crm/controllers/activities", ["exports", "controller"], function (_exports, _controller) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _controller = _interopRequireDefault(_controller);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class ActivitiesController extends _controller.default {
    checkAccess(action) {
      if (this.getAcl().check('Activities')) {
        return true;
      }
      return false;
    }

    // noinspection JSUnusedGlobalSymbols
    actionActivities(options) {
      this.processList('activities', options.entityType, options.id, options.targetEntityType);
    }
    actionHistory(options) {
      this.processList('history', options.entityType, options.id, options.targetEntityType);
    }

    /**
     * @param {'activities'|'history'} type
     * @param {string} entityType
     * @param {string} id
     * @param {string} targetEntityType
     */
    processList(type, entityType, id, targetEntityType) {
      let viewName = 'crm:views/activities/list';
      let model;
      this.modelFactory.create(entityType).then(m => {
        model = m;
        model.id = id;
        return model.fetch({
          main: true
        });
      }).then(() => {
        return this.collectionFactory.create(targetEntityType);
      }).then(collection => {
        collection.url = 'Activities/' + model.entityType + '/' + id + '/' + type + '/list/' + targetEntityType;
        this.main(viewName, {
          scope: entityType,
          model: model,
          collection: collection,
          link: type + '_' + targetEntityType,
          type: type
        });
      });
    }
  }
  var _default = _exports.default = ActivitiesController;
});

define("modules/crm/acl-portal/document", ["exports", "acl-portal"], function (_exports, _aclPortal) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _aclPortal = _interopRequireDefault(_aclPortal);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class DocumentAclPortal extends _aclPortal.default {
    // noinspection JSUnusedGlobalSymbols
    checkModelEdit(model, data, precise) {
      let result = this.checkModel(model, data, 'delete', precise);
      if (result) {
        return true;
      }
      if (data.edit === 'account') {
        return true;
      }
      return false;
    }
  }
  var _default = _exports.default = DocumentAclPortal;
});

define("modules/crm/acl-portal/contact", ["exports", "acl-portal"], function (_exports, _aclPortal) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _aclPortal = _interopRequireDefault(_aclPortal);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class ContactAclPortal extends _aclPortal.default {
    checkIsOwnContact(model) {
      const contactId = this.getUser().get('contactId');
      if (!contactId) {
        return false;
      }
      if (contactId === model.id) {
        return true;
      }
      return false;
    }
  }
  var _default = _exports.default = ContactAclPortal;
});

define("modules/crm/acl-portal/account", ["exports", "acl-portal"], function (_exports, _aclPortal) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _aclPortal = _interopRequireDefault(_aclPortal);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class AccountAclPortal extends _aclPortal.default {
    checkInAccount(model) {
      const accountIdList = this.getUser().getLinkMultipleIdList('accounts');
      if (!accountIdList.length) {
        return false;
      }
      if (~accountIdList.indexOf(model.id)) {
        return true;
      }
      return false;
    }
  }
  var _default = _exports.default = AccountAclPortal;
});

define("modules/crm/acl/mass-email", ["exports", "acl"], function (_exports, _acl) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _acl = _interopRequireDefault(_acl);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class MassEmailAcl extends _acl.default {
    checkScope(data, action, precise, entityAccessData) {
      if (action === 'create') {
        return super.checkScope(data, 'edit', precise, entityAccessData);
      }
      return super.checkScope(data, action, precise, entityAccessData);
    }
    checkIsOwner(model) {
      if (model.has('campaignId')) {
        return true;
      }
      return super.checkIsOwner(model);
    }
    checkInTeam(model) {
      if (model.has('campaignId')) {
        return true;
      }
      return super.checkInTeam(model);
    }
  }
  var _default = _exports.default = MassEmailAcl;
});

define("modules/crm/acl/campaign-tracking-url", ["exports", "acl"], function (_exports, _acl) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _acl = _interopRequireDefault(_acl);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class CampaignTrackingUrlAcl extends _acl.default {
    checkIsOwner(model) {
      if (model.has('campaignId')) {
        return true;
      }
      return false;
    }
    checkInTeam(model) {
      if (model.has('campaignId')) {
        return true;
      }
      return false;
    }
  }
  var _default = _exports.default = CampaignTrackingUrlAcl;
});

define("modules/crm/acl/call", ["exports", "modules/crm/acl/meeting"], function (_exports, _meeting) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _meeting = _interopRequireDefault(_meeting);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class CallAcl extends _meeting.default {}
  var _default = _exports.default = CallAcl;
});


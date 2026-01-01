define("views/import/record/panels/imported", ["exports", "views/record/panels/relationship"], function (_exports, _relationship) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _relationship = _interopRequireDefault(_relationship);
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

  class ImportImportedPanelView extends _relationship.default {
    link = 'imported';
    readOnly = true;
    rowActionsView = 'views/record/row-actions/relationship-no-unlink';
    setup() {
      this.entityType = this.model.get('entityType');
      this.title = this.title || this.translate('Imported', 'labels', 'Import');
      super.setup();
    }
  }
  var _default = _exports.default = ImportImportedPanelView;
});

define("views/email-account/record/detail", ["exports", "views/record/detail"], function (_exports, _detail) {
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
    setup() {
      super.setup();
      this.setupFieldsBehaviour();
      this.initSslFieldListening();
      this.initSmtpFieldsControl();
      if (this.getUser().isAdmin()) {
        this.setFieldNotReadOnly('assignedUser');
      } else {
        this.setFieldReadOnly('assignedUser');
      }
    }
    modifyDetailLayout(layout) {
      layout.filter(panel => panel.tabLabel === '$label:SMTP').forEach(panel => {
        panel.rows.forEach(row => {
          row.forEach(item => {
            const labelText = this.translate(item.name, 'fields', 'EmailAccount');
            if (labelText && labelText.indexOf('SMTP ') === 0) {
              item.labelText = Espo.Utils.upperCaseFirst(labelText.substring(5));
            }
          });
        });
      });
    }
    setupFieldsBehaviour() {
      this.controlStatusField();
      this.listenTo(this.model, 'change:status', (model, value, o) => {
        if (o.ui) {
          this.controlStatusField();
        }
      });
      this.listenTo(this.model, 'change:useImap', (model, value, o) => {
        if (o.ui) {
          this.controlStatusField();
        }
      });
      if (this.wasFetched()) {
        this.setFieldReadOnly('fetchSince');
      } else {
        this.setFieldNotReadOnly('fetchSince');
      }
    }
    controlStatusField() {
      const list = ['username', 'port', 'host', 'monitoredFolders'];
      if (this.model.get('status') === 'Active' && this.model.get('useImap')) {
        list.forEach(item => {
          this.setFieldRequired(item);
        });
        return;
      }
      list.forEach(item => {
        this.setFieldNotRequired(item);
      });
    }
    wasFetched() {
      if (!this.model.isNew()) {
        return !!(this.model.get('fetchData') || {}).lastUID;
      }
      return false;
    }
    initSslFieldListening() {
      this.listenTo(this.model, 'change:security', (model, value, o) => {
        if (!o.ui) {
          return;
        }
        if (value === 'SSL') {
          this.model.set('port', 993);
        } else {
          this.model.set('port', 143);
        }
      });
      this.listenTo(this.model, 'change:smtpSecurity', (model, value, o) => {
        if (o.ui) {
          if (value === 'SSL') {
            this.model.set('smtpPort', 465);
          } else if (value === 'TLS') {
            this.model.set('smtpPort', 587);
          } else {
            this.model.set('smtpPort', 25);
          }
        }
      });
    }
    initSmtpFieldsControl() {
      this.controlSmtpFields();
      this.listenTo(this.model, 'change:useSmtp', this.controlSmtpFields, this);
      this.listenTo(this.model, 'change:smtpAuth', this.controlSmtpFields, this);
    }
    controlSmtpFields() {
      if (this.model.get('useSmtp')) {
        this.showField('smtpHost');
        this.showField('smtpPort');
        this.showField('smtpAuth');
        this.showField('smtpSecurity');
        this.showField('smtpTestSend');
        this.setFieldRequired('smtpHost');
        this.setFieldRequired('smtpPort');
        this.controlSmtpAuthField();
        return;
      }
      this.hideField('smtpHost');
      this.hideField('smtpPort');
      this.hideField('smtpAuth');
      this.hideField('smtpUsername');
      this.hideField('smtpPassword');
      this.hideField('smtpAuthMechanism');
      this.hideField('smtpSecurity');
      this.hideField('smtpTestSend');
      this.setFieldNotRequired('smtpHost');
      this.setFieldNotRequired('smtpPort');
      this.setFieldNotRequired('smtpUsername');
    }
    controlSmtpAuthField() {
      if (this.model.get('smtpAuth')) {
        this.showField('smtpUsername');
        this.showField('smtpPassword');
        this.showField('smtpAuthMechanism');
        this.setFieldRequired('smtpUsername');
        return;
      }
      this.hideField('smtpUsername');
      this.hideField('smtpPassword');
      this.hideField('smtpAuthMechanism');
      this.setFieldNotRequired('smtpUsername');
    }
  }
  _exports.default = _default;
});

define("views/personal-data/record/record", ["exports", "views/record/base"], function (_exports, _base) {
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

  class PersonalDataRecordView extends _base.default {
    template = 'personal-data/record/record';
    additionalEvents = {
      /** @this PersonalDataRecordView */
      'click .checkbox': function (e) {
        const name = $(e.currentTarget).data('name');
        if (e.currentTarget.checked) {
          if (!~this.checkedFieldList.indexOf(name)) {
            this.checkedFieldList.push(name);
          }
          if (this.checkedFieldList.length === this.fieldList.length) {
            this.$el.find('.checkbox-all').prop('checked', true);
          } else {
            this.$el.find('.checkbox-all').prop('checked', false);
          }
        } else {
          const index = this.checkedFieldList.indexOf(name);
          if (~index) {
            this.checkedFieldList.splice(index, 1);
          }
          this.$el.find('.checkbox-all').prop('checked', false);
        }
        this.trigger('check', this.checkedFieldList);
      },
      /** @this PersonalDataRecordView */
      'click .checkbox-all': function (e) {
        if (e.currentTarget.checked) {
          this.checkedFieldList = Espo.Utils.clone(this.fieldList);
          this.$el.find('.checkbox').prop('checked', true);
        } else {
          this.checkedFieldList = [];
          this.$el.find('.checkbox').prop('checked', false);
        }
        this.trigger('check', this.checkedFieldList);
      }
    };
    checkedFieldList;
    data() {
      const data = {};
      data.fieldDataList = this.getFieldDataList();
      data.scope = this.scope;
      data.editAccess = this.editAccess;
      return data;
    }
    setup() {
      super.setup();
      this.events = {
        ...this.additionalEvents,
        ...this.events
      };
      this.scope = this.model.entityType;
      this.fieldList = [];
      this.checkedFieldList = [];
      this.editAccess = this.getAcl().check(this.model, 'edit');
      const fieldDefs = this.getMetadata().get(['entityDefs', this.scope, 'fields']) || {};
      const fieldList = [];
      for (const field in fieldDefs) {
        const defs = /** @type {Record} */fieldDefs[field];
        if (defs.isPersonalData) {
          fieldList.push(field);
        }
      }
      fieldList.forEach(field => {
        const type = fieldDefs[field].type;
        const attributeList = this.getFieldManager().getActualAttributeList(type, field);
        let isNotEmpty = false;
        attributeList.forEach(attribute => {
          const value = this.model.get(attribute);
          if (value) {
            if (Object.prototype.toString.call(value) === '[object Array]') {
              if (value.length) {
                return;
              }
            }
            isNotEmpty = true;
          }
        });
        const hasAccess = !this.getAcl().getScopeForbiddenFieldList(this.scope).includes(field);
        if (isNotEmpty && hasAccess) {
          this.fieldList.push(field);
        }
      });
      this.fieldList = this.fieldList.sort((v1, v2) => {
        return this.translate(v1, 'fields', this.scope).localeCompare(this.translate(v2, 'fields', this.scope));
      });
      this.fieldList.forEach(field => {
        this.createField(field, null, null, 'detail', true);
      });
    }
    getFieldDataList() {
      const forbiddenList = this.getAcl().getScopeForbiddenFieldList(this.scope, 'edit');
      const list = [];
      this.fieldList.forEach(field => {
        list.push({
          name: field,
          key: field + 'Field',
          editAccess: this.editAccess && !~forbiddenList.indexOf(field)
        });
      });
      return list;
    }
  }
  var _default = _exports.default = PersonalDataRecordView;
});

define("views/personal-data/modals/personal-data", ["exports", "views/modal"], function (_exports, _modal) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _modal = _interopRequireDefault(_modal);
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

  class _default extends _modal.default {
    template = 'personal-data/modals/personal-data';
    className = 'dialog dialog-record';
    backdrop = true;
    setup() {
      super.setup();
      this.buttonList = [{
        name: 'cancel',
        label: 'Close'
      }];
      this.headerText = this.getLanguage().translate('Personal Data');
      this.headerText += ': ' + this.model.get('name');
      if (this.getAcl().check(this.model, 'edit')) {
        this.buttonList.unshift({
          name: 'erase',
          label: 'Erase',
          style: 'danger',
          disabled: true,
          onClick: () => this.actionErase()
        });
      }
      this.fieldList = [];
      this.scope = this.model.entityType;
      this.createView('record', 'views/personal-data/record/record', {
        selector: '.record',
        model: this.model
      }, view => {
        this.listenTo(view, 'check', fieldList => {
          this.fieldList = fieldList;
          if (fieldList.length) {
            this.enableButton('erase');
          } else {
            this.disableButton('erase');
          }
        });
        if (!view.fieldList.length) {
          this.disableButton('export');
        }
      });
    }
    actionErase() {
      this.confirm({
        message: this.translate('erasePersonalDataConfirmation', 'messages'),
        confirmText: this.translate('Erase')
      }, () => {
        this.disableButton('erase');
        Espo.Ajax.postRequest('DataPrivacy/action/erase', {
          fieldList: this.fieldList,
          entityType: this.scope,
          id: this.model.id
        }).then(() => {
          Espo.Ui.success(this.translate('Done'));
          this.trigger('erase');
        }).catch(() => {
          this.enableButton('erase');
        });
      });
    }
  }
  _exports.default = _default;
});

define("views/outbound-email/modals/test-send", ["exports", "views/modal"], function (_exports, _modal) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _modal = _interopRequireDefault(_modal);
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

  class _default extends _modal.default {
    cssName = 'test-send';
    templateContent = `
        <label class="control-label">{{translate \'Email Address\' scope=\'Email\'}}</label>
        <input type="text" name="emailAddress" value="{{emailAddress}}" class="form-control">
    `;
    data() {
      return {
        emailAddress: this.options.emailAddress
      };
    }
    setup() {
      this.buttonList = [{
        name: 'send',
        text: this.translate('Send', 'labels', 'Email'),
        style: 'primary',
        onClick: () => {
          const emailAddress = this.$el.find('input').val();
          if (emailAddress === '') {
            return;
          }
          this.trigger('send', emailAddress);
        }
      }, {
        name: 'cancel',
        label: 'Cancel',
        onClick: dialog => {
          dialog.close();
        }
      }];
    }
  }
  _exports.default = _default;
});

define("views/import-error/fields/validation-failures", ["exports", "views/fields/base"], function (_exports, _base) {
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

  class ValidationFailuresFieldView extends _base.default {
    // language=Handlebars
    detailTemplateContent = `
        {{#if itemList.length}}
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="width: 50%;">{{translate 'Field'}}</th>
                    <th>{{translateOption 'Validation' scope='ImportError' field='type'}}</th>
                </tr>
            </thead>
            <tbody>
                {{#each itemList}}
                <tr>
                    <td>{{translate field category='fields' scope=entityType}}</td>
                    <td>
                        {{translate type category='fieldValidations'}}
                        {{#if popoverText}}
                        <a
                            role="button"
                            tabindex="-1"
                            class="text-danger popover-anchor"
                            data-text="{{popoverText}}"
                        ><span class="fas fa-info-circle"></span></a>
                        {{/if}}
                    </td>
                </tr>
                {{/each}}
            </tbody>
        </table>
        {{else}}
        <span class="none-value">{{translate 'None'}}</span>
        {{/if}}
    `;
    data() {
      const data = super.data();
      data.itemList = this.getDataList();
      return data;
    }
    afterRenderDetail() {
      this.$el.find('.popover-anchor').each((i, /** HTMLElement */el) => {
        const text = this.getHelper().transformMarkdownText(el.dataset.text).toString();
        Espo.Ui.popover($(el), {
          content: text
        }, this);
      });
    }

    /**
     * @return {Object[]}
     */
    getDataList() {
      const itemList = Espo.Utils.cloneDeep(this.model.get(this.name)) || [];
      const entityType = this.model.get('entityType');
      if (Array.isArray(itemList)) {
        itemList.forEach(item => {
          const fieldManager = this.getFieldManager();
          const language = this.getLanguage();
          const fieldType = fieldManager.getEntityTypeFieldParam(entityType, item.field, 'type');
          if (!fieldType) {
            return;
          }
          const key = fieldType + '_' + item.type;
          if (!language.has(key, 'fieldValidationExplanations', 'Global')) {
            if (!language.has(item.type, 'fieldValidationExplanations', 'Global')) {
              return;
            }
            item.popoverText = language.translate(item.type, 'fieldValidationExplanations');
            return;
          }
          item.popoverText = language.translate(key, 'fieldValidationExplanations');
        });
      }
      return itemList;
    }
  }

  // noinspection JSUnusedGlobalSymbols
  var _default = _exports.default = ValidationFailuresFieldView;
});

define("views/import-error/fields/line-number", ["exports", "views/fields/int"], function (_exports, _int) {
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

  class _default extends _int.default {
    disableFormatting = true;
    data() {
      const data = super.data();
      data.valueIsSet = this.model.has(this.sourceName);
      data.isNotEmpty = this.model.has(this.sourceName);
      return data;
    }
    setup() {
      super.setup();
      this.sourceName = this.name === 'exportLineNumber' ? 'exportRowIndex' : 'rowIndex';
    }
    getAttributeList() {
      return [this.sourceName];
    }
    getValueForDisplay() {
      let value = this.model.get(this.sourceName);
      value++;
      return this.formatNumber(value);
    }
  }
  _exports.default = _default;
});

define("views/import/step2", ["exports", "view", "ui/select"], function (_exports, _view, _select) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _view = _interopRequireDefault(_view);
  _select = _interopRequireDefault(_select);
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

  class Step2ImportView extends _view.default {
    template = 'import/step-2';
    allowedFieldList = ['createdAt', 'createdBy'];
    events = {
      /** @this Step2ImportView */
      'click button[data-action="back"]': function () {
        this.back();
      },
      /** @this Step2ImportView */
      'click button[data-action="next"]': function () {
        this.next();
      },
      /** @this Step2ImportView */
      'click a[data-action="addField"]': function (e) {
        const field = $(e.currentTarget).data('name');
        this.addField(field);
      },
      /** @this Step2ImportView */
      'click a[data-action="removeField"]': function (e) {
        const field = $(e.currentTarget).data('name');
        this.$el.find('a[data-action="addField"]').parent().removeClass('hidden');
        const index = this.additionalFields.indexOf(field);
        if (~index) {
          this.additionalFields.splice(index, 1);
        }
        this.$el.find('.field[data-name="' + field + '"]').parent().remove();
      },
      /** @this Step2ImportView */
      'keyup input.add-field-quick-search-input': function (e) {
        this.processFieldFilterQuickSearch(e.currentTarget.value);
      }
    };
    data() {
      return {
        scope: this.scope,
        fieldList: this.getFieldList()
      };
    }
    setup() {
      this.formData = this.options.formData;
      this.scope = this.formData.entityType;
      const mapping = [];
      this.additionalFields = [];
      if (this.formData.previewArray) {
        let index = 0;
        if (this.formData.headerRow) {
          index = 1;
        }
        if (this.formData.previewArray.length > index) {
          this.formData.previewArray[index].forEach((value, i) => {
            const d = {
              value: value
            };
            if (this.formData.headerRow) {
              d.name = this.formData.previewArray[0][i];
            }
            mapping.push(d);
          });
        }
      }
      this.wait(true);
      this.getModelFactory().create(this.scope, model => {
        this.model = model;
        if (this.formData.defaultValues) {
          this.model.set(this.formData.defaultValues);
        }
        this.wait(false);
      });
      this.mapping = mapping;

      /** @type {string[]} */
      this.fieldList = this.getFieldList();
      this.fieldTranslations = this.fieldList.reduce((map, item) => {
        map[item] = this.translate(item, 'fields', this.scope);
        return map;
      }, {});
    }
    afterRender() {
      const $container = $('#mapping-container');
      const $table = $('<table>').addClass('table').addClass('table-bordered').css('table-layout', 'fixed');
      const $tbody = $('<tbody>').appendTo($table);
      let $row = $('<tr>');
      if (this.formData.headerRow) {
        const $cell = $('<th>').attr('width', '25%').text(this.translate('Header Row Value', 'labels', 'Import'));
        $row.append($cell);
      }
      let $cell = $('<th>').attr('width', '25%').text(this.translate('Field', 'labels', 'Import'));
      $row.append($cell);
      $cell = $('<th>').text(this.translate('First Row Value', 'labels', 'Import'));
      $row.append($cell);
      if (~['update', 'createAndUpdate'].indexOf(this.formData.action)) {
        $cell = $('<th>').text(this.translate('Update by', 'labels', 'Import'));
        $row.append($cell);
      }
      $tbody.append($row);
      const selectList = [];
      this.mapping.forEach((d, i) => {
        $row = $('<tr>');
        if (this.formData.headerRow) {
          $cell = $('<td>').text(d.name);
          $row.append($cell);
        }
        let selectedName = d.name;
        if (this.formData.attributeList) {
          if (this.formData.attributeList[i]) {
            selectedName = this.formData.attributeList[i];
          } else {
            selectedName = null;
          }
        }
        const $select = this.getFieldDropdown(i, selectedName);
        selectList.push($select.get(0));
        $cell = $('<td>').append($select);
        $row.append($cell);
        let value = d.value || '';
        if (value.length > 200) {
          value = value.substring(0, 200) + '...';
        }
        $cell = $('<td>').css('overflow', 'hidden').text(value);
        $row.append($cell);
        if (~['update', 'createAndUpdate'].indexOf(this.formData.action)) {
          const $checkbox = $('<input>').attr('type', 'checkbox').addClass('form-checkbox').attr('id', 'update-by-' + i.toString());

          /** @type {HTMLInputElement} */
          const checkboxElement = $checkbox.get(0);
          if (!this.formData.updateBy) {
            if (d.name === 'id') {
              checkboxElement.checked = true;
            }
          } else if (~this.formData.updateBy.indexOf(i)) {
            checkboxElement.checked = true;
          }
          $cell = $('<td>').append(checkboxElement);
          $row.append($cell);
        }
        $tbody.append($row);
      });
      $container.empty();
      $container.append($table);
      this.getDefaultFieldList().forEach(name => {
        this.addField(name);
      });
      this.$addFieldButton = this.$el.find('button.add-field');
      this.$defaultFieldList = this.$el.find('ul.default-field-list');
      this.$fieldQuickSearch = this.$el.find('input.add-field-quick-search-input');
      this.initQuickSearchUi();
      selectList.forEach(select => _select.default.init(select));
    }
    resetFieldFilterQuickSearch() {
      this.$fieldQuickSearch.val('');
      this.$defaultFieldList.find('li.item').removeClass('hidden');
    }
    initQuickSearchUi() {
      this.$addFieldButton.parent().on('show.bs.dropdown', () => {
        setTimeout(() => {
          this.$fieldQuickSearch.focus();
          const width = this.$fieldQuickSearch.outerWidth();
          this.$fieldQuickSearch.css('minWidth', width);
        }, 1);
      });
      this.$addFieldButton.parent().on('hide.bs.dropdown', () => {
        this.resetFieldFilterQuickSearch();
        this.$fieldQuickSearch.css('minWidth', '');
      });
    }

    /**
     * @private
     * @param {string} text
     */
    processFieldFilterQuickSearch(text) {
      text = text.trim();
      text = text.toLowerCase();

      /** @type {JQuery} */
      const $li = this.$defaultFieldList.find('li.item');
      if (text === '') {
        $li.removeClass('hidden');
        return;
      }
      $li.addClass('hidden');
      this.fieldList.forEach(field => {
        let label = this.fieldTranslations[field] || field;
        label = label.toLowerCase();
        const wordList = label.split(' ');
        let matched = label.indexOf(text) === 0;
        if (!matched) {
          matched = wordList.filter(word => word.length > 3 && word.indexOf(text) === 0).length > 0;
        }
        if (matched) {
          $li.filter(`[data-name="${field}"]`).removeClass('hidden');
        }
      });
    }

    /**
     * @return {string[]}
     */
    getDefaultFieldList() {
      if (this.formData.defaultFieldList) {
        return this.formData.defaultFieldList;
      }
      if (!this.formData.defaultValues) {
        return [];
      }
      const defaultAttributes = Object.keys(this.formData.defaultValues);
      return this.getFieldManager().getEntityTypeFieldList(this.scope).filter(field => {
        const attributeList = this.getFieldManager().getEntityTypeFieldActualAttributeList(this.scope, field);
        return attributeList.findIndex(attribute => defaultAttributes.includes(attribute)) !== -1;
      });
    }

    /**
     * @private
     * @return {string[]}
     */
    getFieldList() {
      const defs = this.getMetadata().get(`entityDefs.${this.scope}.fields`);
      const forbiddenFieldList = this.getAcl().getScopeForbiddenFieldList(this.scope, 'edit');
      let fieldList = [];
      for (const field in defs) {
        if (forbiddenFieldList.includes(field)) {
          continue;
        }
        const d = /** @type {Object.<string, *>} */defs[field];
        if (!this.allowedFieldList.includes(field) && (d.disabled || d.importDisabled || d.utility || d.directAccessDisabled && !d.importEnabled || d.directUpdateDisabled && !d.importEnabled && !d.directUpdateEnabled)) {
          continue;
        }
        fieldList.push(field);
      }
      fieldList = fieldList.sort((v1, v2) => {
        return this.translate(v1, 'fields', this.scope).localeCompare(this.translate(v2, 'fields', this.scope));
      });
      return fieldList;
    }

    /**
     * @private
     * @returns {string[]}
     */
    getAttributeList() {
      const fields = this.getMetadata().get(['entityDefs', this.scope, 'fields']) || {};
      const forbiddenFieldList = this.getAcl().getScopeForbiddenFieldList(this.scope, 'edit');
      let attributeList = [];
      attributeList.push('id');
      for (const field in fields) {
        if (forbiddenFieldList.includes(field)) {
          continue;
        }
        const defs = /** @type {Object.<string, *>} */fields[field];
        if (!this.allowedFieldList.includes(field) && (defs.disabled || defs.importDisabled || defs.utility || defs.directAccessDisabled && !defs.importEnabled || defs.directUpdateDisabled && !defs.importEnabled && !defs.directUpdateEnabled)) {
          continue;
        }
        if (defs.type === 'phone') {
          attributeList.push(field);
          (this.getMetadata().get(`entityDefs.${this.scope}.fields.${field}.typeList`) || []).map(item => item.replace(/\s/g, '_')).forEach(item => {
            attributeList.push(field + Espo.Utils.upperCaseFirst(item));
          });
          continue;
        }
        if (defs.type === 'email') {
          attributeList.push(field + '2');
          attributeList.push(field + '3');
          attributeList.push(field + '4');
        }
        if (defs.type === 'link') {
          attributeList.push(field + 'Name');
          attributeList.push(field + 'Id');
        }
        if (defs.type === 'foreign' && !defs.relateOnImport) {
          continue;
        }
        if (defs.type === 'personName') {
          attributeList.push(field);
        }
        const type = defs.type;
        let actualAttributeList = this.getFieldManager().getActualAttributeList(type, field);
        if (!actualAttributeList.length) {
          actualAttributeList = [field];
        }
        actualAttributeList.forEach(it => {
          if (attributeList.indexOf(it) === -1) {
            attributeList.push(it);
          }
        });
      }
      attributeList = attributeList.sort((v1, v2) => {
        return this.translate(v1, 'fields', this.scope).localeCompare(this.translate(v2, 'fields', this.scope));
      });
      return attributeList;
    }
    getFieldDropdown(num, name) {
      name = name || false;
      const fieldList = this.getAttributeList();
      const $select = $('<select>').addClass('form-control').attr('id', 'column-' + num.toString());
      let $option = $('<option>').val('').text('-' + this.translate('Skip', 'labels', 'Import') + '-');
      const scope = this.formData.entityType;
      $select.append($option);
      fieldList.forEach(field => {
        let label = '';
        if (this.getLanguage().has(field, 'fields', scope) || this.getLanguage().has(field, 'fields', 'Global')) {
          label = this.translate(field, 'fields', scope);
        } else {
          if (field.indexOf('Id') === field.length - 2) {
            const baseField = field.substr(0, field.length - 2);
            if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
              label = this.translate(baseField, 'fields', scope) + ' (' + this.translate('id', 'fields') + ')';
            }
          } else if (field.indexOf('Name') === field.length - 4) {
            const baseField = field.substr(0, field.length - 4);
            if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
              label = this.translate(baseField, 'fields', scope) + ' (' + this.translate('name', 'fields') + ')';
            }
          } else if (field.indexOf('Type') === field.length - 4) {
            const baseField = field.substr(0, field.length - 4);
            if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
              label = this.translate(baseField, 'fields', scope) + ' (' + this.translate('type', 'fields') + ')';
            }
          } else if (field.indexOf('phoneNumber') === 0) {
            const phoneNumberType = field.substr(11);
            const phoneNumberTypeLabel = this.getLanguage().translateOption(phoneNumberType, 'phoneNumber', scope);
            label = this.translate('phoneNumber', 'fields', scope) + ' (' + phoneNumberTypeLabel + ')';
          } else if (field.indexOf('emailAddress') === 0 && parseInt(field.substr(12)).toString() === field.substr(12)) {
            const emailAddressNum = field.substr(12);
            label = this.translate('emailAddress', 'fields', scope) + ' ' + emailAddressNum.toString();
          } else if (field.indexOf('Ids') === field.length - 3) {
            const baseField = field.substr(0, field.length - 3);
            if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
              label = this.translate(baseField, 'fields', scope) + ' (' + this.translate('ids', 'fields') + ')';
            }
          }
        }
        if (!label) {
          label = field;
        }
        $option = $('<option>').val(field).text(label);
        if (name) {
          if (field === name) {
            $option.prop('selected', true);
          } else {
            if (name.toLowerCase().replace(/_/g, '') === field.toLowerCase()) {
              $option.prop('selected', true);
            }
          }
        }
        $select.append($option);
      });
      return $select;
    }

    /**
     * @param {string} name
     */
    addField(name) {
      this.$el.find('[data-action="addField"][data-name="' + name + '"]').parent().addClass('hidden');
      $(this.containerSelector + ' button[data-name="update"]').removeClass('disabled');
      Espo.Ui.notifyWait();
      let label = this.translate(name, 'fields', this.scope);
      label = this.getHelper().escapeString(label);
      const removeLink = '<a role="button" class="pull-right" data-action="removeField" data-name="' + name + '">' + '<span class="fas fa-times"></span></a>';
      const html = '<div class="cell form-group">' + removeLink + '<label class="control-label">' + label + '</label><div class="field" data-name="' + name + '"/></div>';
      $('#default-values-container').append(html);
      const type = Espo.Utils.upperCaseFirst(this.model.getFieldParam(name, 'type'));
      const viewName = this.getMetadata().get(['entityDefs', this.scope, 'fields', name, 'view']) || this.getFieldManager().getViewName(type);
      this.createView(name, viewName, {
        model: this.model,
        fullSelector: this.getSelector() + ' .field[data-name="' + name + '"]',
        defs: {
          name: name
        },
        mode: 'edit',
        readOnlyDisabled: true
      }, view => {
        this.additionalFields.push(name);
        view.render();
        view.notify(false);
      });
      this.resetFieldFilterQuickSearch();
    }
    disableButtons() {
      this.$el.find('button[data-action="next"]').addClass('disabled').attr('disabled', 'disabled');
      this.$el.find('button[data-action="back"]').addClass('disabled').attr('disabled', 'disabled');
    }
    enableButtons() {
      this.$el.find('button[data-action="next"]').removeClass('disabled').removeAttr('disabled');
      this.$el.find('button[data-action="back"]').removeClass('disabled').removeAttr('disabled');
    }

    /**
     * @param {string} field
     * @return {module:views/fields/base}
     */
    getFieldView(field) {
      return this.getView(field);
    }
    fetch(skipValidation) {
      const attributes = {};
      this.additionalFields.forEach(field => {
        const view = this.getFieldView(field);
        _.extend(attributes, view.fetch());
      });
      this.model.set(attributes);
      let notValid = false;
      this.additionalFields.forEach(field => {
        const view = this.getFieldView(field);
        notValid = view.validate() || notValid;
      });
      if (!notValid) {
        this.formData.defaultValues = attributes;
      }
      if (notValid && !skipValidation) {
        return false;
      }
      this.formData.defaultFieldList = Espo.Utils.clone(this.additionalFields);
      const attributeList = [];
      this.mapping.forEach((d, i) => {
        attributeList.push($('#column-' + i).val());
      });
      this.formData.attributeList = attributeList;
      if (~['update', 'createAndUpdate'].indexOf(this.formData.action)) {
        const updateBy = [];
        this.mapping.forEach((d, i) => {
          if ($('#update-by-' + i).get(0).checked) {
            updateBy.push(i);
          }
        });
        this.formData.updateBy = updateBy;
      }
      this.getParentIndexView().formData = this.formData;
      this.getParentIndexView().trigger('change');
      return true;
    }

    /**
     * @return {import('./index').default}
     */
    getParentIndexView() {
      // noinspection JSValidateTypes
      return this.getParentView();
    }
    back() {
      this.fetch(true);
      this.getParentIndexView().changeStep(1);
    }
    next() {
      if (!this.fetch()) {
        return;
      }
      this.disableButtons();
      Espo.Ui.notifyWait();
      Espo.Ajax.postRequest('Import/file', null, {
        timeout: 0,
        contentType: 'text/csv',
        data: this.getParentIndexView().fileContents
      }).then(result => {
        if (!result.attachmentId) {
          Espo.Ui.error(this.translate('Bad response'));
          return;
        }
        this.runImport(result.attachmentId);
      });
    }
    runImport(attachmentId) {
      this.formData.attachmentId = attachmentId;
      this.getRouter().confirmLeaveOut = false;
      Espo.Ui.notify(this.translate('importRunning', 'messages', 'Import'));
      Espo.Ajax.postRequest('Import', this.formData, {
        timeout: 0
      }).then(result => {
        const id = result.id;
        this.getParentIndexView().trigger('done');
        if (!id) {
          Espo.Ui.error(this.translate('Error'), true);
          this.enableButtons();
          return;
        }
        if (!this.formData.manualMode) {
          this.getRouter().navigate('#Import/view/' + id, {
            trigger: true
          });
          Espo.Ui.notify(false);
          return;
        }
        this.createView('dialog', 'views/modal', {
          templateContent: "{{complexText viewObject.options.msg}}",
          headerText: ' ',
          backdrop: 'static',
          msg: this.translate('commandToRun', 'strings', 'Import') + ':\n\n' + '```php command.php import --id=' + id + '```',
          buttonList: [{
            name: 'close',
            label: this.translate('Close')
          }]
        }, view => {
          view.render();
          this.listenToOnce(view, 'close', () => {
            this.getRouter().navigate('#Import/view/' + id, {
              trigger: true
            });
          });
        });
        Espo.Ui.notify(false);
      }).catch(() => this.enableButtons());
    }
  }
  var _default = _exports.default = Step2ImportView;
});

define("views/import/step1", ["exports", "view", "model", "intl-tel-input-globals"], function (_exports, _view, _model, _intlTelInputGlobals) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _view = _interopRequireDefault(_view);
  _model = _interopRequireDefault(_model);
  _intlTelInputGlobals = _interopRequireDefault(_intlTelInputGlobals);
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

  // noinspection NpmUsedModulesInstalled

  class Step1ImportView extends _view.default {
    template = 'import/step-1';
    events = {
      /** @this Step1ImportView */
      'change #import-file': function (e) {
        const files = e.currentTarget.files;
        if (files.length) {
          this.loadFile(files[0]);
        }
      },
      /** @this Step1ImportView */
      'click button[data-action="next"]': function () {
        this.next();
      },
      /** @this Step1ImportView */
      'click button[data-action="saveAsDefault"]': function () {
        this.saveAsDefault();
      }
    };
    getEntityList() {
      const list = [];
      /** @type {Object.<string, Record>} */
      const scopes = this.getMetadata().get('scopes');
      for (const scopeName in scopes) {
        if (scopes[scopeName].importable) {
          if (!this.getAcl().checkScope(scopeName, 'create')) {
            continue;
          }
          list.push(scopeName);
        }
      }
      list.sort((v1, v2) => {
        return this.translate(v1, 'scopeNamesPlural').localeCompare(this.translate(v2, 'scopeNamesPlural'));
      });
      return list;
    }
    data() {
      return {
        entityList: this.getEntityList()
      };
    }
    setup() {
      this.attributeList = ['entityType', 'action'];
      this.paramList = ['headerRow', 'decimalMark', 'personNameFormat', 'delimiter', 'dateFormat', 'timeFormat', 'currency', 'timezone', 'textQualifier', 'silentMode', 'idleMode', 'skipDuplicateChecking', 'manualMode', 'phoneNumberCountry'];
      this.paramList.forEach(item => {
        this.attributeList.push(item);
      });
      this.formData = this.options.formData || {
        entityType: this.options.entityType || null,
        create: 'create',
        headerRow: true,
        delimiter: ',',
        textQualifier: '"',
        dateFormat: 'YYYY-MM-DD',
        timeFormat: 'HH:mm:ss',
        currency: this.getConfig().get('defaultCurrency'),
        timezone: 'UTC',
        decimalMark: '.',
        personNameFormat: 'f l',
        idleMode: false,
        skipDuplicateChecking: false,
        silentMode: true,
        manualMode: false
      };
      const defaults = Espo.Utils.cloneDeep((this.getPreferences().get('importParams') || {}).default || {});
      if (!this.options.formData) {
        for (const p in defaults) {
          this.formData[p] = defaults[p];
        }
      }
      const model = this.model = new _model.default();
      this.attributeList.forEach(a => {
        model.set(a, this.formData[a]);
      });
      this.attributeList.forEach(a => {
        this.listenTo(model, 'change:' + a, (m, v, o) => {
          if (!o.ui) {
            return;
          }
          this.formData[a] = this.model.get(a);
          this.preview();
        });
      });
      const personNameFormatList = ['f l', 'l f', 'l, f'];
      const personNameFormat = this.getConfig().get('personNameFormat') || 'firstLast';
      if (~personNameFormat.toString().toLowerCase().indexOf('middle')) {
        personNameFormatList.push('f m l');
        personNameFormatList.push('l f m');
      }
      const dateFormatDataList = this.getDateFormatDataList();
      const timeFormatDataList = this.getTimeFormatDataList();
      const dateFormatList = [];
      const dateFormatOptions = {};
      dateFormatDataList.forEach(item => {
        dateFormatList.push(item.key);
        dateFormatOptions[item.key] = item.label;
      });
      const timeFormatList = [];
      const timeFormatOptions = {};
      timeFormatDataList.forEach(item => {
        timeFormatList.push(item.key);
        timeFormatOptions[item.key] = item.label;
      });
      this.createView('actionField', 'views/fields/enum', {
        selector: '.field[data-name="action"]',
        model: this.model,
        name: 'action',
        mode: 'edit',
        params: {
          options: ['create', 'createAndUpdate', 'update'],
          translatedOptions: {
            create: this.translate('Create Only', 'labels', 'Import'),
            createAndUpdate: this.translate('Create and Update', 'labels', 'Import'),
            update: this.translate('Update Only', 'labels', 'Import')
          }
        }
      });
      this.createView('entityTypeField', 'views/fields/enum', {
        selector: '.field[data-name="entityType"]',
        model: this.model,
        name: 'entityType',
        mode: 'edit',
        params: {
          options: [''].concat(this.getEntityList()),
          translation: 'Global.scopeNamesPlural',
          required: true
        },
        labelText: this.translate('Entity Type', 'labels', 'Import')
      });
      this.createView('decimalMarkField', 'views/fields/varchar', {
        selector: '.field[data-name="decimalMark"]',
        model: this.model,
        name: 'decimalMark',
        mode: 'edit',
        params: {
          options: ['.', ','],
          maxLength: 1,
          required: true
        },
        labelText: this.translate('Decimal Mark', 'labels', 'Import')
      });
      this.createView('personNameFormatField', 'views/fields/enum', {
        selector: '.field[data-name="personNameFormat"]',
        model: this.model,
        name: 'personNameFormat',
        mode: 'edit',
        params: {
          options: personNameFormatList,
          translation: 'Import.options.personNameFormat'
        }
      });
      this.createView('delimiterField', 'views/fields/enum', {
        selector: '.field[data-name="delimiter"]',
        model: this.model,
        name: 'delimiter',
        mode: 'edit',
        params: {
          options: [',', ';', '\\t', '|']
        }
      });
      this.createView('textQualifierField', 'views/fields/enum', {
        selector: '.field[data-name="textQualifier"]',
        model: this.model,
        name: 'textQualifier',
        mode: 'edit',
        params: {
          options: ['"', '\''],
          translatedOptions: {
            '"': this.translate('Double Quote', 'labels', 'Import'),
            '\'': this.translate('Single Quote', 'labels', 'Import')
          }
        }
      });
      this.createView('dateFormatField', 'views/fields/enum', {
        selector: '.field[data-name="dateFormat"]',
        model: this.model,
        name: 'dateFormat',
        mode: 'edit',
        params: {
          options: dateFormatList,
          translatedOptions: dateFormatOptions
        }
      });
      this.createView('timeFormatField', 'views/fields/enum', {
        selector: '.field[data-name="timeFormat"]',
        model: this.model,
        name: 'timeFormat',
        mode: 'edit',
        params: {
          options: timeFormatList,
          translatedOptions: timeFormatOptions
        }
      });
      this.createView('currencyField', 'views/fields/enum', {
        selector: '.field[data-name="currency"]',
        model: this.model,
        name: 'currency',
        mode: 'edit',
        params: {
          options: this.getConfig().get('currencyList')
        }
      });
      this.createView('timezoneField', 'views/fields/enum', {
        selector: '.field[data-name="timezone"]',
        model: this.model,
        name: 'timezone',
        mode: 'edit',
        params: {
          options: Espo.Utils.clone(this.getHelper().getAppParam('timeZoneList')) || []
        }
      });
      this.createView('headerRowField', 'views/fields/bool', {
        selector: '.field[data-name="headerRow"]',
        model: this.model,
        name: 'headerRow',
        mode: 'edit'
      });
      this.createView('silentModeField', 'views/fields/bool', {
        selector: '.field[data-name="silentMode"]',
        model: this.model,
        name: 'silentMode',
        mode: 'edit',
        tooltip: true,
        tooltipText: this.translate('silentMode', 'tooltips', 'Import')
      });
      this.createView('idleModeField', 'views/fields/bool', {
        selector: '.field[data-name="idleMode"]',
        model: this.model,
        name: 'idleMode',
        mode: 'edit'
      });
      this.createView('skipDuplicateCheckingField', 'views/fields/bool', {
        selector: '.field[data-name="skipDuplicateChecking"]',
        model: this.model,
        name: 'skipDuplicateChecking',
        mode: 'edit'
      });
      this.createView('manualModeField', 'views/fields/bool', {
        selector: '.field[data-name="manualMode"]',
        model: this.model,
        name: 'manualMode',
        mode: 'edit',
        tooltip: true,
        tooltipText: this.translate('manualMode', 'tooltips', 'Import')
      });
      this.createView('phoneNumberCountryField', 'views/fields/enum', {
        selector: '.field[data-name="phoneNumberCountry"]',
        model: this.model,
        name: 'phoneNumberCountry',
        mode: 'edit',
        params: {
          options: ['', ..._intlTelInputGlobals.default.getCountryData().map(item => item.iso2)]
        },
        translatedOptions: _intlTelInputGlobals.default.getCountryData().reduce((map, item) => {
          map[item.iso2] = `${item.iso2.toUpperCase()} +${item.dialCode}`;
          return map;
        }, {})
      });
      this.listenTo(this.model, 'change', (m, o) => {
        if (!o.ui) {
          return;
        }
        let isParamChanged = false;
        this.paramList.forEach(a => {
          if (m.hasChanged(a)) {
            isParamChanged = true;
          }
        });
        if (isParamChanged) {
          this.showSaveAsDefaultButton();
        }
      });
      this.listenTo(this.model, 'change', () => {
        if (this.isRendered()) {
          this.controlFieldVisibility();
        }
      });
      this.listenTo(this.model, 'change:entityType', () => {
        delete this.formData.defaultFieldList;
        delete this.formData.defaultValues;
        delete this.formData.attributeList;
        delete this.formData.updateBy;
      });
      this.listenTo(this.model, 'change:action', () => {
        delete this.formData.updateBy;
      });
      this.listenTo(this.model, 'change', (m, o) => {
        if (!o.ui) {
          return;
        }
        this.getRouter().confirmLeaveOut = true;
      });
    }
    afterRender() {
      this.setupFormData();
      if (this.getParentIndexView() && this.getParentIndexView().fileContents) {
        this.setFileIsLoaded();
        this.preview();
      }
      this.controlFieldVisibility();
    }

    /**
     * @return {import('./index').default}
     */
    getParentIndexView() {
      // noinspection JSValidateTypes
      return this.getParentView();
    }
    showSaveAsDefaultButton() {
      this.$el.find('[data-action="saveAsDefault"]').removeClass('hidden');
    }
    hideSaveAsDefaultButton() {
      this.$el.find('[data-action="saveAsDefault"]').addClass('hidden');
    }

    /**
     * @return {module:views/fields/base}
     */
    getFieldView(field) {
      return this.getView(field + 'Field');
    }
    next() {
      this.attributeList.forEach(field => {
        this.getFieldView(field).fetchToModel();
        this.formData[field] = this.model.get(field);
      });
      let isInvalid = false;
      this.attributeList.forEach(field => {
        isInvalid |= this.getFieldView(field).validate();
      });
      if (isInvalid) {
        Espo.Ui.error(this.translate('Not valid'));
        return;
      }
      this.getParentIndexView().formData = this.formData;
      this.getParentIndexView().trigger('change');
      this.getParentIndexView().changeStep(2);
    }
    setupFormData() {
      this.attributeList.forEach(field => {
        this.model.set(field, this.formData[field]);
      });
    }

    /**
     * @param {File} file
     */
    loadFile(file) {
      const blob = file.slice(0, 1024 * 512);
      const readerPreview = new FileReader();
      readerPreview.onloadend = e => {
        if (e.target.readyState === FileReader.DONE) {
          this.formData.previewString = e.target.result;
          this.preview();
        }
      };
      readerPreview.readAsText(blob);
      const reader = new FileReader();
      reader.onloadend = e => {
        if (e.target.readyState === FileReader.DONE) {
          this.getParentIndexView().fileContents = e.target.result;
          this.setFileIsLoaded();
          this.getRouter().confirmLeaveOut = true;
          this.setFileName(file.name);
        }
      };
      reader.readAsText(file);
    }

    /**
     * @param {string} name
     */
    setFileName(name) {
      this.$el.find('.import-file-name').text(name);
      this.$el.find('.import-file-info').text('');
    }
    setFileIsLoaded() {
      this.$el.find('button[data-action="next"]').removeClass('hidden');
    }
    preview() {
      if (!this.formData.previewString) {
        return;
      }
      const arr = this.csvToArray(this.formData.previewString, this.formData.delimiter, this.formData.textQualifier);
      this.formData.previewArray = arr;
      const $table = $('<table>').addClass('table').addClass('table-bordered');
      const $tbody = $('<tbody>').appendTo($table);
      arr.forEach((row, i) => {
        if (i >= 3) {
          return;
        }
        const $row = $('<tr>');
        row.forEach(value => {
          const $cell = $('<td>').html(this.getHelper().sanitizeHtml(value));
          $row.append($cell);
        });
        $tbody.append($row);
      });
      const $container = $('#import-preview');
      $container.empty().append($table);
    }
    csvToArray(strData, strDelimiter, strQualifier) {
      strDelimiter = strDelimiter || ',';
      strQualifier = strQualifier || '\"';
      strDelimiter = strDelimiter.replace(/\\t/, '\t');
      const objPattern = new RegExp(
      // Delimiters.
      "(\\" + strDelimiter + "|\\r?\\n|\\r|^)" +
      // Quoted fields.
      "(?:" + strQualifier + "([^" + strQualifier + "]*(?:" + strQualifier + "" + strQualifier + "[^" + strQualifier + "]*)*)" + strQualifier + "|" +
      // Standard fields.
      "([^" + strQualifier + "\\" + strDelimiter + "\\r\\n]*))", "gi");
      const arrData = [[]];
      let arrMatches = null;
      while (arrMatches = objPattern.exec(strData)) {
        const strMatchedDelimiter = arrMatches[1];
        let strMatchedValue;
        if (strMatchedDelimiter.length && strMatchedDelimiter !== strDelimiter) {
          arrData.push([]);
        }
        strMatchedValue = arrMatches[2] ? arrMatches[2].replace(new RegExp("\"\"", "g"), "\"") : arrMatches[3];
        arrData[arrData.length - 1].push(strMatchedValue);
      }
      return arrData;
    }
    saveAsDefault() {
      const preferences = this.getPreferences();
      const importParams = Espo.Utils.cloneDeep(preferences.get('importParams') || {});
      const data = {};
      this.paramList.forEach(attribute => {
        data[attribute] = this.model.get(attribute);
      });
      importParams.default = data;
      preferences.save({
        importParams: importParams
      }).then(() => {
        Espo.Ui.success(this.translate('Saved'));
      });
      this.hideSaveAsDefaultButton();
    }
    controlFieldVisibility() {
      if (this.model.get('idleMode')) {
        this.hideField('manualMode');
      } else {
        this.showField('manualMode');
      }
      if (this.model.get('manualMode')) {
        this.hideField('idleMode');
      } else {
        this.showField('idleMode');
      }
    }
    hideField(name) {
      this.$el.find('.field[data-name="' + name + '"]').parent().addClass('hidden-cell');
    }
    showField(name) {
      this.$el.find('.field[data-name="' + name + '"]').parent().removeClass('hidden-cell');
    }
    convertFormatToLabel(format) {
      const formatItemLabelMap = {
        'YYYY': '2021',
        'DD': '27',
        'MM': '12',
        'HH': '23',
        'mm': '00',
        'hh': '11',
        'ss': '00',
        'a': 'pm',
        'A': 'PM'
      };
      let label = format;
      for (const item in formatItemLabelMap) {
        const value = formatItemLabelMap[item];
        label = label.replace(new RegExp(item, 'g'), value);
      }
      return format + ' · ' + label;
    }
    getDateFormatDataList() {
      const dateFormatList = this.getMetadata().get(['clientDefs', 'Import', 'dateFormatList']) || [];
      return dateFormatList.map(item => {
        return {
          key: item,
          label: this.convertFormatToLabel(item)
        };
      });
    }
    getTimeFormatDataList() {
      const timeFormatList = this.getMetadata().get(['clientDefs', 'Import', 'timeFormatList']) || [];
      return timeFormatList.map(item => {
        return {
          key: item,
          label: this.convertFormatToLabel(item)
        };
      });
    }
  }
  var _default = _exports.default = Step1ImportView;
});

define("views/import/list", ["exports", "views/list"], function (_exports, _list) {
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

  class ImportListView extends _list.default {
    createButton = false;
    setup() {
      super.setup();
      this.menu.buttons.unshift({
        iconHtml: '<span class="fas fa-plus fa-sm"></span>',
        text: this.translate('New Import', 'labels', 'Import'),
        link: '#Import',
        acl: 'edit'
      });
    }
  }
  var _default = _exports.default = ImportListView;
});

define("views/import/index", ["exports", "view"], function (_exports, _view) {
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

  /** @module views/import/index */

  class IndexImportView extends _view.default {
    template = 'import/index';
    formData = null;
    fileContents = null;
    data() {
      return {
        fromAdmin: this.options.fromAdmin
      };
    }
    setup() {
      this.entityType = this.options.entityType || null;
      this.startFromStep = 1;
      if (this.options.formData || this.options.fileContents) {
        this.formData = this.options.formData || {};
        this.fileContents = this.options.fileContents || null;
        this.entityType = this.formData.entityType || null;
        if (this.options.step) {
          this.startFromStep = this.options.step;
        }
      }
    }
    changeStep(num, result) {
      this.step = num;
      if (num > 1) {
        this.setConfirmLeaveOut(true);
      }
      this.createView('step', 'views/import/step' + num.toString(), {
        selector: '> .import-container',
        entityType: this.entityType,
        formData: this.formData,
        result: result
      }, view => {
        view.render();
      });
      let url = '#Import';
      if (this.options.fromAdmin && this.step === 1) {
        url = '#Admin/import';
      }
      if (this.step > 1) {
        url += '/index/step=' + this.step;
      }
      this.getRouter().navigate(url, {
        trigger: false
      });
    }
    afterRender() {
      this.changeStep(this.startFromStep);
    }
    updatePageTitle() {
      this.setPageTitle(this.getLanguage().translate('Import', 'labels', 'Admin'));
    }
    setConfirmLeaveOut(value) {
      this.getRouter().confirmLeaveOut = value;
    }
  }
  var _default = _exports.default = IndexImportView;
});

define("views/import/detail", ["exports", "views/detail"], function (_exports, _detail) {
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

  class ImportDetailView extends _detail.default {
    getHeader() {
      let name = this.getDateTime().toDisplay(this.model.get('createdAt'));
      return this.buildHeaderHtml([$('<a>').attr('href', '#' + this.model.entityType + '/list').text(this.getLanguage().translate(this.model.entityType, 'scopeNamesPlural')), $('<span>').text(name)]);
    }
    setup() {
      super.setup();
      this.setupMenu();
      this.listenTo(this.model, 'change', () => {
        this.setupMenu();
        if (this.isRendered()) {
          this.getView('header').reRender();
        }
      });
      this.listenTo(this.model, 'sync', m => {
        this.controlButtons(m);
      });
    }
    setupMenu() {
      this.addMenuItem('buttons', {
        label: "Remove Import Log",
        action: "removeImportLog",
        name: 'removeImportLog',
        style: "default",
        acl: "delete",
        title: this.translate('removeImportLog', 'messages', 'Import')
      }, true);
      this.addMenuItem('buttons', {
        label: "Revert Import",
        name: 'revert',
        action: "revert",
        style: "danger",
        acl: "edit",
        title: this.translate('revert', 'messages', 'Import'),
        hidden: !this.model.get('importedCount')
      }, true);
      this.addMenuItem('buttons', {
        label: "Remove Duplicates",
        name: 'removeDuplicates',
        action: "removeDuplicates",
        style: "default",
        acl: "edit",
        title: this.translate('removeDuplicates', 'messages', 'Import'),
        hidden: !this.model.get('duplicateCount')
      }, true);
      this.addMenuItem('dropdown', {
        label: 'New import with same params',
        name: 'createWithSameParams',
        action: 'createWithSameParams'
      });
    }
    controlButtons(model) {
      if (!model || model.hasChanged('importedCount')) {
        if (this.model.get('importedCount')) {
          this.showHeaderActionItem('revert');
        } else {
          this.hideHeaderActionItem('revert');
        }
      }
      if (!model || model.hasChanged('duplicateCount')) {
        if (this.model.get('duplicateCount')) {
          this.showHeaderActionItem('removeDuplicates');
        } else {
          this.hideHeaderActionItem('removeDuplicates');
        }
      }
    }

    // noinspection JSUnusedGlobalSymbols
    actionRemoveImportLog() {
      this.confirm(this.translate('confirmRemoveImportLog', 'messages', 'Import'), () => {
        this.disableMenuItem('removeImportLog');
        Espo.Ui.notify(this.translate('pleaseWait', 'messages'));
        this.model.destroy({
          wait: true
        }).then(() => {
          Espo.Ui.notify(false);
          var collection = this.model.collection;
          if (collection) {
            if (collection.total > 0) {
              collection.total--;
            }
          }
          this.getRouter().navigate('#Import/list', {
            trigger: true
          });
          this.removeMenuItem('removeImportLog', true);
        });
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionRevert() {
      this.confirm(this.translate('confirmRevert', 'messages', 'Import'), () => {
        this.disableMenuItem('revert');
        Espo.Ui.notify(this.translate('pleaseWait', 'messages'));
        Espo.Ajax.postRequest(`Import/${this.model.id}/revert`).then(() => {
          this.getRouter().navigate('#Import/list', {
            trigger: true
          });
        });
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionRemoveDuplicates() {
      this.confirm(this.translate('confirmRemoveDuplicates', 'messages', 'Import'), () => {
        this.disableMenuItem('removeDuplicates');
        Espo.Ui.notify(this.translate('pleaseWait', 'messages'));
        Espo.Ajax.postRequest(`Import/${this.model.id}/removeDuplicates`).then(() => {
          this.removeMenuItem('removeDuplicates', true);
          this.model.fetch();
          this.model.trigger('update-all');
          Espo.Ui.success(this.translate('duplicatesRemoved', 'messages', 'Import'));
        });
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionCreateWithSameParams() {
      let formData = this.model.get('params') || {};
      formData.entityType = this.model.get('entityType');
      formData.attributeList = this.model.get('attributeList') || [];
      formData = Espo.Utils.cloneDeep(formData);
      this.getRouter().navigate('#Import', {
        trigger: false
      });
      this.getRouter().dispatch('Import', 'index', {
        formData: formData
      });
    }
  }
  var _default = _exports.default = ImportDetailView;
});

define("views/import/record/list", ["exports", "views/record/list"], function (_exports, _list) {
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

  class ImportListRecordView extends _list.default {
    quickDetailDisabled = true;
    quickEditDisabled = true;
    checkAllResultDisabled = true;
    massActionList = ['remove'];
    rowActionsView = 'views/record/row-actions/remove-only';
  }
  var _default = _exports.default = ImportListRecordView;
});

define("views/import/record/detail", ["exports", "views/record/detail"], function (_exports, _detail) {
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

  class ImportDetailRecordView extends _detail.default {
    readOnly = true;
    returnUrl = '#Import/list';
    checkInterval = 5;
    resultPanelFetchLimit = 10;
    duplicateAction = false;
    setup() {
      super.setup();
      this.fetchCounter = 0;
      this.setupChecking();
      this.hideActionItem('delete');
    }
    setupChecking() {
      if (!this.model.has('status')) {
        this.listenToOnce(this.model, 'sync', this.setupChecking.bind(this));
        return;
      }
      if (!~['In Process', 'Pending', 'Standby'].indexOf(this.model.get('status'))) {
        return;
      }
      setTimeout(this.runChecking.bind(this), this.checkInterval * 1000);
      this.on('remove', () => {
        this.stopChecking = true;
      });
    }
    runChecking() {
      if (this.stopChecking) {
        return;
      }
      this.model.fetch().then(() => {
        const isFinished = !~['In Process', 'Pending', 'Standby'].indexOf(this.model.get('status'));
        if (this.fetchCounter < this.resultPanelFetchLimit && !isFinished) {
          this.fetchResultPanels();
        }
        if (isFinished) {
          this.fetchResultPanels();
          return;
        }
        setTimeout(this.runChecking.bind(this), this.checkInterval * 1000);
      });
      this.fetchCounter++;
    }
    fetchResultPanels() {
      const bottomView = this.getView('bottom');
      if (!bottomView) {
        return;
      }
      const importedView = bottomView.getView('imported');
      if (importedView && importedView.collection) {
        importedView.collection.fetch();
      }
      const duplicatesView = bottomView.getView('duplicates');
      if (duplicatesView && duplicatesView.collection) {
        duplicatesView.collection.fetch();
      }
      const updatedView = bottomView.getView('updated');
      if (updatedView && updatedView.collection) {
        updatedView.collection.fetch();
      }
    }
  }
  var _default = _exports.default = ImportDetailRecordView;
});

define("views/import/record/row-actions/duplicates", ["exports", "views/record/row-actions/default"], function (_exports, _default2) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _default2 = _interopRequireDefault(_default2);
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

  class ImportDuplicatesRowActionsView extends _default2.default {
    getActionList() {
      const list = super.getActionList();
      list.push({
        action: 'unmarkAsDuplicate',
        label: 'Set as Not Duplicate',
        data: {
          id: this.model.id,
          type: this.model.entityType
        }
      });
      return list;
    }
  }
  var _default = _exports.default = ImportDuplicatesRowActionsView;
});

define("views/import/record/panels/updated", ["exports", "views/import/record/panels/imported"], function (_exports, _imported) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _imported = _interopRequireDefault(_imported);
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

  class ImportUpdatedPanelView extends _imported.default {
    link = 'updated';
    rowActionsView = 'views/record/row-actions/relationship-view-and-edit';
    setup() {
      this.title = this.title || this.translate('Updated', 'labels', 'Import');
      super.setup();
    }
  }
  var _default = _exports.default = ImportUpdatedPanelView;
});

define("views/import/record/panels/duplicates", ["exports", "views/import/record/panels/imported"], function (_exports, _imported) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _imported = _interopRequireDefault(_imported);
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

  class ImportDuplicatesPanelView extends _imported.default {
    link = 'duplicates';
    setup() {
      this.title = this.title || this.translate('Duplicates', 'labels', 'Import');
      super.setup();
    }

    // noinspection JSUnusedGlobalSymbols
    actionUnmarkAsDuplicate(data) {
      const id = data.id;
      const type = data.type;
      this.confirm(this.translate('confirmation', 'messages'), () => {
        Espo.Ajax.postRequest(`Import/${this.model.id}/unmarkDuplicates`, {
          entityId: id,
          entityType: type
        }).then(() => {
          this.collection.fetch();
        });
      });
    }
  }
  var _default = _exports.default = ImportDuplicatesPanelView;
});

define("views/group-email-folder/list", ["exports", "views/list"], function (_exports, _list) {
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

  class GroupEmailFolderListView extends _list.default {
    quickCreate = true;
    setup() {
      super.setup();
      if (this.options.params.fromAdmin) {
        this.hideHeaderActionItem('emails');
      }
    }
  }
  var _default = _exports.default = GroupEmailFolderListView;
});

define("views/group-email-folder/record/list", ["exports", "views/record/list"], function (_exports, _list) {
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
    rowActionsView = 'views/email-folder/record/row-actions/default';

    // noinspection JSUnusedGlobalSymbols
    async actionMoveUp(data) {
      const model = this.collection.get(data.id);
      if (!model) {
        return;
      }
      const index = this.collection.indexOf(model);
      if (index === 0) {
        return;
      }
      Espo.Ui.notifyWait();
      await Espo.Ajax.postRequest('GroupEmailFolder/action/moveUp', {
        id: model.id
      });
      await this.collection.fetch();
      Espo.Ui.notify(false);
    }

    // noinspection JSUnusedGlobalSymbols
    async actionMoveDown(data) {
      const model = this.collection.get(data.id);
      if (!model) {
        return;
      }
      const index = this.collection.indexOf(model);
      if (index === this.collection.length - 1 && this.collection.length === this.collection.total) {
        return;
      }
      Espo.Ui.notifyWait();
      await Espo.Ajax.postRequest('GroupEmailFolder/action/moveDown', {
        id: model.id
      });
      await this.collection.fetch();
      Espo.Ui.notify(false);
    }
  }
  _exports.default = _default;
});

define("views/group-email-folder/record/edit-small", ["exports", "views/record/edit"], function (_exports, _edit) {
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

  class _default extends _edit.default {
    afterSave() {
      this.getBaseController().clearScopeStoredMainView('Email');
      super.afterSave();
    }
  }
  _exports.default = _default;
});

define("views/group-email-folder/record/row-actions/default", ["exports", "views/record/row-actions/default"], function (_exports, _default2) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _default2 = _interopRequireDefault(_default2);
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

  class _default extends _default2.default {
    getActionList() {
      const list = super.getActionList();
      if (this.options.acl.edit) {
        list.unshift({
          action: 'moveDown',
          label: 'Move Down',
          data: {
            id: this.model.id
          }
        });
        list.unshift({
          action: 'moveUp',
          label: 'Move Up',
          data: {
            id: this.model.id
          }
        });
      }
      return list;
    }
  }
  _exports.default = _default;
});

define("views/external-account/oauth2", ["exports", "view", "model"], function (_exports, _view, _model) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _view = _interopRequireDefault(_view);
  _model = _interopRequireDefault(_model);
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
   * @internal Do not extend.
   */
  class ExternalAccountOauth2View extends _view.default {
    template = 'external-account/oauth2';
    data() {
      return {
        integration: this.integration,
        helpText: this.helpText,
        isConnected: this.isConnected
      };
    }
    isConnected = false;
    setup() {
      this.addActionHandler('connect', () => this.connect());
      this.addActionHandler('save', () => this.save());
      this.addActionHandler('cancel', () => this.getRouter().navigate('#ExternalAccount', {
        trigger: true
      }));
      this.integration = this.options.integration;
      this.id = this.options.id;
      this.helpText = false;
      if (this.getLanguage().has(this.integration, 'help', 'ExternalAccount')) {
        this.helpText = this.translate(this.integration, 'help', 'ExternalAccount');
      }
      this.fieldList = [];
      this.dataFieldList = [];
      this.model = new _model.default();
      this.model.id = this.id;
      this.model.entityType = this.model.name = 'ExternalAccount';
      this.model.urlRoot = 'ExternalAccount';
      this.model.defs = {
        fields: {
          enabled: {
            required: true,
            type: 'bool'
          }
        }
      };
      this.wait(true);
      this.model.populateDefaults();
      this.listenToOnce(this.model, 'sync', () => {
        this.createFieldView('bool', 'enabled');
        Espo.Ajax.getRequest('ExternalAccount/action/getOAuth2Info?id=' + this.id).then(response => {
          this.clientId = response.clientId;
          this.redirectUri = response.redirectUri;
          if (response.isConnected) {
            this.isConnected = true;
          }
          this.wait(false);
        });
      });
      this.model.fetch();
    }
    hideField(name) {
      this.$el.find(`label[data-name="${name}"]`).addClass('hide');
      this.$el.find(`div.field[data-name="${name}"]`).addClass('hide');
      const view = this.getView(name);
      if (view) {
        view.disabled = true;
      }
    }
    showField(name) {
      this.$el.find(`label[data-name="${name}"]`).removeClass('hide');
      this.$el.find(`div.field[data-name="${name}"]`).removeClass('hide');
      const view = this.getView(name);
      if (view) {
        view.disabled = false;
      }
    }
    afterRender() {
      if (!this.model.get('enabled')) {
        this.$el.find('.data-panel').addClass('hidden');
      }
      this.listenTo(this.model, 'change:enabled', () => {
        if (this.model.get('enabled')) {
          this.$el.find('.data-panel').removeClass('hidden');
        } else {
          this.$el.find('.data-panel').addClass('hidden');
        }
      });
    }
    createFieldView(type, name, readOnly, params) {
      this.createView(name, this.getFieldManager().getViewName(type), {
        model: this.model,
        selector: '.field[data-name="' + name + '"]',
        defs: {
          name: name,
          params: params
        },
        mode: readOnly ? 'detail' : 'edit',
        readOnly: readOnly
      });
      this.fieldList.push(name);
    }
    save() {
      this.fieldList.forEach(field => {
        const view = /** @type {import('views/fields/base').default} */this.getView(field);
        if (!view.readOnly) {
          view.fetchToModel();
        }
      });
      let notValid = false;
      this.fieldList.forEach(field => {
        const view = /** @type {import('views/fields/base').default} */this.getView(field);
        notValid = view.validate() || notValid;
      });
      if (notValid) {
        Espo.Ui.error(this.translate('Not valid'));
        return;
      }
      this.listenToOnce(this.model, 'sync', () => {
        Espo.Ui.success(this.translate('Saved'));
        if (!this.model.get('enabled')) {
          this.setNotConnected();
        }
      });
      Espo.Ui.notify(this.translate('saving', 'messages'));
      this.model.save();
    }
    popup(options, callback) {
      options.windowName = options.windowName || 'ConnectWithOAuth';
      options.windowOptions = options.windowOptions || 'location=0,status=0,width=800,height=400';
      options.callback = options.callback || function () {
        window.location.reload();
      };
      const self = this;
      let path = options.path;
      const arr = [];
      const params = options.params || {};
      for (const name in params) {
        if (params[name]) {
          arr.push(name + '=' + encodeURI(params[name]));
        }
      }
      path += '?' + arr.join('&');
      const parseUrl = str => {
        let code = null;
        let error = null;
        str = str.substr(str.indexOf('?') + 1, str.length);
        str.split('&').forEach(part => {
          const arr = part.split('=');
          const name = decodeURI(arr[0]);
          const value = decodeURI(arr[1] || '');
          if (name === 'code') {
            code = value;
          }
          if (name === 'error') {
            error = value;
          }
        });
        if (code) {
          return {
            code: code
          };
        } else if (error) {
          return {
            error: error
          };
        }
      };
      const popup = window.open(path, options.windowName, options.windowOptions);
      let interval;
      interval = window.setInterval(() => {
        if (popup.closed) {
          window.clearInterval(interval);
          return;
        }
        const res = parseUrl(popup.location.href.toString());
        if (res) {
          callback.call(self, res);
          popup.close();
          window.clearInterval(interval);
        }
      }, 500);
    }
    connect() {
      this.popup({
        path: this.getMetadata().get(`integrations.${this.integration}.params.endpoint`),
        params: {
          client_id: this.clientId,
          redirect_uri: this.redirectUri,
          scope: this.getMetadata().get(`integrations.${this.integration}.params.scope`),
          response_type: 'code',
          access_type: 'offline',
          approval_prompt: 'force'
        }
      }, response => {
        if (response.error) {
          Espo.Ui.notify(false);
          return;
        }
        if (!response.code) {
          Espo.Ui.error(this.translate('Error occurred'));
          return;
        }
        this.$el.find('[data-action="connect"]').addClass('disabled');
        Espo.Ajax.postRequest('ExternalAccount/action/authorizationCode', {
          id: this.id,
          code: response.code
        }).then(response => {
          Espo.Ui.notify(false);
          if (response === true) {
            this.setConnected();
          } else {
            this.setNotConnected();
          }
          this.$el.find('[data-action="connect"]').removeClass('disabled');
        }).catch(() => {
          this.$el.find('[data-action="connect"]').removeClass('disabled');
        });
      });
    }
    setConnected() {
      this.isConnected = true;
      this.$el.find('[data-action="connect"]').addClass('hidden');
      this.$el.find('.connected-label').removeClass('hidden');
    }
    setNotConnected() {
      this.isConnected = false;
      this.$el.find('[data-action="connect"]').removeClass('hidden');
      this.$el.find('.connected-label').addClass('hidden');
    }
  }

  // noinspection JSUnusedGlobalSymbols
  var _default = _exports.default = ExternalAccountOauth2View;
});

define("views/external-account/index", ["exports", "view"], function (_exports, _view) {
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

  class ExternalAccountIndex extends _view.default {
    template = 'external-account/index';
    data() {
      return {
        externalAccountList: this.externalAccountList,
        id: this.id,
        externalAccountListCount: this.externalAccountList.length
      };
    }
    setup() {
      this.addHandler('click', '#external-account-menu a.external-account-link', (e, target) => {
        const id = `${target.dataset.id}__${this.userId}`;
        this.openExternalAccount(id);
      });
      this.externalAccountList = this.collection.models.map(model => model.getClonedAttributes());
      this.userId = this.getUser().id;
      this.id = this.options.id || null;
      if (this.id) {
        this.userId = this.id.split('__')[1];
      }
      this.on('after:render', () => {
        this.renderHeader();
        if (!this.id) {
          this.renderDefaultPage();
        } else {
          this.openExternalAccount(this.id);
        }
      });
    }
    openExternalAccount(id) {
      this.id = id;
      const integration = this.integration = id.split('__')[0];
      this.userId = id.split('__')[1];
      this.getRouter().navigate(`#ExternalAccount/edit/${id}`, {
        trigger: false
      });
      const authMethod = this.getMetadata().get(['integrations', integration, 'authMethod']);
      const viewName = this.getMetadata().get(['integrations', integration, 'userView']) || 'views/external-account/' + Espo.Utils.camelCaseToHyphen(authMethod);
      Espo.Ui.notifyWait();
      this.createView('content', viewName, {
        fullSelector: '#external-account-content',
        id: id,
        integration: integration
      }, view => {
        this.renderHeader();
        view.render();
        Espo.Ui.notify(false);
        $(window).scrollTop(0);
        this.controlCurrentLink(id);
      });
    }
    controlCurrentLink() {
      const id = this.integration;
      this.element.querySelectorAll('.external-account-link').forEach(element => {
        element.classList.remove('disabled', 'text-muted');
      });
      const currentLink = this.element.querySelector(`.external-account-link[data-id="${id}"]`);
      if (currentLink) {
        currentLink.classList.add('disabled', 'text-muted');
      }
    }
    renderDefaultPage() {
      $('#external-account-header').html('').hide();
      $('#external-account-content').html('');
    }
    renderHeader() {
      const $header = $('#external-account-header');
      if (!this.id) {
        $header.html('');
        return;
      }
      $header.show().text(this.integration);
    }
    updatePageTitle() {
      this.setPageTitle(this.translate('ExternalAccount', 'scopeNamesPlural'));
    }
  }
  var _default = _exports.default = ExternalAccountIndex;
});

define("views/email-account/list", ["exports", "views/list"], function (_exports, _list) {
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

  class EmailAccountListView extends _list.default {
    keepCurrentRootUrl = true;
    setup() {
      this.options.params = this.options.params || {};
      const params = this.options.params || {};
      this.userId = params.userId;
      super.setup();
      if (this.userId) {
        this.collection.where = [{
          type: 'equals',
          field: 'assignedUserId',
          value: params.userId
        }];
      }
    }
    setupSearchPanel() {
      if (this.userId || !this.getUser().isAdmin()) {
        this.searchPanel = false;
        this.searchManager.reset();
        return;
      }
      super.setupSearchPanel();
    }
    getCreateAttributes() {
      const attributes = {};
      if (this.options.params.userId) {
        attributes.assignedUserId = this.options.params.userId;
        attributes.assignedUserName = this.options.params.userName || this.options.params.userId;
      }
      return attributes;
    }
  }
  var _default = _exports.default = EmailAccountListView;
});

define("views/email-account/record/list", ["exports", "views/record/list"], function (_exports, _list) {
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
    quickDetailDisabled = true;
    quickEditDisabled = true;
    checkAllResultDisabled = true;
    massActionList = ['remove', 'massUpdate'];
  }
  _exports.default = _default;
});

define("views/email-account/record/edit", ["exports", "views/record/edit", "views/email-account/record/detail"], function (_exports, _edit, _detail) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _edit = _interopRequireDefault(_edit);
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

  class _default extends _edit.default {
    setup() {
      super.setup();
      _detail.default.prototype.setupFieldsBehaviour.call(this);
      _detail.default.prototype.initSslFieldListening.call(this);
      _detail.default.prototype.initSmtpFieldsControl.call(this);
      if (this.getUser().isAdmin()) {
        this.setFieldNotReadOnly('assignedUser');
      } else {
        this.setFieldReadOnly('assignedUser');
      }
    }
    modifyDetailLayout(layout) {
      _detail.default.prototype.modifyDetailLayout.call(this, layout);
    }
    setupFieldsBehaviour() {
      _detail.default.prototype.setupFieldsBehaviour.call(this);
    }
    controlStatusField() {
      _detail.default.prototype.controlStatusField.call(this);
    }
    controlSmtpFields() {
      _detail.default.prototype.controlSmtpFields.call(this);
    }
    controlSmtpAuthField() {
      _detail.default.prototype.controlSmtpAuthField.call(this);
    }
    wasFetched() {
      _detail.default.prototype.wasFetched.call(this);
    }
  }
  _exports.default = _default;
});

define("views/email-account/modals/select-folder", ["exports", "views/modal"], function (_exports, _modal) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _modal = _interopRequireDefault(_modal);
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

  class _default extends _modal.default {
    cssName = 'select-folder-modal';
    template = 'email-account/modals/select-folder';
    data() {
      return {
        folders: this.options.folders
      };
    }
    setup() {
      this.headerText = this.translate('Select');
      this.addActionHandler('select', (event, target) => {
        const value = target.dataset.value;
        this.trigger('select', value);
      });
    }
  }
  _exports.default = _default;
});

define("views/email-account/fields/email-folder", ["exports", "views/fields/link"], function (_exports, _link) {
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
    createDisabled = true;
    autocompleteDisabled = true;
    getSelectFilters() {
      if (this.getUser().isAdmin() && this.model.get('assignedUserId')) {
        return {
          assignedUser: {
            type: 'equals',
            attribute: 'assignedUserId',
            value: this.model.get('assignedUserId'),
            data: {
              type: 'is',
              nameValue: this.model.get('assignedUserName')
            }
          }
        };
      }
    }
    setup() {
      super.setup();
      this.listenTo(this.model, 'change:assignedUserId', (model, e, o) => {
        if (!o.ui) {
          return;
        }
        this.model.set({
          emailFolderId: null,
          emailFolderName: null
        });
      });
    }
  }
  _exports.default = _default;
});

define("views/email-account/fields/email-address", ["exports", "views/fields/email-address"], function (_exports, _emailAddress) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _emailAddress = _interopRequireDefault(_emailAddress);
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

  class _default extends _emailAddress.default {
    setup() {
      super.setup();
      this.on('change', () => {
        const emailAddress = this.model.get('emailAddress');
        this.model.set('name', emailAddress);
      });
      const userId = this.model.get('assignedUserId');
      if (this.getUser().isAdmin() && userId !== this.getUser().id) {
        Espo.Ajax.getRequest(`User/${userId}`).then(data => {
          const list = [];
          if (data.emailAddress) {
            list.push(data.emailAddress);
            this.params.options = list;
            if (data.emailAddressData) {
              data.emailAddressData.forEach(item => {
                if (item.emailAddress === data.emailAddress) {
                  return;
                }
                list.push(item.emailAddress);
              });
            }
            this.reRender();
          }
        });
      }
    }
    setupOptions() {
      if (this.model.get('assignedUserId') === this.getUser().id) {
        this.params.options = this.getUser().get('userEmailAddressList');
      }
    }
  }
  _exports.default = _default;
});


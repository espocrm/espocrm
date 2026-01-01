define("views/admin/integrations/edit", ["exports", "view", "model"], function (_exports, _view, _model) {
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

  class IntegrationsEditView extends _view.default {
    template = 'admin/integrations/edit';

    /**
     * @protected
     * @type {string}
     */
    integration;

    /**
     * @private
     * @type {string|null}
     */
    helpText = null;

    /**
     * @private
     * @type {{name: string, label: string}[]}
     */
    fieldDataList;

    /**
     * @private
     * @type {string[]}
     */
    fieldList;
    data() {
      return {
        integration: this.integration,
        fieldDataList: this.fieldDataList,
        helpText: this.helpText
      };
    }
    setup() {
      this.addActionHandler('save', () => this.save());
      this.addActionHandler('cancel', () => this.actionCancel());
      this.integration = this.options.integration;
      if (this.getLanguage().has(this.integration, 'help', 'Integration')) {
        this.helpText = this.translate(this.integration, 'help', 'Integration');
      }
      this.fieldList = [];
      this.fieldDataList = [];
      this.model = new _model.default({}, {
        entityType: 'Integration',
        urlRoot: 'Integration'
      });
      this.model.id = this.integration;
      const fieldDefs = {
        enabled: {
          required: true,
          type: 'bool'
        }
      };
      const fields = /** @type {Record<string, Record>} */
      this.getMetadata().get(`integrations.${this.integration}.fields`) ?? {};
      Object.keys(fields).forEach(name => {
        const defs = {
          ...fields[name]
        };
        fieldDefs[name] = defs;
        let label = this.translate(name, 'fields', 'Integration');
        if (defs.labelTranslation) {
          label = this.getLanguage().translatePath(defs.labelTranslation);
        }
        this.fieldDataList.push({
          name: name,
          label: label
        });
      });
      this.model.setDefs({
        fields: fieldDefs
      });
      this.model.populateDefaults();
      this.wait((async () => {
        await this.model.fetch();
        this.createFieldView('bool', 'enabled');
        Object.keys(fields).forEach(name => {
          this.createFieldView(fields[name].type, name, undefined, fields[name]);
        });
      })());
    }

    /**
     * @private
     */
    actionCancel() {
      this.getRouter().navigate('#Admin/integrations', {
        trigger: true
      });
    }

    /**
     * @protected
     * @param {string} name
     */
    hideField(name) {
      this.$el.find('label[data-name="' + name + '"]').addClass('hide');
      this.$el.find('div.field[data-name="' + name + '"]').addClass('hide');
      const view = this.getView(name);
      if (view) {
        view.disabled = true;
      }
    }

    /**
     * @protected
     * @param {string} name
     */
    showField(name) {
      this.$el.find(`label[data-name="${name}"]`).removeClass('hide');
      this.$el.find(`div.field[data-name="${name}"]`).removeClass('hide');
      const view = this.getFieldView(name);
      if (view) {
        view.disabled = false;
      }
    }

    /**
     * @since 9.0.0
     * @param {string} name
     * @return {import('views/fields/base').default}
     */
    getFieldView(name) {
      return this.getView(name);
    }
    afterRender() {
      if (!this.model.attributes.enabled) {
        this.fieldDataList.forEach(it => this.hideField(it.name));
      }
      this.listenTo(this.model, 'change:enabled', () => {
        if (this.model.attributes.enabled) {
          this.fieldDataList.forEach(it => this.showField(it.name));
        } else {
          this.fieldDataList.forEach(it => this.hideField(it.name));
        }
      });
    }

    /**
     * @protected
     * @param {string} type
     * @param {string} name
     * @param {boolean} [readOnly]
     * @param {Record} [params]
     */
    createFieldView(type, name, readOnly, params) {
      const viewName = this.model.getFieldParam(name, 'view') || this.getFieldManager().getViewName(type);
      let labelText = undefined;
      if (params && params.labelTranslation) {
        labelText = this.getLanguage().translatePath(params.labelTranslation);
      }
      this.createView(name, viewName, {
        name: name,
        model: this.model,
        selector: `.field[data-name="${name}"]`,
        params: params,
        mode: readOnly ? 'detail' : 'edit',
        readOnly: readOnly,
        labelText: labelText
      });
      this.fieldList.push(name);
    }

    /**
     * @protected
     */
    save() {
      this.fieldList.forEach(field => {
        const view = this.getFieldView(field);
        if (!view.readOnly) {
          view.fetchToModel();
        }
      });
      let notValid = false;
      this.fieldList.forEach(field => {
        const fieldView = this.getFieldView(field);
        if (fieldView && !fieldView.disabled) {
          notValid = fieldView.validate() || notValid;
        }
      });
      if (notValid) {
        Espo.Ui.error(this.translate('Not valid'));
        return;
      }
      this.listenToOnce(this.model, 'sync', () => {
        Espo.Ui.success(this.translate('Saved'));
      });
      Espo.Ui.notify(this.translate('saving', 'messages'));
      this.model.save();
    }
  }
  _exports.default = IntegrationsEditView;
});
//# sourceMappingURL=edit.js.map ;
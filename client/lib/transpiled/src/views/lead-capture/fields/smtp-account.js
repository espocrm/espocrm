define("views/lead-capture/fields/smtp-account", ["exports", "views/fields/enum"], function (_exports, _enum) {
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
    /**
     * @private
     * @type {string}
     */
    dataUrl = 'LeadCapture/action/smtpAccountDataList';
    getAttributeList() {
      return [this.name, 'inboundEmailId'];
    }
    data() {
      const data = super.data();
      data.valueIsSet = this.model.has('inboundEmailId');
      data.isNotEmpty = this.model.has('inboundEmailId');
      data.value = this.getValueForDisplay();
      data.valueTranslated = data.value != null ? this.translatedOptions[data.value] : undefined;
      return data;
    }
    setupOptions() {
      super.setupOptions();
      this.params.options = [];
      this.translatedOptions = {};
      this.params.options.push('');
      if (!this.loadedOptionList) {
        if (this.model.get('inboundEmailId')) {
          const item = 'inboundEmail:' + this.model.get('inboundEmailId');
          this.params.options.push(item);
          this.translatedOptions[item] = (this.model.get('inboundEmailName') || this.model.get('inboundEmailId')) + ' (' + this.translate('group', 'labels', 'MassEmail') + ')';
        }
      } else {
        this.loadedOptionList.forEach(item => {
          this.params.options.push(item);
          this.translatedOptions[item] = (this.loadedOptionTranslations[item] || item) + ' (' + this.translate('group', 'labels', 'MassEmail') + ')';
        });
      }
      this.translatedOptions[''] = this.getConfig().get('outboundEmailFromAddress') + ' (' + this.translate('system', 'labels', 'MassEmail') + ')';
    }
    getValueForDisplay() {
      if (!this.model.has(this.name)) {
        if (this.model.has('inboundEmailId')) {
          if (this.model.get('inboundEmailId')) {
            return 'inboundEmail:' + this.model.get('inboundEmailId');
          } else {
            return '';
          }
        } else {
          return '';
        }
      }
      return this.model.get(this.name);
    }
    setup() {
      super.setup();
      if (this.getAcl().checkScope('MassEmail', 'create') || this.getAcl().checkScope('MassEmail', 'edit')) {
        Espo.Ajax.getRequest(this.dataUrl).then(dataList => {
          if (!dataList.length) {
            return;
          }
          this.loadedOptionList = [];
          this.loadedOptionTranslations = {};
          this.loadedOptionAddresses = {};
          this.loadedOptionFromNames = {};
          dataList.forEach(item => {
            this.loadedOptionList.push(item.key);
            this.loadedOptionTranslations[item.key] = item.emailAddress;
            this.loadedOptionAddresses[item.key] = item.emailAddress;
            this.loadedOptionFromNames[item.key] = item.fromName || '';
          });
          this.setupOptions();
          this.reRender();
        });
      }
    }
    fetch() {
      const data = {};
      const value = this.$element.val();
      data[this.name] = value;
      if (!value || value === '') {
        data.inboundEmailId = null;
        data.inboundEmailName = null;
      } else {
        const arr = value.split(':');
        if (arr.length > 1) {
          data.inboundEmailId = arr[1];
          data.inboundEmailName = this.translatedOptions[data.inboundEmailId] || data.inboundEmailId;
        }
      }
      return data;
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=smtp-account.js.map ;
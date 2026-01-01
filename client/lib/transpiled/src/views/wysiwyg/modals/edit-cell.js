define("views/wysiwyg/modals/edit-cell", ["exports", "views/modal", "views/record/edit-for-modal", "model", "views/fields/varchar", "views/fields/colorpicker", "views/fields/enum"], function (_exports, _modal, _editForModal, _model, _varchar, _colorpicker, _enum) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _modal = _interopRequireDefault(_modal);
  _editForModal = _interopRequireDefault(_editForModal);
  _model = _interopRequireDefault(_model);
  _varchar = _interopRequireDefault(_varchar);
  _colorpicker = _interopRequireDefault(_colorpicker);
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

  class EditCellModalView extends _modal.default {
    templateContent = `
        <div class="record no-side-margin">{{{record}}}</div>
    `;
    /**
     * @param {{
     *     params: {
     *         width: null|string,
     *         height: null|height,
     *         backgroundColor: null|string,
     *         verticalAlign: null|'top'|'middle'|'bottom',
     *    },
     *    onApply: function({
     *         width: null|string,
     *         height: null|height,
     *         backgroundColor: null|string,
     *         verticalAlign: null|'top'|'middle'|'bottom',
     *    }),
     * }} options
     */
    constructor(options) {
      super(options);
      this.params = options.params;
      this.onApply = options.onApply;
    }
    setup() {
      this.addButton({
        name: 'apply',
        style: 'primary',
        label: 'Apply',
        onClick: () => this.apply()
      });
      this.addButton({
        name: 'cancel',
        label: 'Cancel',
        onClick: () => this.close()
      });
      this.shortcutKeys = {
        'Control+Enter': () => this.apply()
      };
      this.model = new _model.default({
        width: this.params.width,
        height: this.params.height,
        backgroundColor: this.params.backgroundColor,
        verticalAlign: this.params.verticalAlign
      });
      this.recordView = new _editForModal.default({
        model: this.model,
        detailLayout: [{
          rows: [[{
            view: new _varchar.default({
              name: 'width',
              labelText: this.translate('width', 'wysiwygLabels'),
              params: {
                maxLength: 12
              }
            })
          }, {
            view: new _varchar.default({
              name: 'height',
              labelText: this.translate('height', 'wysiwygLabels'),
              params: {
                maxLength: 12
              }
            })
          }], [{
            view: new _enum.default({
              name: 'verticalAlign',
              labelText: this.translate('verticalAlign', 'wysiwygLabels'),
              params: {
                options: ['', 'top', 'middle', 'bottom'],
                translation: 'Global.wysiwygOptions.verticalAlign'
              }
            })
          }, {
            view: new _colorpicker.default({
              name: 'backgroundColor',
              labelText: this.translate('backgroundColor', 'wysiwygLabels')
            })
          }]]
        }]
      });
      this.assignView('record', this.recordView, '.record');
    }
    apply() {
      if (this.recordView.validate()) {
        return;
      }
      let width = this.model.attributes.width;
      if (/^\d+$/.test(width)) {
        width += 'px';
      }
      let height = this.model.attributes.height;
      if (/^\d+$/.test(height)) {
        height += 'px';
      }
      this.onApply({
        width: width,
        height: height,
        backgroundColor: this.model.attributes.backgroundColor,
        verticalAlign: this.model.attributes.verticalAlign
      });
      this.close();
    }
  }
  var _default = _exports.default = EditCellModalView;
});
//# sourceMappingURL=edit-cell.js.map ;
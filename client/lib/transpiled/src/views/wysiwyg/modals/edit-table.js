define("views/wysiwyg/modals/edit-table", ["exports", "views/modal", "views/record/edit-for-modal", "model", "views/fields/enum", "views/fields/varchar", "views/fields/colorpicker"], function (_exports, _modal, _editForModal, _model, _enum, _varchar, _colorpicker) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _modal = _interopRequireDefault(_modal);
  _editForModal = _interopRequireDefault(_editForModal);
  _model = _interopRequireDefault(_model);
  _enum = _interopRequireDefault(_enum);
  _varchar = _interopRequireDefault(_varchar);
  _colorpicker = _interopRequireDefault(_colorpicker);
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

  class EditTableModalView extends _modal.default {
    templateContent = `
        <div class="record no-side-margin">{{{record}}}</div>
    `;
    /**
     * @param {{
     *     params: {
     *         align: null|'left'|'center'|'right',
     *         width: null|string,
     *         height: null|string,
     *         borderWidth: null|string,
     *         borderColor: null|string,
     *         cellPadding: null|string,
     *         backgroundColor: null|string,
     *    },
     *    onApply: function({
     *         align: null|'left'|'center'|'right',
     *         width: null|string,
     *         height: null|string,
     *         borderWidth: null|string,
     *         borderColor: null|string,
     *         cellPadding: null|string,
     *         backgroundColor: null|string,
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
        align: this.params.align,
        width: this.params.width,
        height: this.params.height,
        borderWidth: this.params.borderWidth,
        borderColor: this.params.borderColor,
        cellPadding: this.params.cellPadding,
        backgroundColor: this.params.backgroundColor
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
            view: new _varchar.default({
              name: 'borderWidth',
              labelText: this.translate('borderWidth', 'wysiwygLabels'),
              params: {
                maxLength: 12
              }
            })
          }, {
            view: new _colorpicker.default({
              name: 'borderColor',
              labelText: this.translate('borderColor', 'wysiwygLabels')
            })
          }], [{
            view: new _varchar.default({
              name: 'cellPadding',
              labelText: this.translate('cellPadding', 'wysiwygLabels'),
              params: {
                maxLength: 12
              }
            })
          }, {
            view: new _colorpicker.default({
              name: 'backgroundColor',
              labelText: this.translate('backgroundColor', 'wysiwygLabels')
            })
          }], [{
            view: new _enum.default({
              name: 'align',
              labelText: this.translate('align', 'wysiwygLabels'),
              params: {
                options: ['', 'left', 'center', 'right'],
                translation: 'Global.wysiwygOptions.align'
              }
            })
          }, false]]
        }]
      });
      this.assignView('record', this.recordView, '.record');
    }
    apply() {
      if (this.recordView.validate()) {
        return;
      }
      let borderWidth = this.model.attributes.borderWidth;
      let cellPadding = this.model.attributes.cellPadding;
      let width = this.model.attributes.width;
      let height = this.model.attributes.height;
      if (/^\d+$/.test(borderWidth)) {
        borderWidth += 'px';
      }
      if (/^\d+$/.test(cellPadding)) {
        cellPadding += 'px';
      }
      if (/^\d+$/.test(width)) {
        width += 'px';
      }
      if (/^\d+$/.test(height)) {
        height += 'px';
      }
      this.onApply({
        align: this.model.attributes.align,
        width: width,
        height: height,
        borderWidth: borderWidth,
        borderColor: this.model.attributes.borderColor,
        cellPadding: cellPadding,
        backgroundColor: this.model.attributes.backgroundColor
      });
      this.close();
    }
  }
  var _default = _exports.default = EditTableModalView;
});
//# sourceMappingURL=edit-table.js.map ;
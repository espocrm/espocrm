define("views/fields/array", ["exports", "views/fields/base", "helpers/reg-exp-pattern", "ui/multi-select", "views/modal", "model", "views/record/edit-for-modal", "views/fields/varchar"], function (_exports, _base, _regExpPattern, _multiSelect, _modal, _model, _editForModal, _varchar) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _base = _interopRequireDefault(_base);
  _regExpPattern = _interopRequireDefault(_regExpPattern);
  _multiSelect = _interopRequireDefault(_multiSelect);
  _modal = _interopRequireDefault(_modal);
  _model = _interopRequireDefault(_model);
  _editForModal = _interopRequireDefault(_editForModal);
  _varchar = _interopRequireDefault(_varchar);
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

  /** @module views/fields/array */

  /**
   * An array field.
   *
   * @extends BaseFieldView<module:views/fields/array~params>
   */
  class ArrayFieldView extends _base.default {
    /**
     * @typedef {Object} module:views/fields/array~options
     * @property {
     *     module:views/fields/array~params &
     *     module:views/fields/base~params &
     *     Record
     * } [params] Parameters.
     */

    /**
     * @typedef {Object} module:views/fields/array~params
     * @property {string} [translation] A translation string. E.g. `Global.scopeNames`.
     * @property {string[]} [options] Select options.
     * @property {boolean} [required] Required.
     * @property {boolean} [displayAsList] Display as list (line breaks).
     * @property {boolean} [displayAsLabel] Display as label.
     * @property {string|'state'} [labelType] A label type.
     * @property {boolean} [noEmptyString] No empty string.
     * @property {string} [optionsReference] A reference to options. E.g. `Account.industry`.
     * @property {string} [optionsPath] An options metadata path.
     * @property {boolean} [isSorted] To sort options.
     * @property {Object.<string, string>} [translatedOptions] Option translations.
     * @property {Object.<string, 'warning'|'danger'|'success'|'info'|'primary'>} [style] A style map.
     * @property {number} [maxCount] A max number of items.
     * @property {boolean} [allowCustomOptions] Allow custom options.
     * @property {string} [pattern] A regular expression pattern.
     * @property {boolean} [keepItems] Disable the ability to add or remove items. Reordering is allowed.
     * @property {number} [maxItemLength] Max item length. If not specified, 100 is used. As of v9.1.
     * @property {boolean} [itemsEditable] Items are editable. As of v9.2.
     */

    /**
     * @param {
     *     module:views/fields/array~options &
     *     module:views/fields/base~options
     * } options Options.
     */
    constructor(options) {
      super(options);
    }
    type = 'array';
    listTemplate = 'fields/array/list';
    listLinkTemplate = 'fields/array/list-link';
    detailTemplate = 'fields/array/detail';
    editTemplate = 'fields/array/edit';
    searchTemplate = 'fields/array/search';
    searchTypeList = ['anyOf', 'noneOf', 'allOf', 'isEmpty', 'isNotEmpty'];
    maxItemLength = null;

    /**
     * @inheritDoc
     * @type {Array<(function (): boolean)|string>}
     */
    validations = ['required', 'maxCount'];
    MAX_ITEM_LENGTH = 100;

    /**
     * An add-item model view.
     *
     * @protected
     * @type {string}
     */
    addItemModalView = 'views/modals/array-field-add';
    /**
     * @protected
     * @type {string}
     */
    itemDelimiter = ':,:';
    /**
     * @protected
     * @type {boolean}
     */
    matchAnyWord = true;
    /**
     * @protected
     * @type {Object|null}
     */
    translatedOptions = null;

    /**
     * @protected
     * @type {boolean}
     * @since 9.2.0
     */
    noDragHandle = false;

    /**
     * @protected
     * @type {string[]}
     */
    selected;

    // noinspection JSCheckFunctionSignatures
    /** @inheritDoc */
    data() {
      const itemHtmlList = [];
      (this.selected || []).forEach(value => {
        itemHtmlList.push(this.getItemHtml(value || ''));
      });

      // noinspection JSValidateTypes
      return {
        ...super.data(),
        selected: this.selected,
        translatedOptions: this.translatedOptions,
        hasAdd: !!this.params.options && !this.params.keepItems,
        keepItems: this.params.keepItems,
        itemHtmlList: itemHtmlList,
        isEmpty: (this.selected || []).length === 0,
        valueIsSet: this.model.has(this.name),
        maxItemLength: this.maxItemLength || this.MAX_ITEM_LENGTH,
        allowCustomOptions: this.allowCustomOptions
      };
    }

    /** @inheritDoc */
    events = {
      /** @this ArrayFieldView */
      'click [data-action="removeValue"]': function (e) {
        const value = $(e.currentTarget).attr('data-value').toString();
        this.removeValue(value);
        this.focusOnElement();
      },
      /** @this ArrayFieldView */
      'click [data-action="showAddModal"]': function () {
        this.actionAddItem();
      }
    };
    setup() {
      super.setup();
      this.addActionHandler('editItem', (e, target) => {
        this.actionEditItem(target.dataset.value);
      });
      this.noEmptyString = this.params.noEmptyString;
      if (this.params.maxItemLength != null) {
        this.maxItemLength = this.params.maxItemLength;
      }
      this.maxItemLength = this.maxItemLength || this.MAX_ITEM_LENGTH;
      this.listenTo(this.model, 'change:' + this.name, () => {
        this.selected = Espo.Utils.clone(this.model.get(this.name)) || [];
      });
      this.selected = Espo.Utils.clone(this.model.get(this.name) || []);
      if (Object.prototype.toString.call(this.selected) !== '[object Array]') {
        this.selected = [];
      }
      this.styleMap = this.params.style || {};
      let optionsPath = this.params.optionsPath;
      /** @type {?string} */
      const optionsReference = this.params.optionsReference;
      if (!optionsPath && optionsReference) {
        const [refEntityType, refField] = optionsReference.split('.');
        optionsPath = `entityDefs.${refEntityType}.fields.${refField}.options`;
        if (Object.keys(this.styleMap).length === 0) {
          this.styleMap = this.getMetadata().get(`entityDefs.${refEntityType}.fields.${refField}.style`) || {};
        }
      }
      if (optionsPath) {
        this.params.options = Espo.Utils.clone(this.getMetadata().get(optionsPath)) || [];
      }
      this.setupOptions();
      if ('translatedOptions' in this.options) {
        this.translatedOptions = this.options.translatedOptions;
      }
      if ('translatedOptions' in this.params) {
        this.translatedOptions = this.params.translatedOptions;
      }
      if (!this.translatedOptions) {
        this.setupTranslation();
      }
      this.displayAsLabel = this.params.displayAsLabel || this.displayAsLabel;
      this.displayAsList = this.params.displayAsList || this.displayAsList;
      if (this.params.isSorted && this.translatedOptions) {
        this.params.options = Espo.Utils.clone(this.params.options);
        this.params.options = this.params.options.sort((v1, v2) => {
          return (this.translatedOptions[v1] || v1).localeCompare(this.translatedOptions[v2] || v2);
        });
      }
      if (this.options.customOptionList) {
        this.setOptionList(this.options.customOptionList, true);
      }
      if (this.params.allowCustomOptions || !this.params.options) {
        this.allowCustomOptions = true;
      }
      if (this.params.allowCustomOptions === false) {
        this.allowCustomOptions = false;
      }
      if (this.type === 'array') {
        this.validations.push('noInputValue');
      }
    }
    focusOnElement() {
      const $button = this.$el.find('button[data-action="showAddModal"]');
      if ($button[0]) {
        // noinspection JSUnresolvedReference
        $button[0].focus({
          preventScroll: true
        });
        return;
      }
      const $input = this.$el.find('input.main-element');
      if ($input[0]) {
        // noinspection JSUnresolvedReference
        $input[0].focus({
          preventScroll: true
        });
      }
    }
    setupSearch() {
      this.events['change select.search-type'] = e => {
        this.handleSearchType($(e.currentTarget).val());
      };
    }
    handleSearchType(type) {
      const $inputContainer = this.$el.find('div.input-container');
      if (~['anyOf', 'noneOf', 'allOf'].indexOf(type)) {
        $inputContainer.removeClass('hidden');
      } else {
        $inputContainer.addClass('hidden');
      }
    }
    setupTranslation() {
      let obj = {};
      let translation = this.params.translation;
      /** @type {?string} */
      const optionsReference = this.params.optionsReference;
      if (!translation && optionsReference) {
        const [refEntityType, refField] = optionsReference.split('.');
        translation = `${refEntityType}.options.${refField}`;
      }
      this.translatedOptions = null;
      if (!this.params.options) {
        return;
      }
      obj = translation ? this.getLanguage().translatePath(translation) : this.translate(this.name, 'options', this.model.name);
      const map = {};
      this.params.options.forEach(o => {
        if (typeof obj === 'object' && o in obj) {
          map[o] = obj[o];
          return;
        }
        map[o] = o;
      });
      this.translatedOptions = map;
    }
    setupOptions() {}
    setOptionList(optionList, silent) {
      const previousOptions = this.params.options;
      if (!this.originalOptionList) {
        this.originalOptionList = this.params.options;
      }
      this.params.options = Espo.Utils.clone(optionList);
      const isChanged = !_(previousOptions).isEqual(optionList);
      if (this.isEditMode() && !silent && isChanged) {
        const selectedOptionList = [];
        this.selected.forEach(option => {
          if (~optionList.indexOf(option)) {
            selectedOptionList.push(option);
          }
        });
        this.selected = selectedOptionList;
        if (this.isRendered()) {
          this.reRender();
          this.trigger('change');
        } else {
          this.once('after:render', () => {
            this.trigger('change');
          });
        }
      }
    }
    setTranslatedOptions(translatedOptions) {
      this.translatedOptions = translatedOptions;
    }
    resetOptionList() {
      if (!this.originalOptionList) {
        return;
      }
      const previousOptions = this.params.options;
      this.params.options = Espo.Utils.clone(this.originalOptionList);
      const isChanged = !_(previousOptions).isEqual(this.originalOptionList);
      if (!this.isEditMode() || !isChanged) {
        return;
      }
      if (this.isRendered()) {
        this.reRender();
      }
    }

    /**
     * @private
     */
    controlAddItemButton() {
      const $select = this.$select;
      if (!$select) {
        return;
      }
      if (!$select.get(0)) {
        return;
      }
      const value = $select.val().toString().trim();
      if (!value && this.params.noEmptyString) {
        this.$addButton.addClass('disabled').attr('disabled', 'disabled');
      } else {
        this.$addButton.removeClass('disabled').removeAttr('disabled');
      }
    }
    afterRender() {
      if (this.isEditMode()) {
        this.$list = this.$el.find('.list-group');
        const $select = this.$select = this.$el.find('.select');
        if (this.allowCustomOptions) {
          this.$addButton = this.$el.find('button[data-action="addItem"]');
          this.$addButton.on('click', () => {
            const value = $select.val().toString();
            this.addValueFromUi(value);
            this.focusOnElement();
          });
          $select.on('input', () => this.controlAddItemButton());
          $select.on('keydown', e => {
            const key = Espo.Utils.getKeyFromKeyEvent(e);
            if (key === 'Enter') {
              const value = $select.val().toString();
              this.addValueFromUi(value);
            }
          });
          this.controlAddItemButton();
        }
        this.$list.sortable({
          stop: () => {
            this.fetchFromDom();
            this.trigger('change');
          },
          distance: 5,
          cancel: 'input,textarea,button,select,option,a[role="button"]',
          cursor: 'grabbing',
          handle: !this.noDragHandle ? '.drag-handle' : undefined
        });
      }
      if (this.isSearchMode()) {
        this.renderSearch();
      }
    }

    /**
     * @protected
     * @param {string} value
     */
    addValueFromUi(value) {
      value = value.trim();
      if (this.noEmptyString && value === '') {
        return;
      }
      if (this.params.pattern) {
        const helper = new _regExpPattern.default();
        const result = helper.validate(this.params.pattern, value, this.name, this.entityType);
        if (result) {
          setTimeout(() => this.showValidationMessage(result.message, 'input.select'), 10);
          return;
        }
      }
      this.addValue(value);
      this.$select.val('');
      this.controlAddItemButton();
    }
    renderSearch() {
      this.$element = this.$el.find('.main-element');
      const valueList = this.getSearchParamsData().valueList || this.searchParams.valueFront || [];
      this.$element.val(valueList.join(this.itemDelimiter));
      const items = [];
      (this.params.options || []).forEach(value => {
        let label = this.getLanguage().translateOption(value, this.name, this.scope);
        if (this.translatedOptions) {
          if (value in this.translatedOptions) {
            label = this.translatedOptions[value];
          }
        }
        if (label === '') {
          return;
        }
        items.push({
          value: value,
          text: label,
          style: this.styleMap[value] || undefined
        });
      });
      valueList.filter(item => !(this.params.options || []).includes(item)).forEach(item => {
        items.push({
          value: item,
          text: item
        });
      });

      /** @type {module:ui/multi-select~Options} */
      const multiSelectOptions = {
        items: items,
        delimiter: this.itemDelimiter,
        matchAnyWord: this.matchAnyWord,
        allowCustomOptions: this.allowCustomOptions,
        create: input => {
          return {
            value: input,
            text: input
          };
        }
      };
      _multiSelect.default.init(this.$element, multiSelectOptions);
      this.$el.find('.selectize-dropdown-content').addClass('small');
      const type = this.$el.find('select.search-type').val();
      this.handleSearchType(type);
      this.$el.find('select.search-type').on('change', () => {
        this.trigger('change');
      });
      this.$element.on('change', () => {
        this.trigger('change');
      });
    }

    /**
     * @protected
     */
    fetchFromDom() {
      const selected = [];
      this.$el.find('.list-group .list-group-item').each((i, el) => {
        const value = $(el).attr('data-value').toString();
        selected.push(value);
      });
      this.selected = selected;
    }
    getValueForDisplay() {
      // Do not use the `html` method to avoid XSS.

      /** @var {string[]} */
      const list = this.selected.map(item => {
        let label = null;
        if (this.translatedOptions !== null) {
          if (item in this.translatedOptions) {
            label = this.translatedOptions[item];
          }
        }
        if (label === null) {
          label = item;
        }
        if (label === '') {
          label = this.translate('None');
        }
        const style = this.styleMap[item] || 'default';
        if (this.displayAsLabel) {
          let className = 'label label-md label-' + style;
          if (this.params.labelType === 'state') {
            className += ' label-state';
          }
          return $('<span>').addClass(className).text(label).get(0).outerHTML;
        }
        if (style && style !== 'default') {
          return $('<span>').addClass('text-' + style).text(label).get(0).outerHTML;
        }
        return $('<span>').text(label).get(0).outerHTML;
      });
      if (this.displayAsList) {
        if (!list.length) {
          return '';
        }
        let itemClassName = 'multi-enum-item-container';
        if (this.displayAsLabel) {
          itemClassName += ' multi-enum-item-label-container';
        }
        return list.map(item => $('<div>').addClass(itemClassName).html(item).get(0).outerHTML).join('');
      }
      if (this.displayAsLabel) {
        return list.join(' ');
      }
      return list.join(', ');
    }

    /**
     * @protected
     * @param {string} value
     * @return {string}
     */
    getItemHtml(value) {
      // Do not use the `html` method to avoid XSS.

      if (this.translatedOptions !== null) {
        for (const item in this.translatedOptions) {
          if (this.translatedOptions[item] === value) {
            value = item;
            break;
          }
        }
      }
      value = value.toString();
      const text = this.translatedOptions && value in this.translatedOptions ? this.translatedOptions[value].toString() : value;
      const div = document.createElement('div');
      div.className = 'list-group-item';
      div.dataset.value = value;
      div.style.cursor = 'default';
      if (!this.params.keepItems) {
        const a = document.createElement('a');
        a.role = 'button';
        a.tabIndex = 0;
        a.classList.add('pull-right');
        a.dataset.value = value;
        a.dataset.action = 'removeValue';
        a.append((() => {
          const span = document.createElement('span');
          span.className = 'fas fa-times';
          return span;
        })());
        div.append(a);
      }
      div.append((() => {
        const span = document.createElement('span');
        span.className = 'drag-handle';
        span.append((() => {
          const span = document.createElement('span');
          span.className = 'fas fa-grip fa-sm';
          return span;
        })());
        return span;
      })());
      if (this.params.itemsEditable && this.allowCustomOptions) {
        div.append((() => {
          const span = document.createElement('span');
          span.className = 'item-button';
          span.append((() => {
            const a = document.createElement('a');
            a.role = 'button';
            a.tabIndex = 0;
            a.dataset.value = value;
            a.dataset.action = 'editItem';
            a.append((() => {
              const span = document.createElement('span');
              span.className = 'fas fa-pencil-alt fa-sm';
              return span;
            })());
            return a;
          })());
          return span;
        })());
      }
      div.append((() => {
        const span = document.createElement('span');
        span.classList.add('text');
        span.textContent = text;
        return span;
      })());
      return div.outerHTML;
    }

    /**
     * @param {string} value
     */
    addValue(value) {
      if (this.selected.indexOf(value) === -1) {
        const html = this.getItemHtml(value);
        this.$list.append(html);
        this.selected.push(value);
        this.trigger('change');
      }
    }

    /**
     * @param {string} value
     */
    removeValue(value) {
      const valueInternal = CSS.escape(value);
      this.$list.children('[data-value="' + valueInternal + '"]').remove();
      const index = this.selected.indexOf(value);
      this.selected.splice(index, 1);
      this.trigger('change');
    }
    fetch() {
      const data = {};
      let list = Espo.Utils.clone(this.selected || []);
      if (this.params.isSorted && this.translatedOptions) {
        list = list.sort((v1, v2) => {
          return (this.translatedOptions[v1] || v1).localeCompare(this.translatedOptions[v2] || v2);
        });
      }
      data[this.name] = list;
      return data;
    }
    fetchSearch() {
      const type = this.$el.find('select.search-type').val() || 'anyOf';
      let valueList;
      if (~['anyOf', 'noneOf', 'allOf'].indexOf(type)) {
        valueList = this.$element.val().split(this.itemDelimiter);
        if (valueList.length === 1 && valueList[0] === '') {
          valueList = [];
        }
        if (valueList.length === 0) {
          if (type === 'anyOf') {
            return {
              type: 'any',
              data: {
                type: type,
                valueList: valueList
              }
            };
          }
          if (type === 'noneOf') {
            return {
              type: 'any',
              data: {
                type: type,
                valueList: valueList
              }
            };
          }
          if (type === 'allOf') {
            return {
              type: 'any',
              data: {
                type: type,
                valueList: valueList
              }
            };
          }
        }
      }
      if (type === 'anyOf') {
        const data = {
          type: 'arrayAnyOf',
          value: valueList,
          data: {
            type: 'anyOf',
            valueList: valueList
          }
        };
        if (!valueList.length) {
          data.value = null;
        }
        return data;
      }
      if (type === 'noneOf') {
        return {
          type: 'arrayNoneOf',
          value: valueList,
          data: {
            type: 'noneOf',
            valueList: valueList
          }
        };
      }
      if (type === 'allOf') {
        const data = {
          type: 'arrayAllOf',
          value: valueList,
          data: {
            type: 'allOf',
            valueList: valueList
          }
        };
        if (!valueList.length) {
          data.value = null;
        }
        return data;
      }
      if (type === 'isEmpty') {
        return {
          type: 'arrayIsEmpty',
          data: {
            type: 'isEmpty'
          }
        };
      }
      if (type === 'isNotEmpty') {
        return {
          type: 'arrayIsNotEmpty',
          data: {
            type: 'isNotEmpty'
          }
        };
      }
      return null;
    }
    validateRequired() {
      if (this.isRequired()) {
        const value = this.model.get(this.name);
        if (!value || value.length === 0) {
          const msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.getLabelText());
          this.showValidationMessage(msg, '.array-control-container');
          return true;
        }
      }
      return false;
    }

    // noinspection JSUnusedGlobalSymbols
    validateMaxCount() {
      if (this.params.maxCount) {
        const itemList = this.model.get(this.name) || [];
        if (itemList.length > this.params.maxCount) {
          const msg = this.translate('fieldExceedsMaxCount', 'messages').replace('{field}', this.getLabelText()).replace('{maxCount}', this.params.maxCount.toString());
          this.showValidationMessage(msg, '.array-control-container');
          return true;
        }
      }
      return false;
    }
    getSearchType() {
      return this.getSearchParamsData().type || 'anyOf';
    }

    /**
     * @return {{
     *    translatedOptions: Object.<string, *>|null,
     *    options: string[],
     * } | Object.<string, *>}
     */
    getAddItemModalOptions() {
      const options = [];
      this.params.options.forEach(item => {
        if (!~this.selected.indexOf(item)) {
          options.push(item);
        }
      });
      return {
        options: options,
        translatedOptions: this.translatedOptions
      };
    }

    /**
     * @protected
     * @return {Promise<import('views/modals/array-field-add').default>}
     */
    actionAddItem() {
      return this.createView('dialog', this.addItemModalView, this.getAddItemModalOptions(), view => {
        view.render();
        view.once('add', item => {
          this.addValue(item);
          view.close();
        });
        view.once('add-mass', items => {
          items.forEach(item => this.addValue(item));
          view.close();
        });
      });
    }

    /**
     * @protected
     * @param value
     */
    async actionEditItem(value) {
      const view = new EditItemModalView({
        value: value,
        required: this.noEmptyString,
        maxLength: this.maxItemLength,
        onApply: async data => {
          const index = this.selected.findIndex(it => it === value);
          if (index < 0) {
            return;
          }
          this.selected[index] = data.value;
          this.selected = this.selected.filter((it, i) => this.selected.indexOf(it) === i);
          await this.reRender();
          this.trigger('change');
        }
      });
      await this.assignView('dialog', view);
      await view.render();
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @protected
     * @return {boolean}
     */
    validateNoInputValue() {
      if (!this.element) {
        return false;
      }
      const input = this.element.querySelector('input.select');
      if (!(input instanceof HTMLInputElement)) {
        return false;
      }
      if (!input.value) {
        return false;
      }
      const message = this.translate('arrayInputNotEmpty', 'messages');
      this.showValidationMessage(message, 'input.select');
      return true;
    }
  }
  var _default = _exports.default = ArrayFieldView;
  class EditItemModalView extends _modal.default {
    // language=Handlebars
    templateContent = `
        <div class="record no-side-margin">{{{record}}}</div>
    `;

    /**
     * @private
     * @type {EditForModalRecordView}
     */
    recordView;

    /**
     *
     * @param {{
     *    value: string,
     *    maxLength: number,
     *    required: boolean,
     *    onApply: function({value: string}),
     * }} options
     */
    constructor(options) {
      super(options);
      this.options = options;
    }
    setup() {
      this.buttonList = [{
        name: 'apply',
        label: 'Apply',
        style: 'danger',
        onClick: () => this.actionApply()
      }, {
        name: 'cancel',
        label: 'Cancel',
        onClick: () => this.actionCancel()
      }];
      this.shortcutKeys = {
        'Control+Enter': () => this.actionApply()
      };
      this.headerText = this.translate('Edit Item');
      this.model = new _model.default({
        value: this.options.value
      });
      this.recordView = new _editForModal.default({
        model: this.model,
        detailLayout: [{
          rows: [[{
            view: new _varchar.default({
              name: 'value',
              labelText: this.translate('Value'),
              params: {
                required: this.options.required,
                maxLength: this.options.maxLength
              }
            })
          }, false]]
        }]
      });
      this.assignView('record', this.recordView);
    }

    /**
     * @private
     */
    actionApply() {
      const data = this.recordView.processFetch();
      if (!data) {
        return;
      }
      const value = this.model.attributes.value ?? '';
      this.options.onApply({
        value
      });
      this.close();
    }
  }
});
//# sourceMappingURL=array.js.map ;
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/** @module views/fields/link-parent */

import BaseFieldView from 'views/fields/base';
import RecordModal from 'helpers/record-modal';
import Select from 'ui/select';
import Autocomplete from 'ui/autocomplete';

/**
 * A link-parent field (belongs-to-parent relation).
 *
 * @extends BaseFieldView<module:views/fields/link-parent~params>
 */
class LinkParentFieldView extends BaseFieldView {

    /**
     * @typedef {Object} module:views/fields/link-parent~options
     * @property {
     *     module:views/fields/link~params &
     *     module:views/fields/base~params &
     *     Record
     * } [params] Parameters.
     */

    /**
     * @typedef {Object} module:views/fields/link-parent~params
     * @property {boolean} [required] Required.
     * @property {boolean} [autocompleteOnEmpty] Autocomplete on empty input.
     * @property {string[]} [entityList] An entity type list.
     */

    /**
     * @param {
     *     module:views/fields/link-parent~options &
     *     module:views/fields/base~options
     * } options Options.
     */
    constructor(options) {
        super(options);
    }

    type = 'linkParent'

    listTemplate = 'fields/link-parent/list'
    detailTemplate = 'fields/link-parent/detail'
    editTemplate = 'fields/link-parent/edit'
    searchTemplate = 'fields/link-parent/search'
    listLinkTemplate = 'fields/link-parent/list-link'

    /**
     * A name attribute name.
     *
     * @type {string}
     */
    nameName

    /**
     * An ID attribute name.
     *
     * @type {string}
     */
    idName

    /**
     * A type attribute name.
     *
     * @type {string}
     */
    typeName

    /**
     * A current foreign entity type.
     *
     * @type {string|null}
     */
    foreignScope = null

    /**
     * A foreign entity type list.
     *
     * @type {string[]}
     */
    foreignScopeList = null

    /**
     * Autocomplete disabled.
     *
     * @protected
     * @type {boolean}
     */
    autocompleteDisabled = false

    /**
     * A select-record view.
     *
     * @protected
     * @type {string}
     */
    selectRecordsView = 'views/modals/select-records'

    /**
     * Create disabled.
     *
     * @protected
     * @type {boolean}
     */
    createDisabled = false

    /**
     * A search type list.
     *
     * @protected
     * @type {string[]}
     */
    searchTypeList = [
        'is',
        'isEmpty',
        'isNotEmpty',
    ]

    /**
     * A select primary filter.
     *
     * @protected
     * @type {string|null}
     */
    selectPrimaryFilterName = null

    /**
     * A select bool filter list.
     *
     * @protected
     * @type {string[]|null}
     */
    selectBoolFilterList = null

    /**
     * An autocomplete max record number.
     *
     * @protected
     * @type {number|null}
     */
    autocompleteMaxCount = null

    /**
     * Select all attributes.
     *
     * @protected
     * @type {boolean}
     */
    forceSelectAllAttributes = false

    /**
     * Mandatory select attributes.
     *
     * @protected
     * @type {string[]|null}
     */
    mandatorySelectAttributeList = null

    /** @inheritDoc */
    initialSearchIsNotIdle = true

    /**
     * Trigger autocomplete on empty input.
     *
     * @protected
     * @type {boolean}
     */
    autocompleteOnEmpty

    /**
     * @protected
     * @type {boolean}
     */
    displayScopeColorInListMode = true

    /**
     * @protected
     * @type {boolean}
     */
    displayEntityType

    /** @inheritDoc */
    events = {
        /** @this LinkParentFieldView */
        'auxclick a[href]:not([role="button"])': function (e) {
            if (!this.isReadMode()) {
                return;
            }

            const isCombination = e.button === 1 && (e.ctrlKey || e.metaKey);

            if (!isCombination) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            this.quickView();
        },
    }

    // noinspection JSCheckFunctionSignatures
    data() {
        let nameValue = this.model.get(this.nameName);

        if (!nameValue && this.model.get(this.idName) && this.model.get(this.typeName)) {
            nameValue = this.translate(this.model.get(this.typeName), 'scopeNames');
        }

        let iconHtml = null;

        if (
            (
                this.mode === this.MODE_DETAIL ||
                this.mode === this.MODE_LIST && this.displayScopeColorInListMode
            ) &&
            this.foreignScope
        ) {
            iconHtml = this.getHelper().getScopeColorIconHtml(this.foreignScope);
        }

        // noinspection JSValidateTypes
        return {
            ...super.data(),
            idName: this.idName,
            nameName: this.nameName,
            typeName: this.typeName,
            idValue: this.model.get(this.idName),
            nameValue: nameValue,
            typeValue: this.model.get(this.typeName),
            foreignScope: this.foreignScope,
            foreignScopeList: this.foreignScopeList,
            valueIsSet: this.model.has(this.idName) || this.model.has(this.typeName),
            iconHtml: iconHtml,
            displayEntityType: this.displayEntityType && this.model.get(this.typeName),
        };
    }

    /**
     * Get advanced filters (field filters) to be applied when select a record.
     * Can be extended.
     *
     * @protected
     * @return {Object.<string,module:search-manager~advancedFilter>|null}
     */
    getSelectFilters() {
        return null;
    }

    /**
     * Get a select bool filter list. Applied when select a record.
     * Can be extended.
     *
     * @protected
     * @return {string[]|null}
     */
    getSelectBoolFilterList() {
        return this.selectBoolFilterList;
    }

    /**
     * Get a select primary filter. Applied when select a record.
     * Can be extended.
     *
     * @protected
     * @return {string|null}
     */
    getSelectPrimaryFilterName() {
        return this.selectPrimaryFilterName;
    }

    /**
     * Attributes to pass to a model when creating a new record.
     * Can be extended.
     *
     * @return {Object.<string,*>|null}
     */
    getCreateAttributes() {
        return null;
    }

    /** @inheritDoc */
    setup() {
        this.nameName = this.name + 'Name';
        this.typeName = this.name + 'Type';
        this.idName = this.name + 'Id';

        this.foreignScopeList = this.options.foreignScopeList || this.foreignScopeList;

        this.foreignScopeList = this.foreignScopeList ||
            this.params.entityList ||
            this.model.getLinkParam(this.name, 'entityList') || [];

        this.foreignScopeList = Espo.Utils.clone(this.foreignScopeList).filter(item => {
            if (!this.getMetadata().get(['scopes', item, 'disabled'])) {
                return true;
            }
        });

        this.foreignScope = this.model.get(this.typeName) || this.foreignScopeList[0];

        if (this.foreignScope && !~this.foreignScopeList.indexOf(this.foreignScope)) {
            this.foreignScopeList.unshift(this.foreignScope);
        }

        this.listenTo(this.model, `change:${this.typeName}`, () => {
            this.foreignScope = this.model.get(this.typeName) || this.foreignScopeList[0];
        });

        this.autocompleteOnEmpty = this.params.autocompleteOnEmpty || this.autocompleteOnEmpty;

        if ('createDisabled' in this.options) {
            this.createDisabled = this.options.createDisabled;
        }

        if (!this.isListMode()) {
            this.addActionHandler('selectLink', () => this.actionSelect());
            this.addActionHandler('clearLink', () => this.actionClearLink());

            this.events[`change select[data-name="${this.typeName}"]`] = e => {
                this.foreignScope = e.currentTarget.value;

                this.$elementName.val('');
                this.$elementId.val('');
            };
        }
    }

    /**
     * @protected
     */
    actionClearLink() {
        if (this.foreignScopeList.length) {
            this.foreignScope = this.foreignScopeList[0];

            Select.setValue(this.$elementType, this.foreignScope);
        }

        this.$elementName.val('');
        this.$elementId.val('');

        this.trigger('change');
    }

    /**
     * @protected
     */
    async actionSelect() {
        const viewName = this.getMetadata().get(`clientDefs.${this.foreignScope}.modalViews.select`) ||
            this.selectRecordsView;

        const createButton = !this.createDisabled && this.isEditMode();

        /** @type {module:views/modals/select-records~Options} */
        const options = {
            scope: this.foreignScope,
            createButton: createButton,
            filters: this.getSelectFilters(),
            boolFilterList: this.getSelectBoolFilterList(),
            primaryFilterName: this.getSelectPrimaryFilterName(),
            createAttributes: createButton ? this.getCreateAttributes() : null,
            mandatorySelectAttributeList: this.getMandatorySelectAttributeList(),
            forceSelectAllAttributes: this.isForceSelectAllAttributes(),
            layoutName: this.getSelectLayout(),
            onSelect: models => {
                this.select(models[0]);
            },
        };

        Espo.Ui.notifyWait();

        const view = await this.createView('modal', viewName, options);

        await view.render();

        Espo.Ui.notify();
    }

    /** @inheritDoc */
    setupSearch() {
        const type = this.getSearchParamsData().type;

        if (type === 'is' || !type) {
            this.searchData.idValue = this.getSearchParamsData().idValue ||
                this.searchParams.valueId;
            this.searchData.nameValue = this.getSearchParamsData().nameValue ||
                this.searchParams.valueName;
            this.searchData.typeValue = this.getSearchParamsData().typeValue ||
                this.searchParams.valueType;
        }

        this.events['change select.search-type'] = e => {
            const type = $(e.currentTarget).val();

            this.handleSearchType(type);
        };
    }

    /**
     * Handle a search type.
     *
     * @protected
     * @param {string} type A type.
     */
    handleSearchType(type) {
        if (['is'].includes(type)) {
            this.$el.find('div.primary').removeClass('hidden');
        } else {
            this.$el.find('div.primary').addClass('hidden');
        }
    }

    /**
     * Select.
     *
     * @param {module:model} model A model.
     * @protected
     */
    select(model) {
        this.$elementName.val(model.get('name') || model.id);
        this.$elementId.val(model.get('id'));

        this.trigger('change');
    }

    /**
     * Attributes to select regardless availability on a list layout.
     * Can be extended.
     *
     * @protected
     * @return {string[]|null}
     */
    getMandatorySelectAttributeList() {
        return this.mandatorySelectAttributeList;
    }

    /**
     * Select all attributes. Can be extended.
     *
     * @protected
     * @return {boolean}
     */
    isForceSelectAllAttributes() {
        return this.forceSelectAllAttributes;
    }

    /**
     * Get an autocomplete max record number. Can be extended.
     *
     * @protected
     * @return {number}
     */
    getAutocompleteMaxCount() {
        if (this.autocompleteMaxCount) {
            return this.autocompleteMaxCount;
        }

        return this.getConfig().get('recordsPerPage');
    }

    // noinspection JSUnusedLocalSymbols
    /**
     * Compose an autocomplete URL. Can be extended.
     *
     * @protected
     * @type {string} [q]
     * @return {string}
     */
    getAutocompleteUrl(q) {
        let url = this.foreignScope + '?maxSize=' + this.getAutocompleteMaxCount();

        if (!this.isForceSelectAllAttributes()) {
            let select = ['id', 'name'];

            if (this.getMandatorySelectAttributeList()) {
                select = select.concat(this.getMandatorySelectAttributeList());
            }

            url += '&select=' + select.join(',');
        }

        const boolList = this.getSelectBoolFilterList();

        if (boolList) {
            url += '&' + $.param({'boolFilterList': boolList});
        }

        const primary = this.getSelectPrimaryFilterName();

        if (primary) {
            url += '&' + $.param({'primaryFilter': primary});
        }

        const advanced = this.getSelectFilters();

        if (advanced && Object.keys(advanced).length) {
            url += '&' + $.param({'where': advanced});
        }

        return url;
    }

    afterRender() {
        if (this.isEditMode() || this.isSearchMode()) {
            this.$elementId = this.$el.find('input[data-name="' + this.idName + '"]');
            this.$elementName = this.$el.find('input[data-name="' + this.nameName + '"]');
            this.$elementType = this.$el.find('select[data-name="' + this.typeName + '"]');

            this.$elementName.on('change', () => {
                if (this.$elementName.val() === '') {
                    this.$elementName.val('');
                    this.$elementId.val('');

                    this.trigger('change');
                }
            });

            this.$elementType.on('change', () => {
                this.$elementName.val('');
                this.$elementId.val('');

                this.trigger('change');
            });

            this.$elementName.on('blur', e => {
                setTimeout(() => {
                    if (this.mode === this.MODE_EDIT) {
                        e.currentTarget.value = this.model.get(this.nameName) || '';
                    }
                }, 100);
            });

            if (!this.autocompleteDisabled) {
                /** @type {module:ajax.AjaxPromise & Promise<any>} */
                let lastAjaxPromise;

                const autocomplete = new Autocomplete(this.$elementName.get(0), {
                    name: this.name,
                    focusOnSelect: true,
                    handleFocusMode: 2,
                    autoSelectFirst: true,
                    triggerSelectOnValidInput: false,
                    forceHide: true,
                    minChars: this.autocompleteOnEmpty ? 0 : 1,
                    onSelect: item => {
                        this.getModelFactory().create(this.foreignScope, model => {
                            model.set(item.attributes);

                            this.select(model);
                            this.$elementName.focus();
                        });
                    },
                    lookupFunction: query => {
                        return Promise.resolve(this.getAutocompleteUrl(query))
                            .then(url => {
                                if (lastAjaxPromise && lastAjaxPromise.getReadyState() < 4) {
                                    lastAjaxPromise.abort();
                                }

                                lastAjaxPromise = Espo.Ajax.getRequest(url, {q: query});

                                return lastAjaxPromise;
                            })
                            .then(/** {list: Record[]} */response => {
                                return response.list.map(item => ({
                                    value: item.name,
                                    attributes: item,
                                }));
                            });
                    },
                });

                this.once('render remove', () => autocomplete.dispose());

                Select.init(this.$elementType, {});

                this.$elementType.on('change', () => autocomplete.clear());
            }
        }

        if (this.isSearchMode()) {
            const type = this.$el.find('select.search-type').val();

            this.handleSearchType(type);

            this.$el.find('select.search-type').on('change', () => {
                this.trigger('change');
            });
        }
    }

    /** @inheritDoc */
    getValueForDisplay() {
        return this.model.get(this.nameName);
    }

    /** @inheritDoc */
    validateRequired() {
        if (this.isRequired()) {
            if (this.model.get(this.idName) === null || !this.model.get(this.typeName)) {
                const msg = this.translate('fieldIsRequired', 'messages')
                    .replace('{field}', this.getLabelText());

                this.showValidationMessage(msg);

                return true;
            }
        }
    }

    /** @inheritDoc */
    fetch() {
        const data = {};

        data[this.typeName] = this.$elementType.val() || null;
        data[this.nameName] = this.$elementName.val() || null;
        data[this.idName] = this.$elementId.val() || null;

        if (data[this.idName] === null) {
            data[this.typeName] = null;
        }

        return data;
    }

    /** @inheritDoc */
    fetchSearch() {
        const type = this.$el.find('select.search-type').val();

        if (type === 'isEmpty') {
            return {
                type: 'isNull',
                field: this.idName,
                data: {
                    type: type,
                }
            };
        }

        if (type === 'isNotEmpty') {
            return {
                type: 'isNotNull',
                field: this.idName,
                data: {
                    type: type,
                }
            };
        }

        const entityType = this.$elementType.val();
        const entityName = this.$elementName.val();
        const entityId = this.$elementId.val();

        if (!entityType) {
            return null;
        }

        if (entityId) {
            return {
                type: 'and',
                attribute: this.idName,
                value: [
                    {
                        type: 'equals',
                        field: this.idName,
                        value: entityId,
                    },
                    {
                        type: 'equals',
                        field: this.typeName,
                        value: entityType,
                    }
                ],
                data: {
                    type: 'is',
                    idValue: entityId,
                    nameValue: entityName,
                    typeValue: entityType,
                }
            };
        }

        return {
            type: 'and',
            attribute: this.idName,
            value: [
                {
                    type: 'isNotNull',
                    field: this.idName,
                },
                {
                    type: 'equals',
                    field: this.typeName,
                    value: entityType,
                }
            ],
            data: {
                type: 'is',
                typeValue: entityType,
            }
        };
    }

    /** @inheritDoc */
    getSearchType() {
        return this.getSearchParamsData().type || this.searchParams.typeFront;
    }

    /**
     * @protected
     */
    quickView() {
        const id = this.model.get(this.idName);
        const entityType = this.model.get(this.typeName);

        if (!id || !entityType) {
            return;
        }

        const helper = new RecordModal();

        helper.showDetail(this, {
            id: id,
            entityType: entityType,
        });
    }

    /**
     * @protected
     * @return {string|undefined}
     * @since 9.1.0
     */
    getSelectLayout() {
        return undefined;
    }
}

export default LinkParentFieldView;

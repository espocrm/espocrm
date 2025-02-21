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

import BaseFieldView from 'views/fields/base';
import EmailFromAddressFieldView from 'views/email/fields/from-address-varchar';
import EmailEmailAddressFieldView from 'views/email/fields/email-address';
import Autocomplete from 'ui/autocomplete';

class EmailAddressVarcharFieldView extends BaseFieldView {

    detailTemplate = 'email/fields/email-address-varchar/detail'
    editTemplate = 'email/fields/email-address-varchar/edit'

    emailAddressRegExp = new RegExp(
        /^[-!#$%&'*+/=?^_`{|}~A-Za-z0-9]+(?:\.[-!#$%&'*+/=?^_`{|}~A-Za-z0-9]+)*/.source +
        /@([A-Za-z0-9]([A-Za-z0-9-]*[A-Za-z0-9])?\.)+[A-Za-z0-9][A-Za-z0-9-]*[A-Za-z0-9]/.source
    )

    // noinspection JSCheckFunctionSignatures
    data() {
        const data = super.data();

        data.valueIsSet = this.model.has(this.name);
        data.maxLength = 254;
        data.hasSelectAddress = this.hasSelectAddress;

        // noinspection JSValidateTypes
        return data;
    }

    events = {
        /** @this EmailAddressVarcharFieldView */
        'click a[data-action="clearAddress"]': function (e) {
            const address = $(e.currentTarget).data('address').toString();

            this.deleteAddress(address);
        },
        /** @this EmailAddressVarcharFieldView */
        'keyup input': function (e) {
            if (!this.isEditMode()) {
                return;
            }

            const key = Espo.Utils.getKeyFromKeyEvent(e);

            if (
                key === 'Comma' ||
                key === 'Semicolon' ||
                key === 'Enter'
            ) {
                const $input = $(e.currentTarget);

                const address = this.obtainEmailAddressFromString($input.val());

                if (address) {
                    this.addAddress(address, '');

                    $input.val('');
                }
            }
        },
        /** @this EmailAddressVarcharFieldView */
        'change input': function (e) {
            if (!this.isEditMode()) {
                return;
            }

            const $input = $(e.currentTarget);

            const address = this.obtainEmailAddressFromString($input.val());

            if (address) {
                this.addAddress(address, '');

                $input.val('');
            }
        },
        /** @this EmailAddressVarcharFieldView */
        'click [data-action="createContact"]': function (e) {
            const address = $(e.currentTarget).data('address');

            EmailFromAddressFieldView.prototype.createPerson.call(this, 'Contact', address);
        },
        /** @this EmailAddressVarcharFieldView */
        'click [data-action="createLead"]': function (e) {
            const address = $(e.currentTarget).data('address');

            EmailFromAddressFieldView.prototype.createPerson.call(this, 'Lead', address);
        },
        /** @this EmailAddressVarcharFieldView */
        'click [data-action="addToContact"]': function (e) {
            const address = $(e.currentTarget).data('address');

            EmailFromAddressFieldView.prototype.addToPerson.call(this, 'Contact', address);
        },
        /** @this EmailAddressVarcharFieldView */
        'click [data-action="addToLead"]': function (e) {
            const address = $(e.currentTarget).data('address');

            EmailFromAddressFieldView.prototype.addToPerson.call(this, 'Lead', address);
        },
        /** @this EmailAddressVarcharFieldView */
        'auxclick a[href][data-scope][data-id]': function (e) {
            const isCombination = e.button === 1 && (e.ctrlKey || e.metaKey);

            if (!isCombination) {
                return;
            }

            const $target = $(e.currentTarget);

            const id = $target.attr('data-id');
            const scope = $target.attr('data-scope');

            e.preventDefault();
            e.stopPropagation();

            EmailFromAddressFieldView.prototype.quickView.call(this, {
                id: id,
                scope: scope,
            });
        },
    }

    /**
     *
     * @param {string} input
     * @return {string}
     */
    obtainEmailAddressFromString(input) {
        input = input.replace(',', '').replace(';', '').trim();

        const address = input.split(' ').find(it => it.includes('@'));

        if (!address) {
            return undefined;
        }

        if (!this.checkEmailAddressInString(address)) {
            return undefined;
        }

        return address;
    }

    getAutocompleteMaxCount() {
        if (this.autocompleteMaxCount) {
            return this.autocompleteMaxCount;
        }

        return this.getConfig().get('recordsPerPage');
    }

    getAttributeList() {
        const list = super.getAttributeList();

        list.push('nameHash');
        list.push('typeHash');
        list.push('idHash');
        list.push('accountId');
        list.push(this.name + 'EmailAddressesNames');
        list.push(this.name + 'EmailAddressesIds');

        return list;
    }

    setup() {
        this.setupSelectAddress();

        this.on('render', () => this.initAddressList());
    }

    /**
     * @private
     */
    setupSelectAddress() {
        const list = /** @type {string[]} */this.getConfig().get('emailAddressSelectEntityTypeList') || [];

        this.selectAddressEntityTypeList = list.filter(it => this.getAcl().checkScope(it));
        this.hasSelectAddress = this.selectAddressEntityTypeList.length !== 0;

        this.addActionHandler('selectAddress', () => {
            const entityType = this.selectAddressEntityTypeList[0];

            this.processSelectEntityType(entityType);
        });
    }

    /**
     * @param {string} entityType
     */
    async processSelectEntityType(entityType) {
        const viewName = this.getMetadata().get(['clientDefs', entityType, 'modalViews', 'select']) ||
            'views/modals/select-records';

        const headerText = this.translate('Select') + ' · ' + this.translate(this.name, 'fields', 'Email');

        const filters = {
            emailAddress: {
                type: 'isNotNull',
                data: {
                    type: 'isNotEmpty',
                },
            },
            emailAddressIsInvalid: {
                type: 'isFalse',
                data: {
                    type: 'isFalse',
                },
            },
        };

        if (
            entityType === 'Contact' &&
            (
                this.model.attributes.parentId && this.model.attributes.parentType === 'Account' ||
                this.model.attributes.accountId
            )
        ) {
            const accountId = this.model.attributes.accountId || this.model.attributes.parentId;
            const accountName = this.model.attributes.accountId ?
                this.model.attributes.accountName : this.model.attributes.parentName;

            filters.accounts = {
                field: 'accounts',
                type: 'linkedWith',
                value: [accountId],
                data: {
                    nameHash: {[accountId]: accountName},
                },
            };
        }

        /** @type {module:views/modals/select-records~Options} */
        const options = {
            entityType: entityType,
            multiple: true,
            createButton: false,
            mandatorySelectAttributeList: ['emailAddress'],
            headerText: headerText,
            filters: filters,
            onSelect: models => {
                models
                    .filter(model => model.attributes.emailAddress)
                    .forEach(model => {
                        const address = model.attributes.emailAddress;

                        if (this.addressList.includes(address)) {
                            return;
                        }

                        this.addressList.push(address);
                        this.nameHash[address] = model.attributes.name;
                        this.idHash[address] = model.id;
                        this.typeHash[address] = model.entityType;

                        this.addAddressHtml(address, model.attributes.name);
                    });

                this.trigger('change');
            },
        };

        const view = /** @type {import('views/modals/select-records').default} */
            await this.createView('modal', viewName, options);

        this.selectAddressEntityTypeList.forEach(itemEntityType => {
            view.addButton({
                name: 'selectEntityType' + itemEntityType,
                style: 'text',
                position: 'right',
                label: this.translate(itemEntityType, 'scopeNamesPlural'),
                className: itemEntityType === entityType ? 'active btn-xs-wide' : 'btn-xs-wide',
                disabled: itemEntityType === entityType,
                onClick: () => {
                    this.clearView('modal');

                    this.processSelectEntityType(itemEntityType);
                },
            }, false, true);
        });

        await view.render();
    }

    initAddressList() {
        this.nameHash = {};

        this.addressList = (this.model.get(this.name) || '')
            .split(';')
            .filter((item) => {
                return item !== '';
            })
            .map(item => {
                return item.trim();
            });

        this.idHash = this.idHash || {};
        this.typeHash = this.typeHash || {};
        this.nameHash = this.nameHash || {};

        _.extend(this.typeHash, this.model.get('typeHash') || {});
        _.extend(this.nameHash, this.model.get('nameHash') || {});
        _.extend(this.idHash, this.model.get('idHash') || {});

        this.nameHash = _.clone(this.nameHash);
        this.typeHash = _.clone(this.typeHash);
        this.idHash = _.clone(this.idHash);
    }

    afterRender() {
        super.afterRender();

        if (this.isEditMode()) {
            this.$input = this.$element = this.$el.find('input');

            this.addressList.forEach(item => {
                this.addAddressHtml(item, this.nameHash[item] || '');
            });

            /** @type {module:ajax.AjaxPromise & Promise<any>} */
            let lastAjaxPromise;

            const autocomplete = new Autocomplete(this.$input.get(0), {
                name: this.name,
                autoSelectFirst: true,
                triggerSelectOnValidInput: false,
                focusOnSelect: true,
                minChars: 1,
                forceHide: true,
                onSelect: item => {
                    this.addAddress(
                        item.emailAddress,
                        item.entityName,
                        item.entityType,
                        item.entityId
                    );

                    this.$input.val('');
                },
                formatResult: item => {
                    return this.getHelper().escapeString(item.name) + ' &#60;' +
                        this.getHelper().escapeString(item.id) + '&#62;';
                },
                lookupFunction: query => {
                    if (lastAjaxPromise && lastAjaxPromise.getReadyState() < 4) {
                        lastAjaxPromise.abort();
                    }

                    lastAjaxPromise = Espo.Ajax
                        .getRequest('EmailAddress/search', {
                            q: query,
                            maxSize: this.getAutocompleteMaxCount(),
                            onlyActual: true,
                        });

                    return lastAjaxPromise.then(/** Record[] */response => {
                        return response.map(item => {
                            return {
                                id: item.emailAddress,
                                name: item.entityName,
                                emailAddress: item.emailAddress,
                                entityId: item.entityId,
                                entityName: item.entityName,
                                entityType: item.entityType,
                                data: item.emailAddress,
                                value: item.emailAddress,
                            };
                        });
                    });
                },
            });

            this.once('render remove', () => autocomplete.dispose());
        }

        if (this.mode === 'search' && this.getAcl().check('Email', 'create')) {
            EmailEmailAddressFieldView.prototype.initSearchAutocomplete.call(this);
        }

        if (this.mode === 'search') {
            this.$input.on('input', () => {
                this.trigger('change');
            });
        }
    }

    checkEmailAddressInString(string) {
        const arr = string.match(this.emailAddressRegExp);

        if (!arr || !arr.length) {
            return;
        }

        return true;
    }

    addAddress(address, name, type, id) {
        if (this.justAddedAddress) {
            this.deleteAddress(this.justAddedAddress);
        }

        this.justAddedAddress = address;

        setTimeout(() => {
            this.justAddedAddress = null;
        }, 100);

        address = address.trim();

        if (!type) {
            const arr = address.match(this.emailAddressRegExp);

            if (!arr || !arr.length) {
                return;
            }

            address = arr[0];
        }

        if (!~this.addressList.indexOf(address)) {
            this.addressList.push(address);
            this.nameHash[address] = name;

            if (type) {
                this.typeHash[address] = type;
            }

            if (id) {
                this.idHash[address] = id;
            }

            this.addAddressHtml(address, name);
            this.trigger('change');
        }
    }

    /**
     * @private
     * @param {string} address
     * @param {string} name
     */
    addAddressHtml(address, name) {
        const $container = this.$el.find('.link-container');

        const type = this.typeHash[address];
        const id = this.idHash[address];

        let avatarHtml = '';

        const $text = $('<span>');

        if (name) {
            if (type === 'User' && id) {
                avatarHtml = this.getHelper().getAvatarHtml(id, 'small', 18, 'avatar-link');
            }

            $text.append(
                $('<span>').text(name),
                ' ',
                $('<span>').addClass('text-muted middle-dot'),
                ' '
            );
        }

        $text.append(
            $('<span>').text(address)
        );

        const $div = $('<div>')
            .attr('data-address', address)
            .addClass('list-group-item')
            .append(
                avatarHtml,
                $('<a>')
                    .attr('data-address', address)
                    .attr('role', 'button')
                    .attr('tabindex', '0')
                    .attr('data-action', 'clearAddress')
                    .addClass('pull-right')
                    .append(
                        $('<span>').addClass('fas fa-times')
                    ),
                $text
            );

        $container.append($div);
    }

    deleteAddress(address) {
        this.deleteAddressHtml(address);

        const index = this.addressList.indexOf(address);

        if (index > -1) {
            this.addressList.splice(index, 1);
        }

        delete this.nameHash[address];

        this.trigger('change');
    }

    deleteAddressHtml(address) {
        this.$el.find('.list-group-item[data-address="' + address + '"]').remove();
    }

    fetch() {
        const data = {};

        data[this.name] = this.addressList.join(';');

        return data;
    }

    fetchSearch() {
        const value = this.$element.val().trim();

        if (value) {
            return {
                type: 'equals',
                value: value,
            };
        }

        return null;
    }

    getValueForDisplay() {
        if (this.isDetailMode()) {
            const names = [];

            this.addressList.forEach((address) => {
                names.push(this.getDetailAddressHtml(address));
            });

            return names.join('');
        }
    }

    getDetailAddressHtml(address) {
        if (!address) {
            return '';
        }

        const name = this.nameHash[address] || null;
        const entityType = this.typeHash[address] || null;
        const id = this.idHash[address] || null;

        if (id) {
            let avatarHtml = '';

            if (entityType === 'User') {
                avatarHtml = this.getHelper().getAvatarHtml(id, 'small', 18, 'avatar-link');
            }

            return $('<div class="email-address-detail-item">')
                .append(
                    avatarHtml,
                    $('<a>')
                        .attr('href', `#${entityType}/view/${id}`)
                        .attr('data-scope', entityType)
                        .attr('data-id', id)
                        .text(name),
                    ' <span class="text-muted middle-dot"></span> ',
                    $('<span>').text(address)
                )
                .get(0).outerHTML;
        }

        const $div = $('<div>');

        if (name) {
            $div.append(
                $('<span>')
                    .addClass('email-address-line')
                    .text(name)
                    .append(' <span class="text-muted middle-dot"></span> ')
                    .append(
                        $('<span>').text(address)
                    )
            );
        }
        else {
            $div.append(
                $('<span>')
                    .addClass('email-address-line')
                    .text(address)
            );
        }

        if (this.getAcl().check('Contact', 'create') || this.getAcl().check('Lead', 'create')) {
            $div.prepend(
                EmailFromAddressFieldView.prototype.getCreateHtml.call(this, address)
            );
        }

        return $div.get(0).outerHTML;
    }

    validateRequired() {
        if (this.model.get('status') === 'Draft') {
            return false;
        }

        return super.validateRequired();
    }
}

export default EmailAddressVarcharFieldView;

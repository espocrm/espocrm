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
import EmailEmailAddressFieldView from 'views/email/fields/email-address';
import RecordModal from 'helpers/record-modal';
import EmailHelper from 'email-helper';

class EmailFromAddressVarchar extends BaseFieldView {

    detailTemplate = 'email/fields/email-address-varchar/detail'

    validations = ['required', 'email']
    skipCurrentInAutocomplete = true

    emailAddressRegExp = new RegExp(
        /^[-!#$%&'*+/=?^_`{|}~A-Za-z0-9]+(?:\.[-!#$%&'*+/=?^_`{|}~A-Za-z0-9]+)*/.source +
        /@([A-Za-z0-9]([A-Za-z0-9-]*[A-Za-z0-9])?\.)+[A-Za-z0-9][A-Za-z0-9-]*[A-Za-z0-9]/.source
    )

    setup() {
        super.setup();

        this.erasedPlaceholder = 'ERASED:';

        this.on('render', () => {
            if (this.mode === this.MODE_SEARCH) {
                return;
            }

            this.initAddressList();
        });
    }

    events = {
        /** @this EmailFromAddressVarchar */
        'click [data-action="createContact"]': function (e) {
            const address = $(e.currentTarget).data('address');

            this.createPerson('Contact', address);
        },
        /** @this EmailFromAddressVarchar */
        'click [data-action="createLead"]': function (e) {
            const address = $(e.currentTarget).data('address');

            this.createPerson('Lead', address);
        },
        /** @this EmailFromAddressVarchar */
        'click [data-action="addToContact"]': function (e) {
            const address = $(e.currentTarget).data('address');

            this.addToPerson('Contact', address);
        },
        /** @this EmailFromAddressVarchar */
        'click [data-action="addToLead"]': function (e) {
            const address = $(e.currentTarget).data('address');

            this.addToPerson('Lead', address);
        },
        /** @this EmailFromAddressVarchar */
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

            this.quickView({
                id: id,
                scope: scope,
            });
        },
    }

    // noinspection JSCheckFunctionSignatures
    data() {
        const data = super.data();

        const address = this.model.get(this.name);

        if (address && !(address in this.idHash) && this.model.get('parentId')) {
            if (this.getAcl().check('Contact', 'edit')) {
                data.showCreate = true;
            }
        }

        data.valueIsSet = this.model.has(this.name);

        return data;
    }

    afterRender() {
        super.afterRender();

        if (this.mode === this.MODE_SEARCH && this.getAcl().check('Email', 'create')) {
            EmailEmailAddressFieldView.prototype.initSearchAutocomplete.call(this);
        }

        if (this.mode === this.MODE_EDIT && this.getAcl().check('Email', 'create')) {
            EmailEmailAddressFieldView.prototype.initSearchAutocomplete.call(this);
        }

        if (this.mode === this.MODE_SEARCH) {
            this.$input.on('input', () => {
                this.trigger('change');
            });
        }
    }

    // noinspection JSUnusedGlobalSymbols
    getAutocompleteMaxCount() {
        return EmailEmailAddressFieldView.prototype.getAutocompleteMaxCount.call(this);
    }

    initAddressList() {
        this.nameHash = {};
        this.typeHash = this.model.get('typeHash') || {};
        this.idHash = this.model.get('idHash') || {};

        _.extend(this.nameHash, this.model.get('nameHash') || {});
    }

    getAttributeList() {
        const list = super.getAttributeList();

        list.push('nameHash');
        list.push('idHash');
        list.push('accountId');

        return list;
    }

    getValueForDisplay() {
        if (this.mode === this.MODE_DETAIL) {
            const address = this.model.get(this.name);

            return this.getDetailAddressHtml(address);
        }

        return super.getValueForDisplay();
    }

    /**
     * @protected
     * @param {string} address
     * @return {string}
     */
    getDetailAddressHtml(address) {
        if (!address) {
            return '';
        }

        const fromString = this.model.get('fromString') || this.model.get('fromName');

        const name = this.nameHash[address] || this.parseNameFromStringAddress(fromString) || null;

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
                    ' ',
                    $('<span>').addClass('text-muted middle-dot'),
                    ' ',
                    $('<span>').text(address)
                )
                .get(0).outerHTML;
        }

        const $div = $('<div>');

        if (this.getAcl().check('Contact', 'create') || this.getAcl().check('Lead', 'create')) {
            $div.append(
                this.getCreateHtml(address)
            );
        }

        if (name) {
            $div.append(
                $('<span>')
                    .addClass('email-address-line')
                    .text(name)
                    .append(
                        ' ',
                        $('<span>').addClass('text-muted middle-dot'),
                        ' ',
                        $('<span>').text(address)
                    )
            );

            return $div.get(0).outerHTML;
        }

        $div.append(
            $('<span>')
                .addClass('email-address-line')
                .text(address)
        )

        return $div.get(0).outerHTML;
    }

    getCreateHtml(address) {
        const $ul = $('<ul>')
            .addClass('dropdown-menu')
            .attr('role', 'menu');

        const $container = $('<span>')
            .addClass('dropdown email-address-create-dropdown pull-right')
            .append(
                $('<button>')
                    .addClass('dropdown-toggle btn btn-link btn-sm')
                    .attr('data-toggle', 'dropdown')
                    .append(
                        $('<span>').addClass('caret text-muted')
                    ),
                $ul
            );

        if (this.getAcl().check('Contact', 'create')) {
            $ul.append(
                $('<li>')
                    .append(
                        $('<a>')
                            .attr('role', 'button')
                            .attr('tabindex', '0')
                            .attr('data-action', 'createContact')
                            .attr('data-address', address)
                            .text(this.translate('Create Contact', 'labels', 'Email'))
                    )
            );
        }

        if (this.getAcl().check('Lead', 'create')) {
            $ul.append(
                $('<li>')
                    .append(
                        $('<a>')
                            .attr('role', 'button')
                            .attr('tabindex', '0')
                            .attr('data-action', 'createLead')
                            .attr('data-address', address)
                            .text(this.translate('Create Lead', 'labels', 'Email'))
                    )
            );
        }

        if (this.getAcl().check('Contact', 'edit')) {
            $ul.append(
                $('<li>')
                    .append(
                        $('<a>')
                            .attr('role', 'button')
                            .attr('tabindex', '0')
                            .attr('data-action', 'addToContact')
                            .attr('data-address', address)
                            .text(this.translate('Add to Contact', 'labels', 'Email'))
                    )
            );
        }

        if (this.getAcl().check('Lead', 'edit')) {
            $ul.append(
                $('<li>')
                    .append(
                        $('<a>')
                            .attr('role', 'button')
                            .attr('tabindex', '0')
                            .attr('data-action', 'addToLead')
                            .attr('data-address', address)
                            .text(this.translate('Add to Lead', 'labels', 'Email'))
                    )
            );
        }

        if (this.name === 'from' && this.getAcl().check('EmailFilter', 'create')) {
            if ($ul.children().length) {
                $ul.append(`<li class="divider"></li>`)
            }

            const url = '#EmailFilter/create?from=' + encodeURI(address) +
                '&returnUrl=' + encodeURI(this.getRouter().getCurrentUrl());

            $ul.append(
                $('<li>')
                    .append(
                        $('<a>')
                            .attr('tabindex', '0')
                            .attr('href', url)
                            .text(this.translate('Create EmailFilter', 'labels', 'EmailFilter'))
                    )
            );
        }

        return $container.get(0).outerHTML;
    }

    /**
     * @param {string} value
     * @return {string|null}
     */
    parseNameFromStringAddress(value) {
        value = value || '';

        const emailHelper = new EmailHelper();

        return emailHelper.parseNameFromStringAddress(value);
    }

    /**
     * @internal Called with a different context from another view.
     * @param {string} scope
     * @param {string} address
     */
    createPerson(scope, address) {
        const fromString = this.model.get('fromString') || this.model.get('fromName');
        let name = this.nameHash[address] || null;

        if (!name && this.name === 'from' && fromString) {
            const emailHelper = new EmailHelper();

            name = emailHelper.parseNameFromStringAddress(fromString);
        }

        if (name) {
            name = this.getHelper().escapeString(name);
        }

        const attributes = {
            emailAddress: address,
        };

        if (this.model.get('accountId') && scope === 'Contact') {
            attributes.accountId = this.model.get('accountId');
            attributes.accountName = this.model.get('accountName');
        }

        if (name) {
            const firstName = name.split(' ').slice(0, -1).join(' ');
            const lastName = name.split(' ').slice(-1).join(' ');

            attributes.firstName = firstName;
            attributes.lastName = lastName;
        }

        const helper = new RecordModal();

        helper.showCreate(this, {
            entityType: scope,
            attributes: attributes,
            afterSave: model => {
                const nameHash = Espo.Utils.clone(this.model.get('nameHash') || {});
                const typeHash = Espo.Utils.clone(this.model.get('typeHash') || {});
                const idHash = Espo.Utils.clone(this.model.get('idHash') || {});

                idHash[address] = model.id;
                nameHash[address] = model.attributes.name;
                typeHash[address] = scope;

                this.idHash = idHash;
                this.nameHash = nameHash;
                this.typeHash = typeHash;

                const attributes = {
                    nameHash: nameHash,
                    idHash: idHash,
                    typeHash: typeHash,
                };

                setTimeout(() => {
                    this.model.set(attributes);

                    if (this.model.attributes.icsContents) {
                        this.model.fetch();
                    }
                }, 50);
            },
        });
    }

    /**
     * @internal Called with a different context from another view.
     * @param {string} scope
     * @param {string} address
     */
    async addToPerson(scope, address) {
        const fromString = this.model.get('fromString') || this.model.get('fromName');
        let name = this.nameHash[address] || null;

        if (!name && this.name === 'from' && fromString) {
            const emailHelper = new EmailHelper();

            name = emailHelper.parseNameFromStringAddress(fromString);
        }

        if (name) {
            name = this.getHelper().escapeString(name);
        }

        const attributes = {
            emailAddress: address,
        };

        if (this.model.get('accountId') && scope === 'Contact') {
            attributes.accountId = this.model.get('accountId');
            attributes.accountName = this.model.get('accountName');
        }

        const filters = {};

        if (name) {
            filters['name'] = {
                type: 'equals',
                field: 'name',
                value: name,
            };
        }

        const afterSave = /** import('model').default */model => {
            const nameHash = Espo.Utils.clone(this.model.get('nameHash') || {});
            const typeHash = Espo.Utils.clone(this.model.get('typeHash') || {});
            const idHash = Espo.Utils.clone(this.model.get('idHash') || {});

            idHash[address] = model.id;
            nameHash[address] = model.attributes.name;
            typeHash[address] = scope;

            this.idHash = idHash;
            this.nameHash = nameHash;
            this.typeHash = typeHash;

            const attributes = {
                nameHash: nameHash,
                idHash: idHash,
                typeHash: typeHash,
            };

            setTimeout(() => {
                this.model.set(attributes);

                if (this.model.attributes.icsContents) {
                    this.model.fetch();
                }
            }, 50);
        };

        const viewName = this.getMetadata().get(`clientDefs.${scope}.modalViews.select`) ||
            'views/modals/select-records';

        /** @type {module:views/modals/select-records~Options} */
        const options = {
            entityType: scope,
            createButton: false,
            filters: filters,
            onSelect: async models => {
                const model = models[0];

                if (!model.attributes.emailAddress) {
                    await model.save({emailAddress: address}, {patch: true});

                    afterSave(model);

                    return;
                }

                await model.fetch();

                const emailAddressData = [...(model.attributes.emailAddressData || [])];

                emailAddressData.push({
                    emailAddress: address,
                    primary: emailAddressData.length === 0
                });

                await model.save({emailAddressData: emailAddressData}, {patch: true});

                afterSave(model);
            }
        };

        Espo.Ui.notifyWait();

        const view = await this.createView('modal', viewName, options);

        await view.render();

        Espo.Ui.notify();
    }

    fetchSearch() {
        const value = this.$element.val().trim();

        if (value) {
            return {
                type: 'equals',
                value: value,
            }
        }

        return null;
    }

    // noinspection JSUnusedGlobalSymbols
    validateEmail() {
        const address = this.model.get(this.name);

        if (!address) {
            return;
        }

        const addressLowerCase = String(address).toLowerCase();

        if (!this.emailAddressRegExp.test(addressLowerCase) && address.indexOf(this.erasedPlaceholder) !== 0) {
            const msg = this.translate('fieldShouldBeEmail', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg);

            return true;
        }
    }

    quickView(data) {
        const helper = new RecordModal();

        helper.showDetail(this, {
            id: data.id,
            entityType: data.scope,
        });
    }
}

export default EmailFromAddressVarchar;

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
import Select from 'ui/select';

export default class extends BaseFieldView {

    detailTemplate = 'email/fields/email-address-varchar/detail'
    editTemplate = 'email/fields/compose-from-address/edit'

    data() {
        let noSmtpMessage = this.translate('noSmtpSetup', 'messages', 'Email');

        const linkHtml = $('<a>')
            .attr('href', '#EmailAccount')
            .text(this.translate('EmailAccount', 'scopeNamesPlural'))
            .get(0).outerHTML;

        noSmtpMessage = noSmtpMessage.replace('{link}', linkHtml);

        return {
            list: this.list,
            noSmtpMessage: noSmtpMessage,
            ...super.data(),
        };
    }

    setup() {
        super.setup();

        this.nameHash = {...(this.model.get('nameHash') || {})};
        this.typeHash = this.model.get('typeHash') || {};
        this.idHash = this.model.get('idHash') || {};

        this.list = this.getUser().get('emailAddressList') || [];
    }

    afterRenderEdit() {
        if (this.$element.length) {
            Select.init(this.$element);
        }
    }

    getValueForDisplay() {
        if (this.isDetailMode()) {
            const address = this.model.get(this.name);

            return this.getDetailAddressHtml(address);
        }

        return super.getValueForDisplay();
    }

    getDetailAddressHtml(address) {
        if (!address) {
            return '';
        }

        const name = this.nameHash[address] || null;

        const entityType = this.typeHash[address] || null;
        const id = this.idHash[address] || null;

        if (id && name) {
            return $('<div>')
                .append(
                    $('<a>')
                        .attr('href', `#${entityType}/view/${id}`)
                        .attr('data-scope', entityType)
                        .attr('data-id', id)
                        .text(name),
                    ' ',
                    $('<span>').addClass('text-muted chevron-right'),
                    ' ',
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
                    .append(
                        ' ',
                        $('<span>').addClass('text-muted chevron-right'),
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
}

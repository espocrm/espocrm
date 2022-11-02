/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

define('views/email/fields/compose-from-address', ['views/fields/base'], function (Dep) {

    return Dep.extend({

        detailTemplate: 'email/fields/email-address-varchar/detail',

        editTemplate: 'email/fields/compose-from-address/edit',

        data: function () {
            let noSmtpMessage = this.translate('noSmtpSetup', 'messages', 'Email');

            let linkHtml = $('<a>')
                    .attr('href', '#EmailAccount')
                    .text(this.translate('EmailAccount', 'scopeNamesPlural'))
                    .get(0).outerHTML;

            noSmtpMessage = noSmtpMessage.replace('{link}', linkHtml);

            return {
                list: this.list,
                noSmtpMessage: noSmtpMessage,
                ...Dep.prototype.data.call(this),
            };
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.nameHash = {...(this.model.get('nameHash') || {})};
            this.typeHash = this.model.get('typeHash') || {};
            this.idHash = this.model.get('idHash') || {};

            this.list = this.getUser().get('emailAddressList') || [];
        },

        getValueForDisplay: function () {
            if (this.isDetailMode()) {
                let address = this.model.get(this.name);

                return this.getDetailAddressHtml(address);
            }

            return Dep.prototype.getValueForDisplay.call(this);
        },

        getDetailAddressHtml: function (address) {
            if (!address) {
                return '';
            }

            let name = this.nameHash[address] || null;

            let entityType = this.typeHash[address] || null;
            let id = this.idHash[address] || null;

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

            let $div = $('<div>');

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
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('crm:views/account/fields/shipping-address', ['views/fields/address'], function (Dep) {

    return Dep.extend({

        copyFrom: 'billingAddress',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.attributePartList = this.getMetadata().get(['fields', 'address', 'actualFields']) || [];

            this.allAddressAttributeList = [];

            this.attributePartList.forEach(part => {
                this.allAddressAttributeList.push(this.copyFrom + Espo.Utils.upperCaseFirst(part));
                this.allAddressAttributeList.push(this.name + Espo.Utils.upperCaseFirst(part));
            });

            this.listenTo(this.model, 'change', () => {
                var isChanged = false;

                this.allAddressAttributeList.forEach(attribute => {
                    if (this.model.hasChanged(attribute)) {
                        isChanged = true;
                    }
                });

                if (isChanged) {
                    if (this.isEditMode() && this.isRendered() && this.$copyButton) {
                        if (this.toShowCopyButton()) {
                            this.$copyButton.removeClass('hidden');
                        } else {
                            this.$copyButton.addClass('hidden');
                        }
                    }
                }
            });
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.mode === 'edit') {
                var label = this.translate('Copy Billing', 'labels', 'Account');
                this.$copyButton = $('<button class="btn btn-default btn-sm">' + label + '</button>');

                this.$copyButton.on('click', () => {
                    this.copy(this.copyFrom);
                });

                if (!this.toShowCopyButton()) {
                    this.$copyButton.addClass('hidden');
                }

                this.$el.append(this.$copyButton);
            }
        },

        copy: function (fieldFrom) {
            Object.keys(this.getMetadata().get('fields.address.fields'))
                .forEach(attr => {
                    let destField = this.name + Espo.Utils.upperCaseFirst(attr);
                    let sourceField = fieldFrom + Espo.Utils.upperCaseFirst(attr);

                    this.model.set(destField, this.model.get(sourceField));
                });
        },

        toShowCopyButton: function () {
            var billingIsNotEmpty = false;
            var shippingIsNotEmpty = false;

            this.attributePartList.forEach(part => {
                let attribute1 = this.copyFrom + Espo.Utils.upperCaseFirst(part);

                if (this.model.get(attribute1)) {
                    billingIsNotEmpty = true;
                }

                let attribute2 = this.name + Espo.Utils.upperCaseFirst(part);

                if (this.model.get(attribute2)) {
                    shippingIsNotEmpty = true;
                }
            });

            return billingIsNotEmpty && !shippingIsNotEmpty;
        },
    });
});

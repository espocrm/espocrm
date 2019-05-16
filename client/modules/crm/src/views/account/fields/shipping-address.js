/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

define('crm:views/account/fields/shipping-address', 'views/fields/address', function (Dep) {

    return Dep.extend({

        copyFrom: 'billingAddress',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.attributePartList = this.getMetadata().get(['fields', 'address', 'actualFields']) || [];

            this.allAddressAttributeList = [];
            this.attributePartList.forEach(function (part) {
                this.allAddressAttributeList.push(this.copyFrom + Espo.Utils.upperCaseFirst(part));
                this.allAddressAttributeList.push(this.name + Espo.Utils.upperCaseFirst(part));
            }, this);

            this.listenTo(this.model, 'change', function () {
                var isChanged = false;
                this.allAddressAttributeList.forEach(function (attribute) {
                    if (this.model.hasChanged(attribute)) {
                        isChanged = true;
                    }
                }, this);
                if (isChanged) {
                    if (this.isEditMode() && this.isRendered() && this.$copyButton) {
                        if (this.toShowCopyButton()) {
                            this.$copyButton.removeClass('hidden');
                        } else {
                            this.$copyButton.addClass('hidden');
                        }
                    }
                }
            }, this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.mode == 'edit') {
                var label = this.translate('Copy Billing', 'labels', 'Account');
                this.$copyButton = $('<button class="btn btn-default btn-sm">' + label + '</button>');
                this.$copyButton.on('click', function () {
                    this.copy(this.copyFrom);
                }.bind(this));
                if (!this.toShowCopyButton()) {
                    this.$copyButton.addClass('hidden');
                }
                this.$el.append(this.$copyButton);
            }
        },

        copy: function (fieldFrom) {
            var attrList = Object.keys(this.getMetadata().get('fields.address.fields')).forEach(function (attr) {
                destField = this.name + Espo.Utils.upperCaseFirst(attr);
                sourceField = fieldFrom + Espo.Utils.upperCaseFirst(attr);

                this.model.set(destField, this.model.get(sourceField));
            }, this);
        },

        toShowCopyButton: function () {
            var billingIsNotEmpty = false;
            var shippingIsNotEmpty = false;
            this.attributePartList.forEach(function (part) {
                var attribute = this.copyFrom + Espo.Utils.upperCaseFirst(part);
                if (this.model.get(attribute)) {
                    billingIsNotEmpty = true;
                }
                var attribute = this.name + Espo.Utils.upperCaseFirst(part);
                if (this.model.get(attribute)) {
                    shippingIsNotEmpty = true;
                }
            }, this);

            return billingIsNotEmpty && !shippingIsNotEmpty;
        },

    });
});

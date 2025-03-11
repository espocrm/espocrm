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

import AddressFieldView from 'views/fields/address';

export default class extends AddressFieldView {

    copyFrom = 'billingAddress'

    setup() {
        super.setup();

        this.addActionHandler('copyFromBilling', () => this.copy());

        this.attributePartList = this.getMetadata().get(['fields', 'address', 'actualFields']) || [];

        this.allAddressAttributeList = [];

        this.attributePartList.forEach(part => {
            this.allAddressAttributeList.push(this.copyFrom + Espo.Utils.upperCaseFirst(part));
            this.allAddressAttributeList.push(this.name + Espo.Utils.upperCaseFirst(part));
        });

        this.listenTo(this.model, 'change', () => {
            let isChanged = false;

            for (const attribute of this.allAddressAttributeList) {
                if (this.model.hasChanged(attribute)) {
                    isChanged = true;

                    break;
                }
            }

            if (!isChanged) {
                return;
            }

            if (!this.isEditMode() || !this.isRendered() || !this.copyButtonElement) {
                return;
            }

            if (this.toShowCopyButton()) {
                this.copyButtonElement.classList.remove('hidden');
            } else {
                this.copyButtonElement.classList.add('hidden');
            }
        });
    }

    afterRender() {
        super.afterRender();

        if (this.mode === this.MODE_EDIT && this.element) {
            const label = this.translate('Copy Billing', 'labels', 'Account');

            const button = this.copyButtonElement = document.createElement('button');

            button.classList.add('btn', 'btn-default', 'btn-sm', 'action');
            button.textContent = label;
            button.setAttribute('data-action', 'copyFromBilling')

            if (!this.toShowCopyButton()) {
                button.classList.add('hidden');
            }

            this.element.append(button);
        }
    }

    /**
     * @private
     */
    copy() {
        const fieldFrom = this.copyFrom;

        Object.keys(this.getMetadata().get('fields.address.fields') || {})
            .forEach(attr => {
                const destField = this.name + Espo.Utils.upperCaseFirst(attr);
                const sourceField = fieldFrom + Espo.Utils.upperCaseFirst(attr);

                this.model.set(destField, this.model.get(sourceField));
            });
    }

    /**
     * @private
     * @return {boolean}
     */
    toShowCopyButton() {
        let billingIsNotEmpty = false;
        let shippingIsNotEmpty = false;

        this.attributePartList.forEach(part => {
            const attribute1 = this.copyFrom + Espo.Utils.upperCaseFirst(part);

            if (this.model.get(attribute1)) {
                billingIsNotEmpty = true;
            }

            const attribute2 = this.name + Espo.Utils.upperCaseFirst(part);

            if (this.model.get(attribute2)) {
                shippingIsNotEmpty = true;
            }
        });

        return billingIsNotEmpty && !shippingIsNotEmpty;
    }
}

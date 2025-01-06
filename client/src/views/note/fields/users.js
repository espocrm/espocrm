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

import LinkMultipleFieldView from 'views/fields/link-multiple';

export default class extends LinkMultipleFieldView {

    init() {
        this.messagePermission = this.getAcl().getPermissionLevel('message');
        this.portalPermission = this.getAcl().getPermissionLevel('portal');

        if (this.messagePermission === 'no' && this.portalPermission === 'no') {
            this.readOnly = true;
        }

        super.init();
    }

    getSelectBoolFilterList() {
        if (this.messagePermission === 'team') {
            return ['onlyMyTeam'];
        }

        if (this.portalPermission === 'yes') {
            return null;
        }
    }

    getSelectPrimaryFilterName() {
        if (this.portalPermission === 'yes' && this.messagePermission === 'no') {
            return 'activePortal';
        }

        return 'active';
    }

    getSelectFilterList() {
        if (this.portalPermission === 'yes') {
            if (this.messagePermission === 'no') {
                 return ['activePortal'];
            }

            return ['active', 'activePortal'];
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    prepareEditItemElement(id, name) {
        const itemElement = super.prepareEditItemElement(id, name);

        const avatarHtml = this.getHelper().getAvatarHtml(id, 'small', 18, 'avatar-link');

        if (avatarHtml) {
            const img = new DOMParser().parseFromString(avatarHtml, 'text/html').body.childNodes[0];

            itemElement.prepend(img);
        }

        return itemElement;
    }
}

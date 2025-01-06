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

export default class extends BaseFieldView {

    editTemplate = 'admin/entity-manager/fields/icon-class/edit'

    setup() {
        super.setup();

        this.addActionHandler('selectIcon', () => this.selectIcon());
    }

    selectIcon() {
        this.createView('dialog', 'views/admin/entity-manager/modals/select-icon', {}, view => {
            view.render();

            this.listenToOnce(view, 'select', value => {
                if (value === '') {
                    value = null;
                }

                this.model.set(this.name, value);

                view.close();
            });
        });
    }

    fetch() {
        const data = {};

        data[this.name] = this.model.get(this.name);

        return data;
    }
}

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

import MultiEnumFieldView from 'views/fields/multi-enum';
import LayoutsIndex from 'views/admin/layouts/index';

export default class extends MultiEnumFieldView {

    typeList = [
        'list',
        'detail',
        'listSmall',
        'detailSmall',
        'bottomPanelsDetail',
        'filters',
        'massUpdate',
        'sidePanelsDetail',
        'sidePanelsEdit',
        'sidePanelsDetailSmall',
        'sidePanelsEditSmall',
    ]

    setupOptions() {
        this.params.options = [];
        this.translatedOptions = {};

        this.scopeList = Object.keys(this.getMetadata().get('scopes'))
            .filter(item => this.getMetadata().get(['scopes', item, 'layouts']))
            .sort((v1, v2) => {
                return this.translate(v1, 'scopeNames')
                    .localeCompare(this.translate(v2, 'scopeNames'));
            });

        const dataList = LayoutsIndex.prototype.getLayoutScopeDataList.call(this);

        dataList.forEach(item1 => {
            item1.typeList.forEach(type => {
                const item = item1.scope + '.' + type;

                if (type.substr(-6) === 'Portal') {
                    return;
                }

                this.params.options.push(item);

                this.translatedOptions[item] = this.translate(item1.scope, 'scopeNames') + ' . ' +
                    this.translate(type, 'layouts', 'Admin');
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    translateLayoutName(type, scope) {
        return LayoutsIndex.prototype.translateLayoutName.call(this, type, scope);
    }
}

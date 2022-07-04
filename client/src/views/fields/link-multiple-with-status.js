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

define('views/fields/link-multiple-with-status', ['views/fields/link-multiple'], function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.columnsName = this.name + 'Columns';
            this.columns = Espo.Utils.cloneDeep(this.model.get(this.columnsName) || {});

            this.listenTo(this.model, 'change:' + this.columnsName, () => {
                this.columns = Espo.Utils.cloneDeep(this.model.get(this.columnsName) || {});
            });

            this.statusField = this.getMetadata()
                .get(['entityDefs', this.model.entityType,  'fields', this.name, 'columns', 'status']);

            this.styleMap = this.getMetadata()
                .get(['entityDefs', this.foreignScope, 'fields', this.statusField, 'style']) || {};
        },

        getAttributeList: function () {
            let list = Dep.prototype.getAttributeList.call(this);

            list.push(this.name + 'Columns');

            return list;
        },

        getDetailLinkHtml: function (id, name) {
            let status = (this.columns[id] || {}).status;

            if (!status) {
                return Dep.prototype.getDetailLinkHtml.call(this, id, name);
            }

            let style = this.styleMap[status];

            let targetStyleList = ['success', 'danger'];

            if (!style || !~targetStyleList.indexOf(style)) {
                return Dep.prototype.getDetailLinkHtml.call(this, id, name);
            }

            let iconStyle = '';

            if (style === 'success') {
                iconStyle = 'fas fa-check text-success small';
            }
            else if (style === 'danger') {
                iconStyle = 'fas fa-times text-danger small';
            }

            return '<span class="' + iconStyle + '"></span> ' +
                Dep.prototype.getDetailLinkHtml.call(this, id, name);
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

Espo.define('crm:views/knowledge-base-article/record/row-actions/default', 'views/record/row-actions/default', function (Dep) {

    return Dep.extend({

        getActionList: function () {
            var actionList = Dep.prototype.getActionList.call(this);

            if (this.options.acl.edit && this.model.collection && this.model.collection.orderBy == 'order' && this.model.collection.order === 'asc') {
                actionList.push({
                    action: 'moveToTop',
                    label: 'Move to Top',
                    data: {
                        id: this.model.id
                    }
                });
                actionList.push({
                    action: 'moveUp',
                    label: 'Move Up',
                    data: {
                        id: this.model.id
                    }
                });
                actionList.push({
                    action: 'moveDown',
                    label: 'Move Down',
                    data: {
                        id: this.model.id
                    }
                });
                actionList.push({
                    action: 'moveToBottom',
                    label: 'Move to Bottom',
                    data: {
                        id: this.model.id
                    }
                });
            }

            return actionList;
        }
    });

});

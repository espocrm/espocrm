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

define('crm:views/record/row-actions/activities-dashlet', ['views/record/row-actions/view-and-edit'], function (Dep) {

    return Dep.extend({

        getActionList: function () {
            var actionList = Dep.prototype.getActionList.call(this);

            var scope = this.model.entityType;

            actionList.forEach(function (item) {
                item.data = item.data || {};
                item.data.scope = this.model.entityType;
            }, this);

            if (scope === 'Task') {
                if (this.options.acl.edit && !~['Completed', 'Canceled'].indexOf(this.model.get('status'))) {
                    actionList.push({
                        action: 'setCompleted',
                        label: 'Complete',
                        data: {
                            id: this.model.id
                        },
                        groupIndex: 1,
                    });
                }
            } else {
                if (this.options.acl.edit && !~['Held', 'Not Held'].indexOf(this.model.get('status'))) {
                    actionList.push({
                        action: 'setHeld',
                        label: 'Set Held',
                        data: {
                            id: this.model.id,
                            scope: this.model.entityType
                        },
                        groupIndex: 1,
                    });
                    actionList.push({
                        action: 'setNotHeld',
                        label: 'Set Not Held',
                        data: {
                            id: this.model.id,
                            scope: this.model.entityType
                        },
                        groupIndex: 1,
                    });
                }
            }

            if (this.options.acl.edit) {
                actionList.push({
                    action: 'quickRemove',
                    label: 'Remove',
                    data: {
                        id: this.model.id,
                        scope: this.model.entityType
                    },
                    groupIndex: 0,
                });
            }

            return actionList;
        },
    });
});

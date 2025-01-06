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

define('crm:views/meeting/record/panels/scheduler', ['views/record/panels/bottom'], function (Dep) {

    return Dep.extend({

        templateContent: '<div class="scheduler-container no-margin">{{{scheduler}}}</div>',

        setup: function () {
            Dep.prototype.setup.call(this);

            var viewName = this.getMetadata().get(['clientDefs', this.scope, 'schedulerView']) ||
                'crm:views/scheduler/scheduler';

            this.createView('scheduler', viewName, {
                selector: '.scheduler-container',
                notToRender: true,
                model: this.model,
            });

            this.once('after:render', () => {
                if (this.disabled) {
                    return;
                }

                this.getView('scheduler').render();
                this.getView('scheduler').notToRender = false;
            });

            if (this.defs.disabled) {
                this.once('show', () => {
                    this.getView('scheduler').render();
                    this.getView('scheduler').notToRender = false;
                });
            }
        },

        actionRefresh: function () {
            this.getView('scheduler').reRender();
        },
    });
});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/

Espo.define('views/email/record/row-actions/default', 'views/record/row-actions/default', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);
            this.listenTo(this.model, 'change:isImportant', function () {
                setTimeout(function () {
                    this.reRender();
                }.bind(this), 10);
            }, this);
        },

        getActionList: function () {
            var list = Dep.prototype.getActionList.call(this);
            if (!this.model.get('isImportant')) {
                list.push({
                    action: 'markAsImportant',
                    label: 'Mark as Important',
                    data: {
                        id: this.model.id
                    }
                });
            } else {
                list.push({
                    action: 'markAsNotImportant',
                    label: 'Mark as Not Important',
                    data: {
                        id: this.model.id
                    }
                });
            }
            return list;
        }

    });

});



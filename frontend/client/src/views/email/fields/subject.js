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
Espo.define('Views.Email.Fields.Subject', 'Views.Fields.Varchar', function (Dep) {

    return Dep.extend({

        listLinkTemplate: 'email.fields.subject.list-link',

        data: function () {
            var status = this.model.get('status');

            return _.extend({
                'isRead': !(~['Archived', 'Received'].indexOf(status)) || this.model.get('isRead')
            }, Dep.prototype.data.call(this));
        },

        getValueForDisplay: function () {
            return this.model.get('name');
        },

        getAttributeList: function () {
            return ['name', 'isRead'];
        },

        setup: function () {
            Dep.prototype.setup.call(this);
            this.listenTo(this.model, 'change:isRead', function () {
                this.reRender();
            }, this);
        }

    });

});

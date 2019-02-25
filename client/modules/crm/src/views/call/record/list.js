/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('crm:views/call/record/list', 'views/record/list', function (Dep) {

    return Dep.extend({

        rowActionsView: 'crm:views/call/record/row-actions/default',

        setup: function () {
            Dep.prototype.setup.call(this);
            this.massActionList.push('setHeld');
            this.massActionList.push('setNotHeld');
        },

        actionSetHeld: function (data) {
            var id = data.id;
            if (!id) {
                return;
            }
            var model = this.collection.get(id);
            if (!model) {
                return;
            }

            model.set('status', 'Held');

            this.listenToOnce(model, 'sync', function () {
                this.notify(false);
                this.collection.fetch();
            }, this);

            this.notify('Saving...');
            model.save();

        },

        actionSetNotHeld: function (data) {
            var id = data.id;
            if (!id) {
                return;
            }
            var model = this.collection.get(id);
            if (!model) {
                return;
            }

            model.set('status', 'Not Held');

            this.listenToOnce(model, 'sync', function () {
                this.notify(false);
                this.collection.fetch();
            }, this);

            this.notify('Saving...');
            model.save();
        },

        massActionSetHeld: function () {
            this.notify('Please wait...');
            var data = {};
            data.ids = this.checkedList;
            $.ajax({
                url: this.collection.url + '/action/massSetHeld',
                type: 'POST',
                data: JSON.stringify(data)
            }).done(function (result) {
                this.notify(false);
                this.listenToOnce(this.collection, 'sync', function () {
                    data.ids.forEach(function (id) {
                        if (this.collection.get(id)) {
                            this.checkRecord(id);
                        }
                    }, this);
                }, this);
                this.collection.fetch();
            }.bind(this));
        },

        massActionSetNotHeld: function () {
            this.notify('Please wait...');
            var data = {};
            data.ids = this.checkedList;
            $.ajax({
                url: this.collection.url + '/action/massSetNotHeld',
                type: 'POST',
                data: JSON.stringify(data)
            }).done(function (result) {
                this.notify(false);
                this.listenToOnce(this.collection, 'sync', function () {
                    data.ids.forEach(function (id) {
                        if (this.collection.get(id)) {
                            this.checkRecord(id);
                        }
                    }, this);
                }, this);
                this.collection.fetch();
            }.bind(this));
        },

    });

});

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

Espo.define('Crm:Views.Call.Record.Detail', 'Views.Record.Detail', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);
            if (this.getAcl().checkModel(this.model, 'edit')) {
                this.dropdownItemList.push({
                    'label': 'Duplicate',
                    'name': 'Duplicate'
                });
                if (['Held', 'Not Held'].indexOf(this.model.get('status')) == -1) {
                    this.dropdownItemList.push({
                        'label': 'Set Held',
                        'name': 'setHeld'
                    });
                    this.dropdownItemList.push({
                        'label': 'Set Not Held',
                        'name': 'setNotHeld'
                    });
                }
            }
        },

        actionSetHeld: function () {
                this.model.save({
                    status: 'Held'
                }, {
                    patch: true,
                    success: function () {
                        Espo.Ui.success(this.translate('Saved'));
                        this.removeButton('setHeld');
                        this.removeButton('setNotHeld');
                    }.bind(this),
                });
        },

        actionSetNotHeld: function () {
                this.model.save({
                    status: 'Not Held'
                }, {
                    patch: true,
                    success: function () {
                        Espo.Ui.success(this.translate('Saved'));
                        this.removeButton('setHeld');
                        this.removeButton('setNotHeld');
                    }.bind(this),
                });
        },

    });
});


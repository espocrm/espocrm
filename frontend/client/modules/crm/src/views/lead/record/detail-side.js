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

Espo.define('Crm:Views.Lead.Record.DetailSide', 'Views.Record.DetailSide', function (Dep) {

    return Dep.extend({

        setupPanels: function () {
            if (this.model.get('status') == 'Converted') {
                var panel = {
                    name: 'convertedTo',
                    label: 'Converted To',
                    view: 'Record.Panels.Side',
                    notRefreshable: true,
                    options: {
                        fieldList: [],
                        mode: 'detail',
                    }
                };

                if (this.model.get('createdAccountId')) {
                    panel.options.fieldList.push('createdAccount');
                }
                if (this.model.get('createdContactId')) {
                    panel.options.fieldList.push('createdContact');
                }
                if (this.model.get('createdOpportunityId')) {
                    panel.options.fieldList.push('createdOpportunity');
                }
                if (panel.options.fieldList.length) {
                    this.panels = Espo.Utils.clone(this.panels);
                    this.panels.splice(1, 0, panel);
                }
            }
        }

    });
});


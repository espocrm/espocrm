/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('Crm:Views.InboundEmail.Record.Detail', 'Views.Record.Detail', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);
            this.handleDistributionField();
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            this.initSslFieldListening();
        },

        handleDistributionField: function () {
            var handleRequirement = function (model) {
                if (model.get('createCase') && ['Round-Robin', 'Least-Busy'].indexOf(model.get('caseDistribution')) != -1) {
                    this.getFieldView('team').setRequired();
                } else {
                    this.getFieldView('team').setNotRequired();
                }
            }.bind(this);

            this.listenTo(this.model, 'change:createCase', function (model) {
                if (!model.get('createCase')) {
                    this.model.set('caseDistribution', 'Direct-Assignment');
                }
            }, this);

            this.on('render', function () {
                handleRequirement(this.model);
            }, this);

            this.listenTo(this.model, 'change:caseDistribution', function (model) {
                handleRequirement(model);
            });
        },

        initSslFieldListening: function () {
            var sslField = this.getFieldView('ssl');
            this.listenTo(sslField, 'change', function () {
                var ssl = sslField.fetch()['ssl'];
                if (ssl) {
                    this.model.set('port', '993');
                } else {
                    this.model.set('port', '143');
                }
            }, this);
        }

    });

});


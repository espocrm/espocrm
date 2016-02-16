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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('views/inbound-email/record/detail', 'views/record/detail', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);
            this.handleDistributionField();
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            this.initSslFieldListening();

            if (this.wasFetched()) {
                this.setFieldReadOnly('fetchSince');
            }
        },

        wasFetched: function () {
            if (!this.model.isNew()) {
                return !!((this.model.get('fetchData') || {}).lastUID);
            }
            return false;
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
                    if (this.model.get('caseDistribution') !== '') {
                        this.model.set('caseDistribution', 'Direct-Assignment');
                    }
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


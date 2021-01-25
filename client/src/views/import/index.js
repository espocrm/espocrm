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

define('views/import/index', 'view', function (Dep) {

    return Dep.extend({

        template: 'import/index',

        data: function () {
            return {
            };
        },

        formData: null,

        fileContents: null,

        setup: function () {
            this.entityType = this.options.entityType || null;

            this.startFromStep = 1;

            if (this.options.formData || this.options.fileContents) {
                this.formData = this.options.formData || {};
                this.fileContents = this.options.fileContents || null;

                this.entityType = this.formData.entityType || null;

                if (this.options.step) {
                    this.startFromStep = this.options.step;
                }
            }
        },

        changeStep: function (num, result) {
            this.step = num;

            if (num > 1) {
                this.setConfirmLeaveOut(true);
            }

            this.createView('step', 'views/import/step' + num.toString(), {
                el: this.options.el + ' > .import-container',
                entityType: this.entityType,
                formData: this.formData,
                result: result
            }, function (view) {
                view.render();
            });

            var url = '#Import';
            if (this.step > 1) {
                url += '/index/step=' + this.step;
            }
            this.getRouter().navigate(url, {trigger: false})
        },

        afterRender: function () {
            this.changeStep(this.startFromStep);
        },

        updatePageTitle: function () {
            this.setPageTitle(this.getLanguage().translate('Import', 'labels', 'Admin'));
        },

        setConfirmLeaveOut: function (value) {
            this.getRouter().confirmLeaveOut = value;
        },

    });
});

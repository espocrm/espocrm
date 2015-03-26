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
Espo.define('Views.Import.Index', 'View', function (Dep) {

    return Dep.extend({

        template: 'import.index',

        data: function () {
            return {

            };
        },

        formData: null,

        fileContents: null,

        setup: function () {
            this.entityType = this.options.entityType || false;
        },

        changeStep: function (num, result) {
            this.createView('step', 'Import.Step' + num.toString(), {
                el: this.options.el + ' > .import-container',
                entityType: this.entityType,
                formData: this.formData,
                result: result
            }, function (view) {
                view.render();
            });
        },

        afterRender: function () {
            this.changeStep(1);
        },

        updatePageTitle: function () {
            this.setPageTitle(this.getLanguage().translate('Import'));
        },

    });
});

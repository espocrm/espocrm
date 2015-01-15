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
Espo.define('Controllers.Base', 'Controller', function (Dep) {

    return Dep.extend({

        login: function () {
            this.entire('Login', {}, function (login) {
                login.render();
                login.on('login', function (data) {
                    this.trigger('login', data);
                }.bind(this));
            }.bind(this));
        },

        logout: function () {
            this.trigger('logout');
        },
        
        clearCache: function (options) {                
            var cache = this.getCache();
            if (cache) {
                cache.clear();
                this.getRouter().navigateBack();
                window.location.reload();
            } else {
                Espo.Ui.notify('Cache is not enabled', 'error', 3000);
                this.getRouter().navigateBack();
            }
        },

        error404: function () {
            this.entire('Base', {template: 'errors.404'}, function (view) {
                view.render();
            });
        },

        error403: function () {
            this.entire('Base', {template: 'errors.403'}, function (view) {
                view.render();
            });
        },
        

    });
});


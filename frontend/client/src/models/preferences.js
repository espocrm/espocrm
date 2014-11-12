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
Espo.define('Models.Preferences', 'Model', function (Dep) {
    
    return Dep.extend({
    
        name: "Preferences",
        
        settings: null,
    
        getTimeZoneOptions: function () {
            if (this.settings) {
                return this.settings.getFieldParam('timeZone', 'options');
            }
        },
        
        getLanguageOptions: function () {
            if (this.settings) {
                return this.settings.getFieldParam('language', 'options');
            }
        },
        
        getDefaultCurrencyOptions: function () {
            if (this.settings) {
                return this.settings.getDefaultCurrencyOptions();
            }
        },
        
        setDashletOptions: function (id, attributes) {
            var value = this.get('dashletOptions') || {};
            value[id] = attributes;
            this.set('dashletOptions', value);
        },
        
        getDashletOptions: function (id) {
            var value = this.get('dashletOptions') || {};
            return value[id] || false;
        },
        
        unsetDashletOptions: function (id) {
            var value = this.get('dashletOptions') || {};
            delete value[id];
            if (Object.getOwnPropertyNames(value).length == 0) {
                value = null;
            }
            this.set('dashletOptions', value);
        },

    });

});

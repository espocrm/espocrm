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

Espo.define('crm:views/dashlets/calendar', 'views/dashlets/abstract/base', function (Dep) {

    return Dep.extend({

        name: 'Calendar',

        noPadding: true,

        _template: '<div class="calendar-container">{{{calendar}}} </div>',

        init: function () {
            Dep.prototype.init.call(this);
            this.optionsFields['enabledScopeList'].options = this.getMetadata().get('clientDefs.Calendar.scopeList') || this.optionsFields['enabledScopeList'].options;
        },

        afterRender: function () {
            this.createView('calendar', 'crm:views/calendar/calendar', {
                mode: this.getOption('mode'),
                el: this.options.el + ' > .calendar-container',
                header: false,
                enabledScopeList: this.getOption('enabledScopeList'),
                containerSelector: this.options.el
            }, function (view) {
                view.render();
                this.on('resize', function () {
                    view.adjustSize();
                });
            }, this);
        }
    });
});



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

define('views/clear-cache', 'view', function (Dep) {

    return Dep.extend({

        template: 'clear-cache',

        el: '> body',

        events: {
            'click .action[data-action="clearLocalCache"]': function () {
                this.clearLocalCache();
            },
            'click .action[data-action="returnToApplication"]': function () {
                this.returnToApplication();
            }
        },

        data: function () {
            return {
                cacheIsEnabled: !!this.options.cache
            };
        },

        clearLocalCache: function () {
            this.options.cache.clear();
            this.$el.find('.action[data-action="clearLocalCache"]').remove();
            this.$el.find('.message-container').removeClass('hidden');
            this.$el.find('.message-container span').html(this.translate('Cache has been cleared'));
            this.$el.find('.action[data-action="returnToApplication"]').removeClass('hidden');
        },

        returnToApplication: function () {
            this.getRouter().navigate('', {trigger: true});
        }

    });
});

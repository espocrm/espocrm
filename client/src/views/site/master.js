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

Espo.define('views/site/master', 'view', function (Dep) {

    return Dep.extend({

        template: 'site/master',

        views: {
            header: {
                id: 'header',
                view: 'views/site/header'
            },
            main: {
                id: 'main',
                view: false,
            },
            footer: {
                el: 'body > footer',
                view: 'views/site/footer'
            },
        },

        showLoadingNotification: function () {
            this.notify('Loading...');
        },

        hideLoadingNotification: function () {
            this.notify(false);
        },

        afterRender: function () {
            var footerView = this.getView('footer');
            if (footerView) {
                var html = footerView.$el.html() || '';
                if ((html.match(/espocrm/gi) || []).length < 2) {
                    var text = 'PHAgY2xhc3M9ImNyZWRpdCBzbWFsbCI+JmNvcHk7IDxhIGhyZWY9Imh0dHA6Ly93d3cuZXNwb2NybS5jb20iPkVzcG9DUk08L2E+PC9wPg==';
                    if (typeof window.atob === "function") {
                        var decText = window.atob(text);
                    } else if (typeof atob === "function") {
                        var decText = atob(text);
                    }

                    if (decText) {
                        footerView.$el.html(decText);
                    }
                }
            }
        },
    });
});

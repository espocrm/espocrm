/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('views/global-search/panel', 'view', function (Dep) {

    return Dep.extend({

        template: 'global-search/panel',

        afterRender: function () {

            this.listenToOnce(this.collection, 'sync', function () {
                this.createView('list', 'views/record/list-expanded', {
                    el: this.options.el + ' .list-container',
                    collection: this.collection,
                    listLayout: {
                        rows: [
                            [
                                {
                                    name: 'name',
                                    view: 'views/global-search/name-field',
                                    params: {
                                        containerEl: this.options.el
                                    },
                                }
                            ]
                        ],
                        right: {
                            name: 'read',
                            view: 'views/global-search/scope-badge',
                            width: '80px'
                        }
                    }
                }, function (view) {
                    view.render();
                });
            }.bind(this));
            this.collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 10;
            this.collection.fetch();
        }

    });

});


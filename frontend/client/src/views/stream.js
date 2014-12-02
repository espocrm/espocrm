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

Espo.define('Views.Stream', 'View', function (Dep) {

    return Dep.extend({
    
        template: 'stream',
        
        events: {
        },

        data: function () {
            return {
                displayTitle: this.options.displayTitle
            };
        },

        setup: function () {
            
        },

        afterRender: function () {
            this.notify('Loading...');
            this.getCollectionFactory().create('Note', function (collection) {
                this.collection = collection;
                collection.url = 'Stream';
                
                this.listenToOnce(collection, 'sync', function () {
                    this.createView('list', 'Stream.List', {
                        el: this.options.el + ' .list-container',
                        collection: collection,
                        isUserStream: true,
                    }, function (view) {
                        view.notify(false);
                        view.render();
                    });
                }.bind(this));
                collection.fetch();
                
            }, this);
        },

    });
});


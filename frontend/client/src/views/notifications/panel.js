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

Espo.define('Views.Notifications.Panel', 'View', function (Dep) {

    return Dep.extend({

        template: 'notifications.panel',
        
        events: {
            'click [data-action="markAllNotificationsRead"]': function () {
                $.ajax({                
                    url: 'Notification/action/markAllRead',
                    type: 'POST'
                }).done(function (count) {
                    this.trigger('all-read');
                }.bind(this));
            },
        },
        
        setup: function () {
            this.wait(true);
            this.getCollectionFactory().create('Notification', function (collection) {
                this.collection = collection;
                collection.maxSize = 5;
                this.wait(false);
            }, this);            
        },
        
        afterRender: function () {            
            this.listenToOnce(this.collection, 'sync', function () {
                this.createView('list', 'Record.ListExpanded', {
                    el: this.options.el + ' .list-container',
                    collection: this.collection,
                    showCount: false,
                    listLayout: {
                        rows: [
                            [
                                {
                                    name: 'data',
                                    view: 'Notifications.Field',
                                    params: {
                                        containerEl: this.options.el
                                    },
                                }                            
                            ]
                        ],
                        right: {
                            name: 'read',
                            view: 'Notifications.Read',
                            width: '10px'
                        }
                    }
                }, function (view) {
                    view.render();
                });
            }.bind(this));            
            this.collection.fetch();
        },
        
    });
    
});

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
Espo.define('Controllers.Record', 'Controller', function (Dep) {

    return Dep.extend({

        viewMap: null,

        defaultAction: 'list',
        
        checkAccess: function (action) {
            if (this.getUser().isAdmin()) {
                return true;
            }
            if (this.getAcl().check(this.name, action)) {
                return true;
            }
            return false;
        },

        initialize: function () {
            this.viewMap = this.viewMap || {};
            this.viewsMap = this.viewsMap || {};
        },

        getViewName: function (type) {
            return this.viewMap[type] || this.getMetadata().get('clientDefs.' + this.name + '.views.' + type) || Espo.Utils.upperCaseFirst(type);
        },

        getViews: function (type) {
            var views = {};
            var recordView = this.getMetadata().get('clientDefs.' + this.name + '.recordViews.' + type);
            if (recordView) {
                if (!views.body) {
                    views.body = {};
                }
                views.body.view = recordView;
            }
            return views;
        },
        
        beforeList: function () {
            this.handleCheckAccess('read');
        },

        list: function (options) {                        
            this.getCollection(function (collection) {        


                this.main(this.getViewName('list'), {
                    scope: this.name,
                    collection: collection,
                });        
            });
        },
        
        beforeView: function () {
            this.handleCheckAccess('read');
        },

        view: function (options) {
            var id = options.id;
            
            var createView = function (model) {
                this.main(this.getViewName('detail'), {
                    scope: this.name,
                    model: model,
                    views: this.getViews('detail'),
                });    
            }.bind(this);
            
            if ('model' in options) {
                var model = options.model;
                createView(model);
                
                model.once('sync', function () {
                    this.hideLoadingNotification();
                }, this);
                this.showLoadingNotification();
                model.fetch();    
            } else {
                this.getModel(function (model) {
                    model.id = id;
                    
                    this.showLoadingNotification();
                    model.once('sync', function () {
                        createView(model);
                    }, this);                
                    model.fetch({main: true});
                });
            }
        },
        
        beforeCreate: function () {
            this.handleCheckAccess('edit');
        },

        create: function (options) {
            options = options || {};
            this.getModel(function (model) {
                model.populateDefaults();
                if (options.relate) {
                    model.setRelate(options.relate);
                }
                if (options.attributes) {
                    model.set(options.attributes)
                }

                this.main(this.getViewName('edit'), {
                    scope: this.name,
                    model: model,
                    returnUrl: options.returnUrl,
                    views: this.getViews('edit'),
                });
            });
        },
        
        beforeEdit: function () {
            this.handleCheckAccess('edit');
        },

        edit: function (options) {
            var id = options.id;

            this.getModel(function (model) {
                model.id = id;
                
                this.showLoadingNotification();
                model.once('sync', function () {

                    if (options.attributes) {
                        model.set(options.attributes)
                    }
                    
                    this.main(this.getViewName('edit'), {
                        scope: this.name,
                        model: model,
                        returnUrl: options.returnUrl,
                        views: this.getViews('edit'),
                    });    
                }, this);                
                model.fetch({main: true});
            });
        },
        
        beforeMerge: function () {
            this.handleCheckAccess('edit');
        },

        merge: function (options) {
            var ids = options.ids.split(',');

            this.getModel(function (model) {
                var models = [];

                var proceed = function () {
                    this.main('Merge', {
                        models: models,
                        scope: this.name
                    });
                }.bind(this);

                var i = 0;
                ids.forEach(function (id) {
                    var current = model.clone();
                    current.id = id;
                    models.push(current);
                    current.once('sync', function () {
                        i++;
                        if (i == ids.length) {
                            proceed();
                        }
                    });
                    current.fetch();
                }.bind(this));
            }.bind(this));
        },

        /**
         * Get collection for the current controller.
         * @param {Espo.Collection}.
         */
        getCollection: function (callback, context) {
            context = context || this;
            
            if (!this.name) {
                throw new Error('No collection for unnamed controller');
            }
            var collectionName = this.name;
            this.collectionFactory.create(collectionName, function (collection) {
                callback.call(context, collection);
            }, context);
        },

        /**
         * Get model for the current controller.
         * @param {Espo.Model}.
         */
        getModel: function (callback, context) {            
            context = context || this;
                            
            if (!this.name) {
                throw new Error('No collection for unnamed controller');
            }
            var modelName = this.name;
            this.modelFactory.create(modelName, function (model) {
                callback.call(context, model);
            }, context);
        },
    });

});

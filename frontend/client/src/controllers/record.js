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
Espo.define('controllers/record', 'controller', function (Dep) {

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
            this.collectionMap = {};
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
            var isReturn = options.isReturn;

            var key = this.name + 'List';

            if (!isReturn) {
                var stored = this.get('storedView-' + key);
                if (stored) {
                    stored.remove();
                    this.unset('storedView-' + key);
                }
            }

            this.getCollection(function (collection) {
                this.main(this.getViewName('list'), {
                    scope: this.name,
                    collection: collection
                }, null, isReturn, key);
            }.bind(this), null, isReturn);
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

                var o = {
                    scope: this.name,
                    model: model,
                    returnUrl: options.returnUrl,
                    returnDispatchParams: options.returnDispatchParams,
                    views: this.getViews('edit'),
                };

                if (options.attributes) {
                    model.set(options.attributes);
                }

                this.main(this.getViewName('edit'), o);
            });
        },

        beforeEdit: function () {
            this.handleCheckAccess('edit');
        },

        edit: function (options) {
            var id = options.id;

            this.getModel(function (model) {
                model.id = id;
                if (options.model) {
                    model = options.model;
                }

                this.showLoadingNotification();
                model.once('sync', function () {
                    var o = {
                        scope: this.name,
                        model: model,
                        returnUrl: options.returnUrl,
                        returnDispatchParams: options.returnDispatchParams,
                        views: this.getViews('edit'),
                    };

                    if (options.attributes) {
                        o.attributes = options.attributes;
                    }

                    this.main(this.getViewName('edit'), o);
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
         * @param {collection}.
         */
        getCollection: function (callback, context, usePreviouslyFetched) {
            context = context || this;

            if (!this.name) {
                throw new Error('No collection for unnamed controller');
            }
            var collectionName = this.name;
            if (usePreviouslyFetched) {
                if (collectionName in this.collectionMap) {
                    var collection = this.collectionMap[collectionName];// = this.collectionMap[collectionName].clone();
                    /*var maxSize = this.getConfig().get('recordsPerPage');
                    if (collection.length > maxSize) {
                        var k = collection.length - maxSize;
                        while (k) {
                            collection.pop();
                            k--;
                        }
                    }*/
                    callback.call(context, collection);
                    return;
                }
            }
            this.collectionFactory.create(collectionName, function (collection) {
                this.collectionMap[collectionName] = collection;
                this.listenTo(collection, 'sync', function () {
                    collection.isFetched = true;
                }, this);
                callback.call(context, collection);
            }, context);
        },

        /**
         * Get model for the current controller.
         * @param {model}.
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

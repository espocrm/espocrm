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

Espo.define('controllers/record', 'controller', function (Dep) {

    return Dep.extend({

        viewMap: null,

        defaultAction: 'list',

        checkAccess: function (action) {
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
            return this.viewMap[type] || this.getMetadata().get('clientDefs.' + this.name + '.views.' + type) || 'views/' + Espo.Utils.camelCaseToHyphen(type);
        },

        beforeList: function () {
            this.handleCheckAccess('read');
        },

        list: function (options) {
            var isReturn = options.isReturn;
            if (this.getRouter().backProcessed) {
                isReturn = true;
            }

            var key = this.name + 'List';

            if (!isReturn) {
                var stored = this.getStoredMainView(key);
                if (stored) {
                    this.clearStoredMainView(key);
                }
            }

            this.getCollection(function (collection) {
                this.main(this.getViewName('list'), {
                    scope: this.name,
                    collection: collection
                }, null, isReturn, key);
            }, this, false);
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
                    returnUrl: options.returnUrl,
                    returnDispatchParams: options.returnDispatchParams,
                });
            }.bind(this);

            if ('model' in options) {
                var model = options.model;
                createView(model);

                this.listenToOnce(model, 'sync', function () {
                    this.hideLoadingNotification();
                }, this);
                this.showLoadingNotification();
                model.fetch();
            } else {
                this.getModel(function (model) {
                    model.id = id;

                    this.showLoadingNotification();
                    this.listenToOnce(model, 'sync', function () {
                        createView(model);
                    }, this);
                    model.fetch({main: true});
                });
            }
        },

        beforeCreate: function () {
            this.handleCheckAccess('create');
        },

        create: function (options) {
            options = options || {};
            this.getModel(function (model) {
                if (options.relate) {
                    model.setRelate(options.relate);
                }

                var o = {
                    scope: this.name,
                    model: model,
                    returnUrl: options.returnUrl,
                    returnDispatchParams: options.returnDispatchParams
                };

                if (options.attributes) {
                    model.set(options.attributes);
                }

                this.listenToOnce(model, 'before:save', function () {
                    var key = this.name + 'List';
                    this.clearStoredMainView(key);
                }, this);

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
                this.listenToOnce(model, 'before:save', function () {
                    var key = this.name + 'List';
                    this.clearStoredMainView(key);
                }, this);

                this.showLoadingNotification();
                this.listenToOnce(model, 'sync', function () {
                    var o = {
                        scope: this.name,
                        model: model,
                        returnUrl: options.returnUrl,
                        returnDispatchParams: options.returnDispatchParams
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
                    this.main('views/merge', {
                        models: models,
                        scope: this.name,
                        collection: options.collection
                    });
                }.bind(this);

                var i = 0;
                ids.forEach(function (id) {
                    var current = model.clone();
                    current.id = id;
                    models.push(current);
                    this.listenToOnce(current, 'sync', function () {
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

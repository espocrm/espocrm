/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('controllers/record', ['controller'], function (Dep) {

    /**
     * A record controller.
     *
     * @class
     * @name Class
     * @extends module:controller.Class
     * @memberOf module:controllers/record
     */
    return Dep.extend(/** @lends module:controllers/record.Class# */{

        /**
         * A type => view-name map.
         * @protected
         * @type {Object.<string,string>}
         */
        viewMap: null,

        /**
         * @inheritDoc
         */
        defaultAction: 'list',

        /**
         * @inheritDoc
         */
        checkAccess: function (action) {
            if (this.getAcl().check(this.name, action)) {
                return true;
            }

            return false;
        },

        /**
         * @inheritDoc
         */
        initialize: function () {
            /**
             * A type => view-name map.
             * @protected
             * @type {Object.<string,string>}
             */
            this.viewMap = this.viewMap || {};
            this.viewsMap = this.viewsMap || {};

            /**
             * @private
             * @type {Object}
             */
            this.collectionMap = {};
        },

        /**
         * Get a view name/path.
         *
         * @protected
         * @param {'list'|'detail'|'edit'|'create'} type A type.
         * @returns {string}
         */
        getViewName: function (type) {
            return this.viewMap[type] ||
                this.getMetadata().get(['clientDefs', this.name, 'views', type]) ||
                'views/' + Espo.Utils.camelCaseToHyphen(type);
        },

        beforeList: function () {
            this.handleCheckAccess('read');
        },

        actionList: function (options) {
            let isReturn = options.isReturn || this.getRouter().backProcessed;

            let key = this.name + 'List';

            if (!isReturn && this.getStoredMainView(key)) {
                this.clearStoredMainView(key);
            }

            this.getCollection(collection => {
                let mediator = {};

                let abort = () => {
                    collection.abortLastFetch();
                    mediator.abort = true;
                    Espo.Ui.notify(false);
                };

                this.listenToOnce(this.baseController, 'action', abort);
                this.listenToOnce(collection, 'sync', () =>
                    this.stopListening(this.baseController, 'action', abort));

                this.main(this.getViewName('list'), {
                    scope: this.name,
                    collection: collection,
                    params: options,
                    mediator: mediator,
                }, null, isReturn, key);
            }, this, false);
        },

        beforeView: function () {
            this.handleCheckAccess('read');
        },

        createViewView: function (options, model, view) {
            view = view || this.getViewName('detail');

            this.main(view, {
                scope: this.name,
                model: model,
                returnUrl: options.returnUrl,
                returnDispatchParams: options.returnDispatchParams,
                params: options,
            });
        },

        /**
         * @protected
         * @param {module:model.Class} model
         * @param {Object} options
         */
        prepareModelView: function (model, options) {},

        actionView: function (options) {
            var id = options.id;

            var isReturn = this.getRouter().backProcessed;

            if (isReturn) {
                if (this.lastViewActionOptions && this.lastViewActionOptions.id === id) {
                    options = this.lastViewActionOptions;
                }
            }
            else {
                delete this.lastViewActionOptions;
            }

            this.lastViewActionOptions = options;

            var createView = (model) => {
                this.prepareModelView(model, options);

                this.createViewView.call(this, options, model);
            };

            if ('model' in options) {
                var model = options.model;

                createView(model);

                this.showLoadingNotification();

                model
                    .fetch()
                    .then(() => {
                        this.hideLoadingNotification();
                    });

                this.listenToOnce(this.baseController, 'action', () => {
                    model.abortLastFetch();
                });

                return;
            }

            this.getModel().then(model => {
                model.id = id;

                this.showLoadingNotification();

                model
                    .fetch({main: true})
                    .then(() => {
                        this.hideLoadingNotification();

                        if (model.get('deleted')) {
                            this.listenToOnce(model, 'after:restore-deleted', () => {
                                createView(model);
                            });

                            this.prepareModelView(model, options);
                            this.createViewView(options, model, 'views/deleted-detail');

                            return;
                        }

                        createView(model);
                    });

                this.listenToOnce(this.baseController, 'action', () => {
                    model.abortLastFetch();
                });
            });
        },

        beforeCreate: function () {
            this.handleCheckAccess('create');
        },

        /**
         * @protected
         * @param {module:model.Class} model
         * @param {Object} options
         */
        prepareModelCreate: function (model, options) {
            this.listenToOnce(model, 'before:save', () => {
                var key = this.name + 'List';

                var stored = this.getStoredMainView(key);

                if (stored && !stored.storeViewAfterCreate) {
                    this.clearStoredMainView(key);
                }
            });

            this.listenToOnce(model, 'after:save', () => {
                var key = this.name + 'List';

                var stored = this.getStoredMainView(key);

                if (stored && stored.storeViewAfterCreate && stored.collection) {
                    this.listenToOnce(stored, 'after:render', () => {
                        stored.collection.fetch();
                    });
                }
            });
        },

        create: function (options) {
            options = options || {};

            let optionsOptions = options.options || {};

            this.getModel().then((model) => {
                if (options.relate) {
                    model.setRelate(options.relate);
                }

                let o = {
                    scope: this.name,
                    model: model,
                    returnUrl: options.returnUrl,
                    returnDispatchParams: options.returnDispatchParams,
                    params: options,
                };

                for (let k in optionsOptions) {
                    o[k] = optionsOptions[k];
                }

                if (options.attributes) {
                    model.set(options.attributes);
                }

                this.prepareModelCreate(model, options);

                this.main(this.getViewName('edit'), o);
            });
        },

        actionCreate: function (options) {
            this.create(options);
        },

        beforeEdit: function () {
            this.handleCheckAccess('edit');
        },

        /**
         * @protected
         * @param {module:model.Class} model
         * @param {Object} options
         */
        prepareModelEdit: function (model, options) {
            this.listenToOnce(model, 'before:save', () => {
                var key = this.name + 'List';

                var stored = this.getStoredMainView(key);

                if (stored && !stored.storeViewAfterUpdate) {
                    this.clearStoredMainView(key);
                }
            });
        },

        actionEdit: function (options) {
            var id = options.id;

            let optionsOptions = options.options || {};

            this.getModel().then(model => {
                model.id = id;

                if (options.model) {
                    model = options.model;
                }

                this.prepareModelEdit(model, options);

                this.showLoadingNotification();

                model
                    .fetch({main: true})
                    .then(() => {
                        this.hideLoadingNotification();

                        var o = {
                            scope: this.name,
                            model: model,
                            returnUrl: options.returnUrl,
                            returnDispatchParams: options.returnDispatchParams,
                            params: options,
                        };

                        for (let k in optionsOptions) {
                            o[k] = optionsOptions[k];
                        }

                        if (options.attributes) {
                            o.attributes = options.attributes;
                        }

                        this.main(this.getViewName('edit'), o);
                    });

                this.listenToOnce(this.baseController, 'action', () => {
                    model.abortLastFetch();
                });
            });
        },

        beforeMerge: function () {
            this.handleCheckAccess('edit');
        },

        actionMerge: function (options) {
            var ids = options.ids.split(',');

            this.getModel().then((model) => {
                var models = [];

                var proceed = () => {
                    this.main('views/merge', {
                        models: models,
                        scope: this.name,
                        collection: options.collection
                    });
                };

                var i = 0;

                ids.forEach((id) => {
                    var current = model.clone();

                    current.id = id;
                    models.push(current);

                    this.listenToOnce(current, 'sync', () => {
                        i++;

                        if (i === ids.length) {
                            proceed();
                        }
                    });

                    current.fetch();
                });
            });
        },

        /**
         * Get a collection for the current controller.
         *
         * @protected
         * @param {function(module:collection.Class): void|null} [callback]
         * @param {Object|null} [context]
         * @param {boolean} [usePreviouslyFetched=false] Use a previously fetched.
         * @return {Promise<module:collection.Class>}
         */
        getCollection: function (callback, context, usePreviouslyFetched) {
            context = context || this;

            if (!this.name) {
                throw new Error('No collection for unnamed controller');
            }

            let collectionName = this.entityType || this.name;

            if (usePreviouslyFetched) {
                if (collectionName in this.collectionMap) {
                    let collection = this.collectionMap[collectionName];

                    callback.call(context, collection);

                    return Promise.resolve(collection);
                }
            }

            return this.collectionFactory.create(collectionName, (collection) => {
                this.collectionMap[collectionName] = collection;

                this.listenTo(collection, 'sync', () => {
                    collection.isFetched = true;
                });

                if (callback) {
                    callback.call(context, collection);
                }
            });
        },

        /**
         * Get a model for the current controller.
         *
         * @protected
         * @param {Function} [callback]
         * @param {Object} [context]
         * @return {Promise<module:model.Class>}
         */
        getModel: function (callback, context) {
            context = context || this;

            if (!this.name) {
                throw new Error('No collection for unnamed controller');
            }

            let modelName = this.entityType || this.name;

            return this.modelFactory.create(modelName, (model) => {
                if (callback) {
                    callback.call(context, model);
                }
            });
        },
    });
});

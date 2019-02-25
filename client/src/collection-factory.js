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

 define('collection-factory', [], function () {

    var CollectionFactory = function (loader, modelFactory) {
        this.loader = loader;
        this.modelFactory = modelFactory;
    };

    _.extend(CollectionFactory.prototype, {

        loader: null,

        modelFactory: null,

        create: function (name, callback, context) {
            return new Promise(function (resolve) {
                context = context || this;
                this.modelFactory.getSeed(name, function (seed) {
                    var orderBy = this.modelFactory.metadata.get(['entityDefs', name, 'collection', 'orderBy']);
                    var order = this.modelFactory.metadata.get(['entityDefs', name, 'collection', 'order']);
                    var className = this.modelFactory.metadata.get(['clientDefs', name, 'collection']) || 'collection';
                    Espo.loader.require(className, function (collectionClass) {
                        var collection = new collectionClass(null, {
                            name: name,
                            orderBy: orderBy,
                            order: order
                        });
                        collection.model = seed;
                        collection._user = this.modelFactory.user;
                        collection.entityType = name;
                        if (callback) {
                            callback.call(context, collection);
                        }
                        resolve(collection);
                    }.bind(this));
                }.bind(this));
            }.bind(this));
        }
    });

    return CollectionFactory;

});

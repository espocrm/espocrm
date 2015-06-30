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

 Espo.define('CollectionFactory', [], function () {

    var CollectionFactory = function (loader, modelFactory) {
        this.loader = loader;
        this.modelFactory = modelFactory;
    };

    _.extend(CollectionFactory.prototype, {

        loader: null,

        modelFactory: null,

        create: function (name, callback, context) {
            context = context || this;

            this.modelFactory.getSeed(name, function (seed) {

                var asc = this.modelFactory.metadata.get('entityDefs.' + name + '.collection.asc');
                var sortBy = this.modelFactory.metadata.get('entityDefs.' + name + '.collection.sortBy');

                var className = this.modelFactory.metadata.get('clientDefs.' + name + '.collection') || 'Collection';

                Espo.loader.require(className, function (collectionClass) {
                    var collection = new collectionClass(null, {
                        name: name,
                        asc: asc,
                        sortBy: sortBy
                    });
                    collection.model = seed;
                    collection._user = this.modelFactory.user;
                    callback.call(context, collection);
                }.bind(this));
            }.bind(this));
        }
    });

    return CollectionFactory;

});


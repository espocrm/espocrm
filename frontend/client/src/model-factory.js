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

Espo.define('model-factory', [], function () {

    var ModelFactory = function (loader, metadata, user) {
        this.loader = loader;
        this.metadata = metadata;
        this.user = user;

        this.seeds = {};
    };

    _.extend(ModelFactory.prototype, {

        loader: null,

        metadata: null,

        seeds: null,

        dateTime: null,

        user: null,

        create: function (name, callback, context) {
            context = context || this;
            this.getSeed(name, function (seed) {
                var model = new seed();
                callback.call(context, model);
            }.bind(this));
        },

        getSeed: function (name, callback) {
            if ('name' in this.seeds) {
                callback(this.seeds[name]);
                return;
            }

            var className = this.metadata.get('clientDefs.' + name + '.model') || 'Model';

            Espo.loader.require(className, function (modelClass) {
                this.seeds[name] = modelClass.extend({
                    name: name,
                    defs: this.metadata.get('entityDefs.' + name, {}),
                    dateTime: this.dateTime,
                    _user: this.user
                });
                callback(this.seeds[name]);
            }.bind(this));
        },
    });

    return ModelFactory;

});

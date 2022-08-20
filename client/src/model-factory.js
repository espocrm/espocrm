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

define('model-factory', [], function () {

    /**
     * A model factory.
     *
     * @class
     * @name Class
     * @memberOf module:model-factory
     */
    let ModelFactory = function (metadata, user) {
        this.metadata = metadata;
        this.user = user;

        this.seeds = {};
    };

    _.extend(ModelFactory.prototype, /** @lends module:model-factory.Class# */ {

        /**
         * @private
         */
        metadata: null,

        /**
         * @private
         */
        seeds: null,

        /**
         * @public
         * @type {module:date-time.Class|null}
         * @internal
         */
        dateTime: null,

        /**
         * @private
         */
        user: null,

        /**
         * Create a model.
         *
         * @param {string} name An entity type.
         * @param {Function} [callback] Deprecated.
         * @param {Object} [context] Deprecated.
         * @returns {Promise<module:model.Class>}
         */
        create: function (name, callback, context) {
            return new Promise(resolve => {
                context = context || this;

                this.getSeed(name, seed => {
                    let model = new seed();

                    if (callback) {
                        callback.call(context, model);
                    }

                    resolve(model);
                });
            });
        },

        /**
         * @private
         */
        getSeed: function (name, callback) {
            if ('name' in this.seeds) {
                callback(this.seeds[name]);

                return;
            }

            let className = this.metadata.get('clientDefs.' + name + '.model') || 'model';

            require(className, modelClass => {
                this.seeds[name] = modelClass.extend({
                    name: name,
                    entityType: name,
                    defs: this.metadata.get('entityDefs.' + name) || {},
                    dateTime: this.dateTime,
                    _user: this.user,
                });

                callback(this.seeds[name]);
            });
        },
    });

    return ModelFactory;
});

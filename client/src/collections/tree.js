/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('collections/tree', ['collection'], function (Dep) {

    return Dep.extend({

        createSeed: function () {
            let seed = new this.constructor();

            seed.url = this.url;
            seed.model = this.model;
            seed._user = this._user;
            seed.name = this.name;

            return seed;
        },

        parse: function (response) {
            let list = Dep.prototype.parse.call(this, response);

            let seed = this.clone();

            seed.reset();

            this.path = response.path;

            this.categoryData = response.data || null;

            let f = (l, depth) => {
                l.forEach((d) => {
                    d.depth = depth;

                    let c = this.createSeed();

                    if (d.childList) {
                        if (d.childList.length) {
                            f(d.childList, depth + 1);
                            c.set(d.childList);
                            d.childCollection = c;

                            return;
                        }

                        d.childCollection = c;

                        return;
                    }

                    if (d.childList === null) {
                        d.childCollection = null;

                        return;
                    }

                    d.childCollection = c;

                });
            };

            f(list, 0);

            return list;
        },

        fetch: function (options) {
            options = options || {};
            options.data = options.data || {};

            if (this.parentId) {
                options.data.parentId = this.parentId;
            }

            options.data.maxDepth = this.maxDepth;

            return Dep.prototype.fetch.call(this, options);
        },
    });
});

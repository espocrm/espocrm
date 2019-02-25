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
Espo.define('collections/note', 'collection', function (Dep) {

    return Dep.extend({

        parse: function (response, params) {
            var total = this.total;
            var list = Dep.prototype.parse.call(this, response, params);

            if (params.data && params.data.after) {
                if (total >= 0 && response.total >= 0) {
                    this.total = total + response.total;
                } else {
                    this.total = total;
                }
            }
            return list;
        },

        fetchNew: function (options) {
            var options = options || {};
            options.data = options.data || {};

            if (this.length) {
                options.data.after = this.models[0].get('createdAt');
                options.remove = false;
                options.at = 0;
                options.maxSize = null;
            }

            this.fetch(options);
        },

    });

});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/admin/link-manager/fields/foreign-link-entity-type-list', 'views/fields/checklist', function (Dep) {

    return Dep.extend({

        setup: function () {
            this.params.translation = 'Global.scopeNames';
            Dep.prototype.setup.call(this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            this.controlOptionsAviability();
        },

        controlOptionsAviability: function () {
            this.params.options.forEach(function (item) {
                var link = this.model.get('link');
                var linkForeign = this.model.get('linkForeign');
                var entityType = this.model.get('entity');

                var linkDefs = this.getMetadata().get(['entityDefs', item, 'links']) || {};

                var isFound = false;
                for (var i in linkDefs) {
                    if (linkDefs[i].foreign == link && !linkDefs[i].isCustom && linkDefs[i].entity == entityType) {
                        isFound = true;
                    } else if (i === linkForeign && linkDefs[i].type !== 'hasChildren') {
                        isFound = true;
                    }
                }

                if (isFound) {
                    this.$el.find('input[data-name="checklistItem-foreignLinkEntityTypeList-'+item+'"]').attr('disabled', 'disabled');
                }
            }, this);
        },

    });
});

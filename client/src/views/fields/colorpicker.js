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

define('views/fields/colorpicker', ['views/fields/varchar', 'lib!Colorpicker'], function (Dep, Colorpicker) {

    return Dep.extend({

        type: 'varchar',

        detailTemplate: 'fields/colorpicker/detail',

        listTemplate: 'fields/colorpicker/detail',

        editTemplate: 'fields/colorpicker/edit',

        setup: function () {
            Dep.prototype.setup.call(this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.isEditMode()) {
                var isModal = !!this.$el.closest('.modal').length;

                this.$element.parent().colorpicker({
                    format: 'hex',
                    container: isModal ? this.$el : false,
                });

                if (isModal) {
                    this.$el.find('.colorpicker').css('position', 'relative').addClass('pull-right');
                }

                this.$element.on('change', () => {
                    if (this.$element.val() === '') {
                        this.$el.find('.input-group-addon > i').css('background-color', 'transparent');
                    }
                });
            }
        },
    });
});

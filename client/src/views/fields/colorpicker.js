/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

import VarcharFieldView from 'views/fields/varchar';

class ColorpickerFieldView extends VarcharFieldView {

    type = 'varchar'

    detailTemplate = 'fields/colorpicker/detail'
    listTemplate = 'fields/colorpicker/detail'
    editTemplate = 'fields/colorpicker/edit'

    setup() {
        super.setup();

        this.params.maxLength = 7;

        this.wait(Espo.loader.requirePromise('lib!bootstrap-colorpicker'));
    }

    afterRender() {
        super.afterRender();

        if (this.isEditMode()) {
            const isModal = !!this.$el.closest('.modal').length;

            // noinspection JSUnresolvedReference
            this.$element.parent().colorpicker({
                format: 'hex',
                container: isModal ? this.$el : false,
                sliders: {
                    saturation: {
                        maxLeft: 200,
                        maxTop: 200,
                    },
                    hue: {
                        maxTop: 200,
                    },
                    alpha: {
                        maxTop: 200,
                    },
                },
            });

            if (isModal) {
                this.$el.find('.colorpicker')
                    .css('position', 'relative')
                    .addClass('pull-right');
            }

            this.$element.on('change', () => {
                if (this.$element.val() === '') {
                    this.$el.find('.input-group-addon > i').css('background-color', 'transparent');
                }
            });
        }
    }
}

export default ColorpickerFieldView;

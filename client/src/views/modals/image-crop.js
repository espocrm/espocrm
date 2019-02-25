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

Espo.define('views/modals/image-crop', ['views/modal', 'lib!Cropper'], function (Dep, Cropper) {

    return Dep.extend({

        cssName: 'image-crop',

        template: 'modals/image-crop',

        events: {
            'click [data-action="zoomIn"]': function () {
                this.$img.cropper('zoom', 0.1);
            },
            'click [data-action="zoomOut"]': function () {
                this.$img.cropper('zoom', -0.1);
            }
        },

        setup: function () {
            this.buttonList = [
                {
                    name: 'crop',
                    label: 'Submit',
                    style: 'primary'
                },
                {
                    name: 'cancel',
                    label: 'Cancel'
                }
            ];

            this.on('remove', function () {
                if (this.$img.length) {
                    this.$img.cropper('destroy');
                    this.$img.parent().empty();
                }
            }, this);
        },

        afterRender: function () {
            var $img = this.$img = $('<img>').attr('src', this.options.contents).addClass('hidden');

            this.$el.find('.image-container').append($img);

            setTimeout(function () {
                $img.cropper({
                    aspectRatio: 1,
                    movable: true,
                    resizable: true,
                    rotatable: false,
                });
            }, 50);

        },

        actionCrop: function () {
            var dataUrl = this.$img.cropper('getDataURL', 'image/jpeg');
            this.trigger('crop', dataUrl);
            this.close();
        }

    });
});


/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('Views.Modals.ImageCrop', ['Views.Modal', 'lib!Cropper'], function (Dep, Cropper) {

    return Dep.extend({

        cssName: 'image-crop',

        template: 'modals.image-crop',

        events: {
            'click [data-action="zoomIn"]': function () {
                this.$img.cropper('zoom', 0.1);
            },
            'click [data-action="zoomOut"]': function () {
                this.$img.cropper('zoom', -0.1);
            }
        },
        

        data: function () {
            return {

            };
        },

        setup: function () {
            this.header = null;

            this.buttons = [
                {
                    name: 'crop',
                    label: 'Submit',
                    style: 'primary',
                    onClick: function (dialog) {
                        this.crop();
                    }.bind(this)
                },
                {
                    name: 'cancel',
                    label: 'Cancel',
                    onClick: function (dialog) {
                        this.close();
                    }.bind(this)
                }
            ];
        },

        afterRender: function () {
            var $img = this.$img = $('<img>').attr('src', this.options.contents).addClass('hidden');

            this.$el.find('.image-container').append($img);

            $img.cropper({
                aspectRatio: 1,
                movable: true,
                resizable: true,
                rotatable: false,
            });
        },

        crop: function () {
            var dataUrl = this.$img.cropper('getDataURL', 'image/jpeg');
            this.trigger('crop', dataUrl);
            this.close();
        }

    });
});


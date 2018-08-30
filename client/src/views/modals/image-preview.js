/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('views/modals/image-preview', 'views/modal', function (Dep) {

    return Dep.extend({

        cssName: 'image-preview',

        header: true,

        template: 'modals/image-preview',

        size: '',

        backdrop: true,

        data: function () {
            return {
                name: this.options.name,
                url: this.getImageUrl(),
                originalUrl: this.getOriginalImageUrl(),
                size: this.size
            };
        },

        setup: function () {
            this.buttonList = [];
            this.header = '&nbsp;';

            this.navigationEnabled = (this.options.imageList && this.options.imageList.length > 1);

            this.imageList = this.options.imageList || [];

            this.once('remove', function () {
                $(window).off('resize.image-review');
            }, this);
        },

        getImageUrl: function () {
            var url = this.getBasePath() + '?entryPoint=image&id=' + this.options.id;
            if (this.size) {
                url += '&size=' + this.size;
            }
            if (this.getUser().get('portalId')) {
                url += '&portalId=' + this.getUser().get('portalId');
            }
            return url;
        },

        getOriginalImageUrl: function () {
            var url = this.getBasePath() + '?entryPoint=image&id=' + this.options.id;
            if (this.getUser().get('portalId')) {
                url += '&portalId=' + this.getUser().get('portalId');
            }
            return url;
        },

        afterRender: function () {
            $container = this.$el.find('.image-container');
            $img = this.$el.find('.image-container img');

            if (this.navigationEnabled) {
                $img.css('cursor', 'pointer');
                $img.click(function () {
                    this.switchToNext();
                }.bind(this));
            }

            var manageSize = function () {
                var width = $container.width();
                $img.css('maxWidth', width);
            }.bind(this);

            $(window).off('resize.image-review');
            $(window).on('resize.image-review', function () {
                manageSize();
            });

            setTimeout(function () {
                manageSize();
            }, 100);
        },

        switchToNext: function () {
            var index = -1;
            this.imageList.forEach(function (d, i) {
                if (d.id === this.options.id) {
                    index = i;
                }
            }, this);

            index++;
            if (index > this.imageList.length - 1) {
                index = 0;
            }

            this.options.id = this.imageList[index].id
            this.options.name = this.imageList[index].name;
            this.reRender();
        },

    });
});


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

define('views/fields/barcode',
    ['views/fields/varchar', 'lib!JsBarcode', 'lib!qrcode'],
    function (Dep, JsBarcode, QRCode) {

    return Dep.extend({

        type: 'barcode',

        listTemplate: 'fields/barcode/detail',

        detailTemplate: 'fields/barcode/detail',

        setup: function () {
            this.params.trim = true;

            var maxLength = 255;

            switch (this.params.codeType) {
                case 'EAN2':
                    maxLength = 2; break;
                case 'EAN5':
                    maxLength = 5; break;
                case 'EAN8':
                    maxLength = 8; break;
                case 'EAN13':
                    maxLength = 13; break;
                case 'UPC':
                    maxLength = 12; break;
                case 'UPCE':
                    maxLength = 11; break;
                case 'ITF14':
                    maxLength = 14; break;
                case 'pharmacode':
                    maxLength = 6; break;
            }

            this.params.maxLength = maxLength;

            if (this.params.codeType !== 'QRcode') {
                this.isSvg = true;
            }

            Dep.prototype.setup.call(this);

            $(window).on('resize.' + this.cid, function () {
                if (!this.isRendered()) {
                    return;
                }

                this.controlWidth();
            }.bind(this));
        },

        onRemove: function () {
            $(window).off('resize.' + this.cid);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.mode === 'list' || this.mode === 'detail') {
                var value = this.model.get(this.name);

                if (value) {
                    if (this.params.codeType === 'QRcode') {
                        var size = 128;

                        if (this.isListMode()) {
                            size = 64;
                        }

                        var containerWidth = this.$el.width() ;

                        if (containerWidth < size && containerWidth) {
                            size = containerWidth;
                        }

                        new QRCode(this.$el.find('.barcode').get(0), {
                            text: value,
                            width: size,
                            height: size,
                            colorDark : '#000000',
                            colorLight : '#ffffff',
                            correctLevel : QRCode.CorrectLevel.H,
                        });
                    }
                    else {
                        var $barcode = $(this.getSelector() + ' .barcode');

                        if ($barcode.length) {
                            this.initBarcode(value);
                        }
                        else {
                            // SVG may be not available yet (in webkit).
                            setTimeout(
                                function () {
                                    this.initBarcode(value);

                                    this.controlWidth();
                                }
                                .bind(this),
                                100
                            );
                        }

                    }
                }

                this.controlWidth();
            }
        },

        initBarcode: function (value) {
            JsBarcode(this.getSelector() + ' .barcode', value, {
                format: this.params.codeType,
                height: 50,
                fontSize: 14,
                margin: 0,
                lastChar: this.params.lastChar,
            });
        },

        controlWidth: function () {
            this.$el.find('.barcode').css('max-width', this.$el.width() + 'px');
        },

    });
});

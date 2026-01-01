define("views/modals/image-crop", ["exports", "views/modal"], function (_exports, _modal) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _modal = _interopRequireDefault(_modal);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class ImageCropModalView extends _modal.default {
    template = 'modals/image-crop';
    cssName = 'image-crop';
    events = {
      /** @this ImageCropModalView */
      'click [data-action="zoomIn"]': function () {
        this.$img.cropper('zoom', 0.1);
      },
      /** @this ImageCropModalView */
      'click [data-action="zoomOut"]': function () {
        this.$img.cropper('zoom', -0.1);
      }
    };
    setup() {
      this.buttonList = [{
        name: 'crop',
        label: 'Submit',
        style: 'primary'
      }, {
        name: 'cancel',
        label: 'Cancel'
      }];
      this.wait(Espo.loader.requirePromise('lib!cropper'));
      this.on('remove', () => {
        if (this.$img.length) {
          this.$img.cropper('destroy');
          this.$img.parent().empty();
        }
      });
    }
    afterRender() {
      // noinspection RequiredAttributes,HtmlRequiredAltAttribute
      let $img = this.$img = $(`<img>`).attr('src', this.options.contents).addClass('hidden');
      this.$el.find('.image-container').append($img);
      setTimeout(() => {
        $img.cropper({
          aspectRatio: 1,
          movable: true,
          resizable: true,
          rotatable: false
        });
      }, 50);
    }

    // noinspection JSUnusedGlobalSymbols
    actionCrop() {
      let dataUrl = this.$img.cropper('getDataURL', 'image/jpeg');
      this.trigger('crop', dataUrl);
      this.close();
    }
  }
  var _default = _exports.default = ImageCropModalView;
});
//# sourceMappingURL=image-crop.js.map ;
define("di", ["exports"], function (_exports) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.container = void 0;
  _exports.inject = inject;
  _exports.register = register;
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

  const registry = new Map();
  const container = _exports.container = new Map();

  /**
   * A DI container.
   */

  /**
   * A 'register' decorator.
   *
   * @param {*[]} argumentList Arguments.
   * @return {function(typeof Object)}
   */
  function register() {
    let argumentList = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
    return function (classObject) {
      registry.set(classObject, argumentList);
    };
  }

  /**
   * An 'inject' decorator.
   *
   * @param {typeof Object} classObject A class.
   * @return {(function(*, Object): void)}
   */
  function inject(classObject) {
    /**
     * @param {{addInitializer: function(function())}} context
     */
    return function (value, context) {
      context.addInitializer(function () {
        let instance = container.get(classObject);
        if (!instance) {
          instance = Reflect.construct(classObject, registry.get(classObject));
          container.set(classObject, instance);
        }
        this[context.name] = instance;
      });
    };
  }
});
//# sourceMappingURL=di.js.map ;
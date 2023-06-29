/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/** @module view-helper */

import {marked} from 'marked';
import DOMPurify from 'dompurify';
import Handlebars from 'handlebars';

/**
 * A view helper.
 */
class ViewHelper {

    constructor() {
        this._registerHandlebarsHelpers();

        /** @private */
        this.mdBeforeList = [
            {
                regex: /&#x60;&#x60;&#x60;\n?([\s\S]*?)&#x60;&#x60;&#x60;/g,
                value: function (s, string) {
                    return '<pre><code>' +string.replace(/\*/g, '&#42;').replace(/~/g, '&#126;') +
                        '</code></pre>';
                }
            },
            {
                regex: /&#x60;([\s\S]*?)&#x60;/g,
                value: function (s, string) {
                    return '<code>' + string.replace(/\*/g, '&#42;').replace(/~/g, '&#126;') + '</code>';
                }
            }
        ];

        marked.setOptions({
            breaks: true,
            tables: false,
        });

        DOMPurify.addHook('beforeSanitizeAttributes', function (node) {
            if (node instanceof HTMLAnchorElement) {
                if (node.getAttribute('target')) {
                    node.targetBlank = true;
                }
                else {
                    node.targetBlank = false;
                }
            }
        });

        DOMPurify.addHook('afterSanitizeAttributes', function (node) {
            if (node instanceof HTMLAnchorElement) {
                if (node.targetBlank) {
                    node.setAttribute('target', '_blank');
                    node.setAttribute('rel', 'noopener noreferrer');
                }
                else {
                    node.removeAttribute('rel');
                }
            }
        });
    }

    /**
     * A layout manager.
     *
     * @type {module:layout-manager}
     */
    layoutManager = null

    /**
     * A config.
     *
     * @type {module:models/settings}
     */
    settings = null

    /**
     * A config.
     *
     * @type {module:models/settings}
     */
    config = null

    /**
     * A current user.
     *
     * @type {module:models/user}
     */
    user = null

    /**
     * A preferences.
     *
     * @type {module:models/preferences}
     */
    preferences = null

    /**
     * An ACL manager.
     *
     * @type {module:acl-manager}
     */
    acl = null

    /**
     * A model factory.
     *
     * @type {module:model-factory}
     */
    modelFactory = null

    /**
     * A collection factory.
     *
     * @type {module:collection-factory}
     */
    collectionFactory = null

    /**
     * A router.
     *
     * @type {module:router}
     */
    router = null

    /**
     * A storage.
     *
     * @type {module:storage}
     */
    storage = null

    /**
     * A session storage.
     *
     * @type {module:session-storage}
     */
    sessionStorage = null

    /**
     * A date-time util.
     *
     * @type {module:date-time}
     */
    dateTime = null

    /**
     * A language.
     *
     * @type {module:language}
     */
    language = null

    /**
     * A metadata.
     *
     * @type {module:metadata}
     */
    metadata = null

    /**
     * A field-manager util.
     *
     * @type {module:field-manager}
     */
    fieldManager = null

    /**
     * A cache.
     *
     * @type {module:cache}
     */
    cache = null

    /**
     * A theme manager.
     *
     * @type {module:theme-manager}
     */
    themeManager = null

    /**
     * A web-socket manager. Null if not enabled.
     *
     * @type {?module:web-socket-manager}
     */
    webSocketManager = null

    /**
     * A number util.
     *
     * @type {module:num-util}
     */
    numberUtil = null

    /**
     * A page-title util.
     *
     * @type {module:page-title}
     */
    pageTitle = null

    /**
     * A broadcast channel.
     *
     * @type {?module:broadcast-channel}
     */
    broadcastChannel = null

    /**
     * A base path.
     *
     * @type {string}
     */
    basePath = ''

    /**
     * Application parameters.
     *
     * @type {Object}
     */
    appParams = null

    /**
     * @private
     */
    _registerHandlebarsHelpers() {
        Handlebars.registerHelper('img', img => {
            return new Handlebars.SafeString(`<img src="img/${img}" alt="img">`);
        });

        Handlebars.registerHelper('prop', (object, name) => {
            if (name in object) {
                return object[name];
            }
        });

        Handlebars.registerHelper('var', (name, context, options) => {
            if (typeof context === 'undefined') {
                return null;
            }

            let contents = context[name];

            if (options.hash.trim) {
                contents = contents.trim();
            }

            return new Handlebars.SafeString(contents);
        });

        Handlebars.registerHelper('concat', function (left, right) {
            return left + right;
        });

        Handlebars.registerHelper('ifEqual', function (left, right, options) {
            // noinspection EqualityComparisonWithCoercionJS
            if (left == right) {
                return options.fn(this);
            }

            return options.inverse(this);
        });

        Handlebars.registerHelper('ifNotEqual', function (left, right, options) {
            // noinspection EqualityComparisonWithCoercionJS
            if (left != right) {
                return options.fn(this);
            }

            return options.inverse(this);
        });

        Handlebars.registerHelper('ifPropEquals', function (object, property, value, options) {
            // noinspection EqualityComparisonWithCoercionJS
            if (object[property] == value) {
                return options.fn(this);
            }

            return options.inverse(this);
        });

        Handlebars.registerHelper('ifAttrEquals', function (model, attr, value, options) {
            // noinspection EqualityComparisonWithCoercionJS
            if (model.get(attr) == value) {
                return options.fn(this);
            }

            return options.inverse(this);
        });

        Handlebars.registerHelper('ifAttrNotEmpty', function (model, attr, options) {
            let value = model.get(attr);

            if (value !== null && typeof value !== 'undefined') {
                return options.fn(this);
            }

            return options.inverse(this);
        });

        Handlebars.registerHelper('ifNotEmptyHtml', function (value, options) {
            value = value.replace(/\s/g, '');

            if (value) {
                return options.fn(this);
            }

            return options.inverse(this);
        });

        Handlebars.registerHelper('get', (model, name) => model.get(name));

        Handlebars.registerHelper('length', arr => arr.length);

        Handlebars.registerHelper('translate', (name, options) => {
            let scope = options.hash.scope || null;
            let category = options.hash.category || null;

            if (name === 'null') {
                return '';
            }

            return this.language.translate(name, category, scope);
        });

        Handlebars.registerHelper('dropdownItem', (name, options) => {
            let scope = options.hash.scope || null;
            let label = options.hash.label;
            let labelTranslation = options.hash.labelTranslation;
            let data = options.hash.data;
            let hidden = options.hash.hidden;
            let disabled = options.hash.disabled;
            let title = options.hash.title;
            let link = options.hash.link;
            let action = options.hash.action || name;
            let iconHtml = options.hash.iconHtml;

            let html =
                options.hash.html ||
                options.hash.text ||
                (
                    labelTranslation ?
                        this.language.translatePath(labelTranslation) :
                        this.language.translate(label, 'labels', scope)
                );

            if (!options.hash.html) {
                html = this.escapeString(html);
            }

            if (iconHtml) {
                html = iconHtml + ' ' + html;
            }

            let $li = $('<li>')
                .addClass(hidden ? 'hidden' : '')
                .addClass(disabled ? 'disabled' : '');

            let $a = $('<a>')
                .attr('role', 'button')
                .attr('tabindex', '0')
                .addClass('action')
                .html(html);

            if (action) {
                $a.attr('data-action', action);
            }

            $li.append($a);

            link ?
                $a.attr('href', link) :
                $a.attr('role', 'button');

            if (data) {
                for (let key in data) {
                    $a.attr('data-' + Espo.Utils.camelCaseToHyphen(key), data[key]);
                }
            }

            if (disabled) {
                $li.attr('disabled', 'disabled');
            }

            if (title) {
                $a.attr('title', title);
            }

            return new Handlebars.SafeString($li.get(0).outerHTML);
        });

        Handlebars.registerHelper('button', (name, options) => {
            let style = options.hash.style || 'default';
            let scope = options.hash.scope || null;
            let label = options.hash.label || name;
            let labelTranslation = options.hash.labelTranslation;
            let link = options.hash.link;
            let iconHtml = options.hash.iconHtml;

            let html =
                options.hash.html ||
                options.hash.text ||
                (
                    labelTranslation ?
                        this.language.translatePath(labelTranslation) :
                        this.language.translate(label, 'labels', scope)
                );

            if (!options.hash.html) {
                html = this.escapeString(html);
            }

            if (iconHtml) {
                html = iconHtml + ' ' + html;
            }

            let tag = link ? '<a>' : '<button>';

            let $button = $(tag)
                .addClass('btn action')
                .addClass(options.hash.className || '')
                .addClass(options.hash.hidden ? 'hidden' : '')
                .addClass(options.hash.disabled ? 'disabled' : '')
                .attr('data-action', name)
                .addClass('btn-' + style)
                .html(html);

            link ?
                $button.href(link) :
                $button.attr('type', 'button')

            if (options.hash.disabled) {
                $button.attr('disabled', 'disabled');
            }

            if (options.hash.title) {
                $button.attr('title', options.hash.title);
            }

            return new Handlebars.SafeString($button.get(0).outerHTML);
        });

        Handlebars.registerHelper('hyphen', (string) => {
            return Espo.Utils.convert(string, 'c-h');
        });

        Handlebars.registerHelper('toDom', (string) => {
            return Espo.Utils.toDom(string);
        });

        // noinspection SpellCheckingInspection
        Handlebars.registerHelper('breaklines', (text) => {
            text = Handlebars.Utils.escapeExpression(text || '');
            text = text.replace(/(\r\n|\n|\r)/gm, '<br>');

            return new Handlebars.SafeString(text);
        });

        Handlebars.registerHelper('complexText', (text, options) => {
            return this.transformMarkdownText(text, options.hash);
        });

        Handlebars.registerHelper('translateOption', (name, options) => {
            let scope = options.hash.scope || null;
            let field = options.hash.field || null;

            if (!field) {
                return '';
            }

            let translationHash = options.hash.translatedOptions || null;

            if (translationHash === null) {
                translationHash = this.language.translate(/** @type {string} */field, 'options', scope) || {};

                if (typeof translationHash !== 'object') {
                    translationHash = {};
                }
            }

            if (name === null) {
                name = '';
            }

            return translationHash[name] || name;
        });

        Handlebars.registerHelper('options', (list, value, options) => {
            if (typeof value === 'undefined') {
                value = false;
            }

            list = list || [];

            let html = '';

            let multiple = (Object.prototype.toString.call(value) === '[object Array]');

            let checkOption = name => {
                if (multiple) {
                    return value.indexOf(name) !== -1;
                }

                return value === name || !value && !name;
            };

            options.hash = /** @type {Object.<string, *>} */ options.hash || {};

            let scope = options.hash.scope || false;
            let category = options.hash.category || false;
            let field = options.hash.field || false;
            let styleMap = options.hash.styleMap || {};

            if (!multiple && options.hash.includeMissingOption && (value || value === '')) {
                if (!~list.indexOf(value)) {
                    list = Espo.Utils.clone(list);

                    list.push(value);
                }
            }

            let translationHash = options.hash.translationHash ||
                options.hash.translatedOptions ||
                null;

            if (translationHash === null) {
                if (!category && field) {
                    translationHash = this.language
                        .translate(/** @type {string}*/field, 'options', /** @type {string}*/scope) || {};

                    if (typeof translationHash !== 'object') {
                        translationHash = {};
                    }
                }
                else {
                    translationHash = {};
                }
            }

            let translate = name => {
                if (!category) {
                    return translationHash[name] || name;
                }

                return this.language.translate(name, category, /** @type {string} */scope);
            };

            for (let key in list) {
                let value = list[key];
                let label = translate(value);

                let $option =
                    $('<option>')
                        .attr('value', value)
                        .addClass(styleMap[value] ? 'text-' + styleMap[value]: '')
                        .text(label);

                if (checkOption(list[key])) {
                    $option.attr('selected', 'selected')
                }

                html += $option.get(0).outerHTML;
            }

            return new Handlebars.SafeString(html);
        });

        Handlebars.registerHelper('basePath', () => {
            return this.basePath || '';
        });
    }

    /**
     * Get an application parameter.
     *
     * @param {string} name
     * @returns {*}
     */
    getAppParam(name) {
        return (this.appParams || {})[name];
    }

    /**
     * Escape a string.
     *
     * @param {string} text A string.
     * @returns {string}
     */
    escapeString(text) {
        return Handlebars.Utils.escapeExpression(text);
    }

    /**
     * Get a user avatar HTML.
     *
     * @param {string} id A user ID.
     * @param {'small'|'medium'|'large'} [size='small'] A size.
     * @param {int} [width=16]
     * @param {string} [additionalClassName]  An additional class-name.
     * @returns {string}
     */
    getAvatarHtml(id, size, width, additionalClassName) {
        if (this.config.get('avatarsDisabled')) {
            return '';
        }

        let t = this.cache ? this.cache.get('app', 'timestamp') : Date.now();

        let basePath = this.basePath || '';
        size = size || 'small';
        width = width || 16;

        let className = 'avatar';

        if (additionalClassName) {
            className += ' ' + additionalClassName;
        }

        // noinspection RequiredAttributes,HtmlRequiredAltAttribute
        return $(`<img>`)
            .attr('src', `${basePath}?entryPoint=avatar&size=${size}&id=${id}&t=${t}`)
            .attr('alt', 'avatar')
            .addClass(className)
            .attr('width', width.toString())
            .get(0).outerHTML;
    }

    /**
     * A Markdown text to HTML (one-line).
     *
     * @param {string} text A text.
     * @returns {Handlebars.SafeString} HTML.
     */
    transformMarkdownInlineText(text) {
        return this.transformMarkdownText(text, {inline: true});
    }

    /**
     * A Markdown text to HTML.
     *
     * @param {string} text A text.
     * @param {{inline?: boolean, linksInNewTab?: boolean}} [options] Options.
     * @returns {Handlebars.SafeString} HTML.
     */
    transformMarkdownText(text, options) {
        text = text || '';

        text = Handlebars.Utils.escapeExpression(text).replace(/&gt;+/g, '>');

        this.mdBeforeList.forEach(item => {
            text = text.replace(item.regex, item.value);
        });

        options = options || {};

        if (options.inline) {
            text = marked.parseInline(text);
        }
        else {
            text = marked.parse(text);
        }

        text = DOMPurify.sanitize(text, {}).toString();

        if (options.linksInNewTab) {
            text = text.replace(/<a href=/gm, '<a target="_blank" rel="noopener noreferrer" href=');
        }

        text = text.replace(
            /<a href="mailto:(.*)"/gm,
            '<a role="button" class="selectable" data-email-address="$1" data-action="mailTo"'
        );

        return new Handlebars.SafeString(text);
    }

    /**
     * Get a color-icon HTML for a scope.
     *
     * @param {string} scope A scope.
     * @param {boolean} [noWhiteSpace=false] No white space.
     * @param {string} [additionalClassName] An additional class-name.
     * @returns {string}
     */
    getScopeColorIconHtml(scope, noWhiteSpace, additionalClassName) {
        if (this.config.get('scopeColorsDisabled') || this.preferences.get('scopeColorsDisabled')) {
            return '';
        }

        let color = this.metadata.get(['clientDefs', scope, 'color']);

        let html = '';

        if (color) {
            let $span = $('<span class="color-icon fas fa-square">');

            $span.css('color', color);

            if (additionalClassName) {
                $span.addClass(additionalClassName);
            }

            html = $span.get(0).outerHTML;
        }

        if (!noWhiteSpace) {
            if (html) {
                html += `<span style="user-select: none;">&nbsp;</span>`;
            }
        }

        return html;
    }

    /**
     * Sanitize HTML.
     *
     * @param {string} text HTML.
     * @param {Object} [options] Options.
     * @returns {string}
     */
    sanitizeHtml(text, options) {
        return DOMPurify.sanitize(text, options);
    }

    /**
     * Moderately sanitize HTML.
     *
     * @param {string} value HTML.
     * @returns {string}
     */
    moderateSanitizeHtml(value) {
        value = value || '';
        value = value.replace(/<\/?(base)[^><]*>/gi, '');
        value = value.replace(/<\/?(object)[^><]*>/gi, '');
        value = value.replace(/<\/?(embed)[^><]*>/gi, '');
        value = value.replace(/<\/?(applet)[^><]*>/gi, '');
        value = value.replace(/<\/?(iframe)[^><]*>/gi, '');
        value = value.replace(/<\/?(script)[^><]*>/gi, '');
        value = value.replace(/<[^><]*([^a-z]on[a-z]+)=[^><]*>/gi, function (match) {
            return match.replace(/[^a-z]on[a-z]+=/gi, ' data-handler-stripped=');
        });

        value = this.stripEventHandlersInHtml(value);

        value = value.replace(/href=" *javascript:(.*?)"/gi, () => {
            return 'removed=""';
        });

        value = value.replace(/href=' *javascript:(.*?)'/gi, () => {
            return 'removed=""';
        });

        value = value.replace(/src=" *javascript:(.*?)"/gi, () => {
            return 'removed=""';
        });

        value = value.replace(/src=' *javascript:(.*?)'/gi, () => {
            return 'removed=""';
        });

        return value;
    }

    /**
     * Strip event handlers in HTML.
     *
     * @param {string} html HTML.
     * @returns {string}
     */
    stripEventHandlersInHtml(html) {
        let j; // @todo Revise.

        function stripHTML() {
            html = html.slice(0, strip) + html.slice(j);
            j = strip;

            strip = false;
        }

        function isValidTagChar(str) {
            return str.match(/[a-z?\\\/!]/i);
        }

        let strip = false;
        let lastQuote = false;

        for (let i = 0; i < html.length; i++){
            if (html[i] === '<' && html[i + 1] && isValidTagChar(html[i + 1])) {
                i++;

                for (let j = i; j < html.length; j++){
                    if (!lastQuote && html[j] === '>'){
                        if (strip) {
                            stripHTML();
                        }

                        i = j;

                        break;
                    }

                    // noinspection JSIncompatibleTypesComparison
                    if (lastQuote === html[j]){
                        lastQuote = false;

                        continue;
                    }

                    if (!lastQuote && html[j - 1] === "=" && (html[j] === "'" || html[j] === '"')) {
                        lastQuote = html[j];
                    }

                    if (!lastQuote && html[j - 2] === " " && html[j - 1] === "o" && html[j] === "n") {
                        strip = j - 2;
                    }

                    if (strip && html[j] === " " && !lastQuote){
                        stripHTML();
                    }
                }
            }
        }

        return html;
    }

    /**
     * Calculate a content container height.
     *
     * @param {JQuery} $el Element.
     * @returns {number}
     */
    calculateContentContainerHeight($el) {
        let smallScreenWidth = this.themeManager.getParam('screenWidthXs');

        let $window = $(window);

        let footerHeight = $('#footer').height() || 26;
        let top = 0;
        let element = $el.get(0);

        if (element) {
            top = element.getBoundingClientRect().top;

            if ($window.width() < smallScreenWidth) {
                let $navbarCollapse = $('#navbar .navbar-body');

                if ($navbarCollapse.hasClass('in') || $navbarCollapse.hasClass('collapsing')) {
                    top -= $navbarCollapse.height();
                }
            }
        }

        let spaceHeight = top + footerHeight;

        return $window.height() - spaceHeight - 20;
    }

    /**
     * Process view-setup-handlers.
     *
     * @param {module:view} view A view.
     * @param {string} type A view-setup-handler type.
     * @param {string} [scope] A scope.
     * @return Promise
     */
    processSetupHandlers(view, type, scope) {
        // noinspection JSUnresolvedReference
        scope = scope || view.scope || view.entityType;

        let handlerIdList = this.metadata.get(['clientDefs', 'Global', 'viewSetupHandlers', type]) || [];

        if (scope) {
            handlerIdList = handlerIdList
                .concat(
                    this.metadata.get(['clientDefs', scope, 'viewSetupHandlers', type]) || []
                );
        }

        if (handlerIdList.length === 0) {
            return Promise.resolve();
        }

        /**
         * @interface
         * @name ViewHelper~Handler
         */

        /**
         * @function
         * @name ViewHelper~Handler#process
         * @param {module:view} [view] Deprecated.
         */

        let promiseList = [];

        for (let id of handlerIdList) {
            let promise = new Promise(resolve => {
                Espo.loader.require(id, /** typeof ViewHelper~Handler */Handler => {
                    let result = (new Handler(view)).process(view);

                    if (result && Object.prototype.toString.call(result) === '[object Promise]') {
                        result.then(() => resolve());

                        return;
                    }

                    resolve();
                });
            });

            promiseList.push(promise);
        }

        return Promise.all(promiseList);
    }

    /**
     * @deprecated Use `transformMarkdownText`.
     * @todo Remove in v8.0.
     * @internal Used in extensions.
     */
    transfromMarkdownText(text, options) {
        return this.transformMarkdownText(text, options);
    }

    /**
     * @deprecated Use `transformMarkdownInlineText`.
     * @todo Remove in v8.0.
     * @internal Used in extensions.
     */
    transfromMarkdownInlineText(text) {
        return this.transformMarkdownInlineText(text);
    }
}

export default ViewHelper;

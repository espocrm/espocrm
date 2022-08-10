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

define('view-helper', ['lib!marked', 'lib!dompurify', 'lib!handlebars'],
function (marked, DOMPurify, /** typeof Handlebars */Handlebars) {

    /**
     * A view helper.
     *
     * @class
     * @name Class
     * @memberOf module:view-helper
     */
    let ViewHelper = function () {
        this._registerHandlebarsHelpers();

        /**
         * @private
         */
        this.mdBeforeList = [
            {
                regex: /\&\#x60;\&\#x60;\&\#x60;\n?([\s\S]*?)\&\#x60;\&\#x60;\&\#x60;/g,
                value: function (s, string) {
                    return '<pre><code>' +string.replace(/\*/g, '&#42;').replace(/\~/g, '&#126;') +
                        '</code></pre>';
                }
            },
            {
                regex: /\&\#x60;([\s\S]*?)\&\#x60;/g,
                value: function (s, string) {
                    return '<code>' + string.replace(/\*/g, '&#42;').replace(/\~/g, '&#126;') + '</code>';
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
    };

    _.extend(ViewHelper.prototype, /** @lends module:view-helper.Class# */{

        /**
         * A layout manager.
         *
         * @type {module:layout-manager.Class}
         */
        layoutManager: null,

        /**
         * A config.
         *
         * @type {module:models/settings.Class}
         */
        settings: null,

        /**
         * A config.
         *
         * @type {module:models/settings.Class}
         */
        config: null,

        /**
         * A current user.
         *
         * @type {module:models/user.Class}
         */
        user: null,

        /**
         * A preferences.
         *
         * @type {module:models/preferences.Class}
         */
        preferences: null,

        /**
         * An ACL manager.
         *
         * @type {module:acl-manager.Class}
         */
        acl: null,

        /**
         * A model factory.
         *
         * @type {module:model-factory.Class}
         */
        modelFactory: null,

        /**
         * A collection factory.
         *
         * @type {module:collection-factory.Class}
         */
        collectionFactory: null,

        /**
         * A storage.
         *
         * @type {module:storage.Class}
         */
        storage: null,

        /**
         * A session storage.
         *
         * @type {module:session-storage.Class}
         */
        sessionStorage: null,

        /**
         * A date-time util.
         *
         * @type {module:date-time.Class}
         */
        dateTime: null,

        /**
         * A language.
         *
         * @type {module:language.Class}
         */
        language: null,

        /**
         * A metadata.
         *
         * @type {module:metadata.Class}
         */
        metadata: null,

        /**
         * A field-manager util.
         *
         * @type {module:field-manager.Class}
         */
        fieldManager: null,

        /**
         * A cache.
         *
         * @type {module:cache.Class}
         */
        cache: null,

        /**
         * A theme manager.
         *
         * @type {module:theme-manager.Class}
         */
        themeManager: null,

        /**
         * A web-socket manager
         *
         * @type {module:web-socket-manager.Class}
         */
        webSocketManager: null,

        /**
         * A number util.
         *
         * @type {module:number.Class}
         */
        numberUtil: null,

        /**
         * A page-title util.
         *
         * @type {module:page-title.Class}
         */
        pageTitle: null,

        /**
         * A base path.
         *
         * @type {string}
         */
        basePath: '',

        /**
         * Application parameters.
         *
         * @type {Object}
         */
        appParams: null,

        /**
         * @private
         */
        _registerHandlebarsHelpers: function () {
            Handlebars.registerHelper('img', img => {
                return new Handlebars.SafeString("<img src=\"img/" + img + "\"></img>");
            });

            Handlebars.registerHelper('prop', (object, name) => {
                if (name in object) {
                    return object[name];
                }
            });

            Handlebars.registerHelper('var', (name, context) => {
                if (typeof context === 'undefined') {
                    return null;
                }

                return new Handlebars.SafeString(context[name]);
            });

            Handlebars.registerHelper('concat', function (left, right) {
                return left + right;
            });

            Handlebars.registerHelper('ifEqual', function (left, right, options) {
                if (left == right) {
                    return options.fn(this);
                }

                return options.inverse(this);
            });

            Handlebars.registerHelper('ifNotEqual', function (left, right, options) {
                if (left != right) {
                    return options.fn(this);
                }

                return options.inverse(this);
            });

            Handlebars.registerHelper('ifPropEquals', function (object, property, value, options) {
                if (object[property] == value) {
                    return options.fn(this);
                }

                return options.inverse(this);
            });

            Handlebars.registerHelper('ifAttrEquals', function (model, attr, value, options) {
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

            Handlebars.registerHelper('button', (name, options) => {
                let style = options.hash.style || 'default';
                let scope = options.hash.scope || null;
                let label = options.hash.label || name;

                let html =
                    options.hash.html ||
                    options.hash.text ||
                    this.language.translate(label, 'labels', scope);

                if (!options.hash.html) {
                    html = this.escapeString(html);
                }

                let $button = $('<button>')
                    .attr('type', 'button')
                    .addClass('btn action')
                    .addClass(options.hash.className || '')
                    .addClass(options.hash.hidden ? 'hidden' : '')
                    .addClass(options.hash.disabled ? 'disabled' : '')
                    .attr('data-action', name)
                    .addClass('btn-' + style)
                    .html(html);

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

                var translationHash = options.hash.translatedOptions || null;

                if (translationHash === null) {
                    translationHash = this.language.translate(field, 'options', scope) || {};

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

                    return value === name;
                };

                options.hash = options.hash || {};

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
                        translationHash = this.language.translate(field, 'options', scope) || {};

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

                    return this.language.translate(name, category, scope);
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
        },

        /**
         * Get an application parameter.
         *
         * @param {string} name
         * @returns {*}
         */
        getAppParam: function (name) {
            return (this.appParams || {})[name];
        },

        /**
         * Strip tags.
         *
         * @deprecated
         * @param {string} text
         * @returns {string}
         */
        stripTags: function (text) {
            text = text || '';

            if (typeof text === 'string' || text instanceof String) {
                return text.replace(/<\/?[^>]+(>|$)/g, '');
            }

            return text;
        },

        /**
         * Escape a string.
         *
         * @param {string} text A string.
         * @returns {string}
         */
        escapeString: function (text) {
            return Handlebars.Utils.escapeExpression(text);
        },

        /**
         * Get a user avatar HTML.
         *
         * @param {string} id A user ID.
         * @param {'small'|'medium'|'large'} [size='small'] A size.
         * @param {int} [width=16]
         * @param {string} [additionalClassName]  An additional class-name.
         * @returns {string}
         */
        getAvatarHtml: function (id, size, width, additionalClassName) {
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

            return $('<img>')
                .attr('src', `${basePath}?entryPoint=avatar&size=${size}&id=${id}&t=${t}`)
                .addClass(className)
                .attr('width', width.toString())
                .get(0).outerHTML;
        },

        /**
         * A Markdown text to HTML (one-line).
         *
         * @param {string} text A text.
         * @returns {string} HTML.
         */
        transformMarkdownInlineText: function (text) {
            return this.transformMarkdownText(text, {inline: true});
        },

        /**
         * A Markdown text to HTML.
         *
         * @param {string} text A text.
         * @param {{inline?: boolean, linksInNewTab?: boolean}} [options] Options.
         * @returns {string} HTML.
         */
        transformMarkdownText: function (text, options) {
            text = text || '';

            text = Handlebars.Utils.escapeExpression(text).replace(/&gt;+/g, '>');

            this.mdBeforeList.forEach(function (item) {
                text = text.replace(item.regex, item.value);
            });

            options = options || {};

            if (options.inline) {
                text = marked.parseInline(text);
            }
            else {
                text = marked.parse(text);
            }

            text = DOMPurify.sanitize(text).toString();

            if (options.linksInNewTab) {
                text = text.replace(/<a href=/gm, '<a target="_blank" rel="noopener noreferrer" href=');
            }

            text = text.replace(
                /<a href="mailto:(.*)"/gm,
                '<a type="button" class="selectable" data-email-address="$1" data-action="mailTo"'
            );

            return new Handlebars.SafeString(text);
        },

        /**
         * Get a color-icon HTML for a scope.
         *
         * @param {string} scope A scope.
         * @param {boolean} [noWhiteSpace=false] No white space.
         * @param {string} [additionalClassName] An additional class-name.
         * @returns {string}
         */
        getScopeColorIconHtml: function (scope, noWhiteSpace, additionalClassName) {
            if (this.config.get('scopeColorsDisabled') || this.preferences.get('scopeColorsDisabled')) {
                return '';
            }

            var color = this.metadata.get(['clientDefs', scope, 'color']);

            var html = '';

            if (color) {
                var $span = $('<span class="color-icon fas fa-square-full">');

                $span.css('color', color);

                if (additionalClassName) {
                    $span.addClass(additionalClassName);
                }

                html = $span.get(0).outerHTML;
            }

            if (!noWhiteSpace) {
                if (html) {
                    html += '&nbsp;';
                }
            }

            return html;
        },

        /**
         * Sanitize HTML.
         *
         * @param {type} text HTML.
         * @param {Object} [options] Options.
         * @returns {string}
         */
        sanitizeHtml: function (text, options) {
            return DOMPurify.sanitize(text, options);
        },

        /**
         * Moderately sanitize HTML.
         *
         * @param {string} value HTML.
         * @returns {string}
         */
        moderateSanitizeHtml: function (value) {
            value = value || '';
            value = value.replace(/<[\/]{0,1}(base)[^><]*>/gi, '');
            value = value.replace(/<[\/]{0,1}(object)[^><]*>/gi, '');
            value = value.replace(/<[\/]{0,1}(embed)[^><]*>/gi, '');
            value = value.replace(/<[\/]{0,1}(applet)[^><]*>/gi, '');
            value = value.replace(/<[\/]{0,1}(iframe)[^><]*>/gi, '');
            value = value.replace(/<[\/]{0,1}(script)[^><]*>/gi, '');
            value = value.replace(/<[^><]*([^a-z]{1}on[a-z]+)=[^><]*>/gi, function (match) {
                return match.replace(/[^a-z]{1}on[a-z]+=/gi, ' data-handler-stripped=');
            });

            value = this.stripEventHandlersInHtml(value);

            value = value.replace(/href=" *javascript\:(.*?)"/gi, function(m, $1) {
                return 'removed=""';
            });

            value = value.replace(/href=' *javascript\:(.*?)'/gi, function(m, $1) {
                return 'removed=""';
            });

            value = value.replace(/src=" *javascript\:(.*?)"/gi, function(m, $1) {
                return 'removed=""';
            });

            value = value.replace(/src=' *javascript\:(.*?)'/gi, function(m, $1) {
                return 'removed=""';
            });

            return value;
        },

        /**
         * Strip event handlers in HTML.
         *
         * @param {string} html HTML.
         * @returns {string}
         */
        stripEventHandlersInHtml: function (html) {
            function stripHTML() {
                html = html.slice(0, strip) + html.slice(j);
                j = strip;

                strip = false;
            }

            function isValidTagChar(str) {
                return str.match(/[a-z?\\\/!]/i);
            }

            var strip = false;
            var lastQuote = false;

            for (var i = 0; i < html.length; i++){
                if (html[i] === "<" && html[i + 1] && isValidTagChar(html[i + 1])) {
                    i++;

                    for (var j = i; j<html.length; j++){
                        if (!lastQuote && html[j] === ">"){
                            if (strip) {
                                stripHTML();
                            }

                            i = j;

                            break;
                        }

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
        },

        /**
         * Calculate a content container height.
         *
         * @param {$} $el Element.
         * @returns {number}
         */
        calculateContentContainerHeight: function ($el) {
            var smallScreenWidth = this.themeManager.getParam('screenWidthXs');

            var $window = $(window);

            var footerHeight = $('#footer').height() || 26;
            var top = 0;
            var element = $el.get(0);

            if (element) {
                top = element.getBoundingClientRect().top;

                if ($window.width() < smallScreenWidth) {
                    var $navbarCollapse = $('#navbar .navbar-body');

                    if ($navbarCollapse.hasClass('in') || $navbarCollapse.hasClass('collapsing')) {
                        top -= $navbarCollapse.height();
                    }
                }
            }

            var spaceHeight = top + footerHeight;

            return $window.height() - spaceHeight - 20;
        },

        /**
         * Process view-setup-handlers.
         *
         * @param {module:view} view A view.
         * @param {string} type A view-setup-handler type.
         * @param {string} [scope] A scope.
         */
        processSetupHandlers: function (view, type, scope) {
            scope = scope || view.scope;

            let handlerList = this.metadata.get(['clientDefs', 'Global', 'viewSetupHandlers', type]) || [];

            if (scope) {
                handlerList = handlerList
                    .concat(
                        this.metadata.get(['clientDefs', scope, 'viewSetupHandlers', type]) || []
                    );
            }

            if (handlerList.length === 0) {
                return;
            }

            for (let handlerClassName of handlerList) {
                let promise = new Promise(function (resolve) {
                    require(handlerClassName, function (Handler) {
                        let result = (new Handler(view)).process(view);

                        if (result && Object.prototype.toString.call(result) === '[object Promise]') {
                            result.then(function () {
                                resolve();
                            });

                            return;
                        }

                        resolve();
                    });
                });

                view.wait(promise);
            }
        },

        /**
         * @deprecated Use `transformMarkdownText`.
         * @internal Used in extensions.
         */
        transfromMarkdownText: function (text, options) {
            return this.transformMarkdownText(text, options);
        },

        /**
         * @deprecated Use `transformMarkdownInlineText`.
         * @internal Used in extensions.
         */
        transfromMarkdownInlineText: function (text) {
            return this.transformMarkdownInlineText(text);
        },
    });

    return ViewHelper;
});

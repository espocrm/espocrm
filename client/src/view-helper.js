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

Espo.define('view-helper', [], function () {

    var ViewHelper = function (options) {
        this.urlRegex = /(^|[^\(])(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
        this._registerHandlebarsHelpers();

        this.mdBeforeList = [
            {
                regex: /\["?(.*?)"?\]\((.*?)\)/g,
                value: '<a href="$2">$1</a>'
            },
            {
                regex: /\&\#x60;\&\#x60;\&\#x60;\n?([\s\S]*?)\&\#x60;\&\#x60;\&\#x60;/g,
                value: function (s, string) {
                    return '<pre><code>' + string.replace(/\*/g, '&#42;').replace(/\~/g, '&#126;') + '</code></pre>';
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
            tables: false
        });
    }

    _.extend(ViewHelper.prototype, {

        layoutManager: null,

        settings: null,

        user: null,

        preferences: null,

        language: null,

        getAppParam: function (name) {
            return (this.appParams || {})[name];
        },

        stripTags: function (text) {
            text = text || '';
            if (typeof text === 'string' || text instanceof String) {
                return text.replace(/<\/?[^>]+(>|$)/g, '');
            }
            return text;
        },

        getAvatarHtml: function (id, size, width, additionalClassName) {
            if (this.config.get('avatarsDisabled')) {
                return '';
            }

            var t;
            var cache = this.cache;
            if (cache) {
                t = cache.get('app', 'timestamp');
            } else {
                t = Date.now();
            }

            var basePath = this.basePath || '';
            var size = size || 'small';

            var width = (width || 16).toString();

            var className = 'avatar';
            if (additionalClassName) {
                className += ' ' + additionalClassName;
            }

            return '<img class="'+className+'" width="'+width+'" src="'+basePath+'?entryPoint=avatar&size='+size+'&id=' + id + '&t='+t+'">';
        },

        _registerHandlebarsHelpers: function () {
            var self = this;

            Handlebars.registerHelper('img', function (img) {
                return new Handlebars.SafeString("<img src=\"img/" + img + "\"></img>");
            });

            Handlebars.registerHelper('prop', function (object, name) {
                if (name in object) {
                    return object[name];
                }
            });

            Handlebars.registerHelper('var', function (name, context, options) {
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
                var value = model.get(attr);
                if (value !== null && typeof value !== 'undefined') {
                    return options.fn(this);
                }
                return options.inverse(this);
            });

            Handlebars.registerHelper('get', function (model, name) {
                return model.get(name);
            });

            Handlebars.registerHelper('length', function (arr) {
                return arr.length;
            });

            Handlebars.registerHelper('translate', function (name, options) {
                var scope = options.hash.scope || null;
                var category = options.hash.category || null;
                if (name === 'null') {
                    return '';
                }
                return self.language.translate(name, category, scope);
            });

            Handlebars.registerHelper('button', function (name, options) {
                var style = options.hash.style || 'default';
                var scope = options.hash.scope || null;
                var label = options.hash.label || name;

                var html = options.hash.html || options.hash.text || self.language.translate(label, 'labels', scope);
                return new Handlebars.SafeString('<button class="btn btn-'+style+' action'+ (options.hash.hidden ? ' hidden' : '')+'" data-action="'+name+'" type="button">'+html+'</button>');
            });

            Handlebars.registerHelper('hyphen', function (string) {
                return Espo.Utils.convert(string, 'c-h');
            });

            Handlebars.registerHelper('toDom', function (string) {
                return Espo.Utils.toDom(string);
            });

            Handlebars.registerHelper('breaklines', function (text) {
                text = Handlebars.Utils.escapeExpression(text || '');
                text = text.replace(/(\r\n|\n|\r)/gm, '<br>');
                return new Handlebars.SafeString(text);
            });

            Handlebars.registerHelper('complexText', function (text) {
                text = text || ''

                text = text.replace(this.urlRegex, '$1[$2]($2)');

                text = Handlebars.Utils.escapeExpression(text).replace(/&gt;+/g, '>');

                this.mdBeforeList.forEach(function (item) {
                    text = text.replace(item.regex, item.value);
                });

                text = marked(text);

                text = text.replace('[#see-more-text]', ' <a href="javascript:" data-action="seeMoreText">' + self.language.translate('See more')) + '</a>';
                return new Handlebars.SafeString(text);
            }.bind(this));

            Handlebars.registerHelper('translateOption', function (name, options) {
                var scope = options.hash.scope || null;
                var field = options.hash.field || null;
                if (!field) {
                    return '';
                }
                var translationHash = options.hash.translatedOptions || null;
                if (translationHash === null) {
                    translationHash = self.language.translate(field, 'options', scope) || {};
                    if (typeof translationHash !== 'object') {
                        translationHash = {};
                    }
                }
                return translationHash[name] || name;
            });

            Handlebars.registerHelper('options', function (list, value, options) {
                if (typeof value === 'undefined') {
                    value = false;
                }
                list = list || {};
                var html = '';
                var isArray = (Object.prototype.toString.call(list) === '[object Array]');

                var multiple = (Object.prototype.toString.call(value) === '[object Array]');
                var checkOption = function (name) {
                    if (multiple) {
                        return value.indexOf(name) !== -1;
                    } else {
                        return value === name;
                    }
                };

                options.hash = options.hash || {};

                var scope = options.hash.scope || false;
                var category = options.hash.category || false;
                var field = options.hash.field || false;

                var translationHash = options.hash.translationHash || options.hash.translatedOptions || null;

                if (translationHash === null) {
                    if (!category && field) {
                        translationHash = self.language.translate(field, 'options', scope) || {};
                        if (typeof translationHash !== 'object') {
                            translationHash = {};
                        }
                    } else {
                        translationHash = {};
                    }
                }
                var translate = function (name) {
                    if (!category) {
                        return translationHash[name] || name;
                    }
                    return self.language.translate(name, category, scope);
                };

                for (var key in list) {
                    var keyVal = list[key];
                    html += "<option value=\"" + keyVal + "\" " + (checkOption(list[key]) ? 'selected' : '') + ">" + translate(list[key]) + "</option>"
                }
                return new Handlebars.SafeString(html);
            });

            Handlebars.registerHelper('basePath', function () {
                return self.basePath || '';
            });
        },

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
                if (html) html += '&nbsp;';
            }

            return html;
        }
    });

    return ViewHelper;

});

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('email-helper', [], function () {

    var EmailHelper = function (language, user, dateTime) {
        this.language = language;
        this.user = user;
        this.dateTime = dateTime;
    }

    _.extend(EmailHelper.prototype, {

        getLanguage: function () {
            return this.language;
        },

        getUser: function () {
            return this.user;
        },

        getDateTime: function () {
            return this.dateTime;
        },

        getReplyAttributes: function (model, data, cc) {
            var attributes = {
                status: 'Draft',
                isHtml: model.get('isHtml')
            };

            var subject = model.get('name') || '';
            if (subject.toUpperCase().indexOf('RE:') !== 0) {
                attributes['name'] = 'Re: ' + subject;
            } else {
                attributes['name'] = subject;
            }

            var to = '';

            var nameHash = model.get('nameHash') || {};

            var isReplyOnSent = false;

            if (model.get('replyToString')) {
                var str = model.get('replyToString');

                var a = [];
                str.split(';').forEach(function (item) {
                    var part = item.trim();
                    var address = this.parseAddressFromStringAddress(item);

                    if (address) {
                        a.push(address);
                        var name = this.parseNameFromStringAddress(part);
                        if (name && name !== address) {
                            nameHash[address] = name;
                        }

                    }
                }, this);
                to = a.join('; ');
            }
            if (!to || !~to.indexOf('@')) {
                if (model.get('from')) {
                    if (model.get('from') != this.getUser().get('emailAddress')) {
                        to = model.get('from');
                        if (!nameHash[to]) {
                            var fromString = model.get('fromString') || model.get('fromName');
                            if (fromString) {
                                var name = this.parseNameFromStringAddress(fromString);
                                if (name != to) {
                                    nameHash[to] = name;
                                }
                            }
                        }
                    } else {
                        isReplyOnSent = true;
                    }
                }
            }

            attributes.to = to;

            if (cc) {
                attributes.cc = model.get('cc') || '';
                (model.get('to') || '').split(';').forEach(function (item) {
                    item = item.trim();
                    if (item != this.getUser().get('emailAddress')) {
                        if (isReplyOnSent) {
                            attributes.to += '; ' + item;
                        } else {
                            attributes.cc += '; ' + item;
                        }
                    }
                }, this);
                attributes.cc = attributes.cc.replace(/^(\; )/,"");
            }

            if (model.get('parentId')) {
                attributes['parentId'] = model.get('parentId');
                attributes['parentName'] = model.get('parentName');
                attributes['parentType'] = model.get('parentType');
            }

            attributes.nameHash = nameHash;

            attributes.repliedId = model.id;

            this.addReplyBodyAttrbutes(model, attributes);

            return attributes;
        },

        getForwardAttributes: function (model, data, cc) {
            var attributes = {
                status: 'Draft',
                isHtml: model.get('isHtml')
            };

            var subject = model.get('name');
            if (~!subject.toUpperCase().indexOf('FWD:') && ~!subject.toUpperCase().indexOf('FW:')) {
                attributes['name'] = 'Fwd: ' + subject;
            } else {
                attributes['name'] = subject;
            }

            if (model.get('parentId')) {
                attributes['parentId'] = model.get('parentId');
                attributes['parentName'] = model.get('parentName');
                attributes['parentType'] = model.get('parentType');
            }

            this.addForwardBodyAttrbutes(model, attributes);

            return attributes;
        },

        addForwardBodyAttrbutes: function (model, attributes) {
            var prepending = '';

            if (model.get('isHtml')) {
                prepending = '<br>' + '------' + this.getLanguage().translate('Forwarded message', 'labels', 'Email') + '------';
            } else {
                prepending = '\n\n' + '------' + this.getLanguage().translate('Forwarded message', 'labels', 'Email') + '------';
            }

            var list = [];

            if (model.get('from')) {
                var from = model.get('from');
                var line = this.getLanguage().translate('from', 'fields', 'Email') + ': ';
                var nameHash = model.get('nameHash') || {};
                if (from in nameHash) {
                    line += nameHash[from] + ' ';
                }
                if (model.get('isHtml')) {
                    line += '&lt;' + from + '&gt;';
                } else {
                    line += '<' + from + '>';
                }

                list.push(line);
            }

            if (model.get('dateSent')) {
                line = this.getLanguage().translate('dateSent', 'fields', 'Email') + ': ';
                line += this.getDateTime().toDisplayDateTime(model.get('dateSent'));
                list.push(line);
            }

            if (model.get('name')) {
                var line = this.getLanguage().translate('subject', 'fields', 'Email') + ': ';
                line += model.get('name');
                list.push(line);
            }

            if (model.get('to')) {
                var line = this.getLanguage().translate('to', 'fields', 'Email') + ': ';
                var partList = [];
                model.get('to').split(';').forEach(function (to) {
                    var nameHash = model.get('nameHash') || {};
                    var line = '';
                    if (to in nameHash) {
                        line += nameHash[to] + ' ';
                    }
                    if (model.get('isHtml')) {
                        line += '&lt;' + to + '&gt;';
                    } else {
                        line += '<' + to + '>';
                    }
                    partList.push(line);

                }, this);
                line += partList.join('; ');
                list.push(line);
            }

            list.forEach(function (line) {
                if (model.get('isHtml')) {
                    prepending += '<br>' + line;
                } else {
                    prepending += '\n' + line;
                }
            }, this);

            if (model.get('isHtml')) {
                var body = model.get('body');
                attributes['body'] = prepending + '<br><br>' + body;
            } else {
                var bodyPlain = model.get('body') || model.get('bodyPlain') || '';
                attributes['bodyPlain'] = attributes['body'] = prepending + '\n\n' + bodyPlain;
            }
        },

        parseNameFromStringAddress: function (value) {
            if (~value.indexOf('<')) {
                var name = value.replace(/<(.*)>/, '').trim();
                if (name.charAt(0) === '"' && name.charAt(name.length - 1) === '"') {
                    name = name.substr(1, name.length - 2);
                }
                return name;
            }
            return null;
        },

        parseAddressFromStringAddress: function (value) {
            var r = value.match(/<(.*)>/);
            var address = null;
            if (r && r.length > 1) {
                address = r[1];
            } else {
                address = value.trim();
            }
            return address;
        },

        addReplyBodyAttrbutes: function (model, attributes) {
            if (model.get('isHtml')) {
                var body = model.get('body');
                body = '<br><blockquote>' + '------' + this.getLanguage().translate('Original message', 'labels', 'Email') + '------<br>' + body + '</blockquote>';

                attributes['body'] = body;
            } else {
                var bodyPlain = model.get('body') || model.get('bodyPlain') || '';

                var b = '\n\n';
                b += '------' + this.getLanguage().translate('Original message', 'labels', 'Email') + '------' + '\n';

                bodyPlain.split('\n').forEach(function (line) {
                    b += '> ' + line + '\n';
                });
                bodyPlain = b;

                attributes['body'] = bodyPlain;
                attributes['bodyPlain'] = bodyPlain;
            }
        },

    });

    return EmailHelper;

});

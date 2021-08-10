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

define('email-helper', [], function () {

    let EmailHelper = function (language, user, dateTime, acl) {
        this.language = language;
        this.user = user;
        this.dateTime = dateTime;
        this.acl = acl;

        this.erasedPlaceholder = 'ERASED:';
    };

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
            let attributes = {
                status: 'Draft',
                isHtml: model.get('isHtml')
            };

            let subject = model.get('name') || '';

            if (subject.toUpperCase().indexOf('RE:') !== 0) {
                attributes['name'] = 'Re: ' + subject;
            }
            else {
                attributes['name'] = subject;
            }

            let to = '';

            let nameHash = model.get('nameHash') || {};

            let isReplyOnSent = false;

            let replyToAddressString = model.get('replyTo') || null;

            if (replyToAddressString) {
                let replyToAddressList = replyToAddressString.split(';');

                to = replyToAddressList.join(';');
            }
            else {
                if (model.get('replyToString')) {
                    let str = model.get('replyToString');

                    let a = [];

                    str.split(';').forEach(item => {
                        let part = item.trim();
                        let address = this.parseAddressFromStringAddress(item);

                        if (address) {
                            a.push(address);

                            let name = this.parseNameFromStringAddress(part);

                            if (name && name !== address) {
                                nameHash[address] = name;
                            }
                        }
                    });

                    to = a.join(';');
                }
            }

            if (!to || !~to.indexOf('@')) {
                if (model.get('from')) {
                    if (!~(this.getUser().get('emailAddressList') || []).indexOf(model.get('from'))) {
                        to = model.get('from');

                        if (!nameHash[to]) {
                            let fromString = model.get('fromString') || model.get('fromName');

                            if (fromString) {
                                let name = this.parseNameFromStringAddress(fromString);

                                if (name !== to) {
                                    nameHash[to] = name;
                                }
                            }
                        }
                    }
                    else {
                        isReplyOnSent = true;
                    }
                }
            }

            attributes.to = to;

            if (cc) {
                attributes.cc = model.get('cc') || '';

                (model.get('to') || '').split(';').forEach(item => {
                    item = item.trim();

                    if (item !== this.getUser().get('emailAddress')) {
                        if (isReplyOnSent) {
                            if (attributes.to) {
                                attributes.to += ';';
                            }

                            attributes.to += item;
                        }
                        else {
                            if (attributes.cc) {
                                attributes.cc += ';';
                            }

                            attributes.cc += item;
                        }
                    }
                });

                attributes.cc = attributes.cc.replace(/^(\; )/,"");
            }

            if (attributes.to) {
                let toList = attributes.to.split(';');

                toList = toList.filter(item => {
                    if (item.indexOf(this.erasedPlaceholder) === 0) {
                        return false;
                    }

                    return true;
                });

                attributes.to = toList.join(';');
            }

            if (attributes.cc) {
                let ccList = attributes.cc.split(';');

                ccList = ccList.filter(item => {
                    if (item.indexOf(this.erasedPlaceholder) === 0) {
                        return false;
                    }

                    return true;
                });

                attributes.cc = ccList.join(';');
            }

            if (model.get('parentId')) {
                attributes['parentId'] = model.get('parentId');
                attributes['parentName'] = model.get('parentName');
                attributes['parentType'] = model.get('parentType');
            }

            if (model.get('teamsIds') && model.get('teamsIds').length) {
                attributes.teamsIds = Espo.Utils.clone(model.get('teamsIds'));
                attributes.teamsNames = Espo.Utils.clone(model.get('teamsNames') || {});

                let defaultTeamId = this.user.get('defaultTeamId');

                if (defaultTeamId && !~attributes.teamsIds.indexOf(defaultTeamId)) {
                    attributes.teamsIds.push(this.user.get('defaultTeamId'));
                    attributes.teamsNames[this.user.get('defaultTeamId')] = this.user.get('defaultTeamName');
                }

                attributes.teamsIds = attributes.teamsIds.filter(teamId => {
                    return this.acl.checkTeamAssignmentPermission(teamId);
                });
            }

            attributes.nameHash = nameHash;

            attributes.repliedId = model.id;

            attributes.inReplyTo = model.get('messageId');

            this.addReplyBodyAttributes(model, attributes);

            return attributes;
        },

        getForwardAttributes: function (model, data, cc) {
            let attributes = {
                status: 'Draft',
                isHtml: model.get('isHtml'),
            };

            let subject = model.get('name');

            if (~!subject.toUpperCase().indexOf('FWD:') && ~!subject.toUpperCase().indexOf('FW:')) {
                attributes['name'] = 'Fwd: ' + subject;
            }
            else {
                attributes['name'] = subject;
            }

            if (model.get('parentId')) {
                attributes['parentId'] = model.get('parentId');
                attributes['parentName'] = model.get('parentName');
                attributes['parentType'] = model.get('parentType');
            }

            this.addForwardBodyAttributes(model, attributes);

            return attributes;
        },

        addForwardBodyAttributes: function (model, attributes) {
            let prepending = '';

            if (model.get('isHtml')) {
                prepending = '<br>' + '------' +
                    this.getLanguage().translate('Forwarded message', 'labels', 'Email') + '------';
            }
            else {
                prepending = '\n\n' + '------' +
                    this.getLanguage().translate('Forwarded message', 'labels', 'Email') + '------';
            }

            let list = [];

            if (model.get('from')) {
                let from = model.get('from');
                let line = this.getLanguage().translate('from', 'fields', 'Email') + ': ';

                let nameHash = model.get('nameHash') || {};

                if (from in nameHash) {
                    line += nameHash[from] + ' ';
                }

                if (model.get('isHtml')) {
                    line += '&lt;' + from + '&gt;';
                }
                else {
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
                let line = this.getLanguage().translate('subject', 'fields', 'Email') + ': ';

                line += model.get('name');

                list.push(line);
            }

            if (model.get('to')) {
                let line = this.getLanguage().translate('to', 'fields', 'Email') + ': ';

                let partList = [];

                model.get('to').split(';').forEach(to => {
                    let nameHash = model.get('nameHash') || {};
                    let line = '';

                    if (to in nameHash) {
                        line += nameHash[to] + ' ';
                    }

                    if (model.get('isHtml')) {
                        line += '&lt;' + to + '&gt;';
                    }
                    else {
                        line += '<' + to + '>';
                    }

                    partList.push(line);
                });

                line += partList.join(';');

                list.push(line);
            }

            list.forEach(line => {
                if (model.get('isHtml')) {
                    prepending += '<br>' + line;
                }
                else {
                    prepending += '\n' + line;
                }
            });

            if (model.get('isHtml')) {
                let body = model.get('body');

                attributes['body'] = prepending + '<br><br>' + body;
            }
            else {
                let bodyPlain = model.get('body') || model.get('bodyPlain') || '';

                attributes['bodyPlain'] = attributes['body'] = prepending + '\n\n' + bodyPlain;
            }
        },

        parseNameFromStringAddress: function (value) {
            if (~value.indexOf('<')) {
                let name = value.replace(/<(.*)>/, '').trim();

                if (name.charAt(0) === '"' && name.charAt(name.length - 1) === '"') {
                    name = name.substr(1, name.length - 2);
                }

                return name;
            }

            return null;
        },

        parseAddressFromStringAddress: function (value) {
            let r = value.match(/<(.*)>/);
            let address = null;

            if (r && r.length > 1) {
                address = r[1];
            }
            else {
                address = value.trim();
            }

            return address;
        },

        addReplyBodyAttributes: function (model, attributes) {
            let format = this.getDateTime().getReadableShortDateTimeFormat();

            let dateSent = model.get('dateSent');

            let dateSentSting = null;

            if (dateSent) {
                let dateSentMoment = this.getDateTime().toMoment(dateSent);

                dateSentSting = dateSentMoment.format(format);
            }

            let replyHeadString =
                (dateSentSting || this.getLanguage().translate('Original message', 'labels', 'Email'));

            let fromName = model.get('fromName');

            if (!fromName && model.get('from')) {
                fromName = (model.get('nameHash') || {})[model.get('from')];

                if (fromName) {
                    replyHeadString += ', ' + fromName;
                }
            }

            replyHeadString += ':';

            if (model.get('isHtml')) {
                let body = model.get('body');

                body = '<p>&nbsp;</p><p>' +  replyHeadString + '</p><blockquote>' +  body + '</blockquote>';

                attributes['body'] = body;
            }
            else {
                let bodyPlain = model.get('body') || model.get('bodyPlain') || '';

                let b = '\n\n';

                b += replyHeadString + '\n';

                bodyPlain.split('\n').forEach(line => {
                    b += '> ' + line + '\n';
                });

                bodyPlain = b;

                attributes['body'] = bodyPlain;
                attributes['bodyPlain'] = bodyPlain;
            }
        },

        composeMailToLink: function (attributes, bcc) {
            let link = 'mailto:';

            link += (attributes.to || '').split(';').join(',');

            let o = {};

            if (attributes.cc) {
                o.cc = attributes.cc.split(';').join(',');
            }

            if (attributes.bcc) {
                 if (!bcc) {
                    bcc = '';
                } else {
                    bcc += ';';
                }
                bcc += attributes.bcc;
            }

            if (bcc) {
                o.bcc = bcc.split(';').join(',');
            }

            if (attributes.name) {
                o.subject = attributes.name;
            }

            if (attributes.body) {
                o.body = attributes.body;
                if (attributes.isHtml) {
                    o.body = this.htmlToPlain(o.body);
                }
            }

            if (attributes.inReplyTo) {
                o['In-Reply-To'] = attributes.inReplyTo;
            }

            let part = '';

            for (let key in o) {
                if (part !== '') {
                    part += '&';
                }
                else {
                    part += '?';
                }

                part += key + '=' + encodeURIComponent(o[key]);
            }

            link += part;

            return link;
        },

        htmlToPlain: function (text) {
            text = text || '';

            let value = text.replace(/<br\s*\/?>/mg, '\n');

            value = value.replace(/<\/p\s*\/?>/mg, '\n\n');

            let $div = $('<div>').html(value);

            $div.find('style').remove();
            $div.find('link[ref="stylesheet"]').remove();

            value =  $div.text();

            return value;
        },
    });

    return EmailHelper;
});

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

Espo.define('views/email/record/compose', ['views/record/edit', 'views/email/record/detail'], function (Dep, Detail) {

    return Dep.extend({

        isWide: true,

        sideView: false,

        setup: function () {
        	Dep.prototype.setup.call(this);

        	if (!this.options.signatureDisabled && this.hasSignature()) {
                var body = this.prependSignature(this.model.get('body') || '', this.model.get('isHtml'));
	        	this.model.set('body', body);
	        }

            this.listenTo(this.model, 'insert-template', function (data) {
                var body = this.appendSignature(data.body || '', data.isHtml);
                this.model.set('isHtml', data.isHtml);
                this.model.set('name', data.subject);
                this.model.set('body', '');
                this.model.set('body', body);
                this.model.set({
                    attachmentsIds: data.attachmentsIds,
                    attachmentsNames: data.attachmentsNames
                });
            }, this);

            if (this.options.selectTemplateDisabled) {
                this.hideField('selectTemplate');
            }
        },

        prependSignature: function (body, isHtml) {
            if (isHtml) {
                var signature = this.getSignature();
                if (body) {
                    signature += '<br>';
                }
                body = '<p><br></p><br>' + signature + body;
            } else {
                var signature = this.getPlainTextSignature();
                if (body) {
                    signature += '\n';
                }
                body = '\n\n' + signature + body;
            }
            return body;
        },

        appendSignature: function (body, isHtml) {
            if (isHtml) {
                var signature = this.getSignature();
                body = body + '<p><br></p>' + signature;
            } else {
                var signature = this.getPlainTextSignature();
                body = body + '\n\n' + signature;
            }
            return body;
        },

        hasSignature: function () {
            return !!this.getPreferences().get('signature');
        },

        getSignature: function () {
            return this.getPreferences().get('signature');
        },

        getPlainTextSignature: function () {
            var value = this.getSignature().replace(/<br\s*\/?>/mg, '\n');
            value = $('<div>').html(value).text();
            return value;
        },

        send: function () {
            Detail.prototype.send.call(this);
        },

        saveDraft: function () {
            var model = this.model;
            model.set('status', 'Draft');

            this.save();
        }

    });

});

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
 ************************************************************************/

Espo.define('Views.Email.Record.Compose', ['Views.Record.Edit', 'Views.Email.Record.Detail'], function (Dep, Detail) {

    return Dep.extend({

        isWide: true,

        sideView: false,

        setup: function () {
        	Dep.prototype.setup.call(this);

        	if (this.hasSignature()) {
	        	var body = this.model.get('body') || '';
                if (this.model.get('isHtml')) {
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
	        	this.model.set('body', body);
	        }
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

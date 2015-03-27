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

Espo.define('Views.Import.Detail', 'Views.Detail', function (Dep) {

    return Dep.extend({

        getHeader: function () {
        	var dt = this.model.get('createdAt');
        	dt = this.getDateTime().toDisplay(dt);
            var name = Handlebars.Utils.escapeExpression(dt);

            return this.buildHeaderHtml([
                '<a href="#' + this.model.name + '/list">' + this.getLanguage().translate(this.model.name, 'scopeNamesPlural') + '</a>',
                name
            ]);
        },

        actionRevert: function () {
        	if (confirm(this.translate('confirmation', 'messages'))) {
	        	$.ajax({
	        		type: 'POST',
	        		url: 'Import/action/revert',
	        		data: JSON.stringify({
	        			id: this.model.id
	        		})
	        	}).done(function () {
	        		this.getRouter().navigate('#Import/list', {trigger: true});
	        	});
        	}
        },

        actionRemoveDuplicates: function () {
        	if (confirm(this.translate('confirmation', 'messages'))) {
	        	$.ajax({
	        		type: 'POST',
	        		url: 'Import/action/removeDuplicates',
	        		data: JSON.stringify({
	        			id: this.model.id
	        		})
	        	}).done(function () {
	        		this.getRouter().navigate('#Import/list', {trigger: true});
	        	});
        	}
        }

    });
});


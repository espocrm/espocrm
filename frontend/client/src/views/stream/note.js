/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('Views.Stream.Note', 'View', function (Dep) {

	return Dep.extend({
	
		data: function () {
			return {
				isUserStream: this.isUserStream,
				parentTypeString: this.translate(this.model.get('parentType'), 'scopeNames').toLowerCase(),
				acl: this.options.acl,
				onlyContent: this.options.onlyContent
			};
		},
	
		init: function () {
			this.createField('createdBy');
			this.createField('createdAt');			
			this.isUserStream = this.options.isUserStream;
			
			if (this.isUserStream) {
				this.createField('parent');
			}
		},
		
		createField: function (name, type, params) {			
			type = type || this.model.getFieldType(name) || 'base';		
			this.createView(name, this.getFieldManager().getViewName(type), {
				model: this.model,
				defs: {
					name: name,
					params: params || {}
				},
				mode: 'list'
			});
		},

	});
});


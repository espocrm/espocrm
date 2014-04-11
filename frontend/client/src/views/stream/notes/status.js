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

Espo.define('Views.Stream.Notes.Status', 'Views.Stream.Note', function (Dep) {

	return Dep.extend({

		template: 'stream.notes.status',
		
		data: function () {
			return _.extend({
				style: this.style,
				statusText: this.statusText,
				parentType: this.model.get('parentType'),
				field: this.field
			}, Dep.prototype.data.call(this));
		},
				
		setup: function () {
			var data = JSON.parse(this.model.get('data'));
			
			var field = this.field = data.field;
			var value = data.value;
			
			this.style = data.style || 'default';
			
			this.statusText = this.getLanguage().translateOption(value, field, this.model.get('parentType'));
		},
		
	});
});


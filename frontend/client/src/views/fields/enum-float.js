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

Espo.define('Views.Fields.EnumFloat', 'Views.Fields.EnumInt', function (Dep) {

	return Dep.extend({

		type: 'enumFloat',		

		fetch: function () {
			var value = parseFloat(this.$el.find('[name="' + this.name + '"]').val());
			var data = {};
			data[this.name] = value;
			return data;
		},

		fetchSearch: function () {
			var arr = [];
			$.each(this.$el.find('[name="' + this.name + '"]').find('option:selected'), function (i, el) {
				arr.push(parseFloat($(el).val()));
			});
			var data = {
				type: 'in',
				value: arr
			};
			return data;
		},
	});
});


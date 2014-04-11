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

Espo.define('Views.Fields.Datetime', 'Views.Fields.Date', function (Dep) {

	return Dep.extend({

		type: 'datetime',

		editTemplate: 'fields.datetime.edit',

		validations: ['required', 'datetime', 'after', 'before'],
		
		searchTypeOptions: ['on', 'after', 'before', 'between'],

		timeFormatMap: {
			'HH:mm': 'H:i',
			'hh:mm A': 'h:i A',
			'hh:mm a': 'h:i a',
		},

		data: function () {
			var data = Dep.prototype.data.call(this);

			data.date = data.time = '';
			var value = this.getDateTime().toDisplay(this.model.get(this.name));
			if (value) {
				data.date = value.substr(0, value.indexOf(' '));
				data.time = value.substr(value.indexOf(' ') + 1);
			}
			return data;
		},

		getValueForDisplay: function () {
			var value = this.model.get(this.name);
			if (!value) {
				if (this.mode == 'edit' || this.mode == 'search') {
					return '';
				}
				return this.translate('None');
			}
			return this.getDateTime().toDisplay(value);
		},

		afterRender: function () {
			var self = this;
			Dep.prototype.afterRender.call(this);
			if (this.mode == 'edit') {
				var $date = this.$date = this.$element;				
				var $time = this.$time = this.$el.find('input[name="' + this.name + '-time"]');
				$time.timepicker({
					step: 30,
					scrollDefaultNow: true,
					timeFormat: this.timeFormatMap[this.getDateTime().timeFormat]
				});
				$time.parent().find('button').click(function () {
					$time.timepicker('show');
				});

				var timeout = false;
				var changeCallback = function () {
					if (!timeout) {
						self.trigger('change');
					}
					timeout = true;
					setTimeout(function () {
						timeout = false;
					}, 100)
				};
				$time.on('change', changeCallback);
			}
		},
		
		update: function (value) {
			if (this.mode == 'edit') {
				var formatedValue = this.getDateTime().toDisplay(value);
				var arr = formatedValue.split(' ');
				this.$date.val(arr[0]);
				this.$time.val(arr[1]);
			} else {
				this.setup();
				this.render();
			}
		},

		parse: function (string) {
			return this.getDateTime().fromDisplay(string);
		},

		fetch: function () {
			var data = {};

			var date = this.$el.find('[name="' + this.name + '"]').val();
			var time = this.$el.find('[name="' + this.name + '-time"]').val();

			var value = null;
			if (date != '' && time != '') {
				value = this.parse(date + ' ' + time);
			}
			data[this.name] = value;
			return data;
		},

		validateDatetime: function () {
			if (this.model.get(this.name) === -1) {
				this.showValidationMessage(this.getLanguage().translate(this.name, 'fields', this.model.name) + " " + this.getLanguage().translate("should be valid date/time"));
				return true;
			}
		},
		
		fetchSearch: function () {
			var value = this.parseDate(this.$element.val());

			var type = this.$el.find('[name="'+this.name+'-type"]').val();
			var data;
			
			if (!value) {
				return false;
			}
			
			if (type != 'between') {
				data = {
					type: type,
					value: value,
					value1: value,
					dateTime: true,
				};
			} else {
				var valueTo = this.parseDate(this.$el.find('[name="' + this.name + '-additional"]').val());
				if (!valueTo) {
					return false;
				}
				data = {
					type: type,
					value: [value, valueTo],
					value1: value,
					value2: valueTo,
					dateTime: true,							
				};
			}
			return data;				
		},
		
	});
});


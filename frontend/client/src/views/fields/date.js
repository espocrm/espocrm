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

Espo.define('Views.Fields.Date', 'Views.Fields.Base', function (Dep) {

	return Dep.extend({	
	
		type: 'date',
		
		editTemplate: 'fields.date.edit',
		
		searchTemplate: 'fields.date.search',
		
		validations: ['required', 'date', 'after', 'before'],
		
		searchTypeOptions: ['on', 'notOn', 'after', 'before', 'between'],
		
		setup: function () {
			Dep.prototype.setup.call(this);
		},
		
		setupSearch: function () {
			this.searchParams.typeOptions = this.searchTypeOptions;
			this.events = _.extend({
				'change select.search-type': function (e) {
					var additional = this.$el.find('.additional');
					if ($(e.currentTarget).val() == 'between') {
						additional.removeClass('hide');
					} else {
						additional.addClass('hide');
					}
				},
			}, this.events || {});
			
			
			this.searchParams.value1 = this.getDateTime().toDisplayDate(this.searchParams.value1);
			this.searchParams.value2 = this.getDateTime().toDisplayDate(this.searchParams.value2);
		},
		
		getValueForDisplay: function () {
			var value = this.model.get(this.name);
			if (!value) {
				if (this.mode == 'edit' || this.mode == 'search') {
					return '';
				}
				return this.translate('None');
			}
			return this.getDateTime().toDisplayDate(value);
		},
		
		afterRender: function () {
			if (this.mode == 'edit' || this.mode == 'search') {
				this.$element = this.$el.find('[name="' + this.name + '"]');
				
				var wait = false;
				this.$element.on('change', function () {
					if (!wait) {
						this.trigger('change');
						wait = true;
						setTimeout(function () {
							wait = false
						}, 100);
					}
				}.bind(this));
			
				var options = {
					format: this.getDateTime().dateFormat.toLowerCase(),
					weekStart: this.getDateTime().weekStart,
					autoclose: true,
					todayHighlight: true,
				};					
				
				this.$element.datepicker(options);
				
				if (this.mode == 'search') {
					var elAdd = this.$el.find('input[name="' + this.name + '-additional"]');
					elAdd.datepicker(options);
					elAdd.parent().find('button').click(function (e) {
						elAdd.datepicker('show');
					});
				}
				
				this.$element.parent().find('button').click(function (e) {
					this.$element.datepicker('show');
				}.bind(this));					
			}
		},
		
		parseDate: function (string) {
			return this.getDateTime().fromDisplayDate(string);
		},
		
		parse: function (string) {
			return this.parseDate(string);
		},
		
		fetch: function () {			
			var data = {};
			data[this.name] = this.parse(this.$element.val());
			return data;
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
					value1: value									
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
					value2: valueTo								
				};
			}
			return data;				
		},
		
		validateRequired: function () {
			if (this.params.required || this.model.isRequired(this.name)) {
				if (this.model.get(this.name) === null) {
					var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
					this.showValidationMessage(msg);
					return true;
				}
			}
		},
		
		validateDate: function () {
			if (this.model.get(this.name) === -1) {
				var msg = this.translate('fieldShouldBeDate', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
				this.showValidationMessage(msg);
				return true;
			}
		},
		
		validateAfter: function () {
			var field = this.model.getFieldParam(this.name, 'after');
			if (field) {
				var value = this.model.get(this.name);
				var otherValue = this.model.get(field);
				if (value && otherValue) {
					if (moment(value).unix() <= moment(otherValue).unix()) {
						var msg = this.translate('fieldShouldAfter', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name))
						                                                        .replace('{otherField}', this.translate(field, 'fields', this.model.name));
						
						this.showValidationMessage(msg);
						return true;	
					}
				}
			}
		},
		
		validateBefore: function () {
			var field = this.model.getFieldParam(this.name, 'before');
			if (field) {
				var value = this.model.get(this.name);
				var otherValue = this.model.get(field);
				if (value && otherValue) {
					if (moment(value).unix() >= moment(otherValue).unix()) {
						var msg = this.translate('fieldShouldBefore', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name))
						                                                        .replace('{otherField}', this.translate(field, 'fields', this.model.name));
						this.showValidationMessage(msg);
						return true;	
					}
				}
			}
		},
	});
});


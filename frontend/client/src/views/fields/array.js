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

Espo.define('Views.Fields.Array', 'Views.Fields.Enum', function (Dep) {

	return Dep.extend({

		type: 'enum',

		listTemplate: 'fields.array.detail',

		detailTemplate: 'fields.array.detail',

		editTemplate: 'fields.array.edit',
		
		data: function () {
			return _.extend({
				selected: this.selected,
				translatedOptions: this.translatedOptions
			}, Dep.prototype.data.call(this));
		},
		
		events: {
			'click [data-action="removeValue"]': function (e) {
				var value = $(e.currentTarget).data('value');
				this.removeValue(value);
			},
		},

		setup: function () {
			Dep.prototype.setup.call(this);			
			
			var t = {};
			if (this.params.translation) {
				var data = this.getLanguage().data;
				var arr = this.params.translation.split('.');
				var pointer = this.getLanguage().data;
				arr.forEach(function (key) {
					if (key in pointer) {
						pointer = pointer[key];
						t = pointer;
					}
				}, this);
			} else {
				t = this.translate(this.name, 'options', this.model.name);
			}

			this.translatedOptions = null;
					
			var translatedOptions = {};
			if (this.params.options) {
				this.params.options.forEach(function (o) {
					if (typeof t === 'object' && o in t) {
						translatedOptions[o] = t[o];
					} else {
						translatedOptions[o] = o;
					}
				}.bind(this));
				this.translatedOptions = translatedOptions;
			}
			
			
			this.selected = _.clone(this.model.get(this.name) || []);				
		},
		
		afterRender: function () {
			this.$list = this.$el.find('.list-group');
			var $select = this.$select = this.$el.find('.select');
			
			if (this.params.options) {				
				var options = [];				
				for (var i in this.translatedOptions) {
					options.push(this.translatedOptions[i]);
				}								
				this.params.options.forEach(function (item) {
					if (!(item in this.translatedOptions)) {
						options.push(item);
					}
				}, this);
							
				$select.autocomplete({
					lookup: options,
					minChars: 0,
			        lookupFilter: function (suggestion, originalQuery, queryLowerCase) {
			            return suggestion.value.toLowerCase().indexOf(queryLowerCase) === 0;
			        },
			        formatResult: function (suggestion) {
			        	return suggestion.value;
			        },
					onSelect: function (s) {
						this.addValue(s.value);
						$select.val('');
					}.bind(this)
				});
			} else {
				$select.on('keypress', function (e) {
					if (e.keyCode == 13) {
						var value = $select.val();
						this.addValue(value);
						$select.val('');

					}
				}.bind(this));
			}
			
			this.$list.sortable({
				stop: function () {
					this.trigger('change');	
				}.bind(this)
			});	
		},
		
		getValueForDisplay: function () {
			return this.selected.join(', ');
		},
		
		addValue: function (value) {		
			for (var item in this.translatedOptions) {
				if (this.translatedOptions[item] == value) {
					value = item;
					break;
				}
			}
		
			if (this.selected.indexOf(value) == -1) {
			
				var label = value;
				if (this.translatedOptions) {
					label = ((value in this.translatedOptions) ? this.translatedOptions [value]: value);
				}	
				var html = '<div class="list-group-item" data-value="' + value + '">' + label +	
				'&nbsp;<a href="javascript:" class="pull-right" data-value="' + value + '" data-action="removeValue"><span class="glyphicon glyphicon-remove"></a>' +
				'</div>';
				this.$list.append(html);
				this.selected.push(value);
				this.trigger('change');
			}		
		},
		
		removeValue: function (value) {
			this.$list.children('[data-value="' + value + '"]').remove();
			var index = this.selected.indexOf(value);
			this.selected.splice(index, 1);
			this.trigger('change');
		},	

		fetch: function () {
			var $li = this.$el.find('.list-group').children('.list-group-item');
			var value = [];
			$li.each(function (i, li) {
				value.push($(li).data('value'));
			});				
			var data = {};
			data[this.name] = value;
			return data;
		},
		
		validateRequired: function () {				
			if (this.model.isRequired(this.name)) {
				var value = this.model.get(this.name);
				if (!value || value.length == 0) {
					var msg = this.translate(this.name, 'fields', this.model.name) + " " + this.translate("is required");
					this.showValidationMessage(msg);
					return true;
				}
			}
		},

	});
});



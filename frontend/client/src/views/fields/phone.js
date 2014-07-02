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

Espo.define('Views.Fields.Phone', 'Views.Fields.Base', function (Dep) {
	
	return Dep.extend({

		type: 'phone',
	
		editTemplate: 'fields.phone.edit',
		
		detailTemplate: 'fields.phone.detail',
		
		listTemplate: 'fields.phone.list',
		
		validations: ['required'],
		
		validateRequired: function () {
			if (this.params.required || this.model.isRequired(this.name)) {
				if (this.model.get(this.name) === '') {
					var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
					this.showValidationMessage(msg, 'div.phone-number-block:nth-child(1) input');
					return true;
				}
			}
		},
		
		data: function () {
			var phoneNumberData;
			if (this.mode == 'edit') {						
				phoneNumberData = Espo.Utils.cloneDeep(this.model.get(this.dataFieldName));
	
				if (this.model.isNew()) {
					if (!this.defaultType) {
						this.defaultType = this.getMetadata().get('entityDefs.' + this.model.name + '.fields.' + this.name + '.defaultType');
					}			
					if (!phoneNumberData || !phoneNumberData.length) {
		 				phoneNumberData = [{
							phoneNumber: '',
							primary: true,
							type: this.defaultType,
						}];
					}
				}
			} else {
				phoneNumberData = this.model.get(this.dataFieldName) || false;
			}
			return _.extend({
				phoneNumberData: phoneNumberData
			}, Dep.prototype.data.call(this));
		},
		
		events: {
			'click [data-action="switchPhoneProperty"]': function (e) {
				var $target = $(e.currentTarget);
				var $block = $(e.currentTarget).closest('div.phone-number-block');
				var property = $target.data('property-type');
				
				
				if (property == 'primary') {
					if (!$target.hasClass('active')) {
						if ($block.find('input.phone-number').val() != '') {
							this.$el.find('button.phone-property[data-property-type="primary"]').removeClass('active').children().addClass('text-muted');
							$target.addClass('active').children().removeClass('text-muted');
						}
					}				
				} else {
					if ($target.hasClass('active')) {
						$target.removeClass('active').children().addClass('text-muted');
					} else {
						$target.addClass('active').children().removeClass('text-muted');
					}
				}
				this.fetchToModel();		
			},
			
			'click [data-action="removePhoneNumber"]': function (e) {
				var $block = $(e.currentTarget).closest('div.phone-number-block');
				if ($block.parent().children().size() == 1) {
					$block.find('input.phone-number').val('');
				} else {				
					this.removePhoneNumberBlock($block);
				}
				this.fetchToModel();
			},
			
			'change input.phone-number': function (e) {
				var $input = $(e.currentTarget);
				var $block = $input.closest('div.phone-number-block');
				
				if ($input.val() == '') {
					if ($block.parent().children().size() == 1) {
						$block.find('input.phone-number').val('');
					} else {				
						this.removePhoneNumberBlock($block);
					}
				}
				
				this.fetchToModel();
			},

			'click [data-action="addPhoneNumber"]': function () {			
				var data = Espo.Utils.cloneDeep(this.fetchPhoneNumberData());				
		
				o = {
					phoneNumber: '',
					primary: data.length ? false : true,
					type: false
				};
				
				data.push(o);				
				
				this.model.set(this.dataFieldName, data, {silent: true});
				this.render();
			},
			
		},
		
		removePhoneNumberBlock: function ($block) {					
			var changePrimary = false;
			if ($block.find('button[data-property-type="primary"]').hasClass('active')) {
				changePrimary = true;
			}					
			$block.remove();
			
			if (changePrimary) {
				this.$el.find('button[data-property-type="primary"]').first().addClass('active').children().removeClass('text-muted');
			}
		},
		
		setup: function () {
			this.dataFieldName = this.name + 'Data';
			
			if (this.mode == 'detail' || this.mode == 'edit') {
				this.listenTo(this.model, 'change:' + this.dataFieldName, function () {				
					this.render();
				}, this);
			}
		},		
		
		fetchPhoneNumberData: function () {
			var data = [];
			
			var $list = this.$el.find('div.phone-number-block');
			
			if ($list.size()) {			
				$list.each(function (i, d) {
					var row = {};
					var $d = $(d);
					row.phoneNumber = $d.find('input.phone-number').val();
					if (row.phoneNumber == '') {
						return;
					}
					row.primary = $d.find('button[data-property-type="primary"]').hasClass('active');
					row.type = $d.find('select[data-property-type="type"]').val();
					data.push(row);				
				}.bind(this));
			}
			
			return data;
		},
		
		fetch: function () {
			var data = {};	
			
			var adderssData = this.fetchPhoneNumberData();		
			data[this.dataFieldName] = adderssData;			
			data[this.name] = null;			
			if (adderssData.length) {
				data[this.name] = adderssData[0].phoneNumber;
			}		
			
			return data;
		},
		
		fetchSearch: function () {
			var value = this.$element.val() || null;
			if (value) {
				value += '%';
				var data = {
					type: 'like',
					value: value,
				};
				return data;
			}
			return false;				
		},
	});

});



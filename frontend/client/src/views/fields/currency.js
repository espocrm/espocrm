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

Espo.define('Views.Fields.Currency', 'Views.Fields.Float', function (Dep) {

	return Dep.extend({

		type: 'currency',

		editTemplate: 'fields.currency.edit',

		detailTemplate: 'fields.currency.detail',

		listTemplate: 'fields.currency.detail',

		data: function () {
			return _.extend({
				currencyFieldName: this.currencyFieldName,
				currencyValue: this.currencyValue,
				currencyOptions: this.currencyOptions,
			}, Dep.prototype.data.call(this));
		},

		setup: function () {
			Dep.prototype.setup.call(this);
			this.currencyFieldName = this.name + 'Currency';
			
			var currencyValue = this.currencyValue = this.model.get(this.currencyFieldName) || this.getConfig().get('defaultCurrency');
		
			this.listenTo(this.model, 'change:' + this.currencyFieldName, function () {
				this.currencyValue = this.model.get(this.currencyFieldName);
				this.updateCurrency();
			}.bind(this));
			
			if (this.mode == 'edit' || this.mode == 'detail') {
				this.updateCurrency();
			}
		},
		
		updateCurrency: function () {
			this.currencyList = this.getConfig().get('currencyList') || ['USD', 'EUR'];	
			var currencyOptions = '';
			this.currencyList.forEach(function (code) {
				currencyOptions += '<option value="' + code + '"' + ((this.currencyValue == code) ? ' selected' : '') + '>' + code + '</option>';
			}, this);
			this.currencyOptions = currencyOptions;			
		},

		afterRender: function () {
			Dep.prototype.afterRender.call(this);
			if (this.mode == 'edit') {
				this.$currency = this.$el.find('[name="' + this.currencyFieldName + '"]');
				this.$currency.on('change', function () {
					this.trigger('change');
				}.bind(this));
			}
		},

		fetch: function () {
			var value = this.$element.val();
			value = this.parse(value);

			var data = {};
			data[this.name] = value;
			data[this.currencyFieldName] = this.$currency.val();
			return data;
		},
	});
});


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

Espo.define('Views.Modals.Duplicate', 'Views.Modal', function (Dep) {

	return Dep.extend({

		cssName: 'duplicate-modal',

		header: false,

		template: 'modals.duplicate',

		data: function () {
			return {
				scope: this.scope,
				duplicates: this.duplicates
			};
		},

		setup: function () {
			this.buttons = [
				{
					name: 'save',
					label: 'Save',
					style: 'danger',
					onClick: function (dialog) {						
						this.trigger('save');
						dialog.close();						
					}.bind(this),
				},
				{
					name: 'cancel',
					label: 'Cancel',
					onClick: function (dialog) {
						dialog.close();
					}
				}
			];
			this.scope = this.options.scope;
			this.duplicates = this.options.duplicates;
		},

	});
});


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

Espo.define('Views.ComposeEmail', 'Views.EditModal', function (Dep) {

	return Dep.extend({
	
		scope: 'Email',
		
		layoutName: 'composeSmall',
	
		saveButton: false,
		
		fullFormButton: false,
		
		editViewName: 'Email.Record.Compose',
		
		columnCount: 2,

		setup: function () {		
			Dep.prototype.setup.call(this);
			var self = this;
			
			this.buttons.unshift({
				name: 'send',
				text: this.getLanguage().translate('Send'),
				style: 'primary',
				onClick: function (dialog) {
					var editView = this.getView('edit');
					var model = editView.model;
					model.set('status', 'Sending');	
									
					editView.once('after:save', function () {
						this.trigger('after:save', model);
						this.notify('Email has been sent', 'success');
						dialog.close();
					}, this);
					
					editView.once('before:save', function () {
						this.notify('Sending...');
					}, this);					
					
					var $button = dialog.$el.find('button[data-name="send"]');		
					$button.addClass('disabled');
					editView.once('cancel:save', function () {
						$button.removeClass('disabled');	
					}, this);
														
					editView.save();
					
				}.bind(this)
			});
			
			this.header = this.getLanguage().translate('Compose Email');
		},

	});
});


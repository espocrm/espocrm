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

Espo.define('Views.Modal', 'View', function (Dep) {

	return Dep.extend({	
		
		cssName: 'modal-dialog',
		
		header: false,	
		
		dialog: null,
		
		containerSelector: null,
					
		buttons: [
			{
				name: 'cancel',
				label: 'Cancel',
				onClick: function (dialog) {
					dialog.close();
				}
			} 
		],
		
		width: false,
		
		init: function () {
			var id = this.cssName + '-container-' + Math.floor((Math.random() * 10000) + 1).toString();
			var containerSelector = this.containerSelector = '#' + id;
			
			this.options = this.options || {};
			this.options.el = this.containerSelector;
			
			this.on('render', function () {			
				$(containerSelector).remove();
				$('<div />').css('display', 'none').attr('id', id).appendTo('body');
				
				var buttons = _.clone(this.buttons);
				
				for (var i in buttons) {
					if (!('text' in buttons[i]) && ('label' in buttons[i])) {
						buttons[i].text = this.getLanguage().translate(buttons[i].label);
					}
				}
								
				this.dialog = new Espo.Ui.Dialog({
					header: this.header,
					container: containerSelector,
					body: '',
					buttons: buttons,
					width: this.width,
					onRemove: function () {
						this.remove();
					}.bind(this)
				});
				this.setElement(containerSelector + ' .body');							
			}, this);
			
			this.on('after:render', function () {					
				$(containerSelector).show();
				this.dialog.show();
			});
			
			this.once('remove', function () {
				$(containerSelector).remove();
			});
		},
		
		close: function () {
			this.dialog.close();
		},			
	});
});


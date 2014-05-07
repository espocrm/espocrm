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

Espo.define('Views.Notifications.Badge', 'View', function (Dep) {

	return Dep.extend({

		template: 'notifications.badge',
		
		updateFrequency: 10,
		
		timeout: null,
		
		events: {
			'click a[data-action="showNotifications"]': function (e) {
				this.showNotifications();
			},
		},
		
		setup: function () {
			this.once('remove', function () {
				if (this.timeout) {
					clearTimeout(this.timeout);
				}
			}, this);
		},
		
		afterRender: function () {
			this.$badge = this.$el.find('.notifications-button');
			this.$icon = this.$el.find('.notifications-button .icon');	
			this.checkUpdates();
		},
		
		checkUpdates: function () {			
			$.ajax('Notification/action/notReadCount').done(function (count) {
				if (count) {
					this.$icon.addClass('warning');
					this.$badge.attr('title', this.translate('New notifications') + ': ' + count);
				} else {
					this.$icon.removeClass('warning');
					this.$badge.attr('title', '');
				}
			}.bind(this));
		
			this.timeout = setTimeout(function () {
				this.checkUpdates();
			}.bind(this), this.updateFrequency * 1000)
		},
		
		showNotifications: function () {
			this.closeNotifications();
			
			var $container = $('<div>').attr('id', 'notifications-panel').css({
				'position': 'absolute',
				'width': '500px',
				'z-index': 1001,
				'right': 0,
				'left': 'auto'
			});			
						
			$container.appendTo(this.$el.find('.notifications-panel-container'));
			
			this.createView('panel', 'Notifications.Panel', {
				el: '#notifications-panel',				
			}, function (view) {
				view.render();
			}.bind(this));
			
			$document = $(document);			
			$document.on('mouseup.notification', function (e) { 				
 				if (!$container.is(e.target) && $container.has(e.target).length === 0) {
					this.closeNotifications();
       			}
			}.bind(this));
		},
		
		closeNotifications: function () {
			$container = $('#notifications-panel');
			
			$('#notifications-panel').remove();			
			$document = $(document);
			if (this.hasView('panel')) {
				this.getView('panel').remove();
			};
 			$document.off('mouseup.notification');
       		$container.remove();
		},
		
	});
	
});

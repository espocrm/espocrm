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

Espo.define('Views.Modals.ImagePreview', 'Views.Modal', function (Dep) {

	return Dep.extend({

		cssName: 'image-preview',

		header: true,

		template: 'modals.image-preview',
		
		size: 'x-large',

		data: function () {
			return {
				id: this.options.id,
				name: this.options.name,
				size: this.size
			};
		},		

		setup: function () {
			this.buttons = [];			
			this.header = '&nbsp;';
		},
		
		afterRender: function () {
			$container = this.$el.find('.image-container');
			$img = this.$el.find('.image-container img');
			
			var manageSize = function () {
				var width = this.$el.width();
				if (width < 868) {
					$img.attr('width', width);
				} else {
					$img.removeAttr('width');
				}
			}.bind(this);
			
			$(window).on('resize', function () {
				manageSize();
			});
			
			setTimeout(function () {
				manageSize();
			}, 100);
			
		},

	});
});


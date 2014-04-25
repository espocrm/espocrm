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

Espo.define('Views.Fields.Wysiwyg', 'Views.Fields.Text', function (Dep) {

	return Dep.extend({	
	
		type: 'wysiwyg',	
		
		detailTemplate: 'fields.wysiwyg.detail',
		
		editTemplate: 'fields.wysiwyg.edit',
		
		setup: function () {			
			Dep.prototype.setup.call(this);
			
			if (!('summernote' in $)) {
				this.addReadyCondition(function () {
					return ('summernote' in $);
				});
				Espo.loadLib('client/lib/summernote.min.js', function () {
					this.tryReady();
				}.bind(this));
			}
			
			this.listenTo(this.model, 'change:isHtml', function (model) {						
				if (!model.has('isHtml') || model.get('isHtml')) {
					this.enableWysiwygMode();
				} else {
					this.disableWysiwygMode();
				}
			}.bind(this));
			
			this.once('remove', function () {
				$('body > .tooltip').remove();
			});			
		},
		
		afterRender: function () {
			Dep.prototype.afterRender.call(this);	
			
			if (this.mode == 'edit') {
				if (this.model.get('isHtml')) {
					this.enableWysiwygMode();
				}
			}
		},
		
		enableWysiwygMode: function () {
			this.$summernote = this.$element.summernote({
				height: 250,
				onblur: function () {
					this.model.set(this.name, this.$element.code());
				}.bind(this),
				toolbar: [
					['style', ['style']],
					['style', ['bold', 'italic', 'underline', 'clear']],
					['color', ['color']],
					['para', ['ul', 'ol', 'paragraph']],
					['height', ['height']],					
					['table', ['table']],
				]
			});
		},
		
		disableWysiwygMode: function () {
			this.$element.destroy();
		},
		
		fetch: function () {
			var data = {};
			if (this.model.get('isHtml')) {
				data[this.name] = this.$element.code();
			} else {
				data[this.name] = this.$element.val();	
			}
			return data;
		}
	});
});


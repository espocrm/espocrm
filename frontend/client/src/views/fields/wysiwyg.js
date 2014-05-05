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
			
			var language = this.getConfig().get('language');
			
			if (!(language in $.summernote.lang)) {
				$.summernote.lang[language] = this.getLanguage().translate('summernote', 'sets');
			}
			
			if (this.mode == 'edit') {
				if (this.model.get('isHtml')) {
					this.enableWysiwygMode();
				}
			}
			
			if (this.mode == 'detail') {
				var iframe = this.$el.find('iframe').get(0);				
				var document = iframe.contentWindow.document;
				
				document.open('text/html', 'replace');				
				var body = this.model.get('body');
				
				body = '<style>@font-face {' +
					 'font-family: Open Sans;' +
					 'src: url(\'client/fonts/open-sans-regular.eot?\') format(\'eot\'),' +
						  'url(\'client/fonts/open-sans-regular.woff\') format(\'woff\'),' +
						  'url(\'client/fonts/open-sans-regular.ttf\')  format(\'truetype\'),' +
						  'url(\'client/fonts/open-sans-regular.svg#svgFontName\') format(\'svg\');' +
					'}' +
					'body {font-size: 14px;}' +					
					'</style>' + body;
				
				
				document.write(body);				
				document.body.style.fontFamily = 'Open Sans';
				
				document.close();
				iframe.style.height = document.body.scrollHeight + 'px';
			}
		},
		
		enableWysiwygMode: function () {
			this.$summernote = this.$element.summernote({
				height: 250,
				lang: this.getConfig().get('language'),
				onImageUpload: function (files, editor, welEditable) {
					var file = files[0];
					this.notify('Uploading...');					
					this.getModelFactory().create('Attachment', function (attachment) {					
						var fileReader = new FileReader();
						fileReader.onload = function (e) {					
							$.ajax({
								type: 'POST',
								url: 'Attachment/action/upload',
								data: e.target.result,
								contentType: 'multipart/encrypted',
							}).done(function (data) {								
								attachment.id = data.attachmentId;							
								attachment.set('name', file.name);
								attachment.set('type', file.type);
								attachment.set('global', true);
								attachment.set('size', file.size);
								attachment.once('sync', function () {
									var url = '?entryPoint=attachment&id=' + attachment.id;
									editor.insertImage(welEditable, url);
									this.notify(false);
								}, this);						
								attachment.save();
							}.bind(this));
						}.bind(this);
						fileReader.readAsDataURL(file);
					
					}, this);
				}.bind(this),
				toolbar: [
					['style', ['style']],
					['style', ['bold', 'italic', 'underline', 'clear']],
					['color', ['color']],
					['para', ['ul', 'ol', 'paragraph']],
					['height', ['height']],					
					['table', ['table', 'link', 'picture']],
					['misc',['codeview']]
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


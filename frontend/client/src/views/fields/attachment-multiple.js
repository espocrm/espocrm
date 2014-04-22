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

Espo.define('Views.Fields.AttachmentMultiple', 'Views.Fields.Base', function (Dep) {

	return Dep.extend({

		type: 'attachmentMultiple',

		listTemplate: 'fields.attachments-multiple.detail',

		detailTemplate: 'fields.attachments-multiple.detail',

		editTemplate: 'fields.attachments-multiple.edit',

		nameHashName: null,

		idsName: null,

		nameHash: null,

		foreignScope: null,
		
		events: {
			'click a.remove-attachment': function (e) {
				var $div = $(e.currentTarget).parent();
				var id = $div.attr('data-id');
				if (id) {
					this.deleteAttachment(id);
				}
				$div.parent().remove();
			},			
			'change input.file': function (e) {
				var $file = $(e.currentTarget);
				var files = e.currentTarget.files;
				this.uploadFiles(files);

				$file.replaceWith($file.clone(true));
			},
		},

		data: function () {
			var ids = this.model.get(this.idsName);

			return _.extend({
				idValues: this.model.get(this.idsName),
				idValuesString: ids ? ids.join(',') : '',
				nameHash: this.model.get(this.nameHashName),
				foreignScope: this.foreignScope,
			}, Dep.prototype.data.call(this));
		},

		setup: function () {
			this.nameHashName = this.name + 'Names';
			this.idsName = this.name + 'Ids';
			this.foreignScope = 'Attachment';

			var self = this;
			
			this.nameHash = _.clone(this.model.get(this.nameHashName)) || {};
			
			this.listenTo(this.model, 'change:' + this.nameHashName, function () {
				this.nameHash = _.clone(this.model.get(this.nameHashName)) || {};
			}.bind(this));			
			
			if (!this.model.get(this.idsName)) {
				this.clearIds();
			}
		},
		
		empty: function () {
			this.clearIds();
			this.$attachments.empty();
		},
		
		deleteAttachment: function (id) {
			this.removeId(id);	
			if (this.model.isNew()) {		
				this.getModelFactory().create('Attachment', function (attachment) {
					attachment.id = id;
					attachment.destroy();
				});
			}
		},
		
		removeId: function (id) {
			var arr = _.clone(this.model.get(this.idsName));
			var i = arr.indexOf(id);
			arr.splice(i, 1);
			this.model.set(this.idsName, arr);
			
			var hash = _.clone(this.model.get(this.nameHashName) || {});
			delete hash[id];
			this.model.set(this.nameHashName, hash);
		},
		
		clearIds: function () {
			this.model.set(this.idsName, []);
			this.model.set(this.nameHashName, {})
		},
		
		pushAttachment: function (attachment) {
			var arr = _.clone(this.model.get(this.idsName));
			
			arr.push(attachment.id);
			this.model.set(this.idsName, arr)
			
			var hash = _.clone(this.model.get(this.nameHashName) || {});
			hash[attachment.id] = attachment.get('name');
			this.model.set(this.nameHashName, hash);
		},
		
		addAttachmentBox: function (name, id) {
			$attachments = this.$attachments;
			var self = this;
				
			var removeLink = '<a href="javascript:" class="remove-attachment pull-right"><span class="glyphicon glyphicon-remove"></span></a>';

			var $att = $('<div>').css('display', 'inline-block')
			                     .css('width', '300px')
			                     .addClass('gray-box')
			                     .append($('<span>' + name + '</span>'))
			                     .append(removeLink);
				
			var $container = $('<div>').append($att);					
			$attachments.append($container);			
				
			if (!id) {
				var $loading = $('<span class="small">' + this.translate('Uploading...') + '</span>');				
				$container.append($loading);
				$att.on('ready', function () {
					$loading.html(self.translate('Ready'));
				});
			} else {
				$att.attr('data-id', id);
			}
			
			return $att;
		},
		
		uploadFiles: function (files) {			
			var uploadedCount = 0;
			var totalCount = 0;
			
			this.getModelFactory().create('Attachment', function (model) {
				var canceledList = [];
				
				var fileList = [];
				for (var i = 0; i < files.length; i++) {
					fileList.push(files[i]);
				}
				
				fileList.forEach(function (file) {
					
					var $att = this.addAttachmentBox(file.name);					
					
					$att.find('.remove-attachment').on('click.uploading', function () {
						canceledList.push(attachment.cid);
						totalCount--;
					});
					
					var attachment = model.clone();

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
							attachment.set('type', file.type || 'text/plain');
							attachment.set('size', file.size);
							attachment.once('sync', function () {
								if (canceledList.indexOf(attachment.cid) === -1) {
									$att.trigger('ready');							
									this.pushAttachment(attachment);
									$att.attr('data-id', attachment.id);
									uploadedCount++;			
									if (uploadedCount == totalCount) {								
										afterAttachmentsUploaded.call(this);
									}
								}
							}, this);						
							attachment.save();
						}.bind(this));
					}.bind(this);
					fileReader.readAsDataURL(file);
				}, this);
			}.bind(this));
		},
		
		afterAttachmentsUploaded: function () {
		
		},
		
		afterRender: function () {			
			if (this.mode == 'edit') {			
				this.$attachments = this.$el.find('div.attachments');
				
				var ids = this.model.get(this.idsName) || [];
				
				var hash = this.model.get(this.nameHashName);
				ids.forEach(function (id) {					
					if (hash) {
						var name = hash[id];
						this.addAttachmentBox(name, id);
					}
				}, this);			
			}
			
		},

		getValueForDisplay: function () {
			var nameHash = this.nameHash;
			var string = '';
			var names = [];
			for (var id in nameHash) {
				var line = '<span class="glyphicon glyphicon-paperclip small"></span> <a href="?entryPoint=download&id=' + id + '">' + nameHash[id] + '</a>';
				names.push(line);
			}
			return names.join(', ');
		},

		validateRequired: function () {
			if (this.model.isRequired(this.name)) {
				if (this.model.get(this.idsName).length == 0) {
					var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
					this.showValidationMessage(msg);
					return true;
				}
			}
		},

		fetch: function () {
			return {};
		},
	});
});


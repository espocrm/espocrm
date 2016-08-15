/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('views/fields/file', 'views/fields/link', function (Dep) {

    return Dep.extend({

        type: 'file',

        listTemplate: 'fields/file/detail',

        detailTemplate: 'fields/file/detail',

        editTemplate: 'fields/file/edit',

        showPreview: false,

        accept: false,

        previewTypeList: [
            'image/jpeg',
            'image/png',
            'image/gif',
        ],

        defaultType: false,

        previewSize: 'small',

        events: {
            'click a.remove-attachment': function (e) {
                var $div = $(e.currentTarget).parent();
                this.deleteAttachment();
                $div.parent().remove();
            },
            'change input.file': function (e) {
                var $file = $(e.currentTarget);
                var files = e.currentTarget.files;
                if (files.length) {
                    this.uploadFile(files[0]);
                    $file.replaceWith($file.clone(true));
                }
            },
            'click a[data-action="showImagePreview"]': function (e) {
                var id = $(e.currentTarget).data('id');
                this.createView('preview', 'views/modals/image-preview', {
                    id: id,
                    model: this.model,
                    name: this.nameHash[id]
                }, function (view) {
                    view.render();
                });
            },
            'click a[data-action="showImagePreview"]': function (e) {
                e.preventDefault();

                var id = this.model.get(this.idName);
                this.createView('preview', 'views/modals/image-preview', {
                    id: id,
                    model: this.model,
                    name: this.model.get(this.nameName)
                }, function (view) {
                    view.render();
                });
            },
        },

        data: function () {
            return _.extend({
                id: this.model.get(this.idName),
                acceptAttribue: this.acceptAttribue
            }, Dep.prototype.data.call(this));
        },

        validateRequired: function () {
            if (this.isRequired()) {
                if (this.model.get(this.idName) == null) {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                    this.showValidationMessage(msg, '.attachment-button label');
                    return true;
                }
            }
        },

        setup: function () {
            this.nameName = this.name + 'Name';
            this.idName = this.name + 'Id';
            this.typeName = this.name + 'Type';
            this.foreignScope = 'Attachment';

            if ('showPreview' in this.params) {
                this.showPreview = this.params.showPreview;
            }

            if ('accept' in this.params) {
                this.accept = this.params.accept;
            }

            if (this.accept) {
                this.acceptAttribue = this.accept.join('|');
            }

        },

        afterRender: function () {
            if (this.mode == 'edit') {
                this.$attachment = this.$el.find('div.attachment');

                var name = this.model.get(this.nameName);
                var type = this.model.get(this.typeName) || this.defaultType;
                var id = this.model.get(this.idName);
                if (id) {
                    this.addAttachmentBox(name, type, id);
                }

                this.$el.off('drop');
                this.$el.off('dragover');
                this.$el.off('dragleave');

                this.$el.on('drop', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var e = e.originalEvent;
                    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                        this.uploadFile(e.dataTransfer.files[0]);
                    }
                }.bind(this));

                this.$el.on('dragover', function (e) {
                    e.preventDefault();
                }.bind(this));
                this.$el.on('dragleave', function (e) {
                    e.preventDefault();
                }.bind(this));
            }
        },

        getDetailPreview: function (name, type, id) {
            var preview = name;

            switch (type) {
                case 'image/png':
                case 'image/jpeg':
                case 'image/gif':
                    preview = '<a data-action="showImagePreview" data-id="' + id + '" href="' + this.getImageUrl(id) + '"><img src="'+this.getBasePath()+'?entryPoint=image&size='+this.previewSize+'&id=' + id + '"></a>'; 
            }
            return preview;
        },

        getEditPreview: function (name, type, id) {
            var preview = name;

            switch (type) {
                case 'image/png':
                case 'image/jpeg':
                case 'image/gif':
                    preview = '<img src="' + this.getImageUrl(id, 'small') + '" title="' + name + '">';
            }

            return preview;
        },

        getValueForDisplay: function () {
            if (this.mode == 'detail' || this.mode == 'list') {
                var name = this.model.get(this.nameName);
                var type = this.model.get(this.typeName) || this.defaultType;
                var id = this.model.get(this.idName);

                if (!id) {
                    return false;
                }

                var string = '';

                if (this.showPreview && ~this.previewTypeList.indexOf(type)) {
                    string = '<div class="attachment-preview">' + this.getDetailPreview(name, type, id) + '</div>';
                } else {
                    string = '<span class="glyphicon glyphicon-paperclip small"></span> <a href="'+ this.getDownloadUrl(id) +'" target="_BLANK">' + name + '</a>';
                }
                return string;
            }
        },

        getImageUrl: function (id, size) {
            var url = this.getBasePath() + '?entryPoint=image&id=' + id;
            if (size) {
                url += '&size=' + size;
            }
            if (this.getUser().get('portalId')) {
                url += '&portalId=' + this.getUser().get('portalId');
            }
            return url;
        },

        getDownloadUrl: function (id) {
            var url = this.getBasePath() + '?entryPoint=download&id=' + id;
            if (this.getUser().get('portalId')) {
                url += '&portalId=' + this.getUser().get('portalId');
            }
            return url;
        },

        deleteAttachment: function () {
            var id = this.model.get(this.idName);
            var o = {};
            o[this.idName] = null;
            o[this.nameName] = null;
            this.model.set(o);

            this.$attachment.empty();

            if (id) {
                if (this.model.isNew()) {
                    this.getModelFactory().create('Attachment', function (attachment) {
                        attachment.id = id;
                        attachment.destroy();
                    });
                }
            }
        },

        setAttachment: function (attachment) {
            var arr = _.clone(this.model.get(this.idsName));
            var o = {};
            o[this.idName] = attachment.id;
            o[this.nameName] = attachment.get('name');
            this.model.set(o);
        },

        uploadFile: function (file) {
            var isCanceled = false;

            this.getModelFactory().create('Attachment', function (attachment) {
                var $att = this.addAttachmentBox(file.name, file.type);

                this.$el.find('.attachment-button').addClass('hidden');

                $att.find('.remove-attachment').on('click.uploading', function () {
                    isCanceled = true;
                    this.$el.find('.attachment-button').removeClass('hidden');
                }.bind(this));

                var fileReader = new FileReader();
                fileReader.onload = function (e) {
                    this.handleFileUpload(file, e.target.result, function (result, fileParams) {
                        $.ajax({
                            type: 'POST',
                            url: 'Attachment/action/upload',
                            data: result,
                            contentType: 'multipart/encrypted',
                            timeout: 0,
                        }).done(function (data) {
                            attachment.id = data.attachmentId;
                            attachment.set('name', fileParams.name);
                            attachment.set('type', fileParams.type || 'text/plain');
                            attachment.set('size', fileParams.size);
                            attachment.set('role', 'Attachment');

                            attachment.once('sync', function () {
                                if (!isCanceled) {
                                    $att.trigger('ready');
                                    this.setAttachment(attachment);
                                }
                            }, this);
                            attachment.save();
                        }.bind(this));
                    }.bind(this));
                }.bind(this);
                fileReader.readAsDataURL(file);
            }, this);
        },

        handleFileUpload: function (file, contents, callback) {
            var params = {
                name: file.name,
                type: file.type,
                size: file.size
            };
            callback(contents, params);
        },

        addAttachmentBox: function (name, type, id) {
            this.$attachment.empty();

            var self = this;

            var removeLink = '<a href="javascript:" class="remove-attachment pull-right"><span class="glyphicon glyphicon-remove"></span></a>';

            var preview = name;
            if (this.showPreview && id) {
                preview = this.getEditPreview(name, type, id);
            }

            var $att = $('<div>').css('display', 'inline-block')
                                 .css('width', '100%')
                                 .css('max-width', '300px')
                                 .addClass('gray-box')
                                 .append($('<span class="preview">' + preview + '</span>').css('width', 'cacl(100% - 30px)'))
                                 .append(removeLink);

            var $container = $('<div>').append($att);
            this.$attachment.append($container);

            if (!id) {
                var $loading = $('<span class="small">' + this.translate('Uploading...') + '</span>');
                $container.append($loading);
                $att.on('ready', function () {
                    $loading.html(self.translate('Ready'));
                });
            }

            return $att;
        },

        fetch: function () {
            return {};
        }

    });
});


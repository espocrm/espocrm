/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
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

        listTemplate: 'fields/file/list',

        detailTemplate: 'fields/file/detail',

        editTemplate: 'fields/file/edit',

        showPreview: false,

        accept: false,

        previewTypeList: [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ],

        defaultType: false,

        previewSize: 'small',

        validations: ['ready', 'required'],

        searchTypeList: ['isNotEmpty', 'isEmpty'],

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
            'click a.action[data-action="insertFromSource"]': function (e) {
                var name = $(e.currentTarget).data('name');
                this.insertFromSource(name);
            }
        },

        data: function () {
            var data =_.extend({
                id: this.model.get(this.idName),
                acceptAttribue: this.acceptAttribue
            }, Dep.prototype.data.call(this));

            if (this.mode == 'edit') {
                data.sourceList = this.sourceList;
            }

            data.valueIsSet = this.model.has(this.idName);

            return data;
        },

        showValidationMessage: function (msg, selector) {
            var $label = this.$el.find('label');
            var title = $label.attr('title');
            $label.attr('title', '');
            Dep.prototype.showValidationMessage.call(this, msg, selector);
            $label.attr('title', title);
        },

        validateRequired: function () {
            if (this.isRequired()) {
                if (this.model.get(this.idName) == null) {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.getLabelText());
                    var $target;
                    if (this.isUploading) {
                        $target = this.$el.find('.gray-box');
                    } else {
                        $target = this.$el.find('.attachment-button label');
                    }

                    this.showValidationMessage(msg, $target);
                    return true;
                }
            }
        },

        validateReady: function () {
            if (this.isUploading) {
                var $target = this.$el.find('.gray-box');
                var msg = this.translate('fieldIsUploading', 'messages').replace('{field}', this.getLabelText());
                this.showValidationMessage(msg, $target);
                return true;
            }
        },

        setup: function () {
            this.nameName = this.name + 'Name';
            this.idName = this.name + 'Id';
            this.typeName = this.name + 'Type';
            this.foreignScope = 'Attachment';

            this.previewSize = this.options.previewSize || this.params.previewSize || this.previewSize;

            var sourceDefs = this.getMetadata().get(['clientDefs', 'Attachment', 'sourceDefs']) || {};

            this.sourceList = Espo.Utils.clone(this.params.sourceList || []).filter(function (item) {
                if (!(item in sourceDefs)) return true;
                var defs = sourceDefs[item];
                if (defs.configCheck) {
                    var configCheck = defs.configCheck;
                    if (configCheck) {
                        var arr = configCheck.split('.');
                        if (this.getConfig().getByPath(arr)) {
                            return true;
                        }
                    }
                }
            }, this);

            if ('showPreview' in this.params) {
                this.showPreview = this.params.showPreview;
            }

            if ('accept' in this.params) {
                this.accept = this.params.accept;
            }

            if (this.accept) {
                this.acceptAttribue = this.accept.join('|');
            }

            this.once('remove', function () {
                if (this.resizeIsBeingListened) {
                    $(window).off('resize.' + this.cid);
                }
            }.bind(this));
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

            if (this.mode == 'search') {
                var type = this.$el.find('select.search-type').val();
                this.handleSearchType(type);
            }

            if (this.mode === 'detail') {
                if (this.previewSize === 'large') {
                    this.handleResize();
                    this.resizeIsBeingListened = true;
                    $(window).on('resize.' + this.cid, function () {
                        this.handleResize();
                    }.bind(this));
                }
            }
        },

        handleResize: function () {
            var width = this.$el.width();
            this.$el.find('img.image-preview').css('maxWidth', width + 'px');
        },

        getDetailPreview: function (name, type, id) {
            name = Handlebars.Utils.escapeExpression(name);
            var preview = name;

            if (~this.previewTypeList.indexOf(type)) {
                preview = '<a data-action="showImagePreview" data-id="' + id + '" href="' + this.getImageUrl(id) + '"><img src="'+this.getBasePath()+'?entryPoint=image&size='+this.previewSize+'&id=' + id + '" class="image-preview"></a>';
            }
            return preview;
        },

        getEditPreview: function (name, type, id) {
            name = Handlebars.Utils.escapeExpression(name);
            var preview = name;

            if (~this.previewTypeList.indexOf(type)) {
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
                    string = '<span class="fas fa-paperclip text-soft small"></span> <a href="'+ this.getDownloadUrl(id) +'" target="_BLANK">' + Handlebars.Utils.escapeExpression(name) + '</a>';
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

            var exceedsMaxFileSize = false;

            var maxFileSize = this.params.maxFileSize || 0;
            var appMaxUploadSize = this.getHelper().getAppParam('maxUploadSize') || 0;
            if (!maxFileSize || maxFileSize > appMaxUploadSize) {
                maxFileSize = appMaxUploadSize;
            }

            if (maxFileSize) {
                if (file.size > maxFileSize * 1024 * 1024) {
                    exceedsMaxFileSize = true;
                }
            }
            if (exceedsMaxFileSize) {
                var msg = this.translate('fieldMaxFileSizeError', 'messages')
                          .replace('{field}', this.getLabelText())
                          .replace('{max}', maxFileSize);
                this.showValidationMessage(msg, '.attachment-button label');
                return;
            }

            this.isUploading = true;

            this.getModelFactory().create('Attachment', function (attachment) {
                var $attachmentBox = this.addAttachmentBox(file.name, file.type);

                this.$el.find('.attachment-button').addClass('hidden');

                $attachmentBox.find('.remove-attachment').on('click.uploading', function () {
                    isCanceled = true;
                    this.$el.find('.attachment-button').removeClass('hidden');
                    this.isUploading = false;
                }.bind(this));

                var fileReader = new FileReader();
                fileReader.onload = function (e) {
                    this.handleFileUpload(file, e.target.result, function (result, fileParams) {
                        attachment.set('name', fileParams.name);
                        attachment.set('type', fileParams.type || 'text/plain');
                        attachment.set('size', fileParams.size);
                        attachment.set('role', 'Attachment');
                        attachment.set('relatedType', this.model.name);
                        attachment.set('file', result);
                        attachment.set('field', this.name);

                        attachment.save({}, {timeout: 0}).then(function () {
                            this.isUploading = false;
                            if (!isCanceled) {
                                $attachmentBox.trigger('ready');
                                this.setAttachment(attachment);
                            }
                        }.bind(this)).fail(function () {
                            $attachmentBox.remove();
                            this.$el.find('.uploading-message').remove();
                            this.$el.find('.attachment-button').removeClass('hidden');
                            this.isUploading = false;
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

            var removeLink = '<a href="javascript:" class="remove-attachment pull-right"><span class="fas fa-times"></span></a>';

            var preview = name;
            if (this.showPreview && id) {
                preview = this.getEditPreview(name, type, id);
            } else {
                preview = Handlebars.Utils.escapeExpression(preview);
            }

            var $att = $('<div>').append(removeLink)
                                 .append($('<span class="preview">' + preview + '</span>').css('width', 'cacl(100% - 30px)'))
                                 .addClass('gray-box');

            var $container = $('<div>').append($att);
            this.$attachment.append($container);

            if (!id) {
                var $loading = $('<span class="small uploading-message">' + this.translate('Uploading...') + '</span>');
                $container.append($loading);
                $att.on('ready', function () {
                    $loading.html(self.translate('Ready'));
                });
            }

            return $att;
        },

        insertFromSource: function (source) {
            var viewName =
                this.getMetadata().get(['clientDefs', 'Attachment', 'sourceDefs', source, 'insertModalView']) ||
                this.getMetadata().get(['clientDefs', source, 'modalViews', 'select']) ||
                'views/modals/select-records';

            if (viewName) {
                this.notify('Loading...');

                var filters = null;
                if (('getSelectFilters' + source) in this) {
                    filters = this['getSelectFilters' + source]();

                    if (this.model.get('parentId') && this.model.get('parentType') === 'Account') {
                        if (this.getMetadata().get(['entityDefs', source, 'fields', 'account', 'type']) === 'link') {
                            filters = {
                                account: {
                                    type: 'equals',
                                    field: 'accountId',
                                    value: this.model.get('parentId'),
                                    valueName: this.model.get('parentName')
                                }
                            };
                        }
                    }
                }
                var boolFilterList = this.getMetadata().get(['clientDefs', 'Attachment', 'sourceDefs', source, 'boolFilterList']);
                if (('getSelectBoolFilterList' + source) in this) {
                    boolFilterList = this['getSelectBoolFilterList' + source]();
                }
                var primaryFilterName = this.getMetadata().get(['clientDefs', 'Attachment', 'sourceDefs', source, 'primaryFilter']);
                if (('getSelectPrimaryFilterName' + source) in this) {
                    primaryFilterName = this['getSelectPrimaryFilterName' + source]();
                }
                this.createView('insertFromSource', viewName, {
                    scope: source,
                    createButton: false,
                    filters: filters,
                    boolFilterList: boolFilterList,
                    primaryFilterName: primaryFilterName,
                    multiple: false
                }, function (view) {
                    view.render();
                    this.notify(false);
                    this.listenToOnce(view, 'select', function (modelList) {
                        if (Object.prototype.toString.call(modelList) !== '[object Array]') {
                            modelList = [modelList];
                        }
                        modelList.forEach(function (model) {
                            if (model.name === 'Attachment') {
                                this.setAttachment(model);
                            } else {
                                this.ajaxPostRequest(source + '/action/getAttachmentList', {
                                    id: model.id
                                }).done(function (attachmentList) {
                                    attachmentList.forEach(function (item) {
                                        this.getModelFactory().create('Attachment', function (attachment) {
                                            attachment.set(item);
                                            this.setAttachment(attachment, true);
                                        }, this);
                                    }, this);
                                }.bind(this));
                            }
                        }, this);
                    });
                }, this);
                return;
            }
        },

        fetch: function () {
            var data = {};
            data[this.idName] = this.model.get(this.idName);
            return data;
        }

    });
});

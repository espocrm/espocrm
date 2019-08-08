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

Espo.define('views/fields/attachment-multiple', 'views/fields/base', function (Dep) {

    return Dep.extend({

        type: 'attachmentMultiple',

        listTemplate: 'fields/attachments-multiple/list',

        detailTemplate: 'fields/attachments-multiple/detail',

        editTemplate: 'fields/attachments-multiple/edit',

        searchTemplate: 'fields/link-multiple/search',

        previewSize: 'medium',

        nameHashName: null,

        idsName: null,

        nameHash: null,

        foreignScope: null,

        showPreviews: true,

        previewTypeList: [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ],

        validations: ['ready', 'required'],

        searchTypeList: ['isNotEmpty', 'isEmpty'],

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
            'click a.action[data-action="insertFromSource"]': function (e) {
                var name = $(e.currentTarget).data('name');
                this.insertFromSource(name);
            },
            'click a[data-action="showImagePreview"]': function (e) {
                e.preventDefault();

                var id = $(e.currentTarget).data('id');
                var attachmentIdList = this.model.get(this.idsName) || [];

                var typeHash = this.model.get(this.typeHashName) || {};

                var imageIdListRight = [];
                var imageIdListLeft = [];

                imageIdListLeft.push(id);

                var met = false;
                attachmentIdList.forEach(function (cId) {
                    if (cId === id) {
                        met = true;
                        return;
                    }
                    if (!this.isTypeIsImage(typeHash[cId])) {
                        return;
                    }
                    if (met) {
                        imageIdListLeft.push(cId);
                    } else {
                        imageIdListRight.push(cId);
                    }
                }, this);

                var imageIdList = imageIdListLeft.concat(imageIdListRight);

                var imageList = [];
                imageIdList.forEach(function (cId) {
                    imageList.push({
                        id: cId,
                        name: this.nameHash[cId]
                    });
                }, this);

                this.createView('preview', 'views/modals/image-preview', {
                    id: id,
                    model: this.model,
                    name: this.nameHash[id],
                    imageList: imageList
                }, function (view) {
                    view.render();
                });
            },
        },

        data: function () {
            var ids = this.model.get(this.idsName);

            var data = _.extend({
                idValues: this.model.get(this.idsName),
                idValuesString: ids ? ids.join(',') : '',
                nameHash: this.model.get(this.nameHashName),
                foreignScope: this.foreignScope,
                valueIsSet: this.model.has(this.idsName)
            }, Dep.prototype.data.call(this));

            if (this.mode == 'edit') {
                data.fileSystem = ~this.sourceList.indexOf('FileSystem');
                data.sourceList = this.sourceList;
            }

            return data;
        },

        setup: function () {
            this.nameHashName = this.name + 'Names';
            this.typeHashName = this.name + 'Types';
            this.idsName = this.name + 'Ids';
            this.foreignScope = 'Attachment';

            this.previewSize = this.options.previewSize || this.params.previewSize || this.previewSize;

            var self = this;

            this.nameHash = _.clone(this.model.get(this.nameHashName)) || {};

            if ('showPreviews' in this.params) {
                this.showPreviews = this.params.showPreviews;
            }

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

            this.listenTo(this.model, 'change:' + this.nameHashName, function () {
                this.nameHash = _.clone(this.model.get(this.nameHashName)) || {};
            }.bind(this));

            this.once('remove', function () {
                if (this.resizeIsBeingListened) {
                    $(window).off('resize.' + this.cid);
                }
            }.bind(this));
        },

        setupSearch: function () {
            this.events = _.extend({
                'change select.search-type': function (e) {
                    var type = $(e.currentTarget).val();
                    this.handleSearchType(type);
                },
            }, this.events || {});
        },

        empty: function () {
            this.clearIds();
            this.$attachments.empty();
        },

        handleResize: function () {
            var width = this.$el.width();
            this.$el.find('img.image-preview').css('maxWidth', width + 'px');
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

        removeId: function (id) {
            var arr = _.clone(this.model.get(this.idsName) || []);
            var i = arr.indexOf(id);
            arr.splice(i, 1);
            this.model.set(this.idsName, arr);

            var nameHash = _.clone(this.model.get(this.nameHashName) || {});
            delete nameHash[id];
            this.model.set(this.nameHashName, nameHash);

            var typeHash = _.clone(this.model.get(this.typeHashName) || {});
            delete typeHash[id];
            this.model.set(this.typeHashName, typeHash);
        },

        clearIds: function (silent) {
            var silent = silent || false;
            this.model.set(this.idsName, [], {silent: silent});
            this.model.set(this.nameHashName, {}, {silent: silent});
            this.model.set(this.typeHashName, {}, {silent: silent})
        },

        pushAttachment: function (attachment, link) {
            var arr = _.clone(this.model.get(this.idsName) || []);

            arr.push(attachment.id);
            this.model.set(this.idsName, arr);

            var typeHash = _.clone(this.model.get(this.typeHashName) || {});
            typeHash[attachment.id] = attachment.get('type');
            this.model.set(this.typeHashName, typeHash);

            var nameHash = _.clone(this.model.get(this.nameHashName) || {});
            nameHash[attachment.id] = attachment.get('name');
            this.model.set(this.nameHashName, nameHash);
        },

        getEditPreview: function (name, type, id) {
            name = Handlebars.Utils.escapeExpression(name);

            var preview = name;

            if (~this.previewTypeList.indexOf(type)) {
                preview = '<img src="' + this.getImageUrl(id, 'small') + '" title="' + name + '">';
            }

            return preview;
        },

        addAttachmentBox: function (name, type, id, link) {
            var $attachments = this.$attachments;

            var removeLink = '<a href="javascript:" class="remove-attachment pull-right"><span class="fas fa-times"></span></a>';

            var preview = name;
            if (this.showPreviews && id) {
                preview = this.getEditPreview(name, type, id);
            } else {
                preview = Handlebars.Utils.escapeExpression(preview);
            }

            if (link && preview === name) {
                preview = '<a href="'+this.getBasePath()+'?entryPoint=download&id='+id+'" target="_BLANK">' + preview + '</a>';
            }

            var $att = $('<div>').addClass('gray-box')
                                 .append(removeLink)
                                 .append($('<span class="preview">' + preview + '</span>').css('width', 'cacl(100% - 30px)'));

            var $container = $('<div>').append($att);
            $attachments.append($container);

            if (!id) {
                var $loading = $('<span class="small uploading-message">' + this.translate('Uploading...') + '</span>');
                $container.append($loading);
                $att.on('ready', function () {
                    $loading.html(this.translate('Ready'));
                }.bind(this));
            } else {
                $att.attr('data-id', id);
            }

            return $att;
        },

        showValidationMessage: function (msg, selector) {
            var $label = this.$el.find('label');
            var title = $label.attr('title');
            $label.attr('title', '');
            Dep.prototype.showValidationMessage.call(this, msg, selector);
            $label.attr('title', title);
        },

        uploadFiles: function (files) {
            var uploadedCount = 0;
            var totalCount = 0;

            var exceedsMaxFileSize = false;

            var maxFileSize = this.params.maxFileSize || 0;
            var appMaxUploadSize = this.getHelper().getAppParam('maxUploadSize') || 0;
            if (!maxFileSize || maxFileSize > appMaxUploadSize) {
                maxFileSize = appMaxUploadSize;
            }

            if (maxFileSize) {
                for (var i = 0; i < files.length; i++) {
                    var file = files[i];
                    if (file.size > maxFileSize * 1024 * 1024) {
                        exceedsMaxFileSize = true;
                    }
                }
            }
            if (exceedsMaxFileSize) {
                var msg = this.translate('fieldMaxFileSizeError', 'messages')
                          .replace('{field}', this.getLabelText())
                          .replace('{max}', maxFileSize);

                this.showValidationMessage(msg, 'label');
                return;
            }

            this.isUploading = true;

            this.getModelFactory().create('Attachment', function (model) {
                var canceledList = [];

                var fileList = [];
                for (var i = 0; i < files.length; i++) {
                    fileList.push(files[i]);
                    totalCount++;
                }

                fileList.forEach(function (file) {
                    var $attachmentBox = this.addAttachmentBox(file.name, file.type);

                    $attachmentBox.find('.remove-attachment').on('click.uploading', function () {
                        canceledList.push(attachment.cid);
                        totalCount--;
                        if (uploadedCount == totalCount) {
                            this.isUploading = false;
                            if (totalCount) {
                                this.afterAttachmentsUploaded.call(this);
                            }
                        }
                    }.bind(this));

                    var attachment = model.clone();

                    var fileReader = new FileReader();
                    fileReader.onload = function (e) {
                        attachment.set('name', file.name);
                        attachment.set('type', file.type || 'text/plain');
                        attachment.set('role', 'Attachment');
                        attachment.set('size', file.size);
                        attachment.set('parentType', this.model.name);
                        attachment.set('file', e.target.result);
                        attachment.set('field', this.name);

                        attachment.save({}, {timeout: 0}).then(function () {
                            if (canceledList.indexOf(attachment.cid) === -1) {
                                $attachmentBox.trigger('ready');
                                this.pushAttachment(attachment);
                                $attachmentBox.attr('data-id', attachment.id);
                                uploadedCount++;
                                if (uploadedCount == totalCount && this.isUploading) {
                                    this.isUploading = false;
                                    this.afterAttachmentsUploaded.call(this);
                                }
                            }
                        }.bind(this)).fail(function () {
                            $attachmentBox.remove();
                            totalCount--;
                            if (!totalCount) {
                                this.isUploading = false;
                                this.$el.find('.uploading-message').remove();
                            }
                            if (uploadedCount == totalCount && this.isUploading) {
                                this.isUploading = false;
                                this.afterAttachmentsUploaded.call(this);
                            }
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

                var hameHash = this.model.get(this.nameHashName);
                var typeHash = this.model.get(this.typeHashName) || {};

                ids.forEach(function (id) {
                    if (hameHash) {
                        var name = hameHash[id];
                        var type = typeHash[id] || null;
                        this.addAttachmentBox(name, type, id);
                    }
                }, this);

                this.$el.off('drop');
                this.$el.off('dragover');
                this.$el.off('dragleave');

                this.$el.on('drop', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var e = e.originalEvent;
                    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                        this.uploadFiles(e.dataTransfer.files);
                    }
                }.bind(this));

                this.$el.get(0).addEventListener('dragover', function (e) {
                    e.preventDefault();
                }.bind(this));
                this.$el.get(0).addEventListener('dragleave', function (e) {
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

        isTypeIsImage: function (type) {
            if (~this.previewTypeList.indexOf(type)) {
                return true;
            }
            return false
        },

        getDetailPreview: function (name, type, id) {
            name = Handlebars.Utils.escapeExpression(name);

            var preview = name;
            if (this.isTypeIsImage(type)) {
                preview = '<a data-action="showImagePreview" data-id="' + id + '" href="' + this.getImageUrl(id) + '"><img src="'+this.getImageUrl(id, this.previewSize)+'" class="image-preview"></a>';
            }
            return preview;
        },

        getValueForDisplay: function () {
            if (this.mode == 'detail' || this.mode == 'list') {
                var nameHash = this.nameHash;
                var typeHash = this.model.get(this.typeHashName) || {};

                var previews = [];
                var names = [];
                for (var id in nameHash) {
                    var type = typeHash[id] || false;
                    var name = nameHash[id];
                    if (
                        this.showPreviews
                        &&
                        ~this.previewTypeList.indexOf(type)
                        &&
                        (this.mode === 'detail' || this.mode === 'list' && this.showPreviewsInListMode)
                    ) {
                        previews.push('<div class="attachment-preview">' + this.getDetailPreview(name, type, id) + '</div>');
                        continue;
                    }
                    var line = '<div class="attachment-block"><span class="fas fa-paperclip text-soft small"></span> <a href="' + this.getDownloadUrl(id) + '" target="_BLANK">' + Handlebars.Utils.escapeExpression(name) + '</a></div>';
                    names.push(line);
                }
                var string = previews.join('') + names.join('');

                return string;
            }
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
                    multiple: true
                }, function (view) {
                    view.render();
                    this.notify(false);
                    this.listenToOnce(view, 'select', function (modelList) {
                        if (Object.prototype.toString.call(modelList) !== '[object Array]') {
                            modelList = [modelList];
                        }
                        modelList.forEach(function (model) {
                            if (model.name === 'Attachment') {
                                this.pushAttachment(model);
                            } else {
                                this.ajaxPostRequest(source + '/action/getAttachmentList', {
                                    id: model.id
                                }).done(function (attachmentList) {
                                    attachmentList.forEach(function (item) {
                                        this.getModelFactory().create('Attachment', function (attachment) {
                                            attachment.set(item);
                                            this.pushAttachment(attachment, true);
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

        validateRequired: function () {
            if (this.isRequired()) {
                if ((this.model.get(this.idsName) || []).length == 0) {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.getLabelText());
                    this.showValidationMessage(msg, 'label');
                    return true;
                }
            }
        },

        validateReady: function () {
            if (this.isUploading) {
                var msg = this.translate('fieldIsUploading', 'messages').replace('{field}', this.getLabelText());
                this.showValidationMessage(msg, 'label');
                return true;
            }
        },

        fetch: function () {
            var data = {};
            data[this.idsName] = this.model.get(this.idsName) || [];
            return data;
        },

        handleSearchType: function (type) {
            this.$el.find('div.link-group-container').addClass('hidden');
        },

        fetchSearch: function () {
            var type = this.$el.find('select.search-type').val();

            if (type === 'isEmpty') {
                var data = {
                    type: 'isNotLinked',
                    data: {
                        type: type
                    }
                };
                return data;
            } else if (type === 'isNotEmpty') {
                var data = {
                    type: 'isLinked',
                    data: {
                        type: type
                    }
                };
                return data;
            }
        }

    });
});

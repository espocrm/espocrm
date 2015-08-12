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
 ************************************************************************/

Espo.define('Views.Fields.Wysiwyg', ['Views.Fields.Text', 'lib!Summernote'], function (Dep, Summernote) {

    return Dep.extend({

        detailTemplate: 'fields.wysiwyg.detail',

        editTemplate: 'fields.wysiwyg.edit',

        height: 250,

        rowsDefault: 10,

        setup: function () {
            Dep.prototype.setup.call(this);


            this.height = this.params.height || this.height;
            this.toolbar = this.params.toolbar || [
                ['style', ['style']],
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['height', ['height']],
                ['table', ['table', 'link', 'picture', 'hr']],
                ['misc',['codeview']]
            ];

            this.listenTo(this.model, 'change:isHtml', function (model) {
                if (this.mode == 'edit') {
                    if (!model.has('isHtml') || model.get('isHtml')) {
    		            var value = this.plainToHtml(this.model.get(this.name));
    		            this.model.set(this.name, value);
                        this.enableWysiwygMode();
                    } else {
    		            var value = this.htmlToPlain(this.model.get(this.name));
    		            this.model.set(this.name, value);
                        this.disableWysiwygMode();
                    }
                }
                if (this.mode == 'detail') {
                    if (this.isRendered()) {
                        this.render();
                    }
                }
            }.bind(this));

            this.once('remove', function () {
                $('body > .tooltip').remove();
            });
        },

        getValueForDisplay: function () {
            var value = Dep.prototype.getValueForDisplay.call(this);
            if (this.mode == 'edit' && value) {
                value = value.replace(/<[\/]{0,1}(base|BASE)[^><]*>/g, '');
            }
            return value;
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);


            var language = this.getConfig().get('language');

            if (!(language in $.summernote.lang)) {
                $.summernote.lang[language] = this.getLanguage().translate('summernote', 'sets');
            }

            if (this.mode == 'edit') {
                if (!this.model.has('isHtml') || this.model.get('isHtml')) {
                    this.enableWysiwygMode();
                }
            }

            if (this.mode == 'detail') {
                if (!this.model.has('isHtml') || this.model.get('isHtml')) {
                    this.$el.find('iframe').removeClass('hidden');

                    var $iframe = this.$el.find('iframe');
                    var iframe = this.iframe = $iframe.get(0);

                    if (!iframe) return;

                    $iframe.load(function () {
                        $iframe.contents().find('a').attr('target', '_blank');
                    });

                    var doc = iframe.contentWindow.document;

                    var link = '<link href="client/css/iframe.css" rel="stylesheet" type="text/css"></link>';

                    doc.open('text/html', 'replace');
                    var body = this.model.get(this.name);
                    body += link;

                    doc.write(body);
                    doc.close();

                    var processHeight = function () {
                        var $body = $iframe.contents().find('html body');

                        var height = $body.height() + 30;
                        iframe.style.height = height + 'px';
                    };

                    setTimeout(function () {
                        processHeight();
                        $iframe.load(function () {
                            processHeight();
                        });
                    }, 50);

                } else {
                    this.$el.find('.plain').removeClass('hidden');
                }
            }
        },

        enableWysiwygMode: function () {
            if (!this.$element) {
                return;
            }
            this.$summernote = this.$element.summernote({
                height: this.height,
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
                                attachment.set('role', 'Inline Attachment');
                                attachment.set('global', true);
                                attachment.set('size', file.size);
                                attachment.once('sync', function () {
                                    var url = '?entryPoint=attachment&id=' + attachment.id;
                                    this.$summernote.summernote('insertImage', url);
                                    this.notify(false);
                                }, this);
                                attachment.save();
                            }.bind(this));
                        }.bind(this);
                        fileReader.readAsDataURL(file);

                    }, this);
                }.bind(this),
                onblur: function () {
                    this.trigger('change')
                }.bind(this),
                toolbar: this.toolbar
            });
        },

        plainToHtml: function (html) {
        	html = html || '';
        	var value = html.replace(/\n/g, '<br>');
        	return value;
        },

        htmlToPlain: function (text) {
        	text = text || '';
            var value = text.replace(/<br\s*\/?>/mg, '\n');
            value = $('<div>').html(value).text();
            return value;
        },

        disableWysiwygMode: function () {
            this.$element.destroy();
        },

        fetch: function () {
            var data = {};
            if (!this.model.has('isHtml') || this.model.get('isHtml')) {
                data[this.name] = this.$element.code();
            } else {
                data[this.name] = this.$element.val();
            }

            if (this.model.has('isHtml')) {
            	if (this.model.get('isHtml')) {
            		data[this.name + 'Plain'] = this.htmlToPlain(data[this.name]);
            	} else {
            		data[this.name + 'Plain'] = data[this.name];
            	}
            }
            return data;
        }
    });
});


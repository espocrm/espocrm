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

        type: 'wysiwyg',

        detailTemplate: 'fields.wysiwyg.detail',

        editTemplate: 'fields.wysiwyg.edit',

        height: 250,

        setup: function () {
            Dep.prototype.setup.call(this);

            this.height = this.params.height || this.height;
            this.toolbar = this.params.toolbar || [
                ['style', ['style']],
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['height', ['height']],
                ['table', ['table', 'link', 'picture']],
                ['misc',['codeview']]
            ];

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
                if (!this.model.has('isHtml') || this.model.get('isHtml')) {
                    this.enableWysiwygMode();
                }
            }

            if (this.mode == 'detail') {
                if (!this.model.has('isHtml') || this.model.get('isHtml')) {
                    this.$el.find('iframe').removeClass('hidden');

                    var $iframe = this.$el.find('iframe');
                    var iframe = this.iframe = $iframe.get(0);

                    $iframe.load(function () {
                        $iframe.contents().find('a').attr('target', '_blank');
                    });

                    var doc = iframe.contentWindow.document;

                    var link = '<link href="client/css/iframe.css" rel="stylesheet" type="text/css"></link>';

                    doc.open('text/html', 'replace');
                    var body = this.model.get('body');
                    body += link;

                    doc.write(body);
                    doc.close();



                    setTimeout(function () {
                        var height = $iframe.contents().find('html body').height();
                        iframe.style.height = height + 'px';

                        $iframe.load(function () {
                            var height = $iframe.contents().find('html body').height();
                            iframe.style.height = height + 'px';
                        });

                    }, 50);

                } else {
                    this.$el.find('.plain').removeClass('hidden');
                }
            }
        },

        enableWysiwygMode: function () {
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
                                    editor.insertImage(welEditable, url);
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

        disableWysiwygMode: function () {
            var value = this.model.get(this.name).replace(/<br\s*\/?>/mg,"\n");
            value = $('<div>').html(value).text();
            this.model.set(this.name, value);
            this.$element.destroy();
        },

        fetch: function () {
            var data = {};
            if (!this.model.has('isHtml') || this.model.get('isHtml')) {
                data[this.name] = this.$element.code();
            } else {
                data[this.name] = this.$element.val();
            }
            return data;
        }
    });
});


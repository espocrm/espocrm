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

Espo.define('views/note/fields/post', ['views/fields/text', 'lib!Textcomplete'], function (Dep, Textcomplete) {

    return Dep.extend({

        rowsDefault: 1,

        seeMoreText: false,

        events: _.extend({
            'input textarea': function (e) {
                this.controlTextareaHeight();
            },
        }, Dep.prototype.events),

        setup: function () {
            Dep.prototype.setup.call(this);
        },

        controlTextareaHeight: function () {
            var scrollHeight = this.$element.prop('scrollHeight');
            var clientHeight = this.$element.prop('clientHeight');
            if (this.$element.prop('scrollHeight') > clientHeight) {
                this.$element.prop('rows', this.$element.prop('rows') + 1);
                this.controlTextareaHeight();
            }

            if (this.$element.val().length === 0) {
                this.$element.prop('rows', 1);
            }
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            this.$element.attr('placeholder', this.translate('writeMessage', 'messages', 'Note'));

            this.$textarea = this.$element;
            var $textarea = this.$textarea;

            $textarea.off('drop');
            $textarea.off('dragover');
            $textarea.off('dragleave');

            this.$textarea.on('drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var e = e.originalEvent;
                if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                    this.trigger('add-files', e.dataTransfer.files);
                }
                this.$textarea.attr('placeholder', originalPlaceholderText);
            }.bind(this));

            var originalPlaceholderText = this.$textarea.attr('placeholder');

            this.$textarea.on('dragover', function (e) {
                e.preventDefault();
                this.$textarea.attr('placeholder', this.translate('dropToAttach', 'messages'));
            }.bind(this));
            this.$textarea.on('dragleave', function (e) {
                e.preventDefault();
                this.$textarea.attr('placeholder', originalPlaceholderText);
            }.bind(this));

            var assignmentPermission = this.getAcl().get('assignmentPermission');

            var buildUserListUrl = function (term) {
                var url = 'User?orderBy=name&limit=7&q=' + term + '&' + $.param({'primaryFilter': 'active'});
                if (assignmentPermission == 'team') {
                    url += '&' + $.param({'boolFilterList': ['onlyMyTeam']})
                }
                return url;
            }.bind(this);

            if (assignmentPermission !== 'no') {
                this.$element.textcomplete([{
                    match: /(^|\s)@(\w*)$/,
                    search: function (term, callback) {
                        if (term.length == 0) {
                            callback([]);
                            return;
                        }
                        $.ajax({
                            url: buildUserListUrl(term)
                        }).done(function (data) {
                            callback(data.list)
                        });
                    },
                    template: function (mention) {
                        return mention.name + ' <span class="text-muted">@' + mention.userName + '</span>';
                    },
                    replace: function (o) {
                        return '$1@' + o.userName + '';
                    }
                }],{
                    zIndex: 1100
                });

                this.once('remove', function () {
                    if (this.$element.size()) {
                        this.$element.textcomplete('destroy');
                    }
                }, this);
            }
        },

        validateRequired: function () {
            if (this.isRequired()) {
                if ((this.model.get('attachmentsIds') || []).length) {
                    return false;
                }
            }
            return Dep.prototype.validateRequired.call(this);
        },


    });

});
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

Espo.define('views/note/fields/post', ['views/fields/text', 'lib!Textcomplete'], function (Dep, Textcomplete) {

    return Dep.extend({

        rowsDefault: 1,

        seeMoreText: false,

        events: _.extend({
            'input textarea': function (e) {
                var $text = $(e.currentTarget);
                var numberOfLines = e.currentTarget.value.split("\n").length;
                var numberOfRows = $text.prop('rows');

                if (numberOfRows < numberOfLines) {
                    $text.prop('rows', numberOfLines);
                } else if (numberOfRows > numberOfLines) {
                    $text.prop('rows', numberOfLines);
                }
            },
        }, Dep.prototype.events),

        setup: function () {
            Dep.prototype.setup.call(this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            this.$element.attr('placeholder', this.translate('writeMessage', 'messages', 'Note'));

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
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

Espo.define('Views.Stream.Note', 'View', function (Dep) {

    return Dep.extend({

        messageName: null,

        messageTemplate: null,

        messageData: null,

        isEditable: false,

        isRemovable: false,

        isSystemAvatar: false,

        data: function () {
            return {
                isUserStream: this.isUserStream,
                noEdit: this.options.noEdit,
                acl: this.options.acl,
                onlyContent: this.options.onlyContent,
                avatar: this.getAvatarHtml()
            };
        },

        init: function () {
            this.createField('createdAt', null, null, 'Fields.DatetimeShort');
            this.isUserStream = this.options.isUserStream;
            this.isThis = !this.isUserStream;

            this.parentModel = this.options.parentModel;

            if (!this.isUserStream) {
                if (this.parentModel) {
                    if (
                        this.parentModel.name != this.model.get('parentType') ||
                        this.parentModel.id != this.model.get('parentId')
                    ) {
                        this.isThis = false;
                    }
                }
            }


            if (this.messageName && this.isThis) {
                this.messageName += 'This';
            }

            if (!this.isThis) {
                this.createField('parent');
            }

            this.messageData = {
                'user': 'field:createdBy',
                'entity': 'field:parent',
                'entityType': this.translateEntityType(this.model.get('parentType')),
            };

            if (!this.options.noEdit && (this.isEditable || this.isRemovable)) {
                this.createView('right', 'views/stream/row-actions/default', {
                    el: this.options.el + ' .right-container',
                    acl: this.options.acl,
                    model: this.model,
                    isEditable: this.isEditable,
                    isRemovable: this.isRemovable
                });
            }
        },

        translateEntityType: function (entityType, isPlural) {
            var string;

            if (!isPlural) {
                string = (this.translate(entityType, 'scopeNames') || '');
            } else {
                string = (this.translate(entityType, 'scopeNamesPlural') || '');
            }

            string = string.toLowerCase();

            var language = this.getPreferences().get('language') || this.getConfig().get('language');

            if (~['de_DE', 'nl_NL'].indexOf(language)) {
                string = Espo.Utils.upperCaseFirst(string);
            }
            return string;
        },

        createField: function (name, type, params, view) {
            type = type || this.model.getFieldType(name) || 'base';
            this.createView(name, view || this.getFieldManager().getViewName(type), {
                model: this.model,
                defs: {
                    name: name,
                    params: params || {}
                },
                el: this.options.el + ' .cell-' + name,
                mode: 'list'
            });

        },

        createMessage: function () {
            if (!this.messageTemplate) {
                this.messageTemplate = this.translate(this.messageName, 'streamMessages') || '';
            }

            this.createView('message', 'Stream.Message', {
                messageTemplate: this.messageTemplate,
                el: this.options.el + ' .message',
                model: this.model,
                messageData: this.messageData
            });
        },

        getAvatarHtml: function () {
            if (this.getConfig().get('avatarsDisabled')) {
                return '';
            }
            var t;
            var cache = this.getCache();
            if (cache) {
                t = cache.get('app', 'timestamp');
            } else {
                t = Date.now();
            }
            var id = this.model.get('createdById');
            if (this.isSystemAvatar) {
                id = 'system';
            }
            return '<img class="avatar" width="20" src="?entryPoint=avatar&size=small&id=' + id + '&t='+t+'">';
        }

    });
});


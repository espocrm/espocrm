/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/notification/fields/container', ['views/fields/base'], function (Dep) {

    return Dep.extend({

        type: 'notification',

        listTemplate: 'notification/fields/container',

        detailTemplate: 'notification/fields/container',

        setup: function () {
            switch (this.model.get('type')) {
                case 'Note':
                    this.processNote(this.model.get('noteData'));

                    break;

                case 'MentionInPost':
                    this.processMentionInPost(this.model.get('noteData'));

                    break;

                default:
                    this.process();
            }
        },

        process: function () {
            let type = this.model.get('type');

            if (!type) {
                return;
            }

            type = type.replace(/ /g, '');

            let viewName = this.getMetadata()
                .get('clientDefs.Notification.itemViews.' + type) ||
                'views/notification/items/' + Espo.Utils.camelCaseToHyphen(type);

            this.createView('notification', viewName, {
                model: this.model,
                fullSelector: this.params.containerEl + ' li[data-id="' + this.model.id + '"]',
            });
        },

        processNote: function (data) {
            if (!data) {
                return;
            }

            this.wait(true);

            this.getModelFactory().create('Note', model => {
                model.set(data);

                let viewName = this.getMetadata()
                    .get('clientDefs.Note.itemViews.' + data.type) ||
                    'views/stream/notes/' + Espo.Utils.camelCaseToHyphen(data.type);

                this.createView('notification', viewName, {
                    model: model,
                    isUserStream: true,
                    fullSelector: this.params.containerEl + ' li[data-id="' + this.model.id + '"]',
                    onlyContent: true,
                    isNotification: true,
                });

                this.wait(false);
            });
        },

        processMentionInPost: function (data) {
            if (!data) {
                return;
            }

            this.wait(true);

            this.getModelFactory().create('Note', model => {
                model.set(data);

                let viewName = 'views/stream/notes/mention-in-post';

                this.createView('notification', viewName, {
                    model: model,
                    userId: this.model.get('userId'),
                    isUserStream: true,
                    fullSelector: this.params.containerEl + ' li[data-id="' + this.model.id + '"]',
                    onlyContent: true,
                    isNotification: true,
                });

                this.wait(false);
            });
        },
    });
});


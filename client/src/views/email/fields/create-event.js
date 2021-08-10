/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/email/fields/create-event', ['views/fields/base', 'lib!espo'], function (Dep, Espo) {

    return Dep.extend({

        detailTemplate: 'email/fields/create-event/detail',

        eventEntityType: 'Meeting',

        getAttributeList: function () {
            return [
                'icsEventData',
                'createdEventId',
            ];
        },

        events: {
            'click [data-action="createEvent"]': function () {
                this.createEvent();
            },
        },

        createEvent: function () {
            let viewName = this.getMetadata().get(['clientDefs', this.eventEntityType, 'modalViews', 'edit']) ||
                'views/modals/edit';

            let eventData = this.model.get('icsEventData') || {};

            let attributes = Espo.Utils.cloneDeep(eventData.valueMap || {});

            attributes.parentId = this.model.get('parentId');
            attributes.parentType = this.model.get('parentType');
            attributes.parentName = this.model.get('parentName');

            this.addFromAddressToAttributes(attributes);

            this.createView('dialog', viewName, {
                attributes: attributes,
                scope: this.eventEntityType,
            })
                .then(view => {
                    view.render();

                    this.listenToOnce(view, 'after:save', () => {
                        this.model
                            .fetch()
                            .then(() =>
                                Espo.Ui.success(this.translate('Done'))
                            );
                    });
                });
        },

        addFromAddressToAttributes: function (attributes) {
            let fromAddress = this.model.get('from');
            let idHash = this.model.get('idHash') || {};
            let typeHash = this.model.get('typeHash') || {};
            let nameHash = this.model.get('nameHash') || {};

            let fromId = null;
            let fromType = null;
            let fromName = null;

            if (!fromAddress) {
                return;
            }

            fromId = idHash[fromAddress] || null;
            fromType = typeHash[fromAddress] || null;
            fromName = nameHash[fromAddress] || null;

            let attendeeLink = this.getAttendeeLink(fromType);

            if (!attendeeLink) {
                return;
            }

            attributes[attendeeLink + 'Ids'] = attributes[attendeeLink + 'Ids'] || [];
            attributes[attendeeLink + 'Names'] = attributes[attendeeLink + 'Names'] || {};

            if (~attributes[attendeeLink + 'Ids'].indexOf(fromId)) {
                return;
            }

            attributes[attendeeLink + 'Ids'].push(fromId);
            attributes[attendeeLink + 'Names'][fromId] = fromName;
        },

        getAttendeeLink: function (entityType) {
            if (entityType === 'User') {
                return 'users';
            }

            if (entityType === 'Contact') {
                return 'contacts';
            }

            if (entityType === 'Lead') {
                return 'leads';
            }

            return null;
        },

    });
});

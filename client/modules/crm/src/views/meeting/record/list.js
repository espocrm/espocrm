/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

define('crm:views/meeting/record/list', ['views/record/list'], function (Dep) {

    return Dep.extend({

        rowActionsView: 'crm:views/meeting/record/row-actions/default',

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.getAcl().checkScope(this.entityType, 'edit')) {
                this.massActionList.push('setHeld');
                this.massActionList.push('setNotHeld');
            }
        },

        actionSetHeld: function (data) {
            let id = data.id;

            if (!id) {
                return;
            }

            let model = this.collection.get(id);

            if (!model) {
                return;
            }

            model.set('status', 'Held');

            this.listenToOnce(model, 'sync', () => {
                Espo.Ui.notify(false);

                this.collection.fetch();
            });

            Espo.Ui.notify(this.translate('saving', 'messages'));

            model.save();
        },

        actionSetNotHeld: function (data) {
            let id = data.id;

            if (!id) {
                return;
            }

            var model = this.collection.get(id);

            if (!model) {
                return;
            }

            model.set('status', 'Not Held');

            this.listenToOnce(model, 'sync', () => {
                Espo.Ui.notify(false);
                this.collection.fetch();
            });

            Espo.Ui.notify(this.translate('saving', 'messages'));

            model.save();
        },

        massActionSetHeld: function () {
            Espo.Ui.notify(this.translate('saving', 'messages'));

            let data = {ids: this.checkedList};

            Espo.Ajax.postRequest(this.collection.entityType + '/action/massSetHeld', data)
                .then(() => {
                    Espo.Ui.notify(false);

                    this.listenToOnce(this.collection, 'sync', () => {
                        data.ids.forEach(id => {
                            if (this.collection.get(id)) {
                                this.checkRecord(id);
                            }
                        });
                    });

                    this.collection.fetch();
                });
        },

        massActionSetNotHeld: function () {
            Espo.Ui.notify(this.translate('saving', 'messages'));

            let data = {ids: this.checkedList};

            Espo.Ajax.postRequest(this.collection.entityType + '/action/massSetNotHeld', data)
                .then(() => {
                    Espo.Ui.notify(false);

                    this.listenToOnce(this.collection, 'sync', () => {
                        data.ids.forEach(id => {
                            if (this.collection.get(id)) {
                                this.checkRecord(id);
                            }
                        });
                    });

                    this.collection.fetch();
                });
        },

    });
});

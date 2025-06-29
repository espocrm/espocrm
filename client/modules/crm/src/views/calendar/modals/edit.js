/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

import EditModalView from 'views/modals/edit';

class CalenderEditModalView extends EditModalView {

    template = 'crm:calendar/modals/edit'

    scopeList = [
        'Meeting',
        'Call',
        'Task',
    ]

    data() {
        return {
            scopeList: this.scopeList,
            scope: this.scope,
            isNew: !(this.id),
        };
    }

    additionalEvents = {
        /** @this CalenderEditModalView */
        'change .scope-switcher input[name="scope"]': function () {
            Espo.Ui.notifyWait();

            const prevScope = this.scope;

            const scope = $('.scope-switcher input[name="scope"]:checked').val();
            this.scope = scope;

            this.getModelFactory().create(this.scope, model => {
                model.populateDefaults();

                let attributes = this.getRecordView().fetch();

                attributes = {...attributes, ...this.getRecordView().model.getClonedAttributes()};

                this.filterAttributesForEntityType(attributes, scope, prevScope);

                model.set(attributes);

                this.model = model;

                this.createRecordView(model, (view) => {
                    view.render();
                    view.notify(false);
                });

                this.handleAccess(model);
            });
        },
    }

    /**
     * @param {Record} attributes
     * @param {string} entityType
     * @param {string} previousEntityType
     */
    filterAttributesForEntityType(attributes, entityType, previousEntityType) {
        if (entityType === 'Task' || previousEntityType === 'Task') {
            delete attributes.reminders;
        }

        this.getHelper()
            .fieldManager
            .getEntityTypeFieldList(entityType, {type: 'enum'})
            .forEach(field => {
                if (!(field in attributes)) {
                    return;
                }

                const options = this.getMetadata().get(['entityDefs', entityType, 'fields', field, 'options']) || [];

                const value = attributes[field];

                if (!~options.indexOf(value)) {
                    delete attributes[field];
                }
            });
    }

    createRecordView(model, callback) {
        if (!this.id && !this.dateIsChanged) {
            if (this.options.dateStart && this.options.dateEnd) {
                this.model.set('dateStart', this.options.dateStart);
                this.model.set('dateEnd', this.options.dateEnd);
            }

            if (this.options.allDay) {
                const allDayScopeList = this.getMetadata().get('clientDefs.Calendar.allDayScopeList') || [];

                if (~allDayScopeList.indexOf(this.scope)) {
                    this.model.set('dateStart', null);
                    this.model.set('dateEnd', null);
                    this.model.set('dateStartDate', null);
                    this.model.set('dateEndDate', this.options.dateEndDate);

                    if (this.options.dateEndDate !== this.options.dateStartDate) {
                        this.model.set('dateStartDate', this.options.dateStartDate);
                    }
                }
                else if (this.getMetadata().get(['entityDefs', this.scope, 'fields', 'dateStartDate'])) {
                    this.model.set('dateStart', null);
                    this.model.set('dateEnd', null);
                    this.model.set('dateStartDate', this.options.dateStartDate);
                    this.model.set('dateEndDate', this.options.dateEndDate);
                    this.model.set('isAllDay', true);
                }
                else {
                    this.model.set('isAllDay', false);
                    this.model.set('dateStartDate', null);
                    this.model.set('dateEndDate', null);
                }
            }
        }

        this.listenTo(this.model, 'change:dateStart', (m, value, o) => {
            if (o.ui) {
                this.dateIsChanged = true;
            }
        });

        this.listenTo(this.model, 'change:dateEnd', (m, value, o) => {
            if (o.ui || o.updatedByDuration) {
                this.dateIsChanged = true;
            }
        });


        super.createRecordView(model, callback);
    }

    handleAccess(model) {
        if (
            this.id &&
            !this.getAcl().checkModel(model, 'edit') || !this.id &&
            !this.getAcl().checkModel(model, 'create')
        ) {
            this.hideButton('save');
            this.hideButton('fullForm');

            this.$el.find('button[data-name="save"]').addClass('hidden');
            this.$el.find('button[data-name="fullForm"]').addClass('hidden');
        }
        else {
            this.showButton('save');
            this.showButton('fullForm');
        }

        if (!this.getAcl().checkModel(model, 'delete')) {
            this.hideButton('remove');
        } else {
            this.showButton('remove');
        }
    }

    afterRender() {
        super.afterRender();

        if (this.hasView('edit')) {
            const model = this.getView('edit').model;

            if (model) {
                this.handleAccess(model);
            }
        }
    }

    setup() {
        this.events = {
            ...this.additionalEvents,
            ...this.events,
        };

        this.scopeList = Espo.Utils.clone(this.options.scopeList || this.scopeList);
        this.enabledScopeList = this.options.enabledScopeList || this.scopeList;

        if (!this.options.id && !this.options.scope) {
            const scopeList = [];

            this.scopeList.forEach((scope) => {
                if (this.getAcl().check(scope, 'create')) {
                    if (~this.enabledScopeList.indexOf(scope)) {
                        scopeList.push(scope);
                    }
                }
            });

            this.scopeList = scopeList;

            const calendarDefaultEntity = scopeList[0];

            if (calendarDefaultEntity && ~this.scopeList.indexOf(calendarDefaultEntity)) {
                this.options.scope = calendarDefaultEntity;
            } else {
                this.options.scope = this.scopeList[0] || null;
            }

            if (this.scopeList.length === 0) {
                this.remove();
                return;
            }
        }

        super.setup();

        if (!this.id) {
            this.$header = $('<a>')
                .attr('title', this.translate('Full Form'))
                .attr('role', 'button')
                .attr('data-action', 'fullForm')
                .addClass('action')
                .text(this.translate('Create', 'labels', 'Calendar'));
        }

        if (this.id) {
            this.buttonList.splice(1, 0, {
                name: 'remove',
                text: this.translate('Remove'),
                onClick: () => this.actionRemove(),
            });
        }

        this.once('after:save', () => {
            this.$el.find('.scope-switcher').remove();
        })
    }

    actionRemove() {
        const model = this.getView('edit').model;

        this.confirm(this.translate('removeRecordConfirmation', 'messages'), () => {
            const $buttons = this.dialog.$el.find('.modal-footer button');

            $buttons.addClass('disabled');

            model.destroy()
                .then(() => {
                    this.trigger('after:delete', model);

                    this.dialog.close();
                })
                .catch(() => {
                    $buttons.removeClass('disabled');
                });
        });
    }
}

export default CalenderEditModalView;

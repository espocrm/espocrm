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

import RelationshipPanelView from 'views/record/panels/relationship';
import CreateRelatedHelper from 'helpers/record/create-related';

export default class TasksRelationshipPanelView extends RelationshipPanelView {

    name = 'tasks'
    entityType = 'Task'
    filterList = ['all', 'actual', 'completed']

    orderBy = 'createdAt'
    orderDirection = 'desc'

    rowActionsView = 'crm:views/record/row-actions/tasks'

    buttonList = [
        {
            action: 'createTask',
            title: 'Create Task',
            acl: 'create',
            aclScope: 'Task',
            html: '<span class="fas fa-plus"></span>',
        },
    ]

    actionList = [
        {
            label: 'View List',
            action: 'viewRelatedList'
        }
    ]

    listLayout = {
        rows: [
            [
                {
                    name: 'name',
                    link: true,
                },
            ],
            [
                {
                    name: 'isOverdue'
                },
                {name: 'assignedUser'},
                {
                    name: 'dateEnd',
                    soft: true
                },
                {name: 'status'},
            ]
        ]
    }

    setup() {
        this.parentScope = this.model.entityType;
        this.link = 'tasks';

        this.panelName = 'tasksSide';

        this.defs.create = true;

        if (this.parentScope === 'Account') {
            this.link = 'tasksPrimary';
        }

        this.url = this.model.entityType + '/' + this.model.id + '/' + this.link;

        this.setupSorting();

        if (this.filterList && this.filterList.length) {
            this.filter = this.getStoredFilter();
        }

        this.setupFilterActions();

        this.setupTitle();

        this.wait(true);

        this.getCollectionFactory().create('Task', (collection) => {
            this.collection = collection;
            collection.seeds = this.seeds;
            collection.url = this.url;
            collection.orderBy = this.defaultOrderBy;
            collection.order = this.defaultOrder;
            collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;

            this.setFilter(this.filter);
            this.wait(false);
        });

        this.once('show', () => {
            if (!this.isRendered() && !this.isBeingRendered()) {
                this.collection.fetch();
            }
        });

        let events = `update-related:${this.link} update-all`;

        if (this.parentScope === 'Account') {
            events += ' update-related:tasks';
        }

        this.listenTo(this.model, events, () => this.collection.fetch());
    }

    afterRender() {
        this.createView('list', 'views/record/list-expanded', {
            selector: '> .list-container',
            pagination: false,
            type: 'listRelationship',
            rowActionsView: this.defs.rowActionsView || this.rowActionsView,
            checkboxes: false,
            collection: this.collection,
            listLayout: this.listLayout,
            skipBuildRows: true,
        }, (view) => {
            view.getSelectAttributeList(selectAttributeList => {
                if (selectAttributeList) {
                    this.collection.data.select = selectAttributeList.join(',');
                }

                if (!this.disabled) {
                    this.collection.fetch();

                    return;
                }

                this.once('show', () => this.collection.fetch());
            });
        });
    }

    actionCreateRelated() {
        this.actionCreateTask();
    }

    actionCreateTask() {
        let link = this.link;

        if (this.parentScope === 'Account') {
            link = 'tasks';
        }

        const helper = new CreateRelatedHelper(this);

        helper.process(this.model, link)
    }

    // noinspection JSUnusedGlobalSymbols
    actionComplete(data) {
        const id = data.id;

        if (!id) {
            return;
        }

        const model = this.collection.get(id);

        model.save({status: 'Completed'}, {patch: true})
            .then(() => this.collection.fetch());
    }

    actionViewRelatedList(data) {
        data.viewOptions = data.viewOptions || {};
        data.viewOptions.massUnlinkDisabled = true;

        super.actionViewRelatedList(data);
    }
}

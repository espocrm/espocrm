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

/** @module views/stream/record/list */

import ListExpandedRecordView from 'views/record/list-expanded';

/**
 * @property collection
 * @memberOf ListStreamRecordView#
 * @type module:collections/note
 */

class ListStreamRecordView extends ListExpandedRecordView {

    type = 'listStream'

    massActionsDisabled = true

    setup() {
        this.itemViews = this.getMetadata().get('clientDefs.Note.itemViews') || {};

        super.setup();

        this.isRenderingNew = false;

        this.listenTo(this.collection, 'sync', (c, r, options) => {
            if (!options.fetchNew) {
                return;
            }

            if (this.isRenderingNew) {
                // Prevent race condition.
                return;
            }

            let lengthBeforeFetch = options.lengthBeforeFetch || 0;

            if (lengthBeforeFetch === 0) {
                this.buildRows(() => this.reRender());

                return;
            }

            let $list = this.$el.find(this.listContainerEl);

            let rowCount = this.collection.length - lengthBeforeFetch;

            if (rowCount === 0) {
                return;
            }

            this.isRenderingNew = true;

            for (let i = rowCount - 1; i >= 0; i--) {
                let model = this.collection.at(i);

                this.buildRow(i, model, view => {
                    if (i === 0) {
                        this.isRenderingNew = false;
                    }

                    let $row = $(this.getRowContainerHtml(model.id));

                    // Prevent a race condition issue.
                    let $existingRow = this.$el.find(`[data-id="${model.id}"]`);

                    if ($existingRow.length) {
                        $row = $existingRow;
                    }

                    if (!$existingRow.length) {
                        $list.prepend($row);
                    }

                    view.render();
                });
            }
        });

        this.events['auxclick a[href][data-scope][data-id]'] = e => {
            let isCombination = e.button === 1 && (e.ctrlKey || e.metaKey);

            if (!isCombination) {
                return;
            }

            let $target = $(e.currentTarget);

            let id = $target.attr('data-id');
            let scope = $target.attr('data-scope');

            e.preventDefault();
            e.stopPropagation();

            this.actionQuickView({
                id: id,
                scope: scope,
            });
        };
    }

    buildRow(i, model, callback) {
        let key = model.id;

        this.rowList.push(key);

        let type = model.get('type');
        let viewName = this.itemViews[type] || 'views/stream/notes/' + Espo.Utils.camelCaseToHyphen(type);

        this.createView(key, viewName, {
            model: model,
            parentModel: this.model,
            acl: {
                edit: this.getAcl().checkModel(model, 'edit')
            },
            isUserStream: this.options.isUserStream,
            noEdit: this.options.noEdit,
            optionsToPass: ['acl'],
            name: this.type + '-' + model.entityType,
            selector: 'li[data-id="' + model.id + '"]',
            setViewBeforeCallback: this.options.skipBuildRows && !this.isRendered(),
        }, callback);
    }

    buildRows(callback) {
        this.checkedList = [];
        this.rowList = [];

        if (this.collection.length > 0) {
            this.wait(true);

            let count = this.collection.models.length;
            let built = 0;

            for (let i in this.collection.models) {
                let model = this.collection.models[i];

                this.buildRow(i, model, () => {
                    built++;

                    if (built === count) {
                        if (typeof callback === 'function') {
                            callback();
                        }

                        this.wait(false);

                        this.trigger('after:build-rows');
                    }
                });
            }

            return;
        }

        if (typeof callback === 'function') {
            callback();

            this.trigger('after:build-rows');
        }
    }

    showNewRecords() {
        this.collection.fetchNew();
    }
}

export default ListStreamRecordView;

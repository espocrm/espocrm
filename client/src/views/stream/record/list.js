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

/** @module views/stream/record/list */

import ListExpandedRecordView from 'views/record/list-expanded';

class ListStreamRecordView extends ListExpandedRecordView {

    type = 'listStream'

    massActionsDisabled = true


    /**
     * @private
     * @type {boolean}
     */
    isUserStream

    setup() {
        this.isUserStream = this.options.isUserStream || false;

        this.itemViews = this.getMetadata().get('clientDefs.Note.itemViews') || {};

        super.setup();

        this.isRenderingNew = false;

        this.listenTo(this.collection, 'update-sync', () => {
            this.buildRows(() => this.reRender());
        });

        if (this.isUserStream || this.model.entityType === 'User') {
            const collection = /** @type {import('collections/note').default} */this.collection;

            collection.reactionsCheckMaxSize = this.getConfig().get('streamReactionsCheckMaxSize') || 0;
        }

        this.listenTo(this.collection, 'sync', (c, r, options) => {
            if (!options.fetchNew) {
                return;
            }

            if (this.isRenderingNew) {
                // Prevent race condition.
                return;
            }

            const lengthBeforeFetch = options.lengthBeforeFetch || 0;

            if (lengthBeforeFetch === 0) {
                this.buildRows(() => this.reRender());

                return;
            }

            const $list = this.$el.find(this.listContainerEl);

            const rowCount = this.collection.length - lengthBeforeFetch;

            if (rowCount === 0) {
                return;
            }

            this.isRenderingNew = true;

            for (let i = rowCount - 1; i >= 0; i--) {
                const model = this.collection.at(i);

                this.buildRow(i, model, view => {
                    if (i === 0) {
                        this.isRenderingNew = false;
                    }

                    let $row = $(this.getRowContainerHtml(model.id));

                    // Prevent a race condition issue.
                    const $existingRow = this.$el.find(`[data-id="${model.id}"]`);

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
            const isCombination = e.button === 1 && (e.ctrlKey || e.metaKey);

            if (!isCombination) {
                return;
            }

            const $target = $(e.currentTarget);

            const id = $target.attr('data-id');
            const scope = $target.attr('data-scope');

            e.preventDefault();
            e.stopPropagation();

            this.actionQuickView({
                id: id,
                scope: scope,
            });
        };
    }

    buildRow(i, model, callback) {
        const key = model.id;

        this.rowList.push(key);

        const type = model.get('type');
        const viewName = this.itemViews[type] || 'views/stream/notes/' + Espo.Utils.camelCaseToHyphen(type);

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
            listType: this.type,
            rowActionsView: this.options.rowActionsView,
        }, callback);
    }

    buildRows(callback) {
        this.checkedList = [];
        this.rowList = [];

        if (this.collection.length > 0) {
            this.wait(true);

            const count = this.collection.models.length;
            let built = 0;

            for (const i in this.collection.models) {
                const model = this.collection.models[i];

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

    /**
     * Load new records.
     *
     * @return {Promise}
     */
    showNewRecords() {
        const collection = /** @type {import('collections/note').default} */
            this.collection;

        return collection.fetchNew();
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @private
     * @param {{id: string}} data
     */
    actionPin(data) {
        const collection = /** @type {import('collections/note').default} */this.collection;

        Espo.Ui.notifyWait();

        Espo.Ajax.postRequest(`Note/${data.id}/pin`).then(() => {
            Espo.Ui.notify(false);

            const model = collection.get(data.id);

            if (model) {
                model.set('isPinned', true);
            }

            if (collection.pinnedList) {
                collection.fetchNew();
            }

            collection.trigger('pin', model.id);
        });
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @private
     * @param {{id: string}} data
     */
    actionUnpin(data) {
        const collection = /** @type {import('collections/note').default} */this.collection;

        Espo.Ui.notifyWait();

        Espo.Ajax.deleteRequest(`Note/${data.id}/pin`).then(() => {
            Espo.Ui.notify(false);

            const model = collection.get(data.id);

            if (model) {
                model.set('isPinned', false);
            }

            if (collection.pinnedList) {
                collection.fetchNew();
            }

            collection.trigger('unpin', model.id);
        });
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @private
     * @param {{id: string}} data
     */
    actionQuoteReply(data) {
        const rowView = this.getView(data.id);

        const selection = window.getSelection();

        if (selection && selection.anchorNode && selection.focusNode) {
            const postContainer = rowView.element.querySelector('.complex-text');

            if (
                postContainer.contains(selection.anchorNode) &&
                postContainer.contains(selection.focusNode)
            ) {
                let contents = '';

                for (let i = 0; i < selection.rangeCount; i++) {
                    const range = selection.getRangeAt(i);

                    const div = document.createElement('div');
                    div.appendChild(range.cloneContents());

                    contents += div.innerHTML;
                }

                if (contents) {
                    Espo.loader.requirePromise('turndown')
                        .then(/** typeof import('turndown').default */TurndownService => {
                            const turndownService = (new TurndownService());

                            // noinspection JSValidateTypes
                            const code = turndownService.turndown(contents);

                            this.trigger('quote-reply', code);
                        });

                    return;
                }
            }
        }

        const model = this.collection.get(data.id);

        if (!model) {
            return;
        }

        const code = model.attributes.post;

        if (!code) {
            return;
        }

        this.trigger('quote-reply', code);
    }
}

export default ListStreamRecordView;

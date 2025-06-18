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

/** @module views/record/list-tree */

import ListRecordView from 'views/record/list';
import RecordModal from 'helpers/record-modal';
import {Draggable} from '@shopify/draggable';

class ListTreeRecordView extends ListRecordView {

    template = 'record/list-tree'

    showMore = false
    showCount = false
    checkboxes = false
    rowActionsView = false
    presentationType = 'tree'
    header = false
    listContainerEl = ' > .list > ul'
    checkAllResultDisabled = true
    showRoot = false
    massActionList = ['remove']
    selectable = false
    createDisabled = false
    selectedData = null
    level = 0

    itemViewName = 'views/record/list-tree-item'

    /**
     * @protected
     * @type {boolean}
     */
    readOnly

    expandToggleInactive = false;

    /**
     * @private
     * @type {ListTreeRecordView}
     */
    rootView

    /**
     * @type {string|null}
     */
    movedId = null

    /**
     * @private
     * @type {boolean}
     */
    blockDraggable = false

    /**
     * @private
     * @type {boolean}
     */
    moveSupported

    /**
     * @private
     * {Draggable}
     */
    draggable

    // noinspection JSCheckFunctionSignatures
    data() {
        const data = super.data();

        data.createDisabled = this.createDisabled;

        data.showRoot = this.showRoot;

        if (data.showRoot) {
            data.rootName = this.rootName || this.translate('Top Level');
        }

        if (this.level === 0 && this.selectable && (this.selectedData || {}).id === null) {
            data.rootIsSelected = true;
        }

        if (this.level === 0) {
            data.isExpanded = this.isExpanded;
        }

        data.noData = data.createDisabled && !data.rowDataList.length && !data.showRoot;
        data.expandToggleInactive = this.expandToggleInactive;
        data.hasExpandToggle = !this.getUser().isPortal();

        data.isEditable = this.level === 0 && !this.readOnly;

        return data;
    }

    setup() {
        if ('selectable' in this.options) {
            this.selectable = this.options.selectable;
        }

        this.readOnly = this.options.readOnly;
        this.createDisabled = this.readOnly || this.options.createDisabled || this.createDisabled;
        this.isExpanded = this.options.isExpanded;

        if ('showRoot' in this.options) {
            this.showRoot = this.options.showRoot;

            if ('rootName' in this.options) {
                this.rootName = this.options.rootName;
            }
        }

        if ('level' in this.options) {
            this.level = this.options.level;
        }

        this.rootView = this.options.rootView || this;

        if (this.level === 0) {
            this.selectedData = {
                id: null,
                path: [],
                names: {},
            };
        }

        if ('selectedData' in this.options) {
            this.selectedData = this.options.selectedData;
        }

        this.entityType = this.collection.entityType;

        this.moveSupported = !!this.getMetadata().get(`entityDefs.${this.entityType}.fields.order`);

        super.setup();

        if (this.selectable) {
            this.on('select', o => {
                if (o.id) {
                    this.$el.find('a.link[data-id="'+o.id+'"]').addClass('text-bold');

                    if (this.level === 0) {
                        this.$el.find('a.link').removeClass('text-bold');
                        this.$el.find('a.link[data-id="'+o.id+'"]').addClass('text-bold');

                        this.setSelected(o.id);

                        o.selectedData = this.selectedData;
                    }
                }

                if (this.level > 0) {
                    this.getParentView().trigger('select', o);
                }
            });
        }
    }

    afterRender() {
        super.afterRender();

        if (this.level === 0 && !this.readOnly && this.moveSupported) {
            this.initDraggableRoot();
        }
    }

    /**
     * @param {string|null} id
     */
    setSelected(id) {
        if (id === null) {
            this.selectedData.id = null;
        } else {
            this.selectedData.id = id;
        }

        this.rowList.forEach(key => {
            const view = /** @type module:views/record/list-tree-item */this.getView(key);

            if (view.model.id === id) {
                view.setIsSelected();
            }
            else {
                view.isSelected = false;
            }

            if (view.hasView('children')) {
                view.getChildrenView().setSelected(id);
            }
        });
    }

    /**
     * @return {import('views/record/list-tree-item').default[]}
     */
    getItemViews() {
        return this.rowList.map(key => this.getView(key));
    }

    buildRows(callback) {
        this.checkedList = [];
        this.rowList = [];

        if (this.collection.length > 0) {
            this.wait(true);

            const modelList = this.collection.models;
            const count = modelList.length;
            let built = 0;

            modelList.forEach(model => {
                const key = model.id;

                this.rowList.push(key);

                this.createView(key, this.itemViewName, {
                    model: model,
                    collection: this.collection,
                    selector: this.getRowSelector(model.id),
                    createDisabled: this.createDisabled,
                    readOnly: this.readOnly,
                    level: this.level,
                    isSelected: model.id === this.selectedData.id,
                    selectedData: this.selectedData,
                    selectable: this.selectable,
                    setViewBeforeCallback: this.options.skipBuildRows && !this.isRendered(),
                    rootView: this.rootView,
                    moveSupported: this.moveSupported,
                }, () => {
                    built++;

                    if (built === count) {
                        if (typeof callback === 'function') {
                            callback();
                        }

                        this.wait(false);
                    }
                });
            });

            return;
        }

        if (typeof callback === 'function') {
            callback();
        }
    }

    getRowSelector(id) {
        return 'li[data-id="' + id + '"]';
    }

    getCellSelector(model, item) {
        return `${this.getSelector() + this.getRowSelector(model.id)} span.cell[data-name="${item.name}"]`;
    }

    getCreateAttributes() {
        return {};
    }

    // noinspection JSUnusedGlobalSymbols
    actionCreate(data, e) {
        e.stopPropagation();

        const attributes = this.getCreateAttributes();

        let maxOrder = 0;

        this.collection.models.forEach(m => {
            if (m.get('order') > maxOrder) {
                maxOrder = m.get('order');
            }
        });

        attributes.order = maxOrder + 1;

        attributes.parentId = null;
        attributes.parentName = null;

        if (this.model) {
            attributes.parentId = this.model.id;
            attributes.parentName = this.model.attributes.name;
        }

        const helper = new RecordModal();

        helper.showCreate(this, {
            entityType: this.entityType,
            attributes: attributes,
            afterSave: model => {
                const collection = /** @type {import('collections/tree').default} collection */
                    this.collection;

                model.set('childCollection', collection.createSeed());

                if (model.attributes.parentId !== attributes.parentId) {
                    let v = this;

                    while (1) {
                        if (v.level) {
                            v = v.getParentView().getParentView();
                        } else {
                            break;
                        }
                    }

                    v.collection.fetch();

                    return;
                }

                this.collection.fetch();
            },
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionSelectRoot() {
        this.trigger('select', {id: null});

        if (this.selectable) {
            this.$el.find('a.link').removeClass('text-bold');
            this.$el.find('a.link[data-action="selectRoot"]').addClass('text-bold');

            this.setSelected(null);
        }
    }

    /**
     * @private
     */
    initDraggableRoot() {
        if (this.draggable) {
            this.draggable.destroy();
        }

        const draggable = this.draggable = new Draggable(this.element, {
            distance: 8,
            draggable: '.list-group-item > .cell > [data-role="moveHandle"]',
            mirror: {
                cursorOffsetX: 5,
                cursorOffsetY: 5,
                appendTo: 'body',
            },
        });

        /** @type {HTMLElement[]} */
        let rows;
        /** @type {Map<HTMLElement, number>} */
        let levelMap;
        /** @type {HTMLElement|null} */
        let movedHandle = null;
        /** @type {HTMLElement|null} */
        let movedLink = null;
        /** @type {HTMLElement|null} */
        let movedFromLi = null;

        draggable.on('mirror:created', event => {
            const mirror = event.mirror;
            const source = event.source;
            const originalSource = event.originalSource;

            originalSource.style.display = '';
            source.style.display = 'none';

            mirror.style.display = 'block';
            mirror.style.cursor = 'grabbing';
            mirror.classList.add('draggable-helper', 'draggable-helper-transparent', 'text-info');
            mirror.classList.remove('link');
            mirror.style.pointerEvents = 'auto';
            mirror.removeAttribute('href');
            mirror.style.textDecoration = 'none';

            mirror.innerText = mirror.dataset.title;
        });

        draggable.on('mirror:move', event => {
            event.mirror.style.pointerEvents = 'auto';
        });

        draggable.on('drag:start', event => {
            if (this.blockDraggable) {
                event.cancel();

                return;
            }

            rows = Array.from(this.element.querySelectorAll('.list-group-tree > .list-group-item'));

            levelMap = new Map();

            rows.forEach(row => {
                let depth = 0;
                let current = row;

                while (current && current !== this.element) {
                    current = current.parentElement;

                    depth ++;
                }

                levelMap.set(row, depth);
            });

            rows.sort((a, b) => levelMap.get(b) - levelMap.get(a));

            this.movedId = event.source.dataset.id;
            movedHandle = event.originalSource;
            movedFromLi = movedHandle.parentElement.parentElement;
            movedLink = movedHandle.parentElement.querySelector(`:scope > a.link`);

            movedLink.classList.add('text-info');
        });

        let overId = null;
        let overParentId = null;
        let isAfter = false;
        let wasOutOfSelf = false;

        draggable.on('drag:move', event => {
            isAfter = false;
            overId = null;

            let rowFound = null;

            for (const row of rows) {
                const rect = row.getBoundingClientRect();

                const isIn =
                    rect.left < event.sensorEvent.clientX &&
                    rect.right > event.sensorEvent.clientX &&
                    rect.top < event.sensorEvent.clientY &&
                    rect.bottom >= event.sensorEvent.clientY;

                if (!isIn) {
                    continue;
                }

                const itemId = row.dataset.id ?? null;
                let itemParentId = null;

                if (!itemId) {
                    const parent = row.closest(`.list-group-item[data-id]`);

                    if (parent instanceof HTMLElement) {
                        // Over a plus row.
                        itemParentId = parent.dataset.id;
                    }
                }

                const itemIsAfter = event.sensorEvent.clientY - rect.top >= rect.bottom - event.sensorEvent.clientY;

                if (itemParentId && itemIsAfter) {
                    continue;
                }

                if (itemId === this.movedId) {
                    break;
                }

                if (movedFromLi.contains(row)) {
                    break;
                }

                if (!itemId && !itemParentId) {
                    continue;
                }

                isAfter = itemIsAfter;
                overParentId = itemParentId;
                overId = itemId;
                rowFound = row;

                break;
            }

            for (const row of rows) {
                row.classList.remove('border-top-highlighted');
                row.classList.remove('border-bottom-highlighted');
            }

            if (!rowFound) {
                return;
            }

            if (isAfter) {
                rowFound.classList.add('border-bottom-highlighted');
                rowFound.classList.remove('border-top-highlighted');
            } else {
                rowFound.classList.add('border-top-highlighted');
                rowFound.classList.remove('border-bottom-highlighted');
            }
        });

        draggable.on('drag:stop', async () => {
            if (movedLink) {
                movedLink.classList.remove('text-info');
            }

            rows.forEach(row => {
                row.classList.remove('border-bottom-highlighted');
                row.classList.remove('border-top-highlighted');
            });

            rows = undefined;

            let moveType;
            let referenceId = overId;

            if (overParentId || overId) {
                if (overParentId) {
                    moveType = 'into';
                    referenceId = overParentId;
                } else if (isAfter) {
                    moveType = 'after';
                } else {
                    moveType = 'before';
                }
            }

            if (moveType) {
                this.blockDraggable = true;

                const movedId = this.movedId;
                const affectedId = referenceId;

                Espo.Ui.notifyWait();

                Espo.Ajax
                    .postRequest(`${this.entityType}/action/move`, {
                        id: this.movedId,
                        referenceId: referenceId,
                        type: moveType,
                    })
                    .then(async () => {
                        /**
                         *
                         * @param {ListTreeRecordView} view
                         * @param {string} movedId
                         * @return {Promise}
                         */
                        const update = async (view, movedId) => {
                            if (view.collection.has(movedId)) {
                                await view.collection.fetch();

                                return;
                            }

                            for (const subView of view.getItemViews()) {
                                if (!subView.getChildrenView()) {
                                    continue;
                                }

                                await update(subView.getChildrenView(), movedId);
                            }
                        };

                        const promises = [];

                        if (movedId) {
                            promises.push(update(this, movedId));
                        }

                        if (affectedId) {
                            promises.push(update(this, affectedId));
                        }

                        await Promise.all(promises);

                        Espo.Ui.success(this.translate('Done'));
                    })
                    .finally(() => {
                        this.blockDraggable = false;
                    });
            }

            this.movedId = null;

            movedHandle = null;
            movedFromLi = null;
            levelMap = undefined;
            overParentId = null;
            overId = null;
            isAfter = false;
            wasOutOfSelf = false;
        });
    }
}

export default ListTreeRecordView;

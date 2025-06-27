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
import ListTreeDraggableHelper from 'helpers/list/misc/list-tree-draggable';

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
    moveSupported

    /**
     * @private
     * @type {ListTreeDraggableHelper}
     */
    draggableHelper

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

        if (this.level === 0) {
            this.once('after:render', () => {
                const collection = /** @type {import('collections/tree').default} */this.collection;

                if (collection.openPath) {
                    /**
                     * @param {ListTreeRecordView} view
                     * @param {string[]} path
                     */
                    const open = async (view, path) => {
                        path = [...path];
                        const id = path.shift()

                        const itemView = view.getItemViews().find(view => view.model.id === id);

                        if (!itemView) {
                            return;
                        }

                        await itemView.unfold();

                        if (!path.length) {
                            return;
                        }

                        await open(itemView.getChildrenView(), path);
                    }

                    open(this, collection.openPath);

                    collection.openPath = null;
                }
            });
        }

        this.listenTo(this.collection, 'model-sync', (/** import('model').default */m, /** Record */o) => {
            if (o.action === 'destroy') {
                const index = this.rowList.findIndex(it => it === m.id);

                if (index > -1) {
                    this.rowList.splice(index, 1);
                }
            }
        });
    }

    onRemove() {
        super.onRemove();

        if (this.draggableHelper) {
            this.draggableHelper.destroy();
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
            } else {
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
        if (!this.draggableHelper) {
            this.draggableHelper = new ListTreeDraggableHelper(this);
        }

        this.draggableHelper.init();
    }
}

export default ListTreeRecordView;

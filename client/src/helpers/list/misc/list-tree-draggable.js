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

import {Draggable} from '@shopify/draggable';
import {inject} from 'di';
import Language from 'language';

/**
 * @internal
 */
export default class ListTreeDraggableHelper {

    /**
     * @type {Language}
     */
    @inject(Language)
    language

    /**
     * @private
     * @type {boolean}
     */
    blockDraggable = false

    /**
     * @private
     * {Draggable}
     */
    draggable

    /**
     * @param {import('views/record/list-tree').default} view
     */
    constructor(view) {
        this.view = view;
    }

    destroy() {
        if (this.draggable) {
            this.draggable.destroy();
        }
    }

    init() {
        if (this.draggable) {
            this.draggable.destroy();
        }

        const draggable = this.draggable = new Draggable(this.view.element, {
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

            rows = Array.from(this.view.element.querySelectorAll('.list-group-tree > .list-group-item'));

            levelMap = new Map();

            rows.forEach(row => {
                let depth = 0;
                let current = row;

                while (current && current !== this.view.element) {
                    current = current.parentElement;

                    depth ++;
                }

                levelMap.set(row, depth);
            });

            rows.sort((a, b) => levelMap.get(b) - levelMap.get(a));

            this.view.movedId = event.source.dataset.id;
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

                let itemId = row.dataset.id ?? null;
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

                if (itemId === this.view.movedId) {
                    break;
                }

                if (movedFromLi.contains(row)) {
                    break;
                }

                if (!itemId && !itemParentId) {
                    continue;
                }

                if (itemParentId) {
                    const parent = row.closest(`.list-group-item[data-id]`);

                    if (parent) {
                        /** @type {NodeListOf<HTMLElement>} */
                        const items = parent.querySelectorAll(':scope > .children > .list > .list-group > [data-id]');

                        if (items.length) {
                            itemId = Array.from(items).pop().dataset.id;
                            itemParentId = null;
                        }
                    }
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
            const finalize = () => {
                if (movedLink) {
                    movedLink.classList.remove('text-info');
                }

                rows.forEach(row => {
                    row.classList.remove('border-bottom-highlighted');
                    row.classList.remove('border-top-highlighted');
                });

                rows = undefined;
            };

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

                const movedId = this.view.movedId;
                const affectedId = referenceId;

                Espo.Ui.notifyWait();

                Espo.Ajax
                    .postRequest(`${this.view.entityType}/action/move`, {
                        id: this.view.movedId,
                        referenceId: referenceId,
                        type: moveType,
                    })
                    .then(async () => {
                        const promises = [];

                        if (movedId) {
                            promises.push(this.updateAfter(this.view, movedId));
                        }

                        if (affectedId) {
                            promises.push(this.updateAfter(this.view, affectedId));
                        }

                        await Promise.all(promises);

                        Espo.Ui.success(this.language.translate('Done'));
                    })
                    .finally(() => {
                        this.blockDraggable = false;

                        finalize();
                    });
            }

            if (!moveType) {
                finalize();
            }

            this.view.movedId = null;

            movedHandle = null;
            movedFromLi = null;
            levelMap = undefined;
            overParentId = null;
            overId = null;
            isAfter = false;
            wasOutOfSelf = false;
        });
    }

    /**
     * @private
     * @param {ListTreeRecordView} view
     * @param {string} movedId
     * @return {Promise}
     */
    async updateAfter(view, movedId) {
        if (view.collection.has(movedId)) {
            const unfoldedIds = view.getItemViews()
                .filter(view => view.isUnfolded && view.model)
                .map(view => view.model.id);

            await view.collection.fetch({noRebuild: false});

            view.getItemViews()
                .filter(view => view && view.model && unfoldedIds.includes(view.model.id))
                .forEach(view => view.unfold());

            return;
        }

        for (const subView of view.getItemViews()) {
            if (!subView.getChildrenView()) {
                continue;
            }

            await this.updateAfter(subView.getChildrenView(), movedId);
        }
    }
}


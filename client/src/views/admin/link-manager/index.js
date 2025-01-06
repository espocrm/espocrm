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

/** @module views/admin/link-manager/index */

import View from 'view';
import LinkManagerEditParamsModalView from 'views/admin/link-manager/modals/edit-params';

class LinkManagerIndexView extends View {

    template = 'admin/link-manager/index'

    /**
     * @type {string}
     */
    scope

    data() {
        return {
            linkDataList: this.linkDataList,
            scope: this.scope,
            isCreatable: this.isCustomizable,
        };
    }

    events = {
        /** @this LinkManagerIndexView */
        'click a[data-action="editLink"]': function (e) {
            const link = $(e.currentTarget).data('link');

            this.editLink(link);
        },
        /** @this LinkManagerIndexView */
        'click button[data-action="createLink"]': function () {
            this.createLink();
        },
        /** @this LinkManagerIndexView */
        'click [data-action="removeLink"]': function (e) {
            const link = $(e.currentTarget).data('link');

            const msg = this.translate('confirmRemoveLink', 'messages', 'EntityManager')
                .replace('{link}', link);

            this.confirm(msg, () => {
                this.removeLink(link);
            });
        },
        /** @this LinkManagerIndexView */
        'keyup input[data-name="quick-search"]': function (e) {
            this.processQuickSearch(e.currentTarget.value);
        },
    }

    /**
     *
     * @param {string} type
     * @param {string} foreignType
     * @return {undefined|string}
     */
    computeRelationshipType(type, foreignType) {
        if (type === 'hasMany') {
            if (foreignType === 'hasMany') {
                return 'manyToMany';
            }

            if (foreignType === 'belongsTo') {
                return 'oneToMany';
            }

            return undefined;
        }

        if (type === 'belongsTo') {
            if (foreignType === 'hasMany') {
                return 'manyToOne';
            }

            if (foreignType === 'hasOne') {
                return 'oneToOneRight';
            }

            return undefined;
        }

        if (type === 'belongsToParent') {
            if (foreignType === 'hasChildren') {
                return 'childrenToParent'
            }

            return undefined;
        }

        if (type === 'hasChildren') {
            if (foreignType === 'belongsToParent') {
                return 'parentToChildren'
            }

            return undefined;
        }

        if (type === 'hasOne') {
            if (foreignType === 'belongsTo') {
                return 'oneToOneLeft';
            }

            return undefined;
        }
    }

    setupLinkData() {
        this.linkDataList = [];

        this.isCustomizable =
            !!this.getMetadata().get(`scopes.${this.scope}.customizable`) &&
            this.getMetadata().get(`scopes.${this.scope}.entityManager.relationships`) !== false;

        const links = /** @type {Object.<string, Record>} */
            this.getMetadata().get(`entityDefs.${this.scope}.links`);

        const linkList = Object.keys(links).sort((v1, v2) => {
            return v1.localeCompare(v2);
        });

        linkList.forEach(link => {
            const defs = links[link];

            let type;

            let isEditable = this.isCustomizable;

            if (defs.type === 'belongsToParent') {
                type = 'childrenToParent';
            } else {
                if (!defs.entity) {
                    return;
                }

                if (defs.foreign) {
                    const foreignType = this.getMetadata().get(`entityDefs.${defs.entity}.links.${defs.foreign}.type`);

                    type = this.computeRelationshipType(defs.type, foreignType);
                } else {
                    isEditable = false;

                    if (defs.relationName) {
                        type = 'manyToMany';
                    } else if (defs.type === 'belongsTo') {
                        type = 'manyToOne'
                    }
                }
            }

            const labelEntityForeign = defs.entity ?
                this.getLanguage().translate(defs.entity, 'scopeNames') : undefined;

            const isRemovable = defs.isCustom;

            const hasEditParams = defs.type === 'hasMany' || defs.type === 'hasChildren';

            this.linkDataList.push({
                link: link,
                isCustom: defs.isCustom,
                isRemovable: isRemovable,
                customizable: defs.customizable,
                isEditable: isEditable,
                hasDropdown: isEditable || isRemovable || hasEditParams,
                hasEditParams: hasEditParams,
                type: type,
                entityForeign: defs.entity,
                entity: this.scope,
                labelEntityForeign: labelEntityForeign,
                linkForeign: defs.foreign,
                label: this.getLanguage().translate(link, 'links', this.scope),
                labelForeign: this.getLanguage().translate(defs.foreign, 'links', defs.entity),
            });
        });
    }

    setup() {
        this.addActionHandler('editParams', (e, target) => this.actionEditParams(target.dataset.link));

        this.scope = this.options.scope || null;

        this.setupLinkData();

        this.on('after:render', () => {
            this.renderHeader();
        });
    }

    afterRender() {
        this.$noData = this.$el.find('.no-data');

        this.$el.find('input[data-name="quick-search"]').focus();
    }

    createLink() {
        this.createView('edit', 'views/admin/link-manager/modals/edit', {
            scope: this.scope,
        }, view => {
            view.render();

            this.listenTo(view, 'after:save', () => {
                this.clearView('edit');

                this.setupLinkData();
                this.render();
            });

            this.listenTo(view, 'close', () => {
                this.clearView('edit');
            });
        });
    }

    editLink(link) {
        this.createView('edit', 'views/admin/link-manager/modals/edit', {
            scope: this.scope,
            link: link,
        }, view => {
            view.render();

            this.listenTo(view, 'after:save', () => {
                this.clearView('edit');

                this.setupLinkData();
                this.render();
            });

            this.listenTo(view, 'close', () => {
                this.clearView('edit');
            });
        });
    }

    removeLink(link) {
        Espo.Ajax
            .postRequest('EntityManager/action/removeLink', {
                entity: this.scope,
                link: link,
            })
            .then(() => {
                this.$el.find(`table tr[data-link="${link}"]`).remove();

                this.getMetadata().loadSkipCache().then(() => {
                    this.setupLinkData();

                    Espo.Ui.success(this.translate('Removed'), {suppress: true});

                    this.reRender();
                });
            });
    }

    renderHeader() {
        const $header = $('#scope-header');

        if (!this.scope) {
            $header.html('');

            return;
        }

        $header
            .show()
            .html(this.getLanguage().translate(this.scope, 'scopeNames'));
    }

    updatePageTitle() {
        this.setPageTitle(this.getLanguage().translate('Entity Manager', 'labels', 'Admin'));
    }

    processQuickSearch(text) {
        text = text.trim();

        const $noData = this.$noData;

        $noData.addClass('hidden');

        if (!text) {
            this.$el.find('table tr.link-row').removeClass('hidden');

            return;
        }

        const matchedList = [];

        const lowerCaseText = text.toLowerCase();

        this.linkDataList.forEach(item => {
            let matched = false;

            const label = item.label || '';
            const link = item.link || '';
            const entityForeign = item.entityForeign || '';
            const labelEntityForeign = item.labelEntityForeign || '';

            if (
                label.toLowerCase().indexOf(lowerCaseText) === 0 ||
                link.toLowerCase().indexOf(lowerCaseText) === 0 ||
                entityForeign.toLowerCase().indexOf(lowerCaseText) === 0 ||
                labelEntityForeign.toLowerCase().indexOf(lowerCaseText) === 0
            ) {
                matched = true;
            }

            if (!matched) {
                const wordList = link.split(' ')
                    .concat(
                        label.split(' ')
                    )
                    .concat(
                        entityForeign.split(' ')
                    )
                    .concat(
                        labelEntityForeign.split(' ')
                    );

                wordList.forEach((word) => {
                    if (word.toLowerCase().indexOf(lowerCaseText) === 0) {
                        matched = true;
                    }
                });
            }

            if (matched) {
                matchedList.push(link);
            }
        });

        if (matchedList.length === 0) {
            this.$el.find('table tr.link-row').addClass('hidden');

            $noData.removeClass('hidden');

            return;
        }

        this.linkDataList
            .map(item => item.link)
            .forEach(scope => {
                if (!~matchedList.indexOf(scope)) {
                    this.$el.find(`table tr.link-row[data-link="${scope}"]`).addClass('hidden');

                    return;
                }

                this.$el.find(`table tr.link-row[data-link="${scope}"]`).removeClass('hidden');
            });
    }

    /**
     * @private
     * @param {string} link
     */
    async actionEditParams(link) {
        const view = new LinkManagerEditParamsModalView({
            entityType: this.scope,
            link: link,
        });

        await this.assignView('dialog', view);
        await view.render();
    }
}

export default LinkManagerIndexView;

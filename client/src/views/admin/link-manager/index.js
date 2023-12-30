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

/** @module views/admin/link-manager/index */

import View from 'view';

class LinkManagerIndexView extends View {

    template = 'admin/link-manager/index'

    scope = null

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
            var link = $(e.currentTarget).data('link');

            this.editLink(link);
        },
        /** @this LinkManagerIndexView */
        'click button[data-action="createLink"]': function () {
            this.createLink();
        },
        /** @this LinkManagerIndexView */
        'click [data-action="removeLink"]': function (e) {
            var link = $(e.currentTarget).data('link');
            this.confirm(this.translate('confirmation', 'messages'), function () {
                this.removeLink(link);
            }, this);
        },
        /** @this LinkManagerIndexView */
        'keyup input[data-name="quick-search"]': function (e) {
            this.processQuickSearch(e.currentTarget.value);
        },
    }

    computeRelationshipType(type, foreignType) {
        if (type === 'hasMany') {
            if (foreignType === 'hasMany') {
                return 'manyToMany';
            }
            else if (foreignType === 'belongsTo') {
                return 'oneToMany';
            }
            else {
                return undefined;
            }
        }
        else if (type === 'belongsTo') {
            if (foreignType === 'hasMany') {
                return 'manyToOne';
            }
            else if (foreignType === 'hasOne') {
                return 'oneToOneRight';
            }
            else {
                return undefined;
            }
        }
        else if (type === 'belongsToParent') {
            if (foreignType === 'hasChildren') {
                return 'childrenToParent'
            }

            return undefined;
        }
        else if (type === 'hasChildren') {
            if (foreignType === 'belongsToParent') {
                return 'parentToChildren'
            }

            return undefined;
        }
        else if (type === 'hasOne') {
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

        const links = this.getMetadata().get('entityDefs.' + this.scope + '.links');

        const linkList = Object.keys(links).sort((v1, v2) => {
            return v1.localeCompare(v2);
        });

        linkList.forEach(link => {
            var d = links[link];
            let type;

            var linkForeign = d.foreign;

            if (d.type === 'belongsToParent') {
                type = 'childrenToParent';
            }
            else {
                if (!d.entity) {
                    return;
                }

                if (!linkForeign) {
                    return;
                }

                var foreignType = this.getMetadata()
                    .get('entityDefs.' + d.entity + '.links.' + d.foreign + '.type');

                type = this.computeRelationshipType(d.type, foreignType);
            }

            if (!type) {
                return;
            }

            this.linkDataList.push({
                link: link,
                isCustom: d.isCustom,
                isRemovable: d.isCustom,
                customizable: d.customizable,
                isEditable: this.isCustomizable,
                type: type,
                entityForeign: d.entity,
                entity: this.scope,
                labelEntityForeign: this.getLanguage().translate(d.entity, 'scopeNames'),
                linkForeign: linkForeign,
                label: this.getLanguage().translate(link, 'links', this.scope),
                labelForeign: this.getLanguage().translate(d.foreign, 'links', d.entity),
            });
        });
    }

    setup() {
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
                this.$el.find('table tr[data-link="'+link+'"]').remove();

                this.getMetadata().loadSkipCache().then(() => {
                    this.setupLinkData();

                    Espo.Ui.success(this.translate('Removed'), {suppress: true});

                    this.reRender();
                });
            });
    }

    renderHeader() {
        if (!this.scope) {
            $('#scope-header').html('');

            return;
        }

        $('#scope-header').show().html(this.getLanguage().translate(this.scope, 'scopeNames'));
    }

    updatePageTitle() {
        this.setPageTitle(this.getLanguage().translate('Entity Manager', 'labels', 'Admin'));
    }

    processQuickSearch(text) {
        text = text.trim();

        let $noData = this.$noData;

        $noData.addClass('hidden');

        if (!text) {
            this.$el.find('table tr.link-row').removeClass('hidden');

            return;
        }

        let matchedList = [];

        let lowerCaseText = text.toLowerCase();

        this.linkDataList.forEach(item => {
            let matched = false;

            let label = item.label || '';
            let link = item.link || '';
            let entityForeign = item.entityForeign || '';
            let labelEntityForeign = item.labelEntityForeign || '';

            if (
                label.toLowerCase().indexOf(lowerCaseText) === 0 ||
                link.toLowerCase().indexOf(lowerCaseText) === 0 ||
                entityForeign.toLowerCase().indexOf(lowerCaseText) === 0 ||
                labelEntityForeign.toLowerCase().indexOf(lowerCaseText) === 0
            ) {
                matched = true;
            }

            if (!matched) {
                let wordList = link.split(' ')
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
                    this.$el.find('table tr.link-row[data-link="'+scope+'"]').addClass('hidden');

                    return;
                }

                this.$el.find('table tr.link-row[data-link="'+scope+'"]').removeClass('hidden');
            });
    }
}

export default LinkManagerIndexView;

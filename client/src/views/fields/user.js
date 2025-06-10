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

import LinkFieldView from 'views/fields/link';
import Autocomplete from 'ui/autocomplete';

class UserFieldView extends LinkFieldView {

    searchTemplate = 'fields/user/search'
    linkClass = 'text-default'

    setupSearch() {
        super.setupSearch();

        this.searchTypeList = Espo.Utils.clone(this.searchTypeList);
        this.searchTypeList.push('isFromTeams');

        this.searchData.teamIdList = this.getSearchParamsData().teamIdList ||
            this.searchParams.teamIdList || [];
        this.searchData.teamNameHash = this.getSearchParamsData().teamNameHash ||
            this.searchParams.teamNameHash || {};

        this.events['click a[data-action="clearLinkTeams"]'] = e => {
            const id = $(e.currentTarget).data('id').toString();

            this.deleteLinkTeams(id);
        };

        this.addActionHandler('selectLinkTeams', () => this.actionSelectLinkTeams());

        this.events['click a[data-action="clearLinkTeams"]'] = e => {
            const id = $(e.currentTarget).data('id').toString();

            this.deleteLinkTeams(id);
        };
    }

    getSelectPrimaryFilterName() {
        return 'active';
    }

    /**
     * @protected
     */
    async actionSelectLinkTeams() {
        const viewName = this.getMetadata().get('clientDefs.Team.modalViews.select') ||
            'views/modals/select-records';

        /** @type {module:views/modals/select-records~Options} */
        const options = {
            entityType: 'Team',
            createButton: false,
            multiple: true,
            onSelect: models => {
                models.forEach(model => this.addLinkTeams(model.id, model.attributes.name));
            },
        };

        Espo.Ui.notifyWait();

        const view = await this.createView('modal', viewName, options);

        await view.render();
    }

    handleSearchType(type) {
        super.handleSearchType(type);

        if (type === 'isFromTeams') {
            this.$el.find('div.teams-container').removeClass('hidden');
        } else {
            this.$el.find('div.teams-container').addClass('hidden');
        }
    }

    afterRender() {
        super.afterRender();

        if (this.mode === this.MODE_SEARCH) {
            const $elementTeams = this.$el.find('input.element-teams');

            /** @type {module:ajax.AjaxPromise & Promise<any>} */
            let lastAjaxPromise;

            const autocomplete = new Autocomplete($elementTeams.get(0), {
                minChars: 1,
                focusOnSelect: true,
                handleFocusMode: 3,
                autoSelectFirst: true,
                forceHide: true,
                onSelect: item => {
                    this.addLinkTeams(item.id, item.name);

                    $elementTeams.val('');
                },
                lookupFunction: query => {
                    if (lastAjaxPromise && lastAjaxPromise.getReadyState() < 4) {
                        lastAjaxPromise.abort();
                    }

                    lastAjaxPromise = Espo.Ajax
                        .getRequest('Team', {
                            maxSize: this.getAutocompleteMaxCount(),
                            select: 'id,name',
                            q: query,
                        });

                    return lastAjaxPromise.then(/** {list: Record[]} */response => {
                        return response.list.map(item => ({
                            id: item.id,
                            name: item.name,
                            data: item.id,
                            value: item.name,
                        }));
                    });
                },
            });

            this.once('render remove', () => autocomplete.dispose());

            const type = this.$el.find('select.search-type').val();

            if (type === 'isFromTeams') {
                this.searchData.teamIdList.forEach(id => {
                    this.addLinkTeamsHtml(id, this.searchData.teamNameHash[id]);
                });
            }
        }
    }

    deleteLinkTeams(id) {
        this.deleteLinkTeamsHtml(id);

        const index = this.searchData.teamIdList.indexOf(id);

        if (index > -1) {
            this.searchData.teamIdList.splice(index, 1);
        }

        delete this.searchData.teamNameHash[id];

        this.trigger('change');
    }

    addLinkTeams(id, name) {
        this.searchData.teamIdList = this.searchData.teamIdList || [];

        if (!~this.searchData.teamIdList.indexOf(id)) {
            this.searchData.teamIdList.push(id);
            this.searchData.teamNameHash[id] = name;
            this.addLinkTeamsHtml(id, name);

            this.trigger('change');
        }
    }

    deleteLinkTeamsHtml(id) {
        this.$el.find('.link-teams-container .link-' + id).remove();
    }

    addLinkTeamsHtml(id, name) {
        id = this.getHelper().escapeString(id);
        name = this.getHelper().escapeString(name);

        const $container = this.$el.find('.link-teams-container');

        const $el = $('<div />')
            .addClass('link-' + id)
            .addClass('list-group-item');

        $el.html(name + '&nbsp');

        $el.prepend(
            '<a role="button" class="pull-right" data-id="' + id + '" ' +
            'data-action="clearLinkTeams"><span class="fas fa-times"></a>'
        );

        $container.append($el);

        return $el;
    }

    fetchSearch() {
        const type = this.$el.find('select.search-type').val();

        if (type === 'isFromTeams') {
            return {
                type: 'isUserFromTeams',
                field: this.name,
                value: this.searchData.teamIdList,
                data: {
                    type: type,
                    teamIdList: this.searchData.teamIdList,
                    teamNameHash: this.searchData.teamNameHash,
                },
            };
        }

        return super.fetchSearch();
    }
}

export default UserFieldView;

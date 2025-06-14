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

import View from 'view';
import ListRecordView from 'views/record/list';
import ReactionsHelper from 'helpers/misc/reactions';

export default class NoteReactionsView extends View {

    // language=Handlebars
    templateContent = `
        {{#each dataList}}
            <a
                class="reaction-count small text-soft"
                role="button"
                title="{{label}}"
                data-type="{{type}}"
            >
                <span data-role="icon" class="{{iconClass}} {{#if reacted}} text-warning {{/if}}"></span>
                <span data-role="count">{{count}}</span>
            </a>
        {{/each}}
    `

    /**
     * @private
     * @type {string[]}
     */
    availableReactions

    /**
     * @type {Object.<string, string>}
     * @private
     */
    iconClassMap

    /**
     * @private
     * @type {{destroy: function(), show: function()}}
     */
    popover

    /**
     * @param {{
     *     model: import('model').default,
     * }} options
     */
    constructor(options) {
        super(options);
    }

    data() {
        /** @type {Record.<string, number>} */
        const counts = this.model.attributes.reactionCounts || {};
        /** @type {string[]} */
        const myReactions = this.model.attributes.myReactions || [];

        return {
            dataList: this.availableReactions
                .filter(type => counts[type])
                .map(type => {
                    return {
                        type: type,
                        count: counts[type].toString(),
                        label: this.translate('Reactions') +  ' · ' + this.translate(type, 'reactions'),
                        iconClass: this.iconClassMap[type],
                        reacted: myReactions.includes(type),
                    };
                }),
        }
    }

    setup() {
        const reactionsHelper = new ReactionsHelper();

        this.availableReactions = reactionsHelper.getAvailableReactions();

        const list = reactionsHelper.getDefinitionList();

        this.iconClassMap = list.reduce((o, it) => {
            o[it.type] = it.iconClass;

            return o;
        }, {});

        this.addHandler('click', 'a.reaction-count', (e, target) => this.showUsers(target.dataset.type));
    }

    /**
     * @private
     * @param {string} type
     */
    async showUsers(type) {
        const a = this.element.querySelector(`a.reaction-count[data-type="${type}"]`);

        /*if (this.popover) {
            this.popover.destroy();
        }*/

        const popover = Espo.Ui.popover(a, {
            placement: 'bottom',
            content: `
                <div class="center-align for-list-view">
                    <span class="fas fa-spinner fa-spin text-soft"></span>
                </div>
            `,
            preventDestroyOnRender: true,
            noToggleInit: true,
            keepElementTitle: true,
            title: this.translate('Reactions') +  ' · ' + this.translate(type, 'reactions'),
            onHide: () => {
                this.popover = undefined;

                this.trigger('popover-hidden');
            },
        }, this);

        this.popover = popover;

        const id = popover.show();

        document.querySelector(`#${id}`).classList.add('popover-list-view');

        const selector = `#${id} .popover-content`;

        /** @type {HTMLElement|null} */
        const container = document.querySelector(selector);

        /** @type {import('collection').default} */
        const users = await this.getCollectionFactory().create('User');

        users.url = `Note/${this.model.id}/reactors/${type}`;
        users.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;

        await users.fetch();

        if (!document.body.contains(container)) {
            popover.hide();

            return;
        }

        const listView = new ListRecordView({
            collection: users,
            listLayout: [
                {
                    name: 'name',
                    view: 'views/user/fields/name',
                    link: true,
                }
            ],
            checkboxes: false,
            displayTotalCount: false,
            headerDisabled: true,
            buttonsDisabled: true,
            rowActionsDisabled: true,
        });

        await this.assignView('users', listView);

        listView.setSelector(selector);

        await listView.render();

        this.listenToOnce(listView, 'modal-shown', () => popover.destroy());
    }

    // @todo Prevent popover disappearing.
    reRenderWhenNoPopover() {
        if (this.popover) {
            this.once('popover-hidden', () => this.reRender());

            return;
        }

        this.reRender();
    }
}

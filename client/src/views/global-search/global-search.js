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

import Autocomplete from 'ui/autocomplete';
import TabsHelper from 'helpers/site/tabs';
import SiteNavbarItemView from 'views/site/navbar/item';

/** @module views/global-search/global-search */

class GlobalSearchView extends SiteNavbarItemView {

    template = 'global-search/global-search'

    /**
     * @private
     * @type {HTMLElement}
     */
    containerElement

    /**
     * @private
     * @type {HTMLInputElement}
     */
    inputElement

    /**
     * @private
     * @type {boolean}
     */
    tabQuickSearch

    /**
     * @private
     * @type {boolean}
     */
    hasGlobalSearch

    /**
     * @private
     * @type {TabsHelper}
     */
    tabsHelper

    /**
     * @private
     * @type {Autocomplete}
     */
    autocomplete

    /**
     * @private
     * @type {module:views/global-search/global-search~tabData[]}
     */
    tabDataList

    data() {
        return {
            hasSearchButton: this.hasGlobalSearch,
        };
    }

    setup() {
        this.addHandler('keydown', 'input.global-search-input', 'onKeydown');
        this.addHandler('focus', 'input.global-search-input', 'onFocus');
        this.addHandler('click', '[data-action="search"]', () => this.runSearch());

        const promise = this.getCollectionFactory().create('GlobalSearch', collection => {
            this.collection = collection;
            this.collection.url = 'GlobalSearch';
        });

        this.wait(promise);

        this.closeNavbarOnShow = /iPad|iPhone|iPod/.test(navigator.userAgent);

        this.onMouseUpBind = this.onMouseUp.bind(this);
        this.onClickBind = this.onClick.bind(this);

        this.tabQuickSearch = this.getConfig().get('tabQuickSearch') || false;
        this.hasGlobalSearch = (this.getConfig().get('globalSearchEntityList') || []).length > 0;

        this.tabsHelper = new TabsHelper(
            this.getConfig(),
            this.getPreferences(),
            this.getUser(),
            this.getAcl(),
            this.getMetadata(),
            this.getLanguage()
        );

        this.tabDataList = this.getTabDataList();
    }

    /**
     * @param {MouseEvent} e
     * @private
     */
    onFocus(e) {
        const inputElement = /** @type {HTMLInputElement} */e.target;

        inputElement.select();
    }

    /**
     * @param {KeyboardEvent} e
     * @private
     */
    onKeydown(e) {
        if (!this.hasGlobalSearch) {
            return;
        }

        const key = Espo.Utils.getKeyFromKeyEvent(e);

        if (e.key === 'Enter' || key === 'Enter' || key === 'Control+Enter') {
            this.runSearch();

            return;
        }

        if (key === 'Escape') {
            this.closePanel();
        }
    }

    afterRender() {
        this.$input = this.$el.find('input.global-search-input');

        this.inputElement = this.$input.get(0);

        if (this.tabQuickSearch) {
            this.autocomplete = new Autocomplete(this.inputElement, {
                minChars: 1,
                lookupFunction: async query => {
                    const lower = query.toLowerCase();

                    return this.tabDataList
                        .filter(it => {
                            if (it.words.find(word => word.startsWith(lower))) {
                                return true;
                            }

                            if (it.lowerLabel.toLowerCase().startsWith(lower)) {
                                return true;
                            }

                            return false;
                        })
                        .sort((a, b) => {
                            if (
                                a.lowerLabel.startsWith(lower) &&
                                !b.lowerLabel.startsWith(lower)
                            ) {
                                return -1;
                            }

                            if (
                                !a.lowerLabel.startsWith(lower) &&
                                b.lowerLabel.startsWith(lower)
                            ) {
                                return 1;
                            }

                            const lengthDiff = a.lowerLabel.length - b.lowerLabel.length;

                            if (lengthDiff !== 0) {
                                return lengthDiff;
                            }

                            return a.lowerLabel.localeCompare(b.lowerLabel);
                        })
                        .map(it => ({
                            value: it.label,
                            url: it.url,
                        }));
                },
                formatResult: /** {value: string, url: string} */item => {
                    const a = document.createElement('a');

                    a.text = item.value;
                    a.href = item.url;
                    a.classList.add('text-default');

                    return a.outerHTML;
                },
                onSelect: /** {value: string, url: string} */item => {
                    window.location.href =item.url;

                    this.inputElement.value = '';
                },
            });

            this.once('render remove', () => {
                this.autocomplete.dispose();
                this.autocomplete = undefined;
            });
        }
    }

    /**
     * @private
     */
    runSearch() {
        const text = this.$input.val().trim();

        if (text !== '' && text.length >= 2) {
            this.search(text);
        }
    }

    /**
     * @private
     * @param {string} text
     */
    search(text) {
        this.collection.url = this.collection.urlRoot = 'GlobalSearch?q=' + encodeURIComponent(text);

        this.showPanel();
    }

    /**
     * @param {MouseEvent} e
     * @private
     */
    onMouseUp(e) {
        if (e.button !== 0) {
            return;
        }

        const target = e.target;

        if (!(target instanceof HTMLElement)) {
            return;
        }

        if (
            this.containerElement === target ||
            this.containerElement.contains(target) ||
            target.classList.contains('modal') ||
            target.closest('.dialog.modal')
        ) {
            return;
        }

        return this.closePanel();
    }

    /**
     * @param {MouseEvent} e
     * @private
     */
    onClick(e) {
        const target = e.target;

        if (!(target instanceof HTMLAnchorElement)) {
            return;
        }

        if (
            target.dataset.action === 'showMore' ||
            target.classList.contains('global-search-button')
        ) {
            return;
        }

        setTimeout(() => this.closePanel(), 100);
    }

    /**
     * @private
     */
    showPanel() {
        this.closePanel();

        if (this.autocomplete) {
            this.autocomplete.hide();
        }

        if (this.closeNavbarOnShow) {
            this.$el.closest('.navbar-body').removeClass('in');
        }

        const $container = this.$container = $('<div>').attr('id', 'global-search-panel');

        this.containerElement = this.$container.get(0);

        $container.appendTo(this.$el.find('.global-search-panel-container'));

        this.createView('panel', 'views/global-search/panel', {
            fullSelector: '#global-search-panel',
            collection: this.collection,
        }, view => {
            view.render();

            this.listenToOnce(view, 'close', this.closePanel);
        });

        document.addEventListener('mouseup', this.onMouseUpBind);
        document.addEventListener('click', this.onClickBind);
    }

    /**
     * @private
     */
    closePanel() {
        const $container = $('#global-search-panel');

        $container.remove();

        if (this.hasView('panel')) {
            this.getView('panel').remove();
        }

        document.removeEventListener('mouseup', this.onMouseUpBind);
        document.removeEventListener('click', this.onClickBind);
    }

    /**
     * @typedef {Object} module:views/global-search/global-search~tabData
     * @property {string} url
     * @property {string} label
     * @property {string} lowerLabel
     * @property {string[]} words
     */

    /**
     * @private
     * @return {module:views/global-search/global-search~tabData[]}
     */
    getTabDataList() {
        /** @type {module:views/global-search/global-search~tabData[]}*/
        let list = [];

        /**
         * @param {string|TabsHelper~item} item
         * @return {module:views/global-search/global-search~tabData}
         */
        const toData = (item) => {
            const label = this.tabsHelper.getTranslatedTabLabel(item);

            const url = this.tabsHelper.isTabScope(item) ? `#${item}` : item.url;

            return {
                url: url,
                label: label,
                words: label.split(' ').map(it => it.toLowerCase()),
                lowerLabel: label.toLowerCase(),
            };
        };

        /**
         * @param {string|TabsHelper~item} item
         * @return {boolean}
         */
        const checkTab = (item) => {
            return (this.tabsHelper.isTabScope(item) || this.tabsHelper.isTabUrl(item)) &&
                this.tabsHelper.checkTabAccess(item);
        }

        for (const item of this.tabsHelper.getTabList()) {
            if (checkTab(item)) {
                list.push(toData(item));

                continue;
            }

            if (this.tabsHelper.isTabGroup(item) && item.itemList) {
                for (const subItem of item.itemList) {
                    if (checkTab(subItem)) {
                        list.push(toData(subItem));
                    }
                }
            }
        }

        if (this.getUser().isAdmin()) {
            /** @type {
             *     Record<string, {
             *         order?: number,
             *         itemList: {
             *             url: string,
             *             tabQuickSearch: boolean,
             *             label: string,
             *         }[]
             *     }>
             * } panels */
            const panels = this.getMetadata().get(`app.adminPanel`) || {};

            Object.entries(panels)
                .map(it => it[1])
                .sort((a, b) => a.order - b.order)
                .filter(it => it.itemList) // For bc.
                .forEach(it => {
                    it.itemList
                        .filter(it => it.tabQuickSearch && it.label)
                        .filter(it => !list.find(subIt => subIt.url === it.url))
                        .forEach(it => {
                            const label = this.translate(it.label, 'labels', 'Admin');

                            list.push({
                                label: this.translate(it.label, 'labels', 'Admin'),
                                url: it.url,
                                lowerLabel: label.toLowerCase(),
                                words: label.split(' ').map(it => it.toLowerCase()),
                            });
                        });
                });
        }

        list = list
            .filter((it, i) => {
                return list.findIndex(subIt => subIt.url === it.url) === i &&
                    list.findIndex(subIt => subIt.lowerLabel === it.lowerLabel) === i
            });

        /** @type {Record<string, {tab: boolean}>} */
        const scopes = this.getMetadata().get('scopes') || {};

        Object.entries(scopes)
            .filter(([scope, it]) => it.tab && checkTab(scope))
            .forEach(([scope]) => {
                const data = toData(scope);

                if (list.find(it => it.lowerLabel === data.lowerLabel)) {
                    return;
                }

                list.push(data);
            });

        return list.filter((item, index) => list.findIndex(it => it.lowerLabel === item.lowerLabel) === index);
    }

    isAvailable() {
        if (this.tabQuickSearch && !this.getUser().isPortal()) {
            return true;
        }

        let isAvailable = false;

        /** @type {string[]} */
        const entityTypeList = this.getConfig().get('globalSearchEntityList') || [];

        for (const it of entityTypeList) {
            if (this.getAcl().checkScope(it)) {
                isAvailable = true;

                break;
            }
        }

        return isAvailable;
    }
}

export default GlobalSearchView;

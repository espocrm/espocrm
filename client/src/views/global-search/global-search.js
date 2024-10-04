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

import View from 'view';

class GlobalSearchView extends View {

    template = 'global-search/global-search'

    /**
     * @private
     * @type {HTMLElement}
     */
    containerElement

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
    }

    /**
     * @param {MouseEvent} e
     */
    onFocus(e) {
        const inputElement = /** @type {HTMLInputElement} */e.target;

        inputElement.select();
    }

    /**
     * @param {KeyboardEvent} e
     */
    onKeydown(e) {
        const key = Espo.Utils.getKeyFromKeyEvent(e);

        if (e.code === 'Enter' || key === 'Enter' || key === 'Control+Enter') {
            this.runSearch();

            return;
        }

        if (key === 'Escape') {
            this.closePanel();
        }
    }

    afterRender() {
        this.$input = this.$el.find('input.global-search-input');
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

        if (
            this.containerElement !== e.target &&
            !this.containerElement.contains(e.target)
        ) {
            return this.closePanel();
        }
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
}

export default GlobalSearchView;

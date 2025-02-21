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

/** @module views/dashlet */

import View from 'view'

/**
 * A dashlet container view.
 */
class DashletView extends View {

    /** @inheritDoc */
    template = 'dashlet'

    /**
     * A dashlet name.
     *
     * @type {string}
     */
    name

    /**
     * A dashlet ID.
     *
     * @type {string}
     */
    id

    /**
     * An options view name.
     *
     * @protected
     * @type {string|null}
     */
    optionsView = null

    /** @inheritDoc */
    data() {
        const bodyView = this.getBodyView();

        return {
            name: this.name,
            id: this.id,
            title: this.getTitle(),
            actionList: bodyView ? bodyView.getActionItemDataList() : [],
            buttonList: bodyView ? bodyView.buttonList : [],
            noPadding: bodyView ? bodyView.noPadding : false,
            color: bodyView ? bodyView.getColor() : null,
        };
    }

    /** @inheritDoc */
    events = {
        /** @this DashletView */
        'click .action': function (e) {
            const isHandled = Espo.Utils.handleAction(this, e.originalEvent, e.currentTarget);

            if (isHandled) {
                return;
            }

            this.getBodyView().handleAction(e.originalEvent, e.currentTarget);
        },
        /** @this DashletView */
        'mousedown .panel-heading .dropdown-menu': function (e) {
            // Prevent dragging.
            e.stopPropagation();
        },
        /** @this DashletView */
        'shown.bs.dropdown .panel-heading .btn-group': function (e) {
            this.controlDropdownShown($(e.currentTarget).parent());
        },
        /** @this DashletView */
        'hide.bs.dropdown .panel-heading .btn-group': function () {
            this.controlDropdownHide();
        },
    }

    controlDropdownShown($dropdownContainer) {
        const $panel = this.$el.children().first();

        const dropdownBottom = $dropdownContainer.find('.dropdown-menu')
            .get(0).getBoundingClientRect().bottom;

        const panelBottom = $panel.get(0).getBoundingClientRect().bottom;

        if (dropdownBottom < panelBottom) {
            return;
        }

        $panel.addClass('has-dropdown-opened');
    }

    controlDropdownHide() {
        this.$el.children().first().removeClass('has-dropdown-opened');
    }

    /** @inheritDoc */
    setup() {
        this.name = this.options.name;
        this.id = this.options.id;

        this.on('resize', () => {
            const bodyView = this.getView('body');

            if (!bodyView) {
                return;
            }

            bodyView.trigger('resize');
        });

        const viewName = this.getMetadata().get(['dashlets', this.name, 'view']) ||
            'views/dashlets/' + Espo.Utils.camelCaseToHyphen(this.name);

        this.createView('body', viewName, {
            selector: '.dashlet-body',
            id: this.id,
            name: this.name,
            readOnly: this.options.readOnly,
            locked: this.options.locked,
        });
    }

    /**
     * Refresh.
     */
    refresh() {
        this.getBodyView().actionRefresh();
    }

    actionRefresh() {
        this.refresh();
    }

    actionOptions() {
        const optionsView =
            this.getMetadata().get(['dashlets', this.name, 'options', 'view']) ||
            this.optionsView ||
            'views/dashlets/options/base';

        Espo.Ui.notifyWait();

        this.createView('options', optionsView, {
            name: this.name,
            optionsData: this.getOptionsData(),
            fields: this.getBodyView().optionsFields,
        }, view => {
            view.render();

            Espo.Ui.notify(false);

            this.listenToOnce(view, 'save', (attributes) => {
                const id = this.id;

                Espo.Ui.notify(this.translate('saving', 'messages'));

                this.getPreferences().once('sync', () => {
                    this.getPreferences().trigger('update');

                    Espo.Ui.notify(false);

                    view.close();
                    this.trigger('change');
                });

                const o = this.getPreferences().get('dashletsOptions') || {};

                o[id] = attributes;

                this.getPreferences().save({dashletsOptions: o}, {patch: true});
            });
        });
    }

    /**
     * Get options data.
     *
     * @returns {Object}
     */
    getOptionsData() {
        return this.getBodyView().optionsData;
    }

    /**
     * Get an option value.
     *
     * @param {string} key A option name.
     * @returns {*}
     */
    getOption(key) {
        return this.getBodyView().getOption(key);
    }

    /**
     * Get a dashlet title.
     *
     * @returns {string}
     */
    getTitle() {
        return this.getBodyView().getTitle();
    }

    /**
     * @return {module:views/dashlets/abstract/base}
     */
    getBodyView() {
        return this.getView('body');
    }

    // noinspection JSUnusedGlobalSymbols
    actionRemove() {
        this.confirm(this.translate('confirmation', 'messages'), () => {
            this.trigger('remove-dashlet');
            this.$el.remove();
            this.remove();
        });
    }
}

export default DashletView;

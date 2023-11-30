/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

/** @module views/fields/text */

import BaseFieldView from 'views/fields/base';

/**
 * A text field.
 */
class TextFieldView extends BaseFieldView {

    type = 'text'

    listTemplate = 'fields/text/list'
    detailTemplate = 'fields/text/detail'
    editTemplate = 'fields/text/edit'
    searchTemplate = 'fields/text/search'

    seeMoreText = false
    rowsDefault = 50000
    rowsMin = 2
    seeMoreDisabled = false
    cutHeight = 200
    noResize = false
    changeInterval = 5

    searchTypeList = [
        'contains',
        'startsWith',
        'equals',
        'endsWith',
        'like',
        'notContains',
        'notLike',
        'isEmpty',
        'isNotEmpty',
    ]

    events = {
        /** @this TextFieldView */
        'click a[data-action="seeMoreText"]': function () {
            this.seeMoreText = true;

            this.reRender();
        },
        /** @this TextFieldView */
        'click [data-action="mailTo"]': function (e) {
            this.mailTo($(e.currentTarget).data('email-address'));
        },
    }

    setup() {
        super.setup();

        this.params.rows = this.params.rows || this.rowsDefault;
        this.noResize = this.options.noResize || this.params.noResize || this.noResize;
        this.seeMoreDisabled = this.seeMoreDisabled || this.params.seeMoreDisabled;
        this.autoHeightDisabled = this.options.autoHeightDisabled || this.params.autoHeightDisabled ||
            this.autoHeightDisabled;

        if (this.params.cutHeight) {
            this.cutHeight = this.params.cutHeight;
        }

        this.rowsMin = this.options.rowsMin || this.params.rowsMin || this.rowsMin;

        if (this.params.rows < this.rowsMin) {
            this.rowsMin = this.params.rows;
        }

        this.on('remove', () => {
            $(window).off('resize.see-more-' + this.cid);
        });
    }

    setupSearch() {
        this.events['change select.search-type'] = e => {
            let type = $(e.currentTarget).val();

            this.handleSearchType(type);
        };
    }

    data() {
        let data = super.data();

        if (
            this.model.get(this.name) !== null &&
            this.model.get(this.name) !== '' &&
            this.model.has(this.name)
        ) {
            data.isNotEmpty = true;
        }

        if (this.mode === this.MODE_SEARCH) {
            if (typeof this.searchParams.value === 'string') {
                this.searchData.value = this.searchParams.value;
            }
        }

        if (this.mode === this.MODE_EDIT) {
            if (this.autoHeightDisabled) {
                data.rows = this.params.rows;
            } else {
                data.rows = this.rowsMin;
            }
        }

        data.valueIsSet = this.model.has(this.name);

        if (this.isReadMode()) {
            data.isCut = this.isCut();

            if (data.isCut) {
                data.cutHeight = this.cutHeight;
            }

            data.displayRawText = this.params.displayRawText;
        }

        data.noResize = this.noResize;

        return data;
    }

    handleSearchType(type) {
        if (~['isEmpty', 'isNotEmpty'].indexOf(type)) {
            this.$el.find('input.main-element').addClass('hidden');
        } else {
            this.$el.find('input.main-element').removeClass('hidden');
        }
    }

    getValueForDisplay() {
        let text = this.model.get(this.name);

        return text || '';
    }

    controlTextareaHeight(lastHeight) {
        const scrollHeight = this.$element.prop('scrollHeight');
        const clientHeight = this.$element.prop('clientHeight');

        if (typeof lastHeight === 'undefined' && clientHeight === 0) {
            setTimeout(this.controlTextareaHeight.bind(this), 10);

            return;
        }

        if (clientHeight === lastHeight) {
            return;
        }

        if (scrollHeight > clientHeight + 1) {
            const rows = this.$element.prop('rows');

            if (this.params.rows && rows >= this.params.rows) {
                return;
            }

            this.$element.attr('rows', rows + 1);
            this.controlTextareaHeight(clientHeight);
        }

        if (this.$element.val().length === 0) {
            this.$element.attr('rows', this.rowsMin);
        }
    }

    isCut() {
        return !this.seeMoreText && !this.seeMoreDisabled;
    }

    controlSeeMore() {
        if (!this.isCut()) {
            return;
        }

        if (this.$text.height() > this.cutHeight) {
            this.$seeMoreContainer.removeClass('hidden');
            this.$textContainer.addClass('cut');
        } else {
            this.$seeMoreContainer.addClass('hidden');
            this.$textContainer.removeClass('cut');
        }
    }

    afterRender() {
        super.afterRender();

        if (this.isReadMode()) {
            $(window).off('resize.see-more-' + this.cid);

            this.$textContainer = this.$el.find('> .complex-text-container');
            this.$text = this.$textContainer.find('> .complex-text');
            this.$seeMoreContainer = this.$el.find('> .see-more-container');

            if (this.isCut()) {
                this.controlSeeMore();

                if (this.model.get(this.name) && this.$text.height() === 0) {
                    this.$textContainer.addClass('cut');

                    setTimeout(this.controlSeeMore.bind(this), 50);
                }

                this.listenTo(this.recordHelper, 'panel-show', () => this.controlSeeMore());
                this.on('panel-show-propagated', () => this.controlSeeMore());

                $(window).on('resize.see-more-' + this.cid, () => {
                    this.controlSeeMore();
                });
            }
        }

        if (this.mode === this.MODE_EDIT) {
            var text = this.getValueForDisplay();
            if (text) {
                this.$element.val(text);
            }
        }

        if (this.mode === this.MODE_SEARCH) {
            var type = this.$el.find('select.search-type').val();

            this.handleSearchType(type);

            this.$el.find('select.search-type').on('change', () => {
                this.trigger('change');
            });

            this.$element.on('input', () => {
                this.trigger('change');
            });
        }

        if (this.mode === this.MODE_EDIT && !this.autoHeightDisabled) {
            if (!this.autoHeightDisabled) {
                this.controlTextareaHeight();

                this.$element.on('input', () => this.controlTextareaHeight());
            }

            let lastChangeKeydown = new Date();
            const changeKeydownInterval = this.changeInterval * 1000;

            this.$element.on('keydown', () => {
                if (Date.now() - lastChangeKeydown > changeKeydownInterval) {
                    this.trigger('change');
                    lastChangeKeydown = Date.now();
                }
            });
        }
    }

    fetch() {
        let data = {};

        let value = this.$element.val() || null;

        if (value && value.trim() === '') {
            value = '';
        }

        data[this.name] = value

        return data;
    }

    fetchSearch() {
        let type = this.fetchSearchType() || 'startsWith';

        if (type === 'isEmpty') {
            return  {
                type: 'or',
                value: [
                    {
                        type: 'isNull',
                        field: this.name,
                    },
                    {
                        type: 'equals',
                        field: this.name,
                        value: ''
                    }
                ],
                data: {
                    type: type,
                },
            };
        }

        if (type === 'isNotEmpty') {
            return  {
                type: 'and',
                value: [
                    {
                        type: 'notEquals',
                        field: this.name,
                        value: '',
                    },
                    {
                        type: 'isNotNull',
                        field: this.name,
                        value: null,
                    }
                ],
                data: {
                    type: type,
                },
            };
        }

        let value = this.$element.val().toString().trim();

        if (value) {
            return {
                value: value,
                type: type,
            };
        }

        return false;
    }

    getSearchType() {
        return this.getSearchParamsData().type || this.searchParams.typeFront ||
            this.searchParams.type;
    }

    mailTo(emailAddress) {
        let attributes = {
            status: 'Draft',
            to: emailAddress,
        };

        if (
            this.getConfig().get('emailForceUseExternalClient') ||
            this.getPreferences().get('emailUseExternalClient') ||
            !this.getAcl().checkScope('Email', 'create')
        ) {
            Espo.loader.require('email-helper', EmailHelper => {
                let emailHelper = new EmailHelper();

                document.location.href = emailHelper
                    .composeMailToLink(attributes, this.getConfig().get('outboundEmailBccAddress'));
            });

            return;
        }

        let viewName = this.getMetadata().get('clientDefs.' + this.scope + '.modalViews.compose') ||
            'views/modals/compose-email';

        Espo.Ui.notify(' ... ');

        this.createView('quickCreate', viewName, {
            attributes: attributes,
        }, view => {
            view.render();
            view.notify(false);
        });
    }
}

export default TextFieldView;

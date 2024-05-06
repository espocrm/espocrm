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

import ModalView from 'views/modal';
import EditForModal from 'views/record/edit-for-modal';
import Model from 'model';
import EnumFieldView from 'views/fields/enum';
import VarcharFieldView from 'views/fields/varchar';
import ColorpickerFieldView from 'views/fields/colorpicker';

class EditTableModalView extends ModalView {

    templateContent = `
        <div class="record no-side-margin">{{{record}}}</div>
    `
    /**
     * @param {{
     *     params: {
     *         align: null|'left'|'center'|'right',
     *         width: null|string,
     *         borderWidth: null|string,
     *         borderColor: null|string,
     *         cellPadding: null|string,
     *         backgroundColor: null|string,
     *    },
     *    onApply: function({
     *         align: null|'left'|'center'|'right',
     *         width: null|string,
     *         borderWidth: null|string,
     *         borderColor: null|string,
     *         cellPadding: null|string,
     *         backgroundColor: null|string,
     *    }),
     * }} options
     */
    constructor(options) {
        super(options);

        this.params = options.params;
        this.onApply = options.onApply;
    }

    setup() {
        this.addButton({
            name: 'apply',
            style: 'primary',
            label: 'Apply',
            onClick: () => this.apply(),
        });

        this.addButton({
            name: 'cancel',
            label: 'Cancel',
            onClick: () => this.close(),
        });

        this.shortcutKeys = {
            'Control+Enter': () => this.apply(),
        };

        this.model = new Model({
            align: this.params.align,
            width: this.params.width,
            borderWidth: this.params.borderWidth,
            borderColor: this.params.borderColor,
            cellPadding: this.params.cellPadding,
            backgroundColor: this.params.backgroundColor,
        });

        this.recordView = new EditForModal({
            model: this.model,
            detailLayout: [
                {
                    rows: [
                        [
                            {
                                view: new EnumFieldView({
                                    name: 'align',
                                    labelText: this.translate('align', 'wysiwygLabels'),
                                    params: {
                                        options: [
                                            '',
                                            'left',
                                            'center',
                                            'right',
                                        ],
                                        translation: 'Global.wysiwygOptions.align',
                                    },
                                }),
                            },
                            {
                                view: new VarcharFieldView({
                                    name: 'width',
                                    labelText: this.translate('width', 'wysiwygLabels'),
                                    params: {
                                        maxLength: 12,
                                    },
                                }),
                            },
                        ],
                        [
                            {
                                view: new VarcharFieldView({
                                    name: 'borderWidth',
                                    labelText: this.translate('borderWidth', 'wysiwygLabels'),
                                    params: {
                                        maxLength: 12,
                                    },
                                }),
                            },
                            {
                                view: new ColorpickerFieldView({
                                    name: 'borderColor',
                                    labelText: this.translate('borderColor', 'wysiwygLabels'),
                                }),
                            },
                        ],
                        [
                            {
                                view: new VarcharFieldView({
                                    name: 'cellPadding',
                                    labelText: this.translate('cellPadding', 'wysiwygLabels'),
                                    params: {
                                        maxLength: 12,
                                    },
                                }),
                            },
                            {
                                view: new ColorpickerFieldView({
                                    name: 'backgroundColor',
                                    labelText: this.translate('backgroundColor', 'wysiwygLabels'),
                                }),
                            },
                        ],
                    ],
                },
            ],
        });

        this.assignView('record', this.recordView, '.record');
    }

    apply() {
        if (this.recordView.validate()) {
            return;
        }

        let borderWidth = this.model.attributes.borderWidth;
        let cellPadding = this.model.attributes.cellPadding;

        if (/^\d+$/.test(borderWidth)) {
            borderWidth += 'px';
        }

        if (/^\d+$/.test(cellPadding)) {
            cellPadding += 'px';
        }

        this.onApply({
            align: this.model.attributes.align,
            width: this.model.attributes.width,
            borderWidth: borderWidth,
            borderColor: this.model.attributes.borderColor,
            cellPadding: cellPadding,
            backgroundColor: this.model.attributes.backgroundColor,
        });

        this.close();
    }
}

export default EditTableModalView

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

import ArrayFieldView, {ArrayOptions, ArrayParams} from 'views/fields/array';
import {BaseViewSchema} from 'views/fields/base';

export interface UrlMultipleParams extends ArrayParams {
    /**
     * Strip.
     */
    strip?: boolean
}

export interface UrlMultipleOptions extends ArrayOptions {}

/**
 * A Url-Multiple field.
 */
class UrlMultipleFieldView<
    S extends BaseViewSchema = BaseViewSchema,
    O extends UrlMultipleOptions = UrlMultipleOptions,
    P extends UrlMultipleParams = UrlMultipleParams,
> extends ArrayFieldView<S, O, P> {

    readonly type: string = 'urlMultiple'

    protected maxItemLength = 255
    protected displayAsList = true
    protected defaultProtocol = 'https:'

    protected setup() {
        super.setup();

        this.noEmptyString = true;
        this.params.pattern = '$uriOptionalProtocol';
    }

    protected addValueFromUi(value: string) {
        value = value.trim();

        if (this.params.strip) {
            value = this.strip(value);
        }

        try {
            if (value === decodeURI(value)) {
                value = encodeURI(value);
            }
        } catch (e) {
            console.warn(`Malformed URI ${value}.`);
        }

        super.addValueFromUi(value);
    }

    private decodeURI(value: string): string {
        try {
            return decodeURI(value);
        } catch (e) {
            console.warn(`Malformed URI ${value}.`);

            return value;
        }
    }

    private strip(value: string): string {
        if (value.indexOf('//') !== -1) {
            value = value.substring(value.indexOf('//') + 2);
        }

        value = value.replace(/\/+$/, '');

        return value;
    }

    private prepareUrl(url: string): string {
        if (url.indexOf('//') === -1) {
            url = this.defaultProtocol + '//' + url;
        }

        return url;
    }

    protected getValueForDisplay(): string {
        const $list = this.selected.map(value => {
            return $('<a>')
                .attr('href', this.prepareUrl(value))
                .attr('target', '_blank')
                .text(this.decodeURI(value));
        });

        return $list
            .map($item =>
                $('<div>')
                    .addClass('multi-enum-item-container')
                    .append($item)
                    .get(0)?.outerHTML as any
            )
            .join('');
    }

    protected getItemHtml(value: string): string {
        const html = super.getItemHtml(value);

        const $item = $(html);

        $item.find('span.text').html(
            $('<a>')
                .attr('href', this.prepareUrl(value))
                .css('user-drag', 'none')
                .attr('target', '_blank')
                .text(this.decodeURI(value)) as any
        );

        return $item.get(0)?.outerHTML as string;
    }
}

export default UrlMultipleFieldView;

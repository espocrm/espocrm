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

import Language from 'language';
import {inject} from 'di';
import {h, fragment, VNode} from 'bullbone';

/**
 * @internal
 * @experimental
 * @since 10.0.0
 */
export class ButtonComponent {

    @inject(Language)
    private language: Language

    constructor(
        private options: {
            name: string,
            style?: 'default' | 'success' | 'danger'| 'warning' | 'info' | 'primary' | 'text' | null,
            className?: string | null,
            scope?: string | null,
            title?: string | null,
            label?: string | null,
            labelTranslation?: string | null,
            link?: string | null,
            iconClass?: string | null,
            text?: string | null,
            disabled?: boolean,
            hidden?: boolean,
            html?: string | null,
        },
    ) {}

    node(): VNode {
        const classes: any = {
            'btn': true,
            'disabled': this.options.disabled === true,
            'hidden': this.options.hidden === true,
            'action': true,
        };

        const style = this.options.style ?? 'default';
        classes['btn-' + style] = true;

        if (this.options.className) {
            this.options.className.split(' ').forEach(it => classes[it.trim()] = true);
        }

        const tag: 'button' | 'a' = this.options.link ? 'a' : 'button';

        const attrs: Record<string, any> = {};

        if (this.options.link) {
            attrs.href = this.options.link;
        } else {
            attrs.type = 'button';
        }

        if (this.options.disabled) {
            attrs.disabled = 'disabled';
        }

        if (this.options.title) {
            attrs.title = this.options.title;
        }

        let content = null;

        const props: any = {};

        if (this.options.html) {
            props.innerHTML = this.options.html;
        } else {
            const label = this.options.label ?? this.options.name;

            const text = this.options.text ??
                (
                    this.options.labelTranslation ?
                        this.language.translatePath(this.options.labelTranslation) :
                        this.language.translate(label, 'labels', this.options.scope)
                );


            content = this.options.iconClass ?
                fragment([
                    h('span', {class: {[this.options.iconClass]: true}}),
                    ' ',
                    h('span', text),
                ]) :
                text;
        }


        return h(tag, {
            key: this.options.name ?? null,
            class: classes,
            attrs: attrs,
            dataset: {
                name: this.options.name,
                action: this.options.name,
            },
            props,
        }, content);
    }
}

/**
 * @internal
 * @experimental
 * @since 10.0.0
 */
export class DropdownItemComponent {

    @inject(Language)
    private language: Language

    constructor(
        private options: {
            name: string,
            className?: string | null,
            scope?: string | null,
            title?: string | null,
            label?: string | null,
            labelTranslation?: string | null,
            link?: string | null,
            iconClass?: string | null,
            text?: string | null,
            disabled?: boolean,
            hidden?: boolean,
            html?: string | null,
        },
    ) {}

    node(): VNode {
        const classes: any = {
            'disabled': this.options.disabled === true,
            'hidden': this.options.hidden === true,
            'action': true,
        };

        if (this.options.className) {
            this.options.className.split(' ').forEach(it => classes[it.trim()] = true);
        }

        const attrs: Record<string, any> = {
            tabindex: 0,
        };

        if (this.options.link) {
            attrs.href = this.options.link;
        }

        if (this.options.disabled) {
            attrs.disabled = 'disabled';
        }

        if (this.options.title) {
            attrs.title = this.options.title;
        }

        let content = null;

        const props: any = {};

        if (this.options.html) {
            props.innerHTML = this.options.html;
        } else {
            const label = this.options.label ?? this.options.name;

            const text = this.options.text ??
                (
                    this.options.labelTranslation ?
                        this.language.translatePath(this.options.labelTranslation) :
                        this.language.translate(label, 'labels', this.options.scope)
                );

            content = this.options.iconClass ?
                fragment([
                    h('span', {class: {[this.options.iconClass]: true}}),
                    ' ',
                    h('span', text),
                ]) :
                text;
        }

        const dataset = {
            name: this.options.name,
            action: this.options.name,
        } as Record<string, any>;

        if (!this.options.link) {
            attrs.role = 'button';
        }

        const a = h('a', {
            class: classes,
            attrs: attrs,
            dataset: dataset,
            props: props,
        }, content);

        return h('li', {
            key: this.options.name ?? null,
            class: {
                'disabled': this.options.disabled === true,
                'hidden': this.options.hidden === true,
            },
        }, a);
    }
}

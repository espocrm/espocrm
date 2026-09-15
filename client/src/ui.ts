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

/** @module ui */

import {marked} from 'marked';
import DOMPurify from 'dompurify';
import JQuery from 'jquery';
import Dialog from 'ui/dialog';
import {DialogParams} from 'ui/dialog';
import Utils from 'utils';

const $ = JQuery;

/**
 * Popover options.
 */
interface PopoverOptions {
    placement?: 'bottom' | 'top' | 'left' | 'right';
    container?: string | JQuery;
    content?: string;
    text?: string;
    trigger?: 'manual' | 'click' | 'hover' | 'focus';
    noToggleInit?: boolean;
    preventDestroyOnRender?: boolean;
    noHideOnOutsideClick?: boolean;
    onShow?: () => void;
    onHide?: () => void;
    title?: string | (() => string);
    keepElementTitle?: boolean;
}

/**
 * Notify options.
 */
interface NotifyOptions {
    closeButton?: boolean;
    suppress?: boolean;
}



interface ConfirmOptions {
    confirmText: string;
    cancelText: string;
    confirmStyle?: 'danger' | 'success' | 'warning' | 'default';
    backdrop?: 'static' | boolean;
    cancelCallback?: () => void;
    isHtml?: boolean;
}

/**
 * UI utils.
 */
const Ui = {

    Dialog: Dialog,

    /**
     * Show a confirmation dialog.
     *
     * @param message A message.
     * @param options Options.
     * @param [callback] Deprecated. Use a promise.
     * @param [context] Deprecated.
     * @returns Resolves if confirmed.
     */
    confirm: function (
        message: string,
        options: ConfirmOptions,
        callback?: () => void,
        context?: object,
    ): Promise<void> {

        options = {...options};

        const confirmText = options.confirmText;
        const cancelText = options.cancelText;
        const confirmStyle = options.confirmStyle || 'danger';
        let backdrop = options.backdrop;

        if (typeof backdrop === 'undefined') {
            backdrop = false;
        }

        let isResolved = false;

        const processCancel = () => {
            if (!options.cancelCallback) {
                return;
            }

            if (context) {
                options.cancelCallback.call(context);

                return;
            }

            options.cancelCallback();
        };

        if (!options.isHtml) {
            message = Handlebars.Utils.escapeExpression(message);
        }

        confirmCount ++;

        return new Promise(resolve => {
            const confirm = () => {
                isResolved = true;

                if (callback) {
                    if (context) {
                        callback.call(context);
                    } else {
                        callback();
                    }
                }

                resolve();

                dialog.close();
            };

            const keydownHandler = (e: KeyboardEvent) => {
                if (!dialog.isTopmost()) {
                    return;
                }

                if (Utils.getKeyFromKeyEvent(e) === 'Control+Enter') {
                    confirm();
                }
            };

            window.addEventListener('keydown', keydownHandler);

            const dialog = new Dialog({
                backdrop: backdrop,
                header: null,
                className: 'dialog-confirm',
                backdropClassName: 'backdrop-confirm',
                body: `<span class="confirm-message">${message}</a>`,
                buttonList: [
                    {
                        text: ` ${confirmText} `,
                        name: 'confirm',
                        className: 'btn-s-wide',
                        onClick: () => confirm(),
                        style: confirmStyle,
                        position: 'right',
                    },
                    {
                        text: cancelText,
                        name: 'cancel',
                        className: 'btn-s-wide',
                        onClick: () => {
                            isResolved = true;

                            dialog.close();
                            processCancel();
                        },
                        position: 'left',
                    }
                ],
                onClose: () => {
                    confirmCount --;

                    if (isResolved) {
                        return;
                    }

                    processCancel();
                },
                onRemove: () => {
                    window.removeEventListener('keydown', keydownHandler);
                },
            });

            dialog.show();
        });
    },

    /**
     * Create a dialog.
     *
     * @param options Options.
     * @returns A dialog instance.
     */
    dialog: function (options: DialogParams): Dialog {
        return new Dialog(options);
    },

    /**
     * Init a popover.
     *
     * @param element An element.
     * @param options Options.
     * @param [view] A view.
     * @return Manipulator object.
     */
    popover: function (
        element: Element,
        options: PopoverOptions,
        view?: import('view').default
    ): {
        hide: () => void;
        destroy: () => void;
        show: () => string;
        detach: () => void;
    } {

        const $el = $(element);
        const $body = $('body');
        const content = options.content || Handlebars.Utils.escapeExpression(options.text || '');
        let isShown = false;

        let container = options.container;

        if (!container) {
            const $modalBody = $el.closest('.modal-body');

            container = $modalBody.length ? $modalBody : 'body';
        }

        // noinspection JSUnresolvedReference
        // @ts-ignore
        $el.popover({
            placement: options.placement || 'bottom',
            container: container,
            viewport: container,
            html: true,
            content: content,
            trigger: options.trigger || 'manual',
            title: options.title,
            keepElementTitle: options.keepElementTitle,
        })
            .on('shown.bs.popover', () => {
                isShown = true;

                if (!view) {
                    return;
                }

                if (view && !options.noHideOnOutsideClick) {
                    $body.off(`click.popover-${view.cid}`);

                    $body.on(`click.popover-${view.cid}`, e => {
                        if ($(e.target).closest('.popover').get(0)) {
                            return;
                        }

                        if ($.contains($el.get(0) as Element, e.target)) {
                            return;
                        }

                        if ($el.get(0) === e.target) {
                            return;
                        }

                        $body.off(`click.popover-${view.cid}`);
                        // noinspection JSUnresolvedReference
                        // @ts-ignore
                        $el.popover('hide');
                    });
                }

                if (options.onShow) {
                    options.onShow();
                }
            })
            .on('hidden.bs.popover', () => {
                isShown = false;

                if (options.onHide) {
                    options.onHide();
                }
            });

        if (!options.noToggleInit) {
            $el.on('click', () => {
                // noinspection JSUnresolvedReference
                // @ts-ignore
                $el.popover('toggle');
            });
        }

        let isDetached = false;

        const detach = () => {
            if (view) {
                $body.off(`click.popover-${view.cid}`);

                view.off('remove', destroy);
                view.off('render', destroy);
                view.off('render', hide);
            }

            isDetached = true;
        };

        const destroy = () => {
            if (isDetached) {
                return;
            }

            // noinspection JSUnresolvedReference
            // @ts-ignore
            $el.popover('destroy');

            detach();
        };

        const hide = () => {
            if (!isShown) {
                return;
            }

            // noinspection JSUnresolvedReference
            // @ts-ignore
            $el.popover('hide');
        };

        const show = () => {
            // noinspection JSUnresolvedReference
            // @ts-ignore
            $el.popover('show');

            return $el.attr('aria-describedby') as string;
        };

        if (view) {
            view.once('remove', destroy);

            if (!options.preventDestroyOnRender) {
                view.once('render', destroy);
            }

            if (options.preventDestroyOnRender) {
                view.on('render', hide);
            }
        }

        return {
            hide: () => hide(),
            destroy: () => destroy(),
            show: () => show(),
            detach: () => detach(),
        };
    },

    /**
     * Show the spinner.
     *
     * @since 9.1.0
     */
    notifyWait: function () {
        Ui.notify(' ... ');
    },

    /**
     * Show a notify-message.
     *
     * @param [message] A message. False removes an already displayed message.
     * @param [type='warning'] A type.
     * @param [timeout] Microseconds. If empty, then won't be hidden.
     *   Should be hidden manually or by displaying another message.
     * @param [options] Options.
     */
    notify: function (
        message: string | false = false,
        type?: 'warning' | 'danger' | 'success' | 'info',
        timeout?: number,
        options?: NotifyOptions,
    ) {

        type = type ?? 'warning';
        options = {...options};

        if (type === 'warning' && notifySuppressed) {
            return;
        }

        $('#notification').remove();

        if (!message) {
            return;
        }

        if (options.suppress && timeout) {
            notifySuppressed = true;

            setTimeout(() => notifySuppressed = false, timeout)
        }

        const parsedMessage = message.indexOf('\n') !== -1 ?
            marked.parse(message) :
            marked.parseInline(message);

        let sanitizedMessage = DOMPurify.sanitize(parsedMessage, {}).toString();

        const closeButton = options.closeButton || false;

        // @ts-ignore
        if (type === 'error') {
            // For bc.
            type = 'danger';
        }

        if (sanitizedMessage === ' ... ') {
            sanitizedMessage = ' <span class="fas fa-spinner fa-spin"> ';
        }

        const additionalClassName = closeButton ? ' alert-closable' : '';

        const $el = $('<div>')
            .addClass('alert alert-' + type + additionalClassName + ' fade in')
            .attr('id', 'notification')
            .css({
                'position': 'fixed',
                'top': '0',
                'left': '50vw',
                'transform': 'translate(-50%, 0)',
                'z-index': 2000,
            })
            .append(
                $('<div>')
                    .addClass('message')
                    .html(sanitizedMessage)
            );

        if (closeButton) {
            const $close = $('<button>')
                .attr('type', 'button')
                .attr('data-dismiss', 'modal')
                .attr('aria-hidden', 'true')
                .addClass('close')
                .html(`<span class="fas fa-times"></span>`);

            $el.append(
                $('<div>')
                    .addClass('close-container')
                    .append($close)
            );

            // @ts-ignore
            $close.on('click', () => $el.alert('close'));
        }

        if (timeout) {
            // @ts-ignore
            setTimeout(() => $el.alert('close'),
                timeout);
        }

        $el.appendTo('body')
    },

    /**
     * Show a warning message.
     *
     * @param message message.
     * @param [options] Options.
     */
    warning: function (message: string, options?: NotifyOptions) {
        Ui.notify(message, 'warning', 2000, options);
    },

    /**
     * Show a success message.
     *
     * @param message A message.
     * @param [options] Options.
     */
    success: function (message: string, options?: NotifyOptions) {
        Ui.notify(message, 'success', 2000, options);
    },

    /**
     * Show an error message.
     *
     * @param message A message.
     * @param [options] Options. If true, then only closeButton option will be applied.
     */
    error: function (message: string, options?: NotifyOptions | true) {
        options = typeof options === 'boolean' ?
            {closeButton: options} :
            {...options};

        const timeout = options.closeButton ? 0 : 4000;

        Ui.notify(message, 'danger', timeout, options);
    },

    /**
     * Show an info message.
     *
     * @param message A message.
     * @param [options] Options.
     */
    info: function (message: string, options?: NotifyOptions) {
        Ui.notify(message, 'info', 2000, options);
    },

    /**
     * Get the number of opened confirmation dialogues.
     * @internal
     */
    getConfirmCount(): number {
        return confirmCount;
    }
};

let confirmCount = 0;
let notifySuppressed = false;

Espo.Ui = Ui;

export default Ui;

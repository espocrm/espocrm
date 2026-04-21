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

/** @module view */

import {View as BullView} from 'bullbone';
import Model from 'model';
import Collection from 'collection';
import Ui from 'ui';

export interface ConfirmOptions {
    message: string;
    confirmText?: string;
    cancelText?: string;
    confirmStyle?: 'danger' | 'success' | 'warning' | 'default';
    backdrop?: 'static' | boolean;
    cancelCallback?: () => void;
}

/**
 * @param {MouseEvent} event DOM event.
 * @param {HTMLElement} includeInactive A target element.
 */
type ActionHandlerCallback = (event: MouseEvent, element: HTMLElement) => void;

interface ViewSchema {
    model?: Model;
    collection?: Collection;
}

/**
 * A base view. All views should extend this class.
 *
 * @see https://docs.espocrm.com/development/view/
 */
export default class View<S extends ViewSchema = ViewSchema> extends BullView<S['model'], S['collection']> {

    /**
     * A model.
     */
    model: S['model']

    /**
     * A collection.
     */
    collection: S['collection']

    /**
     * @param options Options.
     */
    constructor(
        options: Record<string, any> & {
            model?: S['model'],
            collection?: S['collection'],
        } = {}
    ) {
        super(options);

        if (options.model) {
            this.model = options.model;
        }

        if (options.collection) {
            this.collection = options.collection;
        }
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * When the view is ready. Can be useful to prevent race condition when re-initialization is needed
     * in-between initialization and render.
     *
     * @todo Move to Bull.View.
     */
    whenReady(): Promise<void> {
        if (this.isReady) {
            return Promise.resolve();
        }

        return new Promise<void>(resolve => {
            this.once('ready', () => resolve());
        });
    }

    /**
     * Add a DOM click event handler for a target defined by `data-action="{name}"` attribute.
     *
     * @param action An action name.
     * @param handler A handler.
     */
    addActionHandler(action: string, handler: ActionHandlerCallback) {
        // The key should be in sync with one in Utils.handleAction.
        const fullAction = `click [data-action="${action}"]`;

        this.events[fullAction] = e => {
            // noinspection JSUnresolvedReference
            handler.call(this, e.originalEvent, e.currentTarget);
        };
    }

    /**
     * Escape a string.
     *
     * @param {string} string
     * @returns {string}
     */
    escapeString(string: string): string {
        return Handlebars.Utils.escapeExpression(string);
    }

    /**
     * Show a notify-message.
     *
     * @deprecated Use `Espo.Ui.notify`.
     * @param {string|false} label
     * @param {string} [type]
     * @param {number} [timeout]
     * @param {string} [scope]
     */
    notify(label: string | false, type: string, timeout: number, scope: string) {
        if (!label) {
            // @ts-ignore
            Ui.notify(false);

            return;
        }

        scope = scope || null;
        timeout = timeout || 2000;

        if (!type) {
            timeout = void 0;
        }

        const text = this.getLanguage().translate(label, 'labels', scope);

        // @ts-ignore
        Ui.notify(text, type, timeout);
    }

    /**
     * Get a view-helper.
     */
    getHelper(): import('view-helper').default {
        return this._helper;
    }

    /**
     * Get a current user.
     */
    getUser(): import('models/user').default {
        // @ts-ignore
        return this._helper.user;
    }

    /**
     * Get the preferences.
     */
    getPreferences(): import('models/preferences').default {
        // @ts-ignore
        return this._helper.preferences;
    }

    /**
     * Get the config.
     */
    getConfig(): import('models/settings').default {
        // @ts-ignore
        return this._helper.settings;
    }

    /**
     * Get the ACL.
     */
    getAcl(): import('acl-manager').default {
        // @ts-ignore
        return this._helper.acl;
    }

    /**
     * Get the model factory.
     */
    getModelFactory(): import('model-factory').default {
        // @ts-ignore
        return this._helper.modelFactory;
    }

    /**
     * Get the collection factory.
     */
    getCollectionFactory(): import('collection-factory').default {
        // @ts-ignore
        return this._helper.collectionFactory;
    }

    /**
     * Get the router.
     */
    getRouter(): import('router').default {
        // @ts-ignore
        return this._helper.router;
    }

    /**
     * Get the storage-util.
     */
    getStorage(): import('storage').default {
        // @ts-ignore
        return this._helper.storage;
    }

    /**
     * Get the session-storage-util.
     */
    getSessionStorage(): import('session-storage').default {
        // @ts-ignore
        return this._helper.sessionStorage;
    }

    /**
     * Get the language-util.
     */
    getLanguage(): import('language').default {
        // @ts-ignore
        return this._helper.language;
    }

    /**
     * Get metadata.
     */
    getMetadata(): import('metadata').default {
        // @ts-ignore
        return this._helper.metadata;
    }

    /**
     * Get the cache-util.
     */
    getCache(): import('cache').default {
        // @ts-ignore
        return this._helper.cache;
    }

    /**
     * Get the date-time util.
     */
    getDateTime(): import('date-time').default {
        // @ts-ignore
        return this._helper.dateTime;
    }

    /**
     * Get the number-util.
     */
    getNumberUtil(): import('number-util').default {
        // @ts-ignore
        return this._helper.numberUtil;
    }

    /**
     * Get the field manager.
     */
    getFieldManager(): import('field-manager').default {
        // @ts-ignore
        return this._helper.fieldManager;
    }

    /**
     * Get the base-controller.
     *
     * @internal
     * @return {import('controllers/base').default}
     */
    getBaseController(): import('controllers/base').default {
        // @ts-ignore
        return this._helper.baseController;
    }

    /**
     * Get the theme manager.
     */
    getThemeManager(): import('theme-manager').default {
        // @ts-ignore
        return this._helper.themeManager;
    }

    /**
     * Update a page title. Supposed to be overridden if needed.
     */
    updatePageTitle() {
        const title = this.getConfig().get('applicationName') || 'EspoCRM';

        this.setPageTitle(title);
    }

    /**
     * Set a page title.
     *
     * @param title A title.
     */
    setPageTitle(title: string) {
        this.getHelper().pageTitle.setTitle(title);
    }

    /**
     * Translate a label.
     *
     * @param label Label.
     * @param [category='labels'] Category.
     * @param [scope='Global'] Scope.
     * @returns {string}
     */
    translate(
        label: string,
        category?: string | 'messages' | 'labels' | 'fields' | 'links' | 'scopeNames' | 'scopeNamesPlural',
        scope?: string,
    ): string {

        return this.getLanguage().translate(label, category, scope);
    }

    /**
     * Get a base path.
     */
    getBasePath(): string {
        // @ts-ignore
        return this._helper.basePath || '';
    }

    /**
     * Show a confirmation dialog.
     *
     * @param options A message or options.
     * @param [callback] A callback. Deprecated, use a promise.
     * @param [context] A context. Deprecated.
     * @returns {Promise} To be resolved if confirmed.
     */
    confirm(options: ConfirmOptions, callback = undefined, context= undefined): Promise<any> {
        let message: string;

        let o: ConfirmOptions | Record<string, any>;

        if (typeof options === 'string' || options instanceof String) {
            message = options.toString();

            o = {};
        } else {
            o = options ?? {};

            message = options.message;
        }

        if (message) {
            message = this.getHelper()
                .transformMarkdownText(message, {linksInNewTab: true})
                .toString();
        }

        const confirmText = options.confirmText || this.translate('Yes');
        const confirmStyle = options.confirmStyle || null;
        const cancelText = options.cancelText || this.translate('Cancel');

        return Espo.Ui.confirm(message, {
            confirmText: confirmText,
            cancelText: cancelText,
            confirmStyle: confirmStyle,
            backdrop: ('backdrop' in options) ? o.backdrop : true,
            isHtml: true,
            cancelCallback: options.cancelCallback,
        }, callback, context);
    }
}

Espo.loader.setContextId('lib!bullbone');
(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports, require('underscore'), require('jquery'), require('handlebars')) :
    typeof define === 'function' && define.amd ? define('bullbone', ['exports', 'underscore', 'jquery', 'handlebars'], factory) :
    (global = typeof globalThis !== 'undefined' ? globalThis : global || self, factory(global.bullbone = {}, global._, global.$, global.Handlebard));
})(this, (function (exports, _, $, Handlebars) { 'use strict';

    /**
     * Credits to Backbone.js.
     * Copyright (c) 2010-2022 Jeremy Ashkenas, DocumentCloud
     */

    /**
     * An Events mixin.
     *
     * @alias Bull.Events
     */
    const Events = {};

    if ('Backbone' in window) {
        /** For backward compatibility. */
        window.Backbone.Events = Events;
    }

    const eventSplitter = /\s+/;

    let _listening;

    const eventsApi = (iteratee, events, name, callback, opts) => {
        let i = 0, names;

        if (name && typeof name === 'object') {
            // Handle event maps.
            if (callback !== void 0 && 'context' in opts && opts.context === void 0) {
                opts.context = callback;
            }

            for (names = _.keys(name); i < names.length ; i++) {
                events = eventsApi(iteratee, events, names[i], name[names[i]], opts);
            }
        } else if (name && eventSplitter.test(name)) {
            // Handle space-separated event names by delegating them individually.
            for (names = name.split(eventSplitter); i < names.length; i++) {
                events = iteratee(events, names[i], callback, opts);
            }
        } else {
            // Finally, standard events.
            events = iteratee(events, name, callback, opts);
        }

        return events;
    };

    /**
     * Subscribe to an event.
     *
     * @param {string} name An event.
     * @param {Bull.Events~callback} callback A callback.
     * @param {Object} [context] Deprecated.
     */
    Events.on = function (name, callback, context) {
        this._events = eventsApi(onApi, this._events || {}, name, callback, {
            context: context,
            ctx: this,
            listening: _listening,
        });

        if (_listening) {
            let listeners = this._listeners || (this._listeners = {});

            listeners[_listening.id] = _listening;
            // Allow the listening to use a counter, instead of tracking
            // callbacks for library interop
            _listening.interop = false;
        }

        return this;
    };

    /**
     * Subscribe to an event of other object.
     *
     * @param {Object} other What to listen.
     * @param {string} name An event.
     * @param {Bull.Events~callback} callback A callback.
     */
    Events.listenTo = function (other, name, callback) {
        if (!other) {
            return this;
        }

        let id = other._listenId || (other._listenId = _.uniqueId('l'));
        let listeningTo = this._listeningTo || (this._listeningTo = {});
        let listening = _listening = listeningTo[id];

        // This object is not listening to any other events on `obj` yet.
        // Set up the necessary references to track the listening callbacks.
        if (!listening) {
            this._listenId || (this._listenId = _.uniqueId('l'));

            listening = _listening = listeningTo[id] = new Listening(this, other);
        }

        // Bind callbacks on obj.
        let error = tryCatchOn(other, name, callback, this);
        _listening = void 0;

        if (error) {
            throw error;
        }

        // If the target obj is not Backbone.Events, track events manually.
        if (listening.interop) {
            listening.on(name, callback);
        }

        return this;
    };

    const onApi = (events, name, callback, options) => {
        if (callback) {
            let handlers = events[name] || (events[name] = []);
            let context = options.context, ctx = options.ctx, listening = options.listening;

            if (listening) {
                listening.count++;
            }

            handlers.push({callback: callback, context: context, ctx: context || ctx, listening: listening});
        }

        return events;
    };

    const tryCatchOn = (obj, name, callback, context) => {
        try {
            obj.on(name, callback, context);
        } catch (e) {
            return e;
        }
    };

    /**
     * Unsubscribe from an event or all events.
     *
     * @function off
     * @memberof Bull.Events
     * @param {string} [name] From a specific event.
     * @param {Bull.Events~callback} [callback] From a specific callback.
     * @param {Object} [context] Deprecated.
     */
    Events.off = function(name, callback, context) {
        if (!this._events) {
            return this;
        }

        this._events = eventsApi(offApi, this._events, name, callback, {
            context: context,
            listeners: this._listeners
        });

        return this;
    };

    /**
     * Stop listening to other object. No arguments will remove all listeners.
     *
     * @param {Object} [other] To remove listeners to a specific object.
     * @param {string} [name] To remove listeners to a specific event.
     * @param {Bull.Events~callback} [callback] To remove listeners to a specific callback.
     */
    Events.stopListening = function (other, name, callback) {
        let listeningTo = this._listeningTo;

        if (!listeningTo) {
            return this;
        }

        let ids = other ? [other._listenId] : _.keys(listeningTo);

        for (let i = 0; i < ids.length; i++) {
            let listening = listeningTo[ids[i]];

            // If listening doesn't exist, this object is not currently
            // listening to obj. Break out early.
            if (!listening) {
                break;
            }

            listening.obj.off(name, callback, this);

            if (listening.interop) {
                listening.off(name, callback);
            }
        }

        if (_.isEmpty(listeningTo)) {
            this._listeningTo = void 0;
        }

        return this;
    };

    const offApi = (events, name, callback, options) => {
        if (!events) {
            return;
        }

        let context = options.context, listeners = options.listeners;
        let i = 0, names;

        // Delete all event listeners and "drop" events.
        if (!name && !context && !callback) {
            for (names = _.keys(listeners); i < names.length; i++) {
                listeners[names[i]].cleanup();
            }

            return;
        }

        names = name ? [name] : _.keys(events);

        for (; i < names.length; i++) {
            name = names[i];
            let handlers = events[name];

            // Bail out if there are no events stored.
            if (!handlers) {
                break;
            }

            // Find any remaining events.
            let remaining = [];

            for (let j = 0; j < handlers.length; j++) {
                let handler = handlers[j];

                if (
                    callback && callback !== handler.callback &&
                    callback !== handler.callback._callback ||
                    context && context !== handler.context
                ) {
                    remaining.push(handler);
                } else {
                    let listening = handler.listening;

                    if (listening) {
                        listening.off(name, callback);
                    }
                }
            }

            // Replace events if there are any remaining.  Otherwise, clean up.
            if (remaining.length) {
                events[name] = remaining;
            } else {
                delete events[name];
            }
        }

        return events;
    };

    /**
     * Subscribe to an event. Fired once.
     *
     * @param {string} name An event.
     * @param {Bull.Events~callback} callback A callback.
     * @param {Object} [context] Deprecated.
     */
    Events.once = function (name, callback, context) {
        // Map the event into a `{event: once}` object.
        let events = eventsApi(onceMap, {}, name, callback, this.off.bind(this));

        if (typeof name === 'string' && context == null) {
            callback = void 0;
        }

        return this.on(events, callback, context);
    };

    /**
     * Subscribe to an event of other object. Fired once. Will be automatically unsubscribed on view removal.
     *
     * @param {Object} other What to listen.
     * @param {string} name An event.
     * @param {Bull.Events~callback} callback A callback.
     */
    Events.listenToOnce = function (other, name, callback) {
        // Map the event into a `{event: once}` object.
        let events = eventsApi(onceMap, {}, name, callback, this.stopListening.bind(this, other));

        return this.listenTo(other, events);
    };

    let onceMap = function (map, name, callback, offer) {
        if (callback) {
            let once = map[name] = _.once(function() {
                offer(name, once);
                callback.apply(this, arguments);
            });

            once._callback = callback;
        }

        return map;
    };

    /**
     * Trigger an event.
     *
     * @param {string} name An event.
     * @param {...*} parameters Arguments.
     */
    Events.trigger = function(name, ...parameters) {
        if (!this._events) {
            return this;
        }

        let length = Math.max(0, arguments.length - 1);
        let args = Array(length);

        for (let i = 0; i < length; i++) {
            args[i] = arguments[i + 1];
        }

        eventsApi(triggerApi, this._events, name, void 0, args);

        return this;
    };

    const triggerApi = (objEvents, name, callback, args) => {
        if (objEvents) {
            let events = objEvents[name];
            let allEvents = objEvents.all;

            if (events && allEvents) {
                allEvents = allEvents.slice();
            }

            if (events) {
                triggerEvents(events, args);
            }

            if (allEvents) {
                triggerEvents(allEvents, [name].concat(args));
            }
        }

        return objEvents;
    };

    const triggerEvents = (events, args) => {
        let ev,
            i = -1,
            l = events.length,
            a1 = args[0],
            a2 = args[1],
            a3 = args[2];

        switch (args.length) {
            case 0: while (++i < l) (ev = events[i]).callback.call(ev.ctx); return;
            case 1: while (++i < l) (ev = events[i]).callback.call(ev.ctx, a1); return;
            case 2: while (++i < l) (ev = events[i]).callback.call(ev.ctx, a1, a2); return;
            case 3: while (++i < l) (ev = events[i]).callback.call(ev.ctx, a1, a2, a3); return;
            default: while (++i < l) (ev = events[i]).callback.apply(ev.ctx, args); return;
        }
    };

    const Listening = function(listener, obj) {
        this.id = listener._listenId;
        this.listener = listener;
        this.obj = obj;
        this.interop = true;
        this.count = 0;
        this._events = void 0;
    };

    Listening.prototype.on = Events.on;

    Listening.prototype.off = function (name, callback) {
        let cleanup;

        if (this.interop) {
            this._events = eventsApi(offApi, this._events, name, callback, {
                context: void 0,
                listeners: void 0
            });

            cleanup = !this._events;
        }
        else {
            this.count--;

            cleanup = this.count === 0;
        }

        if (cleanup) {
            this.cleanup();
        }
    };

    Listening.prototype.cleanup = function () {
        delete this.listener._listeningTo[this.obj._listenId];

        if (!this.interop) {
            delete this.obj._listeners[this.id];
        }
    };

    // noinspection JSUnusedGlobalSymbols


    /**
     * View options passed to a view on creation.
     *
     * @typedef {Object.<string, *>} Bull.View~Options
     *
     * @property {string} [selector] A DOM element selector relative to a parent view.
     * @property {string} [fullSelector] A full DOM element selector.
     * @property {string[]} [optionsToPass] Options to be automatically passed to child views
     *   of the created view.
     * @property {(function: Object)|Object} [data] Data that will be passed to a template or a function
     *   that returns data.
     * @property {string} [template] A template name.
     * @property {string} [templateContent] Template content.
     * @property {Object} [layoutDefs] Internal layout defs.
     * @property {Object} [layoutData] Internal layout data.
     * @property {boolean} [notToRender] Not to render on ready.
     * @property {Object.<string, Bull.View~NestedViewItem>} [views] Child view definitions.
     * @property {string} [name] A view name.
     * @property {Bull.Model} [model] A model.
     * @property {Bull.Collection} [collection] A collection.
     * @property {Bull.View.DomEvents} [events] DOM events.
     * @property {boolean} [setViewBeforeCallback] A child view will be set to a parent before a promise is resolved.
     */

    /**
     * @typedef {Object} Bull
     */

    /**
     * A model.
     *
     * @typedef {Object} Bull~Model
     * @type Object
     * @mixes Bull.Events
     */

    /**
     * A collection.
     *
     * @typedef {Object} Bull~Collection
     * @type Object
     * @mixes Bull.Events
     */

    /**
     * Nested view definitions.
     *
     * @typedef {Object} Bull.View~NestedViewItem
     *
     * @property {string} view A view name/path.
     * @property {string} [selector] A DOM element selector relative to a parent view.
     * @property {string} [fullSelector] A full DOM element selector.
     * @property {string} [el] Deprecated. Use `fullSelector`. A full DOM element selector.
     */

    /**
     * After a view is rendered.
     *
     * @event Bull.View#after:render
     */

    /**
     * Once a view is ready for rendering (loaded).
     *
     * @event Bull.View#ready
     */

    /**
     * Once a view is removed.
     *
     * @event Bull.View#remove
     */

    /**
     * A get-HTML callback.
     *
     * @callback Bull.View~getPreparedElementCallback
     *
     * @param {HTMLTemplateElement} element An element.
     */

    /**
     * A DOM event callback.
     *
     * @callback Bull.View~domEventCallback
     *
     * @param {jQuery.Event} e An event.
     */

    /**
     * A DOM event handler callback.
     *
     * @callback Bull.View~domEventHandlerCallback
     * @param {Event} event An event.
     * @param {HTMLElement} A target element.
     */

    /**
     * @typedef {'click'|'mousedown'|'keydown'|string} Bull.View~domEventType
     */

    /**
     * @callback Bull.Events~callback
     *
     * @param {...*} parameters
     */

    /**
     * @mixin Bull.Events
     */

    /**
     * Trigger an event.
     *
     * @function trigger
     * @memberof Bull.Events
     * @param {string} event An event.
     * @param {...*} parameters Arguments.
     */

    /**
     * Subscribe to an event.
     *
     * @function on
     * @memberof Bull.Events
     * @param {string} event An event.
     * @param {Bull.Events~callback} callback A callback.
     */

    /**
     * Subscribe to an event. Fired once.
     *
     * @function once
     * @memberof Bull.Events
     * @param {string} event An event.
     * @param {Bull.Events~callback} callback A callback.
     */

    /**
     * Unsubscribe from an event or all events.
     *
     * @function off
     * @memberof Bull.Events
     * @param {string} [event] From a specific event.
     * @param {Bull.Events~callback} [callback] From a specific callback.
     */

    /**
     * Subscribe to an event of other object. Will be automatically unsubscribed on view removal.
     *
     * @function listenTo
     * @memberof Bull.Events
     * @param {Object} other What to listen.
     * @param {string} event An event.
     * @param {Bull.Events~callback} callback A callback.
     */

    /**
     * Subscribe to an event of other object. Fired once. Will be automatically unsubscribed on view removal.
     *
     * @function listenToOnce
     * @memberof Bull.Events
     * @param {Object} other What to listen.
     * @param {string} event An event.
     * @param {Bull.Events~callback} callback A callback.
     */

    /**
     * Stop listening to other object. No arguments will remove all listeners.
     *
     * @function stopListening
     * @memberof Bull.Events
     * @param {Object} [other] To remove listeners to a specific object.
     * @param {string} [event] To remove listeners to a specific event.
     * @param {Bull.Events~callback} [callback] To remove listeners to a specific callback.
     */

    /**
     * DOM event listeners.
     *
     * @typedef {Object.<string, Bull.View~domEventCallback>} Bull.View.DomEvents
     */

    /**
     * @typedef {Object} Bull.View~nestedViewItemDefs
     * @property {string} name A name.
     * @property {string|Bull.View|boolean} [view] A view.
     * @property {string} [template] A template.
     * @property {boolean} [notToRender] Not to render.
     * @property {string} [selector] A relative selector.
     * @property {string} [fullSelector] A full selector.
     * @property {Object.<string, *>} [options] Options.
     */

    /**
     * Stores indications to elements with already delegated events.
     *
     * @type {WeakMap<HTMLElement, true>}
     */
    const elementDelegatedMap = new WeakMap();

    /**
     * A view.
     *
     * @alias Bull.View
     */
    class View {

        /**
         * @param {Object.<string, *>} [options]
         */
        constructor(options = {}) {
            this.cid = _.uniqueId('view');

            if ('model' in options) {
                this.model = options.model;
            }

            if ('collection' in options) {
                this.collection = options.collection;
            }

            this.$el = $();
            this.options = options;

            const fullSelector = options.fullSelector || options.el;

            if (fullSelector) {
                this.setSelector(fullSelector);
            }
        }

        /**
         * An ID, unique among all views.
         * @type {string}
         * @public
         */
        cid

        /**
         * @type {string}
         * @private
         */
        _elementSelector

        /**
         * Is component. Components does not require a DOM container defined by a parent view.
         * Should have one root DOM element.
         *
         * An experimental feature.
         *
         * @readonly
         * @type {boolean}
         */
        isComponent = false

        /**
         * A DOM element.
         *
         * @type {HTMLElement}
         */
        element

        /**
         * A template name/path.
         *
         * @type {string|null}
         * @protected
         */
        template = null

        /**
         * Template content. Alternative to specifying a template name/path.
         *
         * @type {string|null}
         * @protected
         */
        templateContent = null

        /**
         * DOM event listeners. Recommended to use `addHandler` method instead.
         *
         * @type {Bull.View.DomEvents}
         * @protected
         */
        events = null

        /**
         * Not to render a view automatically when a view tree is built (ready).
         * Afterward, it can be rendered manually.
         *
         * @type {boolean}
         * @protected
         */
        notToRender = false

        /**
         * Layout definitions.
         *
         * @type {Object|null}
         * @protected
         * @internal
         */
        _layoutDefs = null

        /**
         * Layout data.
         *
         * @type {Object|null}
         * @protected
         */
        layoutData = null

        /**
         * Whether the view is ready for rendering (all necessary data is loaded).
         *
         * @type {boolean}
         * @public
         */
        isReady = false

        /**
         * Definitions for nested views that should be automatically created.
         * Format: viewKey => view defs.
         *
         * Example: ```
         * {
         *   body: {
         *     view: 'view/path/body',
         *     selector: '> .body',
         *   }
         * }
         * ```
         *
         * @type {Object.<string, Bull.View~NestedViewItem>|null}
         * @protected
         */
        views = null

        /**
         * A list of options to be automatically passed to child views.
         *
         * @type {string[]|null}
         * @protected
         */
        optionsToPass = null

        /**
         * Nested views.
         *
         * @type {Object.<string, View>}
         * @protected
         * @internal
         */
        nestedViews = null

        /**
         * @private
         * @type {Bull.Factory}
         */
        _factory = null

        /**
         * A helper.
         *
         * @protected
         */
        _helper = null

        /**
         * @type {string|null}
         * @private
         */
        _template = null
        /**
         * @type {Object.<string, Bull.View~nestedViewItemDefs>|null}
         * @private
         */
        _nestedViewDefs = null
        /**
         * @type {Bull.Templator}
         * @private
         */
        _templator = null
        /** @private */
        _renderer = null
        /**
         * @type {Bull.Layouter}
         * @private
         */
        _layouter = null
        /** @private */
        _templateCompiled = null
        /** @private */
        _parentView = null
        /** @private */
        _path = ''
        /** @private */
        _wait = false
        /** @private */
        _waitViewList = null
        /** @private */
        _nestedViewsFromLayoutLoaded = false
        /** @private */
        _readyConditionList = null
        /** @private */
        _isRendered = false
        /** @private */
        _isFullyRendered = false
        /** @private */
        _isBeingRendered = false
        /** @private */
        _isRemoved = false
        /** @private */
        _isRenderCanceled = false
        /** @private */
        _preCompiledTemplates = null

        /**
         * Set a DOM element selector.
         *
         * @param {string} selector A full DOM selector.
         */
        setElement(selector) {
            this.undelegateEvents();
            this._setElement(selector);
            this._delegateEvents();
        }

        /**
         * Removes all view's delegated events. Useful if you want to disable
         * or remove a view from the DOM temporarily.
         */
        undelegateEvents() {
            if (!this.$el) {
                return;
            }

            this.$el.off(`.delegateEvents${this.cid}`);
        }

        /** @private */
        _delegateEvents() {
            if (this.element) {
                elementDelegatedMap.set(this.element, true);
            }

            const events = _.result(this, 'events');

            if (!events) {
                return;
            }

            this.undelegateEvents();

            for (const key in events) {
                let method = events[key];

                if (typeof method !== 'function') {
                    method = this[method];
                }

                if (!method) {
                    continue;
                }

                const match = key.match(delegateEventSplitter);

                this._delegate(match[1], match[2], method.bind(this));
            }
        }

        /** @private */
        _delegate(eventName, selector, listener) {
            this.$el.on(`${eventName}.delegateEvents${this.cid}`, selector, listener);
        }

        /**
         * Add a DOM event handler. To be called in `setup` method.
         *
         * @param {Bull.View~domEventType} type An event type.
         * @param {string} selector A CSS selector.
         * @param {Bull.View~domEventHandlerCallback|string} handler A handler.
         */
        addHandler(type, selector, handler) {
            const key = type + ' ' + selector;

            if (typeof handler === 'function') {
                // noinspection JSUnresolvedReference
                this.events[key] = (e) => handler(e.originalEvent, e.currentTarget);

                return;
            }

            if (typeof this[handler] !== 'function') {
                console.warn(`Could not add event handler. No '${handler}' method.`);

                return;
            }

            // noinspection JSUnresolvedReference
            this.events[key] = (e) => this[handler](e.originalEvent, e.currentTarget);
        }

        /**
         * To be run by the view-factory after instantiating. Should not be overridden.
         * Not called from the constructor to be able to use ES6 classes with property initializers,
         * as overridden properties not available in a constructor.
         *
         * @param {{
         *   factory: Bull.Factory,
         *   renderer: Bull.Renderer,
         *   templator: Bull.Templator,
         *   layouter: Bull.Layouter,
         *   helper?: Object,
         *   onReady?: function(Bull.View): void,
         *   preCompiledTemplates?: Object,
         * }} data
         * @internal
         */
        _initialize(data) {
            /**
             * @type {Bull.Factory}
             * @private
             */
            this._factory = data.factory;

            /**
             * @type {Bull.Renderer}
             * @private
             */
            this._renderer = data.renderer;

            /**
             * @type {Bull.Templator}
             * @private
             */
            this._templator = data.templator;

            /**
             * @type {Bull.Layouter}
             * @private
             */
            this._layouter = data.layouter;

            /**
             * @type {(function(): void)|null}
             * @private
             */
            this._onReady = data.onReady || null;

            /**
             * @type {Object|null}
             * @private
             */
            this._helper = data.helper || null;

            /**
             * @type {Object}
             * @private
             */
            this._preCompiledTemplates = data.preCompiledTemplates || {};

            this.events = _.clone(this.events || {});
            this.notToRender = ('notToRender' in this.options) ? this.options.notToRender : this.notToRender;

            this.nestedViews = {};
            /** @private */
            this._nestedViewDefs = {};

            if (this._waitViewList == null) {
                /** @private */
                this._waitViewList = [];
            }

            /** @private */
            this._waitPromiseCount = 0;

            if (this._readyConditionList == null) {
                /** @private */
                this._readyConditionList = [];
            }

            this.optionsToPass = this.options.optionsToPass || this.optionsToPass || [];

            const merge = function (target, source) {
                for (const prop in source) {
                    if (typeof target === 'object') {
                        if (prop in target) {
                            merge(target[prop], source[prop]);
                        } else {
                            target[prop] = source[prop];
                        }
                    }
                }

                return target;
            };

            if (this.views || this.options.views) {
                this.views = merge(this.options.views || {}, this.views || {});
            }

            this.init();
            this.setup();
            this.setupFinal();

            this.template = this.options.template || this.template;

            /** @private */
            this._layoutDefs = this.options.layoutDefs || this.options._layout;
            /**
             * @private
             * @type {Object|null}
             */
            this.layoutData = this.options.layoutData || this.layoutData;
            /**
             * @type {string|null}
             * @private
             */
            this._template = this.templateContent || this.options.templateContent || this._template;

            if (this._template != null && this._templator.compilable) {
                /** @private */
                this._templateCompiled = this._templator.compileTemplate(this._template);
            }

            if (this._elementSelector) {
                this.setElementInAdvance(this._elementSelector);
            }

            const loadNestedViews = () => {
                this._loadNestedViews(() => {
                    this._nestedViewsFromLayoutLoaded = true;

                    this._tryReady();
                });
            };

            if (this._layoutDefs !== null) {
                loadNestedViews();

                return;
            }

            if (this.views != null) {
                loadNestedViews();

                return;
            }

            this._nestedViewsFromLayoutLoaded = true;

            this._tryReady();
        }

        /**
         * Compose template data. A key => value result will be passed to
         * a template.
         *
         * @protected
         * @returns {Object.<string, *>|{}}
         */
        data() {
            return {};
        }

        /**
         * Initialize the view. Is invoked before #setup.
         *
         * @protected
         */
        init() {}

        /**
         * Set up the view. Is invoked after #init.
         *
         * @protected
         */
        setup() {}

        /**
         * Additional setup. Is invoked after #setup.
         * Useful to let developers override setup logic w/o needing to call
         * a parent method in right order.
         *
         * @protected
         */
        setupFinal() {}

        /**
         * Set a view container element if it doesn't exist yet. It will call setElement after render.
         *
         * @param {string} fullSelector A full DOM selector.
         * @protected
         */
        setElementInAdvance(fullSelector) {
            if (this._setElementInAdvancedInProcess) {
                return;
            }

            this._setElementInAdvancedInProcess = true;

            this.on('after:render-internal', () => {
                this._setElementInAdvancedInProcess = false;

                if (this.element && elementDelegatedMap.get(this.element)) {
                    return;
                }

                this.setElement(fullSelector);
            });
        }

        /**
         * Get a full DOM element selector.
         *
         * @public
         * @return {string|null}
         */
        getSelector() {
            return this._elementSelector || null
        }

        /**
         * Set a full DOM element selector.
         *
         * @public
         * @param {string} selector A selector.
         */
        setSelector(selector) {
            this._elementSelector = selector;

            // For backward compatibility.
            this.options.el = selector;
        }

        /**
         * Checks whether the view has been already rendered
         *
         * @public
         * @return {boolean}
         */
        isRendered() {
            return this._isRendered;
        }

        /**
         * Checks whether the view has been fully rendered (afterRender has been executed).
         *
         * @public
         * @return {boolean}
         */
        isFullyRendered() {
            return this._isFullyRendered;
        }

        /**
         * Whether the view is being rendered at the moment.
         *
         * @public
         * @return {boolean}
         */
        isBeingRendered() {
            return this._isBeingRendered;
        }

        /**
         * Whether the view is removed.
         *
         * @public
         * @return {boolean}
         */
        isRemoved() {
            return this._isRemoved;
        }

        /**
         * Cancel rendering.
         */
        cancelRender() {
            if (!this.isBeingRendered()) {
                return;
            }

            this._isRenderCanceled = true;
        }

        /**
         * Un-cancel rendering.
         */
        uncancelRender() {
            this._isRenderCanceled = false;
        }

        /**
         * Render the view.
         *
         * @param {function()} [callback] Deprecated. Use promise.
         * @return {Promise<this>}
         */
        render(callback) {
            this._isRendered = false;
            this._isFullyRendered = false;

            return new Promise(resolve => {
                this._getPreparedElement(templateElement => {
                    if (this._isRenderCanceled) {
                        this._isRenderCanceled = false;
                        this._isBeingRendered = false;

                        return;
                    }

                    this.isComponent ?
                        this._renderComponentInDom(templateElement) :
                        this._renderInDom(templateElement);

                    if (!this.element) {
                        const message = this._elementSelector ?
                            `Could not set element '${this._elementSelector}'.` :
                            `Could not set element. No selector.`;

                        console.warn(message);
                    }

                    if (this.element) {
                        this._afterRender();
                    }

                    if (typeof callback === 'function') {
                        callback();
                    }

                    resolve(this);
                });
            });
        }

        /**
         * @param {HTMLTemplateElement} element
         * @private
         */
        _renderComponentInDom(element) {
            if (!this.element) {
                if (!this._elementSelector) {
                    console.warn(`Can't render component. No DOM selector.`);

                    return;
                }

                this._setElement(this._elementSelector);
            }

            if (!this.element) {
                console.warn(`Can't render component. No DOM element.`);

                return;
            }

            const newElement = element.content.children[0];

            const parent = this.element.parentElement;
            parent.replaceChild(newElement, this.element);

            this._setElementInternal(newElement);

            this.element.setAttribute('data-view-cid', this.cid);

            this.undelegateEvents();
            this._delegateEvents();
        }

        /**
         * @param {HTMLTemplateElement} element
         * @private
         */
        _renderInDom(element) {
            if (!this.element && this._elementSelector) {
                this.setElement(this._elementSelector);
            } else if (this._elementSelector) {
                this.undelegateEvents();
                this._delegateEvents();
            }

            if (!this.element) {
                return;
            }

            this.element.setAttribute('data-view-cid', this.cid);

            const childNodes = element.content.childNodes;

            this.element.replaceChildren(...childNodes);
        }

        /**
         * @typedef {Object} Bull.View~reRenderOptions
         * @property {boolean} [force] To render if was not re-render.
         * @property {string[]} [keep] Views not to be re-rendered. View keys.
         * @since 1.2.15
         */

        /**
         * @private
         */
        _keepElementOnRender = false

        /**
         * Re-render the view.
         *
         * @param {Bull.View~reRenderOptions|true} [options] Options.
         * @return {Promise<this>}
         */
        reRender(options = {}) {
            if (typeof options !== 'object') {
                options = {force: options};
            }

            const hasKeep = options.keep && options.keep.length;

            if (hasKeep && (this.isRendered() || this.isBeingRendered())) {
                for (const key of options.keep) {
                    const subView = this.getView(key);

                    if (!subView) {
                        continue;
                    }

                    subView._keepElementOnRender = true;
                }
            }

            if (this.isRendered()) {
                return this.render();
            }

            if (this.isBeingRendered()) {
                return new Promise((resolve, reject) => {
                    this.once('after:render', () => {
                        this.render()
                            .then(() => resolve(this))
                            .catch(reject);
                    });
                });
            }

            if (options.force) {
                return this.render();
            }

            // Don't reject, preventing an exception on a non-caught promise.
            return new Promise(() => {});
        }

        /** @private */
        _afterRender() {
            this._isBeingRendered = false;

            this.trigger('after:render-internal', this);

            if (this.element) {
                this._isRendered = true;
            } else {
                return;
            }

            for (const key in this.nestedViews) {
                const nestedView = this.nestedViews[key];

                if (!nestedView.notToRender) {
                    nestedView._afterRender();
                }
            }

            this.afterRender();

            this.trigger('after:render', this);

            this._isFullyRendered = true;
        }

        /**
         * Executed after render.
         *
         * @protected
         */
        afterRender() {}

        /**
         * Proceed when rendered.
         *
         * @return {Promise<void>}
         */
        whenRendered() {
            if (this.isRendered()) {
                return Promise.resolve();
            }

            return new Promise(resolve => {
                this.once('after:render', () => resolve());
            });
        }

        /** @private */
        _tryReady() {
            if (this.isReady) {
                return;
            }

            if (this._wait) {
                return;
            }

            if (!this._nestedViewsFromLayoutLoaded) {
                return;
            }

            for (let i = 0; i < this._waitViewList.length; i++) {
                if (!this.hasView(this._waitViewList[i])) {
                    return;
                }
            }

            if (this._waitPromiseCount) {
                return;
            }

            for (let i = 0; i < this._readyConditionList.length; i++) {
                if (typeof this._readyConditionList[i] === 'function') {
                    if (!this._readyConditionList[i]()) {
                        return;
                    }
                }
                else {
                    if (!this._readyConditionList) {
                        return;
                    }
                }
            }

            this._makeReady();
        }

        /** @private */
        _makeReady() {
            this.isReady = true;
            this.trigger('ready');

            if (typeof this._onReady === 'function') {
                this._onReady(this);
            }
        }

        /**
         * @private
         * @param {Bull.View~nestedViewItemDefs[]} [list]
         */
        _addDefinedNestedViewDefs(list) {
            for (const name in this.views) {
                const o = _.clone(this.views[name]);

                o.name = name;

                list.push(o);

                this._nestedViewDefs[name] = o;
            }

            return list;
        }

        /**
         * @private
         * @return {Bull.View~nestedViewItemDefs[]}
         */
        _getNestedViewDefsFromLayout() {
            const itemList = this._layouter.findNestedViews(this._layoutDefs);

            if (Object.prototype.toString.call(itemList) !== '[object Array]') {
                throw new Error(`Bad layout. It should be an array.`);
            }

            const nestedViewDefsFiltered = [];

            for (const item of itemList) {
                const key = item.name;

                this._nestedViewDefs[key] = item;

                if ('view' in item && item.view === true) {
                    if (!('template' in item)) {
                        continue;
                    }
                }

                nestedViewDefsFiltered.push(item);
            }

            return nestedViewDefsFiltered;
        }

        /**
         * @private
         * @param {function()} callback
         */
        _loadNestedViews(callback) {
            const nestedViewDefs = this._layoutDefs != null ?
                this._getNestedViewDefsFromLayout() : [];

            this._addDefinedNestedViewDefs(nestedViewDefs);

            let count = nestedViewDefs.length;
            let loaded = 0;

            const tryReady = () => {
                if (loaded === count) {
                    callback();
                }
            };

            tryReady();

            nestedViewDefs.forEach(/** Bull.View~nestedViewItemDefs */def => {
                const key = def.name;
                let viewName = this._factory.defaultViewName;
                let view;

                if ('view' in def) {
                    if (def.view != null && typeof def.view === 'object') {
                        view = def.view;
                    } else {
                        viewName = def.view;
                    }
                }

                if (viewName === false) {
                    loaded++;
                    tryReady();

                    return;
                }

                if (typeof view === 'object') {
                    this.assignView(key, view, def.selector)
                        .then(() => {
                            loaded++;
                            tryReady();
                        });

                    return;
                }

                let options = {};

                if ('template' in def) {
                    options.template = def.template;
                }

                // noinspection JSUnresolvedReference
                const fullSelector = def.fullSelector || /** @type {string} */def.el;

                if (fullSelector) {
                    options.fullSelector = fullSelector;
                } else if ('selector' in def) {
                    options.selector = def.selector;
                }

                if ('options' in def) {
                    options = {...options, ...def.options};
                }

                if (this.model) {
                    options.model = this.model;
                }

                if (this.collection) {
                    options.collection = this.collection;
                }

                for (const k in this.optionsToPass) {
                    const name = this.optionsToPass[k];

                    options[name] = this.options[name];
                }

                this._factory.create(viewName, options, view => {
                    if ('notToRender' in def) {
                        view.notToRender = def.notToRender;
                    }

                    this.setView(key, view);

                    loaded++;
                    tryReady();
                });
            });
        }

        /**
         * @private
         * @return {Object.<string, *>}
         */
        _getData() {
            if (this.options.data) {
                if (typeof this.options.data === 'function') {
                    return this.options.data();
                }

                return this.options.data;
            }

            if (typeof this.data === 'function') {
                return this.data();
            }

            return this.data;
        }

        /**
         * @private
         * @return {{
         *     key: string,
         *     view: View,
         * }[]}
         */
        _getNestedViewsAsArray() {
            const nestedViewsArray = [];

            for (const key in this.nestedViews) {
                nestedViewsArray.push({
                    key: key,
                    view: this.nestedViews[key],
                });
            }

            return nestedViewsArray;
        }

        /**
         * @private
         * @param {function(Object.<string, {element: HTMLTemplateElement, view: Bull.View}>)} callback
         */
        _getNestedViewsMap(callback) {
            const data = {};
            const items = this._getNestedViewsAsArray();

            let loaded = 0;
            let count = items.length;

            const tryReady = () => {
                if (loaded === count) {
                    callback(data);
                }
            };

            tryReady();

            items.forEach(item => {
                const key = item.key;
                const view = item.view;

                if (view.notToRender || view._keepElementOnRender) {
                    const templateElement = this._createPlaceholderElement(view.cid);
                    data[key] = {
                        element: templateElement,
                        view: view,
                    };

                    if (view._keepElementOnRender && view.element) {
                        if (view.isComponent) {
                            templateElement.content.appendChild(view.element);
                        } else {
                            templateElement.content.append(...view.element.childNodes);
                        }

                        view._keepElementOnRender = false; // ?
                    }

                    loaded++;
                    tryReady();

                    return;
                }

                view._getPreparedElement(element => {
                    data[key] = {
                        element: element,
                        view: view,
                    };

                    loaded++;
                    tryReady();
                });
            });
        }

        /**
         * Provides the ability to modify template data right before render.
         *
         * @param {Object.<string, *>} data Data.
         */
        handleDataBeforeRender(data) {}

        /**
         * Called each time before render. Should be extended as async.
         *
         * @protected
         * @return {Promise|undefined}
         */
        prepareRender() {}

        /**
         * @public
         * @param {Bull.View~getPreparedElementCallback} callback.
         * @internal
         */
        _getPreparedElement(callback) {
            this._isBeingRendered = true;
            this.trigger('render', this);

            // Promise is avoided when not necessary to preserve execution flow.
            // Render can be processed synchronously when all data is ready.
            // Maybe in future this should be changed to process always asynchronously
            // but this would require extensive testing.

            const preparePromise = this.prepareRender();

            const proceed = () => {
                this._getNestedViewsMap(nestedMap => {
                    const data = {...this._getData()};

                    for (const [key, item] of Object.entries(nestedMap)) {
                        const cid = item.view.cid;

                        data[key] = `<template data-view-cid="${cid}"></template>`;
                    }

                    if (this.collection || null) {
                        data.collection = this.collection;
                    }

                    if (this.model || null) {
                        data.model = this.model;
                    }

                    data.viewObject = this;

                    this.handleDataBeforeRender(data);

                    this._getTemplate(template => {
                        const html = this._renderer.render(template, data);

                        const templateElement = document.createElement('template');
                        templateElement.innerHTML = html;

                        const templateContent = templateElement.content;

                        for (const item of Object.values(nestedMap)) {
                            const element = item.element;
                            const cid = item.view.cid;

                            const placeholder = templateContent.querySelector(`template[data-view-cid="${cid}"]`);

                            if (!placeholder) {
                                continue;
                            }

                            if (item.view.isComponent) {
                                let newElement = placeholder;

                                if (element.content.children.length) {
                                    newElement = element.content.children[0];

                                    placeholder.replaceWith(newElement);
                                }

                                item.view._setElementInternal(newElement);

                                newElement.setAttribute('data-view-cid', item.view.cid);
                            } else {
                                const parent = placeholder.parentElement;

                                placeholder.replaceWith(...element.content.childNodes);

                                item.view._setElementInternal(parent || undefined);

                                if (parent) {
                                    parent.setAttribute('data-view-cid', item.view.cid);
                                }
                            }
                        }

                        if (!this.isComponent) {
                            callback(templateElement);

                            return;
                        }

                        const root = document.createElement('template');

                        root.content.appendChild(templateContent.childNodes[0]);

                        callback(root);
                    });
                });
            };

            if (preparePromise) {
                preparePromise.then(() => proceed());

                return;
            }

            proceed();
        }

        /**
         * @param {HTMLElement|undefined} element
         * @private
         */
        _setElementInternal(element) {
            this.element = element;
            this.$el = $(element);

            /**
             * @todo Remove.
             * @deprecated
             */
            this.el = element;
        }

        /**
         * @private
         * @return {string|null}
         */
        _getTemplateName() {
            return this.template || null;
        }

        /** @private */
        _getLayoutData() {
            return this.layoutData;
        }

        /**
         * @private
         * @param {function(*)} callback
         */
        _getTemplate(callback) {
            if (
                this._templator &&
                this._templator.compilable &&
                this._templateCompiled !== null
            ) {
                callback(this._templateCompiled);

                return;
            }

            const _template = this._template || null;

            if (_template !== null) {
                callback(_template);

                return;
            }

            const templateName = this._getTemplateName();

            if (
                templateName &&
                templateName in (this._preCompiledTemplates || {})
            ) {
                callback(this._preCompiledTemplates[templateName]);

                return;
            }

            let layoutOptions = {};

            if (!templateName) {
                layoutOptions = {
                    data: this._getLayoutData(),
                    layout: this._layoutDefs,
                };
            }

            this._templator.getTemplate(templateName, layoutOptions, callback);
        }

        /** @private */
        _updatePath(parentPath, viewKey) {
            this._path = parentPath + '/' + viewKey;

            for (const key in this.nestedViews) {
                this.nestedViews[key]._updatePath(this._path, key);
            }
        }

        /**
         * @private
         * @param {string} key
         * @return {string|null}
         */
        _getSelectorForNestedView(key) {
            if (!(key in this._nestedViewDefs)) {
                return null;
            }

            if ('id' in this._nestedViewDefs[key]) {
                return '#' + this._nestedViewDefs[key].id;
            }

            const fullSelector = this._nestedViewDefs[key].fullSelector ||
                this._nestedViewDefs[key].el;

            if (fullSelector) {
                return fullSelector;
            }

            const currentEl = this.getSelector();

            if (!currentEl) {
                return null;
            }

            if ('selector' in this._nestedViewDefs[key]) {
                return currentEl + ' ' + this._nestedViewDefs[key].selector;
            }

            return `${currentEl} [data-view="${key}"]`;
        }

        /**
         * Whether the view has a nested view.
         *
         * @param {string} key A view key.
         * @return {boolean}
         */
        hasView(key) {
            return key in this.nestedViews;
        }

        /**
         * Get a nested view.
         *
         * @param {string} key A view key.
         * @return {View|null}
         */
        getView(key) {
            if (key in this.nestedViews) {
                return this.nestedViews[key];
            }

            return null;
        }

        /**
         * Get a nested view key by a view instance.
         *
         * @param {View} view A view.
         * @return {string|null}
         */
        getViewKey(view) {
            for (const key in this.nestedViews) {
                if (view === this.nestedViews[key]) {
                    return key;
                }
            }

            return null;
        }

        /**
         * Assign a view instance as nested.
         *
         * @param {string} key A view key.
         * @param {Bull.View} view A view.
         * @param {string} [selector] A relative selector.
         * @return {Promise<View>}
         */
        assignView(key, view, selector) {
            this.clearView(key);

            this._viewPromiseHash = this._viewPromiseHash || {};
            let promise = null;

            promise = this._viewPromiseHash[key] = new Promise(resolve => {
                if (!this.isReady) {
                    this.waitForView(key);
                }

                if (!selector && !view.getSelector()) {
                    selector = `[data-view-cid="${view.cid}"]`;
                }

                if (selector) {
                    view.setSelector(this.getSelector() + ' ' + selector);
                }

                view._initialize({
                    factory: this._factory,
                    layouter: this._layouter,
                    templator: this._templator,
                    renderer: this._renderer,
                    helper: this._helper,
                    onReady: () => this._assignViewCallback(key, view, resolve, promise),
                });
            });

            return promise;
        }

        /**
         * Create a nested view. The important method.
         *
         * @param {string} key A view key.
         * @param {string} viewName A view name/path.
         * @param {Bull.View~Options} options View options. Custom options can be passed as well.
         * @param {Function} [callback] Deprecated. Use a promise. Invoked once a nested view is ready (loaded).
         * @param {boolean} [wait=true] Set false if no need a parent view to wait till nested view loaded.
         * @return {Promise<View>}
         */
        createView(key, viewName, options, callback, wait) {
            this.clearView(key);

            this._viewPromiseHash = this._viewPromiseHash || {};

            let promise = null;

            promise = this._viewPromiseHash[key] = new Promise(resolve => {
                wait = (typeof wait === 'undefined') ? true : wait;

                if (wait) {
                    this.waitForView(key);
                }

                options = options || {};

                // noinspection JSUnresolvedReference
                const fullSelector = options.fullSelector || options.el;

                if (!fullSelector && options.selector) {
                    options.fullSelector = this.getSelector() + ' ' + options.selector;
                }

                this._factory.create(viewName, options, view => {
                    if (!options.fullSelector && !fullSelector) {
                        const fullSelector = `${this.getSelector()} [data-view-cid="${view.cid}"]`;

                        view.setSelector(fullSelector);
                    }

                    this._assignViewCallback(
                        key,
                        view,
                        resolve,
                        promise,
                        callback,
                        options.setViewBeforeCallback
                    );
                });
            });

            return promise;
        }

        /**
         * @param {string} key
         * @param {Bull.View} view
         * @param {function} resolve
         * @param {Promise} promise
         * @param {function} [callback]
         * @param {boolean} [setViewBeforeCallback]
         * @private
         */
        _assignViewCallback(
            key,
            view,
            resolve,
            promise,
            callback,
            setViewBeforeCallback
        ) {
            const previousView = this.getView(key);

            if (previousView) {
                previousView.cancelRender();
            }

            delete this._viewPromiseHash[key];

            // noinspection JSUnresolvedReference
            if (promise && promise._isToCancel) {
                if (!view.isRemoved()) {
                    view.remove();
                }

                return;
            }

            let isSet = false;

            if (this._isRendered || setViewBeforeCallback) {
                this.setView(key, view);

                isSet = true;
            }

            if (typeof callback === 'function') {
                callback.call(this, view);
            }

            resolve(view);

            if (!this._isRendered && !setViewBeforeCallback && !isSet) {
                this.setView(key, view);
            }
        }

        /**
         * Set a nested view.
         *
         * @param {string} key A view key.
         * @param {Bull.View} view A view name/path.
         * @param {string} [fullSelector] A full DOM selector for a view container.
         */
        setView(key, view, fullSelector) {
            fullSelector = fullSelector || this._getSelectorForNestedView(key) || view.getSelector();

            if (fullSelector) {
                this.isRendered() ?
                    view.setElement(fullSelector) :
                    view.setElementInAdvance(fullSelector);
            }

            if (key in this.nestedViews) {
                this.clearView(key);
            }

            this.nestedViews[key] = view;

            view._parentView = this;
            view._updatePath(this._path, key);

            this._tryReady();
        }

        /**
         * Clear a nested view. Initiates removal of the nested view.
         *
         * @param {string} key A view key.
         */
        clearView(key) {
            if (key in this.nestedViews) {
                this.nestedViews[key].remove();

                delete this.nestedViews[key];
            }

            this._viewPromiseHash = this._viewPromiseHash || {};

            const previousPromise = this._viewPromiseHash[key];

            if (previousPromise) {
                previousPromise._isToCancel = true;
            }
        }

        /**
         * Removes a nested view for cases when it's supposed that this view can be re-used in future.
         *
         * @param {string} key A view key.
         */
        unchainView(key) {
            if (key in this.nestedViews) {
                this.nestedViews[key]._parentView = null;
                this.nestedViews[key].undelegateEvents();

                delete this.nestedViews[key];
            }
        }

        /**
         * Get a parent view.
         *
         * @return {Bull.View}
         */
        getParentView() {
            return this._parentView;
        }

        /**
         * Has a parent view.
         *
         * @return {boolean}
         */
        hasParentView() {
            return !!this._parentView;
        }

        /**
         * Add a condition for the view getting ready.
         *
         * @param {(Function|boolean)} condition A condition.
         */
        addReadyCondition(condition) {
            this._readyConditionList.push(condition);
        }

        /**
         * Wait for a nested view.
         *
         * @protected
         * @param {string} key A view key.
         */
        waitForView(key) {
            this._waitViewList.push(key);
        }

        /**
         * Makes the view to wait for a promise (if a Promise is passed as a parameter).
         * Adds a wait condition if true is passed. Removes the wait condition if false.
         *
         * @protected
         * @param {Promise|boolean} wait A wait-promise or true/false.
         */
        wait(wait) {
            if (typeof wait === 'object' && (wait instanceof Promise || typeof wait.then === 'function')) {
                this._waitPromiseCount++;

                wait.then(() => {
                    this._waitPromiseCount--;
                    this._tryReady();
                });

                return;
            }

            if (typeof wait === 'function') {
                this._waitPromiseCount++;

                const promise = new Promise(resolve => {
                    // noinspection JSUnresolvedReference
                    resolve(wait.call(this));
                });

                promise.then(() => {
                    this._waitPromiseCount--;
                    this._tryReady();
                });

                return promise;
            }

            if (wait) {
                this._wait = true;

                return;
            }

            this._wait = false;
            this._tryReady();
        }

        /**
         * @private
         * @param {string} [cid]
         * @return {HTMLTemplateElement}
         */
        _createPlaceholderElement(cid) {
            const placeholder = document.createElement('template');
            placeholder.setAttribute('data-view-cid', cid || this.cid);

            return placeholder;
        }

        /** @private */
        _replaceWithPlaceholderElement() {
            if (!this.element) {
                return;
            }

            const parent = this.element.parentElement;

            parent.replaceChild(this._createPlaceholderElement(), this.element);
        }

        /**
         * Remove the view and all nested tree. Removes the element from DOM. Triggers the 'remove' event.
         *
         * @public
         * @param {boolean} [dontEmpty] Skips emptying the element container.
         */
        remove(dontEmpty) {
            this.cancelRender();

            for (const key in this.nestedViews) {
                this.clearView(key);
            }

            this.trigger('remove');
            this.onRemove();
            this.off();

            if (!dontEmpty) {
                this.isComponent ?
                    this._replaceWithPlaceholderElement() :
                    this.$el.empty();
            }

            this.stopListening();
            this.undelegateEvents();

            if (this.model && typeof this.model.off === 'function') {
                this.model.off(null, null, this);
            }

            if (this.collection && typeof this.collection.off === 'function') {
                this.collection.off(null, null, this);
            }

            this._isRendered = false;
            this._isFullyRendered = false;
            this._isBeingRendered = false;
            this._isRemoved = true;

            this.element = undefined;
            this.$el = $();
            this.el = undefined;

            return this;
        }

        /**
         * Called on view removal.
         *
         * @protected
         */
        onRemove() {}

        /** @private */
        _setElement(fullSelector) {
            if (this.element) {
                this._setElementInternal(this.element);

                return;
            }

            if (typeof fullSelector === 'string') {
                const parentView = this.getParentView();

                if (
                    parentView &&
                    parentView.isRendered() &&
                    parentView.element &&
                    parentView.getSelector() &&
                    fullSelector.indexOf(parentView.getSelector()) === 0
                ) {
                    const subSelector = fullSelector.slice(parentView.getSelector().length);

                    this.element = parentView.element.querySelector(`:scope ${subSelector}`);

                    this._setElementInternal(this.element);

                    return;
                }
            }

            this.element = document.querySelector(fullSelector);

            this._setElementInternal(this.element);
        }

        /**
         * Propagate an event to nested views.
         *
         * @public
         * @param {...*} parameters
         */
        propagateEvent(...parameters) {
            this.trigger.apply(this, arguments);

            for (const key in this.nestedViews) {
                const view = this.nestedViews[key];

                view.propagateEvent.apply(view, arguments);
            }
        }

        /**
         * Set a template. Experimental.
         *
         * @protected
         * @param {string} [template]
         */
        setTemplate(template) {
            this.template = template;

            this._templateCompiled = null;
        }

        /**
         * Set template content. Experimental.
         *
         * @protected
         * @param {string} templateContent
         */
        setTemplateContent(templateContent) {
            this._templateCompiled = this._templator.compileTemplate(templateContent);
        }

        /**
         * Subscribe to an event.
         *
         * @param {string} name An event.
         * @param {Bull.Events~callback} callback A callback.
         * @param {Object} [context] Deprecated.
         */
        on(name, callback, context) {
            return Events.on.call(this, name, callback, context);
        }

        /**
         * Subscribe to an event. Fired once.
         *
         * @param {string} name An event.
         * @param {Bull.Events~callback} callback A callback.
         * @param {Object} [context] Deprecated.
         */
        once(name, callback, context) {
            return Events.once.call(this, name, callback, context);
        }

        /**
         * Unsubscribe from an event or all events.
         *
         * @param {string} [name] From a specific event.
         * @param {Bull.Events~callback} [callback] From a specific callback.
         * @param {Object} [context] Deprecated.
         */
        off(name, callback, context) {
            return Events.off.call(this, name, callback, context);
        }

        /**
         * Subscribe to an event of other object.
         *
         * @param {Object} other What to listen.
         * @param {string} name An event.
         * @param {Bull.Events~callback} callback A callback.
         */
        listenTo(other, name, callback) {
            return Events.listenTo.call(this, other, name, callback);
        }

        /**
         * Subscribe to an event of other object. Fired once. Will be automatically unsubscribed on view removal.
         *
         * @param {Object} other What to listen.
         * @param {string} name An event.
         * @param {Bull.Events~callback} callback A callback.
         */
        listenToOnce(other, name, callback) {
            return Events.listenToOnce.call(this, other, name, callback);
        }

        /**
         * Stop listening to other object. No arguments will remove all listeners.
         *
         * @param {Object} [other] To remove listeners to a specific object.
         * @param {string} [name] To remove listeners to a specific event.
         * @param {Bull.Events~callback} [callback] To remove listeners to a specific callback.
         */
        stopListening(other, name, callback) {
            return Events.stopListening.call(this, other, name, callback);
        }

        /**
         * Trigger an event.
         *
         * @param {string} name An event.
         * @param {...*} parameters Arguments.
         */
        trigger(name, ...parameters) {
            return Events.trigger.call(this, name, ...parameters);
        }
    }

    //Object.assign(View.prototype, Events);

    const isEsClass = fn => {
        return typeof fn === 'function' &&
            Object.getOwnPropertyDescriptor(fn, 'prototype')?.writable === false;
    };

    View.extend = function (protoProps, staticProps) {
        const parent = this;

        let child;

        if (isEsClass(parent)) {
            const TemporaryHelperConstructor = function () {};

            child = function () {
                if (new.target) {
                    // noinspection JSCheckFunctionSignatures
                    const obj = Reflect.construct(parent, arguments, new.target);

                    for (const prop of Object.getOwnPropertyNames(obj)) {
                        if (typeof this[prop] !== 'undefined') {
                            obj[prop] = this[prop];
                        }
                    }

                    return obj;
                }

                // noinspection JSCheckFunctionSignatures
                return Reflect.construct(parent, arguments, TemporaryHelperConstructor);
            };

            _.extend(child, parent, staticProps);

            // noinspection JSUnresolvedReference
            child.prototype = _.create(parent.prototype, protoProps);
            child.prototype.constructor = child;
            // noinspection JSUnresolvedReference
            child.__super__ = parent.prototype;
            child.prototype.__isEs = true;

            TemporaryHelperConstructor.prototype = child.prototype;

            return child;
        }

        child = function () {
            // noinspection JSUnresolvedReference
            if (parent.prototype.__isEs) {
                // noinspection JSCheckFunctionSignatures
                return Reflect.construct(parent, arguments, new.target);
            }

            // noinspection JSUnresolvedReference
            return parent.apply(this, arguments);
        };

        _.extend(child, parent, staticProps);

        // noinspection JSUnresolvedReference
        child.prototype = _.create(parent.prototype, protoProps);
        child.prototype.constructor = child;
        // noinspection JSUnresolvedReference
        child.__super__ = parent.prototype;

        return child;
    };

    const delegateEventSplitter = /^(\S+)\s*(.*)$/;

    class Loader {

        /**
         * @param {{
         *     paths?: Object.<string, string>,
         *     exts?: Object.<string, string>,
         *     normalize?: Object.<string, string>,
         *     loaders?: Object.<string, function(*): void>,
         *     path?: function(string, string): void,
         *     isJson?: Object.<string, boolean>,
         * }}options
         */
        constructor(options) {
            options = {...options};

            this._paths = _.extend(this._paths, options.paths || {});
            this._exts = _.extend(this._exts, options.exts || {});
            this._normalize = _.extend(this._normalize, options.normalize || {});
            this._isJson = _.extend(this._isJson, options.isJson || {});
            this._externalLoaders = _.extend(this._externalLoaders, options.loaders || {});
            this._externalPathFunction = options.path || null;
        }

        _exts = {
            layout: 'json',
            template: 'tpl',
            layoutTemplate: 'tpl',
        }

        _paths = {
            layout: 'layouts',
            template: 'templates',
            layoutTemplate: 'templates/layouts',
        }

        _isJson = {
            layout: true,
        }

        _externalLoaders = {
            layout: null,
            template: null,
            layoutTemplate: null,
        }

        _externalPathFunction = null

        _normalize = {
            layouts: function (name) {
                return name;
            },
            templates: function (name) {
                return name;
            },
            layoutTemplates: function (name) {
                return name;
            },
        }

        getFilePath(type, name) {
            if (!(type in this._paths) || !(type in this._exts)) {
                throw new TypeError("Unknown resource type \"" + type + "\" requested in Bull.Loader.");
            }

            let namePart = name;

            if (type in this._normalize) {
                namePart = this._normalize[type](name);
            }

            let pathPart = this._paths[type];

            if (pathPart.substr(-1) === '/') {
                pathPart = pathPart.substr(0, pathPart.length - 1);
            }

            return pathPart + '/' + namePart + '.' + this._exts[type];
        }

        _callExternalLoader(type, name, callback) {
            if (type in this._externalLoaders && this._externalLoaders[type] !== null) {
                if (typeof this._externalLoaders[type] === 'function') {
                    this._externalLoaders[type](name, callback);

                    return true;
                }

                throw new Error("Loader for \"" + type + "\" in not a Function.");
            }

            return null;
        }

        load(type, name, callback) {
            let customCalled = this._callExternalLoader(type, name, callback);

            if (customCalled) {
                return;
            }

            let response, filePath;

            if (this._externalPathFunction != null) {
                filePath = this._externalPathFunction.call(this, type, name);
            } else {
                filePath = this.getFilePath(type, name);
            }

            filePath += '?_=' + new Date().getTime();

            let xhr = new XMLHttpRequest();

            xhr.open('GET', filePath, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onreadystatechange = () => {
                if (xhr.readyState === 4) {
                    response = xhr.responseText;

                    if (type in this._isJson) {
                        if (this._isJson[type]) {
                            let obj;

                            if (xhr.status === 404 || xhr.status === 403) {
                                throw new Error("Could not load " + type + " \"" + name + "\".");
                            }

                            try {
                                obj = JSON.parse(String(response));
                            }
                            catch (e) {
                                throw new SyntaxError(
                                    "Error while parsing " + type + " \"" + name + "\": (" + e.message + ").");
                            }

                            callback(obj);

                            return;
                        }
                    }

                    callback(response);
                }
            };

            xhr.send(null);
        }
    }

    /**
     * @alias Bull.Renderer
     */
    class Renderer {

        constructor(options) {
            options = options || {};

            this._render = options.method || this._render;
        }

        render(template, data) {
            return this._render.call(this, template, data);
        }

        _render(template, data) {
            return template(data, {allowProtoPropertiesByDefault: true});
        }
    }

    /**
     * @alias Bull.Layouter
     */
    class Layouter {

        /**
         * @param {Object} layoutDefs
         * @return {Bull.View~nestedViewItemDefs[]}
         */
        findNestedViews(layoutDefs) {
            if (!layoutDefs) {
                throw new Error("Can not find nested views. No layout data and name.");
            }

            let layout = layoutDefs.layout;
            let viewPathList = [];

            const uniqName = (name, count) => {
                let modName = name;

                if (typeof count !== 'undefined') {
                    modName = modName + '_' + count;
                } else {
                    count = 0;
                }

                for (let i in viewPathList) {
                    if (viewPathList[i].name === modName) {
                        return uniqName(name, count + 1);
                    }
                }

                return modName;
            };

            const getDefsForNestedView = (defsInLayout) => {
                let defs = {};

                let params = [
                    'view',
                    'layout',
                    'notToRender',
                    'options',
                    'template',
                    'id',
                    'selector',
                    'el',
                ];

                for (let i in params) {
                    let param = params[i];

                    if (param in defsInLayout) {
                        defs[param] = defsInLayout[param];
                    }
                }

                if ('name' in defsInLayout) {
                    defs.name = uniqName(defsInLayout.name);
                }

                return defs;
            };

            const seekForViews = (tree) => {
                for (let key in tree) {
                    if (tree[key] == null || typeof tree[key] !== 'object') {
                        continue;
                    }

                    if ('view' in tree[key] || 'layout' in tree[key] || 'template' in tree[key]) {
                        let def = getDefsForNestedView(tree[key]);

                        if ('name' in def) {
                            viewPathList.push(def);
                        }

                        continue;
                    }

                    seekForViews(tree[key]);
                }
            };

            seekForViews(layout);

            return viewPathList;
        }
    }

    /**
     * @alias Bull.Templator
     */
    class Templator {

        /**
         * @param {{
         *   loader?: Loader,
         * }|null} data
         */
        constructor(data) {
            data = data || {};

            this._templates = {};
            this._layoutTemplates = {};

            /**
             * @type {Loader|null}
             * @private
             */
            this._loader = data.loader || null;

            if ('compilable' in data) {
                this.compilable = data.compilable;
            }
        }

        compilable = true

        _templates = null
        _layoutTemplates = null

        addTemplate(name, template) {
            this._templates[name] = template;
        }

        /**
         * @param {string} [name]
         * @param {{
         *     layout?: Object,
         *     data?: Object.<string, *>
         * }} [layoutOptions]
         * @param callback
         */
        getTemplate(name, layoutOptions,  callback) {
            layoutOptions = layoutOptions || {};

            if (!layoutOptions.layout && !name) {
                throw new Error(`Can not get template. Not enough data passed.`);
            }

            /**
             * @return {boolean}
             */
            const tryCache = () => {
                if (!name || layoutOptions.layout) {
                    return false;
                }

                const template = this._getCachedTemplate(name);

                if (!template) {
                    return false;
                }

                callback(template);

                return true;
            };

            if (tryCache()) {
                return;
            }

            let then = (template) => {
                if (tryCache()) {
                    // Prevents re-compiling when the same template was requested multiple times in parallel.
                    return;
                }

                if (this.compilable) {
                    template = this.compileTemplate(template);
                }

                this._templates[name] = template;

                callback(template);
            };

            if (layoutOptions.layout) {
                this._buildTemplate(layoutOptions.layout, layoutOptions.data, then);

                return;
            }

            this._loader.load('template', name, then);
        }

        compileTemplate(template) {
            if (typeof Handlebars !== 'undefined') {
                return Handlebars.compile(template);
            }

            return template;
        }

        _getCachedTemplate(templateName) {
            if (templateName in this._templates) {
                return this._templates[templateName];
            }

            return false;
        }

        _getCachedLayoutTemplate(layoutType) {
            if (layoutType in this._layoutTemplates) {
                return this._layoutTemplates[layoutType];
            }

            return false;
        }

        _cacheLayoutTemplate(layoutType, layoutTemplate) {
            this._layoutTemplates[layoutType] = layoutTemplate;
        }

        _buildTemplate(layoutDefs, data, callback) {
            let layoutType = layoutDefs.type || 'default';

            const proceed = layoutTemplate => {
                let injection = _.extend(layoutDefs, data || {});
                let template = _.template(layoutTemplate, injection);

                if (typeof template === 'function') {
                    template = template(injection);
                }

                callback(template);
            };

            let layoutTemplate = this._getCachedLayoutTemplate(layoutType);

            if (layoutTemplate) {
                proceed(layoutTemplate);

                return;
            }

            this._loader.load('layoutTemplate', layoutType, layoutTemplate => {
                this._cacheLayoutTemplate(layoutType, layoutTemplate);

                proceed(layoutTemplate);
            });
        }
    }

    let root = window;

    /**
     * @callback viewLoader
     * @param {string} viewName,
     * @param {function(): void} callback
     */

    /**
     * A view factory.
     *
     * @alias Bull.Factory
     */
    class Factory {

        /**
         * @param {{
         *   defaultViewName?: string,
         *   customLoader?: Object,
         *   customRenderer?: Object,
         *   customLayouter?: Object,
         *   customTemplator?: Object,
         *   helper?: Object,
         *   viewLoader?: function(string, function(Bull.View)),
         *   resources?: {
         *       loaders?: {
         *           template?: function(string, function(string)),
         *           layoutTemplate?: function(string, function(string)),
         *       }
         *   },
         *   preCompiledTemplates?: Object.<string, function()>,
         * }|null} options Configuration options.
         * <ul>
         *  <li>defaultViewName: {String} Default name for views when it is not defined.</li>
         *  <li>viewLoader: {Function} Function that loads view class ({Function} in javascript)
         *  by the given view name and callback function as parameters. Here you can load js code using sync XHR request.
         *  If not defined it will look up classes in window object.</li>
         *  <li>helper: {Object} View Helper that will be injected into all views.</li>
         *  <li>resources: {Object} Resources loading options: paths, exts, loaders. Example: <br>
         *    <i>{
         *      paths: { // Custom paths for resource files.
         *        templates: 'resources/templates',
         *        layoutTemplate: 'resources/templates/layouts',
         *      },
         *      exts: { // Custom extensions of resource files.
         *        templates: 'tpl',
         *      },
         *      loaders: {}, // Custom resources loading functions. Define it if some type of resources needs to be loaded
         *      path: function (type, name) {} // Custom path function. Should return path to the needed resource.
         *    }</i>
         *  </li>
         *  <li>rendering: {Object} Rendering options: method (Method is the custom function for a rendering.
         *  Define it if you want to use another templating engine. <i>Function (template, data)</i>).</li>
         *  <li>templating: {Object} Templating options: {bool} compilable (If templates are compilable (like Handlebars).
         *  True by default.)</li>
         * </ul>
         */
        constructor(options) {
            options = options || {};

            this.defaultViewName = options.defaultViewName || this.defaultViewName;

            this._loader = options.customLoader || new Loader(options.resources || {});
            this._renderer = options.customRenderer || new Renderer();
            this._layouter = options.customLayouter || new Layouter();
            this._templator = options.customTemplator || new Templator({loader: this._loader});

            this._helper = options.helper || null;

            this._viewClassHash = {};
            this._getViewClassFunction = options.viewLoader || this._getViewClassFunction;
            this._viewLoader = this._getViewClassFunction;
            this._preCompiledTemplates = options.preCompiledTemplates;
        }

        /** @private */
        defaultViewName = 'View'
        /** @private */
        _layouter = null
        /** @private */
        _templator = null
        /** @private */
        _renderer = null
        /** @private */
        _loader = null
        /** @private */
        _helper = null
        /** @private */
        _viewClassHash = null
        /** @private */
        _viewLoader = null

        /**
         * Create a view.
         *
         * @param {string} viewName A view name/path.
         * @param {Bull.View~Options} [options] Options.
         * @param {function(Bull.View)} [callback] Invoked once the view is ready.
         */
        create(viewName, options, callback) {
            this._getViewClass(viewName, viewClass => {
                if (typeof viewClass === 'undefined') {
                    throw new Error(`A view class '${viewName}' not found.`);
                }

                const view = new viewClass(options || {});

                this.prepare(view, callback);
            });
        }

        /**
         * Prepare a view instance.
         *
         * @param {Bull.View} view A view.
         * @param {function(Bull.View)} [callback] Invoked once the view is ready.
         */
        prepare(view, callback) {
            view._initialize({
                factory: this,
                layouter: this._layouter,
                templator: this._templator,
                renderer: this._renderer,
                helper: this._helper,
                preCompiledTemplates: this._preCompiledTemplates,
                onReady: callback,
            });
        }

        /** @private */
        _getViewClassFunction(viewName, callback) {
            let viewClass = root[viewName];

            if (typeof viewClass !== "function") {
                throw new Error("function \"" + viewClass + "\" not found.");
            }

            callback(viewClass);
        }

        /** @private */
        _getViewClass(viewName, callback) {
            if (viewName in this._viewClassHash) {
                callback(this._viewClassHash[viewName]);

                return;
            }

            this._getViewClassFunction(viewName, (viewClass) => {
                this._viewClassHash[viewName] = viewClass;

                callback(viewClass);
            });
        }
    }

    exports.Events = Events;
    exports.Factory = Factory;
    exports.View = View;

}));

Espo.loader.setContextId(null);

define('@textcomplete/core', (function () { 'use strict';

	function getDefaultExportFromCjs (x) {
		return x && x.__esModule && Object.prototype.hasOwnProperty.call(x, 'default') ? x['default'] : x;
	}

	var dist = {};

	var Completer = {};

	var eventemitter3 = {exports: {}};

	var hasRequiredEventemitter3;

	function requireEventemitter3 () {
		if (hasRequiredEventemitter3) return eventemitter3.exports;
		hasRequiredEventemitter3 = 1;
		(function (module) {

			var has = Object.prototype.hasOwnProperty
			  , prefix = '~';

			/**
			 * Constructor to create a storage for our `EE` objects.
			 * An `Events` instance is a plain object whose properties are event names.
			 *
			 * @constructor
			 * @private
			 */
			function Events() {}

			//
			// We try to not inherit from `Object.prototype`. In some engines creating an
			// instance in this way is faster than calling `Object.create(null)` directly.
			// If `Object.create(null)` is not supported we prefix the event names with a
			// character to make sure that the built-in object properties are not
			// overridden or used as an attack vector.
			//
			if (Object.create) {
			  Events.prototype = Object.create(null);

			  //
			  // This hack is needed because the `__proto__` property is still inherited in
			  // some old browsers like Android 4, iPhone 5.1, Opera 11 and Safari 5.
			  //
			  if (!new Events().__proto__) prefix = false;
			}

			/**
			 * Representation of a single event listener.
			 *
			 * @param {Function} fn The listener function.
			 * @param {*} context The context to invoke the listener with.
			 * @param {Boolean} [once=false] Specify if the listener is a one-time listener.
			 * @constructor
			 * @private
			 */
			function EE(fn, context, once) {
			  this.fn = fn;
			  this.context = context;
			  this.once = once || false;
			}

			/**
			 * Add a listener for a given event.
			 *
			 * @param {EventEmitter} emitter Reference to the `EventEmitter` instance.
			 * @param {(String|Symbol)} event The event name.
			 * @param {Function} fn The listener function.
			 * @param {*} context The context to invoke the listener with.
			 * @param {Boolean} once Specify if the listener is a one-time listener.
			 * @returns {EventEmitter}
			 * @private
			 */
			function addListener(emitter, event, fn, context, once) {
			  if (typeof fn !== 'function') {
			    throw new TypeError('The listener must be a function');
			  }

			  var listener = new EE(fn, context || emitter, once)
			    , evt = prefix ? prefix + event : event;

			  if (!emitter._events[evt]) emitter._events[evt] = listener, emitter._eventsCount++;
			  else if (!emitter._events[evt].fn) emitter._events[evt].push(listener);
			  else emitter._events[evt] = [emitter._events[evt], listener];

			  return emitter;
			}

			/**
			 * Clear event by name.
			 *
			 * @param {EventEmitter} emitter Reference to the `EventEmitter` instance.
			 * @param {(String|Symbol)} evt The Event name.
			 * @private
			 */
			function clearEvent(emitter, evt) {
			  if (--emitter._eventsCount === 0) emitter._events = new Events();
			  else delete emitter._events[evt];
			}

			/**
			 * Minimal `EventEmitter` interface that is molded against the Node.js
			 * `EventEmitter` interface.
			 *
			 * @constructor
			 * @public
			 */
			function EventEmitter() {
			  this._events = new Events();
			  this._eventsCount = 0;
			}

			/**
			 * Return an array listing the events for which the emitter has registered
			 * listeners.
			 *
			 * @returns {Array}
			 * @public
			 */
			EventEmitter.prototype.eventNames = function eventNames() {
			  var names = []
			    , events
			    , name;

			  if (this._eventsCount === 0) return names;

			  for (name in (events = this._events)) {
			    if (has.call(events, name)) names.push(prefix ? name.slice(1) : name);
			  }

			  if (Object.getOwnPropertySymbols) {
			    return names.concat(Object.getOwnPropertySymbols(events));
			  }

			  return names;
			};

			/**
			 * Return the listeners registered for a given event.
			 *
			 * @param {(String|Symbol)} event The event name.
			 * @returns {Array} The registered listeners.
			 * @public
			 */
			EventEmitter.prototype.listeners = function listeners(event) {
			  var evt = prefix ? prefix + event : event
			    , handlers = this._events[evt];

			  if (!handlers) return [];
			  if (handlers.fn) return [handlers.fn];

			  for (var i = 0, l = handlers.length, ee = new Array(l); i < l; i++) {
			    ee[i] = handlers[i].fn;
			  }

			  return ee;
			};

			/**
			 * Return the number of listeners listening to a given event.
			 *
			 * @param {(String|Symbol)} event The event name.
			 * @returns {Number} The number of listeners.
			 * @public
			 */
			EventEmitter.prototype.listenerCount = function listenerCount(event) {
			  var evt = prefix ? prefix + event : event
			    , listeners = this._events[evt];

			  if (!listeners) return 0;
			  if (listeners.fn) return 1;
			  return listeners.length;
			};

			/**
			 * Calls each of the listeners registered for a given event.
			 *
			 * @param {(String|Symbol)} event The event name.
			 * @returns {Boolean} `true` if the event had listeners, else `false`.
			 * @public
			 */
			EventEmitter.prototype.emit = function emit(event, a1, a2, a3, a4, a5) {
			  var evt = prefix ? prefix + event : event;

			  if (!this._events[evt]) return false;

			  var listeners = this._events[evt]
			    , len = arguments.length
			    , args
			    , i;

			  if (listeners.fn) {
			    if (listeners.once) this.removeListener(event, listeners.fn, undefined, true);

			    switch (len) {
			      case 1: return listeners.fn.call(listeners.context), true;
			      case 2: return listeners.fn.call(listeners.context, a1), true;
			      case 3: return listeners.fn.call(listeners.context, a1, a2), true;
			      case 4: return listeners.fn.call(listeners.context, a1, a2, a3), true;
			      case 5: return listeners.fn.call(listeners.context, a1, a2, a3, a4), true;
			      case 6: return listeners.fn.call(listeners.context, a1, a2, a3, a4, a5), true;
			    }

			    for (i = 1, args = new Array(len -1); i < len; i++) {
			      args[i - 1] = arguments[i];
			    }

			    listeners.fn.apply(listeners.context, args);
			  } else {
			    var length = listeners.length
			      , j;

			    for (i = 0; i < length; i++) {
			      if (listeners[i].once) this.removeListener(event, listeners[i].fn, undefined, true);

			      switch (len) {
			        case 1: listeners[i].fn.call(listeners[i].context); break;
			        case 2: listeners[i].fn.call(listeners[i].context, a1); break;
			        case 3: listeners[i].fn.call(listeners[i].context, a1, a2); break;
			        case 4: listeners[i].fn.call(listeners[i].context, a1, a2, a3); break;
			        default:
			          if (!args) for (j = 1, args = new Array(len -1); j < len; j++) {
			            args[j - 1] = arguments[j];
			          }

			          listeners[i].fn.apply(listeners[i].context, args);
			      }
			    }
			  }

			  return true;
			};

			/**
			 * Add a listener for a given event.
			 *
			 * @param {(String|Symbol)} event The event name.
			 * @param {Function} fn The listener function.
			 * @param {*} [context=this] The context to invoke the listener with.
			 * @returns {EventEmitter} `this`.
			 * @public
			 */
			EventEmitter.prototype.on = function on(event, fn, context) {
			  return addListener(this, event, fn, context, false);
			};

			/**
			 * Add a one-time listener for a given event.
			 *
			 * @param {(String|Symbol)} event The event name.
			 * @param {Function} fn The listener function.
			 * @param {*} [context=this] The context to invoke the listener with.
			 * @returns {EventEmitter} `this`.
			 * @public
			 */
			EventEmitter.prototype.once = function once(event, fn, context) {
			  return addListener(this, event, fn, context, true);
			};

			/**
			 * Remove the listeners of a given event.
			 *
			 * @param {(String|Symbol)} event The event name.
			 * @param {Function} fn Only remove the listeners that match this function.
			 * @param {*} context Only remove the listeners that have this context.
			 * @param {Boolean} once Only remove one-time listeners.
			 * @returns {EventEmitter} `this`.
			 * @public
			 */
			EventEmitter.prototype.removeListener = function removeListener(event, fn, context, once) {
			  var evt = prefix ? prefix + event : event;

			  if (!this._events[evt]) return this;
			  if (!fn) {
			    clearEvent(this, evt);
			    return this;
			  }

			  var listeners = this._events[evt];

			  if (listeners.fn) {
			    if (
			      listeners.fn === fn &&
			      (!once || listeners.once) &&
			      (!context || listeners.context === context)
			    ) {
			      clearEvent(this, evt);
			    }
			  } else {
			    for (var i = 0, events = [], length = listeners.length; i < length; i++) {
			      if (
			        listeners[i].fn !== fn ||
			        (once && !listeners[i].once) ||
			        (context && listeners[i].context !== context)
			      ) {
			        events.push(listeners[i]);
			      }
			    }

			    //
			    // Reset the array, or remove it completely if we have no more listeners.
			    //
			    if (events.length) this._events[evt] = events.length === 1 ? events[0] : events;
			    else clearEvent(this, evt);
			  }

			  return this;
			};

			/**
			 * Remove all listeners, or those of the specified event.
			 *
			 * @param {(String|Symbol)} [event] The event name.
			 * @returns {EventEmitter} `this`.
			 * @public
			 */
			EventEmitter.prototype.removeAllListeners = function removeAllListeners(event) {
			  var evt;

			  if (event) {
			    evt = prefix ? prefix + event : event;
			    if (this._events[evt]) clearEvent(this, evt);
			  } else {
			    this._events = new Events();
			    this._eventsCount = 0;
			  }

			  return this;
			};

			//
			// Alias methods names because people roll like that.
			//
			EventEmitter.prototype.off = EventEmitter.prototype.removeListener;
			EventEmitter.prototype.addListener = EventEmitter.prototype.on;

			//
			// Expose the prefix.
			//
			EventEmitter.prefixed = prefix;

			//
			// Allow `EventEmitter` to be imported as module namespace.
			//
			EventEmitter.EventEmitter = EventEmitter;

			//
			// Expose the module.
			//
			{
			  module.exports = EventEmitter;
			} 
		} (eventemitter3));
		return eventemitter3.exports;
	}

	var Strategy = {};

	var SearchResult = {};

	var hasRequiredSearchResult;

	function requireSearchResult () {
		if (hasRequiredSearchResult) return SearchResult;
		hasRequiredSearchResult = 1;
		Object.defineProperty(SearchResult, "__esModule", { value: true });
		SearchResult.SearchResult = void 0;
		const MAIN = /\$&/g;
		const PLACE = /\$(\d)/g;
		let SearchResult$1 = class SearchResult {
		    constructor(data, term, strategy) {
		        this.data = data;
		        this.term = term;
		        this.strategy = strategy;
		    }
		    getReplacementData(beforeCursor) {
		        let result = this.strategy.replace(this.data);
		        if (result == null)
		            return null;
		        let afterCursor = "";
		        if (Array.isArray(result)) {
		            afterCursor = result[1];
		            result = result[0];
		        }
		        const match = this.strategy.match(beforeCursor);
		        if (match == null || match.index == null)
		            return null;
		        const replacement = result
		            .replace(MAIN, match[0])
		            .replace(PLACE, (_, p) => match[parseInt(p)]);
		        return {
		            start: match.index,
		            end: match.index + match[0].length,
		            beforeCursor: replacement,
		            afterCursor: afterCursor,
		        };
		    }
		    replace(beforeCursor, afterCursor) {
		        const replacement = this.getReplacementData(beforeCursor);
		        if (replacement === null)
		            return;
		        afterCursor = replacement.afterCursor + afterCursor;
		        return [
		            [
		                beforeCursor.slice(0, replacement.start),
		                replacement.beforeCursor,
		                beforeCursor.slice(replacement.end),
		            ].join(""),
		            afterCursor,
		        ];
		    }
		    render() {
		        return this.strategy.renderTemplate(this.data, this.term);
		    }
		    getStrategyId() {
		        return this.strategy.getId();
		    }
		};
		SearchResult.SearchResult = SearchResult$1;
		
		return SearchResult;
	}

	var hasRequiredStrategy;

	function requireStrategy () {
		if (hasRequiredStrategy) return Strategy;
		hasRequiredStrategy = 1;
		(function (exports) {
			Object.defineProperty(exports, "__esModule", { value: true });
			exports.Strategy = exports.DEFAULT_INDEX = void 0;
			const SearchResult_1 = requireSearchResult();
			exports.DEFAULT_INDEX = 1;
			class Strategy {
			    constructor(props) {
			        this.props = props;
			        this.cache = {};
			    }
			    destroy() {
			        this.cache = {};
			        return this;
			    }
			    replace(data) {
			        return this.props.replace(data);
			    }
			    execute(beforeCursor, callback) {
			        var _a;
			        const match = this.matchWithContext(beforeCursor);
			        if (!match)
			            return false;
			        const term = match[(_a = this.props.index) !== null && _a !== void 0 ? _a : exports.DEFAULT_INDEX];
			        this.search(term, (results) => {
			            callback(results.map((result) => new SearchResult_1.SearchResult(result, term, this)));
			        }, match);
			        return true;
			    }
			    renderTemplate(data, term) {
			        if (this.props.template) {
			            return this.props.template(data, term);
			        }
			        if (typeof data === "string")
			            return data;
			        throw new Error(`Unexpected render data type: ${typeof data}. Please implement template parameter by yourself`);
			    }
			    getId() {
			        return this.props.id || null;
			    }
			    match(text) {
			        return typeof this.props.match === "function"
			            ? this.props.match(text)
			            : text.match(this.props.match);
			    }
			    search(term, callback, match) {
			        if (this.props.cache) {
			            this.searchWithCach(term, callback, match);
			        }
			        else {
			            this.props.search(term, callback, match);
			        }
			    }
			    matchWithContext(beforeCursor) {
			        const context = this.context(beforeCursor);
			        if (context === false)
			            return null;
			        return this.match(context === true ? beforeCursor : context);
			    }
			    context(beforeCursor) {
			        return this.props.context ? this.props.context(beforeCursor) : true;
			    }
			    searchWithCach(term, callback, match) {
			        if (this.cache[term] != null) {
			            callback(this.cache[term]);
			        }
			        else {
			            this.props.search(term, (results) => {
			                this.cache[term] = results;
			                callback(results);
			            }, match);
			        }
			    }
			}
			exports.Strategy = Strategy;
			
		} (Strategy));
		return Strategy;
	}

	var hasRequiredCompleter;

	function requireCompleter () {
		if (hasRequiredCompleter) return Completer;
		hasRequiredCompleter = 1;
		Object.defineProperty(Completer, "__esModule", { value: true });
		Completer.Completer = void 0;
		const eventemitter3_1 = requireEventemitter3();
		const Strategy_1 = requireStrategy();
		let Completer$1 = class Completer extends eventemitter3_1.EventEmitter {
		    constructor(strategyPropsList) {
		        super();
		        this.handleQueryResult = (searchResults) => {
		            this.emit("hit", { searchResults });
		        };
		        this.strategies = strategyPropsList.map((p) => new Strategy_1.Strategy(p));
		    }
		    destroy() {
		        this.strategies.forEach((s) => s.destroy());
		        return this;
		    }
		    run(beforeCursor) {
		        for (const strategy of this.strategies) {
		            const executed = strategy.execute(beforeCursor, this.handleQueryResult);
		            if (executed)
		                return;
		        }
		        this.handleQueryResult([]);
		    }
		};
		Completer.Completer = Completer$1;
		
		return Completer;
	}

	var Dropdown = {};

	var utils = {};

	var hasRequiredUtils;

	function requireUtils () {
		if (hasRequiredUtils) return utils;
		hasRequiredUtils = 1;
		Object.defineProperty(utils, "__esModule", { value: true });
		utils.createCustomEvent = void 0;
		const isCustomEventSupported = typeof window !== "undefined" && !!window.CustomEvent;
		const createCustomEvent = (type, options) => {
		    if (isCustomEventSupported)
		        return new CustomEvent(type, options);
		    const event = document.createEvent("CustomEvent");
		    event.initCustomEvent(type, 
		    /* bubbles */ false, (options === null || options === void 0 ? void 0 : options.cancelable) || false, (options === null || options === void 0 ? void 0 : options.detail) || undefined);
		    return event;
		};
		utils.createCustomEvent = createCustomEvent;
		
		return utils;
	}

	var hasRequiredDropdown;

	function requireDropdown () {
		if (hasRequiredDropdown) return Dropdown;
		hasRequiredDropdown = 1;
		(function (exports) {
			Object.defineProperty(exports, "__esModule", { value: true });
			exports.Dropdown = exports.DEFAULT_DROPDOWN_ITEM_ACTIVE_CLASS_NAME = exports.DEFAULT_DROPDOWN_ITEM_CLASS_NAME = exports.DEFAULT_DROPDOWN_CLASS_NAME = exports.DEFAULT_DROPDOWN_PLACEMENT = exports.DEFAULT_DROPDOWN_MAX_COUNT = void 0;
			const eventemitter3_1 = requireEventemitter3();
			const utils_1 = requireUtils();
			// Default constants for Dropdown
			exports.DEFAULT_DROPDOWN_MAX_COUNT = 10;
			exports.DEFAULT_DROPDOWN_PLACEMENT = "auto";
			exports.DEFAULT_DROPDOWN_CLASS_NAME = "dropdown-menu textcomplete-dropdown";
			// Default constants for DropdownItem
			exports.DEFAULT_DROPDOWN_ITEM_CLASS_NAME = "textcomplete-item";
			exports.DEFAULT_DROPDOWN_ITEM_ACTIVE_CLASS_NAME = `${exports.DEFAULT_DROPDOWN_ITEM_CLASS_NAME} active`;
			class Dropdown extends eventemitter3_1.EventEmitter {
			    static create(option) {
			        const ul = document.createElement("ul");
			        ul.className = option.className || exports.DEFAULT_DROPDOWN_CLASS_NAME;
			        Object.assign(ul.style, {
			            display: "none",
			            position: "absolute",
			            zIndex: "1000",
			        }, option.style);
			        const parent = option.parent || document.body;
			        parent === null || parent === void 0 ? void 0 : parent.appendChild(ul);
			        return new Dropdown(ul, option);
			    }
			    constructor(el, option) {
			        super();
			        this.el = el;
			        this.option = option;
			        this.shown = false;
			        this.items = [];
			        this.activeIndex = null;
			    }
			    /**
			     * Render the given search results. Previous results are cleared.
			     *
			     * @emits render
			     * @emits rendered
			     */
			    render(searchResults, cursorOffset) {
			        const event = (0, utils_1.createCustomEvent)("render", { cancelable: true });
			        this.emit("render", event);
			        if (event.defaultPrevented)
			            return this;
			        this.clear();
			        if (searchResults.length === 0)
			            return this.hide();
			        this.items = searchResults
			            .slice(0, this.option.maxCount || exports.DEFAULT_DROPDOWN_MAX_COUNT)
			            .map((r, index) => { var _a; return new DropdownItem(this, index, r, ((_a = this.option) === null || _a === void 0 ? void 0 : _a.item) || {}); });
			        this.setStrategyId(searchResults[0])
			            .renderEdge(searchResults, "header")
			            .renderItems()
			            .renderEdge(searchResults, "footer")
			            .show()
			            .setOffset(cursorOffset)
			            .activate(0);
			        this.emit("rendered", (0, utils_1.createCustomEvent)("rendered"));
			        return this;
			    }
			    destroy() {
			        var _a;
			        this.clear();
			        (_a = this.el.parentNode) === null || _a === void 0 ? void 0 : _a.removeChild(this.el);
			        return this;
			    }
			    /**
			     * Select the given item
			     *
			     * @emits select
			     * @emits selected
			     */
			    select(item) {
			        const detail = { searchResult: item.searchResult };
			        const event = (0, utils_1.createCustomEvent)("select", { cancelable: true, detail });
			        this.emit("select", event);
			        if (event.defaultPrevented)
			            return this;
			        this.hide();
			        this.emit("selected", (0, utils_1.createCustomEvent)("selected", { detail }));
			        return this;
			    }
			    /**
			     * Show the dropdown element
			     *
			     * @emits show
			     * @emits shown
			     */
			    show() {
			        if (!this.shown) {
			            const event = (0, utils_1.createCustomEvent)("show", { cancelable: true });
			            this.emit("show", event);
			            if (event.defaultPrevented)
			                return this;
			            this.el.style.display = "block";
			            this.shown = true;
			            this.emit("shown", (0, utils_1.createCustomEvent)("shown"));
			        }
			        return this;
			    }
			    /**
			     * Hide the dropdown element
			     *
			     * @emits hide
			     * @emits hidden
			     */
			    hide() {
			        if (this.shown) {
			            const event = (0, utils_1.createCustomEvent)("hide", { cancelable: true });
			            this.emit("hide", event);
			            if (event.defaultPrevented)
			                return this;
			            this.el.style.display = "none";
			            this.shown = false;
			            this.clear();
			            this.emit("hidden", (0, utils_1.createCustomEvent)("hidden"));
			        }
			        return this;
			    }
			    /** Clear search results */
			    clear() {
			        this.items.forEach((i) => i.destroy());
			        this.items = [];
			        this.el.innerHTML = "";
			        this.activeIndex = null;
			        return this;
			    }
			    up(e) {
			        return this.shown ? this.moveActiveItem("prev", e) : this;
			    }
			    down(e) {
			        return this.shown ? this.moveActiveItem("next", e) : this;
			    }
			    moveActiveItem(direction, e) {
			        if (this.activeIndex != null) {
			            const activeIndex = direction === "next"
			                ? this.getNextActiveIndex()
			                : this.getPrevActiveIndex();
			            if (activeIndex != null) {
			                this.activate(activeIndex);
			                e.preventDefault();
			            }
			        }
			        return this;
			    }
			    activate(index) {
			        if (this.activeIndex !== index) {
			            if (this.activeIndex != null) {
			                this.items[this.activeIndex].deactivate();
			            }
			            this.activeIndex = index;
			            this.items[index].activate();
			        }
			        return this;
			    }
			    isShown() {
			        return this.shown;
			    }
			    getActiveItem() {
			        return this.activeIndex != null ? this.items[this.activeIndex] : null;
			    }
			    setOffset(cursorOffset) {
			        const doc = document.documentElement;
			        if (doc) {
			            const elementWidth = this.el.offsetWidth;
			            if (cursorOffset.left) {
			                const browserWidth = this.option.dynamicWidth
			                    ? doc.scrollWidth
			                    : doc.clientWidth;
			                if (cursorOffset.left + elementWidth > browserWidth) {
			                    cursorOffset.left = browserWidth - elementWidth;
			                }
			                this.el.style.left = `${cursorOffset.left}px`;
			            }
			            else if (cursorOffset.right) {
			                if (cursorOffset.right - elementWidth < 0) {
			                    cursorOffset.right = 0;
			                }
			                this.el.style.right = `${cursorOffset.right}px`;
			            }
			            let forceTop = false;
			            const placement = this.option.placement || exports.DEFAULT_DROPDOWN_PLACEMENT;
			            if (placement === "auto") {
			                const dropdownHeight = this.items.length * cursorOffset.lineHeight;
			                forceTop =
			                    cursorOffset.clientTop != null &&
			                        cursorOffset.clientTop + dropdownHeight > doc.clientHeight;
			            }
			            if (placement === "top" || forceTop) {
			                this.el.style.bottom = `${doc.clientHeight - cursorOffset.top + cursorOffset.lineHeight}px`;
			                this.el.style.top = "auto";
			            }
			            else {
			                this.el.style.top = `${cursorOffset.top}px`;
			                this.el.style.bottom = "auto";
			            }
			        }
			        return this;
			    }
			    getNextActiveIndex() {
			        if (this.activeIndex == null)
			            throw new Error();
			        return this.activeIndex < this.items.length - 1
			            ? this.activeIndex + 1
			            : this.option.rotate
			                ? 0
			                : null;
			    }
			    getPrevActiveIndex() {
			        if (this.activeIndex == null)
			            throw new Error();
			        return this.activeIndex !== 0
			            ? this.activeIndex - 1
			            : this.option.rotate
			                ? this.items.length - 1
			                : null;
			    }
			    renderItems() {
			        const fragment = document.createDocumentFragment();
			        for (const item of this.items) {
			            fragment.appendChild(item.el);
			        }
			        this.el.appendChild(fragment);
			        return this;
			    }
			    setStrategyId(searchResult) {
			        const id = searchResult.getStrategyId();
			        if (id)
			            this.el.dataset.strategy = id;
			        return this;
			    }
			    renderEdge(searchResults, type) {
			        const option = this.option[type];
			        const li = document.createElement("li");
			        li.className = `textcomplete-${type}`;
			        li.innerHTML =
			            typeof option === "function"
			                ? option(searchResults.map((s) => s.data))
			                : option || "";
			        this.el.appendChild(li);
			        return this;
			    }
			}
			exports.Dropdown = Dropdown;
			class DropdownItem {
			    constructor(dropdown, index, searchResult, props) {
			        this.dropdown = dropdown;
			        this.index = index;
			        this.searchResult = searchResult;
			        this.props = props;
			        this.active = false;
			        this.onClick = (e) => {
			            e.preventDefault();
			            this.dropdown.select(this);
			        };
			        this.className = this.props.className || exports.DEFAULT_DROPDOWN_ITEM_CLASS_NAME;
			        this.activeClassName =
			            this.props.activeClassName || exports.DEFAULT_DROPDOWN_ITEM_ACTIVE_CLASS_NAME;
			        const li = document.createElement("li");
			        li.className = this.active ? this.activeClassName : this.className;
			        const span = document.createElement("span");
			        span.tabIndex = -1;
			        span.innerHTML = this.searchResult.render();
			        li.appendChild(span);
			        li.addEventListener("click", this.onClick);
			        this.el = li;
			    }
			    destroy() {
			        var _a;
			        const li = this.el;
			        (_a = li.parentNode) === null || _a === void 0 ? void 0 : _a.removeChild(li);
			        li.removeEventListener("click", this.onClick, false);
			        return this;
			    }
			    activate() {
			        if (!this.active) {
			            this.active = true;
			            this.el.className = this.activeClassName;
			            this.dropdown.el.scrollTop = this.el.offsetTop;
			        }
			        return this;
			    }
			    deactivate() {
			        if (this.active) {
			            this.active = false;
			            this.el.className = this.className;
			        }
			        return this;
			    }
			}
			
		} (Dropdown));
		return Dropdown;
	}

	var Editor = {};

	var hasRequiredEditor;

	function requireEditor () {
		if (hasRequiredEditor) return Editor;
		hasRequiredEditor = 1;
		Object.defineProperty(Editor, "__esModule", { value: true });
		Editor.Editor = void 0;
		const eventemitter3_1 = requireEventemitter3();
		const utils_1 = requireUtils();
		let Editor$1 = class Editor extends eventemitter3_1.EventEmitter {
		    /**
		     * Finalize the editor object.
		     *
		     * It is called when associated textcomplete object is destroyed.
		     */
		    destroy() {
		        return this;
		    }
		    /**
		     * It is called when a search result is selected by a user.
		     */
		    applySearchResult(_searchResult) {
		        throw new Error("Not implemented.");
		    }
		    /**
		     * The input cursor's absolute coordinates from the window's left
		     * top corner.
		     */
		    getCursorOffset() {
		        throw new Error("Not implemented.");
		    }
		    /**
		     * Editor string value from head to the cursor.
		     * Returns null if selection type is range not cursor.
		     */
		    getBeforeCursor() {
		        throw new Error("Not implemented.");
		    }
		    /**
		     * Emit a move event, which moves active dropdown element.
		     * Child class must call this method at proper timing with proper parameter.
		     *
		     * @see {@link Textarea} for live example.
		     */
		    emitMoveEvent(code) {
		        const moveEvent = (0, utils_1.createCustomEvent)("move", {
		            cancelable: true,
		            detail: {
		                code: code,
		            },
		        });
		        this.emit("move", moveEvent);
		        return moveEvent;
		    }
		    /**
		     * Emit a enter event, which selects current search result.
		     * Child class must call this method at proper timing.
		     *
		     * @see {@link Textarea} for live example.
		     */
		    emitEnterEvent() {
		        const enterEvent = (0, utils_1.createCustomEvent)("enter", { cancelable: true });
		        this.emit("enter", enterEvent);
		        return enterEvent;
		    }
		    /**
		     * Emit a change event, which triggers auto completion.
		     * Child class must call this method at proper timing.
		     *
		     * @see {@link Textarea} for live example.
		     */
		    emitChangeEvent() {
		        const changeEvent = (0, utils_1.createCustomEvent)("change", {
		            detail: {
		                beforeCursor: this.getBeforeCursor(),
		            },
		        });
		        this.emit("change", changeEvent);
		        return changeEvent;
		    }
		    /**
		     * Emit a esc event, which hides dropdown element.
		     * Child class must call this method at proper timing.
		     *
		     * @see {@link Textarea} for live example.
		     */
		    emitEscEvent() {
		        const escEvent = (0, utils_1.createCustomEvent)("esc", { cancelable: true });
		        this.emit("esc", escEvent);
		        return escEvent;
		    }
		    /**
		     * Helper method for parsing KeyboardEvent.
		     *
		     * @see {@link Textarea} for live example.
		     */
		    getCode(e) {
		        switch (e.keyCode) {
		            case 9: // tab
		            case 13: // enter
		                return "ENTER";
		            case 27: // esc
		                return "ESC";
		            case 38: // up
		                return "UP";
		            case 40: // down
		                return "DOWN";
		            case 78: // ctrl-n
		                if (e.ctrlKey)
		                    return "DOWN";
		                break;
		            case 80: // ctrl-p
		                if (e.ctrlKey)
		                    return "UP";
		                break;
		        }
		        return "OTHER";
		    }
		};
		Editor.Editor = Editor$1;
		
		return Editor;
	}

	var Textcomplete = {};

	var hasRequiredTextcomplete;

	function requireTextcomplete () {
		if (hasRequiredTextcomplete) return Textcomplete;
		hasRequiredTextcomplete = 1;
		Object.defineProperty(Textcomplete, "__esModule", { value: true });
		Textcomplete.Textcomplete = void 0;
		const eventemitter3_1 = requireEventemitter3();
		const Dropdown_1 = requireDropdown();
		const Completer_1 = requireCompleter();
		const PASSTHOUGH_EVENT_NAMES = [
		    "show",
		    "shown",
		    "render",
		    "rendered",
		    "selected",
		    "hidden",
		    "hide",
		];
		let Textcomplete$1 = class Textcomplete extends eventemitter3_1.EventEmitter {
		    constructor(editor, strategies, option) {
		        super();
		        this.editor = editor;
		        this.isQueryInFlight = false;
		        this.nextPendingQuery = null;
		        this.handleHit = ({ searchResults, }) => {
		            if (searchResults.length) {
		                this.dropdown.render(searchResults, this.editor.getCursorOffset());
		            }
		            else {
		                this.dropdown.hide();
		            }
		            this.isQueryInFlight = false;
		            if (this.nextPendingQuery !== null)
		                this.trigger(this.nextPendingQuery);
		        };
		        this.handleMove = (e) => {
		            e.detail.code === "UP" ? this.dropdown.up(e) : this.dropdown.down(e);
		        };
		        this.handleEnter = (e) => {
		            const activeItem = this.dropdown.getActiveItem();
		            if (activeItem) {
		                this.dropdown.select(activeItem);
		                e.preventDefault();
		            }
		            else {
		                this.dropdown.hide();
		            }
		        };
		        this.handleEsc = (e) => {
		            if (this.dropdown.isShown()) {
		                this.dropdown.hide();
		                e.preventDefault();
		            }
		        };
		        this.handleChange = (e) => {
		            if (e.detail.beforeCursor != null) {
		                this.trigger(e.detail.beforeCursor);
		            }
		            else {
		                this.dropdown.hide();
		            }
		        };
		        this.handleSelect = (selectEvent) => {
		            this.emit("select", selectEvent);
		            if (!selectEvent.defaultPrevented) {
		                this.editor.applySearchResult(selectEvent.detail.searchResult);
		            }
		        };
		        this.handleResize = () => {
		            if (this.dropdown.isShown()) {
		                this.dropdown.setOffset(this.editor.getCursorOffset());
		            }
		        };
		        this.completer = new Completer_1.Completer(strategies);
		        this.dropdown = Dropdown_1.Dropdown.create((option === null || option === void 0 ? void 0 : option.dropdown) || {});
		        this.startListening();
		    }
		    destroy(destroyEditor = true) {
		        this.completer.destroy();
		        this.dropdown.destroy();
		        if (destroyEditor)
		            this.editor.destroy();
		        this.stopListening();
		        return this;
		    }
		    isShown() {
		        return this.dropdown.isShown();
		    }
		    hide() {
		        this.dropdown.hide();
		        return this;
		    }
		    trigger(beforeCursor) {
		        if (this.isQueryInFlight) {
		            this.nextPendingQuery = beforeCursor;
		        }
		        else {
		            this.isQueryInFlight = true;
		            this.nextPendingQuery = null;
		            this.completer.run(beforeCursor);
		        }
		        return this;
		    }
		    startListening() {
		        var _a;
		        this.editor
		            .on("move", this.handleMove)
		            .on("enter", this.handleEnter)
		            .on("esc", this.handleEsc)
		            .on("change", this.handleChange);
		        this.dropdown.on("select", this.handleSelect);
		        for (const eventName of PASSTHOUGH_EVENT_NAMES) {
		            this.dropdown.on(eventName, (e) => this.emit(eventName, e));
		        }
		        this.completer.on("hit", this.handleHit);
		        (_a = this.dropdown.el.ownerDocument.defaultView) === null || _a === void 0 ? void 0 : _a.addEventListener("resize", this.handleResize);
		    }
		    stopListening() {
		        var _a;
		        (_a = this.dropdown.el.ownerDocument.defaultView) === null || _a === void 0 ? void 0 : _a.removeEventListener("resize", this.handleResize);
		        this.completer.removeAllListeners();
		        this.dropdown.removeAllListeners();
		        this.editor
		            .removeListener("move", this.handleMove)
		            .removeListener("enter", this.handleEnter)
		            .removeListener("esc", this.handleEsc)
		            .removeListener("change", this.handleChange);
		    }
		};
		Textcomplete.Textcomplete = Textcomplete$1;
		
		return Textcomplete;
	}

	var hasRequiredDist;

	function requireDist () {
		if (hasRequiredDist) return dist;
		hasRequiredDist = 1;
		(function (exports) {
			var __createBinding = (dist && dist.__createBinding) || (Object.create ? (function(o, m, k, k2) {
			    if (k2 === undefined) k2 = k;
			    var desc = Object.getOwnPropertyDescriptor(m, k);
			    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
			      desc = { enumerable: true, get: function() { return m[k]; } };
			    }
			    Object.defineProperty(o, k2, desc);
			}) : (function(o, m, k, k2) {
			    if (k2 === undefined) k2 = k;
			    o[k2] = m[k];
			}));
			var __exportStar = (dist && dist.__exportStar) || function(m, exports) {
			    for (var p in m) if (p !== "default" && !Object.prototype.hasOwnProperty.call(exports, p)) __createBinding(exports, m, p);
			};
			Object.defineProperty(exports, "__esModule", { value: true });
			__exportStar(requireCompleter(), exports);
			__exportStar(requireDropdown(), exports);
			__exportStar(requireEditor(), exports);
			__exportStar(requireSearchResult(), exports);
			__exportStar(requireStrategy(), exports);
			__exportStar(requireTextcomplete(), exports);
			__exportStar(requireUtils(), exports);
			
		} (dist));
		return dist;
	}

	var distExports = requireDist();
	var index = /*@__PURE__*/getDefaultExportFromCjs(distExports);

	return index;

}));

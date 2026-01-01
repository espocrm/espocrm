define('@textcomplete/textarea', ['@textcomplete/core'], (function (require$$2) { 'use strict';

	function getDefaultExportFromCjs (x) {
		return x && x.__esModule && Object.prototype.hasOwnProperty.call(x, 'default') ? x['default'] : x;
	}

	function getAugmentedNamespace(n) {
	  if (Object.prototype.hasOwnProperty.call(n, '__esModule')) return n;
	  var f = n.default;
		if (typeof f == "function") {
			var a = function a () {
				var isInstance = false;
	      try {
	        isInstance = this instanceof a;
	      } catch {}
				if (isInstance) {
	        return Reflect.construct(f, arguments, this.constructor);
				}
				return f.apply(this, arguments);
			};
			a.prototype = f.prototype;
	  } else a = {};
	  Object.defineProperty(a, '__esModule', {value: true});
		Object.keys(n).forEach(function (k) {
			var d = Object.getOwnPropertyDescriptor(n, k);
			Object.defineProperty(a, k, d.get ? d : {
				enumerable: true,
				get: function () {
					return n[k];
				}
			});
		});
		return a;
	}

	var dist$2 = {};

	var TextareaEditor = {};

	function update(el, headToCursor, cursorToTail) {
	    const curr = el.value; // strA + strB1 + strC
	    const next = headToCursor + (cursorToTail || ""); // strA + strB2 + strC
	    const activeElement = document.activeElement;
	    //  Calculate length of strA and strC
	    let aLength = 0;
	    let cLength = 0;
	    while (aLength < curr.length && aLength < next.length && curr[aLength] === next[aLength]) {
	        aLength++;
	    }
	    while (curr.length - cLength - 1 >= 0 &&
	        next.length - cLength - 1 >= 0 &&
	        curr[curr.length - cLength - 1] === next[next.length - cLength - 1]) {
	        cLength++;
	    }
	    aLength = Math.min(aLength, Math.min(curr.length, next.length) - cLength);
	    // Select strB1
	    el.setSelectionRange(aLength, curr.length - cLength);
	    // Get strB2
	    const strB2 = next.substring(aLength, next.length - cLength);
	    // Replace strB1 with strB2
	    el.focus();
	    if (!document.execCommand("insertText", false, strB2)) {
	        // Document.execCommand returns false if the command is not supported.
	        // Firefox and IE returns false in this case.
	        el.value = next;
	        // Dispatch input event. Note that `new Event("input")` throws an error on IE11
	        const event = document.createEvent("Event");
	        event.initEvent("input", true, true);
	        el.dispatchEvent(event);
	    }
	    // Move cursor to the end of headToCursor
	    el.setSelectionRange(headToCursor.length, headToCursor.length);
	    activeElement.focus();
	    return el;
	}

	function wrapCursor(el, before, after) {
	    const initEnd = el.selectionEnd;
	    const headToCursor = el.value.substr(0, el.selectionStart) + before;
	    const cursorToTail = el.value.substring(el.selectionStart, initEnd) + (after || "") + el.value.substr(initEnd);
	    update(el, headToCursor, cursorToTail);
	    el.selectionEnd = initEnd + before.length;
	    return el;
	}

	var dist$1 = /*#__PURE__*/Object.freeze({
		__proto__: null,
		update: update,
		wrapCursor: wrapCursor
	});

	var require$$0 = /*@__PURE__*/getAugmentedNamespace(dist$1);

	var textareaCaret = {exports: {}};

	/* jshint browser: true */

	var hasRequiredTextareaCaret;

	function requireTextareaCaret () {
		if (hasRequiredTextareaCaret) return textareaCaret.exports;
		hasRequiredTextareaCaret = 1;
		(function (module) {
			(function () {

			// We'll copy the properties below into the mirror div.
			// Note that some browsers, such as Firefox, do not concatenate properties
			// into their shorthand (e.g. padding-top, padding-bottom etc. -> padding),
			// so we have to list every single property explicitly.
			var properties = [
			  'direction',  // RTL support
			  'boxSizing',
			  'width',  // on Chrome and IE, exclude the scrollbar, so the mirror div wraps exactly as the textarea does
			  'height',
			  'overflowX',
			  'overflowY',  // copy the scrollbar for IE

			  'borderTopWidth',
			  'borderRightWidth',
			  'borderBottomWidth',
			  'borderLeftWidth',
			  'borderStyle',

			  'paddingTop',
			  'paddingRight',
			  'paddingBottom',
			  'paddingLeft',

			  // https://developer.mozilla.org/en-US/docs/Web/CSS/font
			  'fontStyle',
			  'fontVariant',
			  'fontWeight',
			  'fontStretch',
			  'fontSize',
			  'fontSizeAdjust',
			  'lineHeight',
			  'fontFamily',

			  'textAlign',
			  'textTransform',
			  'textIndent',
			  'textDecoration',  // might not make a difference, but better be safe

			  'letterSpacing',
			  'wordSpacing',

			  'tabSize',
			  'MozTabSize'

			];

			var isBrowser = (typeof window !== 'undefined');
			var isFirefox = (isBrowser && window.mozInnerScreenX != null);

			function getCaretCoordinates(element, position, options) {
			  if (!isBrowser) {
			    throw new Error('textarea-caret-position#getCaretCoordinates should only be called in a browser');
			  }

			  var debug = options && options.debug || false;
			  if (debug) {
			    var el = document.querySelector('#input-textarea-caret-position-mirror-div');
			    if (el) el.parentNode.removeChild(el);
			  }

			  // The mirror div will replicate the textarea's style
			  var div = document.createElement('div');
			  div.id = 'input-textarea-caret-position-mirror-div';
			  document.body.appendChild(div);

			  var style = div.style;
			  var computed = window.getComputedStyle ? window.getComputedStyle(element) : element.currentStyle;  // currentStyle for IE < 9
			  var isInput = element.nodeName === 'INPUT';

			  // Default textarea styles
			  style.whiteSpace = 'pre-wrap';
			  if (!isInput)
			    style.wordWrap = 'break-word';  // only for textarea-s

			  // Position off-screen
			  style.position = 'absolute';  // required to return coordinates properly
			  if (!debug)
			    style.visibility = 'hidden';  // not 'display: none' because we want rendering

			  // Transfer the element's properties to the div
			  properties.forEach(function (prop) {
			    if (isInput && prop === 'lineHeight') {
			      // Special case for <input>s because text is rendered centered and line height may be != height
			      style.lineHeight = computed.height;
			    } else {
			      style[prop] = computed[prop];
			    }
			  });

			  if (isFirefox) {
			    // Firefox lies about the overflow property for textareas: https://bugzilla.mozilla.org/show_bug.cgi?id=984275
			    if (element.scrollHeight > parseInt(computed.height))
			      style.overflowY = 'scroll';
			  } else {
			    style.overflow = 'hidden';  // for Chrome to not render a scrollbar; IE keeps overflowY = 'scroll'
			  }

			  div.textContent = element.value.substring(0, position);
			  // The second special handling for input type="text" vs textarea:
			  // spaces need to be replaced with non-breaking spaces - http://stackoverflow.com/a/13402035/1269037
			  if (isInput)
			    div.textContent = div.textContent.replace(/\s/g, '\u00a0');

			  var span = document.createElement('span');
			  // Wrapping must be replicated *exactly*, including when a long word gets
			  // onto the next line, with whitespace at the end of the line before (#7).
			  // The  *only* reliable way to do that is to copy the *entire* rest of the
			  // textarea's content into the <span> created at the caret position.
			  // For inputs, just '.' would be enough, but no need to bother.
			  span.textContent = element.value.substring(position) || '.';  // || because a completely empty faux span doesn't render at all
			  div.appendChild(span);

			  var coordinates = {
			    top: span.offsetTop + parseInt(computed['borderTopWidth']),
			    left: span.offsetLeft + parseInt(computed['borderLeftWidth']),
			    height: parseInt(computed['lineHeight'])
			  };

			  if (debug) {
			    span.style.backgroundColor = '#aaa';
			  } else {
			    document.body.removeChild(div);
			  }

			  return coordinates;
			}

			{
			  module.exports = getCaretCoordinates;
			}

			}()); 
		} (textareaCaret));
		return textareaCaret.exports;
	}

	var dist = {};

	var calculateElementOffset = {};

	var hasRequiredCalculateElementOffset;

	function requireCalculateElementOffset () {
		if (hasRequiredCalculateElementOffset) return calculateElementOffset;
		hasRequiredCalculateElementOffset = 1;
		Object.defineProperty(calculateElementOffset, "__esModule", { value: true });
		calculateElementOffset.calculateElementOffset = void 0;
		/**
		 * Get the current coordinates of the `el` relative to the document.
		 */
		const calculateElementOffset$1 = (el) => {
		    const rect = el.getBoundingClientRect();
		    const owner = el.ownerDocument;
		    if (owner == null) {
		        throw new Error("Given element does not belong to document");
		    }
		    const { defaultView, documentElement } = owner;
		    if (defaultView == null) {
		        throw new Error("Given element does not belong to window");
		    }
		    const offset = {
		        top: rect.top + defaultView.pageYOffset,
		        left: rect.left + defaultView.pageXOffset,
		    };
		    if (documentElement) {
		        offset.top -= documentElement.clientTop;
		        offset.left -= documentElement.clientLeft;
		    }
		    return offset;
		};
		calculateElementOffset.calculateElementOffset = calculateElementOffset$1;
		
		return calculateElementOffset;
	}

	var getLineHeightPx = {};

	var hasRequiredGetLineHeightPx;

	function requireGetLineHeightPx () {
		if (hasRequiredGetLineHeightPx) return getLineHeightPx;
		hasRequiredGetLineHeightPx = 1;
		Object.defineProperty(getLineHeightPx, "__esModule", { value: true });
		getLineHeightPx.getLineHeightPx = void 0;
		const CHAR_CODE_ZERO = "0".charCodeAt(0);
		const CHAR_CODE_NINE = "9".charCodeAt(0);
		const isDigit = (charCode) => CHAR_CODE_ZERO <= charCode && charCode <= CHAR_CODE_NINE;
		const getLineHeightPx$1 = (el) => {
		    const computedStyle = getComputedStyle(el);
		    const lineHeight = computedStyle.lineHeight;
		    // If the char code starts with a digit, it is either a value in pixels,
		    // or unitless, as per:
		    // https://drafts.csswg.org/css2/visudet.html#propdef-line-height
		    // https://drafts.csswg.org/css2/cascade.html#computed-value
		    if (isDigit(lineHeight.charCodeAt(0))) {
		        const floatLineHeight = parseFloat(lineHeight);
		        // In real browsers the value is *always* in pixels, even for unit-less
		        // line-heights. However, we still check as per the spec.
		        return isDigit(lineHeight.charCodeAt(lineHeight.length - 1))
		            ? floatLineHeight * parseFloat(computedStyle.fontSize)
		            : floatLineHeight;
		    }
		    // Otherwise, the value is "normal".
		    // If the line-height is "normal", calculate by font-size
		    return calculateLineHeightPx(el.nodeName, computedStyle);
		};
		getLineHeightPx.getLineHeightPx = getLineHeightPx$1;
		/**
		 * Returns calculated line-height of the given node in pixels.
		 */
		const calculateLineHeightPx = (nodeName, computedStyle) => {
		    const body = document.body;
		    if (!body)
		        return 0;
		    const tempNode = document.createElement(nodeName);
		    tempNode.innerHTML = "&nbsp;";
		    Object.assign(tempNode.style, {
		        fontSize: computedStyle.fontSize,
		        fontFamily: computedStyle.fontFamily,
		        padding: "0",
		    });
		    body.appendChild(tempNode);
		    // Make sure textarea has only 1 row
		    if (tempNode instanceof HTMLTextAreaElement) {
		        tempNode.rows = 1;
		    }
		    // Assume the height of the element is the line-height
		    const height = tempNode.offsetHeight;
		    body.removeChild(tempNode);
		    return height;
		};
		
		return getLineHeightPx;
	}

	var isSafari = {};

	var hasRequiredIsSafari;

	function requireIsSafari () {
		if (hasRequiredIsSafari) return isSafari;
		hasRequiredIsSafari = 1;
		Object.defineProperty(isSafari, "__esModule", { value: true });
		isSafari.isSafari = void 0;
		const isSafari$1 = () => /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
		isSafari.isSafari = isSafari$1;
		
		return isSafari;
	}

	var hasRequiredDist$1;

	function requireDist$1 () {
		if (hasRequiredDist$1) return dist;
		hasRequiredDist$1 = 1;
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
			__exportStar(requireCalculateElementOffset(), exports);
			__exportStar(requireGetLineHeightPx(), exports);
			__exportStar(requireIsSafari(), exports);
			
		} (dist));
		return dist;
	}

	var hasRequiredTextareaEditor;

	function requireTextareaEditor () {
		if (hasRequiredTextareaEditor) return TextareaEditor;
		hasRequiredTextareaEditor = 1;
		var __importDefault = (TextareaEditor && TextareaEditor.__importDefault) || function (mod) {
		    return (mod && mod.__esModule) ? mod : { "default": mod };
		};
		Object.defineProperty(TextareaEditor, "__esModule", { value: true });
		TextareaEditor.TextareaEditor = void 0;
		const undate_1 = require$$0;
		const textarea_caret_1 = __importDefault(requireTextareaCaret());
		const core_1 = require$$2;
		const utils_1 = requireDist$1();
		let TextareaEditor$1 = class TextareaEditor extends core_1.Editor {
		    constructor(el) {
		        super();
		        this.el = el;
		        this.onInput = () => {
		            this.emitChangeEvent();
		        };
		        this.onKeydown = (e) => {
		            const code = this.getCode(e);
		            let event;
		            if (code === "UP" || code === "DOWN") {
		                event = this.emitMoveEvent(code);
		            }
		            else if (code === "ENTER") {
		                event = this.emitEnterEvent();
		            }
		            else if (code === "ESC") {
		                event = this.emitEscEvent();
		            }
		            if (event && event.defaultPrevented) {
		                e.preventDefault();
		            }
		        };
		        this.startListening();
		    }
		    destroy() {
		        super.destroy();
		        this.stopListening();
		        return this;
		    }
		    /**
		     * @implements {@link Editor#applySearchResult}
		     */
		    applySearchResult(searchResult) {
		        const beforeCursor = this.getBeforeCursor();
		        if (beforeCursor != null) {
		            const replace = searchResult.replace(beforeCursor, this.getAfterCursor());
		            this.el.focus(); // Clicking a dropdown item removes focus from the element.
		            if (Array.isArray(replace)) {
		                (0, undate_1.update)(this.el, replace[0], replace[1]);
		                if (this.el) {
		                    this.el.dispatchEvent((0, core_1.createCustomEvent)("input"));
		                }
		            }
		        }
		    }
		    /**
		     * @implements {@link Editor#getCursorOffset}
		     */
		    getCursorOffset() {
		        const elOffset = (0, utils_1.calculateElementOffset)(this.el);
		        const elScroll = this.getElScroll();
		        const cursorPosition = this.getCursorPosition();
		        const lineHeight = (0, utils_1.getLineHeightPx)(this.el);
		        const top = elOffset.top - elScroll.top + cursorPosition.top + lineHeight;
		        const left = elOffset.left - elScroll.left + cursorPosition.left;
		        const clientTop = this.el.getBoundingClientRect().top;
		        if (this.el.dir !== "rtl") {
		            return { top, left, lineHeight, clientTop };
		        }
		        else {
		            const right = document.documentElement
		                ? document.documentElement.clientWidth - left
		                : 0;
		            return { top, right, lineHeight, clientTop };
		        }
		    }
		    /**
		     * @implements {@link Editor#getBeforeCursor}
		     */
		    getBeforeCursor() {
		        return this.el.selectionStart !== this.el.selectionEnd
		            ? null
		            : this.el.value.substring(0, this.el.selectionEnd);
		    }
		    getAfterCursor() {
		        return this.el.value.substring(this.el.selectionEnd);
		    }
		    getElScroll() {
		        return { top: this.el.scrollTop, left: this.el.scrollLeft };
		    }
		    /**
		     * The input cursor's relative coordinates from the textarea's left
		     * top corner.
		     */
		    getCursorPosition() {
		        return (0, textarea_caret_1.default)(this.el, this.el.selectionEnd);
		    }
		    startListening() {
		        this.el.addEventListener("input", this.onInput);
		        this.el.addEventListener("keydown", this.onKeydown);
		    }
		    stopListening() {
		        this.el.removeEventListener("input", this.onInput);
		        this.el.removeEventListener("keydown", this.onKeydown);
		    }
		};
		TextareaEditor.TextareaEditor = TextareaEditor$1;
		
		return TextareaEditor;
	}

	var hasRequiredDist;

	function requireDist () {
		if (hasRequiredDist) return dist$2;
		hasRequiredDist = 1;
		(function (exports) {
			Object.defineProperty(exports, "__esModule", { value: true });
			exports.TextareaEditor = void 0;
			var TextareaEditor_1 = requireTextareaEditor();
			Object.defineProperty(exports, "TextareaEditor", { enumerable: true, get: function () { return TextareaEditor_1.TextareaEditor; } });
			
		} (dist$2));
		return dist$2;
	}

	var distExports = requireDist();
	var index = /*@__PURE__*/getDefaultExportFromCjs(distExports);

	return index;

}));

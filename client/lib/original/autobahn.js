/** @license MIT License (c) 2011,2012 Copyright Tavendo GmbH. */

/**
 * AutobahnJS - http://autobahn.ws
 *
 * A lightweight implementation of
 *
 *   WAMP (The WebSocket Application Messaging Protocol) - http://wamp.ws
 *
 * Provides asynchronous RPC/PubSub over WebSocket.
 *
 * Copyright 2011, 2012 Tavendo GmbH. Licensed under the MIT License.
 * See license text at http://www.opensource.org/licenses/mit-license.php
 */

"use strict";

/** @define {string} */
var AUTOBAHNJS_VERSION = '?.?.?';

/** @define {boolean} */
var AUTOBAHNJS_DEBUG = true;



var ab = window.ab = {};

ab._version = AUTOBAHNJS_VERSION;

/**
 * Fallbacks for browsers lacking
 *
 *    Array.prototype.indexOf
 *    Array.prototype.forEach
 *
 * most notably MSIE8.
 *
 * Source:
 *    https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Array/indexOf
 *    https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Array/forEach
 */
(function () {
   if (!Array.prototype.indexOf) {
      Array.prototype.indexOf = function (searchElement /*, fromIndex */ ) {
         "use strict";
         if (this === null) {
            throw new TypeError();
         }
         var t = new Object(this);
         var len = t.length >>> 0;
         if (len === 0) {
            return -1;
         }
         var n = 0;
         if (arguments.length > 0) {
            n = Number(arguments[1]);
            if (n !== n) { // shortcut for verifying if it's NaN
               n = 0;
            } else if (n !== 0 && n !== Infinity && n !== -Infinity) {
               n = (n > 0 || -1) * Math.floor(Math.abs(n));
            }
         }
         if (n >= len) {
            return -1;
         }
         var k = n >= 0 ? n : Math.max(len - Math.abs(n), 0);
         for (; k < len; k++) {
            if (k in t && t[k] === searchElement) {
               return k;
            }
         }
         return -1;
      };
   }

   if (!Array.prototype.forEach) {

      Array.prototype.forEach = function (callback, thisArg) {

         var T, k;

         if (this === null) {
            throw new TypeError(" this is null or not defined");
         }

         // 1. Let O be the result of calling ToObject passing the |this| value as the argument.
         var O = new Object(this);

         // 2. Let lenValue be the result of calling the Get internal method of O with the argument "length".
         // 3. Let len be ToUint32(lenValue).
         var len = O.length >>> 0; // Hack to convert O.length to a UInt32

         // 4. If IsCallable(callback) is false, throw a TypeError exception.
         // See: http://es5.github.com/#x9.11
         if ({}.toString.call(callback) !== "[object Function]") {
            throw new TypeError(callback + " is not a function");
         }

         // 5. If thisArg was supplied, let T be thisArg; else let T be undefined.
         if (thisArg) {
            T = thisArg;
         }

         // 6. Let k be 0
         k = 0;

         // 7. Repeat, while k < len
         while (k < len) {

            var kValue;

            // a. Let Pk be ToString(k).
            //   This is implicit for LHS operands of the in operator
            // b. Let kPresent be the result of calling the HasProperty internal method of O with argument Pk.
            //   This step can be combined with c
            // c. If kPresent is true, then
            if (k in O) {

               // i. Let kValue be the result of calling the Get internal method of O with argument Pk.
               kValue = O[k];

               // ii. Call the Call internal method of callback with T as the this value and
               // argument list containing kValue, k, and O.
               callback.call(T, kValue, k, O);
            }
            // d. Increase k by 1.
            k++;
         }
         // 8. return undefined
      };
   }

})();


// Helper to slice out browser / version from userAgent
ab._sliceUserAgent = function (str, delim, delim2) {
   var ver = [];
   var ua = navigator.userAgent;
   var i = ua.indexOf(str);
   var j = ua.indexOf(delim, i);
   if (j < 0) {
      j = ua.length;
   }
   var agent = ua.slice(i, j).split(delim2);
   var v = agent[1].split('.');
   for (var k = 0; k < v.length; ++k) {
      ver.push(parseInt(v[k], 10));
   }
   return {name: agent[0], version: ver};
};

/**
 * Detect browser and browser version.
 */
ab.getBrowser = function () {

   var ua = navigator.userAgent;
   if (ua.indexOf("Chrome") > -1) {
      return ab._sliceUserAgent("Chrome", " ", "/");
   } else if (ua.indexOf("Safari") > -1) {
      return ab._sliceUserAgent("Safari", " ", "/");
   } else if (ua.indexOf("Firefox") > -1) {
      return ab._sliceUserAgent("Firefox", " ", "/");
   } else if (ua.indexOf("MSIE") > -1) {
      return ab._sliceUserAgent("MSIE", ";", " ");
   } else {
      return null;
   }
};


// Logging message for unsupported browser.
ab.browserNotSupportedMessage = "Browser does not support WebSockets (RFC6455)";


ab._idchars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
ab._idlen = 16;
ab._subprotocol = "wamp";

ab._newid = function () {
   var id = "";
   for (var i = 0; i < ab._idlen; i += 1) {
      id += ab._idchars.charAt(Math.floor(Math.random() * ab._idchars.length));
   }
   return id;
};

ab.log = function (o) {
   if (window.console && console.log) {
      //console.log.apply(console, !!arguments.length ? arguments : [this]);
      if (arguments.length > 1) {
         console.group("Log Item");
         for (var i = 0; i < arguments.length; i += 1) {
            console.log(arguments[i]);
         }
         console.groupEnd();
      } else {
         console.log(arguments[0]);
      }
   }
};

ab._debugrpc = false;
ab._debugpubsub = false;
ab._debugws = false;

ab.debug = function (debugWamp, debugWs) {
   if ("console" in window) {
      ab._debugrpc = debugWamp;
      ab._debugpubsub = debugWamp;
      ab._debugws = debugWs;
   } else {
      throw "browser does not support console object";
   }
};

ab.version = function () {
   return ab._version;
};

ab.PrefixMap = function () {

   var self = this;
   self._index = {};
   self._rindex = {};
};

ab.PrefixMap.prototype.get = function (prefix) {

   var self = this;
   return self._index[prefix];
};

ab.PrefixMap.prototype.set = function (prefix, uri) {

   var self = this;
   self._index[prefix] = uri;
   self._rindex[uri] = prefix;
};

ab.PrefixMap.prototype.setDefault = function (uri) {

   var self = this;
   self._index[""] = uri;
   self._rindex[uri] = "";
};

ab.PrefixMap.prototype.remove = function (prefix) {

   var self = this;
   var uri = self._index[prefix];
   if (uri) {
      delete self._index[prefix];
      delete self._rindex[uri];
   }
};

ab.PrefixMap.prototype.resolve = function (curie, pass) {

   var self = this;

   // skip if not a CURIE
   var i = curie.indexOf(":");
   if (i >= 0) {
      var prefix = curie.substring(0, i);
      if (self._index[prefix]) {
         return self._index[prefix] + curie.substring(i + 1);
      }
   }

   // either pass-through or null
   if (pass == true) {
      return curie;
   } else {
      return null;
   }
};

ab.PrefixMap.prototype.shrink = function (uri, pass) {

   var self = this;

   // skip if already a CURIE
   var i = uri.indexOf(":");
   if (i == -1) {
      for (var i = uri.length; i > 0; i -= 1) {
         var u = uri.substring(0, i);
         var p = self._rindex[u];
         if (p) {
            return p + ":" + uri.substring(i);
         }
      }
   }

   // either pass-through or null
   if (pass == true) {
      return uri;
   } else {
      return null;
   }
};


ab._MESSAGE_TYPEID_WELCOME        = 0;
ab._MESSAGE_TYPEID_PREFIX         = 1;
ab._MESSAGE_TYPEID_CALL           = 2;
ab._MESSAGE_TYPEID_CALL_RESULT    = 3;
ab._MESSAGE_TYPEID_CALL_ERROR     = 4;
ab._MESSAGE_TYPEID_SUBSCRIBE      = 5;
ab._MESSAGE_TYPEID_UNSUBSCRIBE    = 6;
ab._MESSAGE_TYPEID_PUBLISH        = 7;
ab._MESSAGE_TYPEID_EVENT          = 8;


ab.CONNECTION_CLOSED = 0;
ab.CONNECTION_LOST = 1;
ab.CONNECTION_UNREACHABLE = 2;
ab.CONNECTION_UNSUPPORTED = 3;

ab.Session = function (wsuri, onopen, onclose, options) {

   var self = this;

   self._wsuri = wsuri;
   self._options = options;
   self._websocket_onopen = onopen;
   self._websocket_onclose = onclose;
   self._websocket = null;
   self._websocket_connected = false;
   self._session_id = null;
   self._calls = {};
   self._subscriptions = {};
   self._prefixes = new ab.PrefixMap();

   self._txcnt = 0;
   self._rxcnt = 0;

   if ("WebSocket" in window) {
      // Chrome, MSIE, newer Firefox
      self._websocket = new WebSocket(self._wsuri, [ab._subprotocol]);
   } else if ("MozWebSocket" in window) {
      // older versions of Firefox prefix the WebSocket object
      self._websocket = new MozWebSocket(self._wsuri, [ab._subprotocol]);
   } else {
      if (onclose !== undefined) {
         onclose(ab.CONNECTION_UNSUPPORTED);
         return;
      } else {
         throw ab.browserNotSupportedMessage;
      }
   }

   self._websocket.onmessage = function (e)
   {
      if (ab._debugws) {
         self._rxcnt += 1;
         console.group("WS Receive");
         console.info(self._wsuri + "  [" + self._session_id + "]");
         console.log(self._rxcnt);
         console.log(e.data);
         console.groupEnd();
      }

      var o = JSON.parse(e.data);
      if (o[1] in self._calls)
      {
         if (o[0] === ab._MESSAGE_TYPEID_CALL_RESULT) {

            var dr = self._calls[o[1]];
            var r = o[2];

            if (ab._debugrpc && dr._ab_callobj !== undefined) {
               console.group("WAMP Call", dr._ab_callobj[2]);
               console.timeEnd(dr._ab_tid);
               console.group("Arguments");
               for (var i = 3; i < dr._ab_callobj.length; i += 1) {
                  var arg = dr._ab_callobj[i];
                  if (arg !== undefined) {
                     console.log(arg);
                  } else {
                     break;
                  }
               }
               console.groupEnd();
               console.group("Result");
               console.log(r);
               console.groupEnd();
               console.groupEnd();
            }

            dr.resolve(r);
         }
         else if (o[0] === ab._MESSAGE_TYPEID_CALL_ERROR) {

            var de = self._calls[o[1]];
            var uri = o[2];
            var desc = o[3];
            var detail = o[4];

            if (ab._debugrpc && de._ab_callobj !== undefined) {
               console.group("WAMP Call", de._ab_callobj[2]);
               console.timeEnd(de._ab_tid);
               console.group("Arguments");
               for (var j = 3; j < de._ab_callobj.length; j += 1) {
                  var arg2 = de._ab_callobj[j];
                  if (arg2 !== undefined) {
                     console.log(arg2);
                  } else {
                     break;
                  }
               }
               console.groupEnd();
               console.group("Error");
               console.log(uri);
               console.log(desc);
               if (detail !== undefined) {
                  console.log(detail);
               }
               console.groupEnd();
               console.groupEnd();
            }

            if (detail !== undefined) {
               de.reject(uri, desc, detail);
            } else {
               de.reject(uri, desc);
            }
         }
         delete self._calls[o[1]];
      }
      else if (o[0] === ab._MESSAGE_TYPEID_EVENT)
      {
         var subid = self._prefixes.resolve(o[1], true);
         if (subid in self._subscriptions) {

            var uri2 = o[1];
            var val = o[2];

            if (ab._debugpubsub) {
               console.group("WAMP Event");
               console.info(self._wsuri + "  [" + self._session_id + "]");
               console.log(uri2);
               console.log(val);
               console.groupEnd();
            }

            self._subscriptions[subid].forEach(function (callback) {

               callback(uri2, val);
            });
         }
         else {
            // ignore unsolicited event!
         }
      }
      else if (o[0] === ab._MESSAGE_TYPEID_WELCOME)
      {
         if (self._session_id === null) {
            self._session_id = o[1];
            self._wamp_version = o[2];
            self._server = o[3];

            if (ab._debugrpc || ab._debugpubsub) {
               console.group("WAMP Welcome");
               console.info(self._wsuri + "  [" + self._session_id + "]");
               console.log(self._wamp_version);
               console.log(self._server);
               console.groupEnd();
            }

            // only now that we have received the initial server-to-client
            // welcome message, fire application onopen() hook
            if (self._websocket_onopen !== null) {
               self._websocket_onopen(self._session_id, self._wamp_version, self._server);
            }
         } else {
            throw "protocol error (welcome message received more than once)";
         }
      }
   };

   self._websocket.onopen = function (e)
   {
      // check if we can speak WAMP!
      if (self._websocket.protocol !== ab._subprotocol) {

         if (typeof self._websocket.protocol === 'undefined') {
            // i.e. Safari does subprotocol negotiation (broken), but then
            // does NOT set the protocol attribute of the websocket object (broken)
            //
            if (ab._debugws) {
               console.group("WS Warning");
               console.info(self._wsuri);
               console.log("WebSocket object has no protocol attribute: WAMP subprotocol check skipped!");
               console.groupEnd();
            }
         }
         else if (self._options && self._options.skipSubprotocolCheck) {
            // WAMP subprotocol check disabled by session option
            //
            if (ab._debugws) {
               console.group("WS Warning");
               console.info(self._wsuri);
               console.log("Server does not speak WAMP, but subprotocol check disabled by option!");
               console.log(self._websocket.protocol);
               console.groupEnd();
            }
         } else {
            // we only speak WAMP .. if the server denied us this, we bail out.
            //
            self._websocket.close(1000, "server does not speak WAMP");
            throw "server does not speak WAMP (but '" + self._websocket.protocol + "' !)";
         }
      }
      if (ab._debugws) {
         console.group("WAMP Connect");
         console.info(self._wsuri);
         console.log(self._websocket.protocol);
         console.groupEnd();
      }
      self._websocket_connected = true;
   };

   self._websocket.onerror = function (e)
   {
      // FF fires this upon unclean closes
      // Chrome does not fire this
   };

   self._websocket.onclose = function (e)
   {
      if (ab._debugws) {
         if (self._websocket_connected) {
            console.log("Autobahn connection to " + self._wsuri + " lost (code " + e.code + ", reason '" + e.reason + "', wasClean " + e.wasClean + ").");
         } else {
            console.log("Autobahn could not connect to " + self._wsuri + " (code " + e.code + ", reason '" + e.reason + "', wasClean " + e.wasClean + ").");
         }
      }

      // fire app callback
      if (self._websocket_onclose !== undefined) {
         if (self._websocket_connected) {
            if (e.wasClean) {
               // connection was closed cleanly (closing HS was performed)
               self._websocket_onclose(ab.CONNECTION_CLOSED);
            } else {
               // connection was closed uncleanly (lost without closing HS)
               self._websocket_onclose(ab.CONNECTION_LOST);
            }
         } else {
            // connection could not be established in the first place
            self._websocket_onclose(ab.CONNECTION_UNREACHABLE);
         }
      }

      // cleanup - reconnect requires a new session object!
      self._websocket_connected = false;
      self._wsuri = null;
      self._websocket_onopen = null;
      self._websocket_onclose = null;
      self._websocket = null;
   };
};


ab.Session.prototype._send = function (msg) {

   var self = this;

   if (!self._websocket_connected) {
      throw "Autobahn not connected";
   }

   var rmsg = JSON.stringify(msg);
   self._websocket.send(rmsg);
   self._txcnt += 1;

   if (ab._debugws) {
      console.group("WS Send");
      console.info(self._wsuri + "  [" + self._session_id + "]");
      console.log(self._txcnt);
      console.log(rmsg);
      console.groupEnd();
   }
};


ab.Session.prototype.close = function () {

   var self = this;

   if (!self._websocket_connected) {
      throw "Autobahn not connected";
   }

   self._websocket.close();
};


ab.Session.prototype.sessionid = function () {

   var self = this;
   return self._session_id;
};


ab.Session.prototype.shrink = function (uri, pass) {

   var self = this;
   return self._prefixes.shrink(uri, pass);
};


ab.Session.prototype.resolve = function (curie, pass) {

   var self = this;
   return self._prefixes.resolve(curie, pass);
};


ab.Session.prototype.prefix = function (prefix, uri) {

   var self = this;

   if (self._prefixes.get(prefix) !== undefined) {
      throw "prefix '" + prefix + "' already defined";
   }

   self._prefixes.set(prefix, uri);

   if (ab._debugrpc || ab._debugpubsub) {
      console.group("WAMP Prefix");
      console.info(self._wsuri + "  [" + self._session_id + "]");
      console.log(prefix);
      console.log(uri);
      console.groupEnd();
   }

   var msg = [ab._MESSAGE_TYPEID_PREFIX, prefix, uri];
   self._send(msg);
};


ab.Session.prototype.call = function () {

   var self = this;

   var d = new when.defer();
   var callid;
   while (true) {
      callid = ab._newid();
      if (!(callid in self._calls)) {
         break;
      }
   }
   self._calls[callid] = d;

   var procuri = self._prefixes.shrink(arguments[0], true);
   var obj = [ab._MESSAGE_TYPEID_CALL, callid, procuri];
   for (var i = 1; i < arguments.length; i += 1) {
      obj.push(arguments[i]);
   }

   self._send(obj);

   if (ab._debugrpc) {
      d._ab_callobj = obj;
      d._ab_tid = self._wsuri + "  [" + self._session_id + "][" + callid + "]";
      console.time(d._ab_tid);
      console.info();
   }

   return d;
};


ab.Session.prototype.subscribe = function (topicuri, callback) {

   var self = this;

   // subscribe by sending WAMP message when topic not already subscribed
   //
   var rtopicuri = self._prefixes.resolve(topicuri, true);
   if (!(rtopicuri in self._subscriptions)) {

      if (ab._debugpubsub) {
         console.group("WAMP Subscribe");
         console.info(self._wsuri + "  [" + self._session_id + "]");
         console.log(topicuri);
         console.log(callback);
         console.groupEnd();
      }

      var msg = [ab._MESSAGE_TYPEID_SUBSCRIBE, topicuri];
      self._send(msg);

      self._subscriptions[rtopicuri] = [];
   }

   // add callback to event listeners list if not already in list
   //
   var i = self._subscriptions[rtopicuri].indexOf(callback);
   if (i === -1) {
      self._subscriptions[rtopicuri].push(callback);
   }
   else {
      throw "callback " + callback + " already subscribed for topic " + rtopicuri;
   }
};


ab.Session.prototype.unsubscribe = function (topicuri, callback) {

   var self = this;

   var rtopicuri = self._prefixes.resolve(topicuri, true);
   if (!(rtopicuri in self._subscriptions)) {
      throw "not subscribed to topic " + rtopicuri;
   }
   else {
      var removed;
      if (callback !== undefined) {
         var idx = self._subscriptions[rtopicuri].indexOf(callback);
         if (idx !== -1) {
            removed = callback;
            self._subscriptions[rtopicuri].splice(idx, 1);
         }
         else {
            throw "no callback " + callback + " subscribed on topic " + rtopicuri;
         }
      }
      else {
         removed = self._subscriptions[rtopicuri].slice();
         self._subscriptions[rtopicuri] = [];
      }

      if (self._subscriptions[rtopicuri].length === 0) {

         delete self._subscriptions[rtopicuri];

         if (ab._debugpubsub) {
            console.group("WAMP Unsubscribe");
            console.info(self._wsuri + "  [" + self._session_id + "]");
            console.log(topicuri);
            console.log(removed);
            console.groupEnd();
         }

         var msg = [ab._MESSAGE_TYPEID_UNSUBSCRIBE, topicuri];
         self._send(msg);
      }
   }
};


ab.Session.prototype.publish = function () {

   var self = this;

   var topicuri = arguments[0];
   var event = arguments[1];

   var excludeMe = null;
   var exclude = null;
   var eligible = null;

   var msg = null;

   if (arguments.length > 3) {

      if (!(arguments[2] instanceof Array)) {
         throw "invalid argument type(s)";
      }
      if (!(arguments[3] instanceof Array)) {
         throw "invalid argument type(s)";
      }

      exclude = arguments[2];
      eligible = arguments[3];
      msg = [ab._MESSAGE_TYPEID_PUBLISH, topicuri, event, exclude, eligible];

   } else if (arguments.length > 2) {

      if (typeof(arguments[2]) === 'boolean') {

         excludeMe = arguments[2];
         msg = [ab._MESSAGE_TYPEID_PUBLISH, topicuri, event, excludeMe];

      } else if (arguments[2] instanceof Array) {

         exclude = arguments[2];
         msg = [ab._MESSAGE_TYPEID_PUBLISH, topicuri, event, exclude];

      } else {
         throw "invalid argument type(s)";
      }

   } else {

      msg = [ab._MESSAGE_TYPEID_PUBLISH, topicuri, event];
   }

   if (ab._debugpubsub) {
      console.group("WAMP Publish");
      console.info(self._wsuri + "  [" + self._session_id + "]");
      console.log(topicuri);
      console.log(event);

      if (excludeMe !== null) {
         console.log(excludeMe);
      } else {
         if (exclude !== null) {
            console.log(exclude);
            if (eligible !== null) {
               console.log(eligible);
            }
         }
      }
      console.groupEnd();
   }

   self._send(msg);
};
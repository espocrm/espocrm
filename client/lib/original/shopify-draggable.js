define('@shopify/draggable', ['exports'], (function (exports) { 'use strict';

  class AbstractEvent {

    constructor(data) {

      this._canceled = false;
      this.data = data;
    }

    get type() {
      return this.constructor.type;
    }

    get cancelable() {
      return this.constructor.cancelable;
    }

    cancel() {
      this._canceled = true;
    }

    canceled() {
      return this._canceled;
    }

    clone(data) {
      return new this.constructor({
        ...this.data,
        ...data
      });
    }
  }

  AbstractEvent.type = 'event';

  AbstractEvent.cancelable = false;

  class AbstractPlugin {

    constructor(draggable) {
      this.draggable = draggable;
    }

    attach() {
      throw new Error('Not Implemented');
    }

    detach() {
      throw new Error('Not Implemented');
    }
  }

  const defaultDelay = {
    mouse: 0,
    drag: 0,
    touch: 100
  };

  class Sensor {

    constructor(containers = [], options = {}) {

      this.containers = [...containers];

      this.options = {
        ...options
      };

      this.dragging = false;

      this.currentContainer = null;

      this.originalSource = null;

      this.startEvent = null;

      this.delay = calcDelay(options.delay);
    }

    attach() {
      return this;
    }

    detach() {
      return this;
    }

    addContainer(...containers) {
      this.containers = [...this.containers, ...containers];
    }

    removeContainer(...containers) {
      this.containers = this.containers.filter(container => !containers.includes(container));
    }

    trigger(element, sensorEvent) {
      const event = document.createEvent('Event');
      event.detail = sensorEvent;
      event.initEvent(sensorEvent.type, true, true);
      element.dispatchEvent(event);
      this.lastEvent = sensorEvent;
      return sensorEvent;
    }
  }

  function calcDelay(optionsDelay) {
    const delay = {};
    if (optionsDelay === undefined) {
      return {
        ...defaultDelay
      };
    }
    if (typeof optionsDelay === 'number') {
      for (const key in defaultDelay) {
        if (Object.prototype.hasOwnProperty.call(defaultDelay, key)) {
          delay[key] = optionsDelay;
        }
      }
      return delay;
    }
    for (const key in defaultDelay) {
      if (Object.prototype.hasOwnProperty.call(defaultDelay, key)) {
        if (optionsDelay[key] === undefined) {
          delay[key] = defaultDelay[key];
        } else {
          delay[key] = optionsDelay[key];
        }
      }
    }
    return delay;
  }

  function closest(node, value) {
    if (node == null) {
      return null;
    }
    function conditionFn(currentNode) {
      if (currentNode == null || value == null) {
        return false;
      } else if (isSelector(value)) {
        return Element.prototype.matches.call(currentNode, value);
      } else if (isNodeList(value)) {
        return [...value].includes(currentNode);
      } else if (isElement(value)) {
        return value === currentNode;
      } else if (isFunction(value)) {
        return value(currentNode);
      } else {
        return false;
      }
    }
    let current = node;
    do {
      current = current.correspondingUseElement || current.correspondingElement || current;
      if (conditionFn(current)) {
        return current;
      }
      current = current?.parentNode || null;
    } while (current != null && current !== document.body && current !== document);
    return null;
  }
  function isSelector(value) {
    return Boolean(typeof value === 'string');
  }
  function isNodeList(value) {
    return Boolean(value instanceof NodeList || value instanceof Array);
  }
  function isElement(value) {
    return Boolean(value instanceof Node);
  }
  function isFunction(value) {
    return Boolean(typeof value === 'function');
  }

  function distance(x1, y1, x2, y2) {
    return Math.sqrt((x2 - x1) ** 2 + (y2 - y1) ** 2);
  }

  class SensorEvent extends AbstractEvent {

    get originalEvent() {
      return this.data.originalEvent;
    }

    get clientX() {
      return this.data.clientX;
    }

    get clientY() {
      return this.data.clientY;
    }

    get target() {
      return this.data.target;
    }

    get container() {
      return this.data.container;
    }

    get originalSource() {
      return this.data.originalSource;
    }

    get pressure() {
      return this.data.pressure;
    }
  }

  class DragStartSensorEvent extends SensorEvent {}

  DragStartSensorEvent.type = 'drag:start';
  class DragMoveSensorEvent extends SensorEvent {}

  DragMoveSensorEvent.type = 'drag:move';
  class DragStopSensorEvent extends SensorEvent {}

  DragStopSensorEvent.type = 'drag:stop';
  class DragPressureSensorEvent extends SensorEvent {}
  DragPressureSensorEvent.type = 'drag:pressure';

  const onContextMenuWhileDragging = Symbol('onContextMenuWhileDragging');
  const onMouseDown$2 = Symbol('onMouseDown');
  const onMouseMove$1 = Symbol('onMouseMove');
  const onMouseUp$2 = Symbol('onMouseUp');
  const startDrag$1 = Symbol('startDrag');
  const onDistanceChange$1 = Symbol('onDistanceChange');

  class MouseSensor extends Sensor {

    constructor(containers = [], options = {}) {
      super(containers, options);

      this.mouseDownTimeout = null;

      this.pageX = null;

      this.pageY = null;
      this[onContextMenuWhileDragging] = this[onContextMenuWhileDragging].bind(this);
      this[onMouseDown$2] = this[onMouseDown$2].bind(this);
      this[onMouseMove$1] = this[onMouseMove$1].bind(this);
      this[onMouseUp$2] = this[onMouseUp$2].bind(this);
      this[startDrag$1] = this[startDrag$1].bind(this);
      this[onDistanceChange$1] = this[onDistanceChange$1].bind(this);
    }

    attach() {
      document.addEventListener('mousedown', this[onMouseDown$2], true);
    }

    detach() {
      document.removeEventListener('mousedown', this[onMouseDown$2], true);
    }

    [onMouseDown$2](event) {
      if (event.button !== 0 || event.ctrlKey || event.metaKey) {
        return;
      }
      const container = closest(event.target, this.containers);
      if (!container) {
        return;
      }
      if (this.options.handle && event.target && !closest(event.target, this.options.handle)) {
        return;
      }
      const originalSource = closest(event.target, this.options.draggable);
      if (!originalSource) {
        return;
      }
      const {
        delay
      } = this;
      const {
        pageX,
        pageY
      } = event;
      Object.assign(this, {
        pageX,
        pageY
      });
      this.onMouseDownAt = Date.now();
      this.startEvent = event;
      this.currentContainer = container;
      this.originalSource = originalSource;
      document.addEventListener('mouseup', this[onMouseUp$2]);
      document.addEventListener('dragstart', preventNativeDragStart);
      document.addEventListener('mousemove', this[onDistanceChange$1]);
      this.mouseDownTimeout = window.setTimeout(() => {
        this[onDistanceChange$1]({
          pageX: this.pageX,
          pageY: this.pageY
        });
      }, delay.mouse);
    }

    [startDrag$1]() {
      const startEvent = this.startEvent;
      const container = this.currentContainer;
      const originalSource = this.originalSource;
      const dragStartEvent = new DragStartSensorEvent({
        clientX: startEvent.clientX,
        clientY: startEvent.clientY,
        target: startEvent.target,
        container,
        originalSource,
        originalEvent: startEvent
      });
      this.trigger(this.currentContainer, dragStartEvent);
      this.dragging = !dragStartEvent.canceled();
      if (this.dragging) {
        document.addEventListener('contextmenu', this[onContextMenuWhileDragging], true);
        document.addEventListener('mousemove', this[onMouseMove$1]);
      }
    }

    [onDistanceChange$1](event) {
      const {
        pageX,
        pageY
      } = event;
      const {
        distance: distance$1
      } = this.options;
      const {
        startEvent,
        delay
      } = this;
      Object.assign(this, {
        pageX,
        pageY
      });
      if (!this.currentContainer) {
        return;
      }
      const timeElapsed = Date.now() - this.onMouseDownAt;
      const distanceTravelled = distance(startEvent.pageX, startEvent.pageY, pageX, pageY) || 0;
      clearTimeout(this.mouseDownTimeout);
      if (timeElapsed < delay.mouse) {

        document.removeEventListener('mousemove', this[onDistanceChange$1]);
      } else if (distanceTravelled >= distance$1) {
        document.removeEventListener('mousemove', this[onDistanceChange$1]);
        this[startDrag$1]();
      }
    }

    [onMouseMove$1](event) {
      if (!this.dragging) {
        return;
      }
      const target = document.elementFromPoint(event.clientX, event.clientY);
      const dragMoveEvent = new DragMoveSensorEvent({
        clientX: event.clientX,
        clientY: event.clientY,
        target,
        container: this.currentContainer,
        originalEvent: event
      });
      this.trigger(this.currentContainer, dragMoveEvent);
    }

    [onMouseUp$2](event) {
      clearTimeout(this.mouseDownTimeout);
      if (event.button !== 0) {
        return;
      }
      document.removeEventListener('mouseup', this[onMouseUp$2]);
      document.removeEventListener('dragstart', preventNativeDragStart);
      document.removeEventListener('mousemove', this[onDistanceChange$1]);
      if (!this.dragging) {
        return;
      }
      const target = document.elementFromPoint(event.clientX, event.clientY);
      const dragStopEvent = new DragStopSensorEvent({
        clientX: event.clientX,
        clientY: event.clientY,
        target,
        container: this.currentContainer,
        originalEvent: event
      });
      this.trigger(this.currentContainer, dragStopEvent);
      document.removeEventListener('contextmenu', this[onContextMenuWhileDragging], true);
      document.removeEventListener('mousemove', this[onMouseMove$1]);
      this.currentContainer = null;
      this.dragging = false;
      this.startEvent = null;
    }

    [onContextMenuWhileDragging](event) {
      event.preventDefault();
    }
  }
  function preventNativeDragStart(event) {
    event.preventDefault();
  }

  function touchCoords(event) {
    const {
      touches,
      changedTouches
    } = event;
    return touches && touches[0] || changedTouches && changedTouches[0];
  }

  const onTouchStart = Symbol('onTouchStart');
  const onTouchEnd = Symbol('onTouchEnd');
  const onTouchMove = Symbol('onTouchMove');
  const startDrag = Symbol('startDrag');
  const onDistanceChange = Symbol('onDistanceChange');

  let preventScrolling = false;

  window.addEventListener('touchmove', event => {
    if (!preventScrolling) {
      return;
    }

    event.preventDefault();
  }, {
    passive: false
  });

  class TouchSensor extends Sensor {

    constructor(containers = [], options = {}) {
      super(containers, options);

      this.currentScrollableParent = null;

      this.tapTimeout = null;

      this.touchMoved = false;

      this.pageX = null;

      this.pageY = null;
      this[onTouchStart] = this[onTouchStart].bind(this);
      this[onTouchEnd] = this[onTouchEnd].bind(this);
      this[onTouchMove] = this[onTouchMove].bind(this);
      this[startDrag] = this[startDrag].bind(this);
      this[onDistanceChange] = this[onDistanceChange].bind(this);
    }

    attach() {
      document.addEventListener('touchstart', this[onTouchStart]);
    }

    detach() {
      document.removeEventListener('touchstart', this[onTouchStart]);
    }

    [onTouchStart](event) {
      const container = closest(event.target, this.containers);
      if (!container) {
        return;
      }
      if (this.options.handle && event.target && !closest(event.target, this.options.handle)) {
        return;
      }
      const originalSource = closest(event.target, this.options.draggable);
      if (!originalSource) {
        return;
      }
      const {
        distance = 0
      } = this.options;
      const {
        delay
      } = this;
      const {
        pageX,
        pageY
      } = touchCoords(event);
      Object.assign(this, {
        pageX,
        pageY
      });
      this.onTouchStartAt = Date.now();
      this.startEvent = event;
      this.currentContainer = container;
      this.originalSource = originalSource;
      document.addEventListener('touchend', this[onTouchEnd]);
      document.addEventListener('touchcancel', this[onTouchEnd]);
      document.addEventListener('touchmove', this[onDistanceChange]);
      container.addEventListener('contextmenu', onContextMenu);
      if (distance) {
        preventScrolling = true;
      }
      this.tapTimeout = window.setTimeout(() => {
        this[onDistanceChange]({
          touches: [{
            pageX: this.pageX,
            pageY: this.pageY
          }]
        });
      }, delay.touch);
    }

    [startDrag]() {
      const startEvent = this.startEvent;
      const container = this.currentContainer;
      const touch = touchCoords(startEvent);
      const originalSource = this.originalSource;
      const dragStartEvent = new DragStartSensorEvent({
        clientX: touch.pageX,
        clientY: touch.pageY,
        target: startEvent.target,
        container,
        originalSource,
        originalEvent: startEvent
      });
      this.trigger(this.currentContainer, dragStartEvent);
      this.dragging = !dragStartEvent.canceled();
      if (this.dragging) {
        document.addEventListener('touchmove', this[onTouchMove]);
      }
      preventScrolling = this.dragging;
    }

    [onDistanceChange](event) {
      const {
        distance: distance$1
      } = this.options;
      const {
        startEvent,
        delay
      } = this;
      const start = touchCoords(startEvent);
      const current = touchCoords(event);
      const timeElapsed = Date.now() - this.onTouchStartAt;
      const distanceTravelled = distance(start.pageX, start.pageY, current.pageX, current.pageY);
      Object.assign(this, current);
      clearTimeout(this.tapTimeout);
      if (timeElapsed < delay.touch) {

        document.removeEventListener('touchmove', this[onDistanceChange]);
      } else if (distanceTravelled >= distance$1) {
        document.removeEventListener('touchmove', this[onDistanceChange]);
        this[startDrag]();
      }
    }

    [onTouchMove](event) {
      if (!this.dragging) {
        return;
      }
      const {
        pageX,
        pageY
      } = touchCoords(event);
      const target = document.elementFromPoint(pageX - window.scrollX, pageY - window.scrollY);
      const dragMoveEvent = new DragMoveSensorEvent({
        clientX: pageX,
        clientY: pageY,
        target,
        container: this.currentContainer,
        originalEvent: event
      });
      this.trigger(this.currentContainer, dragMoveEvent);
    }

    [onTouchEnd](event) {
      clearTimeout(this.tapTimeout);
      preventScrolling = false;
      document.removeEventListener('touchend', this[onTouchEnd]);
      document.removeEventListener('touchcancel', this[onTouchEnd]);
      document.removeEventListener('touchmove', this[onDistanceChange]);
      if (this.currentContainer) {
        this.currentContainer.removeEventListener('contextmenu', onContextMenu);
      }
      if (!this.dragging) {
        return;
      }
      document.removeEventListener('touchmove', this[onTouchMove]);
      const {
        pageX,
        pageY
      } = touchCoords(event);
      const target = document.elementFromPoint(pageX - window.scrollX, pageY - window.scrollY);
      event.preventDefault();
      const dragStopEvent = new DragStopSensorEvent({
        clientX: pageX,
        clientY: pageY,
        target,
        container: this.currentContainer,
        originalEvent: event
      });
      this.trigger(this.currentContainer, dragStopEvent);
      this.currentContainer = null;
      this.dragging = false;
      this.startEvent = null;
    }
  }
  function onContextMenu(event) {
    event.preventDefault();
    event.stopPropagation();
  }

  const onMouseDown$1 = Symbol('onMouseDown');
  const onMouseUp$1 = Symbol('onMouseUp');
  const onDragStart$7 = Symbol('onDragStart');
  const onDragOver$3 = Symbol('onDragOver');
  const onDragEnd = Symbol('onDragEnd');
  const onDrop = Symbol('onDrop');
  const reset = Symbol('reset');

  class DragSensor extends Sensor {

    constructor(containers = [], options = {}) {
      super(containers, options);

      this.mouseDownTimeout = null;

      this.draggableElement = null;

      this.nativeDraggableElement = null;
      this[onMouseDown$1] = this[onMouseDown$1].bind(this);
      this[onMouseUp$1] = this[onMouseUp$1].bind(this);
      this[onDragStart$7] = this[onDragStart$7].bind(this);
      this[onDragOver$3] = this[onDragOver$3].bind(this);
      this[onDragEnd] = this[onDragEnd].bind(this);
      this[onDrop] = this[onDrop].bind(this);
    }

    attach() {
      document.addEventListener('mousedown', this[onMouseDown$1], true);
    }

    detach() {
      document.removeEventListener('mousedown', this[onMouseDown$1], true);
    }

    [onDragStart$7](event) {

      event.dataTransfer.setData('text', '');
      event.dataTransfer.effectAllowed = this.options.type;
      const target = document.elementFromPoint(event.clientX, event.clientY);
      const originalSource = this.draggableElement;
      if (!originalSource) {
        return;
      }
      const dragStartEvent = new DragStartSensorEvent({
        clientX: event.clientX,
        clientY: event.clientY,
        target,
        originalSource,
        container: this.currentContainer,
        originalEvent: event
      });

      setTimeout(() => {
        this.trigger(this.currentContainer, dragStartEvent);
        if (dragStartEvent.canceled()) {
          this.dragging = false;
        } else {
          this.dragging = true;
        }
      }, 0);
    }

    [onDragOver$3](event) {
      if (!this.dragging) {
        return;
      }
      const target = document.elementFromPoint(event.clientX, event.clientY);
      const container = this.currentContainer;
      const dragMoveEvent = new DragMoveSensorEvent({
        clientX: event.clientX,
        clientY: event.clientY,
        target,
        container,
        originalEvent: event
      });
      this.trigger(container, dragMoveEvent);
      if (!dragMoveEvent.canceled()) {
        event.preventDefault();
        event.dataTransfer.dropEffect = this.options.type;
      }
    }

    [onDragEnd](event) {
      if (!this.dragging) {
        return;
      }
      document.removeEventListener('mouseup', this[onMouseUp$1], true);
      const target = document.elementFromPoint(event.clientX, event.clientY);
      const container = this.currentContainer;
      const dragStopEvent = new DragStopSensorEvent({
        clientX: event.clientX,
        clientY: event.clientY,
        target,
        container,
        originalEvent: event
      });
      this.trigger(container, dragStopEvent);
      this.dragging = false;
      this.startEvent = null;
      this[reset]();
    }

    [onDrop](event) {
      event.preventDefault();
    }

    [onMouseDown$1](event) {

      if (event.target && (event.target.form || event.target.contenteditable)) {
        return;
      }
      const target = event.target;
      this.currentContainer = closest(target, this.containers);
      if (!this.currentContainer) {
        return;
      }
      if (this.options.handle && target && !closest(target, this.options.handle)) {
        return;
      }
      const originalSource = closest(target, this.options.draggable);
      if (!originalSource) {
        return;
      }
      const nativeDraggableElement = closest(event.target, element => element.draggable);
      if (nativeDraggableElement) {
        nativeDraggableElement.draggable = false;
        this.nativeDraggableElement = nativeDraggableElement;
      }
      document.addEventListener('mouseup', this[onMouseUp$1], true);
      document.addEventListener('dragstart', this[onDragStart$7], false);
      document.addEventListener('dragover', this[onDragOver$3], false);
      document.addEventListener('dragend', this[onDragEnd], false);
      document.addEventListener('drop', this[onDrop], false);
      this.startEvent = event;
      this.mouseDownTimeout = setTimeout(() => {
        originalSource.draggable = true;
        this.draggableElement = originalSource;
      }, this.delay.drag);
    }

    [onMouseUp$1]() {
      this[reset]();
    }

    [reset]() {
      clearTimeout(this.mouseDownTimeout);
      document.removeEventListener('mouseup', this[onMouseUp$1], true);
      document.removeEventListener('dragstart', this[onDragStart$7], false);
      document.removeEventListener('dragover', this[onDragOver$3], false);
      document.removeEventListener('dragend', this[onDragEnd], false);
      document.removeEventListener('drop', this[onDrop], false);
      if (this.nativeDraggableElement) {
        this.nativeDraggableElement.draggable = true;
        this.nativeDraggableElement = null;
      }
      if (this.draggableElement) {
        this.draggableElement.draggable = false;
        this.draggableElement = null;
      }
    }
  }

  const onMouseForceWillBegin = Symbol('onMouseForceWillBegin');
  const onMouseForceDown = Symbol('onMouseForceDown');
  const onMouseDown = Symbol('onMouseDown');
  const onMouseForceChange = Symbol('onMouseForceChange');
  const onMouseMove = Symbol('onMouseMove');
  const onMouseUp = Symbol('onMouseUp');
  const onMouseForceGlobalChange = Symbol('onMouseForceGlobalChange');

  class ForceTouchSensor extends Sensor {

    constructor(containers = [], options = {}) {
      super(containers, options);

      this.mightDrag = false;
      this[onMouseForceWillBegin] = this[onMouseForceWillBegin].bind(this);
      this[onMouseForceDown] = this[onMouseForceDown].bind(this);
      this[onMouseDown] = this[onMouseDown].bind(this);
      this[onMouseForceChange] = this[onMouseForceChange].bind(this);
      this[onMouseMove] = this[onMouseMove].bind(this);
      this[onMouseUp] = this[onMouseUp].bind(this);
    }

    attach() {
      for (const container of this.containers) {
        container.addEventListener('webkitmouseforcewillbegin', this[onMouseForceWillBegin], false);
        container.addEventListener('webkitmouseforcedown', this[onMouseForceDown], false);
        container.addEventListener('mousedown', this[onMouseDown], true);
        container.addEventListener('webkitmouseforcechanged', this[onMouseForceChange], false);
      }
      document.addEventListener('mousemove', this[onMouseMove]);
      document.addEventListener('mouseup', this[onMouseUp]);
    }

    detach() {
      for (const container of this.containers) {
        container.removeEventListener('webkitmouseforcewillbegin', this[onMouseForceWillBegin], false);
        container.removeEventListener('webkitmouseforcedown', this[onMouseForceDown], false);
        container.removeEventListener('mousedown', this[onMouseDown], true);
        container.removeEventListener('webkitmouseforcechanged', this[onMouseForceChange], false);
      }
      document.removeEventListener('mousemove', this[onMouseMove]);
      document.removeEventListener('mouseup', this[onMouseUp]);
    }

    [onMouseForceWillBegin](event) {
      event.preventDefault();
      this.mightDrag = true;
    }

    [onMouseForceDown](event) {
      if (this.dragging) {
        return;
      }
      const target = document.elementFromPoint(event.clientX, event.clientY);
      const container = event.currentTarget;
      if (this.options.handle && target && !closest(target, this.options.handle)) {
        return;
      }
      const originalSource = closest(target, this.options.draggable);
      if (!originalSource) {
        return;
      }
      const dragStartEvent = new DragStartSensorEvent({
        clientX: event.clientX,
        clientY: event.clientY,
        target,
        container,
        originalSource,
        originalEvent: event
      });
      this.trigger(container, dragStartEvent);
      this.currentContainer = container;
      this.dragging = !dragStartEvent.canceled();
      this.mightDrag = false;
    }

    [onMouseUp](event) {
      if (!this.dragging) {
        return;
      }
      const dragStopEvent = new DragStopSensorEvent({
        clientX: event.clientX,
        clientY: event.clientY,
        target: null,
        container: this.currentContainer,
        originalEvent: event
      });
      this.trigger(this.currentContainer, dragStopEvent);
      this.currentContainer = null;
      this.dragging = false;
      this.mightDrag = false;
    }

    [onMouseDown](event) {
      if (!this.mightDrag) {
        return;
      }

      event.stopPropagation();
      event.stopImmediatePropagation();
      event.preventDefault();
    }

    [onMouseMove](event) {
      if (!this.dragging) {
        return;
      }
      const target = document.elementFromPoint(event.clientX, event.clientY);
      const dragMoveEvent = new DragMoveSensorEvent({
        clientX: event.clientX,
        clientY: event.clientY,
        target,
        container: this.currentContainer,
        originalEvent: event
      });
      this.trigger(this.currentContainer, dragMoveEvent);
    }

    [onMouseForceChange](event) {
      if (this.dragging) {
        return;
      }
      const target = event.target;
      const container = event.currentTarget;
      const dragPressureEvent = new DragPressureSensorEvent({
        pressure: event.webkitForce,
        clientX: event.clientX,
        clientY: event.clientY,
        target,
        container,
        originalEvent: event
      });
      this.trigger(container, dragPressureEvent);
    }

    [onMouseForceGlobalChange](event) {
      if (!this.dragging) {
        return;
      }
      const target = event.target;
      const dragPressureEvent = new DragPressureSensorEvent({
        pressure: event.webkitForce,
        clientX: event.clientX,
        clientY: event.clientY,
        target,
        container: this.currentContainer,
        originalEvent: event
      });
      this.trigger(this.currentContainer, dragPressureEvent);
    }
  }

  var index$2 = /*#__PURE__*/Object.freeze({
    __proto__: null,
    DragMoveSensorEvent: DragMoveSensorEvent,
    DragPressureSensorEvent: DragPressureSensorEvent,
    DragSensor: DragSensor,
    DragStartSensorEvent: DragStartSensorEvent,
    DragStopSensorEvent: DragStopSensorEvent,
    ForceTouchSensor: ForceTouchSensor,
    MouseSensor: MouseSensor,
    Sensor: Sensor,
    SensorEvent: SensorEvent,
    TouchSensor: TouchSensor
  });

  class CollidableEvent extends AbstractEvent {

    constructor(data) {
      super(data);
      this.data = data;
    }

    get dragEvent() {
      return this.data.dragEvent;
    }
  }
  CollidableEvent.type = 'collidable';

  class CollidableInEvent extends CollidableEvent {

    get collidingElement() {
      return this.data.collidingElement;
    }
  }
  CollidableInEvent.type = 'collidable:in';

  class CollidableOutEvent extends CollidableEvent {

    get collidingElement() {
      return this.data.collidingElement;
    }
  }
  CollidableOutEvent.type = 'collidable:out';

  const onDragMove$4 = Symbol('onDragMove');
  const onDragStop$7 = Symbol('onDragStop');
  const onRequestAnimationFrame = Symbol('onRequestAnimationFrame');

  class Collidable extends AbstractPlugin {

    constructor(draggable) {
      super(draggable);

      this.currentlyCollidingElement = null;

      this.lastCollidingElement = null;

      this.currentAnimationFrame = null;
      this[onDragMove$4] = this[onDragMove$4].bind(this);
      this[onDragStop$7] = this[onDragStop$7].bind(this);
      this[onRequestAnimationFrame] = this[onRequestAnimationFrame].bind(this);
    }

    attach() {
      this.draggable.on('drag:move', this[onDragMove$4]).on('drag:stop', this[onDragStop$7]);
    }

    detach() {
      this.draggable.off('drag:move', this[onDragMove$4]).off('drag:stop', this[onDragStop$7]);
    }

    getCollidables() {
      const collidables = this.draggable.options.collidables;
      if (typeof collidables === 'string') {
        return Array.prototype.slice.call(document.querySelectorAll(collidables));
      } else if (collidables instanceof NodeList || collidables instanceof Array) {
        return Array.prototype.slice.call(collidables);
      } else if (collidables instanceof HTMLElement) {
        return [collidables];
      } else if (typeof collidables === 'function') {
        return collidables();
      } else {
        return [];
      }
    }

    [onDragMove$4](event) {
      const target = event.sensorEvent.target;
      this.currentAnimationFrame = requestAnimationFrame(this[onRequestAnimationFrame](target));
      if (this.currentlyCollidingElement) {
        event.cancel();
      }
      const collidableInEvent = new CollidableInEvent({
        dragEvent: event,
        collidingElement: this.currentlyCollidingElement
      });
      const collidableOutEvent = new CollidableOutEvent({
        dragEvent: event,
        collidingElement: this.lastCollidingElement
      });
      const enteringCollidable = Boolean(this.currentlyCollidingElement && this.lastCollidingElement !== this.currentlyCollidingElement);
      const leavingCollidable = Boolean(!this.currentlyCollidingElement && this.lastCollidingElement);
      if (enteringCollidable) {
        if (this.lastCollidingElement) {
          this.draggable.trigger(collidableOutEvent);
        }
        this.draggable.trigger(collidableInEvent);
      } else if (leavingCollidable) {
        this.draggable.trigger(collidableOutEvent);
      }
      this.lastCollidingElement = this.currentlyCollidingElement;
    }

    [onDragStop$7](event) {
      const lastCollidingElement = this.currentlyCollidingElement || this.lastCollidingElement;
      const collidableOutEvent = new CollidableOutEvent({
        dragEvent: event,
        collidingElement: lastCollidingElement
      });
      if (lastCollidingElement) {
        this.draggable.trigger(collidableOutEvent);
      }
      this.lastCollidingElement = null;
      this.currentlyCollidingElement = null;
    }

    [onRequestAnimationFrame](target) {
      return () => {
        const collidables = this.getCollidables();
        this.currentlyCollidingElement = closest(target, element => collidables.includes(element));
      };
    }
  }

  function createAddInitializerMethod(e, t) {
    return function (r) {
      assertNotFinished(t, "addInitializer"), assertCallable(r, "An initializer"), e.push(r);
    };
  }
  function assertInstanceIfPrivate(e, t) {
    if (!e(t)) throw new TypeError("Attempted to access private element on non-instance");
  }
  function memberDec(e, t, r, a, n, i, s, o, c, l, u) {
    var f;
    switch (i) {
      case 1:
        f = "accessor";
        break;
      case 2:
        f = "method";
        break;
      case 3:
        f = "getter";
        break;
      case 4:
        f = "setter";
        break;
      default:
        f = "field";
    }
    var d,
      p,
      h = {
        kind: f,
        name: o ? "#" + r : r,
        static: s,
        private: o,
        metadata: u
      },
      v = {
        v: false
      };
    if (0 !== i && (h.addInitializer = createAddInitializerMethod(n, v)), o || 0 !== i && 2 !== i) {
      if (2 === i) d = function (e) {
        return assertInstanceIfPrivate(l, e), a.value;
      };else {
        var y = 0 === i || 1 === i;
        (y || 3 === i) && (d = o ? function (e) {
          return assertInstanceIfPrivate(l, e), a.get.call(e);
        } : function (e) {
          return a.get.call(e);
        }), (y || 4 === i) && (p = o ? function (e, t) {
          assertInstanceIfPrivate(l, e), a.set.call(e, t);
        } : function (e, t) {
          a.set.call(e, t);
        });
      }
    } else d = function (e) {
      return e[r];
    }, 0 === i && (p = function (e, t) {
      e[r] = t;
    });
    var m = o ? l.bind() : function (e) {
      return r in e;
    };
    h.access = d && p ? {
      get: d,
      set: p,
      has: m
    } : d ? {
      get: d,
      has: m
    } : {
      set: p,
      has: m
    };
    try {
      return e.call(t, c, h);
    } finally {
      v.v = true;
    }
  }
  function assertNotFinished(e, t) {
    if (e.v) throw new Error("attempted to call " + t + " after decoration was finished");
  }
  function assertCallable(e, t) {
    if ("function" != typeof e) throw new TypeError(t + " must be a function");
  }
  function assertValidReturnValue(e, t) {
    var r = typeof t;
    if (1 === e) {
      if ("object" !== r || null === t) throw new TypeError("accessor decorators must return an object with get, set, or init properties or void 0");
      void 0 !== t.get && assertCallable(t.get, "accessor.get"), void 0 !== t.set && assertCallable(t.set, "accessor.set"), void 0 !== t.init && assertCallable(t.init, "accessor.init");
    } else if ("function" !== r) {
      var a;
      throw a = 0 === e ? "field" : 5 === e ? "class" : "method", new TypeError(a + " decorators must return a function or void 0");
    }
  }
  function curryThis1(e) {
    return function () {
      return e(this);
    };
  }
  function curryThis2(e) {
    return function (t) {
      e(this, t);
    };
  }
  function applyMemberDec(e, t, r, a, n, i, s, o, c, l, u) {
    var f,
      d,
      p,
      h,
      v,
      y,
      m = r[0];
    a || Array.isArray(m) || (m = [m]), o ? f = 0 === i || 1 === i ? {
      get: curryThis1(r[3]),
      set: curryThis2(r[4])
    } : 3 === i ? {
      get: r[3]
    } : 4 === i ? {
      set: r[3]
    } : {
      value: r[3]
    } : 0 !== i && (f = Object.getOwnPropertyDescriptor(t, n)), 1 === i ? p = {
      get: f.get,
      set: f.set
    } : 2 === i ? p = f.value : 3 === i ? p = f.get : 4 === i && (p = f.set);
    for (var g = a ? 2 : 1, b = m.length - 1; b >= 0; b -= g) {
      var I;
      if (void 0 !== (h = memberDec(m[b], a ? m[b - 1] : void 0, n, f, c, i, s, o, p, l, u))) assertValidReturnValue(i, h), 0 === i ? I = h : 1 === i ? (I = h.init, v = h.get || p.get, y = h.set || p.set, p = {
        get: v,
        set: y
      }) : p = h, void 0 !== I && (void 0 === d ? d = I : "function" == typeof d ? d = [d, I] : d.push(I));
    }
    if (0 === i || 1 === i) {
      if (void 0 === d) d = function (e, t) {
        return t;
      };else if ("function" != typeof d) {
        var w = d;
        d = function (e, t) {
          for (var r = t, a = w.length - 1; a >= 0; a--) r = w[a].call(e, r);
          return r;
        };
      } else {
        var M = d;
        d = function (e, t) {
          return M.call(e, t);
        };
      }
      e.push(d);
    }
    0 !== i && (1 === i ? (f.get = p.get, f.set = p.set) : 2 === i ? f.value = p : 3 === i ? f.get = p : 4 === i && (f.set = p), o ? 1 === i ? (e.push(function (e, t) {
      return p.get.call(e, t);
    }), e.push(function (e, t) {
      return p.set.call(e, t);
    })) : 2 === i ? e.push(p) : e.push(function (e, t) {
      return p.call(e, t);
    }) : Object.defineProperty(t, n, f));
  }
  function applyMemberDecs(e, t, r, a) {
    for (var n, i, s, o = [], c = new Map(), l = new Map(), u = 0; u < t.length; u++) {
      var f = t[u];
      if (Array.isArray(f)) {
        var d,
          p,
          h = f[1],
          v = f[2],
          y = f.length > 3,
          m = 16 & h,
          g = !!(8 & h),
          b = r;
        if (h &= 7, g ? (d = e, 0 !== h && (p = i = i || []), y && !s && (s = function (t) {
          return _checkInRHS(t) === e;
        }), b = s) : (d = e.prototype, 0 !== h && (p = n = n || [])), 0 !== h && !y) {
          var I = g ? l : c,
            w = I.get(v) || 0;
          if (true === w || 3 === w && 4 !== h || 4 === w && 3 !== h) throw new Error("Attempted to decorate a public method/accessor that has the same name as a previously decorated public method/accessor. This is not currently supported by the decorators plugin. Property name was: " + v);
          I.set(v, !(!w && h > 2) || h);
        }
        applyMemberDec(o, d, f, m, v, h, g, y, p, b, a);
      }
    }
    return pushInitializers(o, n), pushInitializers(o, i), o;
  }
  function pushInitializers(e, t) {
    t && e.push(function (e) {
      for (var r = 0; r < t.length; r++) t[r].call(e);
      return e;
    });
  }
  function applyClassDecs(e, t, r, a) {
    if (t.length) {
      for (var n = [], i = e, s = e.name, o = 1, c = t.length - 1; c >= 0; c -= o) {
        var l = {
          v: false
        };
        try {
          var u = t[c].call(r ? t[c - 1] : void 0, i, {
            kind: "class",
            name: s,
            addInitializer: createAddInitializerMethod(n, l),
            metadata: a
          });
        } finally {
          l.v = true;
        }
        void 0 !== u && (assertValidReturnValue(5, u), i = u);
      }
      return [defineMetadata(i, a), function () {
        for (var e = 0; e < n.length; e++) n[e].call(i);
      }];
    }
  }
  function defineMetadata(e, t) {
    return Object.defineProperty(e, Symbol.metadata || Symbol.for("Symbol.metadata"), {
      configurable: true,
      enumerable: true,
      value: t
    });
  }
  function _applyDecs2305(e, t, r, a, n, i) {
    if (arguments.length >= 6) var s = i[Symbol.metadata || Symbol.for("Symbol.metadata")];
    var o = Object.create(void 0 === s ? null : s),
      c = applyMemberDecs(e, t, n, o);
    return r.length || defineMetadata(e, o), {
      e: c,
      get c() {
        return applyClassDecs(e, r, a, o);
      }
    };
  }
  function _checkInRHS(e) {
    if (Object(e) !== e) throw TypeError("right-hand side of 'in' should be an object, got " + (null !== e ? typeof e : "null"));
    return e;
  }

  function AutoBind(originalMethod, {
    name,
    addInitializer
  }) {
    addInitializer(function () {

      this[name] = originalMethod.bind(this);

    });
  }

  function requestNextAnimationFrame(callback) {
    return requestAnimationFrame(() => {
      requestAnimationFrame(callback);
    });
  }

  class DragEvent extends AbstractEvent {

    constructor(data) {
      super(data);
      this.data = data;
    }

    get source() {
      return this.data.source;
    }

    get originalSource() {
      return this.data.originalSource;
    }

    get mirror() {
      return this.data.mirror;
    }

    get sourceContainer() {
      return this.data.sourceContainer;
    }

    get sensorEvent() {
      return this.data.sensorEvent;
    }

    get originalEvent() {
      if (this.sensorEvent) {
        return this.sensorEvent.originalEvent;
      }
      return null;
    }
  }

  DragEvent.type = 'drag';
  class DragStartEvent extends DragEvent {}

  DragStartEvent.type = 'drag:start';
  DragStartEvent.cancelable = true;
  class DragMoveEvent extends DragEvent {}

  DragMoveEvent.type = 'drag:move';

  class DragOverEvent extends DragEvent {

    get overContainer() {
      return this.data.overContainer;
    }

    get over() {
      return this.data.over;
    }
  }
  DragOverEvent.type = 'drag:over';
  DragOverEvent.cancelable = true;
  function isDragOverEvent(event) {
    return event.type === DragOverEvent.type;
  }

  class DragOutEvent extends DragEvent {

    get overContainer() {
      return this.data.overContainer;
    }

    get over() {
      return this.data.over;
    }
  }

  DragOutEvent.type = 'drag:out';

  class DragOverContainerEvent extends DragEvent {

    get overContainer() {
      return this.data.overContainer;
    }
  }

  DragOverContainerEvent.type = 'drag:over:container';

  class DragOutContainerEvent extends DragEvent {

    get overContainer() {
      return this.data.overContainer;
    }
  }

  DragOutContainerEvent.type = 'drag:out:container';

  class DragPressureEvent extends DragEvent {

    get pressure() {
      return this.data.pressure;
    }
  }

  DragPressureEvent.type = 'drag:pressure';
  class DragStopEvent extends DragEvent {}

  DragStopEvent.type = 'drag:stop';
  DragStopEvent.cancelable = true;
  class DragStoppedEvent extends DragEvent {}
  DragStoppedEvent.type = 'drag:stopped';

  var _initProto$1, _class$1;

  const defaultOptions$8 = {};

  class ResizeMirror extends AbstractPlugin {

    constructor(draggable) {
      _initProto$1(super(draggable));

      this.lastWidth = 0;

      this.lastHeight = 0;

      this.mirror = null;
    }

    attach() {
      this.draggable.on('mirror:created', this.onMirrorCreated).on('drag:over', this.onDragOver).on('drag:over:container', this.onDragOver);
    }

    detach() {
      this.draggable.off('mirror:created', this.onMirrorCreated).off('mirror:destroy', this.onMirrorDestroy).off('drag:over', this.onDragOver).off('drag:over:container', this.onDragOver);
    }

    getOptions() {
      return this.draggable.options.resizeMirror || {};
    }

    onMirrorCreated({
      mirror
    }) {
      this.mirror = mirror;
    }

    onMirrorDestroy() {
      this.mirror = null;
    }

    onDragOver(dragEvent) {
      this.resize(dragEvent);
    }

    resize(dragEvent) {
      requestAnimationFrame(() => {
        let over = null;
        const {
          overContainer
        } = dragEvent;
        if (this.mirror == null || this.mirror.parentNode == null) {
          return;
        }
        if (this.mirror.parentNode !== overContainer) {
          overContainer.appendChild(this.mirror);
        }
        if (isDragOverEvent(dragEvent)) {
          over = dragEvent.over;
        }
        const overElement = over || this.draggable.getDraggableElementsForContainer(overContainer)[0];
        if (!overElement) {
          return;
        }
        requestNextAnimationFrame(() => {
          const overRect = overElement.getBoundingClientRect();
          if (this.mirror == null || this.lastHeight === overRect.height && this.lastWidth === overRect.width) {
            return;
          }
          this.mirror.style.width = `${overRect.width}px`;
          this.mirror.style.height = `${overRect.height}px`;
          this.lastWidth = overRect.width;
          this.lastHeight = overRect.height;
        });
      });
    }
  }
  _class$1 = ResizeMirror;
  [_initProto$1] = _applyDecs2305(_class$1, [[AutoBind, 2, "onMirrorCreated"], [AutoBind, 2, "onMirrorDestroy"], [AutoBind, 2, "onDragOver"]], [], 0, void 0, AbstractPlugin).e;

  class SnapEvent extends AbstractEvent {

    get dragEvent() {
      return this.data.dragEvent;
    }

    get snappable() {
      return this.data.snappable;
    }
  }

  SnapEvent.type = 'snap';
  class SnapInEvent extends SnapEvent {}

  SnapInEvent.type = 'snap:in';
  SnapInEvent.cancelable = true;
  class SnapOutEvent extends SnapEvent {}
  SnapOutEvent.type = 'snap:out';
  SnapOutEvent.cancelable = true;

  const onDragStart$6 = Symbol('onDragStart');
  const onDragStop$6 = Symbol('onDragStop');
  const onDragOver$2 = Symbol('onDragOver');
  const onDragOut = Symbol('onDragOut');
  const onMirrorCreated$1 = Symbol('onMirrorCreated');
  const onMirrorDestroy = Symbol('onMirrorDestroy');

  class Snappable extends AbstractPlugin {

    constructor(draggable) {
      super(draggable);

      this.firstSource = null;

      this.mirror = null;
      this[onDragStart$6] = this[onDragStart$6].bind(this);
      this[onDragStop$6] = this[onDragStop$6].bind(this);
      this[onDragOver$2] = this[onDragOver$2].bind(this);
      this[onDragOut] = this[onDragOut].bind(this);
      this[onMirrorCreated$1] = this[onMirrorCreated$1].bind(this);
      this[onMirrorDestroy] = this[onMirrorDestroy].bind(this);
    }

    attach() {
      this.draggable.on('drag:start', this[onDragStart$6]).on('drag:stop', this[onDragStop$6]).on('drag:over', this[onDragOver$2]).on('drag:out', this[onDragOut]).on('droppable:over', this[onDragOver$2]).on('droppable:out', this[onDragOut]).on('mirror:created', this[onMirrorCreated$1]).on('mirror:destroy', this[onMirrorDestroy]);
    }

    detach() {
      this.draggable.off('drag:start', this[onDragStart$6]).off('drag:stop', this[onDragStop$6]).off('drag:over', this[onDragOver$2]).off('drag:out', this[onDragOut]).off('droppable:over', this[onDragOver$2]).off('droppable:out', this[onDragOut]).off('mirror:created', this[onMirrorCreated$1]).off('mirror:destroy', this[onMirrorDestroy]);
    }

    [onDragStart$6](event) {
      if (event.canceled()) {
        return;
      }
      this.firstSource = event.source;
    }

    [onDragStop$6]() {
      this.firstSource = null;
    }

    [onDragOver$2](event) {
      if (event.canceled()) {
        return;
      }
      const source = event.source || event.dragEvent.source;
      if (source === this.firstSource) {
        this.firstSource = null;
        return;
      }
      const snapInEvent = new SnapInEvent({
        dragEvent: event,
        snappable: event.over || event.droppable
      });
      this.draggable.trigger(snapInEvent);
      if (snapInEvent.canceled()) {
        return;
      }
      if (this.mirror) {
        this.mirror.style.display = 'none';
      }
      source.classList.remove(...this.draggable.getClassNamesFor('source:dragging'));
      source.classList.add(...this.draggable.getClassNamesFor('source:placed'));

      setTimeout(() => {
        source.classList.remove(...this.draggable.getClassNamesFor('source:placed'));
      }, this.draggable.options.placedTimeout);
    }

    [onDragOut](event) {
      if (event.canceled()) {
        return;
      }
      const source = event.source || event.dragEvent.source;
      const snapOutEvent = new SnapOutEvent({
        dragEvent: event,
        snappable: event.over || event.droppable
      });
      this.draggable.trigger(snapOutEvent);
      if (snapOutEvent.canceled()) {
        return;
      }
      if (this.mirror) {
        this.mirror.style.display = '';
      }
      source.classList.add(...this.draggable.getClassNamesFor('source:dragging'));
    }

    [onMirrorCreated$1]({
      mirror
    }) {
      this.mirror = mirror;
    }

    [onMirrorDestroy]() {
      this.mirror = null;
    }
  }

  var _initProto, _class;

  const defaultOptions$7 = {
    duration: 150,
    easingFunction: 'ease-in-out',
    horizontal: false
  };

  class SwapAnimation extends AbstractPlugin {

    constructor(draggable) {
      _initProto(super(draggable));

      this.options = {
        ...defaultOptions$7,
        ...this.getOptions()
      };

      this.lastAnimationFrame = null;
    }

    attach() {
      this.draggable.on('sortable:sorted', this.onSortableSorted);
    }

    detach() {
      this.draggable.off('sortable:sorted', this.onSortableSorted);
    }

    getOptions() {
      return this.draggable.options.swapAnimation || {};
    }

    onSortableSorted({
      oldIndex,
      newIndex,
      dragEvent
    }) {
      const {
        source,
        over
      } = dragEvent;
      if (this.lastAnimationFrame) {
        cancelAnimationFrame(this.lastAnimationFrame);
      }

      this.lastAnimationFrame = requestAnimationFrame(() => {
        if (oldIndex >= newIndex) {
          animate$1(source, over, this.options);
        } else {
          animate$1(over, source, this.options);
        }
      });
    }
  }

  _class = SwapAnimation;
  [_initProto] = _applyDecs2305(_class, [[AutoBind, 2, "onSortableSorted"]], [], 0, void 0, AbstractPlugin).e;
  function animate$1(from, to, {
    duration,
    easingFunction,
    horizontal
  }) {
    for (const element of [from, to]) {
      element.style.pointerEvents = 'none';
    }
    if (horizontal) {
      const width = from.offsetWidth;
      from.style.transform = `translate3d(${width}px, 0, 0)`;
      to.style.transform = `translate3d(-${width}px, 0, 0)`;
    } else {
      const height = from.offsetHeight;
      from.style.transform = `translate3d(0, ${height}px, 0)`;
      to.style.transform = `translate3d(0, -${height}px, 0)`;
    }
    requestAnimationFrame(() => {
      for (const element of [from, to]) {
        element.addEventListener('transitionend', resetElementOnTransitionEnd$1);
        element.style.transition = `transform ${duration}ms ${easingFunction}`;
        element.style.transform = '';
      }
    });
  }

  function resetElementOnTransitionEnd$1(event) {
    if (event.target == null || !isHTMLElement(event.target)) {
      return;
    }
    event.target.style.transition = '';
    event.target.style.pointerEvents = '';
    event.target.removeEventListener('transitionend', resetElementOnTransitionEnd$1);
  }
  function isHTMLElement(eventTarget) {
    return Boolean('style' in eventTarget);
  }

  const onSortableSorted = Symbol('onSortableSorted');
  const onSortableSort = Symbol('onSortableSort');

  const defaultOptions$6 = {
    duration: 150,
    easingFunction: 'ease-in-out'
  };

  class SortAnimation extends AbstractPlugin {

    constructor(draggable) {
      super(draggable);

      this.options = {
        ...defaultOptions$6,
        ...this.getOptions()
      };

      this.lastAnimationFrame = null;
      this.lastElements = [];
      this[onSortableSorted] = this[onSortableSorted].bind(this);
      this[onSortableSort] = this[onSortableSort].bind(this);
    }

    attach() {
      this.draggable.on('sortable:sort', this[onSortableSort]);
      this.draggable.on('sortable:sorted', this[onSortableSorted]);
    }

    detach() {
      this.draggable.off('sortable:sort', this[onSortableSort]);
      this.draggable.off('sortable:sorted', this[onSortableSorted]);
    }

    getOptions() {
      return this.draggable.options.sortAnimation || {};
    }

    [onSortableSort]({
      dragEvent
    }) {
      const {
        sourceContainer
      } = dragEvent;
      const elements = this.draggable.getDraggableElementsForContainer(sourceContainer);
      this.lastElements = Array.from(elements).map(el => {
        return {
          domEl: el,
          offsetTop: el.offsetTop,
          offsetLeft: el.offsetLeft
        };
      });
    }

    [onSortableSorted]({
      oldIndex,
      newIndex
    }) {
      if (oldIndex === newIndex) {
        return;
      }
      const effectedElements = [];
      let start;
      let end;
      let num;
      if (oldIndex > newIndex) {
        start = newIndex;
        end = oldIndex - 1;
        num = 1;
      } else {
        start = oldIndex + 1;
        end = newIndex;
        num = -1;
      }
      for (let i = start; i <= end; i++) {
        const from = this.lastElements[i];
        const to = this.lastElements[i + num];
        effectedElements.push({
          from,
          to
        });
      }
      cancelAnimationFrame(this.lastAnimationFrame);

      this.lastAnimationFrame = requestAnimationFrame(() => {
        effectedElements.forEach(element => animate(element, this.options));
      });
    }
  }

  function animate({
    from,
    to
  }, {
    duration,
    easingFunction
  }) {
    const domEl = from.domEl;
    const x = from.offsetLeft - to.offsetLeft;
    const y = from.offsetTop - to.offsetTop;
    domEl.style.pointerEvents = 'none';
    domEl.style.transform = `translate3d(${x}px, ${y}px, 0)`;
    requestAnimationFrame(() => {
      domEl.addEventListener('transitionend', resetElementOnTransitionEnd);
      domEl.style.transition = `transform ${duration}ms ${easingFunction}`;
      domEl.style.transform = '';
    });
  }

  function resetElementOnTransitionEnd(event) {
    event.target.style.transition = '';
    event.target.style.pointerEvents = '';
    event.target.removeEventListener('transitionend', resetElementOnTransitionEnd);
  }

  var index$1 = /*#__PURE__*/Object.freeze({
    __proto__: null,
    Collidable: Collidable,
    ResizeMirror: ResizeMirror,
    Snappable: Snappable,
    SortAnimation: SortAnimation,
    SwapAnimation: SwapAnimation,
    defaultResizeMirrorOptions: defaultOptions$8,
    defaultSortAnimationOptions: defaultOptions$6,
    defaultSwapAnimationOptions: defaultOptions$7
  });

  const onInitialize$1 = Symbol('onInitialize');
  const onDestroy$1 = Symbol('onDestroy');
  const announceEvent = Symbol('announceEvent');
  const announceMessage = Symbol('announceMessage');
  const ARIA_RELEVANT = 'aria-relevant';
  const ARIA_ATOMIC = 'aria-atomic';
  const ARIA_LIVE = 'aria-live';
  const ROLE = 'role';

  const defaultOptions$5 = {
    expire: 7000
  };

  class Announcement extends AbstractPlugin {

    constructor(draggable) {
      super(draggable);

      this.options = {
        ...defaultOptions$5,
        ...this.getOptions()
      };

      this.originalTriggerMethod = this.draggable.trigger;
      this[onInitialize$1] = this[onInitialize$1].bind(this);
      this[onDestroy$1] = this[onDestroy$1].bind(this);
    }

    attach() {
      this.draggable.on('draggable:initialize', this[onInitialize$1]);
    }

    detach() {
      this.draggable.off('draggable:destroy', this[onDestroy$1]);
    }

    getOptions() {
      return this.draggable.options.announcements || {};
    }

    [announceEvent](event) {
      const message = this.options[event.type];
      if (message && typeof message === 'string') {
        this[announceMessage](message);
      }
      if (message && typeof message === 'function') {
        this[announceMessage](message(event));
      }
    }

    [announceMessage](message) {
      announce(message, {
        expire: this.options.expire
      });
    }

    [onInitialize$1]() {

      this.draggable.trigger = event => {
        try {
          this[announceEvent](event);
        } finally {

          this.originalTriggerMethod.call(this.draggable, event);
        }
      };
    }

    [onDestroy$1]() {
      this.draggable.trigger = this.originalTriggerMethod;
    }
  }

  const liveRegion = createRegion();

  function announce(message, {
    expire
  }) {
    const element = document.createElement('div');
    element.textContent = message;
    liveRegion.appendChild(element);
    return setTimeout(() => {
      liveRegion.removeChild(element);
    }, expire);
  }

  function createRegion() {
    const element = document.createElement('div');
    element.setAttribute('id', 'draggable-live-region');
    element.setAttribute(ARIA_RELEVANT, 'additions');
    element.setAttribute(ARIA_ATOMIC, 'true');
    element.setAttribute(ARIA_LIVE, 'assertive');
    element.setAttribute(ROLE, 'log');
    element.style.position = 'fixed';
    element.style.width = '1px';
    element.style.height = '1px';
    element.style.top = '-1px';
    element.style.overflow = 'hidden';
    return element;
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.body.appendChild(liveRegion);
  });

  const onInitialize = Symbol('onInitialize');
  const onDestroy = Symbol('onDestroy');

  const defaultOptions$4 = {};

  class Focusable extends AbstractPlugin {

    constructor(draggable) {
      super(draggable);

      this.options = {
        ...defaultOptions$4,
        ...this.getOptions()
      };
      this[onInitialize] = this[onInitialize].bind(this);
      this[onDestroy] = this[onDestroy].bind(this);
    }

    attach() {
      this.draggable.on('draggable:initialize', this[onInitialize]).on('draggable:destroy', this[onDestroy]);
    }

    detach() {
      this.draggable.off('draggable:initialize', this[onInitialize]).off('draggable:destroy', this[onDestroy]);

      this[onDestroy]();
    }

    getOptions() {
      return this.draggable.options.focusable || {};
    }

    getElements() {
      return [...this.draggable.containers, ...this.draggable.getDraggableElements()];
    }

    [onInitialize]() {

      requestAnimationFrame(() => {
        this.getElements().forEach(element => decorateElement(element));
      });
    }

    [onDestroy]() {

      requestAnimationFrame(() => {
        this.getElements().forEach(element => stripElement(element));
      });
    }
  }

  const elementsWithMissingTabIndex = [];

  function decorateElement(element) {
    const hasMissingTabIndex = Boolean(!element.getAttribute('tabindex') && element.tabIndex === -1);
    if (hasMissingTabIndex) {
      elementsWithMissingTabIndex.push(element);
      element.tabIndex = 0;
    }
  }

  function stripElement(element) {
    const tabIndexElementPosition = elementsWithMissingTabIndex.indexOf(element);
    if (tabIndexElementPosition !== -1) {
      element.tabIndex = -1;
      elementsWithMissingTabIndex.splice(tabIndexElementPosition, 1);
    }
  }

  class MirrorEvent extends AbstractEvent {

    constructor(data) {
      super(data);
      this.data = data;
    }

    get source() {
      return this.data.source;
    }

    get originalSource() {
      return this.data.originalSource;
    }

    get sourceContainer() {
      return this.data.sourceContainer;
    }

    get sensorEvent() {
      return this.data.sensorEvent;
    }

    get dragEvent() {
      return this.data.dragEvent;
    }

    get originalEvent() {
      if (this.sensorEvent) {
        return this.sensorEvent.originalEvent;
      }
      return null;
    }
  }

  class MirrorCreateEvent extends MirrorEvent {}
  MirrorCreateEvent.type = 'mirror:create';

  class MirrorCreatedEvent extends MirrorEvent {

    get mirror() {
      return this.data.mirror;
    }
  }
  MirrorCreatedEvent.type = 'mirror:created';

  class MirrorAttachedEvent extends MirrorEvent {

    get mirror() {
      return this.data.mirror;
    }
  }
  MirrorAttachedEvent.type = 'mirror:attached';

  class MirrorMoveEvent extends MirrorEvent {

    get mirror() {
      return this.data.mirror;
    }

    get passedThreshX() {
      return this.data.passedThreshX;
    }

    get passedThreshY() {
      return this.data.passedThreshY;
    }
  }
  MirrorMoveEvent.type = 'mirror:move';
  MirrorMoveEvent.cancelable = true;

  class MirrorMovedEvent extends MirrorEvent {

    get mirror() {
      return this.data.mirror;
    }

    get passedThreshX() {
      return this.data.passedThreshX;
    }

    get passedThreshY() {
      return this.data.passedThreshY;
    }
  }
  MirrorMovedEvent.type = 'mirror:moved';

  class MirrorDestroyEvent extends MirrorEvent {

    get mirror() {
      return this.data.mirror;
    }
  }
  MirrorDestroyEvent.type = 'mirror:destroy';
  MirrorDestroyEvent.cancelable = true;

  const onDragStart$5 = Symbol('onDragStart');
  const onDragMove$3 = Symbol('onDragMove');
  const onDragStop$5 = Symbol('onDragStop');
  const onMirrorCreated = Symbol('onMirrorCreated');
  const onMirrorMove = Symbol('onMirrorMove');
  const onScroll = Symbol('onScroll');
  const getAppendableContainer = Symbol('getAppendableContainer');

  const defaultOptions$3 = {
    constrainDimensions: false,
    xAxis: true,
    yAxis: true,
    cursorOffsetX: null,
    cursorOffsetY: null,
    thresholdX: null,
    thresholdY: null
  };

  class Mirror extends AbstractPlugin {

    constructor(draggable) {
      super(draggable);

      this.options = {
        ...defaultOptions$3,
        ...this.getOptions()
      };

      this.scrollOffset = {
        x: 0,
        y: 0
      };

      this.initialScrollOffset = {
        x: window.scrollX,
        y: window.scrollY
      };
      this[onDragStart$5] = this[onDragStart$5].bind(this);
      this[onDragMove$3] = this[onDragMove$3].bind(this);
      this[onDragStop$5] = this[onDragStop$5].bind(this);
      this[onMirrorCreated] = this[onMirrorCreated].bind(this);
      this[onMirrorMove] = this[onMirrorMove].bind(this);
      this[onScroll] = this[onScroll].bind(this);
    }

    attach() {
      this.draggable.on('drag:start', this[onDragStart$5]).on('drag:move', this[onDragMove$3]).on('drag:stop', this[onDragStop$5]).on('mirror:created', this[onMirrorCreated]).on('mirror:move', this[onMirrorMove]);
    }

    detach() {
      this.draggable.off('drag:start', this[onDragStart$5]).off('drag:move', this[onDragMove$3]).off('drag:stop', this[onDragStop$5]).off('mirror:created', this[onMirrorCreated]).off('mirror:move', this[onMirrorMove]);
    }

    getOptions() {
      return this.draggable.options.mirror || {};
    }
    [onDragStart$5](dragEvent) {
      if (dragEvent.canceled()) {
        return;
      }
      if ('ontouchstart' in window) {
        document.addEventListener('scroll', this[onScroll], true);
      }
      this.initialScrollOffset = {
        x: window.scrollX,
        y: window.scrollY
      };
      const {
        source,
        originalSource,
        sourceContainer,
        sensorEvent
      } = dragEvent;

      this.lastMirrorMovedClient = {
        x: sensorEvent.clientX,
        y: sensorEvent.clientY
      };
      const mirrorCreateEvent = new MirrorCreateEvent({
        source,
        originalSource,
        sourceContainer,
        sensorEvent,
        dragEvent
      });
      this.draggable.trigger(mirrorCreateEvent);
      if (isNativeDragEvent(sensorEvent) || mirrorCreateEvent.canceled()) {
        return;
      }
      const appendableContainer = this[getAppendableContainer](source) || sourceContainer;
      this.mirror = source.cloneNode(true);
      const mirrorCreatedEvent = new MirrorCreatedEvent({
        source,
        originalSource,
        sourceContainer,
        sensorEvent,
        dragEvent,
        mirror: this.mirror
      });
      const mirrorAttachedEvent = new MirrorAttachedEvent({
        source,
        originalSource,
        sourceContainer,
        sensorEvent,
        dragEvent,
        mirror: this.mirror
      });
      this.draggable.trigger(mirrorCreatedEvent);
      appendableContainer.appendChild(this.mirror);
      this.draggable.trigger(mirrorAttachedEvent);
    }
    [onDragMove$3](dragEvent) {
      if (!this.mirror || dragEvent.canceled()) {
        return;
      }
      const {
        source,
        originalSource,
        sourceContainer,
        sensorEvent
      } = dragEvent;
      let passedThreshX = true;
      let passedThreshY = true;
      if (this.options.thresholdX || this.options.thresholdY) {
        const {
          x: lastX,
          y: lastY
        } = this.lastMirrorMovedClient;
        if (Math.abs(lastX - sensorEvent.clientX) < this.options.thresholdX) {
          passedThreshX = false;
        } else {
          this.lastMirrorMovedClient.x = sensorEvent.clientX;
        }
        if (Math.abs(lastY - sensorEvent.clientY) < this.options.thresholdY) {
          passedThreshY = false;
        } else {
          this.lastMirrorMovedClient.y = sensorEvent.clientY;
        }
        if (!passedThreshX && !passedThreshY) {
          return;
        }
      }
      const mirrorMoveEvent = new MirrorMoveEvent({
        source,
        originalSource,
        sourceContainer,
        sensorEvent,
        dragEvent,
        mirror: this.mirror,
        passedThreshX,
        passedThreshY
      });
      this.draggable.trigger(mirrorMoveEvent);
    }
    [onDragStop$5](dragEvent) {
      if ('ontouchstart' in window) {
        document.removeEventListener('scroll', this[onScroll], true);
      }
      this.initialScrollOffset = {
        x: 0,
        y: 0
      };
      this.scrollOffset = {
        x: 0,
        y: 0
      };
      if (!this.mirror) {
        return;
      }
      const {
        source,
        sourceContainer,
        sensorEvent
      } = dragEvent;
      const mirrorDestroyEvent = new MirrorDestroyEvent({
        source,
        mirror: this.mirror,
        sourceContainer,
        sensorEvent,
        dragEvent
      });
      this.draggable.trigger(mirrorDestroyEvent);
      if (!mirrorDestroyEvent.canceled()) {
        this.mirror.remove();
      }
    }
    [onScroll]() {
      this.scrollOffset = {
        x: window.scrollX - this.initialScrollOffset.x,
        y: window.scrollY - this.initialScrollOffset.y
      };
    }

    [onMirrorCreated]({
      mirror,
      source,
      sensorEvent
    }) {
      const mirrorClasses = this.draggable.getClassNamesFor('mirror');
      const setState = ({
        mirrorOffset,
        initialX,
        initialY,
        ...args
      }) => {
        this.mirrorOffset = mirrorOffset;
        this.initialX = initialX;
        this.initialY = initialY;
        this.lastMovedX = initialX;
        this.lastMovedY = initialY;
        return {
          mirrorOffset,
          initialX,
          initialY,
          ...args
        };
      };
      mirror.style.display = 'none';
      const initialState = {
        mirror,
        source,
        sensorEvent,
        mirrorClasses,
        scrollOffset: this.scrollOffset,
        options: this.options,
        passedThreshX: true,
        passedThreshY: true
      };
      return Promise.resolve(initialState)

      .then(computeMirrorDimensions).then(calculateMirrorOffset).then(resetMirror).then(addMirrorClasses).then(positionMirror({
        initial: true
      })).then(removeMirrorID).then(setState);
    }

    [onMirrorMove](mirrorEvent) {
      if (mirrorEvent.canceled()) {
        return null;
      }
      const setState = ({
        lastMovedX,
        lastMovedY,
        ...args
      }) => {
        this.lastMovedX = lastMovedX;
        this.lastMovedY = lastMovedY;
        return {
          lastMovedX,
          lastMovedY,
          ...args
        };
      };
      const triggerMoved = args => {
        const mirrorMovedEvent = new MirrorMovedEvent({
          source: mirrorEvent.source,
          originalSource: mirrorEvent.originalSource,
          sourceContainer: mirrorEvent.sourceContainer,
          sensorEvent: mirrorEvent.sensorEvent,
          dragEvent: mirrorEvent.dragEvent,
          mirror: this.mirror,
          passedThreshX: mirrorEvent.passedThreshX,
          passedThreshY: mirrorEvent.passedThreshY
        });
        this.draggable.trigger(mirrorMovedEvent);
        return args;
      };
      const initialState = {
        mirror: mirrorEvent.mirror,
        sensorEvent: mirrorEvent.sensorEvent,
        mirrorOffset: this.mirrorOffset,
        options: this.options,
        initialX: this.initialX,
        initialY: this.initialY,
        scrollOffset: this.scrollOffset,
        passedThreshX: mirrorEvent.passedThreshX,
        passedThreshY: mirrorEvent.passedThreshY,
        lastMovedX: this.lastMovedX,
        lastMovedY: this.lastMovedY
      };
      return Promise.resolve(initialState).then(positionMirror({
        })).then(setState).then(triggerMoved);
    }

    [getAppendableContainer](source) {
      const appendTo = this.options.appendTo;
      if (typeof appendTo === 'string') {
        return document.querySelector(appendTo);
      } else if (appendTo instanceof HTMLElement) {
        return appendTo;
      } else if (typeof appendTo === 'function') {
        return appendTo(source);
      } else {
        return source.parentNode;
      }
    }
  }

  function computeMirrorDimensions({
    source,
    ...args
  }) {
    return withPromise(resolve => {
      const sourceRect = source.getBoundingClientRect();
      resolve({
        source,
        sourceRect,
        ...args
      });
    });
  }

  function calculateMirrorOffset({
    sensorEvent,
    sourceRect,
    options,
    ...args
  }) {
    return withPromise(resolve => {
      const top = options.cursorOffsetY === null ? sensorEvent.clientY - sourceRect.top : options.cursorOffsetY;
      const left = options.cursorOffsetX === null ? sensorEvent.clientX - sourceRect.left : options.cursorOffsetX;
      const mirrorOffset = {
        top,
        left
      };
      resolve({
        sensorEvent,
        sourceRect,
        mirrorOffset,
        options,
        ...args
      });
    });
  }

  function resetMirror({
    mirror,
    source,
    options,
    ...args
  }) {
    return withPromise(resolve => {
      let offsetHeight;
      let offsetWidth;
      if (options.constrainDimensions) {
        const computedSourceStyles = getComputedStyle(source);
        offsetHeight = computedSourceStyles.getPropertyValue('height');
        offsetWidth = computedSourceStyles.getPropertyValue('width');
      }
      mirror.style.display = null;
      mirror.style.position = 'fixed';
      mirror.style.pointerEvents = 'none';
      mirror.style.top = 0;
      mirror.style.left = 0;
      mirror.style.margin = 0;
      if (options.constrainDimensions) {
        mirror.style.height = offsetHeight;
        mirror.style.width = offsetWidth;
      }
      resolve({
        mirror,
        source,
        options,
        ...args
      });
    });
  }

  function addMirrorClasses({
    mirror,
    mirrorClasses,
    ...args
  }) {
    return withPromise(resolve => {
      mirror.classList.add(...mirrorClasses);
      resolve({
        mirror,
        mirrorClasses,
        ...args
      });
    });
  }

  function removeMirrorID({
    mirror,
    ...args
  }) {
    return withPromise(resolve => {
      mirror.removeAttribute('id');
      delete mirror.id;
      resolve({
        mirror,
        ...args
      });
    });
  }

  function positionMirror({
    withFrame = false,
    initial = false
  } = {}) {
    return ({
      mirror,
      sensorEvent,
      mirrorOffset,
      initialY,
      initialX,
      scrollOffset,
      options,
      passedThreshX,
      passedThreshY,
      lastMovedX,
      lastMovedY,
      ...args
    }) => {
      return withPromise(resolve => {
        const result = {
          mirror,
          sensorEvent,
          mirrorOffset,
          options,
          ...args
        };
        if (mirrorOffset) {
          const x = passedThreshX ? Math.round((sensorEvent.clientX - mirrorOffset.left - scrollOffset.x) / (options.thresholdX || 1)) * (options.thresholdX || 1) : Math.round(lastMovedX);
          const y = passedThreshY ? Math.round((sensorEvent.clientY - mirrorOffset.top - scrollOffset.y) / (options.thresholdY || 1)) * (options.thresholdY || 1) : Math.round(lastMovedY);
          if (options.xAxis && options.yAxis || initial) {
            mirror.style.transform = `translate3d(${x}px, ${y}px, 0)`;
          } else if (options.xAxis && !options.yAxis) {
            mirror.style.transform = `translate3d(${x}px, ${initialY}px, 0)`;
          } else if (options.yAxis && !options.xAxis) {
            mirror.style.transform = `translate3d(${initialX}px, ${y}px, 0)`;
          }
          if (initial) {
            result.initialX = x;
            result.initialY = y;
          }
          result.lastMovedX = x;
          result.lastMovedY = y;
        }
        resolve(result);
      }, {
        });
    };
  }

  function withPromise(callback, {
    raf = false
  } = {}) {
    return new Promise((resolve, reject) => {
      if (raf) {
        requestAnimationFrame(() => {
          callback(resolve, reject);
        });
      } else {
        callback(resolve, reject);
      }
    });
  }

  function isNativeDragEvent(sensorEvent) {
    return /^drag/.test(sensorEvent.originalEvent.type);
  }

  const onDragStart$4 = Symbol('onDragStart');
  const onDragMove$2 = Symbol('onDragMove');
  const onDragStop$4 = Symbol('onDragStop');
  const scroll = Symbol('scroll');

  const defaultOptions$2 = {
    speed: 6,
    sensitivity: 50,
    scrollableElements: []
  };

  class Scrollable extends AbstractPlugin {

    constructor(draggable) {
      super(draggable);

      this.options = {
        ...defaultOptions$2,
        ...this.getOptions()
      };

      this.currentMousePosition = null;

      this.scrollAnimationFrame = null;

      this.scrollableElement = null;

      this.findScrollableElementFrame = null;
      this[onDragStart$4] = this[onDragStart$4].bind(this);
      this[onDragMove$2] = this[onDragMove$2].bind(this);
      this[onDragStop$4] = this[onDragStop$4].bind(this);
      this[scroll] = this[scroll].bind(this);
    }

    attach() {
      this.draggable.on('drag:start', this[onDragStart$4]).on('drag:move', this[onDragMove$2]).on('drag:stop', this[onDragStop$4]);
    }

    detach() {
      this.draggable.off('drag:start', this[onDragStart$4]).off('drag:move', this[onDragMove$2]).off('drag:stop', this[onDragStop$4]);
    }

    getOptions() {
      return this.draggable.options.scrollable || {};
    }

    getScrollableElement(target) {
      if (this.hasDefinedScrollableElements()) {
        return closest(target, this.options.scrollableElements) || document.documentElement;
      } else {
        return closestScrollableElement(target);
      }
    }

    hasDefinedScrollableElements() {
      return Boolean(this.options.scrollableElements.length !== 0);
    }

    [onDragStart$4](dragEvent) {
      this.findScrollableElementFrame = requestAnimationFrame(() => {
        this.scrollableElement = this.getScrollableElement(dragEvent.source);
      });
    }

    [onDragMove$2](dragEvent) {
      this.findScrollableElementFrame = requestAnimationFrame(() => {
        this.scrollableElement = this.getScrollableElement(dragEvent.sensorEvent.target);
      });
      if (!this.scrollableElement) {
        return;
      }
      const sensorEvent = dragEvent.sensorEvent;
      const scrollOffset = {
        x: 0,
        y: 0
      };
      if ('ontouchstart' in window) {
        scrollOffset.y = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
        scrollOffset.x = window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft || 0;
      }
      this.currentMousePosition = {
        clientX: sensorEvent.clientX - scrollOffset.x,
        clientY: sensorEvent.clientY - scrollOffset.y
      };
      this.scrollAnimationFrame = requestAnimationFrame(this[scroll]);
    }

    [onDragStop$4]() {
      cancelAnimationFrame(this.scrollAnimationFrame);
      cancelAnimationFrame(this.findScrollableElementFrame);
      this.scrollableElement = null;
      this.scrollAnimationFrame = null;
      this.findScrollableElementFrame = null;
      this.currentMousePosition = null;
    }

    [scroll]() {
      if (!this.scrollableElement || !this.currentMousePosition) {
        return;
      }
      cancelAnimationFrame(this.scrollAnimationFrame);
      const {
        speed,
        sensitivity
      } = this.options;
      const rect = this.scrollableElement.getBoundingClientRect();
      const bottomCutOff = rect.bottom > window.innerHeight;
      const topCutOff = rect.top < 0;
      const cutOff = topCutOff || bottomCutOff;
      const documentScrollingElement = getDocumentScrollingElement();
      const scrollableElement = this.scrollableElement;
      const clientX = this.currentMousePosition.clientX;
      const clientY = this.currentMousePosition.clientY;
      if (scrollableElement !== document.body && scrollableElement !== document.documentElement && !cutOff) {
        const {
          offsetHeight,
          offsetWidth
        } = scrollableElement;
        if (rect.top + offsetHeight - clientY < sensitivity) {
          scrollableElement.scrollTop += speed;
        } else if (clientY - rect.top < sensitivity) {
          scrollableElement.scrollTop -= speed;
        }
        if (rect.left + offsetWidth - clientX < sensitivity) {
          scrollableElement.scrollLeft += speed;
        } else if (clientX - rect.left < sensitivity) {
          scrollableElement.scrollLeft -= speed;
        }
      } else {
        const {
          innerHeight,
          innerWidth
        } = window;
        if (clientY < sensitivity) {
          documentScrollingElement.scrollTop -= speed;
        } else if (innerHeight - clientY < sensitivity) {
          documentScrollingElement.scrollTop += speed;
        }
        if (clientX < sensitivity) {
          documentScrollingElement.scrollLeft -= speed;
        } else if (innerWidth - clientX < sensitivity) {
          documentScrollingElement.scrollLeft += speed;
        }
      }
      this.scrollAnimationFrame = requestAnimationFrame(this[scroll]);
    }
  }

  function hasOverflow(element) {
    const overflowRegex = /(auto|scroll)/;
    const computedStyles = getComputedStyle(element, null);
    const overflow = computedStyles.getPropertyValue('overflow') + computedStyles.getPropertyValue('overflow-y') + computedStyles.getPropertyValue('overflow-x');
    return overflowRegex.test(overflow);
  }

  function isStaticallyPositioned(element) {
    const position = getComputedStyle(element).getPropertyValue('position');
    return position === 'static';
  }

  function closestScrollableElement(element) {
    if (!element) {
      return getDocumentScrollingElement();
    }
    const position = getComputedStyle(element).getPropertyValue('position');
    const excludeStaticParents = position === 'absolute';
    const scrollableElement = closest(element, parent => {
      if (excludeStaticParents && isStaticallyPositioned(parent)) {
        return false;
      }
      return hasOverflow(parent);
    });
    if (position === 'fixed' || !scrollableElement) {
      return getDocumentScrollingElement();
    } else {
      return scrollableElement;
    }
  }

  function getDocumentScrollingElement() {
    return document.scrollingElement || document.documentElement;
  }

  class Emitter {
    constructor() {
      this.callbacks = {};
    }

    on(type, ...callbacks) {
      if (!this.callbacks[type]) {
        this.callbacks[type] = [];
      }
      this.callbacks[type].push(...callbacks);
      return this;
    }

    off(type, callback) {
      if (!this.callbacks[type]) {
        return null;
      }
      const copy = this.callbacks[type].slice(0);
      for (let i = 0; i < copy.length; i++) {
        if (callback === copy[i]) {
          this.callbacks[type].splice(i, 1);
        }
      }
      return this;
    }

    trigger(event) {
      if (!this.callbacks[event.type]) {
        return null;
      }
      const callbacks = [...this.callbacks[event.type]];
      const caughtErrors = [];
      for (let i = callbacks.length - 1; i >= 0; i--) {
        const callback = callbacks[i];
        try {
          callback(event);
        } catch (error) {
          caughtErrors.push(error);
        }
      }
      if (caughtErrors.length) {

        console.error(`Draggable caught errors while triggering '${event.type}'`, caughtErrors);

      }

      return this;
    }
  }

  class DraggableEvent extends AbstractEvent {

    get draggable() {
      return this.data.draggable;
    }
  }

  DraggableEvent.type = 'draggable';
  class DraggableInitializedEvent extends DraggableEvent {}

  DraggableInitializedEvent.type = 'draggable:initialize';
  class DraggableDestroyEvent extends DraggableEvent {}
  DraggableDestroyEvent.type = 'draggable:destroy';

  const onDragStart$3 = Symbol('onDragStart');
  const onDragMove$1 = Symbol('onDragMove');
  const onDragStop$3 = Symbol('onDragStop');
  const onDragPressure = Symbol('onDragPressure');
  const dragStop = Symbol('dragStop');

  const defaultAnnouncements$3 = {
    'drag:start': event => `Picked up ${event.source.textContent.trim() || event.source.id || 'draggable element'}`,
    'drag:stop': event => `Released ${event.source.textContent.trim() || event.source.id || 'draggable element'}`
  };
  const defaultClasses$1 = {
    'container:dragging': 'draggable-container--is-dragging',
    'source:dragging': 'draggable-source--is-dragging',
    'source:placed': 'draggable-source--placed',
    'container:placed': 'draggable-container--placed',
    'body:dragging': 'draggable--is-dragging',
    'draggable:over': 'draggable--over',
    'container:over': 'draggable-container--over',
    'source:original': 'draggable--original',
    mirror: 'draggable-mirror'
  };
  const defaultOptions$1 = {
    draggable: '.draggable-source',
    handle: null,
    delay: {},
    distance: 0,
    placedTimeout: 800,
    plugins: [],
    sensors: [],
    exclude: {
      plugins: [],
      sensors: []
    }
  };

  class Draggable {

    constructor(containers = [document.body], options = {}) {

      if (containers instanceof NodeList || containers instanceof Array) {
        this.containers = [...containers];
      } else if (containers instanceof HTMLElement) {
        this.containers = [containers];
      } else {
        throw new Error('Draggable containers are expected to be of type `NodeList`, `HTMLElement[]` or `HTMLElement`');
      }
      this.options = {
        ...defaultOptions$1,
        ...options,
        classes: {
          ...defaultClasses$1,
          ...(options.classes || {})
        },
        announcements: {
          ...defaultAnnouncements$3,
          ...(options.announcements || {})
        },
        exclude: {
          plugins: options.exclude && options.exclude.plugins || [],
          sensors: options.exclude && options.exclude.sensors || []
        }
      };

      this.emitter = new Emitter();

      this.dragging = false;

      this.plugins = [];

      this.sensors = [];
      this[onDragStart$3] = this[onDragStart$3].bind(this);
      this[onDragMove$1] = this[onDragMove$1].bind(this);
      this[onDragStop$3] = this[onDragStop$3].bind(this);
      this[onDragPressure] = this[onDragPressure].bind(this);
      this[dragStop] = this[dragStop].bind(this);
      document.addEventListener('drag:start', this[onDragStart$3], true);
      document.addEventListener('drag:move', this[onDragMove$1], true);
      document.addEventListener('drag:stop', this[onDragStop$3], true);
      document.addEventListener('drag:pressure', this[onDragPressure], true);
      const defaultPlugins = Object.values(Draggable.Plugins).filter(Plugin => !this.options.exclude.plugins.includes(Plugin));
      const defaultSensors = Object.values(Draggable.Sensors).filter(sensor => !this.options.exclude.sensors.includes(sensor));
      this.addPlugin(...[...defaultPlugins, ...this.options.plugins]);
      this.addSensor(...[...defaultSensors, ...this.options.sensors]);
      const draggableInitializedEvent = new DraggableInitializedEvent({
        draggable: this
      });
      this.on('mirror:created', ({
        mirror
      }) => this.mirror = mirror);
      this.on('mirror:destroy', () => this.mirror = null);
      this.trigger(draggableInitializedEvent);
    }

    destroy() {
      document.removeEventListener('drag:start', this[onDragStart$3], true);
      document.removeEventListener('drag:move', this[onDragMove$1], true);
      document.removeEventListener('drag:stop', this[onDragStop$3], true);
      document.removeEventListener('drag:pressure', this[onDragPressure], true);
      const draggableDestroyEvent = new DraggableDestroyEvent({
        draggable: this
      });
      this.trigger(draggableDestroyEvent);
      this.removePlugin(...this.plugins.map(plugin => plugin.constructor));
      this.removeSensor(...this.sensors.map(sensor => sensor.constructor));
    }

    addPlugin(...plugins) {
      const activePlugins = plugins.map(Plugin => new Plugin(this));
      activePlugins.forEach(plugin => plugin.attach());
      this.plugins = [...this.plugins, ...activePlugins];
      return this;
    }

    removePlugin(...plugins) {
      const removedPlugins = this.plugins.filter(plugin => plugins.includes(plugin.constructor));
      removedPlugins.forEach(plugin => plugin.detach());
      this.plugins = this.plugins.filter(plugin => !plugins.includes(plugin.constructor));
      return this;
    }

    addSensor(...sensors) {
      const activeSensors = sensors.map(Sensor => new Sensor(this.containers, this.options));
      activeSensors.forEach(sensor => sensor.attach());
      this.sensors = [...this.sensors, ...activeSensors];
      return this;
    }

    removeSensor(...sensors) {
      const removedSensors = this.sensors.filter(sensor => sensors.includes(sensor.constructor));
      removedSensors.forEach(sensor => sensor.detach());
      this.sensors = this.sensors.filter(sensor => !sensors.includes(sensor.constructor));
      return this;
    }

    addContainer(...containers) {
      this.containers = [...this.containers, ...containers];
      this.sensors.forEach(sensor => sensor.addContainer(...containers));
      return this;
    }

    removeContainer(...containers) {
      this.containers = this.containers.filter(container => !containers.includes(container));
      this.sensors.forEach(sensor => sensor.removeContainer(...containers));
      return this;
    }

    on(type, ...callbacks) {
      this.emitter.on(type, ...callbacks);
      return this;
    }

    off(type, callback) {
      this.emitter.off(type, callback);
      return this;
    }

    trigger(event) {
      this.emitter.trigger(event);
      return this;
    }

    getClassNameFor(name) {
      return this.getClassNamesFor(name)[0];
    }

    getClassNamesFor(name) {
      const classNames = this.options.classes[name];
      if (classNames instanceof Array) {
        return classNames;
      } else if (typeof classNames === 'string' || classNames instanceof String) {
        return [classNames];
      } else {
        return [];
      }
    }

    isDragging() {
      return Boolean(this.dragging);
    }

    getDraggableElements() {
      return this.containers.reduce((current, container) => {
        return [...current, ...this.getDraggableElementsForContainer(container)];
      }, []);
    }

    getDraggableElementsForContainer(container) {
      const allDraggableElements = container.querySelectorAll(this.options.draggable);
      return [...allDraggableElements].filter(childElement => {
        return childElement !== this.originalSource && childElement !== this.mirror;
      });
    }

    cancel() {
      this[dragStop]();
    }

    [onDragStart$3](event) {
      const sensorEvent = getSensorEvent(event);
      const {
        target,
        container,
        originalSource
      } = sensorEvent;
      if (!this.containers.includes(container)) {
        return;
      }
      if (this.options.handle && target && !closest(target, this.options.handle)) {
        sensorEvent.cancel();
        return;
      }
      this.originalSource = originalSource;
      this.sourceContainer = container;
      if (this.lastPlacedSource && this.lastPlacedContainer) {
        clearTimeout(this.placedTimeoutID);
        this.lastPlacedSource.classList.remove(...this.getClassNamesFor('source:placed'));
        this.lastPlacedContainer.classList.remove(...this.getClassNamesFor('container:placed'));
      }
      this.source = this.originalSource.cloneNode(true);
      this.originalSource.parentNode.insertBefore(this.source, this.originalSource);
      this.originalSource.style.display = 'none';
      const dragStartEvent = new DragStartEvent({
        source: this.source,
        originalSource: this.originalSource,
        sourceContainer: container,
        sensorEvent
      });
      this.trigger(dragStartEvent);
      this.dragging = !dragStartEvent.canceled();
      if (dragStartEvent.canceled()) {
        this.source.remove();
        this.originalSource.style.display = null;
        return;
      }
      this.originalSource.classList.add(...this.getClassNamesFor('source:original'));
      this.source.classList.add(...this.getClassNamesFor('source:dragging'));
      this.sourceContainer.classList.add(...this.getClassNamesFor('container:dragging'));
      document.body.classList.add(...this.getClassNamesFor('body:dragging'));
      applyUserSelect(document.body, 'none');
      requestAnimationFrame(() => {
        const oldSensorEvent = getSensorEvent(event);
        const newSensorEvent = oldSensorEvent.clone({
          target: this.source
        });
        this[onDragMove$1]({
          ...event,
          detail: newSensorEvent
        });
      });
    }

    [onDragMove$1](event) {
      if (!this.dragging) {
        return;
      }
      const sensorEvent = getSensorEvent(event);
      const {
        container
      } = sensorEvent;
      let target = sensorEvent.target;
      const dragMoveEvent = new DragMoveEvent({
        source: this.source,
        originalSource: this.originalSource,
        sourceContainer: container,
        sensorEvent
      });
      this.trigger(dragMoveEvent);
      if (dragMoveEvent.canceled()) {
        sensorEvent.cancel();
      }
      target = closest(target, this.options.draggable);
      const withinCorrectContainer = closest(sensorEvent.target, this.containers);
      const overContainer = sensorEvent.overContainer || withinCorrectContainer;
      const isLeavingContainer = this.currentOverContainer && overContainer !== this.currentOverContainer;
      const isLeavingDraggable = this.currentOver && target !== this.currentOver;
      const isOverContainer = overContainer && this.currentOverContainer !== overContainer;
      const isOverDraggable = withinCorrectContainer && target && this.currentOver !== target;
      if (isLeavingDraggable) {
        const dragOutEvent = new DragOutEvent({
          source: this.source,
          originalSource: this.originalSource,
          sourceContainer: container,
          sensorEvent,
          over: this.currentOver,
          overContainer: this.currentOverContainer
        });
        this.currentOver.classList.remove(...this.getClassNamesFor('draggable:over'));
        this.currentOver = null;
        this.trigger(dragOutEvent);
      }
      if (isLeavingContainer) {
        const dragOutContainerEvent = new DragOutContainerEvent({
          source: this.source,
          originalSource: this.originalSource,
          sourceContainer: container,
          sensorEvent,
          overContainer: this.currentOverContainer
        });
        this.currentOverContainer.classList.remove(...this.getClassNamesFor('container:over'));
        this.currentOverContainer = null;
        this.trigger(dragOutContainerEvent);
      }
      if (isOverContainer) {
        overContainer.classList.add(...this.getClassNamesFor('container:over'));
        const dragOverContainerEvent = new DragOverContainerEvent({
          source: this.source,
          originalSource: this.originalSource,
          sourceContainer: container,
          sensorEvent,
          overContainer
        });
        this.currentOverContainer = overContainer;
        this.trigger(dragOverContainerEvent);
      }
      if (isOverDraggable) {
        target.classList.add(...this.getClassNamesFor('draggable:over'));
        const dragOverEvent = new DragOverEvent({
          source: this.source,
          originalSource: this.originalSource,
          sourceContainer: container,
          sensorEvent,
          overContainer,
          over: target
        });
        this.currentOver = target;
        this.trigger(dragOverEvent);
      }
    }

    [dragStop](event) {
      if (!this.dragging) {
        return;
      }
      this.dragging = false;
      const dragStopEvent = new DragStopEvent({
        source: this.source,
        originalSource: this.originalSource,
        sensorEvent: event ? event.sensorEvent : null,
        sourceContainer: this.sourceContainer
      });
      this.trigger(dragStopEvent);
      if (!dragStopEvent.canceled()) this.source.parentNode.insertBefore(this.originalSource, this.source);
      this.source.remove();
      this.originalSource.style.display = '';
      this.source.classList.remove(...this.getClassNamesFor('source:dragging'));
      this.originalSource.classList.remove(...this.getClassNamesFor('source:original'));
      this.originalSource.classList.add(...this.getClassNamesFor('source:placed'));
      this.sourceContainer.classList.add(...this.getClassNamesFor('container:placed'));
      this.sourceContainer.classList.remove(...this.getClassNamesFor('container:dragging'));
      document.body.classList.remove(...this.getClassNamesFor('body:dragging'));
      applyUserSelect(document.body, '');
      if (this.currentOver) {
        this.currentOver.classList.remove(...this.getClassNamesFor('draggable:over'));
      }
      if (this.currentOverContainer) {
        this.currentOverContainer.classList.remove(...this.getClassNamesFor('container:over'));
      }
      this.lastPlacedSource = this.originalSource;
      this.lastPlacedContainer = this.sourceContainer;
      this.placedTimeoutID = setTimeout(() => {
        if (this.lastPlacedSource) {
          this.lastPlacedSource.classList.remove(...this.getClassNamesFor('source:placed'));
        }
        if (this.lastPlacedContainer) {
          this.lastPlacedContainer.classList.remove(...this.getClassNamesFor('container:placed'));
        }
        this.lastPlacedSource = null;
        this.lastPlacedContainer = null;
      }, this.options.placedTimeout);
      const dragStoppedEvent = new DragStoppedEvent({
        source: this.source,
        originalSource: this.originalSource,
        sensorEvent: event ? event.sensorEvent : null,
        sourceContainer: this.sourceContainer
      });
      this.trigger(dragStoppedEvent);
      this.source = null;
      this.originalSource = null;
      this.currentOverContainer = null;
      this.currentOver = null;
      this.sourceContainer = null;
    }

    [onDragStop$3](event) {
      this[dragStop](event);
    }

    [onDragPressure](event) {
      if (!this.dragging) {
        return;
      }
      const sensorEvent = getSensorEvent(event);
      const source = this.source || closest(sensorEvent.originalEvent.target, this.options.draggable);
      const dragPressureEvent = new DragPressureEvent({
        sensorEvent,
        source,
        pressure: sensorEvent.pressure
      });
      this.trigger(dragPressureEvent);
    }
  }

  Draggable.Plugins = {
    Announcement,
    Focusable,
    Mirror,
    Scrollable
  };

  Draggable.Sensors = {
    MouseSensor,
    TouchSensor
  };
  function getSensorEvent(event) {
    return event.detail;
  }
  function applyUserSelect(element, value) {
    element.style.webkitUserSelect = value;
    element.style.mozUserSelect = value;
    element.style.msUserSelect = value;
    element.style.oUserSelect = value;
    element.style.userSelect = value;
  }

  class DroppableEvent extends AbstractEvent {

    constructor(data) {
      super(data);
      this.data = data;
    }

    get dragEvent() {
      return this.data.dragEvent;
    }
  }
  DroppableEvent.type = 'droppable';

  class DroppableStartEvent extends DroppableEvent {

    get dropzone() {
      return this.data.dropzone;
    }
  }
  DroppableStartEvent.type = 'droppable:start';
  DroppableStartEvent.cancelable = true;

  class DroppableDroppedEvent extends DroppableEvent {

    get dropzone() {
      return this.data.dropzone;
    }
  }
  DroppableDroppedEvent.type = 'droppable:dropped';
  DroppableDroppedEvent.cancelable = true;

  class DroppableReturnedEvent extends DroppableEvent {

    get dropzone() {
      return this.data.dropzone;
    }
  }
  DroppableReturnedEvent.type = 'droppable:returned';
  DroppableReturnedEvent.cancelable = true;

  class DroppableStopEvent extends DroppableEvent {

    get dropzone() {
      return this.data.dropzone;
    }
  }
  DroppableStopEvent.type = 'droppable:stop';
  DroppableStopEvent.cancelable = true;

  const onDragStart$2 = Symbol('onDragStart');
  const onDragMove = Symbol('onDragMove');
  const onDragStop$2 = Symbol('onDragStop');
  const dropInDropzone = Symbol('dropInDropZone');
  const returnToOriginalDropzone = Symbol('returnToOriginalDropzone');
  const closestDropzone = Symbol('closestDropzone');
  const getDropzones = Symbol('getDropzones');

  function onDroppableDroppedDefaultAnnouncement({
    dragEvent,
    dropzone
  }) {
    const sourceText = dragEvent.source.textContent.trim() || dragEvent.source.id || 'draggable element';
    const dropzoneText = dropzone.textContent.trim() || dropzone.id || 'droppable element';
    return `Dropped ${sourceText} into ${dropzoneText}`;
  }

  function onDroppableReturnedDefaultAnnouncement({
    dragEvent,
    dropzone
  }) {
    const sourceText = dragEvent.source.textContent.trim() || dragEvent.source.id || 'draggable element';
    const dropzoneText = dropzone.textContent.trim() || dropzone.id || 'droppable element';
    return `Returned ${sourceText} from ${dropzoneText}`;
  }

  const defaultAnnouncements$2 = {
    'droppable:dropped': onDroppableDroppedDefaultAnnouncement,
    'droppable:returned': onDroppableReturnedDefaultAnnouncement
  };
  const defaultClasses = {
    'droppable:active': 'draggable-dropzone--active',
    'droppable:occupied': 'draggable-dropzone--occupied'
  };
  const defaultOptions = {
    dropzone: '.draggable-droppable'
  };

  class Droppable extends Draggable {

    constructor(containers = [], options = {}) {
      super(containers, {
        ...defaultOptions,
        ...options,
        classes: {
          ...defaultClasses,
          ...(options.classes || {})
        },
        announcements: {
          ...defaultAnnouncements$2,
          ...(options.announcements || {})
        }
      });

      this.dropzones = null;

      this.lastDropzone = null;

      this.initialDropzone = null;
      this[onDragStart$2] = this[onDragStart$2].bind(this);
      this[onDragMove] = this[onDragMove].bind(this);
      this[onDragStop$2] = this[onDragStop$2].bind(this);
      this.on('drag:start', this[onDragStart$2]).on('drag:move', this[onDragMove]).on('drag:stop', this[onDragStop$2]);
    }

    destroy() {
      super.destroy();
      this.off('drag:start', this[onDragStart$2]).off('drag:move', this[onDragMove]).off('drag:stop', this[onDragStop$2]);
    }

    [onDragStart$2](event) {
      if (event.canceled()) {
        return;
      }
      this.dropzones = [...this[getDropzones]()];
      const dropzone = closest(event.sensorEvent.target, this.options.dropzone);
      if (!dropzone) {
        event.cancel();
        return;
      }
      const droppableStartEvent = new DroppableStartEvent({
        dragEvent: event,
        dropzone
      });
      this.trigger(droppableStartEvent);
      if (droppableStartEvent.canceled()) {
        event.cancel();
        return;
      }
      this.initialDropzone = dropzone;
      for (const dropzoneElement of this.dropzones) {
        if (dropzoneElement.classList.contains(this.getClassNameFor('droppable:occupied'))) {
          continue;
        }
        dropzoneElement.classList.add(...this.getClassNamesFor('droppable:active'));
      }
    }

    [onDragMove](event) {
      if (event.canceled()) {
        return;
      }
      const dropzone = this[closestDropzone](event.sensorEvent.target);
      const overEmptyDropzone = dropzone && !dropzone.classList.contains(this.getClassNameFor('droppable:occupied'));
      if (overEmptyDropzone && this[dropInDropzone](event, dropzone)) {
        this.lastDropzone = dropzone;
      } else if ((!dropzone || dropzone === this.initialDropzone) && this.lastDropzone) {
        this[returnToOriginalDropzone](event);
        this.lastDropzone = null;
      }
    }

    [onDragStop$2](event) {
      const droppableStopEvent = new DroppableStopEvent({
        dragEvent: event,
        dropzone: this.lastDropzone || this.initialDropzone
      });
      this.trigger(droppableStopEvent);
      const occupiedClasses = this.getClassNamesFor('droppable:occupied');
      for (const dropzone of this.dropzones) {
        dropzone.classList.remove(...this.getClassNamesFor('droppable:active'));
      }
      if (this.lastDropzone && this.lastDropzone !== this.initialDropzone) {
        this.initialDropzone.classList.remove(...occupiedClasses);
      }
      this.dropzones = null;
      this.lastDropzone = null;
      this.initialDropzone = null;
    }

    [dropInDropzone](event, dropzone) {
      const droppableDroppedEvent = new DroppableDroppedEvent({
        dragEvent: event,
        dropzone
      });
      this.trigger(droppableDroppedEvent);
      if (droppableDroppedEvent.canceled()) {
        return false;
      }
      const occupiedClasses = this.getClassNamesFor('droppable:occupied');
      if (this.lastDropzone) {
        this.lastDropzone.classList.remove(...occupiedClasses);
      }
      dropzone.appendChild(event.source);
      dropzone.classList.add(...occupiedClasses);
      return true;
    }

    [returnToOriginalDropzone](event) {
      const droppableReturnedEvent = new DroppableReturnedEvent({
        dragEvent: event,
        dropzone: this.lastDropzone
      });
      this.trigger(droppableReturnedEvent);
      if (droppableReturnedEvent.canceled()) {
        return;
      }
      this.initialDropzone.appendChild(event.source);
      this.lastDropzone.classList.remove(...this.getClassNamesFor('droppable:occupied'));
    }

    [closestDropzone](target) {
      if (!this.dropzones) {
        return null;
      }
      return closest(target, this.dropzones);
    }

    [getDropzones]() {
      const dropzone = this.options.dropzone;
      if (typeof dropzone === 'string') {
        return document.querySelectorAll(dropzone);
      } else if (dropzone instanceof NodeList || dropzone instanceof Array) {
        return dropzone;
      } else if (typeof dropzone === 'function') {
        return dropzone();
      } else {
        return [];
      }
    }
  }

  class SwappableEvent extends AbstractEvent {

    constructor(data) {
      super(data);
      this.data = data;
    }

    get dragEvent() {
      return this.data.dragEvent;
    }
  }

  SwappableEvent.type = 'swappable';
  class SwappableStartEvent extends SwappableEvent {}
  SwappableStartEvent.type = 'swappable:start';
  SwappableStartEvent.cancelable = true;

  class SwappableSwapEvent extends SwappableEvent {

    get over() {
      return this.data.over;
    }

    get overContainer() {
      return this.data.overContainer;
    }
  }
  SwappableSwapEvent.type = 'swappable:swap';
  SwappableSwapEvent.cancelable = true;

  class SwappableSwappedEvent extends SwappableEvent {

    get swappedElement() {
      return this.data.swappedElement;
    }
  }

  SwappableSwappedEvent.type = 'swappable:swapped';
  class SwappableStopEvent extends SwappableEvent {}
  SwappableStopEvent.type = 'swappable:stop';

  const onDragStart$1 = Symbol('onDragStart');
  const onDragOver$1 = Symbol('onDragOver');
  const onDragStop$1 = Symbol('onDragStop');

  function onSwappableSwappedDefaultAnnouncement({
    dragEvent,
    swappedElement
  }) {
    const sourceText = dragEvent.source.textContent.trim() || dragEvent.source.id || 'swappable element';
    const overText = swappedElement.textContent.trim() || swappedElement.id || 'swappable element';
    return `Swapped ${sourceText} with ${overText}`;
  }

  const defaultAnnouncements$1 = {
    'swappabled:swapped': onSwappableSwappedDefaultAnnouncement
  };

  class Swappable extends Draggable {

    constructor(containers = [], options = {}) {
      super(containers, {
        ...options,
        announcements: {
          ...defaultAnnouncements$1,
          ...(options.announcements || {})
        }
      });

      this.lastOver = null;
      this[onDragStart$1] = this[onDragStart$1].bind(this);
      this[onDragOver$1] = this[onDragOver$1].bind(this);
      this[onDragStop$1] = this[onDragStop$1].bind(this);
      this.on('drag:start', this[onDragStart$1]).on('drag:over', this[onDragOver$1]).on('drag:stop', this[onDragStop$1]);
    }

    destroy() {
      super.destroy();
      this.off('drag:start', this._onDragStart).off('drag:over', this._onDragOver).off('drag:stop', this._onDragStop);
    }

    [onDragStart$1](event) {
      const swappableStartEvent = new SwappableStartEvent({
        dragEvent: event
      });
      this.trigger(swappableStartEvent);
      if (swappableStartEvent.canceled()) {
        event.cancel();
      }
    }

    [onDragOver$1](event) {
      if (event.over === event.originalSource || event.over === event.source || event.canceled()) {
        return;
      }
      const swappableSwapEvent = new SwappableSwapEvent({
        dragEvent: event,
        over: event.over,
        overContainer: event.overContainer
      });
      this.trigger(swappableSwapEvent);
      if (swappableSwapEvent.canceled()) {
        return;
      }

      if (this.lastOver && this.lastOver !== event.over) {
        swap(this.lastOver, event.source);
      }
      if (this.lastOver === event.over) {
        this.lastOver = null;
      } else {
        this.lastOver = event.over;
      }
      swap(event.source, event.over);
      const swappableSwappedEvent = new SwappableSwappedEvent({
        dragEvent: event,
        swappedElement: event.over
      });
      this.trigger(swappableSwappedEvent);
    }

    [onDragStop$1](event) {
      const swappableStopEvent = new SwappableStopEvent({
        dragEvent: event
      });
      this.trigger(swappableStopEvent);
      this.lastOver = null;
    }
  }
  function withTempElement(callback) {
    const tmpElement = document.createElement('div');
    callback(tmpElement);
    tmpElement.remove();
  }
  function swap(source, over) {
    const overParent = over.parentNode;
    const sourceParent = source.parentNode;
    withTempElement(tmpElement => {
      sourceParent.insertBefore(tmpElement, source);
      overParent.insertBefore(source, over);
      sourceParent.insertBefore(over, tmpElement);
    });
  }

  class SortableEvent extends AbstractEvent {

    constructor(data) {
      super(data);
      this.data = data;
    }

    get dragEvent() {
      return this.data.dragEvent;
    }
  }
  SortableEvent.type = 'sortable';

  class SortableStartEvent extends SortableEvent {

    get startIndex() {
      return this.data.startIndex;
    }

    get startContainer() {
      return this.data.startContainer;
    }
  }
  SortableStartEvent.type = 'sortable:start';
  SortableStartEvent.cancelable = true;

  class SortableSortEvent extends SortableEvent {

    get currentIndex() {
      return this.data.currentIndex;
    }

    get over() {
      return this.data.over;
    }

    get overContainer() {
      return this.data.dragEvent.overContainer;
    }
  }
  SortableSortEvent.type = 'sortable:sort';
  SortableSortEvent.cancelable = true;

  class SortableSortedEvent extends SortableEvent {

    get oldIndex() {
      return this.data.oldIndex;
    }

    get newIndex() {
      return this.data.newIndex;
    }

    get oldContainer() {
      return this.data.oldContainer;
    }

    get newContainer() {
      return this.data.newContainer;
    }
  }
  SortableSortedEvent.type = 'sortable:sorted';

  class SortableStopEvent extends SortableEvent {

    get oldIndex() {
      return this.data.oldIndex;
    }

    get newIndex() {
      return this.data.newIndex;
    }

    get oldContainer() {
      return this.data.oldContainer;
    }

    get newContainer() {
      return this.data.newContainer;
    }
  }
  SortableStopEvent.type = 'sortable:stop';

  const onDragStart = Symbol('onDragStart');
  const onDragOverContainer = Symbol('onDragOverContainer');
  const onDragOver = Symbol('onDragOver');
  const onDragStop = Symbol('onDragStop');

  function onSortableSortedDefaultAnnouncement({
    dragEvent
  }) {
    const sourceText = dragEvent.source.textContent.trim() || dragEvent.source.id || 'sortable element';
    if (dragEvent.over) {
      const overText = dragEvent.over.textContent.trim() || dragEvent.over.id || 'sortable element';
      const isFollowing = dragEvent.source.compareDocumentPosition(dragEvent.over) & Node.DOCUMENT_POSITION_FOLLOWING;
      if (isFollowing) {
        return `Placed ${sourceText} after ${overText}`;
      } else {
        return `Placed ${sourceText} before ${overText}`;
      }
    } else {

      return `Placed ${sourceText} into a different container`;
    }
  }

  const defaultAnnouncements = {
    'sortable:sorted': onSortableSortedDefaultAnnouncement
  };

  class Sortable extends Draggable {

    constructor(containers = [], options = {}) {
      super(containers, {
        ...options,
        announcements: {
          ...defaultAnnouncements,
          ...(options.announcements || {})
        }
      });

      this.startIndex = null;

      this.startContainer = null;
      this[onDragStart] = this[onDragStart].bind(this);
      this[onDragOverContainer] = this[onDragOverContainer].bind(this);
      this[onDragOver] = this[onDragOver].bind(this);
      this[onDragStop] = this[onDragStop].bind(this);
      this.on('drag:start', this[onDragStart]).on('drag:over:container', this[onDragOverContainer]).on('drag:over', this[onDragOver]).on('drag:stop', this[onDragStop]);
    }

    destroy() {
      super.destroy();
      this.off('drag:start', this[onDragStart]).off('drag:over:container', this[onDragOverContainer]).off('drag:over', this[onDragOver]).off('drag:stop', this[onDragStop]);
    }

    index(element) {
      return this.getSortableElementsForContainer(element.parentNode).indexOf(element);
    }

    getSortableElementsForContainer(container) {
      const allSortableElements = container.querySelectorAll(this.options.draggable);
      return [...allSortableElements].filter(childElement => {
        return childElement !== this.originalSource && childElement !== this.mirror && childElement.parentNode === container;
      });
    }

    [onDragStart](event) {
      this.startContainer = event.source.parentNode;
      this.startIndex = this.index(event.source);
      const sortableStartEvent = new SortableStartEvent({
        dragEvent: event,
        startIndex: this.startIndex,
        startContainer: this.startContainer
      });
      this.trigger(sortableStartEvent);
      if (sortableStartEvent.canceled()) {
        event.cancel();
      }
    }

    [onDragOverContainer](event) {
      if (event.canceled()) {
        return;
      }
      const {
        source,
        over,
        overContainer
      } = event;
      const oldIndex = this.index(source);
      const sortableSortEvent = new SortableSortEvent({
        dragEvent: event,
        currentIndex: oldIndex,
        source,
        over
      });
      this.trigger(sortableSortEvent);
      if (sortableSortEvent.canceled()) {
        return;
      }
      const children = this.getSortableElementsForContainer(overContainer);
      const moves = move({
        source,
        over,
        overContainer,
        children
      });
      if (!moves) {
        return;
      }
      const {
        oldContainer,
        newContainer
      } = moves;
      const newIndex = this.index(event.source);
      const sortableSortedEvent = new SortableSortedEvent({
        dragEvent: event,
        oldIndex,
        newIndex,
        oldContainer,
        newContainer
      });
      this.trigger(sortableSortedEvent);
    }

    [onDragOver](event) {
      if (event.over === event.originalSource || event.over === event.source) {
        return;
      }
      const {
        source,
        over,
        overContainer
      } = event;
      const oldIndex = this.index(source);
      const sortableSortEvent = new SortableSortEvent({
        dragEvent: event,
        currentIndex: oldIndex,
        source,
        over
      });
      this.trigger(sortableSortEvent);
      if (sortableSortEvent.canceled()) {
        return;
      }
      const children = this.getDraggableElementsForContainer(overContainer);
      const moves = move({
        source,
        over,
        overContainer,
        children
      });
      if (!moves) {
        return;
      }
      const {
        oldContainer,
        newContainer
      } = moves;
      const newIndex = this.index(source);
      const sortableSortedEvent = new SortableSortedEvent({
        dragEvent: event,
        oldIndex,
        newIndex,
        oldContainer,
        newContainer
      });
      this.trigger(sortableSortedEvent);
    }

    [onDragStop](event) {
      const sortableStopEvent = new SortableStopEvent({
        dragEvent: event,
        oldIndex: this.startIndex,
        newIndex: this.index(event.source),
        oldContainer: this.startContainer,
        newContainer: event.source.parentNode
      });
      this.trigger(sortableStopEvent);
      this.startIndex = null;
      this.startContainer = null;
    }
  }
  function index(element) {
    return Array.prototype.indexOf.call(element.parentNode.children, element);
  }
  function move({
    source,
    over,
    overContainer,
    children
  }) {
    const emptyOverContainer = !children.length;
    const differentContainer = source.parentNode !== overContainer;
    const sameContainer = over && source.parentNode === over.parentNode;
    if (emptyOverContainer) {
      return moveInsideEmptyContainer(source, overContainer);
    } else if (sameContainer) {
      return moveWithinContainer(source, over);
    } else if (differentContainer) {
      return moveOutsideContainer(source, over, overContainer);
    } else {
      return null;
    }
  }
  function moveInsideEmptyContainer(source, overContainer) {
    const oldContainer = source.parentNode;
    overContainer.appendChild(source);
    return {
      oldContainer,
      newContainer: overContainer
    };
  }
  function moveWithinContainer(source, over) {
    const oldIndex = index(source);
    const newIndex = index(over);
    if (oldIndex < newIndex) {
      source.parentNode.insertBefore(source, over.nextElementSibling);
    } else {
      source.parentNode.insertBefore(source, over);
    }
    return {
      oldContainer: source.parentNode,
      newContainer: source.parentNode
    };
  }
  function moveOutsideContainer(source, over, overContainer) {
    const oldContainer = source.parentNode;
    if (over) {
      over.parentNode.insertBefore(source, over);
    } else {

      overContainer.appendChild(source);
    }
    return {
      oldContainer,
      newContainer: source.parentNode
    };
  }

  exports.BaseEvent = AbstractEvent;
  exports.BasePlugin = AbstractPlugin;
  exports.Draggable = Draggable;
  exports.Droppable = Droppable;
  exports.Plugins = index$1;
  exports.Sensors = index$2;
  exports.Sortable = Sortable;
  exports.Swappable = Swappable;

}));

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

var EspoFunnel = {};
window.EspoFunnel = EspoFunnel;
(function (EspoFunnel) {
  EspoFunnel.Funnel = /*#__PURE__*/function () {
    function Funnel(container, params, dataList) {
      _classCallCheck(this, Funnel);

      this.params = Object.assign({}, params || {});
      this.dataList = dataList || [];
      this.element = document.createElementNS('http://www.w3.org/2000/svg', 'svg');

      if (container) {
        container.appendChild(this.element);
      }

      this.trapElementList = [];
      this.element.setAttribute('width', '100%');
      this.element.setAttribute('height', '100%');
      var defaultParams = {
        padding: 10,
        colors: ['#b770e0', '#63a7c2', '#c96947', '#ccc058', '#ab414a'],
        outlineColor: 'red',
        strokeWidth: 1,
        gapWidth: 0.01,
        events: {},
        showTooltip: true,
        tooltipClassName: '',
        tooltipStyleString: 'display:block;position:absolute;white-space:nowrap;',
        callbacks: {}
      };

      for (var param in defaultParams) {
        if (param in this.params) {
          continue;
        }

        this.params[param] = defaultParams[param];
      }

      this.draw();
    }

    _createClass(Funnel, [{
      key: "getMaxValue",
      value: function getMaxValue() {
        var maxValue = 0;

        for (var item of this.dataList) {
          if (item.value > maxValue) {
            maxValue = item.value;
          }
        }

        return maxValue;
      }
    }, {
      key: "draw",
      value: function draw() {
        var cWidth = this.element.getBoundingClientRect().width;
        var cHeight = this.element.getBoundingClientRect().height;
        var padding = this.params.padding;
        var gapWidth = cHeight * this.params.gapWidth;
        var count = this.dataList.length;
        this.cWidth = cWidth;
        var maxValue = this.getMaxValue();
        var centerX = this.centerX = cWidth / 2;
        var top = padding;
        var bottom = height - padding;
        var width = cWidth - 2 * padding;
        var height = cHeight - 2 * padding;
        var itemHeight = (height - gapWidth * count) / count;
        var ratio = this.ratio = width / maxValue;
        this.trapElementList.forEach(function (element) {
          this.element.removeChild(element);
        }, this);
        this.trapElementList = [];
        this.positionList = [];
        this.dataList.forEach(function (item, i) {
          var value = item.value;
          var halfTopWidth = value / 2 * ratio;
          var iTop = top + itemHeight * i + gapWidth * i;
          var iBottom = iTop + itemHeight;

          if (i === this.dataList.length - 1) {
            var nextValue = value;
          } else {
            var nextItem = this.dataList[i + 1];
            var nextValue = nextItem.value;
          }

          var halfBottomWidth = nextValue / 2 * ratio;
          this.positionList.push([iTop, iBottom, centerX, halfTopWidth, halfBottomWidth]);
          var trapElement = this.drawTrapElement(iTop, iBottom, centerX, halfTopWidth, halfBottomWidth, this.getItemColor(i));
          this.trapElementList.push(trapElement);
          this.registerMouseEvents(trapElement, i);
          this.element.appendChild(trapElement);
        }, this);
      }
    }, {
      key: "getItemColor",
      value: function getItemColor(i) {
        var color = this.params.colors[i] || '#AAA';
        return color;
      }
    }, {
      key: "drawTrapElement",
      value: function drawTrapElement(iTop, iBottom, centerX, halfTopWidth, halfBottomWidth, color) {
        var element = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        var d = 'M' + (centerX + halfTopWidth) + ',' + iTop + ' ';
        d += 'L' + (centerX - halfTopWidth) + ',' + iTop + ' ';
        d += 'L' + (centerX - halfBottomWidth) + ',' + iBottom + ' ';
        d += 'L' + (centerX + halfBottomWidth) + ',' + iBottom + ' ';
        d += 'L' + (centerX + halfTopWidth) + ',' + iTop + '';
        element.setAttribute('d', d);
        element.setAttribute('fill', color);
        element.setAttribute('stroke-width', this.params.strokeWidth);
        element.setAttribute('stroke', color);
        return element;
      }
    }, {
      key: "showTooltip",
      value: function showTooltip(index) {
        var style = this.params.tooltipStyleString;
        var tooltipClassName = this.params.tooltipClassName;
        var element = document.createElement('div');
        element.setAttribute('style', style);
        element.setAttribute('class', tooltipClassName);
        var c = this.params.callbacks.tooltipHtml;

        if (!c) {
          return;
        }

        var html = c(index);
        var pos = this.positionList[index];
        var left = pos[2] + pos[3];
        var top = pos[0];
        var toLeft = pos[3] > this.cWidth / 5;
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop;
        var scrollLeft = window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft;
        var cLeft = scrollLeft + this.element.getBoundingClientRect().left;
        var cTop = scrollTop + this.element.getBoundingClientRect().top;
        left += cLeft;
        top += cTop;
        element.innerHTML = html;
        element.style.top = top + 'px';
        element.style.left = left + 'px';
        element.style.pointerEvents = 'none';

        if (toLeft) {
          element.style.transform = 'translate(-100%, 0)';
        }

        this.tooltipElement = element;
        document.body.appendChild(element);
      }
    }, {
      key: "hideTooltip",
      value: function hideTooltip(index) {
        document.body.removeChild(this.tooltipElement);
      }
    }, {
      key: "outlineItem",
      value: function outlineItem(index) {
        var element = this.trapElementList[index];

        if (!element) {
          return;
        }

        element.setAttribute('stroke', this.params.outlineColor);
      }
    }, {
      key: "cancelOutlineItem",
      value: function cancelOutlineItem(index) {
        var element = this.trapElementList[index];

        if (!element) {
          return;
        }

        element.setAttribute('stroke', this.getItemColor(index));
      }
    }, {
      key: "registerMouseEvents",
      value: function registerMouseEvents(element, i) {
        element.onclick = function (e) {
          if (e.which === 1) {
            this.triggerEvent('leftClick', {
              index: i,
              originalEvent: e
            });
          } else if (e.which === 2) {
            this.triggerEvent('rightClick', {
              index: i,
              originalEvent: e
            });
          }
        }.bind(this);

        element.onmouseover = function (e) {
          this.outlineItem(i);
          if (this.params.showTooltip) this.showTooltip(i);
        }.bind(this);

        element.onmouseout = function (e) {
          this.cancelOutlineItem(i);
          if (this.params.showTooltip) this.hideTooltip(i);
        }.bind(this);
      }
    }, {
      key: "triggerEvent",
      value: function triggerEvent(name, o) {
        var c = this.params.events[name];

        if (!c) {
          return;
        }

        c.call(this, o);
      }
    }]);

    return Funnel;
  }();
}).call(this, EspoFunnel);


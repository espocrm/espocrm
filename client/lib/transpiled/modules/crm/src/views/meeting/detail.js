define("modules/crm/views/meeting/detail", ["exports", "views/detail", "moment"], function (_exports, _detail, _moment) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _detail = _interopRequireDefault(_detail);
  _moment = _interopRequireDefault(_moment);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class MeetingDetailView extends _detail.default {
    cancellationPeriod = '8 hours';
    setup() {
      super.setup();
      this.setupStatuses();
      this.addMenuItem('buttons', {
        name: 'sendInvitations',
        text: this.translate('Send Invitations', 'labels', 'Meeting'),
        acl: 'edit',
        hidden: true,
        onClick: () => this.actionSendInvitations()
      });
      this.addMenuItem('dropdown', {
        name: 'sendCancellation',
        text: this.translate('Send Cancellation', 'labels', 'Meeting'),
        acl: 'edit',
        hidden: true,
        onClick: () => this.actionSendCancellation()
      });
      this.addMenuItem('buttons', {
        name: 'setAcceptanceStatus',
        text: '',
        hidden: true,
        onClick: () => this.actionSetAcceptanceStatus()
      });
      this.setupCancellationPeriod();
      this.controlSendInvitationsButton();
      this.controlAcceptanceStatusButton();
      this.controlSendCancellationButton();
      this.listenTo(this.model, 'sync', () => {
        this.controlSendInvitationsButton();
        this.controlSendCancellationButton();
      });
      this.listenTo(this.model, 'sync', () => this.controlAcceptanceStatusButton());
    }
    setupStatuses() {
      this.canceledStatusList = this.getMetadata().get(`scopes.${this.entityType}.canceledStatusList`) || [];
      this.notActualStatusList = [...(this.getMetadata().get(`scopes.${this.entityType}.completedStatusList`) || []), ...this.canceledStatusList];
    }
    setupCancellationPeriod() {
      this.cancellationPeriodAmount = 0;
      this.cancellationPeriodUnits = 'hours';
      const cancellationPeriod = this.getConfig().get('eventCancellationPeriod') || this.cancellationPeriod;
      if (!cancellationPeriod) {
        return;
      }
      const arr = cancellationPeriod.split(' ');
      this.cancellationPeriodAmount = parseInt(arr[0]);
      this.cancellationPeriodUnits = arr[1] ?? 'hours';
    }
    controlAcceptanceStatusButton() {
      if (!this.model.has('status')) {
        return;
      }
      if (!this.model.has('usersIds')) {
        return;
      }
      if (this.notActualStatusList.includes(this.model.get('status'))) {
        this.hideHeaderActionItem('setAcceptanceStatus');
        return;
      }
      if (!this.model.getLinkMultipleIdList('users').includes(this.getUser().id)) {
        this.hideHeaderActionItem('setAcceptanceStatus');
        return;
      }
      const acceptanceStatus = this.model.getLinkMultipleColumn('users', 'status', this.getUser().id);
      let text;
      let style = 'default';
      if (acceptanceStatus && acceptanceStatus !== 'None') {
        text = this.getLanguage().translateOption(acceptanceStatus, 'acceptanceStatus', this.model.entityType);
        style = this.getMetadata().get(['entityDefs', this.model.entityType, 'fields', 'acceptanceStatus', 'style', acceptanceStatus]);
      } else {
        text = this.translate('Acceptance', 'labels', 'Meeting');
      }
      let iconHtml = '';
      if (style) {
        const iconClass = {
          'success': 'fas fa-check-circle',
          'danger': 'fas fa-times-circle',
          'warning': 'fas fa-question-circle'
        }[style];
        iconHtml = $('<span>').addClass(iconClass).addClass('text-' + style).get(0).outerHTML;
      }
      this.updateMenuItem('setAcceptanceStatus', {
        text: text,
        iconHtml: iconHtml,
        hidden: false
      });
    }
    controlSendInvitationsButton() {
      let show = true;
      if (this.notActualStatusList.includes(this.model.get('status'))) {
        show = false;
      }
      if (show && !this.getAcl().checkModel(this.model, 'edit')) {
        show = false;
      }
      if (show) {
        const userIdList = this.model.getLinkMultipleIdList('users');
        const contactIdList = this.model.getLinkMultipleIdList('contacts');
        const leadIdList = this.model.getLinkMultipleIdList('leads');
        if (!contactIdList.length && !leadIdList.length && !userIdList.length) {
          show = false;
        }
        /*else if (
            !contactIdList.length &&
            !leadIdList.length &&
            userIdList.length === 1 &&
            userIdList[0] === this.getUser().id &&
            this.model.getLinkMultipleColumn('users', 'status', this.getUser().id) === 'Accepted'
        ) {
            show = false;
        }*/
      }
      if (show) {
        const dateEnd = this.model.get('dateEnd');
        if (dateEnd && this.getDateTime().toMoment(dateEnd).isBefore(_moment.default.now())) {
          show = false;
        }
      }
      show ? this.showHeaderActionItem('sendInvitations') : this.hideHeaderActionItem('sendInvitations');
    }
    controlSendCancellationButton() {
      let show = this.canceledStatusList.includes(this.model.get('status'));
      if (show) {
        const dateEnd = this.model.get('dateEnd');
        if (dateEnd && this.getDateTime().toMoment(dateEnd).add(this.cancellationPeriodAmount, this.cancellationPeriodUnits).isBefore(_moment.default.now())) {
          show = false;
        }
      }
      if (show) {
        const userIdList = this.model.getLinkMultipleIdList('users');
        const contactIdList = this.model.getLinkMultipleIdList('contacts');
        const leadIdList = this.model.getLinkMultipleIdList('leads');
        if (!contactIdList.length && !leadIdList.length && !userIdList.length) {
          show = false;
        }
      }
      show ? this.showHeaderActionItem('sendCancellation') : this.hideHeaderActionItem('sendCancellation');
    }
    actionSendInvitations() {
      Espo.Ui.notifyWait();
      this.createView('dialog', 'crm:views/meeting/modals/send-invitations', {
        model: this.model
      }).then(view => {
        Espo.Ui.notify(false);
        view.render();
        this.listenToOnce(view, 'sent', () => this.model.fetch());
      });
    }
    actionSendCancellation() {
      Espo.Ui.notifyWait();
      this.createView('dialog', 'crm:views/meeting/modals/send-cancellation', {
        model: this.model
      }).then(view => {
        Espo.Ui.notify(false);
        view.render();
        this.listenToOnce(view, 'sent', () => this.model.fetch());
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionSetAcceptanceStatus() {
      this.createView('dialog', 'crm:views/meeting/modals/acceptance-status', {
        model: this.model
      }, view => {
        view.render();
        this.listenTo(view, 'set-status', status => {
          this.disableMenuItem('setAcceptanceStatus');
          Espo.Ui.notifyWait();
          Espo.Ajax.postRequest(this.model.entityType + '/action/setAcceptanceStatus', {
            id: this.model.id,
            status: status
          }).then(() => {
            this.model.fetch().then(() => {
              Espo.Ui.notify(false);
              this.enableMenuItem('setAcceptanceStatus');
            });
          }).catch(() => this.enableMenuItem('setAcceptanceStatus'));
        });
      });
    }
  }
  var _default = _exports.default = MeetingDetailView;
});
//# sourceMappingURL=detail.js.map ;
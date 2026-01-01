define("views/scheduled-job/fields/job", ["exports", "views/fields/enum"], function (_exports, _enum) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _enum = _interopRequireDefault(_enum);
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

  class _default extends _enum.default {
    setup() {
      super.setup();
      if (this.isEditMode() || this.isDetailMode()) {
        this.wait(true);
        Espo.Ajax.getRequest('Admin/jobs').then(data => {
          this.params.options = data.filter(item => {
            return !this.getMetadata().get(['entityDefs', 'ScheduledJob', 'jobs', item, 'isSystem']);
          });
          this.params.options.unshift('');
          this.wait(false);
        });
      }
      if (this.model.isNew()) {
        this.on('change', () => {
          const job = this.model.get('job');
          if (job) {
            const label = this.getLanguage().translateOption(job, 'job', 'ScheduledJob');
            const scheduling = this.getMetadata().get(`entityDefs.ScheduledJob.jobSchedulingMap.${job}`) || '*/10 * * * *';
            this.model.set('name', label);
            this.model.set('scheduling', scheduling);
            return;
          }
          this.model.set('name', '');
          this.model.set('scheduling', '');
        });
      }
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=job.js.map ;
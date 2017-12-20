<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

return array(
    "INSERT INTO `job` (`id`, `name`, `deleted`, `status`, `execute_time`, `service_name`, `method`, `data`, `attempts`, `failed_attempts`, `created_at`, `modified_at`, `scheduled_job_id`, `target_id`, `target_type`) VALUES ('". uniqid() ."', 'Dummy', '0', 'Pending', '" . gmdate('Y-m-d H:i:s') . "', NULL, 'Dummy', NULL, '3', NULL, '" . gmdate('Y-m-d H:i:s') . "', '" . gmdate('Y-m-d H:i:s') . "', (SELECT id FROM scheduled_job WHERE deleted = 0 AND job = 'Dummy'), NULL, NULL);"
);
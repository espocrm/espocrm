<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

return [
    'apiPath' => '/api/v1',
    'rewriteRules' => [
        'APACHE1' => 'sudo a2enmod rewrite
sudo service apache2 restart',
        'APACHE2' => '&#60;Directory /PATH_TO_ESPO/&#62;
 AllowOverride <b>All</b>
&#60;/Directory&#62;',
        'APACHE2_PATH1' => '/etc/apache2/sites-available/ESPO_VIRTUAL_HOST.conf',
        'APACHE2_PATH2' => '/etc/apache2/apache2.conf',
        'APACHE2_PATH3' => '/etc/httpd/conf/httpd.conf',
        'APACHE3' => 'sudo service apache2 restart',
        'APACHE4' => '# RewriteBase /',
        'APACHE5' => 'RewriteBase {ESPO_PATH}{API_PATH}',
        'WINDOWS_APACHE1' => 'LoadModule rewrite_module modules/mod_rewrite.so',
        'NGINX_PATH' => '/etc/nginx/sites-available/YOUR_SITE',
        'NGINX' => 'server {
    # ...

    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /api/v1/ {
        if (!-e $request_filename){
            rewrite ^/api/v1/(.*)$ /api/v1/index.php last; break;
        }
    }

    location /portal/ {
        try_files $uri $uri/ /portal/index.php?$query_string;
    }

    location /api/v1/portal-access {
        if (!-e $request_filename){
            rewrite ^/api/v1/(.*)$ /api/v1/portal-access/index.php last; break;
        }
    }

    location ~ /reset/?$ {
        try_files /reset.html =404;
    }

    location ^~ (api|client)/ {
        if (-e $request_filename){
            return 403;
        }
    }
    location ^~ /data/ {
        deny all;
    }
    location ^~ /application/ {
        deny all;
    }
    location ^~ /custom/ {
        deny all;
    }
    location ^~ /vendor/ {
        deny all;
    }
    location ~ /\.ht {
        deny all;
    }
}',
    'APACHE_LINK' => 'https://www.espocrm.com/documentation/administration/apache-server-configuration/',
    'NGINX_LINK' => 'https://www.espocrm.com/documentation/administration/nginx-server-configuration/',
    ],
];

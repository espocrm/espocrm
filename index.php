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

$path = getcwd();

echo <<<EOL

<link href="client/css/espo/hazyblue-vertical.css" rel="stylesheet" id='main-stylesheet'>

<body style="padding: 20px 10px 60px 10px; max-width: 900px; margin: 0 auto">

<p>
<strong>You need to configure your webserver in order to being able to run EspoCRM. After that,
refresh the page.</strong>
</p>

<h2>For Apache webserver</h2>

<p>
You need to have <strong>mod_rewrite</strong> enabled. You can do it by running in the terminal:
</p>

<pre>
<code>
sudo a2enmod rewrite
sudo service apache2 restart
</code>
</pre>

<h3>Non-production environment</h3>

<p>
You need to enable `.htaccess` usage in the apache configuration. Add the code:
</p>

<pre>
<code>
&ltDirectory $path>
  AllowOverride All
&lt/Directory>
</code>
</pre>

<h3>Production environment</h3>

<p>
It's recommended to configure the document root to look at the `public`
directory and create an alias for the `client` directory. The code to add to the apache configuration:
</p>

<pre>
<code>
DocumentRoot $path/public/
Alias /client/ $path/client/
</code>
</pre>

<p>
And allow override for the `public` directory:
</p>

<pre>
<code>
&ltDirectory $path/public/>
  AllowOverride All
&lt/Directory>
</code>
</pre>

<p>
<strong>
See more details in the <a href="https://docs.espocrm.com/administration/apache-server-configuration/">documentation</a>.
</strong>
</p>

<h2>For Nginx webserver</h2>

<p>
You need to configure the document root to look at the `public` directory and create an alias for the `client` directory.
</p>

<p>
<strong>
See more details in the <a href="https://docs.espocrm.com/administration/nginx-server-configuration/">documentation</a>.
</strong>
</p>

</body>

EOL;

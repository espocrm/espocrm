<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
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

$path = getcwd();

echo <<<EOL
<h2>For apache webserver</h2>

<h4>Non-production environment</h4>

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

<h4>Poduction environment</h4>

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
More detals in the <a href="https://docs.espocrm.com/administration/apache-server-configuration/">documentation</a>.
</p>

<h2>For nginx webserver</h2>

<p>
You need to configure the document root to look at the `public` directory and create an alias for the `client` directory.
More detals in the <a href="https://docs.espocrm.com/administration/nginx-server-configuration/">documentation</a>.
</p>
EOL;

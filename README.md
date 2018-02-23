## EspoCRM

<a href='http://www.espocrm.com'>EspoCRM is an Open Source CRM</a> (Customer Relationship Management) software that allows you to see, enter and evaluate all your company relationships regardless of the type. People, companies or opportunities - all in an easy and intuitive interface.

It's a web application with a frontend designed as a single page application based on backbone.js and a REST API backend written in PHP.

Download the latest release from our [website](http://www.espocrm.com).

### Requirements

* PHP 5.6 or above (with pdo, json, gd, openssl, zip, imap, mbstring, curl extensions);
* MySQL 5.5.3 or above.

For more information about server configuration see [this article](https://www.espocrm.com/documentation/administration/server-configuration/).

### Documentation

Documentation for administrators, users and developers is available [here](https://www.espocrm.com/documentation/).

### How to report bug

Create an issue [here](https://github.com/espocrm/espocrm/issues) or post on our [forum](http://forum.espocrm.com/forum/bug-reports).

### How to get started (for developers)

1. Clone repository to your local computer.
2. Change to the project's root directory.
3. Install [composer](https://getcomposer.org/doc/00-intro.md).
4. Run `composer install` if composer is installed globally or `php composer.phar install` if locally.

Never update composer dependencies if you are going to contribute code back.

Now you can build. Build will create compiled css files.

To compose a proper config.php and populate database you can run install by opening `http(s)://{YOUR_CRM_URL}/install` location in a browser. Then open `data/config.php` file and add `isDeveloperMode => true`.

### How to build

You need to have nodejs and Grunt CLI installed.

1. Change to the project's root directory.
2. Install project dependencies with `npm install`.
3. Run Grunt with `grunt`.

The build will be created in the `build` directory.

### How to contribute

Before we can merge your pull request you need to accept our CLA [here](https://github.com/espocrm/cla). It's very simple to do.

### How to make a translation

Build po file with command:
`node po.js en_EN`
(specify needed language instead of en_EN)

After that translate the generated po file.

Build json files from the translated po file:

1. Put your po file espocrm-en_EN.po into `build` directory
2. Run `node lang.js en_EN`

Json files will be created in build directory grouped by folders.

### License

EspoCRM is published under the GNU GPLv3 [license](https://raw.githubusercontent.com/espocrm/espocrm/master/LICENSE.txt).


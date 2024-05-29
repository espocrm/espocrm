## EspoCRM

[![PHPStan level 8](https://img.shields.io/badge/PHPStan-level%208-brightgreen)](#espocrm)

[EspoCRM is an Open Source CRM](https://www.espocrm.com) (Customer Relationship Management)
software that allows you to see, enter and evaluate all your company relationships regardless
of the type. People, companies or opportunities – all in an easy and intuitive interface.

It's a web application with a frontend designed as a single page application and a REST API
backend written in PHP.

![Screenshot](https://user-images.githubusercontent.com/1006792/226094559-995dfd2a-a18f-4619-a21b-79a4e671990a.png)

### Demo

You can try the CRM on an online [demo](https://www.espocrm.com/demo/).

### Download

[Download](https://www.espocrm.com/download/) the lastest release from our website. You can also download the latest and previous release packages from GitHub [releases](https://github.com/espocrm/espocrm/releases).

### Release notes

Release notes are available at GitHub [releases](https://github.com/espocrm/espocrm/releases).

### Requirements

* PHP 8.1 - 8.3;
* MySQL 5.7 (and later), or MariaDB 10.2 (and later);
* PostgreSQL 15 (and later) (beta, official support soon).

For more information about server configuration see [this article](https://docs.espocrm.com/administration/server-configuration/).

### Documentation

See the [documentation](https://docs.espocrm.com) for administrators, users and developers.

### Bug reporting

Create a [GitHub issue](https://github.com/espocrm/espocrm/issues/new/choose) or post on our [forum](https://forum.espocrm.com/forum/bug-reports).

### Installing stable version

See installation instructions:

* [Manual installation](https://docs.espocrm.com/administration/installation/)
* [Installation by script](https://docs.espocrm.com/administration/installation-by-script/)
* [Installation with Docker](https://docs.espocrm.com/administration/docker/installation/)
* [Installation with Traefik](https://docs.espocrm.com/administration/docker/traefik/)

### Development

See the [developer documentation](https://docs.espocrm.com/development/).

We highly recommend using an IDE for development. The backend codebase follows SOLID principles, utilizes interfaces, static typing and generics. We recommend to start learning EspoCRM from the Dependency Injection article in the documentation.

Metadata plays an integral role in the EspoCRM application. All possible parameters are described with a JSON Schema, meaning you will have autocompletion in the IDE. You can also find the full metadata reference in the documentation.

### Community & Support

If you have a question regarding some features, need help or customizations, want to get in touch with other EspoCRM users, or add a feature request, please use our [community forum](https://forum.espocrm.com/). We believe that using the forum to ask for help and share experience allows everyone in the community to contribute and use this knowledge later.

### License

EspoCRM is published under the GNU AGPLv3 [license](https://raw.githubusercontent.com/espocrm/espocrm/master/LICENSE.txt).

### Contributing

Before we can merge your pull request, you need to accept our CLA [here](https://github.com/espocrm/cla). See [contributing guidelines](https://github.com/espocrm/espocrm/blob/master/.github/CONTRIBUTING.md).

Branches:

* *fix* – upcoming maintenance release; minor fixes should be pushed to this branch;
* *master* – develop branch; new features should be pushed to this branch;
* *stable* – last stable release.

### Language

If you want to improve existing translation or add a language that is not available yet, you can contribute on our [POEditor](https://poeditor.com/join/project/gLDKZtUF4i) project. See instructions [here](https://www.espocrm.com/blog/how-to-use-poeditor-to-translate-espocrm/). It may be reasonable to let us know about your intention to join the POEditor project by posting on our forum or via the contact form on our website.

Changes on POEditor are usually merged to the GitHub repository before minor releases.

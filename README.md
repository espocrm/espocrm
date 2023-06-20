## EspoCRM

[![PHPStan level 8](https://img.shields.io/badge/PHPStan-level%208-brightgreen)](#espocrm)

[EspoCRM is an Open Source CRM](https://www.espocrm.com) (Customer Relationship Management)
software that allows you to see, enter and evaluate all your company relationships regardless
of the type. People, companies or opportunities – all in an easy and intuitive interface.

It's a web application with a frontend designed as a single page application and REST API
backend written in PHP.

[Download](https://www.espocrm.com/download/) the latest release from our website. Release notes
and release packages are available at [Releases](https://github.com/espocrm/espocrm/releases) on GitHub.

![Screenshot](https://user-images.githubusercontent.com/1006792/226094559-995dfd2a-a18f-4619-a21b-79a4e671990a.png)

### Demo

You can try the CRM on the online [demo](https://www.espocrm.com/demo/).

### Requirements

* PHP 8.0 and later;
* MySQL 5.7 (and later), or MariaDB 10.2 (and later).

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

We highly recommend using IDE for development. The backend codebase follows SOLID principles, utilizes interfaces, static typing and generics. We recommend to start learning EspoCRM from the Dependency Injection article in the documentation.

### Contributing

Before we can merge your pull request, you need to accept our CLA [here](https://github.com/espocrm/cla). It's very simple to do.

Branches:

* *fix* – upcoming maintenance release; minor fixes should be pushed to this branch;
* *master* – develop branch; new features should be pushed to this branch;
* *stable* – last stable release.

### Language

If you want to improve existing translation or add a language that is not available yet, you can contribute on our [POEditor](https://poeditor.com/join/project/gLDKZtUF4i) project. See instructions [here](https://www.espocrm.com/blog/how-to-use-poeditor-to-translate-espocrm/).

Changes on POEditor are usually merged to the GitHub repository before minor releases.

### Community & Support

If you have a question regarding some features, need help or customizations, want to get in touch with other EspoCRM users, or add a feature request, please use our [community forum](https://forum.espocrm.com/). We believe that using the forum to ask for help and share experience allows everyone in the community to contribute and use this knowledge later.

### License

EspoCRM is published under the GNU GPLv3 [license](https://raw.githubusercontent.com/espocrm/espocrm/master/LICENSE.txt).

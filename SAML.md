## SAML with SimpleSAMLphp

This is how I did it, it is actually a call for discussion more then a HOWTO.

Thisn way SAML Service Provider is tightly integrated with EspoCRM and can be accessed at `/simplesaml`.

* Composer requirement `simplesamlphp/simplesamlphp`; it can be done in a different way,
eg have the path to simplesamlphp loader in config - this is simpler.
* `simplesaml` symlinnk to `vendor/simplesamlphp/simplesamlphp/www`

### Directory permissions

I don't think anything different to EspoCRM is required

### Setup

This is in a way out of scope. Follow the [SimpleSAMLphp Documentation](https://simplesamlphp.org/docs/stable/). Admin should

* Create SimpleSAMLphp [config.php](https://simplesamlphp.org/docs/stable/simplesamlphp-install#section_7)
* Go to `/simplesaml` and continue with [Service Provider QuickStart](https://simplesamlphp.org/docs/stable/simplesamlphp-sp)

A digest, additional modifications may be required as per above:

#### Create config files in `vendor/simplesamlphp/simplesamlphp/config/`

Templates are in `vendor/simplesamlphp/simplesamlphp/config-templates/

`config.php`: copy template, change
* `technicalcontact_email`
* `secretsalt`
* `auth.adminpassword`

`config.php`: copy template, change default-sp section
* `idp => 'https://idp-simplesamlphp-or-other.some-domain.net/saml2/idp/metadata.php'`

#### Add IdP metadata in `vendor/simplesamlphp/simplesamlphp/metadata/`

Add saml20-idp-remote.php or similar as provided by simplesamlphp idp instance, or if you have metadata of the remote IdP
as an XML file, use the built-in XML to SimpleSAMLphp metadata converter `/admin/metadata-converter.php`.

#### Export metadata to IdP

Go to `/simplesamlphp', "Federation" tab, "[ Show metadata ]".
If your IdP is SimpleSAMLphp then give them SimpleSAMLphp flat file, otherwise XML.

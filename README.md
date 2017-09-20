# Zutto Character API [![Build Status](https://travis-ci.org/ZuttoVPG/zutto-character.svg?branch=master)](https://travis-ci.org/ZuttoVPG/zutto-character) [![Coverage Status](https://coveralls.io/repos/github/ZuttoVPG/zutto-character/badge.svg?branch=master)](https://coveralls.io/github/ZuttoVPG/zutto-character?branch=master)  

This is the character API, for composing and scaling images (primarily of pets/NPCs). There is no dependency on other Zutto components; this is a stand-alone microservice.

## Installation
The character microservice requires PHP 7.x and the curl, mbstring, openssl, intl, fileinfo, and imagick extensions.

It does not require a database.

```bash
$ git clone https://github.com/ZuttoVPG/zutto-character.git
$ cd zutto-character
$ composer install
$ cp .env.example .env
$ vi .env # or, your editor of choice!
$ composer test # should all pass
```

If this is being run on a real web server instead of the built-in PHP dev server, ensure that the `public/` directory is your document root. Lumen requires some rewrite rules for its router -- if your webserver has .htaccess enabled, it should pick them up immediately from the `public/.htaccess` file.

## Configuration Options
The `.env` file has two options specifically for the character microservice:

- `CHAR_MAX_ATTACHMENTS` should be set to something rational for your use-case. This limit exists to stop someone from sending a request for 20,000 attachments and DoSing you.

- `CHAR_DOMAIN_WHITELIST` is a comma-separated list of hosts that the service is allowed to load assets from. Loading assets from untrusted hosts can be dangerous or abused, so it's best to set this.

Both options may be left blank to disable their checks.

## Example Usage
A test client with sample assets is provided. It will show you how to construct the JSON document for character generation requests.

Once deployed, visit `test_client.html`.

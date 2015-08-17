Klio
====

Klio is a user-friendly MySQL frontend.

[![Build Status](https://travis-ci.org/samwilson/klio.svg)](https://travis-ci.org/samwilson/klio)

Kudos:
* Foundation CSS framework: http://foundation.zurb.com/docs/index.html
* Twig templating system: http://twig.sensiolabs.org/documentation

## Installing

1. Clone into a web-accessible directory: `git clone https://github.com/samwilson/klio.git ~/public_html/klio`
2. Change into the cloned directory and run `composer install`
3. Copy `settings_example.php` to `settings.php` and edit the configuration variables therein
5. Navigate to `/install` and run the installer
6. Log in as `admin` with `admin`
7. Change your password, and configure the site

## Upgrading

1. Update with Git: `git pull origin master`
2. Run `composer install`
3. Navigate to `/upgrade` and run the upgrader

## Reporting issues

Please report all issues, bugs, feature requests, etc. at
https://github.com/samwilson/klio/issues

## Core

### Terminology

* Database
* Table
* Column
* Record

### URLs

Cool URLs don't change. Are these cool though? Perhaps not.

The capital letters are placeholders.

    /table/TABLENAME
    /table/TABLENAME/PAGENUMBER
    /record/PK
    /record/PK/delete

## Users

By default, everyone can *view* everything and logged-in users can *edit* everything.

To grant permissions on specific tables, create the following tables:



Row-level permissions must be implemented in a module.

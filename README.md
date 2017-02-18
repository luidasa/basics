## Synopsis

luidasa/basics: Is a template for create web application.

## Code Example




## Motivation

I use this template for web application.
1. Autorization.
2. Authentication.
3. Manage of accounts.
4. Notifications for accounts.
5. Manage clients.
6. Shared calendar.

## Installation

Clone the project.
1. Update the composer dependencies
  composer install or update
2. Update the bower dependencies. The bower file dependencies is on public folder.
  bower install
3. Create DB, example, db_basics:
4. Create src/config_override.php and set the appropiate value for config entries.
    DB
    mail
    * Tip view the src/config.php file for example. Is empty but if you have an
    develop environment you put, your values here, and you can use config_override.php
    for configure other environments as test or production.
5. For run the project use php -S localhost:8008 -t public.

## API Reference

Depending on the size of the project, if it is small and simple enough the reference docs can be added to the README. For medium size to larger projects it is important to at least provide a link to where the API reference docs live.

## Tests

Describe and show how to run the tests with code examples.

## Contributors

Let people know how they can dive into the project, include important links to things like issue trackers, irc, twitter accounts if applicable.

## License

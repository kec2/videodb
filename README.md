videoDB
=======

VideoDB is a PHP-based web application to manage a personal video collection. Multiple video types are supported, ranging from VHS tapes and DVDs to Blu-ray discs and DivX files on hard-disc. Even video games are supported.

Introduction
------------

### Browse

You can use videoDB to manage your video and CD collection, be it DVD, BluRay or plain Files:

![Browse Movies](https://raw.github.com/andig/videodb/master/doc/screenshots/0.png)

### View

![View Details](https://raw.github.com/andig/videodb/master/doc/screenshots/1.png)

### Edit
All data is editable in nice layed out forms:

![Edit](https://raw.github.com/andig/videodb/master/doc/screenshots/2.png)

### IMDB

New movies are easily added directly from IMDB or other sources:

![IMDB](https://raw.github.com/andig/videodb/master/doc/screenshots/3.png)

### Config

videoDB is also highly customizable- almost every aspect can be changed from template selection to detailed customization:

![Config](https://raw.github.com/andig/videodb/master/doc/screenshots/4.png)

### Docker

Copy the [config.sample.php](config.sample.php) to **config.inc.php** and set the password.
Make sure that the password is the same in [liquibase.properties](liquibase/liquibase.properties)

Run the docker compose script: [docker-compose.yml](docker-compose.yml)

Open http://localhost:8000 and goto Options -> configuration and make the changes you want.

#### Tips
You can export your collection from the menu.

You can import your collection from http://localhost:8000/edit.php?import=xml
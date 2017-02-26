# laravel-uuid

[![MIT licensed](https://img.shields.io/badge/license-MIT-blue.svg)](https://raw.githubusercontent.com/hyperium/hyper/master/LICENSE)

Custom Laravel 5.4 MySQL driver for use with `BINARY(16)` UUIDs. It automatically detects columns in queries and results but it can be finely tuned to your needs.

It **might** work previous minor versions of Laravel 5, let me know if you have successfully used it. Can only be used with MySQL.

Supports Eloquent Models and the Query Builder and handles foreign keys correctly, including for m:n (many to many) relations. 

The binary values are transformed to a properly-formatted string in your models and collections so you always see the readable value for all UUID-fields. When updating a record, just pass back the string value for any foreign keys or other binary uuid fields you might have. 


## Installation
######This package is in it's infancy so use with caution.
First add the package with composer:

	composer require wirk/laravel-uuid:dev-master

Next add the following line to your providers array in config/app.php

    WirksamesDesign\LaravelUuid\Database\DatabaseServiceProvider::class,

### Usage

The `UuidBinaryModelTrait` enables your model to generate primary keys correctly. Use it in every model that has one or more `BINARY(16)` UUID columns. 

```
<?php

namespace App;

use WirksamesDesign\LaravelUuid\Database\Traits\UuidBinaryModelTrait

class Team extends Model
{
    use UuidBinaryModelTrait;
}
```

### Configuration (optional)
The driver mostly does all the work for you. You can however create custom configurations for each model or even for specific fields. To do so, create a static array `uuidSettings` in your model class. You can also use a base model class to change the behaviour globally.
 ```php
     public static $uuidSettings = [];
```
#### Optimizing the binary stored UUIDs for sorting
The bits of your binary UUIDs can be re-arranged to allow sorting by creation date.
######Warning: use only on new models that don't contain any data yet.

[Learn how the optimization works](https://www.percona.com/blog/2014/12/19/store-uuid-optimized-way/) 

 ```php
     public static $uuidSettings = [
        'optimize' => true
     ];
```
#### Changing the version of the uuids
 ```php
     public static $uuidSettings = [
        'version' => 1
     ];
```

#### Disabling auto-detection of columns
 ```php
     public static $uuidSettings = [
        'detectColumns' => false,
     ];
```

#### Per-column configuration
You don't have to register your columns as they are auto-detected but you can override all settings for a specific column. These will override any base settings for the model.
 ```php
     public static $uuidSettings = [
       'version'        => 4,
       'optimize'       => false,
       'columns'        => [
            'id'              => ['version' => 5, 'optimize' => true],
            'cant_touch_this' => ['detectColumns' => false]
        ]
     ];
```

##### Auto-generate UUIDs
By default, the primary key column is automatically filled with a new UUID on insert. You can disable this behaviour if you are creating your own primary keys or add it to any other field.
 ```php
     public static $uuidSettings = [
        'columns' => [
            'id'        => ['generateOnInsert' => false],
            'nonce'     => ['generateOnInsert' => true]
        ]
     ];
```




### Migrations
The default Laravel `Blueprint` 
[does not currently support binary fields with specified length](https://github.com/laravel/framework/issues/1606),
and (at least in MySQL) you cannot create an index (including primary key) on a `BINARY` field without length.

So, the migration should be something like this:

```
<?php
	// ...
	Schema::create('users', function (Blueprint $table) {
		$table->string('username', 32);
		$table->string('password', 50);
	});

	DB::statement('ALTER TABLE `usersb` ADD `id` BINARY(16); ALTER TABLE `usersb` ADD PRIMARY KEY (`id`);')
?>
```

## Running tests

I need to write some new tests as parts of Alsofronie's original code have moved to different places and more code was added.


## Contributing
Please submit any bugs you encounter or create a PR. Or maybe you want to write some tests? ;-)

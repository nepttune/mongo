# Mongo

:floppy_disk: MongoDB Nette extension 

## Introduction

This package contains nette extension, which wraps official PHP library and visualises queries in tracy panel.

## Dependencies

- [nepttune/base-requirements](https://github.com/nepttune/base-requirements)
- [mongodb/mongodb](https://github.com/mongodb/mongodb)

## How to use

- Register `\Nepttune\Mongo\DI\MongoExtension` as extension in cofiguration file.
- Specify connection details in confuguration file.
- Inject services where you require to work with mongo.

### Example configuration

```
extensions:
    mongo: Nepttune\Mongo\DI\MongoExtension
    
mongo:
    connection:
        default:
            host: 'mongodb://localhost'
            user: 'X'
            password: 'X'
            database: 'X'
            ssl: false
            debugger: true // register panel extension
```

Extension registers following services:

- `\MongoDB\Driver\Manager`
- `\MongoDB\Database`

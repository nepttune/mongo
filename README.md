# Mongo

:floppy_disk: MongoDB Nette extension 

![Packagist](https://img.shields.io/packagist/dt/nepttune/mongo.svg)
![Packagist](https://img.shields.io/packagist/v/nepttune/mongo.svg)
[![CommitsSinceTag](https://img.shields.io/github/commits-since/nepttune/mongo/v1.0.2.svg?maxAge=600)]()

[![Code Climate](https://codeclimate.com/github/nepttune/mongo/badges/gpa.svg)](https://codeclimate.com/github/nepttune/mongo)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nepttune/mongo/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/nepttune/mongo/?branch=master)

## Introduction

This package contains nette extension, which wraps official PHP library and visualises queries in tracy panel. Extension is heavily inspired by [kdyby/redis](https://github.com/kdyby/redis) package.

## Dependencies

- [nepttune/base-requirements](https://github.com/nepttune/base-requirements)
- [mongodb/mongodb](https://github.com/mongodb/mongodb)

## How to use

- Register `\Nepttune\Mongo\DI\MongoExtension` as extension in cofiguration file.
- Specify connection details in confuguration file. You can define multiple connections.
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

Extension registers following services (for every connection):

- `\MongoDB\Driver\Manager`
- `\MongoDB\Database`

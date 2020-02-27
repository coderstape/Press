# Press
An elegant markdown-powered Blog for the Laravel Framework.

This package provides all of the bare bones to build a blog in Laravel. The posts come from parsed markdown files and are stored in 1 of 3 location. The location of the files is driven by 1 of the 3 built-in drivers for file, database and gist.

## Basics

### Press File Format

Here is a sample file. All of the fields are the top are customizable and extendable by publishing the service provider and adding your own field definitions.

~~~markdown
---
title: A very important title will go here 
keywords: github, gists, coder's tape
description: Here we will describe the blog post in sentence form.
date: May 14 2020
tags: News, GitHub

---

### Markdown can be used here and even <strong>HTML</strong>

Here is the body of the post
~~~

## Drivers

### File Driver

The file driver will simply look for `.md` files inside a predetermined directory. This is useful for creating blog posts and keeping them inside of your source control.

### Database Driver

As the name suggest, this will store all of your raw markdown files (before being processed) in the database. This is specially helpful for allowing for an admin panel where a user can edit the blog posts inside of the browser. We have provided the controller for basic Admin panel but no views have been provided.

### Gist Driver

The GitHub Gist driver is great for those looking to bring in posts from different sources. It uses a single file as the main source and that file should contain all of the Gists that you would like included inside of the blogs. Any public gist can be used from any source.

## Customizations

### Custom Fields

Any of the fields in the header of the markdown document can be customized for your project.

1. Publish the PressServiceProvider with `php artisan vendor:publish --tag press-provider`
2. Create a class using this stub

~~~php
<?php

namespace App\Fields;

use coderstape\Press\Field\FieldContract;

class Published extends FieldContract
{
    /**
     * Process the field and make any needed modifications.
     *
     * @param $fieldType
     * @param $fieldValue
     * @param $fields
     *
     * @return array
     */
    public static function process($fieldType, $fieldValue, $fields)
    {
        return [
            'active' => ($fieldValue == 'yes') ? 1 : 0,
        ];
    }
}
~~~

> Recommended to add a directory under `app` named `Fields` to store all of your custom fields.

3. Register your new custom field in the service provider

~~~php
/**
 * Bootstrap any additional custom field parsers.
 *
 * @return array
 */
public function fields()
{
    return [
        \App\Fields\Published::class,
    ];
}
~~~

4. To use it simply add to the header section of the markdown file and it will be saved in the extras column of your parsed press file.

5. Using it in your custom views is easy using the `->extra()` method. For this particular example, you may call `$post->extra('published')` as you are iterating through posts.

### Customizing Editors

> This required the database driver

To edit who can edit the blog posts when using the database driver, you can add the following to the PressServiceProvider

~~~php
/**
 * Bootstrap any package services.
 *
 * @return void
 */
public function boot()
{
    Press::fields($this->fields());

    Press::editors([
        'admin@email.com',
        'another@user.com',
    ]);
}
~~~

Listing out all of the emails of authenticated users that have rights to edit this. In your views or controllers, there's a handy method to check if a user is authorized to perform editing functions.

Using the Press facade you can call `Press::isEditor()` to get a boolean value.

+ In Blade views

~~~php
@if(Press::isEditor())
    <a href="{{ Press::path() . '/admin' }}">Admin</a>
@endif
~~~

+ In controllers

~~~php
use coderstape\Press\Facades\Press;

public function show($post)
{
    if ( !Press::isEditor()) {
        return redirect()->to('other/address/here');
    }
    
    // Controller stuff here...
}
~~~

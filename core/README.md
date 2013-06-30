# Wicked - The Event-Driven Web Framework

Wicked is a modular event-driven web framework based on the wisdom of WordPress, Drupal, Ruby on Rails, and similar architectures. In particular, Wicked focuses on being event-based. You can use Wicked to build any type of web application or mobile application using event-driven programming techniques. If you are familiar with WordPress actions/filters or Drupal events, you will feel right at home in Wicked.

## Features and Benefits

Wicked is designed to be lightweight, feature-rich, and performant.

* Small, lightweight microkernel - almost everything in Wicked is an optional module
* Designed to leverage PHP bytecode caching. Wicked uses standard `require` statements and writes all code generation to files rather than using `eval`
* Lazy loading - Wicked lazy-loads all modules and features only when they are referenced in the execution path
* Event-driven architecture - Wicked is event-driven, which means extensibility to your heart's content
* Clean namespacing - Wicked uses lazy-loaded dynamic class extensions so your functions don't pollute global namespace
* Ready for the grid - Wicked is designed with grid-computing in mind

## Installing Wicked

Wicked comes with a CLI tool that gives you easy access to the Wicked core and Wicked Registry of community contributions. It manages dependencies and installs the right things in the right places.

Add this to the bottom of your `~/.bash_profile`, choosing your own location of course:

    # This is the location of the repository where 
    # Wicked modules are downloaded and installed.
    export WICKED_HOME=~/wicked
    
    # This is your local bin folder where the wicked
    # CLI stub will reside
    export PATH=$PATH:~/bin


Install the Wicked core:  

    git clone git@github.com:benallfree/wicked.git $WICKED_HOME/w
    
Now open `nano` or your favorite editor and create a `wicked` CLI stub

    nano ~/bin/wicked

    #!/bin/sh
    php $WICKED_HOME/w/cli/run.php $1 $2 $3 $4 $5 $6 $7

Adjust permissions:

    chmod 755 ~/bin/wicked

And now try the following command:

    which wicked
    wicked --help

    Wicked 1.x.x CLI Tool
    ---------------------
    Repo Location: <$WICKED_HOME>
  
That's it! You installed Wicked and can use it from any of your web applications.

Continue reading for a short tutorial.

## Hello World

Try creating your first app:

    cd ~/path/to/my/new/www
    wicked macro stub
    ls

You should see two new files:

    index.php
    w.php

Let's look at `index.php`:

    require('w.php');
        
    function hello($s)
    {
      return "Hello, world!";
    }
    
    Wicked::register_filter('run', 'hello');
    
    echo Wicked::do_filter('run');

Well thats pretty easy. If you browse to index.php or run `php index.php` from the command line, you should see Hello World.

Let's take a look under the hood at `w.php`:

    $path = "/path/to/wicked/repo"; // This is where $WICKED_HOME points to
    set_include_path(get_include_path() . PATH_SEPARATOR . $path);
    require('w/cked.php');

As you can see, `w.php` is a pretty simple bootstrap itself. It includes the Wicked core and nothing more.

## Including Wicked in an Existing Application

Wicked plays well with others. It will not pollute your global function or variable namespace, but does make use of non-namespaced classes on a very limited basis. Wicked add-on modules may do the same thing.

Create the same stub as you did before. Wicked will not overwrite an existing `index.php` or `w.php` without asking.

Once it has created `w.php`, you will need to include `w.php` in the file of your choice.

## Getting More Wicked

By installing the Wicked CLI, you have done a lot. The CLI provides access to community contributions.

For example, you can browse the popular contributions like this:

    $ wicked list
    

    Available Modules
    ---------------
    request - git@github.com:benallfree/wicked-request.git
    path_utils - git@github.com:benallfree/wicked-path-utils.git
    ....
        
Installing a module is easy:

    $ wicked install request
    
That will install the latest version of `request` into $WICKED_HOME.

Some contributions are "platform" contributions upon which many other contributions are built. You'll begin to see how the pieces fit together as you become more familiar with the community. 

## Getting Even More Wicked

If you do much developing with other people's stuff, you'll arrive at a point where you want to break out the source code and fork your own version of it so you can modify it and submit a pull request like a good citizen. Or maybe you just want to look at it.

Module forks are always installed in your local repo.

Wicked automatically looks in your $WICKED_HOME location when loading modules. But before it looks there, it will also look for a local repo named `wicked` in the current directory or a parent directory. If it finds one, it will assume that is the repo you mean to use. (You can override the repo locations by using a custom `Wicked` file, see below).

## Extending Wicked

Look at existing Wicked modules to understand how to design your own.

To contribute a Wicked module:

1. Create a github repo
1. Add your `wicked` meta file
1. Push your changes
1. Submit your github URL to me

Once added to the registry, your repo will automatically be scanned for the `wicked` metadata file.

## Contributing to the Wicked Core

If you do have a core enhancement or patch that is core:

1. Fork
1. Change
1. Test
1. Submit pull request :)


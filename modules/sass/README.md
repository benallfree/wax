# SASS Mixin for Wicked

The SASS mixin for the [Wicked](http://www.github.com/benallfree/wicked.git) framework provides support for evaluating `.sass` files into CSS.

## Usage

First, create a `.sass` file:

    .body
      h1
        :color white
        :background-color black

Then, create a CSS file from it:

    $cached_css_fpath = W::sass_eval_file('/some/file.sass')
    

# PHP Eval Sandbox mixin for Wicked

The PHP eval() command executes in scope. This wrapper allows you to eval a PHP file in a localized variable scope context.

## Usage

    W::php_sandbox('/path/to/my/file.php', array('foo'=>'bar', 'bar'=>'baz'))
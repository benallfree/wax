# Collections mixin for Wicked

This mixin provides convenient functions to operate and iterate over PHP arrays.

## Functions

1. array_each(&$objects, $proc) - be sure to call as W::array_each(&$my_objects, ...) to pass your objects by reference if you intend to modify them. Or use collect().
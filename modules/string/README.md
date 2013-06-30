# String Mixin for Wicked

The string mixin for the [Wicked framework](http://github.com/benallfree/wicked) adds many helpful string calculations to the `W::` namespace.

## Functions

1. u($s) - Short for urlencode()
2. h($s) - UTF-8 aware htmlentities()
1. j($s) - Encode as JavaScript string
3. startof($s, $chop)/endof($s,$chop) - Return the start or end of a string, where $chop is a number or string to chop.
4. truncate ($str, $length=30, $trailing='...')
5. endswith($s, $suffix, **$suffix**, ...) - Return TRUE if $s ends with any of the supplied suffixes
6. startswith($s, $suffix, **$suffix**, ...) - Return TRUE if $s ends with any of the supplied suffixes
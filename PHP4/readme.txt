These are the changes you need to make to use the Custom SQL report with PHP 4.

These changes were worked out by Andrew Eigus.
See http://tracker.moodle.org/browse/CONTRIB-2080.

To use this:

1. Copy the two other files in this folder up one level, into the customsql folder.

2. Apply the patch using the command line
    patch -u -i php4-patch
or one of the other methods on http://docs.moodle.org/en/How_to_apply_a_patch
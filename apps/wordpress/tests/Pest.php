<?php

/*
|--------------------------------------------------------------------------
| Pest bootstrap (wexoe-core)
|--------------------------------------------------------------------------
|
| Tester för WP-FRIA delar av wexoe-core (helpers, Schema-parsing, Normalizer-
| logik) som kan köras utan en WordPress-runtime. Closures binds till PHPUnits
| standard-TestCase. Autoload: composer autoload-dev mappar Wexoe\Core\ →
| wexoe-core/src/.
|
*/

uses()->in(__DIR__);

<?php

use Crwlr\PackageTemplate\ExampleClass;

test('method foo returns bar', function () {
    expect((new ExampleClass())->foo())->toBe('bar');
});

<?php

namespace Tests;

use Crwlr\SchemaOrg\DataArray;

it('recursively makes all child arrays instances of itself', function () {
    $instance = DataArray::make([
        'one' => 'foo',
        'two' => [
            'three' => 'bar',
            'four' => ['five' => 'baz'],
        ],
        'three' => ['something'],
    ]);

    expect($instance->toArray()['two'])->toBeInstanceOf(DataArray::class);

    expect($instance->toArray()['two']->toArray()['four'])->toBeInstanceOf(DataArray::class);

    expect($instance->toArray()['three'])->toBeInstanceOf(DataArray::class);
});

it('recursively turns all child instances to arrays when toArray is called with recursive param true', function () {
    $instance = DataArray::make([
        'one' => 'foo',
        'two' => [
            'three' => 'bar',
            'four' => ['five' => 'baz'],
        ],
        'three' => ['something'],
    ]);

    expect($instance->toArray(true))->toBe([
        'one' => 'foo',
        'two' => [
            'three' => 'bar',
            'four' => ['five' => 'baz'],
        ],
        'three' => ['something'],
    ]);
});

it('is iterable', function () {
    $instance = DataArray::make([
        1 => 'one',
        'two' => 2,
        'three' => ['something'],
    ]);

    $key = $value = null;

    foreach ($instance as $key => $value) {
        if ($value instanceof DataArray) {
            $value = $value->toArray(true);
        }

        expect($key)->toBeIn([1, 'two', 'three']);

        expect($value)->toBeIn(['one', 2, ['something']]);
    }

    expect($key)->toBe('three');

    expect($value)->toBe(['something']);

    $instance->rewind();

    expect($instance->current())->toBe('one');
});

test('you can set a value by string key', function () {
    $instance = DataArray::make([]);

    $instance->set('foo', 'bar');

    expect($instance->toArray())->toBe(['foo' => 'bar']);
});

test('you can set a value by int key', function () {
    $instance = DataArray::make([]);

    $instance->set(2, true);

    expect($instance->toArray())->toBe([2 => true]);
});

it('returns it\'s string type', function () {
    $instance = DataArray::make(['@type' => 'Organization']);

    expect($instance->getType())->toBe('Organization');
});

it('returns an array type as array', function () {
    $instance = DataArray::make(['@type' => ['CreativeWork', 'Product']]);

    expect($instance->getType())->toBe(['CreativeWork', 'Product']);
});

it('returns null when the @type key is neither string nor array', function () {
    $instance = DataArray::make(['@type' => true]);

    expect($instance->getType())->toBeNull();
});

it('returns null when the @type key does not exist', function () {
    $instance = DataArray::make(['foo' => 'bar']);

    expect($instance->getType())->toBeNull();
});

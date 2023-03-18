<?php

namespace Lenius\SharedData\Tests;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Lenius\SharedData\SharedData;
use stdClass;

class SharedDataTest extends AbstractTestCase
{
    /** @var SharedData|null */
    protected $sharedData;

    /** @var mixed|null */
    protected $lazy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sharedData = new SharedData;

        $this->lazy = null;
    }

    public function testPut()
    {
        // scalar

        $this->sharedData->put('foo', 'bar');

        $this->assertSame('bar', $this->sharedData->get('foo'));

        // iterable

        $this->sharedData->put([
            'scalar' => 'scalar-value',
            'array' => ['nested' => 'value'],
        ]);

        $this->assertSame('scalar-value', $this->sharedData->get('scalar'));

        $this->assertSame(['nested' => 'value'], $this->sharedData->get('array'));

        // JsonSerializable

        $jsonSerializable = new class implements JsonSerializable {
            public function jsonSerialize()
            {
                return [
                    'json-serializable-key' => 'json-serializable-value',
                ];
            }
        };

        $this->sharedData->put($jsonSerializable);

        $this->assertSame('json-serializable-value', $this->sharedData->get('json-serializable-key'));

        // Arrayable

        $arrayable = new class implements Arrayable {
            public function toArray()
            {
                return [
                    'arrayable-key' => 'arrayable-value',
                ];
            }
        };

        $this->sharedData->put($arrayable);

        $this->assertSame('arrayable-value', $this->sharedData->get('arrayable-key'));

        // Closure (lazy)

        $this->lazy = 'foo';

        $this->sharedData->put(function () {
            return [
                'lazy' => $this->lazy,
            ];
        });

        $this->lazy = 'bar';

        $this->assertSame('bar', $this->sharedData->get('lazy'));

        // Closure (lazy) with key

        $this->lazy = 'foo';

        $this->sharedData->put('lazy-with-key', function () {
            return $this->lazy;
        });

        $this->lazy = 'bar';

        $this->assertSame('bar', $this->sharedData->get('lazy-with-key'));

        // object

        $object = new stdClass;

        $object->objectScalar = 'object-scalar';

        $object->objectArray = ['nested' => 'object-scalar'];

        $this->sharedData->put($object);

        $this->assertSame('object-scalar', $this->sharedData->get('objectScalar'));

        $this->assertSame(['nested' => 'object-scalar'], $this->sharedData->get('objectArray'));
    }

    public function testGet()
    {
        $values = [
            'scalar' => 'scalar-value',
            'array' => ['nested' => 'value'],
        ];

        $this->sharedData->put($values);

        $this->assertSame('scalar-value', $this->sharedData->get('scalar'));

        $this->assertSame($values, $this->sharedData->get());
    }

    public function testToJson()
    {
        $this->sharedData->put([
            'scalar' => 'scalar-value',
            'array' => ['nested' => 'value'],
        ]);

        $json = $this->sharedData->toJson();

        $expectedJson = '{"scalar":"scalar-value","array":{"nested":"value"}}';

        $this->assertSame($expectedJson, $json);
    }

    public function testRender()
    {
        $this->sharedData->put([
            'scalar' => 'scalar-value',
            'array' => ['nested' => 'value'],
        ]);

        $this->sharedData->setJsNamespace('customShareDataNamespace');

        $this->sharedData->setJsHelperName('customSharedFunctionName');

        $this->sharedData->setJsHelperEnabled(true);

        $html = $this->sharedData->render();

        $expectedHtml = '<script>window["customShareDataNamespace"]={"scalar":"scalar-value","array":{"nested":"value"}};window["sharedDataNamespace"]="customShareDataNamespace";window["customSharedFunctionName"]=function(e){var n=void 0!==arguments[1]?arguments[1]:null;return[window.sharedDataNamespace].concat("string"==typeof e?e.split("."):[]).reduce(function(e,t){return e===n||"object"!=typeof e||void 0===e[t]?n:e[t]},window)};</script>';

        $this->assertSame($expectedHtml, $html);
    }

    public function testRenderWithAttributes()
    {
        $this->sharedData->put([
            'scalar' => 'scalar-value',
            'array' => ['nested' => 'value'],
        ]);

        $this->sharedData->setJsNamespace('customShareDataNamespace');

        $this->sharedData->setJsHelperName('customSharedFunctionName');

        $this->sharedData->setJsHelperEnabled(true);

        $html = $this->sharedData->render(['attributes' => ['nonce' => 'HELLOWORLD">', 'data-hello' => 'world']]);

        $expectedHtml = '<script nonce="HELLOWORLD&quot;&gt;" data-hello="world">window["customShareDataNamespace"]={"scalar":"scalar-value","array":{"nested":"value"}};window["sharedDataNamespace"]="customShareDataNamespace";window["customSharedFunctionName"]=function(e){var n=void 0!==arguments[1]?arguments[1]:null;return[window.sharedDataNamespace].concat("string"==typeof e?e.split("."):[]).reduce(function(e,t){return e===n||"object"!=typeof e||void 0===e[t]?n:e[t]},window)};</script>';

        $this->assertSame($expectedHtml, $html);
    }

    public function testToString()
    {
        $this->sharedData->put('foo', 'bar');

        $this->assertSame($this->sharedData->render(), (string) $this->sharedData);
    }

    public function testJsNamespace()
    {
        $this->assertSame('sharedData', $this->sharedData->getJsNamespace());

        $this->sharedData->setJsNamespace('foo');

        $this->assertSame('foo', $this->sharedData->getJsNamespace());
    }

    public function testJsHelperName()
    {
        $this->assertSame('shared', $this->sharedData->getJsHelperName());

        $this->sharedData->setJsHelperName('customShared');

        $this->assertSame('customShared', $this->sharedData->getJsHelperName());
    }

    public function testJsHelperEnabled()
    {
        $this->assertSame(true, $this->sharedData->getJsHelperEnabled());

        $this->sharedData->setJsHelperEnabled(false);

        $this->assertSame(false, $this->sharedData->getJsHelperEnabled());
    }

    public function testJsHelper()
    {
        $this->sharedData->setJsHelperName('customSharedFunctionName');

        $this->assertSame('window["customSharedFunctionName"]=function(e){var n=void 0!==arguments[1]?arguments[1]:null;return[window.sharedDataNamespace].concat("string"==typeof e?e.split("."):[]).reduce(function(e,t){return e===n||"object"!=typeof e||void 0===e[t]?n:e[t]},window)}', $this->sharedData->getJsHelper());
    }

    public function testToArray()
    {
        $this->sharedData->put('foo', ['bar' => 'baz']);

        $this->assertSame(['foo' => ['bar' => 'baz']], $this->sharedData->toArray());
    }

    public function testJsonSerialize()
    {
        $this->sharedData->put('foo', ['bar' => 'baz']);

        $this->assertSame(['foo' => ['bar' => 'baz']], $this->sharedData->jsonSerialize());
    }

    public function testOffsetExists()
    {
        $this->sharedData->put('foo.baz', 'bar');

        $this->assertTrue(isset($this->sharedData['foo.baz']));

        $this->assertFalse(isset($this->sharedData['baz.foo']));
    }

    public function testOffsetGet()
    {
        $this->sharedData->put('foo.bar', 'baz');

        $this->assertSame('baz', $this->sharedData['foo.bar']);
    }

    public function testOffsetSet()
    {
        $this->sharedData['foo.bar'] = 'baz';

        $this->assertSame(
            [
                'foo' => [
                    'bar' => 'baz',
                ],
            ],
            $this->sharedData->get()
        );
    }

    public function testOffsetUnset()
    {
        $this->sharedData->put([
            'foo' => [
                'bar' => 'baz',
                'baz' => 'bar',
            ],
        ]);

        unset($this->sharedData['foo.baz']);

        $this->assertSame(
            [
                'foo' => [
                    'bar' => 'baz',
                ],
            ],
            $this->sharedData->get()
        );
    }

    public function testForget()
    {
        $this->sharedData->put([
            'foo' => [
                'bar' => 'baz',
                'baz' => 'bar',
            ],
        ]);

        $this->sharedData->forget('foo.baz');

        $this->assertSame(
            [
                'foo' => [
                    'bar' => 'baz',
                ],
            ],
            $this->sharedData->get()
        );

        $this->sharedData->forget();

        $this->assertSame([], $this->sharedData->get());
    }

    public function testBladeDirective()
    {
        $this->assertEquals(
            shared()->render(),
            view('shared')->render()
        );
    }

    /**
     * @depends testBladeDirective
     */
    public function testBladeDirectiveWithCustomName()
    {
        $this->app['config']['shared-data.blade_directive.name'] = 'shared_custom';

        $this->assertEquals(
            shared()->render(),
            view('shared_custom')->render()
        );
    }
}

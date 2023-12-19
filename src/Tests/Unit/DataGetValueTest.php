<?php

namespace Rapidez\BladeDirectives\Tests\Unit;

use PHPUnit\Framework\TestCase;

class TestClass {
    function __construct(private $_value)
    {}

    function value() {
        return $this->_value;
    }
}

class DataGetValueTest extends TestCase
{
    public function test_that_plain_array_returns_value()
    {
        $target = [
            'foo' => [
                'bar' => [
                    'baz' => 'quux'
                ]
            ],
        ];

        $this->assertEquals('quux', data_get_value($target, 'foo.bar.baz'));
    }

    public function test_that_array_with_final_value_function_returns_value()
    {
        $target = [
            'foo' => [
                'bar' => [
                    'baz' => [
                        'value' => fn() => 'quux'
                    ]
                ]
            ],
        ];

        $this->assertEquals('quux', data_get_value($target, 'foo.bar.baz.value'));
    }

    public function test_that_array_with_value_function_halfway_returns_value()
    {
        $target = [
            'foo' => [
                'bar' => [
                    'value' => fn() => [
                        'baz' =>'quux'
                    ]
                ]
            ],
        ];

        $this->assertEquals('quux', data_get_value($target, 'foo.bar.value.baz'));
    }

    public function test_that_plain_stdclass_returns_value()
    {
        $target = (object) [
            'foo' => [
                'bar' => [
                    'baz' => 'quux'
                ]
            ],
        ];

        $this->assertEquals('quux', data_get_value($target, 'foo.bar.baz'));
    }

    public function test_that_stdclass_with_final_value_function_returns_value()
    {
        $target = (object) [
            'foo' => [
                'bar' => [
                    'baz' => [
                        'value' => fn() => 'quux'
                    ]
                ]
            ],
        ];

        $this->assertEquals('quux', data_get_value($target, 'foo.bar.baz.value'));
    }

    public function test_that_stdclass_with_value_function_halfway_returns_value()
    {
        $target = (object) [
            'foo' => [
                'bar' => [
                    'value' => fn() => [
                        'baz' =>'quux'
                    ]
                ]
            ],
        ];

        $this->assertEquals('quux', data_get_value($target, 'foo.bar.value.baz'));
    }

    public function test_that_class_with_final_value_function_returns_value()
    {
        $target = (object) [
            'foo' => [
                'bar' => [
                    'baz' => new TestClass('quux')
                ]
            ],
        ];

        $this->assertEquals('quux', data_get_value($target, 'foo.bar.baz.value'));
    }

    public function test_that_class_with_value_function_halfway_returns_value()
    {
        $target = (object) [
            'foo' => [
                'bar' => new TestClass([
                    'baz' =>'quux'
                ])
            ],
        ];

        $this->assertEquals('quux', data_get_value($target, 'foo.bar.value.baz'));
    }
}

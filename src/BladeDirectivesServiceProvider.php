<?php

namespace Rapidez\BladeDirectives;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeDirectivesServiceProvider extends ServiceProvider
{
    public function register()
    {
        Blade::directive('attributes', function (string $expression) {
            /**
             * Echo out a componentAttributeBag with the given attributes.
             * Usage: @attributes(['id' => 'test', 'name' => 'some_name'])
             */
            return "<?php echo (new \Illuminate\View\ComponentAttributeBag)($expression); ?>";
        });

        Blade::directive('return', function () {
            return "<?php return; ?>";
        });

        Blade::directive('includeFirstSafe', function (string $expression) {
            /**
             * @see \Illuminate\View\Compilers\Concerns\CompilesIncludes@compileIncludeFirst
             * Attempt to include the first existing file, if none exists. Do nothing.
             * Usage: @includeFirstSafe(['potentially-missing-template', 'another-potentially-missing-template'], $set)
             */
            $expression = Blade::stripParentheses($expression);

            return "<?php try { echo \$__env->first({$expression}, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); } catch (\InvalidArgumentException \$e) { if (!app()->environment('production')) { echo '<hr>'.__('View not found: :view', ['view' => implode(', ', [{$expression}][0])]).'<hr>'; } } ?>";
        });

        Blade::directive('slots', function ($expression) {
            /**
             * @see https://github.com/laravel/framework/pull/47574
             * Define optional slots you will use in your component,
             * this will ensure those slots will be \Illuminate\View\ComponentSlot
             * Usage: @slots(['optionalSlot', 'anotherSlot' => ['contents' => 'default text', 'attributes' => ['class' => 'default classes']]])
             */
            return "<?php foreach ({$expression} as \$__key => \$__value) {
    \$__key = is_numeric(\$__key) ? \$__value : \$__key;
    \$__value = !is_array(\$__value) && !\$__value instanceof \ArrayAccess ? [] : \$__value;
    if (!isset(\$\$__key) || is_string(\$\$__key)) {
        \$\$__key = new \Illuminate\View\ComponentSlot(\$\$__key ?? \$__value['contents'] ?? '', \$__value['attributes'] ?? []);
    }
} ?>
<?php \$attributes ??= new \\Illuminate\\View\\ComponentAttributeBag; ?>
<?php \$attributes = \$attributes->exceptProps($expression); ?>";
        });

        Blade::directive('tags', function ($expression) {
            /**
             * Use Statamic::tag() in a more convenient way
             * Usage:
             *  - @tags(['products' => ['collection:products' => ['type' => 'chair']]])
             *  - @tags(['products' => 'collection:products'])
             *  - @tags('collection:products')
             * etc...
             */
            return "<?php
                foreach(Arr::wrap($expression) as \$__key => \$__value) {
                    if (is_array(\$__value) && count(\$__value) == 1) {
                        \$__tag = array_keys(\$__value)[0];
                        \$__params = array_values(\$__value)[0];
                    } else if (is_array(\$__value)) {
                        \$__tag = \$__value['tag'];
                        \$__params = \$__value['params'] ?? [];
                    } else {
                        \$__tag = \$__value;
                        \$__params = [];
                    }
                    \$__varName = is_string(\$__key) ? \$__key : camel_case(str_replace(':','_',\$__tag));
                    \${\$__varName} = Statamic::tag(\$__tag)->params(\$__params)->fetch();
                }
            ?>";
        });
    }

    public function boot()
    {
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components');
    }
}

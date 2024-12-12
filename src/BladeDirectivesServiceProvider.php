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

        Blade::directive('includeFirstSafe', function (string $expression) {
            /**
             * @see \Illuminate\View\Compilers\Concerns\CompilesIncludes@compileIncludeFirst
             * Attempt to include the first existing file, if none exists. Do nothing.
             * Usage: @includeFirstSafe(['potentially-missing-template', 'another-potentially-missing-template'], $set)
             */
            $expression = Blade::stripParentheses($expression);

            return "<?php try { echo \$__env->first({$expression}, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); } catch (\InvalidArgumentException \$e) { if (!app()->environment('production')) { echo '<hr>'.__('View not found: :view', ['view' => implode(', ', [{$expression}][0])]).'<hr>'; } } ?>";
        });

        Blade::directive('markdown', function ($expression) {
            return "<?php echo (fn(\$markdown, \$html_input = 'escape') => Str::markdown(\$markdown ?? '', ['html_input' => \$html_input]))($expression); ?>";
        });

        Blade::directive('return', function ($expression) {
            return "<?php if ((fn (\$return = true) => \$return)($expression)) {return;}; ?>";
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

        Blade::directive('slotdefault', function ($expression) {
            $slot = trim($expression, '\'"');
            return "<?php if(isset($$slot) && ($$slot)->isNotEmpty()): echo $$slot; else: ?>";
        });

        Blade::directive('endslotdefault', function ($expression) {
            return '<?php endif; ?>';
        });

        Blade::directive('includeCached', function ($expression) {
            $expression = Blade::stripParentheses($expression);

            // The rest of the cacheKey needs to be within the generated views.
            // That way it's stays dynamic. The Str::slug(url('/')) is used
            // to support multi sites; a cache per application url.
            // You could use URL::forceRootUrl(...) for this.
            $cacheKey = trim($expression, '\'"');

            // So it's cached for 5 minutes and refreshed in the background until + 24 hours.
            // Which never happens but it's required; after that it refreshed directly.
            return "<?php echo \Illuminate\Support\Facades\Cache::flexible('include-cache::site-'.\Illuminate\Support\Str::slug(url('/')).'-{$cacheKey}', [now()->addMinutes(5), now()->addDay()], fn() => \$__env->make({$expression}, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render()); ?>";
        });
    }

    public function boot()
    {
    }
}

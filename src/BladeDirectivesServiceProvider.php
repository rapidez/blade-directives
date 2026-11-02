<?php

namespace Rapidez\BladeDirectives;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Factory as ViewFactory;

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
             * Attempt to include the first existing file. If none exists, do nothing.
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

            // Using flexible cache so that it's cached every 5 minutes, but then refreshed in the background if 24 hours have passed without a cache hit.
            // This calculates the cache key inside of the generated PHP so that variables are accounted for.
            // NOTE: Complex expressions (e.g. large collections, eloquent models) will cause the json_encode to be slow.
            return "<?php
\$__cacheKey = md5(serialize([{$expression}]));
\$__cached = \Illuminate\Support\Facades\Cache::flexible(
    'include-cache::site-'.\Illuminate\Support\Str::slug(url('/')).'-'.\$__cacheKey,
    [now()->addMinutes(5), now()->addDay()],
    fn() => \$__env->renderWithStacks({$expression}, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path'])));
if (is_array(\$__cached)) {
    \$__env->pushStacks(\$__cached);
    \$__cached = \$__cached['html'];
}
echo \$__cached;
unset(\$__cacheKey, \$__cached);
            ?>";
        });
    }

    public function boot()
    {
        ViewFactory::macro('renderWithStacks', function ($view, $data = [], $mergeData = []) {
            $pushes = $this->pushes;
            $prepends = $this->prepends;
            $renderedOnce = $this->renderedOnce;
            $this->renderedOnce = [];

            try {
                $html = $this->make($view, $data, $mergeData)->render();
                $once = array_keys($this->renderedOnce);
                $newPushes = [];
                $newPrepends = [];

                foreach ($this->pushes as $section => $contents) {
                    foreach ($contents as $key => $content) {
                        $newPushes[$section] = ($newPushes[$section] ?? '') . substr($content, strlen($pushes[$section][$key] ?? ''));
                    }
                }

                foreach ($this->prepends as $section => $contents) {
                    foreach ($contents as $key => $content) {
                        $newPrepends[$section] = substr($content, 0, strlen($content) - strlen($prepends[$section][$key] ?? '')) . ($newPrepends[$section] ?? '');
                    }
                }
            } finally {
                $this->pushes = $pushes;
                $this->prepends = $prepends;
                $this->renderedOnce = $renderedOnce;
            }

            return [
                'html'     => $html,
                'pushes'   => array_filter($newPushes),
                'prepends' => array_filter($newPrepends),
                'once'     => $once,
            ];
        });

        ViewFactory::macro('pushStacks', function (array $stacks) {
            foreach ($stacks['pushes'] as $section => $content) {
                $this->startPush($section, $content);
            }

            foreach ($stacks['prepends'] as $section => $content) {
                $this->startPrepend($section, $content);
            }

            foreach ($stacks['once'] as $id) {
                $this->markAsRenderedOnce($id);
            }
        });
    }
}

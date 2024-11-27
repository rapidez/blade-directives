# Rapidez Blade Directives

This package adds blade directives that we found we needed in Laravel during development of Rapidez. Like `@slots`, which lets you define optional slots so your `attributes->merge()` always works. Or `@includeFirstSafe` which works the same as `@includeFirst` but will not throw errors if no template was found. All directives included within this package:

- [`@attributes`](#attributes)
- [`@includeFirstSafe`](#includefirstsafe)
- [`@markdown`](#markdown)
- [`@return`](#return)
- [`@slots`](#slots)
- [`@slotdefault` + `@endslotdefault`](#slotdefault)
- [`@includeCached`](#includecached)

## Installation

```
composer require rapidez/blade-directives
```

## Directives

### @attributes

The `@attributes` blade directive allows you to pass the attributes for a html element using an array. It's functionally the same as the `$attributes` of a blade component but you can use it outside of blade components!

#### Usage

[Example](https://github.com/rapidez/statamic/blob/066b5d336e44890c5b4049f5df3c62b15ed302b2/resources/views/page_builder/form.blade.php#L9)

```blade
<input @attributes(['type' => 'text', 'id' => 'test', 'name' => 'some_name'])/>
```

which will result in

```html
<input type="text" id="test" name="some_name" />
```

### @includeFirstSafe

The `@includeFirstSafe` blade directive works the same way that `@includeFirst` does however it will not throw an error if all templates do not exsist.
Outside of production mode it will alert about the missing templates however.

#### Usage

[Example](https://github.com/rapidez/statamic/blob/066b5d336e44890c5b4049f5df3c62b15ed302b2/resources/views/page_builder.blade.php#L2)

```blade
@includeFirstSafe(['custom.admin', 'admin'], ['status' => 'complete'])
```

### @markdown

You can use the `@markdown` directive to transform markdown into html. Basically, `{!! Str::markdown($text) !!}` but in directive form.

#### Usage

```blade
@markdown($text)
```

### @return

The `@return` blade directive simply stops any further processing of the current template

#### Usage

[Example](https://github.com/rapidez/statamic/blob/066b5d336e44890c5b4049f5df3c62b15ed302b2/resources/views/page_builder/form.blade.php#L5)

```blade
@return
```

Or only when a condition is true:
```blade
@return($someConditionIsTrue)
```

### @slotdefault

When you've an optional slot this directive gives you a cleaner way of defining a fallback. Normally you do something like this:

```blade
@if ($slot->isEmpty())
    This is default content if the slot is empty.
@else
    {{ $slot }}
@endif
```

#### Usage

```blade
@slotdefault('slot')
    This is default content if the slot is empty.
@endslotdefault
```

### @slots

The `@slots` blade directive is used within blade components.
It is used to define optional named slots which will be created if they are not passed.
Very useful if named slots might not always be passed but you want to use the `attributes` of this named slot

#### Usage

Within your blade component:
```blade
@slots(['optionalSlot', 'anotherSlot' => ['contents' => 'dummy text', 'attributes' => ['class' => 'bg-red-500']]])

<div {{ $attributes }}>
    {{ $slot }}
    <div {{ $optionalSlot->attributes }}>{{ $optionalSlot }}</div>
    <div {{ $anotherSlot->attributes->class('text-black') }}>{{ $anotherSlot }}</div>
</div>
```

If you enter nothing and only load in the component without passing any named slots it will be

```html
<div >
    <div ></div>
    <div class="bg-red-500 text-black">dummy text</div>
</div>
```

but if you were to pass the named slots it would look like this:

```blade
<x-component>
    Regular slot text
    <x-slot:optionalSlot>Optional content</x-slot:optionalSlot>
    <x-slot:anotherSlot class="text-lg">Optional content</x-slot:anotherSlot>
</x-component>
```

```html
<div >
    Regular slot text
    <div >Optional content</div>
    <div class="text-lg text-black">Optional content</div>
</div>
```

As you can see it has overwritten the class of the optional slot, but not the `attributes->class()`

If you only wish to change the text without changing attributes you can also pass them as attributes.

```blade
<x-component optionalSlot="Optional content" anotherSlot="Optional content">
    Regular slot text
</x-component>
```

```html
<div >
    Regular slot text
    <div >Optional content</div>
    <div class="bg-red-500 text-black">Optional content</div>
</div>
```

### @includeCached

Just like [`@include`](https://laravel.com/docs/11.x/blade#including-subviews) but cached. Everything returned will be cached with [`Cache::flexible()`](https://laravel.com/docs/11.x/cache#swr) for 5 minutes; and refreshed in the background until it expires after 24 hours. After that it will be refreshed as usual. The cache key is a combination of the view name and the current slugified url. That way this can be used with multisite setups:
```
include-cache::site-{ Str::slug(url('/')) }-{ $viewName }'
```

#### Usage

```blade
@includeCached('view.name')
```

#### Notes

Keep in mind that any dynamic things within the view will not be executed when cached. For example `@push`, see [Blade Stacks](https://laravel.com/docs/11.x/blade#stacks). Also [Blade Icons Deferring](https://github.com/blade-ui-kit/blade-icons#deferring-icons) doesn't work, you have to use these things outside the cached include!

## Helpers

### optionalDeep

Have you heard of [optional()](https://laravel.com/docs/10.x/helpers#method-optional)? This is the supercharged version working at any depth!
It makes sure that any missing key will not break your code, especially helpful when mixing Statamic with Blade

#### Usage

It will automatically return the value when casting to string so you can immediately echo out it's value, if you want to get the value use the get method.
This will return null if anywhere along the chain the value or key does not exist.
```blade
{{ optionalDeep($object)->undefinedKey->anotherUndefinedKey }}
{{ optionalDeep($object)->header->usp->link->value() }}
@if(optionalDeep($object)->header->usp->link->value()->isset())
@if(optionalDeep($object)->header->usp->link->value()->get() === 'test')
```

> [!TIP]
> the [OptionalDeep](https://github.com/rapidez/blade-directives/blob/master/src/OptionalDeep.php#L15) class implements Macroable, allowing you to extend it with your own functions!

## License

GNU General Public License v3. Please see [License File](LICENSE) for more information.

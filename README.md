# Rapidez Blade Directives

This package adds blade directives that we found we needed in Laravel during development of Rapidez. Like `@slots`, which lets you define optional slots so your `attributes->merge()` always works. Or `@includeFirstSafe` which works the same as `@includeFirst` but will not throw errors if no template was found.

## Installation

```
composer require rapidez/blade-directives
```

## Components

### x-tag

Yeah we know, it's a component, not a directive but this could be a useful one that's why've added it. It is a Blade version of a [dynamic Vue component](https://vuejs.org/guide/essentials/component-basics.html#dynamic-components)

#### Usage

```blade
<x-tag is="span" class="font-bold">
    Something
</x-tag>
```

which will result in

```html
<span class="font-bold">
    Something
</span>
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

### @return

The `@return` blade directive simply stops any further processing of the current template

#### Usage

[Example](https://github.com/rapidez/statamic/blob/066b5d336e44890c5b4049f5df3c62b15ed302b2/resources/views/page_builder/form.blade.php#L5)

```blade
@return
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

## License

GNU General Public License v3. Please see [License File](LICENSE) for more information.

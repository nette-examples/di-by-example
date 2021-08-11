# 04 · Constructor Hell

What happens when a base class has dependencies, and why the way out is not a better constructor.

**You will learn:**

- what constructor hell is and how you walk into it
- why composition gets you out and inheritance does not
- when to use a constructor and when a setter

## Run it

```shell
php 04-constructor-hell/bad.php
php 04-constructor-hell/good.php
```

## The story

You have followed both rules so far. Nothing is hidden, every class takes what it needs. And then you write the thing every framework tutorial has written since 2006: a base controller, so that all controllers share some plumbing.

```php
abstract class BaseController
{
	public function __construct(
		private Cache $cache,
		private Translator $translator,
	) {
	}
}
```

Reasonable. Now write a controller.

## The pain

```php
class ProductController extends BaseController
{
	public function __construct(Cache $cache, Translator $translator, private Database $db)
	{
		parent::__construct($cache, $translator);
	}
}
```

Look at what that constructor is doing. Of its three arguments, exactly one belongs to `ProductController`. The other two are accepted purely to be handed upstairs to `parent::__construct()`. It is chapter 02's problem again - carrying someone else's luggage - except now inheritance makes it mandatory rather than optional.

This is **constructor hell**, and it has a very specific way of ruining a Tuesday. The product owner asks for request logging on every page. So `BaseController` needs one more dependency:

```php
public function __construct(Cache $cache, Translator $translator, Logger $logger)
```

And now you edit *every child constructor* to accept a `Logger` and pass it up. And every place that instantiates a controller. In this example that is two classes. In the project where I learned this, it was forty, it took most of a day, and the pull request was 600 lines of pure noise with one line of actual feature in it. Reviewing it was impossible, so nobody really did.

Notice too that the base class has quietly become a dependency magnet. Anything anyone might want "everywhere" gets added there, because that is where "everywhere" lives, and every addition taxes all forty children.

## The fix

The problem is not the constructor. It is that `ProductController` **is a** `BaseController`, so it is stuck with its ancestry. Turn the relationship around: instead of inheriting the shared behaviour, own it.

```php
class PageRenderer
{
	public function __construct(
		private Cache $cache,
		private Translator $translator,
	) {
	}

	public function say(string $text): string { /* ... */ }
}

class ProductController
{
	public function __construct(
		private PageRenderer $renderer,
		private Database $db,
	) {
	}
}
```

`ProductController` no longer *is* a renderer, it *has* one. Both of its arguments are genuinely its own, Rule #2 is satisfied again, and the Tuesday scenario becomes: add `Logger` to `PageRenderer`'s constructor. That is the whole change. The controllers do not know it happened.

> **Prefer composition over inheritance.** Inheritance couples you to your ancestor's constructor forever. Composition couples you to an interface you asked for.

This is the moment I stopped writing `Base*` classes almost entirely. Not out of purity - out of self-interest, having been the person who had to write those 600 lines.

## Constructor or setter?

Since we are on the subject of constructors, the short version of a question that comes up constantly:

- **Constructor** for dependencies the class cannot work without. The object cannot even be created in an invalid state, which is exactly what you want. This is the default, and it is the right choice roughly nine times out of ten.
- **Setter** for genuinely optional dependencies, or ones that may legitimately change during the object's life. The cost is that the class must survive the setter never being called, so its code has to cope with the dependency being absent.
- **Public property** is the third option and I would rather you did not. It makes the dependency part of your public API, gives up any control over what gets assigned, and buys you nothing that the other two do not.

There is a fourth way used by Nette presenters - `inject` methods and the `#[Inject]` attribute - which exists precisely to dodge constructor hell in a class hierarchy you do not control. Worth knowing it is there; not worth reaching for elsewhere.

## Output

### bad.php

```
PRODUCT PAGE
[Database] SELECT * FROM products
ORDER PAGE
[Mailer] sending confirmation

Now the product owner wants request logging in every controller.
That means one new argument in BaseController - and then editing
every single child constructor, plus every place that creates one.
```

### good.php

```
PRODUCT PAGE
[Database] SELECT * FROM products
ORDER PAGE
[Mailer] sending confirmation

Now add request logging to PageRenderer: one new constructor argument,
in one class. The controllers do not change. They never even find out.
```

Both versions print the same thing, which is the point: from the outside nothing changed. The difference is entirely in what tomorrow costs.

## Try it yourself

1. Add a `Logger` dependency to `BaseController` in `bad.php` and make the file run again. Count the edits. Then do the same to `PageRenderer` in `good.php` and count again.
2. Add a third controller to each file. Notice which version needed you to know what the base class wanted.
3. In `good.php`, give `OrderController` a second renderer configured differently. In the inheritance version, this is not expressible at all.

## Further reading

- [Passing Dependencies](https://doc.nette.org/dependency-injection/passing-dependencies)
- [Why is composition preferred over inheritance?](https://doc.nette.org/dependency-injection/faq)

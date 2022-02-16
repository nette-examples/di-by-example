# 10 · Generated Factories

Remember the factory you wrote by hand in chapter 05? Delete it. Write the interface and keep the behaviour.

**You will learn:**

- how an interface with a `create()` method becomes a working factory
- how to pass runtime arguments through the factory into the constructor
- what an accessor is and when it beats injecting the service directly

## Run it

```shell
php 10-generated-factories/example.php
```

## The interface is the whole thing

In chapter 05 we established Rule #3 and paid for it with a class:

```php
class ArticleFactory
{
	public function __construct(
		private Database $db,
		private SlugGenerator $slugs,
	) {
	}

	public function create(): Article
	{
		return new Article($this->db, $this->slugs);
	}
}
```

Boring, mechanical, and derivable entirely from `Article`'s constructor. So do not write it:

```php
interface ArticleFactory
{
	function create(int $authorId): Article;
}
```

Register it like any other service (`- ArticleFactory` in the configuration) and the container generates the implementation. Requirements: exactly one method, named `create`, with a declared return type.

The generated code is in `temp/`, and it is what you would have written:

```php
return new class ($this) implements ArticleFactory {
	public function create(int $authorId): Article
	{
		return new Article($this->container->getService('01'), $this->container->getService('03'), $authorId);
	}
};
```

The real prize is what happens next time `Article` changes. Give it a fourth dependency and the factory follows automatically, because it is regenerated from the constructor. In chapter 05 that was the one class you still had to remember to update; now the class does not exist.

## Runtime arguments

Notice that `create()` takes an `int $authorId` and `Article`'s constructor also has `int $authorId`. Nette matches them **by name** and passes the value through, while filling the rest from the container:

```php
$article = $this->articleFactory->create(authorId: 7);
```

That is the split you want: the container supplies what it knows (database, slug generator), the caller supplies what only it can know (which author is posting). No service locator, no `new` with a pile of arguments in a controller.

If you need more control, the long form of the definition takes `arguments` and `setup` just like a normal service - useful when the factory method's parameter should go to a setter rather than the constructor.

## Accessors

The sibling feature: an interface with a single `get()` method and no parameters.

```php
interface DatabaseAccessor
{
	function get(): Database;
}
```

`create()` returns a **new object each time**; `get()` returns **the shared service**, and the example prints proof of both. So why not just inject `Database` and be done?

Because injecting it means creating it. Picture a class that logs errors into a dedicated database - errors are rare, but the connection would be opened on every single request, forever, just in case. Give that class an accessor and the connection happens on the first `get()`, which on most days is never.

That is the entire use case: expensive dependency, rarely used. Reach for it there and nowhere else, because an accessor is slightly less honest than a plain dependency - the constructor now says "I may need a database" rather than "I need one".

If you find yourself wanting several factories and accessors in one class, the interface can hold several `create<Name>()` and `get<Name>()` methods and the container implements all of them.

## Output

```
[Database] connected to mysql:host=127.0.0.1;dbname=blog as admin
[Database] INSERT INTO articles (title, slug, author) VALUES ('Ten things about dependency injection', 'ten-things-about-dependency-injection', 7)
[log] article submitted by author 7
[Database] INSERT INTO articles (title, slug, author) VALUES ('Eleven things about dependency injection', 'eleven-things-about-dependency-injection', 42)
[log] article submitted by author 42

Factory create() built two different Articles above.
Accessor get() called twice returns the same Database: yes
[Database] SELECT count(*) FROM articles
```

One connection, two articles, two different author ids. `EditController` never saw a `Database` in its life.

## Try it yourself

1. Add a `Clock` dependency to `Article`'s constructor and register it. The factory keeps working - you changed one class, not two.
2. Rename `create()` to `make()` and run again. The compiler tells you immediately what it expects.
3. Delete the return type from `ArticleFactory::create()`. Same story: a clear error at compile time, not a mystery at runtime.
4. Open the generated container in `temp/` and find the anonymous class implementing your interface.

## Further reading

- [Generated Factories](https://doc.nette.org/dependency-injection/factory)
- [Service Definitions](https://doc.nette.org/dependency-injection/services)

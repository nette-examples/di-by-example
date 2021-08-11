# 05 · Factories

Some objects are created over and over, with dependencies. Here is who should do the creating.

**You will learn:**

- **Rule #3: let the factory handle it**
- why passing a dependency "through" a class is a smell, even when it works
- what a factory actually is (spoiler: an ordinary class with a `create()` method)

## Run it

```shell
php 05-factories/bad.php
php 05-factories/good.php
```

## The story

Services like a logger or a mailer exist once and get passed around. Easy. But some objects are created on demand, many times, each one fresh - an `Article` the user just wrote, an `Invoice`, an `Order`. And those have dependencies too.

```php
class Article
{
	public function __construct(private Database $db) {}
	public function save(): void { /* ... */ }
}
```

Now a controller has to create one when a form is submitted. Where does it get the `Database`?

## The pain

The obvious answer is to have it passed in, which is what we have been preaching for four chapters:

```php
class EditController
{
	public function __construct(private Database $db) {}

	public function submit(string $title): void
	{
		$article = new Article($this->db);
		// ...
	}
}
```

It runs. It even looks like good DI. But read `EditController` and ask the chapter 02 question: *does it need a database?* It never queries anything. It is holding a `Database` for one reason only - `new Article(...)` demands one. That is Rule #2 violated, just wearing a disguise: the dependency is not hidden, it is merely misfiled.

And the bill arrives the moment `Article` changes. Compare the two files: in `good.php`, `Article` has gained a `SlugGenerator`. In the `bad.php` design, that single addition would force you to:

1. add `SlugGenerator` to `EditController`'s constructor,
2. do the same in every other class that creates an `Article`,
3. and update every place that creates *those*.

A class about slugs propagating into a class about HTTP requests. The change is mechanical, boring and large - the worst combination, because that is the kind you make at 6pm without reading carefully.

## The fix

> **Rule #3: Let the factory handle it.** If a class has dependencies and needs creating on demand, delegate the creation to a factory.

A factory is not a pattern with ceremony. It is a class whose job is knowing how to build one thing:

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

And the controller asks for what it truly needs - the ability to create articles:

```php
class EditController
{
	public function __construct(private ArticleFactory $articleFactory) {}

	public function submit(string $title): void
	{
		$article = $this->articleFactory->create();
		// ...
	}
}
```

Now exactly one place in the entire application knows how an `Article` is assembled. `Article` can grow a third and fourth dependency and the blast radius stays inside `ArticleFactory`. Run both files and note that `good.php` already *has* that extra dependency - and `EditController` looks the same as it would have without it.

Think of a factory as the DI-friendly replacement for `new`. Wherever you would have written `new SomethingWithDependencies(...)` in the middle of business logic, you inject a factory instead.

## "This is more code than before"

It is, and you are right to notice. We now have an `ArticleFactory` class that does nothing but call `new`, and if you are feeling that this is a lot of ceremony for a constructor call - hold that thought, because you are exactly right and Nette DI fixes it.

Later in the course, this entire class disappears and becomes:

```php
interface ArticleFactory
{
	function create(): Article;
}
```

You write the interface. The container writes the implementation, keeps it in sync with `Article`'s constructor, and you never touch it again. But I did not want to show you that trick before you had felt why the factory needs to exist at all, because a generated factory looks like magic if you have not first written the boring one by hand.

## Output

### bad.php

```
[Database] connected to mysql:host=127.0.0.1;dbname=blog
[Database] INSERT INTO articles (title) VALUES ('Ten things about dependency injection')

The controller never queries anything. It carries a Database around
for one reason: `new Article(...)` demands it.
```

### good.php

```
[Database] connected to mysql:host=127.0.0.1;dbname=blog
[Database] INSERT INTO articles (title, slug) VALUES ('Ten things about dependency injection', 'ten-things-about-dependency-injection')

Article gained a second dependency since bad.php - and EditController
did not change at all. Only ArticleFactory knows how an Article is built.
```

## Try it yourself

1. Give `Article` a third dependency - a `Clock` that stamps the publication date - and make both files work again. Count the classes you edited in each.
2. Add a `DraftController` that also creates articles. In `good.php` it needs one constructor argument; in `bad.php` it needs everything `Article` needs.
3. Give `ArticleFactory::create()` a parameter, say `create(int $authorId)`, and pass it through to the `Article`. This is a preview of what generated factories will do for free.

## Further reading

- [Rule #3: Let the Factory Handle It](https://doc.nette.org/dependency-injection/introduction)
- [Generated Factories](https://doc.nette.org/dependency-injection/factory)

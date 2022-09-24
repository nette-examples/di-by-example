# 11 · Registering Services Automatically

Your configuration should not grow by one line every time you write a class. Let the container go and find them.

**You will learn:**

- how the `search` section registers classes found on disk
- how to filter what gets registered
- when automatic registration is a good idea and when it is not

## Run it

```shell
php 11-search/example.php
```

## The problem with lists

The configuration so far has one line per service. That is honest and explicit, and it is fine - until the day you notice your team's pull requests all contain the same diff: a new class, plus a line in `config.neon` remembering to mention it.

Repositories are the classic case. There are twenty of them, they all look alike, they all get registered the same way, and forgetting one produces an error message rather than a bug - but it still costs you a round trip.

## The `search` section

```neon
search:
	repositories:
		in: %appDir%
		classes:
			- *Repository
```

Scan `%appDir%`, register every class whose name ends in `Repository`. Our three repositories appear in the container without being named anywhere, and autowiring wires them up as usual - the example asks for all three by type and they are there.

You can filter by more than the name:

```neon
search:
	handlers:
		in: %appDir%
		extends:
			- BaseHandler
		implements:
			- EventHandler
		exclude:
			classes:
				- *Abstract*
```

And you can add tags to everything found, which is where this combines nicely with the next chapter.

Interfaces are included too, if they look like a [generated factory or accessor](https://doc.nette.org/dependency-injection/factory) - one `create()` or `get()` method. Classes that are already registered explicitly are skipped, so adding a `search` section to an existing project does not produce duplicates.

## One standalone detail

If you are using Nette DI on its own, as this course does, `Compiler` enables only `services` and `parameters`. Sections like `search` come from extensions, and you register them yourself:

```php
$compiler->addExtension('search', new Nette\DI\Extensions\SearchExtension(__DIR__ . '/temp'));
```

One line, and only because we are running bare. In a full Nette application the `Configurator` has already done this for you, along with `decorator`, `di`, `inject` and the rest.

## Should you use it?

I go back and forth on this, so here is my honest position.

**Yes** for large, homogeneous families of classes: repositories, form factories, command handlers, presenters. The registration carries no information - it is the same line with a different name - so automating it removes noise rather than knowledge.

**No** for services that need arguments, setup, or any decision at all. The moment a class needs `create: Foo(%dsn%)` or a `setup` block, put it in the list where a reader can see it.

And a caveat worth knowing: with a list, the configuration file tells you what exists in the application. With `search`, that knowledge moves into a naming convention, which is fine while the convention holds and confusing the day someone writes `ArticleRepo`. When you cannot find a service, `search` is the first place to look.

## Output

```
[Database] connected to mysql:host=127.0.0.1;dbname=blog as admin
[Database] INSERT INTO articles (title) VALUES ('Ten things about dependency injection')
[log] article saved: Ten things about dependency injection
[Database] SELECT count(*) FROM users
[Database] SELECT tag FROM tags ORDER BY uses DESC LIMIT 10

Three repositories, zero lines of configuration each.
```

## Try it yourself

1. Add a `CommentRepository` class to `app/`, require it in `example.php`, and use it. You did not touch the configuration.
2. Rename `TagRepository` to `TagFinder` and run again. It disappears from the container, and the error message tells you exactly which type was missing.
3. Change the filter to `classes: [*Repository, *Finder]` and watch it come back.

## Further reading

- [Search: automatic service registration](https://doc.nette.org/dependency-injection/configuration)
- [Nette DI standalone](https://doc.nette.org/dependency-injection/nette-container)

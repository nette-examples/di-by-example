# 07 · The Container, Generated

We delete the `Container` class from the previous chapter and let Nette DI write it instead. Then we read what it wrote.

**You will learn:**

- how to build a container from a `config.neon` file in three lines
- how to get services out with `getByType()`
- where the generated container lives and what it looks like inside
- why "compiled to PHP" means there is no runtime magic to be afraid of

## Run it

```shell
php 07-nette-container/example.php
```

## The wiring, as configuration

Here is the entire application description:

```neon
parameters:
	dsn: 'mysql:host=127.0.0.1;dbname=blog'
	user: admin

services:
	- Database(%dsn%, %user%)
	- ConsoleLogger
	- ArticleRepository
	- ReportGenerator
```

Two things to notice before anything else.

First, `ArticleRepository` and `ReportGenerator` are listed with **no arguments at all**, even though both constructors need a database and a logger. Nette reads the constructors, sees the types, finds services that match, and passes them. That is autowiring, and it gets a chapter of its own next.

Second, there are no service names here - just a list. Names are optional because we will ask for services by *type*, and a type is something the compiler and your IDE both understand. (Give them names when you need to refer to them, which happens less often than you would think.)

## Three lines to a container

```php
$loader = new Nette\DI\ContainerLoader(__DIR__ . '/temp', autoRebuild: true);
$class = $loader->load(function (Nette\DI\Compiler $compiler): void {
	$compiler->loadConfig(__DIR__ . '/config.neon');
});

$container = new $class;
```

The loader compiles the configuration into a PHP class, writes it to `temp/`, and returns its name. On every later run it just loads the cached file - the NEON is not parsed again. With `autoRebuild: true` it watches the config and the classes and recompiles when they change, which is what you want while developing and can switch off in production.

Then services come out by type:

```php
$container->getByType(ArticleRepository::class)->save('...');
```

No string keys, no `get('article.repository')` that fails at runtime because someone renamed something. `ArticleRepository::class` is checked by PHP, understood by PHPStan, and refactorable by your IDE - rename the class and this line follows.

## Now open `temp/`

This is the part I would have wanted to see years earlier, and the reason I trust this library. Go read the generated file. Here is what is in it:

```php
public function createService01(): Database
{
	return new Database('mysql:host=127.0.0.1;dbname=blog', 'admin');
}

public function createService03(): ArticleRepository
{
	return new ArticleRepository($this->getService('01'), $this->getService('02'));
}
```

Compare that with what you wrote by hand in chapter 06:

```php
public function getDatabase(): Database
{
	return $this->database ??= new Database($this->parameters['dsn'], $this->parameters['user']);
}

public function getArticleRepository(): ArticleRepository
{
	return $this->articleRepository ??= new ArticleRepository($this->getDatabase(), $this->getLogger());
}
```

Almost the same class, with one refactor applied. Every method you wrote did two jobs at once: build the object, and remember it. Nette splits them. `createServiceNN()` is only the building half; the remembering half lives once in the parent `Nette\DI\Container`, whose `getService()` calls `createServiceNN()` the first time and hands out the stored instance ever after. It is your `??=`, hoisted out of every method into a single place.

So the behaviour is identical - ask twice, get the same object, one connection in the output - and the naming follows from it: `createServiceNN()` is what the container calls internally, exactly once per service; `getService()` and `getByType()` are what you call, as often as you like. You did not adopt a mysterious runtime system; you automated a file you were already writing.

This matters more than it sounds. A container that resolves dependencies at runtime by reflection is a box you cannot see into - when it does something surprising, you debug the framework. A container compiled to plain PHP is a file you open, read, and step through in the debugger like any other code. When something is wired the way you did not expect, the answer is written down in `temp/`.

It is also fast, for the boring reason that there is nothing to be slow: on a normal request, "resolving dependencies" is a `new` and a couple of method calls.

## What about a real application?

In a full Nette application you do not write these three lines - `Bootstrap` and the `Configurator` do it for you, with the cache directory, debug mode and extensions already sorted. The three lines are what it looks like standalone, and standalone is entirely supported: Nette DI is a library you can drop into any project, Symfony-flavoured, Laravel-flavoured or homemade.

## Output

```
[Database] connected to mysql:host=127.0.0.1;dbname=blog as admin
[Database] INSERT INTO articles (title) VALUES ('Ten things about dependency injection')
[log] article saved: Ten things about dependency injection
[Database] SELECT count(*) FROM articles
[log] monthly report generated

One connection, same as chapter 06 - but nobody wrote a Container class.
```

## Try it yourself

1. Run the example twice and watch `temp/`: the second run compiles nothing, it loads the cached class. Then delete `temp/` entirely - everything still works, because it is a cache, not a source.
2. Add a `NewsletterDistributor` class and one line to `config.neon`. Compare that with the method and the property you had to write in chapter 06.
3. Call `getByType(ArticleRepository::class)` twice and compare the two objects with `===`. The same instance, because `createService03()` ran only once.
4. Change the `dsn` parameter and run again. The container notices the config changed and rebuilds itself.

## Further reading

- [Nette DI Container](https://doc.nette.org/dependency-injection/nette-container)
- [Service Definitions](https://doc.nette.org/dependency-injection/services)

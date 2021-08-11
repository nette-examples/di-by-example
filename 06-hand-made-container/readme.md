# 06 · A Container, Written by Hand

Wiring an application by hand gets tedious. So we write the thing that does the wiring - and it turns out to be an ordinary class.

**You will learn:**

- what a DI container actually is, by writing one
- why services are shared and what that saves you
- that no class in your application ever needs to know the container exists

## Run it

```shell
php 06-hand-made-container/bad.php
php 06-hand-made-container/good.php
```

## The story

Five chapters in, our classes are honest. Every dependency is declared, nothing hides in a static, factories create what needs creating. This is genuinely good code - and it has produced a new, smaller problem.

Somebody has to say `new`. A lot.

```php
$db = new Database('mysql:host=127.0.0.1;dbname=blog', 'admin');
$logger = new ConsoleLogger;
$repository = new ArticleRepository($db, $logger);
```

Three lines to save one article. And that block has to exist wherever the application starts doing something.

## The pain

Run `bad.php` and count the database connections in the output. There are two.

Nobody was careless. The editing part of the application wired up what it needed. A month later, someone wrote the reporting part and wired up what *it* needed - the same classes, because they needed the same things. Two independent blocks of `new`, and now one process holds two connections to one database.

That is the mild version. The real versions I have met:

- Two loggers writing to the same file, interleaving each other's lines.
- A cache that was empty in half the app, because that half had its own instance.
- Configuration duplicated in six bootstrap files, five of which were updated when the password changed.

And every one of these looks fine in review, because each block on its own is perfectly reasonable code.

## The fix

Move the wiring into one class:

```php
class Container
{
	private ?Database $database = null;
	private ?ArticleRepository $articleRepository = null;

	public function getDatabase(): Database
	{
		return $this->database ??= new Database($this->parameters['dsn'], $this->parameters['user']);
	}

	public function getArticleRepository(): ArticleRepository
	{
		return $this->articleRepository ??= new ArticleRepository($this->getDatabase(), $this->getLogger());
	}
}
```

That is a dependency injection container. All of it. Read it again if you were expecting something bigger - I certainly was, for years, which is why I avoided containers longer than I should have.

Notice that every method has the same two-part shape, and that this is the whole trick: **build on first call, hand out the same instance ever after.** An object the application has exactly one of is what everyone means by the word *service*, and the container's entire job is to own them. That is also why the methods are called `get*` rather than `create*`: asking twice gets you the same object. Nette's generated container behaves identically, though it splits the two halves of these methods apart - the next chapter opens the file and shows you where each half went.

Run `good.php`: one connection, because the second caller got the same `Database` instance as the first.

"But some objects I need many of - articles, invoices, orders." Yes, and that was Rule #3 in the previous chapter. Those do not become services; their **factory** does. The container hands out one shared `ArticleFactory`, and the factory hands out as many articles as you like. The container itself only ever deals in shared instances, and keeping that line clean is what makes the next chapter's generated code readable.

## The part that matters most

Look at `ArticleRepository`. It is byte-for-byte the same class as in `bad.php`. Open `app/` and search for the word "Container" - it is not there.

> The container knows about your classes. Your classes know nothing about the container.

That one-way relationship is what separates dependency injection from a *service locator*, its evil twin. A service locator gets passed into your classes so they can ask it for things: `$this->container->get('database')`. It looks similar and it undoes everything - the class is lying about its dependencies again, you cannot test it without building a container, and the signature tells you nothing. If you ever find a container reference sneaking into a constructor that is not a factory, that is the alarm going off.

## And now the tedium

Here is where I have to be honest about my own creation. That `Container` class is fine at five services. At fifty it is a chore:

- Every new class means a new method, written by hand, in a file everybody edits and nobody enjoys reviewing.
- Every constructor change means finding the matching `get*` method and updating it. Miss one and you find out at runtime.
- Rename a class and you rename it here too.
- And it is all mechanical. Every single line of it is derivable from the constructors it calls.

Which raises the obvious question: if this code can be derived from the classes, why am I typing it?

That is exactly what Nette DI does. Next chapter we throw this file away and let the container be generated - and because you have now written one by hand, the generated one will hold no mystery. It is this class, produced by a machine that never forgets to update a constructor call.

## Output

### bad.php

```
[Database] connected to mysql:host=127.0.0.1;dbname=blog as admin
[Database] INSERT INTO articles (title) VALUES ('Ten things about dependency injection')
[log] article saved: Ten things about dependency injection
[Database] connected to mysql:host=127.0.0.1;dbname=blog as admin
[Database] SELECT count(*) FROM articles
[log] monthly report generated

Count the connections above: two. One process, one database, two connections.
```

### good.php

```
[Database] connected to mysql:host=127.0.0.1;dbname=blog as admin
[Database] INSERT INTO articles (title) VALUES ('Ten things about dependency injection')
[log] article saved: Ten things about dependency injection
[Database] SELECT count(*) FROM articles
[log] monthly report generated

One connection this time, and neither class knows the container exists.
```

## Try it yourself

1. Add a `NewsletterDistributor` that needs the logger, and give the container a `get` method for it. Notice that you had to touch exactly one file.
2. Change `getLogger()` to return a `FileLogger` instead. The rest of the application does not notice - it asked for a `Logger`.
3. Count the lines you would write to add ten more services. That number is why the next chapter exists.

## Further reading

- [What Is a DI Container?](https://doc.nette.org/dependency-injection/container)
- [What is a Service Locator, and why it is not this](https://doc.nette.org/dependency-injection/faq)

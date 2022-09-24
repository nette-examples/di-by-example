Dependency Injection by Example
===============================

Hi! 👋

Years ago I joined a team, opened the codebase, and wrote a little throwaway test to see what a class did. It charged my credit card. Not a test card - mine. The class had looked completely harmless: build the object, call the method. Nothing in its signature suggested that somewhere three layers down it would reach for a global payment gateway that happened to be pointed at production.

That afternoon taught me more about dependency injection than any article had. Not because DI is complicated - it is genuinely simple, three rules and you have it - but because I finally understood what it is *for*. Code that hides where it gets things from will eventually surprise you, and it will pick the worst possible moment.

So this course starts there. Not with a container, not with a config file - with the reason any of it exists.

**A warning about the shape of this repository:** the first six chapters do not use a single library. No `composer require`, no framework, no magic. Just PHP and the habits that make code honest. Only once you have felt why hand-wiring objects gets tedious does [Nette DI](https://doc.nette.org/dependency-injection) show up - and by then it is not a mysterious tool, it is a machine that writes the code you were already writing by hand.


The three rules
---------------

The whole thing fits in three sentences. Everything else in this course is a consequence of them.

1. **Let it be passed to you.** Everything a class needs must be handed to it. Do not go looking for it in a global, a static, a singleton or a constant.
2. **Take what's yours.** Accept your own dependencies, not the dependencies of your dependencies. If you need to log, ask for a logger - not for the path to the log file.
3. **Let the factory handle it.** When a class with dependencies has to be created on demand, delegate creating it to a factory instead of calling `new` with a pile of arguments.

That is it. That is dependency injection. The rest is convenience.

And one modern bonus that surprised me: code that declares its dependencies is dramatically easier for **tools** to work with. PHPStan can check it because there are types to check. Your IDE can refactor it because there are no magic strings to miss. And AI agents can navigate it, because a constructor answers "what does this class talk to?" in one line instead of sending them hunting through the codebase for a static call. I have watched an agent fix a bug in a DI-shaped project in a minute and get thoroughly lost in a facade-shaped one. Honest code was always good practice; now it is also a productivity multiplier.

Nette DI leans into this: the configuration format has a linter, so a broken config fails in CI instead of at runtime; the container is compiled into plain PHP that holds the whole service graph, so a tool can read what depends on what instead of inferring it; and PhpStorm has several plugins for NEON. Tooling that can see your wiring is worth more than tooling that has to guess at it.


Requirements
------------

PHP 8.4 or newer.


Installation
------------

```shell
git clone https://github.com/nette-examples/di-by-example
cd di
composer install
```

The first six chapters run without `composer install` too - they have nothing to install.


How to use this course
----------------------

Chapters in the first part come in pairs. Run the broken version first:

```shell
php 01-hidden-dependencies/bad.php
```

It is a working program that does something you would not want. Read its output, read the code, feel the problem - and only then run `good.php`, which fixes it. The `diff` between the two files is, honestly, the whole lesson; the readme is just me talking around it.

Some chapters end with a test, because that is where the difference between the two designs is easiest to feel: both tests pass, and one of them costs a great deal more than the other. There is no testing framework anywhere in this repository. `test()` is five lines at the top of the file that print `[PASS]` or `[FAIL]` and exit non-zero on failure, and that is on purpose - testability is something a design gives you, not something a library sells you.

From the container chapters onward there is a single `example.php` per chapter, plus a `config.neon`, and occasionally a second script that deliberately blows up, or swaps a service out, so you can see what that looks like.

Go in order. Nothing in a chapter uses anything a later chapter introduces.


Chapters
--------

**Foundations** - plain PHP, no library at all. This is the part that matters.

| # | Chapter | What it covers |
|---|---------|----------------|
| 01 | [Hidden Dependencies](01-hidden-dependencies) | A class that pretends it needs nothing. **Rule #1** |
| 02 | [Take What's Yours](02-take-whats-yours) | Carrying someone else's dependencies, interfaces, and what a test costs. **Rule #2** |
| 03 | [Global State and Singletons](03-global-state) | A green test that charges your credit card. Run it twice |
| 04 | [Constructor Hell](04-constructor-hell) | Why base classes hurt, and what composition fixes |
| 05 | [Factories](05-factories) | Creating objects that have dependencies. **Rule #3** |
| 06 | [A Container, Written by Hand](06-hand-made-container) | You write a DI container. It fits on one screen |

**The container** - now, and only now, Nette DI.

| # | Chapter | What it covers |
|---|---------|----------------|
| 07 | [The Container, Generated](07-nette-container) | The same container, compiled from `config.neon` |
| 08 | [Autowiring](08-autowiring) | Matching by type, conflicts, collections, optional dependencies |
| 09 | [Configuration](09-configuration) | Parameters, setup, one config overriding another - and why NEON and not YAML |
| 10 | [Generated Factories](10-generated-factories) | Write the interface, get the implementation |

**Real-world**

| # | Chapter | What it covers |
|---|---------|----------------|
| 11 | [Registering Services Automatically](11-search) | `search`, so the config stops growing with every class |
| 12 | [Tags and Decorators](12-tags-and-decorator) | Collecting and configuring whole groups of services |
| 13 | [Lazy Services and the Container API](13-lazy-and-container-api) | Deferred creation, and the methods worth knowing |

**Under the hood**

| # | Chapter | What it covers |
|---|---------|----------------|
| 14 | [Writing a Compiler Extension](14-compiler-extension) | Your own config section, wiring things at compile time |


Checking the examples
---------------------

```shell
composer check
```

Runs the NEON linter, static analysis, and a script that verifies the output printed in each chapter still matches what its scripts actually produce.


Further reading
---------------

- [Dependency Injection documentation](https://doc.nette.org/dependency-injection)
- [What is Dependency Injection?](https://doc.nette.org/dependency-injection/introduction) - the text that made it click for me
- [Global State and Singletons](https://doc.nette.org/dependency-injection/global-state) - and the one that made me go clean up
- [Nette forum](https://forum.nette.org)

The credit card story is not original, by the way. It comes from Miško Hevery, whose writing on global state and testability is the foundation most of this course stands on.

Happy injecting!

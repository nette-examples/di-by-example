# 03 · Global State and Singletons

The chapter where a test charges your credit card. Run it twice and watch your money leave.

**You will learn:**

- what global state actually costs, by losing money to it
- why singletons are global state wearing a nicer jacket
- how a signature that tells the truth makes the accident impossible
- why one of these two designs can only be tested by spending the money
- when reaching for something global is still fine

## Run it

```shell
php 03-global-state/bad.php
php 03-global-state/bad.php    # yes, again - watch the balance
php 03-global-state/good.php
```

## The story

New job, second day, large codebase. I was told to add a feature to the billing area, and like a good citizen I started by writing a tiny test to see what the existing code did:

```php
function testCreditCardCharge(): void
{
	$card = new CreditCard('1234567890123456');
	$card->charge(100);
}
```

My card number. My actual card, because that was the one in my wallet and I only wanted to see what the method printed.

I ran it a few times, the way you do when you are exploring. Then my phone buzzed. Then it buzzed again.

`temp/bank-account.txt` in this chapter is that phone. Run `bad.php` twice and watch the balance go from $500 to $400 to $300. Nothing in the test suggests money is involved. Nothing in `CreditCard`'s API suggests it either:

```php
$card = new CreditCard('1234567890123456');   // just a number, surely
$card->charge(100);                            // charge what? to where?
```

## The pain

Here is the entire mechanism, and it is only four lines:

```php
public function charge(int $amount): void
{
	PaymentGateway::getInstance()->charge($this->number, $amount);
}
```

`CreditCard` never asked for a payment gateway. It never received one. It simply *reached out* and got the one instance that exists in the process - which, in that codebase, was configured against production.

This is the same lie chapter 01 told with a constant, and the two are worth reading as one lesson. There, `Logger` reached for a path and the bill was an afternoon of reading source code. Here, `CreditCard` reaches for a payment provider and the bill arrives on your statement. One mechanism; the size of the damage depends entirely on what happens to be at the other end of the wire.

Einstein called quantum entanglement "spooky action at a distance", and that is exactly what this is: two pieces of code affecting each other with no visible connection between them. You can read the whole test, the whole `CreditCard` class signature, and still have no idea that a payment provider is on the other end. The wire is invisible.

The class is, in the phrase I have never been able to unhear, a **pathological liar**. Its API says "I am a value object holding a card number". Its body talks to a bank.

And the consequences are not just about surprise:

- **You cannot test it honestly.** There is no seam, so the only observable effect of `charge()` is the balance itself. `bad.php` writes exactly that test, and it goes green *because* the money moved. To avoid the charge you would have to modify the singleton, globally, and hope nothing else in the test suite noticed.
- **You cannot reason about order.** Somewhere there is an `init()` that must run before the first `getInstance()`, and nothing enforces it. Miss it and you get an exception from a place unrelated to the mistake.
- **You cannot trust a reading.** Anyone, anywhere, at any time, may have swapped what the singleton holds. `Article::setDb($testDb)` in one corner changes where a save lands in another.

I should be careful about how smug I sound here, because I wrote a *lot* of this code. My first serious project had a `Config::getInstance()`, a `Db::getInstance()` and a `Logger::getInstance()`, and I was proud of them. They felt like architecture. They were a way of not deciding who needed what.

## The fix

Say it out loud, in the signature:

```php
public function charge(PaymentGateway $gateway, int $amount): void
{
	$gateway->charge($this->number, $amount);
}
```

That is the entire change, and look at what it does to the newcomer writing that test. They cannot call `charge()` any more without answering one question: *which gateway?* And the honest answer, in a test, is a fake one - which can also remember what it was asked to do:

```php
$gateway = new FakePaymentGateway;
$card->charge($gateway, 100);

test('the gateway was asked for $100', $gateway->charges === [['1234567890123456', 100]]);
test('no money moved', (int) file_get_contents($account) === $before);
```

Compare that with the test in `bad.php`. This one asserts *more* - it checks the card number and the amount that reached the gateway, not just that a number somewhere went down - and it costs nothing. Run `good.php` and the balance is untouched, not because I remembered to be careful, but because carelessness stopped being available. That is the difference between a convention and a design.

`test()` there is five lines at the top of the file: print `[PASS]` or `[FAIL]`, and `exit(1)` when it fails. A testing framework would give you nicer output and a runner; it would not have given you the seam. The seam is the parameter.

## But singletons guarantee one instance!

They do, and that need is usually real: you want one database connection, not forty. The mistake is bundling *"there is one of these"* together with *"anyone can grab it from anywhere"*. Those are separate concerns, and the second one is the poison.

Hand the uniqueness problem to a DI container - which is exactly where this course is heading - and you get one instance of the class, created once and passed to whoever declares that they need it. The class itself goes back to doing its job: no `getInstance()`, no static property, no responsibility for its own uniqueness. It gets *smaller*, and it becomes testable for free.

Global constants deserve the same look. `M_PI` is a universal truth and fine. `LOG_FILE`, which we met in chapter 01, is a hidden dependency wearing a constant's clothing.

This is also my one real disappointment with Laravel, a framework I otherwise enjoyed using. Facades are this pattern with excellent ergonomics: `DB::insert(...)` reads beautifully and every class that uses it lies about what it needs. The bill arrives later - in the test suite, in the onboarding, in the refactor you postpone because nobody can see what depends on what.

## When global state is fine

I do not want to leave you paranoid, because there is a legitimate corner:

- **Debugging.** Dumping a variable, starting a timer, logging a line while hunting a bug. These are temporary and get deleted; they are not part of the design.
- **Deterministic functions.** `strlen()`, `Closure::fromCallable()`, `str_pad()` - same input, same output, no hidden state. Static is not the problem; *mutable* static is.
- **Invisible internal caching**, like PHP compiling a regex once and reusing it. You cannot observe it and it cannot surprise you.

The rule of thumb I use: if a `static` can be *written to*, someone eventually will, at the worst possible time.

## Output

### bad.php

```
[PaymentGateway] charged $100 to card 1234567890123456
...
[PASS] the card was charged $100

The test passes. It also spent $100 to find that out.
Run it again. And again. The balance keeps dropping.
```

### good.php

```
[FakePaymentGateway] would have charged $100 to card 1234567890123456
[PASS] the gateway was asked for $100
[PASS] no money moved

The test asserts more than the one in bad.php and costs nothing.
Not because I was careful. Because being careless was not an option.
```

## Try it yourself

1. Run `bad.php` five times in a row and open `temp/bank-account.txt`. Delete the file to reset it to $500.
2. In `bad.php`, try to make the test use a harmless gateway *without modifying `CreditCard` or `PaymentGateway`*. There is no way in - that is the whole point.
3. In `good.php`, swap `FakePaymentGateway` for `new RealPaymentGateway(__DIR__ . '/temp/bank-account.txt')`. The money moves again, but now the line that spends it says so.

## Further reading

- [Global State and Singletons](https://doc.nette.org/dependency-injection/global-state)
- [DI FAQ: when is it better not to use DI?](https://doc.nette.org/dependency-injection/faq)

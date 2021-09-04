# TinyProxy

A small tool to help break recursive dependency chains by creating fully compatible proxy objects on the fly. It can
also be used to defer instantiation logic until an object is first accessed. It works by generating the PHP code of the
proxy class (which you can then `eval` or persist/require) via Reflection. This class is instantiated with nothing but a
constructor function that returns the actual object. Currently, this package only covers the raw code generation.
Caching to disk, or coming up with OOPy wrappers is up to consumers for now.

Example:

```php

use Noem\TinyProxy\TinyProxy;

class User{

    public function __construct(public string $name, public int $age) {}

}

$php = TinyProxy::generateCode(User::class);
$proxyClassName = TinyProxy::proxyClassName(User::class)
eval($php);
$proxy = new $proxyClassName(fn() => new User('John', 42));

assert( $proxy instanceof User ); // true
$name = $proxy->name // 'John'

```

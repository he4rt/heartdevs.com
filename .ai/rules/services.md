---
paths:
  - 'app-modules/integration-*/**'
  - 'app-modules/*/src/**/Contracts/**'
  - 'app/Contracts/**'
  - 'app/Services/**'
---

# External services

Every third-party service that the app reaches over the network (a mailer, an AI provider, a payment platform, an OAuth API) sits behind a contract, so a change of vendor changes one class.

- The contract is an interface in the module that needs it, named after the role it plays, never after the vendor: `He4rt\<Module>\Contracts\PaymentProvider`. A contract that several modules share goes in `App\Contracts\Services`.
- The real implementation lives in an `integration-*` module and is named vendor + role: `StripePaymentProvider`. The HTTP transport (a Saloon connector and its requests) stays inside that module.
- The integration module binds the contract in its service provider. In the local and testing environments it binds a local implementation that needs no account, credentials or network: `Local<Role>` when it still does the job another way, `Null<Role>` when it answers nothing.
- Everything else depends on the contract, never on the implementation. Inject it into constructors, controller methods and form request `authorize()`, or `resolve(PaymentProvider::class)` where nothing can be injected.
- The contract speaks the app's language: the methods and shapes that the feature needs (`charge(Money $amount): Payment`), with a Data class or `@phpstan-type` for every shape it returns. Vendor URLs, payloads and credentials stay inside the implementation.
- An implementation reads its credentials from `config/services.php`, only where it uses them, and asserts their type there. Nothing outside the implementation asks whether a service is configured: no `isAvailable()` or `isConfigured()`.
- Only the real implementation's own test fakes HTTP (`Saloon::fake()`, `Http::fake()`). Every other test runs against the local implementation, or mocks the contract when it needs specific answers.
- A class that only parses or computes talks to nobody and has no contract.

```php
namespace He4rt\Billing\Contracts;

interface PaymentProvider
{
    public function charge(Team $team, Money $amount): Payment;
}

// app-modules/integration-stripe/src/StripePaymentProvider.php
final readonly class StripePaymentProvider implements PaymentProvider
{
    // ...
}
```

# Incident: registration activation mail and recovery

## Goal

Document the registration bug, the confirmed root causes, and the recovery strategy for accounts created during registration but left without a usable activation flow.

## Executive summary

- The active incident is not "which organization was selected".
- The symptom reported by the client is that the account creation request returns a valid-looking result in Postal, but the user cannot be found or activated in Claroline.
- The reproduced failure on prod is a PHP fatal error: `Allowed memory size of 536870912 bytes exhausted`.
- Postal was confirmed to have accepted and marked the activation message as `SENT` for the reproduction address.
- The activation mail is sent before the user creation is fully flushed by Doctrine.
- If the request later fails, the mail remains in Postal, but the Claroline row and token can be absent.
- That explains why repeated attempts can generate several mails for the same address without leaving a stable community record.
- The organization recovery section below is still useful for older rows created with `Compte prive`, but it is secondary to the current ticket.

## What was confirmed

### 1. The registration flow receives the selected organization

- The frontend sends `mainOrganization` in the registration payload.
- In the backend, `src/main/community/Controller/RegistrationController.php` reads that payload and resolves the organization by `vat` or `code`.

Relevant code:

- `src/main/community/Controller/RegistrationController.php`
- `src/main/core/API/Serializer/User/UserSerializer.php`

### 2. The serializer now accepts `mainOrganization` during registration

- Before the fix, `UserSerializer` ignored `mainOrganization` when `Options::REGISTRATION` was active.
- That meant the user create flow could not set the selected organization directly during deserialization.
- The fix removes that guard so `mainOrganization` is now applied while the user object is being built.

Relevant code:

- `src/main/core/API/Serializer/User/UserSerializer.php`

### 3. The organization field is also stored in the profile facet

- The organization choice is not only present in `mainOrganization`.
- It is also stored in the profile facet `ORGANISATION / EMPLOYEUR`.
- `OrganizationFieldSubscriber` stores the organization UUID in the database and rehydrates it when the value is read back.

Relevant code:

- `src/main/core/Subscriber/Facet/OrganizationFieldSubscriber.php`
- `src/main/community/Validator/UserValidator.php`

### 4. Mail delivery was part of the same request path

- The registration request can still send a validation email in the same HTTP request.
- On this environment, mail goes through Postal.
- If Postal is slow or unhealthy, the request can still fail by exceeding the PHP execution limit.

Relevant code:

- `src/main/core/Library/Mailing/Client/PostalRequestClient.php`
- `src/main/core/Library/Mailing/Client/PostalMailer.php`

Production evidence confirms that Postal is not the immediate cause for the reproduction:

- Postal displayed the activation message for `admin@konsept.ch` as `SENT` at `13:47`.
- The link contained an activation hash.
- No row existed in `claro_user` for the address or for the hash.
- Therefore the message was sent before the user creation was durable.

## Root cause summary

### Activation-mail bug

The bug is not the selected organization. The bug is the point where the activation email is emitted:

- `Crud::create()` dispatches the `preCreate` subscriber before the entity is flushed.
- `UserSubscriber::preCreate()` sends the activation email in that `preCreate` phase.
- The user object is persisted only after the mail has already been sent.
- `persist()` only registers the entity in Doctrine; it does not write the row immediately.
- `UserSubscriber::preCreate()` starts a flush suite and calls `endFlushSuite()` only after the mail, notifications and main-organization initialization.
- The actual flush can therefore fail after the mail has been sent.
- If a later step fails, the mail remains in Postal but the Claroline row and activation hash are not guaranteed to survive.

The deployed order is currently:

```text
generate activation hash
send activation mail
persist user
process notifications
set main organization
endFlushSuite -> Doctrine flush
serialize response
```

### Memory exhaustion

Production `prod.log` contains repeated fatal errors at the `512M` limit. One occurrence points to:

```text
src/main/core/API/Serializer/User/OrganizationSerializer.php:63
```

That line reads a scalar organization property, but Doctrine may have to initialize the organization proxy before returning it. The failure can therefore be caused by the object graph loaded around the organization, not by `maxUsers`.

The registration controller also serializes the complete user after creation:

```php
return new JsonResponse($this->serializer->serialize($user), 204);
```

This can load organization relations and other associations after the flush. The current logs do not prove whether every fatal occurs during `endFlushSuite()` or during response serialization. Both stages must be separated when testing.

The relevant production organizations have `maxUsers = -1`; capacity is not the observed cause.

### Why the previous fix can appear to work and fail later

The registration commit added `Options::NO_PERSONAL_WORKSPACE` and changed organization handling. It does not change the fact that the activation mail is sent in `preCreate`, nor does it remove every organization serialization path.

The same code can pass a validation test and later fail in production when the persisted data, loaded relations or response graph are larger.

### Why this can look like duplicate account creation

This failure mode explains the ticket details:

- the same email can appear several times in Postal because each retry sends another message
- the community can stay empty because the request never reaches a successful commit
- the activation link can exist but still not open a usable account if the user row was rolled back before activation

## What changed in the code

### Registration flow

- `src/main/core/API/Serializer/User/UserSerializer.php`
  - allow `mainOrganization` to be deserialized during `Options::REGISTRATION`.
- `src/main/community/Controller/RegistrationController.php`
  - remove the extra `replace()` after creation.

### Mail transport

- `src/main/core/Library/Mailing/Client/PostalRequestClient.php`
  - send Postal requests with a short timeout so a bad endpoint fails fast.

### Doctrine performance mitigation

- `src/main/core/Entity/Organization/Organization.php`
- `src/main/core/Entity/User.php`
  - `userOrganizationReferences` uses `EXTRA_LAZY`.
  - This reduces the risk of hydrating a huge collection during registration.

## What to check for the current ticket

Use these checks first when the client reports "Postal shows the mail, but Claroline has no trace of the user".

### 1. Confirm whether the user row exists for the real email

```sql
SELECT
    id,
    mail,
    creation_date,
    is_enabled,
    is_removed,
    is_locked,
    is_mail_validated,
    email_validation_hash,
    reset_password
FROM claro_user
WHERE mail = '<EMAIL_FROM_THE_TICKET>';
```

Interpretation:

- no row means the create flow likely failed after the email was already sent
- one row with `is_enabled = 0` and a non-null validation hash means the account exists but is still waiting for activation
- multiple rows for the same email means the frontend retried the failing request and created several attempts
- if the row exists but the activation link still fails, that is a separate bug from organization recovery

### 2. Check the mail log for the same address

```bash
rg -n "<EMAIL_FROM_THE_TICKET>|Email sent to|Fail to send email to|Transport" var/log/email.log
```

This is the fastest way to confirm whether the backend believed it sent the message.

For a link that cannot be activated, search for the exact token copied from the URL:

```sql
SELECT
    id,
    mail,
    creation_date,
    is_enabled,
    is_mail_validated,
    email_validation_hash,
    reset_password
FROM claro_user
WHERE email_validation_hash = '<TOKEN_FROM_ACTIVATION_LINK>';
```

If both the email query and the token query return no rows while Postal marks the message as `SENT`, the user creation failed after the message was generated.

### 3. Check the request path in code

- `src/main/app/API/Crud.php`
  - `preCreate` runs before the flush
- `src/main/community/Subscriber/Crud/UserSubscriber.php`
  - the activation mail is sent before the final flush
- `public/index.php`
  - HTTP requests are capped at `512M` in this branch
- `src/main/core/Manager/MailManager.php`
  - the activation link is built from `resetPasswordHash`
- `src/main/core/Controller/AuthenticationController.php`
  - activation depends on a user being found by hash
- `src/main/core/API/Serializer/User/UserSerializer.php`
  - the create response still serializes the created user, which can force Doctrine to walk a large object graph

### What can be repaired safely

Affected users are usually repairable if one of these sources exists:

- the profile facet `ORGANISATION / EMPLOYEUR`,
- an already-linked secondary organization,
- or another trusted persisted source.

In the local data we checked:

- `10` users were created with `Compte prive` as main organization in the sample analyzed for this incident.
- `1` user was repairable automatically because the chosen organization already existed in the profile facet and was resolvable.
- `9` users had no directly usable secondary organization in the `user_organization` relation and would need profile-facet-based recovery or manual review.

### What should not be guessed

- Do not assign a new main organization by guessing from the email domain alone.
- Do not mass-correct accounts without a trusted organization source.
- If the profile facet is missing or invalid, manual review is the safe fallback.

## Recovery strategy

### Preferred order

1. Identify all users whose main organization is `Compte prive`.
2. For each user, try to resolve the original organization from `ORGANISATION / EMPLOYEUR`.
3. If that resolves to a real organization, promote it to main.
4. If the profile facet does not resolve, check whether another organization is already linked.
5. If no trusted source exists, leave the account for manual correction.

### Why the profile facet is important

- The selected organization is persisted there as an organization UUID.
- That means the original choice is often recoverable even when the user currently displays `Compte prive`.
- This is the most reliable source we found locally for recovering the intended organization.

## Local evidence gathered

Examples from local tests:

- `rex3@test3.ch`
  - main organization: `Commune de Lavigny`
  - this is a correct account
- `rex2@test2.ch`
  - main organization: `Compte prive`
  - profile facet resolves to `Commune de Le Lieu`
- `rex@test2.ch`
  - main organization: `Compte prive`
  - profile facet resolves to `Commune de Lussy-sur-Morges`
- `rex@test.ch`
  - main organization: `Compte prive`
  - profile facet resolves to `Commune de Bussigny`

This is the key practical finding:

- the bad account is not necessarily unrecoverable
- in many cases the intended organization is already stored and can be recovered from the profile facet

## Operational recommendation

- For future registrations:
  - keep the serializer-based assignment
  - keep the Postal timeout
  - move mail sending out of the pre-flush path; send it from a post-create/post-flush hook
  - reduce the registration response to a minimal payload instead of serializing the complete user graph
  - instrument `endFlushSuite()` and response serialization separately before changing the memory limit
  - treat a higher `memory_limit` as a temporary mitigation only
- For existing users:
  - generate a report of all accounts with `Compte prive` as main organization
  - split them into:
    - auto-repairable from profile facet
    - auto-repairable from existing linked organization
    - manual review
- For the step-by-step SQL workflow, see [runbook_registration_private_org_recovery.md](./runbook_registration_private_org_recovery.md)

## Solution

La solution mise en place corrige l’affectation de l’organisation principale lors de l’inscription :

- `mainOrganization` envoyé par le frontend est maintenant désérialisé avec l’option `Options::REGISTRATION` dans `UserSerializer`.
- L’organisation sélectionnée est résolue à partir de son `vat` ou de son `code`, puis appliquée au compte avant sa persistance.
- Le choix est également conservé dans le facet `ORGANISATION / EMPLOYEUR` sous forme d’UUID. Ce facet constitue une source fiable pour récupérer l’organisation d’origine des comptes déjà créés avec `Compte prive`.
- La relation `userOrganizationReferences` est configurée en `EXTRA_LAZY` afin de limiter le chargement de collections volumineuses pendant l’inscription.
- Les appels à Postal utilisent un timeout court pour éviter qu’un service mail indisponible ne bloque longuement la requête.
- Le `replace()` supplémentaire après la création a été supprimé du contrôleur d’inscription.

Les premiers contrôles indiquent que l’organisation sélectionnée est désormais correctement affectée. Pour les comptes historiques dont l’organisation principale reste `Compte prive`, la récupération doit être faite uniquement lorsqu’une source persistée et fiable existe : facet `ORGANISATION / EMPLOYEUR` ou organisation secondaire déjà liée. Les comptes sans source fiable restent en revue manuelle ; l’organisation ne doit pas être déduite du domaine de l’adresse e-mail.

Le détail des requêtes de diagnostic, du classement des comptes, des corrections transactionnelles et des vérifications post-traitement est disponible dans le [runbook de récupération](./runbook_registration_private_org_recovery.md).

## Tests to keep green

- `src/main/core/Tests/Unit/API/Serializer/User/UserSerializerTest.php`
- `src/main/community/Tests/Unit/Controller/RegistrationControllerTest.php`
- `src/main/core/Tests/Unit/Library/Mailing/Client/PostalMailerTest.php`

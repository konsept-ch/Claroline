# Runbook: recover users created with `Compte prive` as main organization

## Purpose

Recover accounts whose main organization was incorrectly left on the platform default organization `Compte prive`.

This runbook is for production or pre-production databases where the issue has already been confirmed.

## Scope

Use this procedure when:

- a user was created through registration,
- the main organization is `Compte prive`,
- and you need to determine whether the intended organization can be recovered safely.

Do not use this runbook to guess organizations from email domains or naming conventions.

## Important facts

- `claro__organization.is_default = 1` is the platform default organization, displayed as `Compte prive`.
- The selected organization is usually stored in the profile facet `ORGANISATION / EMPLOYEUR`.
- `user_organization` contains the main organization link and any secondary organization links.
- If a user has a secondary non-default organization, that user may be repairable without manual investigation.
- If the profile facet resolves to a real organization, that is the preferred source for recovery.
- Only correct active accounts in this runbook:
  - `claro_user.is_enabled = 1`
  - `claro_user.is_removed = 0`
  - `claro_user.is_locked = 0`

## Preconditions

Before modifying anything:

- confirm the affected database and environment,
- take a backup or snapshot,
- run all diagnostic queries in read-only mode first,
- export the result set before making changes.

## Step 1. Confirm the default organization

```sql
SELECT id, uuid, name, code, is_default, is_public
FROM claro__organization
WHERE is_default = 1;
```

Expected result:

- one row
- `name = Compte prive`
- `code = DEFAUT-PRIVÉ`

## Step 2. Count affected active accounts

```sql
SELECT COUNT(DISTINCT u.id) AS total_private_main_users
FROM claro_user u
JOIN user_organization uo
    ON uo.user_id = u.id
   AND uo.is_main = 1
JOIN claro__organization o
    ON o.id = uo.oganization_id
WHERE o.is_default = 1
  AND u.is_enabled = 1
  AND u.is_removed = 0
  AND u.is_locked = 0
;
```

This returns the number of active users whose main organization is the default private organization.
If you need a recent-only slice for incident analysis, add a date filter locally to the same query.

## Step 3. Split active accounts into repairable and manual cases

### 3A. Repairable through an already-linked secondary organization

```sql
SELECT
    u.id,
    u.uuid,
    u.first_name,
    u.last_name,
    u.mail,
    u.creation_date,
    GROUP_CONCAT(
        DISTINCT CONCAT(o.name, ' [main=', uo.is_main, ']')
        ORDER BY uo.is_main DESC, o.name
        SEPARATOR ' | '
    ) AS organizations
FROM claro_user u
JOIN user_organization uo
    ON uo.user_id = u.id
JOIN claro__organization o
    ON o.id = uo.oganization_id
WHERE u.is_enabled = 1
  AND u.is_removed = 0
  AND u.is_locked = 0
GROUP BY u.id
HAVING
    SUM(CASE WHEN o.is_default = 1 AND uo.is_main = 1 THEN 1 ELSE 0 END) = 1
    AND SUM(CASE WHEN o.is_default = 0 THEN 1 ELSE 0 END) > 0
ORDER BY u.creation_date DESC;
```

These users have a non-default organization already present in `user_organization`.

### 3B. Repairable through the profile facet

```sql
SELECT
    u.id,
    u.uuid,
    u.first_name,
    u.last_name,
    u.mail,
    u.creation_date,
    profile_org.organization_uuid,
    o.name AS organization_name,
    o.code AS organization_code
FROM claro_user u
JOIN user_organization uo
    ON uo.user_id = u.id
   AND uo.is_main = 1
JOIN claro__organization main_org
    ON main_org.id = uo.oganization_id
JOIN (
    SELECT
        fv.user_id,
        MAX(
            CASE
                WHEN f.name = 'ORGANISATION / EMPLOYEUR' THEN fv.field_value
                ELSE NULL
            END
        ) AS organization_uuid
    FROM claro_field_facet_value fv
    JOIN claro_field_facet f
        ON f.id = fv.fieldFacet_id
    GROUP BY fv.user_id
) profile_org
    ON profile_org.user_id = u.id
LEFT JOIN claro__organization o
    ON o.uuid = REPLACE(REPLACE(profile_org.organization_uuid, '"', ''), '\\', '')
WHERE u.is_enabled = 1
  AND u.is_removed = 0
  AND u.is_locked = 0
  AND main_org.is_default = 1
  AND profile_org.organization_uuid IS NOT NULL
ORDER BY u.creation_date DESC;
```

Use this output to recover the organization that was selected during registration.

### 3C. Manual review only

```sql
SELECT
    u.id,
    u.uuid,
    u.first_name,
    u.last_name,
    u.mail,
    u.creation_date,
    main_org.name AS current_main_org
FROM claro_user u
JOIN user_organization uo
    ON uo.user_id = u.id
   AND uo.is_main = 1
JOIN claro__organization main_org
    ON main_org.id = uo.oganization_id
WHERE u.is_enabled = 1
  AND u.is_removed = 0
  AND u.is_locked = 0
  AND main_org.is_default = 1
  AND NOT EXISTS (
      SELECT 1
      FROM claro_field_facet_value fv
      JOIN claro_field_facet f
        ON f.id = fv.fieldFacet_id
      WHERE fv.user_id = u.id
        AND f.name = 'ORGANISATION / EMPLOYEUR'
  );
```

These users do not have an immediately usable recovery source in the data checked by this runbook.

### 3D. Known valid organization labels

Some profile values look like labels in the UI but are still valid organization choices.
Do not exclude them from profile recovery.

```sql
SELECT
    u.id,
    u.mail,
    u.creation_date,
    main_org.name AS current_main_org,
    profile_org.organization_uuid AS raw_profile_value,
    CASE
        WHEN profile_org.organization_uuid IS NULL THEN 'missing'
        WHEN profile_org.organization_uuid IN ('', '-') THEN 'placeholder'
        ELSE 'unresolved'
    END AS profile_value_status
FROM claro_user u
JOIN user_organization uo
    ON uo.user_id = u.id
   AND uo.is_main = 1
JOIN claro__organization main_org
    ON main_org.id = uo.oganization_id
LEFT JOIN (
    SELECT
        fv.user_id,
        MAX(
            CASE
                WHEN f.name = 'ORGANISATION / EMPLOYEUR' THEN fv.field_value
                ELSE NULL
            END
        ) AS organization_uuid
    FROM claro_field_facet_value fv
    JOIN claro_field_facet f
        ON f.id = fv.fieldFacet_id
    GROUP BY fv.user_id
) profile_org
    ON profile_org.user_id = u.id
WHERE u.is_enabled = 1
  AND u.is_removed = 0
  AND u.is_locked = 0
  AND main_org.is_default = 1
  AND (
      profile_org.organization_uuid IN ('', '-')
  )
ORDER BY u.mail;
```

These rows are manual-review candidates only when the profile value is missing or blank. Do not use the UI label itself to reject a row.

### 3E. Manual review enrichment

Use this query to compare manual-review candidates against the rest of the active population and spot recurring patterns that may justify a trusted correction rule.

```sql
SELECT
    u.id,
    u.mail,
    u.creation_date,
    main_org.name AS current_main_org,
    LEFT(u.mail, LOCATE('@', u.mail) - 1) AS mail_local_part,
    SUBSTRING_INDEX(u.mail, '@', -1) AS mail_domain,
    profile_org.organization_uuid AS raw_profile_value,
    profile_status.profile_value_status,
    secondary_orgs.secondary_org_count,
    secondary_orgs.secondary_organizations
FROM claro_user u
JOIN user_organization uo
    ON uo.user_id = u.id
   AND uo.is_main = 1
JOIN claro__organization main_org
    ON main_org.id = uo.oganization_id
LEFT JOIN (
    SELECT
        fv.user_id,
        MAX(
            CASE
                WHEN f.name = 'ORGANISATION / EMPLOYEUR' THEN fv.field_value
                ELSE NULL
            END
        ) AS organization_uuid
    FROM claro_field_facet_value fv
    JOIN claro_field_facet f
        ON f.id = fv.fieldFacet_id
    GROUP BY fv.user_id
) profile_org
    ON profile_org.user_id = u.id
LEFT JOIN (
    SELECT
        u.id AS user_id,
        CASE
            WHEN p.organization_uuid IS NULL THEN 'missing'
            WHEN p.organization_uuid IN ('', '-') THEN 'placeholder'
            ELSE 'unresolved'
        END AS profile_value_status
    FROM claro_user u
    LEFT JOIN (
        SELECT
            fv.user_id,
            MAX(
                CASE
                    WHEN f.name = 'ORGANISATION / EMPLOYEUR' THEN fv.field_value
                    ELSE NULL
                END
            ) AS organization_uuid
        FROM claro_field_facet_value fv
        JOIN claro_field_facet f
            ON f.id = fv.fieldFacet_id
        GROUP BY fv.user_id
    ) p
        ON p.user_id = u.id
) profile_status
    ON profile_status.user_id = u.id
LEFT JOIN (
    SELECT
        uo2.user_id,
        COUNT(DISTINCT CASE WHEN o2.is_default = 0 THEN o2.id END) AS secondary_org_count,
        GROUP_CONCAT(
            DISTINCT CASE
                WHEN o2.is_default = 0 THEN CONCAT(o2.name, ' [main=', uo2.is_main, ']')
                ELSE NULL
            END
            ORDER BY uo2.is_main DESC, o2.name
            SEPARATOR ' | '
        ) AS secondary_organizations
    FROM user_organization uo2
    JOIN claro__organization o2
        ON o2.id = uo2.oganization_id
    GROUP BY uo2.user_id
) secondary_orgs
    ON secondary_orgs.user_id = u.id
WHERE u.is_enabled = 1
  AND u.is_removed = 0
  AND u.is_locked = 0
  AND main_org.is_default = 1
  AND (
      profile_status.profile_value_status IN ('missing', 'placeholder')
      OR COALESCE(secondary_orgs.secondary_org_count, 0) = 0
  )
ORDER BY mail_domain, u.mail;
```

This is a comparison report, not an auto-correction rule. Use it to spot patterns and decide whether an external trusted source exists for the manual cases.

## Step 4. Dry-run classification

Run this query to classify each affected account:

```sql
SELECT
    u.id,
    u.mail,
    u.creation_date,
    main_org.name AS current_main_org,
    recovered_org.name AS recovered_org_from_profile,
    CASE
        WHEN recovered_org.id IS NOT NULL AND recovered_org.id <> main_org.id THEN 'repairable_from_profile'
        WHEN EXISTS (
            SELECT 1
            FROM user_organization uo2
            JOIN claro__organization o2 ON o2.id = uo2.oganization_id
            WHERE uo2.user_id = u.id
              AND o2.is_default = 0
              AND uo2.oganization_id <> main_org.id
        ) THEN 'repairable_from_secondary_org'
        ELSE 'manual_review'
    END AS recovery_status
FROM claro_user u
JOIN user_organization uo
    ON uo.user_id = u.id
   AND uo.is_main = 1
JOIN claro__organization main_org
    ON main_org.id = uo.oganization_id
LEFT JOIN (
    SELECT
        fv.user_id,
        MAX(
            CASE
                WHEN f.name = 'ORGANISATION / EMPLOYEUR' THEN fv.field_value
                ELSE NULL
            END
        ) AS organization_uuid
    FROM claro_field_facet_value fv
    JOIN claro_field_facet f
        ON f.id = fv.fieldFacet_id
    GROUP BY fv.user_id
) profile_org
    ON profile_org.user_id = u.id
LEFT JOIN claro__organization recovered_org
    ON recovered_org.uuid = REPLACE(REPLACE(profile_org.organization_uuid, '"', ''), '\\', '')
WHERE u.is_enabled = 1
  AND u.is_removed = 0
  AND u.is_locked = 0
  AND main_org.is_default = 1
ORDER BY u.creation_date DESC;
```

Use the result to build the correction batch.

### 4B. Manual review details

Use this query to get more context on accounts that remain unresolved after the profile and secondary-organization checks.
These are the active accounts that still do not have a trustworthy recovery source in the database.

```sql
SELECT
    u.id,
    u.uuid,
    u.first_name,
    u.last_name,
    u.mail,
    u.creation_date,
    main_org.name AS current_main_org,
    profile_org.organization_uuid AS profile_organization_uuid,
    recovered_org.name AS recovered_org_from_profile,
    secondary_orgs.secondary_org_count,
    secondary_orgs.secondary_organizations
FROM claro_user u
JOIN user_organization uo
    ON uo.user_id = u.id
   AND uo.is_main = 1
JOIN claro__organization main_org
    ON main_org.id = uo.oganization_id
LEFT JOIN (
    SELECT
        fv.user_id,
        MAX(
            CASE
                WHEN f.name = 'ORGANISATION / EMPLOYEUR' THEN fv.field_value
                ELSE NULL
            END
        ) AS organization_uuid
    FROM claro_field_facet_value fv
    JOIN claro_field_facet f
        ON f.id = fv.fieldFacet_id
    GROUP BY fv.user_id
) profile_org
    ON profile_org.user_id = u.id
LEFT JOIN claro__organization recovered_org
    ON recovered_org.uuid = REPLACE(REPLACE(profile_org.organization_uuid, '"', ''), '\\', '')
LEFT JOIN (
    SELECT
        uo2.user_id,
        COUNT(DISTINCT CASE WHEN o2.is_default = 0 THEN o2.id END) AS secondary_org_count,
        GROUP_CONCAT(
            DISTINCT CASE
                WHEN o2.is_default = 0 THEN CONCAT(o2.name, ' [main=', uo2.is_main, ']')
                ELSE NULL
            END
            ORDER BY uo2.is_main DESC, o2.name
            SEPARATOR ' | '
        ) AS secondary_organizations
    FROM user_organization uo2
    JOIN claro__organization o2
        ON o2.id = uo2.oganization_id
    GROUP BY uo2.user_id
) secondary_orgs
    ON secondary_orgs.user_id = u.id
WHERE u.is_enabled = 1
  AND u.is_removed = 0
  AND u.is_locked = 0
  AND main_org.is_default = 1
  AND recovered_org.id IS NULL
  AND COALESCE(secondary_orgs.secondary_org_count, 0) = 0
ORDER BY u.creation_date DESC;
```

This gives you the unresolved accounts with:

- the raw profile organization UUID if present,
- the resolved organization name if the UUID is parseable,
- the number of already-linked non-default organizations,
- the list of those secondary organizations if any exist.

## Step 5. Correct only repairable accounts

### Option A. Manual updates, one account at a time

Use this when you want maximum safety.

```sql
START TRANSACTION;

-- Example:
-- 1. insert or keep the correct organization link if needed
-- 2. mark the correct organization as main
-- 3. ensure the private default org is no longer main

COMMIT;
```

Because `user_organization` is the join table, the exact update depends on whether the target organization is already linked.

### Option B. Promote an already-linked secondary organization

Use this only for active users who have exactly one non-default organization already linked.
This is the batch for the `repairable_from_secondary_org` accounts.

```sql
START TRANSACTION;

CREATE TEMPORARY TABLE tmp_secondary_recovery AS
SELECT
    u.id AS user_id,
    u.mail,
    uo_main.id AS main_user_org_id,
    main_org.name AS current_main_org,
    secondary_orgs.secondary_org_id,
    secondary_orgs.secondary_org_name,
    secondary_orgs.secondary_org_count,
    secondary_orgs.secondary_organizations
FROM claro_user u
JOIN user_organization uo_main
    ON uo_main.user_id = u.id
   AND uo_main.is_main = 1
JOIN claro__organization main_org
    ON main_org.id = uo_main.oganization_id
   AND main_org.is_default = 1
JOIN (
    SELECT
        uo_secondary.user_id,
        COUNT(DISTINCT secondary_org.id) AS secondary_org_count,
        MAX(secondary_org.id) AS secondary_org_id,
        MAX(secondary_org.name) AS secondary_org_name,
        GROUP_CONCAT(
            DISTINCT CONCAT(secondary_org.name, ' [main=', uo_secondary.is_main, ']')
            ORDER BY uo_secondary.is_main DESC, secondary_org.name
            SEPARATOR ' | '
        ) AS secondary_organizations
    FROM user_organization uo_secondary
    JOIN claro__organization secondary_org
        ON secondary_org.id = uo_secondary.oganization_id
       AND secondary_org.is_default = 0
    GROUP BY uo_secondary.user_id
) secondary_orgs
    ON secondary_orgs.user_id = u.id
WHERE u.is_enabled = 1
  AND u.is_removed = 0
  AND u.is_locked = 0
  AND secondary_orgs.secondary_org_count = 1
  AND secondary_orgs.secondary_org_id <> uo_main.oganization_id;

SELECT
    mail,
    current_main_org,
    secondary_org_name AS expected_main_org,
    secondary_organizations
FROM tmp_secondary_recovery
ORDER BY mail;

UPDATE user_organization uo_main
JOIN tmp_secondary_recovery r
    ON r.main_user_org_id = uo_main.id
SET uo_main.oganization_id = r.secondary_org_id;

SELECT
    r.mail,
    r.current_main_org AS main_org_before,
    r.secondary_org_name AS expected_main_org,
    main_org.name AS main_org_after,
    main_org.code AS main_org_code_after,
    main_org.is_default AS is_default_after
FROM tmp_secondary_recovery r
JOIN user_organization uo
    ON uo.id = r.main_user_org_id
JOIN claro__organization main_org
    ON main_org.id = uo.oganization_id
ORDER BY r.mail;

DROP TEMPORARY TABLE tmp_secondary_recovery;

COMMIT;
```

If a user has more than one non-default organization, do not batch it here. Keep it in manual review.
Do not query `tmp_secondary_recovery` after the table has been dropped; rerun the preview block if you need the data again.

### Option C. Promote the resolved profile organization

Use this only for users whose profile facet resolves to a real organization.

This is the batch you use for the `repairable_from_profile` accounts in the active population.
Keep the active filters in place so you do not rewrite historical or disabled accounts.

```sql
START TRANSACTION;

CREATE TEMPORARY TABLE tmp_profile_recovery AS
SELECT DISTINCT
    u.id AS user_id,
    u.mail,
    uo_main.id AS main_user_org_id,
    uo_main.oganization_id AS current_org_id,
    main_org.name AS current_main_org,
    recovered_org.id AS recovered_org_id,
    recovered_org.name AS recovered_org_from_profile,
    EXISTS (
        SELECT 1
        FROM user_organization uo2
        WHERE uo2.user_id = u.id
          AND uo2.oganization_id = recovered_org.id
    ) AS recovered_org_already_linked
FROM claro_user u
JOIN user_organization uo_main
    ON uo_main.user_id = u.id
   AND uo_main.is_main = 1
JOIN claro__organization main_org
    ON main_org.id = uo_main.oganization_id
   AND main_org.is_default = 1
JOIN (
    SELECT
        fv.user_id,
        MAX(
            CASE
                WHEN f.name = 'ORGANISATION / EMPLOYEUR' THEN fv.field_value
                ELSE NULL
            END
        ) AS organization_uuid
    FROM claro_field_facet_value fv
    JOIN claro_field_facet f
        ON f.id = fv.fieldFacet_id
    GROUP BY fv.user_id
) profile_org
    ON profile_org.user_id = u.id
JOIN claro__organization recovered_org
    ON recovered_org.uuid = REPLACE(REPLACE(profile_org.organization_uuid, '"', ''), '\\', '')
WHERE u.is_enabled = 1
  AND u.is_removed = 0
  AND u.is_locked = 0
  AND profile_org.organization_uuid IS NOT NULL
  AND recovered_org.id <> uo_main.oganization_id
;

SELECT
    mail,
    current_main_org,
    recovered_org_from_profile
FROM tmp_profile_recovery
ORDER BY mail;

SELECT *
FROM tmp_profile_recovery
ORDER BY user_id;

UPDATE user_organization uo_main
JOIN tmp_profile_recovery r
    ON r.main_user_org_id = uo_main.id
SET uo_main.oganization_id = r.recovered_org_id
WHERE r.recovered_org_already_linked = 0;

UPDATE user_organization uo_main
JOIN tmp_profile_recovery r
    ON r.main_user_org_id = uo_main.id
SET uo_main.is_main = 0
WHERE r.recovered_org_already_linked = 1;

UPDATE user_organization uo_recovered
JOIN tmp_profile_recovery r
    ON r.user_id = uo_recovered.user_id
   AND r.recovered_org_id = uo_recovered.oganization_id
SET uo_recovered.is_main = 1;

SELECT
    r.mail,
    r.current_main_org AS main_org_before,
    r.recovered_org_from_profile AS expected_main_org,
    main_org.name AS main_org_after,
    main_org.code AS main_org_code_after,
    main_org.is_default AS is_default_after
FROM tmp_profile_recovery r
JOIN user_organization uo
    ON uo.id = r.main_user_org_id
JOIN claro__organization main_org
    ON main_org.id = uo.oganization_id
ORDER BY r.mail;

DROP TEMPORARY TABLE tmp_profile_recovery;

COMMIT;
```

This avoids MySQL error `#1093` by materializing the target set before updating `user_organization`.
It also covers the common VAL case where the recovered organization exists only in the profile facet and has not yet been linked in `user_organization`.

Do not revert to the previous `EXISTS`-based version: that version only handled users who already had the recovered organization linked and missed the profile-only case.
Do not query `tmp_profile_recovery` after the table has been dropped; rerun the preview block if you need the data again.

## Step 6. Verify after correction

Run the verification query before dropping the relevant temporary table in the same SQL session.
Do not start a fresh phpMyAdmin query against `tmp_profile_recovery` after the `DROP TEMPORARY TABLE` statement.
If you need the rows again, rerun the preview/update block first.

```sql
SELECT
    r.mail,
    r.current_main_org AS main_org_before,
    r.recovered_org_from_profile AS expected_main_org,
    main_org.name AS main_org_after,
    main_org.code AS main_org_code_after,
    main_org.is_default AS is_default_after
FROM tmp_profile_recovery r
JOIN user_organization uo
    ON uo.id = r.main_user_org_id
JOIN claro__organization main_org
    ON main_org.id = uo.oganization_id
ORDER BY r.mail;
```

Expected outcome:

- each corrected account no longer points to `Compte prive`
- each `mail` now matches the expected organization after correction

If you already dropped the temporary table, re-run the preview block for the branch you are checking before verifying again.

## Step 6B. Final mail-to-organization report

Use this query to see every affected `mail` with its current main organization after the run:

```sql
SELECT
    u.mail,
    u.creation_date,
    main_org.name AS main_org_name,
    main_org.code AS main_org_code,
    main_org.is_default
FROM claro_user u
JOIN user_organization uo
    ON uo.user_id = u.id
   AND uo.is_main = 1
JOIN claro__organization main_org
    ON main_org.id = uo.oganization_id
WHERE u.is_enabled = 1
  AND u.is_removed = 0
  AND u.is_locked = 0
  AND main_org.is_default = 0
ORDER BY u.mail;
```

This is the cleanest final check for the active profile corrections and any later secondary-org corrections.

### 6C. Final mail-to-organization report for profile repairs

Use this report after the profile branch to compare the repaired subset against the expected organization from `tmp_profile_recovery`:

```sql
SELECT
    r.mail,
    r.current_main_org AS main_org_before,
    r.recovered_org_from_profile AS expected_main_org,
    main_org.name AS main_org_after,
    main_org.code AS main_org_code_after,
    main_org.is_default AS is_default_after
FROM tmp_profile_recovery r
JOIN user_organization uo
    ON uo.id = r.main_user_org_id
JOIN claro__organization main_org
    ON main_org.id = uo.oganization_id
ORDER BY r.mail;
```

### 6D. Final mail-to-organization report for secondary repairs

Use this report after the secondary branch to compare the repaired subset against the expected organization from `tmp_secondary_recovery`:

```sql
SELECT
    r.mail,
    r.current_main_org AS main_org_before,
    r.secondary_org_name AS expected_main_org,
    main_org.name AS main_org_after,
    main_org.code AS main_org_code_after,
    main_org.is_default AS is_default_after
FROM tmp_secondary_recovery r
JOIN user_organization uo
    ON uo.id = r.main_user_org_id
JOIN claro__organization main_org
    ON main_org.id = uo.oganization_id
ORDER BY r.mail;
```

## Step 7. What to report to the client

Give the client three lists:

- accounts automatically repaired from the profile facet
- accounts automatically repaired from an existing secondary organization
- accounts requiring manual correction

That is the safest balance between completeness and avoiding incorrect changes.

## Validation on VAL

Use the VAL environment only as a scratchpad to validate the SQL flow before running it in production.
Keep preview, update, and verification in the same SQL session for each branch, and only drop the temporary table after the verification query has run.

Observed on VAL:

- `80` users had `Compte prive` as main organization in the latest VAL validation run.
- `2` users had a non-default secondary organization already linked in `user_organization`.
- at least one user was recoverable from the profile facet `ORGANISATION / EMPLOYEUR`.
- the remaining manual-review accounts need a separate trusted source; healthy accounts in the database are useful for comparison, but not enough on their own to guess a correction safely.

Important:

- the original join-based diagnostic queries can duplicate rows as soon as a user has several facet values;
- use the rewritten queries above before building any correction batch;
- validate on VAL first, then replay only the approved correction set on prod.

## Notes from local investigation

- The issue is real on the local dataset.
- The selected organization is recoverable from `ORGANISATION / EMPLOYEUR` for many accounts.
- One account in the local sample was already correct and should be excluded from any correction batch.
- The recovery source is better than guessing from the email domain.

## Related code references

- `src/main/community/Controller/RegistrationController.php`
- `src/main/core/API/Serializer/User/UserSerializer.php`
- `src/main/core/Subscriber/Facet/OrganizationFieldSubscriber.php`
- `src/main/community/Validator/UserValidator.php`
- `src/main/core/Library/Mailing/Client/PostalRequestClient.php`

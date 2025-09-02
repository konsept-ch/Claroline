**Template Translations**

- **Domain:** `template` domain JSON lives in `src/main/core/Resources/translations/template.<lang>.json` and provides UI labels, template-type names/descriptions, and placeholder descriptions. Example keys in English: `placeholders_info` (explains placeholders) at `src/main/core/Resources/translations/template.en.json:27`, and `define_as_default_for_type` at `src/main/core/Resources/translations/template.en.json:14`.
- **Types:** Template types use translation keys for name/description (e.g., `user_registration`, `user_registration_desc`) found in the same files.
- **Placeholders:** Each placeholder shown in the UI has a `..._desc` key in the `template` domain (e.g., `username_desc` at `src/main/core/Resources/translations/template.en.json:42`, `first_name_desc` at `src/main/core/Resources/translations/template.en.json:15`, `last_name_desc` at `src/main/core/Resources/translations/template.en.json:18`). Current-user variants are also translated (e.g., `current_user_first_name_desc` at `src/main/core/Resources/translations/template.en.json:52`).

**Frontend Usage**

- **Translator helper:** Components use `trans(key, placeholders?, domain?)` with the `template` domain: see `src/main/app/Resources/modules/intl/translation/index.js:53` for the `trans` export. The translator defers to a BazingaJsTranslationBundle instance if present or falls back to a local implementation.
- **Template form:** The placeholders table in the admin form is built from defaults plus type-specific placeholders. See `src/main/core/Resources/modules/administration/template/components/form.jsx:54-79` where default placeholders are rendered with `trans(
  `${placeholder}_desc`, {}, 'template'
)`. The info alert uses `trans('placeholders_info', {}, 'template')` at `src/main/core/Resources/modules/administration/template/components/form.jsx:33-39`.
- **Default placeholders:** Defined in `src/main/core/Resources/modules/administration/template/constants.js:1-12` as `DEFAULT_PLACEHOLDERS = ['platform_name','platform_url','date','datetime','username','first_name','last_name','civility']`. Each must have a matching `<placeholder>_desc` key in the `template` domain.
- **Type placeholders:** The form also lists `template.type.placeholders` (from the backend) and expects translation keys `<placeholder>_desc` for each (same domain).
- **Labels and meta:** Template type name and description are translation keys stored on the entity. See `src/main/core/Resources/modules/administration/template/components/page.jsx:22-37` using `trans(get(props.templateType, 'name'), {}, 'template')` and `trans(get(props.templateType, 'name')+'_desc', {}, 'template')`.

**Backend Logic**

- **Entities:**
  - `TemplateType` stores `name` (translation key), `type` (technical grouping), `placeholders` (array of additional placeholder names), and `defaultTemplate`. See `src/main/core/Entity/Template/TemplateType.php:29-73` and properties at `:25-47`.
  - `Template` holds `type`, `system` flag, and localized `TemplateContent` entries; see `src/main/core/Entity/Template/Template.php:29` and content accessors at `:79-120`.
- **Serialization to UI:** `TemplateTypeSerializer` exposes `name`, `type`, `placeholders`, `defaultTemplate` to the frontend at `src/main/core/API/Serializer/Template/TemplateTypeSerializer.php:14-28`. `TemplateSerializer` maps `contents[locale] = {title, content}` at `src/main/core/API/Serializer/Template/TemplateSerializer.php:37-66`.
- **Placeholder replacement:** Runtime values are injected by `PlaceholderManager::replacePlaceholders`:
  - Built-ins include platform data and current user info; mapping implemented at `src/main/core/Manager/Template/PlaceholderManager.php:58-71`.
  - Available placeholder names exported by `getAvailablePlaceholders()` at `src/main/core/Manager/Template/PlaceholderManager.php:29-45` serve as a reference; custom placeholders passed by callers are merged in (`$customPlaceholders`).
- **Profile facets (user fields):** User profile fields (like civility/grade) are stored as facet values in table `claro_field_facet_value` and exposed via entities `FieldFacetValue` and `FieldFacet`. The system can format facet values through `FacetManager::serializeFieldValue(...)`.
  - Facet values for a user can be loaded with `FieldFacetValueRepository::findPlatformValuesByUser($user)` at `src/main/core/Repository/Facet/FieldFacetValueRepository.php:23`.
  - The user serializer flattens them into `user.profile[<field_uuid>]` when requested (see `src/main/core/API/Serializer/User/UserSerializer.php:184-200`).
- **Template resolution:** `TemplateManager::getTemplate(typeName, placeholders, locale, mode)` loads the default template for a type and returns either `content` or `title` after replacement. Locale fallback logic and replacement happen in `src/main/core/Manager/Template/TemplateManager.php:78-101`.
- **Preview API:** `GET /apiv2/template_type/{id}/preview/{templateId}/{locale}` previews a template with placeholder replacement; see controller `src/main/core/Controller/APINew/Template/TemplateTypeController.php:109-121`.

**How It Fits Together**

- Admin creates/edits Templates per TemplateType. For each locale, `title` and `content` can include tokens like `%username%`, `%platform_name%`, `%date%`, etc.
- The UI shows which tokens are available by combining `DEFAULT_PLACEHOLDERS` with `TemplateType.placeholders` and pulls human descriptions from `template.<lang>.json` keys `<placeholder>_desc`.
- When sending/previewing, backend picks the right localized `TemplateContent`, applies locale fallback (platform default), and replaces tokens using both built-ins and any custom context placeholders provided by the caller.

**Civility Placeholder**

- UI listing: `%civility%` is included in the admin Templates placeholder table via `DEFAULT_PLACEHOLDERS`.
- Translations: `civility_desc` exists in `template.en.json:19` and `template.fr.json:19`.
- Value source: civility is resolved from user profile facets (table `claro_field_facet_value`). Mail sending now passes `'civility'` by reading the current user’s facet values and formatting them via `FacetManager`.
  - Implementation: `src/main/core/Manager/MailManager.php` constructor now receives `ObjectManager` and `FacetManager` and resolves civility in a private `resolveCivility(User)` method that searches the user’s `FieldFacetValue`s for a field whose label/name matches civility/civilité, then formats it with `FacetManager::serializeFieldValue(...)`.
  - Service wiring: see `src/main/core/Resources/config/services/manager.yml` where `MailManager` is injected with `ObjectManager` and `FacetManager`.

**Extending Templates**

- **Add a new placeholder**
  - Backend: ensure the runtime can compute it. Either add to `PlaceholderManager::replacePlaceholders(...)` or pass via `$customPlaceholders` when calling `TemplateManager`.
  - Frontend: add a description key `<placeholder>_desc` into each `src/main/core/Resources/translations/template.<lang>.json` for proper UI display.
  - (Optional) Add the placeholder name to a `TemplateType.placeholders` array to list it for specific types.
  - For user profile driven values, add a FieldFacet representing the data, then resolve its value in the relevant manager (e.g., `MailManager`) using `FieldFacetValueRepository` + `FacetManager`.
- **Add a new template type**
  - Create a `TemplateType` with `name` set to a translation key (e.g., `my_feature_notice`).
  - Add `my_feature_notice` and `my_feature_notice_desc` in each `template.<lang>.json` file for UI labels.
  - Provide system templates via fixtures or create them via the admin; set one as default.

**Known Mismatch To Fix**

- Delete confirmation key differs between languages:
  - Component expects `template_delete_confirm_message` at `src/main/core/Resources/modules/administration/template/components/details.jsx:41-56`.
  - English translations define `template_delete_message` at `src/main/core/Resources/translations/template.en.json:4`.
  - French defines `template_delete_confirm_message` at `src/main/core/Resources/translations/template.fr.json:4`.
  - Align keys across languages or update the component to use the agreed key.

**Notes**

- The translator defaults to the `platform` domain; always pass `'template'` when translating template-related keys.
- `Template.system` templates are locked in the UI (cannot be edited/deleted) and typically seeded via data fixtures.

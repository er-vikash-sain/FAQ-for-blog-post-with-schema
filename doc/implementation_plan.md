# FAQ Plugin Implementation Plan

## Goal Description
Build a WordPress plugin named "FAQ for blog post with schema" that allows users to add FAQs to standard blog posts via a custom meta box. The plugin will display these FAQs in an accessible accordion format either automatically after the content or via a custom shortcode. It will also generate and inject the corresponding FAQ JSON-LD schema.

## User Review Required
> [!IMPORTANT]
> - **Meta Box JS**: Will use standard WordPress admin JS (jQuery/Underscore) for repeatable fields.
> - **Schema Placement**: Schema will be printed in `wp_footer` to avoid blocking render.
> - **Scope**: Strictly for `post` post type.
> - **Zero State**: If no FAQs are added, no schema or HTML will be output.
> - **Permissions/Nonces**: All saves (meta box + settings) will include nonce + capability checks.
> - **i18n**: All strings wrapped in translation functions with a plugin text domain.

## Proposed Changes

### Plugin Structure
#### [NEW] faq.php
- Main plugin file with standard WP header.
- Load text domain, define constants/paths, and initialize the plugin class.
- Prefix/namescape classes, filters, actions, option keys, and meta keys with `faq_plugin_` (or similar) to avoid collisions.

### Admin Features
#### [NEW] admin/class-faq-admin.php
- **Menu/Settings**: Add a settings page to configure "Display Position" (After Content / Shortcode). Default to "After Content". Sanitize via a whitelist and gate with `manage_options`.
- **Meta Box**: Add a meta box to the `post` post type only.
  - Fields: FAQ Title, FAQ Content.
  - Logic: Unlimited FAQs; add/remove/reorder via WP-native JS.
  - Security: Nonce + capability check on save, bail on autosave/revisions.
  - Sanitization: Titles with `sanitize_text_field`; answers with `wp_kses_post`.
- **Block Editor**: Ensure meta box works in Gutenberg; enqueue admin assets via `enqueue_block_editor_assets` if needed; use `current_user_can( 'edit_post', $post_id )`; consider `show_in_rest` only if REST/editor previews are required.
- **Admin Assets**: Enqueue admin CSS/JS only on the post edit screen when the meta box is present.
- **i18n**: Wrap labels, notices, and option values with translation helpers.

### Frontend Features
#### [NEW] includes/class-faq-display.php
- **Filter**: Hook into `the_content` if setting is "After Content" and only on `is_singular('post')` for the `post` post type, `is_main_query()`, and not in admin.
- **Shortcode**: Register `[faq_accordion]`.
- **Rendering**:
  - Return empty string when no FAQs exist.
  - Enforce scope: only render for `post` post type; bail in shortcode handler when not viewing/processing a `post`.
  - Generate unique ID prefixes per render to avoid collisions across multiple instances.
  - Accessible accordion using `button` elements, `aria-expanded`, `aria-controls`, focus styles, and proper IDs.
  - Action hooks: `faq_accordion_before_render`, `faq_accordion_after_render`.
  - Filters: `faq_accordion_output_html` to allow developers to override output.
  - Theme override support for a template file at `yourtheme/faq-plugin/accordion.php`, with escaping expectations documented.
  - Escape output on render (`esc_html`, `wp_kses_post` as appropriate).
- **Assets**: Enqueue minimal CSS/JS for accordion interactivity only when output is present (after-content render or shortcode use).
- **Collision Guard**: Prevent double rendering when shortcode and after-content are both present on the same post.
- **Context Guard**: Skip output in feeds, embeds, REST/json requests, cron, or AJAX contexts (`is_feed`, `is_embed`, `wp_is_json_request` if available or `REST_REQUEST`, `wp_doing_cron`, `wp_doing_ajax`).

### Schema Features
#### [NEW] includes/class-faq-schema.php
- **Generation**: Method to construct the JSON-LD array from sanitized data.
- **Generation Rules**: FAQ question/answer text for schema should be plain text—strip tags/shortcodes (`wp_strip_all_tags`, `wp_kses` with empty allowed tags) before encoding to avoid invalid JSON-LD.
- **JSON-LD Shape**: Include `@context` (`https://schema.org`), `@type` (`FAQPage`), and `mainEntity` as an array of `Question` items each with `name` and an `acceptedAnswer` of type `Answer` containing `text`.
- **Output**: Hook into `wp_footer`; only on `is_singular('post')`; print once per page when FAQs exist and after passing through `faq_plugin_schema` filter (avoid duplicates if shortcode + after-content both run); guard against feeds/REST/embed/cron/ajax contexts (`is_feed`, `is_embed`, `wp_is_json_request` if available or `REST_REQUEST`, `wp_doing_cron`, `wp_doing_ajax`).
- **Encoding**: Use `wp_json_encode` with `JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE`.
- **Filters**: `faq_plugin_schema` to allow developers to modify data.

### Security, Data Handling, and Cleanup
- Nonce + capability checks for all writes (options + post meta).
- Sanitization on save; escaping on output.
- Prevent empty/invalid schema output; validate FAQ array shape.
- Add `uninstall.php`; default to keep data; provide explicit opt-in toggle to delete plugin option and per-post meta on uninstall.

### Assets and Performance
- Scope admin assets to post edit screen.
- Scope frontend assets to requests where FAQs render.
- Keep CSS/JS minimal and namespaced to avoid conflicts.

### Internationalization
- Load text domain in the main plugin file.
- Wrap all user-facing strings (admin + frontend) in `__()`/`_e()` etc.

## Verification Plan

### Manual Verification
1. **Activate Plugin**: Ensure no errors on activation.
2. **Settings**: Visit settings page; verify default is "After Content"; change to "Shortcode" and back; save without capability should fail.
3. **Edit Post**:
   - Add 0 FAQs; verify nothing renders (no empty wrappers, no schema).
   - Add 2–3 FAQs; save and verify persistence; confirm sanitized display (HTML stripped except allowed tags).
   - If both shortcode and after-content are active, ensure accordion renders only once and IDs remain unique.
   - Confirm meta box works in block editor with JS loaded and no autosave/revision writes.
4. **Frontend**:
   - View post; confirm FAQs appear after content (or via shortcode when configured).
   - Test accordion keyboard support and aria-expanded toggling.
   - Screen reader check for correct aria relationships and focus order.
   - Inspect source: `<script type="application/ld+json">` in footer with correct FAQ data.
   - Confirm schema appears only once on singular posts and not on archives/home loops.
   - Verify no FAQ markup or schema appears in feeds/REST/embed responses.
5. **Hooks & Overrides**:
   - Hook into `faq_plugin_schema` to change a value.
   - Override template file in theme at `faq-plugin/accordion.php` and confirm it is used and properly escaped.
6. **Shortcode**: With setting on "Shortcode", place `[faq_accordion]` in content and verify rendering.
7. **i18n**: Switch site language and confirm translations load on admin fields and frontend output.

### Automated Verification
- Run PHPCS with WPCS ruleset on the plugin directory.
- Add a basic unit/integration test to confirm schema output structure for a post with FAQs.

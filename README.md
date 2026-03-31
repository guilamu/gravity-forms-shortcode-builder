# Gravity Forms Shortcode Builder

Build complex Gravity Forms shortcodes visually from the Form Settings panel — no shortcode syntax knowledge required.

## Shortcode Types

- **Core Form Display:** Embed any form with title, description, AJAX, tab index, and field value pre-fill options
- **Conditional Shortcodes:** Generate AND/OR conditional logic blocks using actual field labels and plain-language descriptions (requires [GF Advanced Conditional Shortcodes by Gravity Wiz](https://gravitywiz.com/gravity-forms-advanced-conditional-shortcode/))
- **User Information:** Output user details or arbitrary user meta with selectable formatting
- **Login Form:** Build login forms with custom labels, registration link, redirect URL, and more
- **Split Test:** Select multiple forms for A/B rotation — the shortcode serves a different form to each visitor
- **Entry Count:** Display total, unread, starred, spam, or trashed entry counts with number formatting
- **Entries Left:** Show remaining submissions before a form reaches its limit — ideal for urgency messaging
- **Progress Meter:** Visual progress toward a goal with payment and field-value tracking (requires [GF Progress Meter by Gravity Wiz](https://gravitywiz.com/gravity-forms-progress-meter/))

## Builder Interface

- Tabbed layout inside **Form Settings → Shortcode Builder** keeps each shortcode type separate and scannable
- Drag-and-drop tab reordering — arrange tabs in the order that fits your workflow
- Per-user tab visibility toggles — disable unused tabs to declutter the interface
- **Notification Editor Modal:** A dedicated toolbar button next to "Add Media" opens the builder inside the Gravity Forms notification editor and inserts the generated shortcode directly into TinyMCE or the plain textarea

## Key Features

- **Visual Builder:** Point-and-click shortcode generation — no syntax memorisation needed
- **Multilingual:** Works with content in any language
- **Translation-Ready:** All strings are internationalized; French (fr_FR) included
- **Secure:** Nonce-verified AJAX handlers with specific capability checks and input sanitization
- **GitHub Updates:** Automatic updates from GitHub releases via the standard WordPress update screen

## Requirements

- [Gravity Forms](https://www.gravityforms.com/) (any recent version)
- WordPress 5.8 or higher
- PHP 7.4 or higher
- _Optional:_ [GF Advanced Conditional Shortcodes (Gravity Wiz)](https://gravitywiz.com/gravity-forms-advanced-conditional-shortcode/) — required for AND/OR conditions in the Conditions tab
- _Optional:_ [GF Progress Meter (Gravity Wiz)](https://gravitywiz.com/gravity-forms-progress-meter/) — required for the Progress Meter tab

## Installation

1. Upload the `gravity-forms-shortcode-builder` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Open any form in Gravity Forms and go to **Settings → Shortcode Builder**
4. Select a tab, configure your options, and copy the generated shortcode

## FAQ

### Where does the builder appear?

Inside any Gravity Form, go to **Settings → Shortcode Builder**. A separate modal is also available inside the notification editor via the **Shortcode Builder** toolbar button next to "Add Media".

### Which tabs appear in the notification modal?

Only the most relevant tabs for notification content: Conditional, User Information, Entry Count, and Entries Left. Disabled tabs are excluded from the modal as well.

### Can I hide tabs I don't use?

Yes. Use the visibility toggles at the top of the Shortcode Builder page. Preferences are saved per user and persist across sessions.

### Do I need Gravity Wiz plugins?

No. The Conditional and Progress Meter tabs are hidden automatically when their respective Gravity Wiz dependencies are not active.

### Can I customize the shortcode output?

The builder generates standard Gravity Forms shortcodes — you can edit the copied shortcode freely after generation. No custom shortcode tags are introduced.

## Project Structure

```
.
├── gf-shortcode-builder.php        # Bootstrap, plugin header, autoloader
├── uninstall.php                   # Cleanup on plugin deletion
├── README.md
├── assets/
│   ├── css/
│   │   ├── admin-builder.css       # Styles for the builder interface
│   │   └── admin-modal.css         # Styles for the notification modal
│   └── js/
│       ├── admin-builder.js        # Drag-and-drop and tab UI logic
│       └── admin-modal.js          # Modal open/close and shortcode insertion
├── includes/
│   ├── class-github-updater.php    # GitHub auto-updates via WP update screen
│   ├── Plugin.php                  # Main plugin class (singleton)
│   └── Tabs/                       # Individual tab logic classes
│       ├── CoreFormDisplay.php
│       ├── Conditional.php
│       ├── UserInfo.php
│       ├── Login.php
│       ├── SplitTest.php
│       ├── EntryCount.php
│       ├── EntriesLeft.php
│       └── ProgressMeter.php
├── views/
│   └── tabs/                       # HTML templates for each tab
│       ├── core-form-display.php
│       ├── conditional.php
│       ├── user-info.php
│       ├── login.php
│       ├── split-test.php
│       ├── entry-count.php
│       ├── entries-left.php
│       └── progress-meter.php
└── languages/
    ├── gf-shortcode-builder-fr_FR.mo   # French translation (binary)
    ├── gf-shortcode-builder-fr_FR.po   # French translation (source)
    └── gf-shortcode-builder.pot        # Translation template
```

## Changelog

### 1.5.1
- **Improved:** Rewrote GitHub auto-updater to match reference template — added `plugin_info` with local README.md parsing (Description, Installation, FAQ, Changelog tabs), Parsedown-based Markdown→HTML conversion, `tables_to_divs` for wp_kses compatibility, CSS/JS injection via `admin_head`, `REQUIRES_GF` sidebar line, and `upgrader_source_selection` folder rename with copy+delete fallback
- **New:** "View details" thickbox link in the plugins list row meta opens the standard WordPress plugin-information modal
- **New:** Added Parsedown.php for reliable Markdown rendering in the plugin details popup
- **Fixed:** `check_for_update` now returns all required keys (`id`, `slug`, `plugin`, `new_version`) to prevent `Undefined property` notices on the update screen

### 1.5.0
- **New:** Composite field support in the Conditional Shortcodes tab — Address, Name, Checkbox, Time, and Date subfields are now listed individually inside grouped optgroups, with hidden subfields automatically excluded
- **Fixed:** PHP 7.4 fatal error caused by `str_ends_with()` (PHP 8.0+) in the GitHub auto-updater
- **Fixed:** Duplicate plugin folder collision (`-main` suffix from GitHub zips) could cause a fatal `require_once` failure — constants now guarded and updater loaded via `__DIR__`
- **Fixed:** Missing `wp_unslash()` in AJAX handlers (`save_tab_order`, `ajax_get_tab_content`, `save_tab_visibility`) and unsanitized `enabled` / `form_id` inputs
- **Fixed:** `array_filter()` result stored without `array_values()`, producing sparse arrays in user meta
- **Fixed:** Uncaught exception during tab rendering could leave an open output buffer; now wrapped in `try/catch`
- **Fixed:** Direct `$_GET` access in the notification modal method — replaced with `\rgget()`
- **Fixed:** Missing `esc_attr()` on `data-tab-enabled` attribute output
- **Fixed:** `__()` called with `self::TEXT_DOMAIN` constant, preventing string extraction by `wp i18n make-pot`
- **Improved:** Autoloader uses `require_once` instead of `require`
- **Improved:** Removed unused `use GFCommon` import from Conditional tab class
- **Improved:** Removed debug `console.log()` calls from production JS
- **Improved:** GitHub token retrieval now uses `constant()` to avoid IDE false positives

### 1.3.0
- **Changed:** Major codebase restructuring — adopted `GFSB` namespace for all classes
- **Improved:** Separated PHP logic (`includes/`) from HTML views (`views/`) for better maintainability
- **Improved:** Enhanced AJAX security with specific capability checks and input validation
- **Improved:** Implemented custom PSR-4 autoloader for class loading

### 1.2.0
- **New:** Plugin logo
- **Improved:** Refactored all inline CSS and JavaScript to external asset files for browser caching and maintainability

### 1.1.1
- **New:** Per-tab visibility toggles at the top of the Shortcode Builder page
- **Improved:** Tab visibility preferences saved per user and persist across sessions

### 1.1.0
- **New:** Modal experience inside the Gravity Forms notification editor with dedicated toolbar button and direct shortcode insertion
- **Improved:** Notification modal restricted to the most relevant tabs for a streamlined workflow
- **Improved:** Conditional placeholders describe conditions in plain language with AND/OR context
- **Improved:** Enhanced localization coverage for modal labels, state messages, and placeholders

### 1.0.0
- Initial release

## License

This project is licensed under the GNU Affero General Public License v3.0 (AGPL-3.0) - see the [LICENSE](LICENSE) file for details.

---

<p align="center">
  Made with love for the WordPress community
</p>


<p align="center">
  <img src="logo.png" alt="Gravity Conditional Compass Logo" width="400">
</p>

**Easily build and manage complex Gravity Forms shortcodes via a user-friendly interface in the Form Settings panel. Each shortcode type is available as an accordions, ideal for sites using conditional display, entry stats, split tests, login forms, and more. Compatible with Gravity Wiz Advanced Conditional Shortcodes and Gravity Wiz Progress Meter, where available.**

![Plugin Screenshot](https://github.com/guilamu/gravity-forms-shortcode-builder/blob/main/screenshot.jpg)


## **Features**

- **Tabbed Builder Interface:** Each shortcode category is in its own tab for clarity. Reorder tabs as you see fit.
- **Notification Editor Modal:** Launch the builder right from the Gravity Forms notification editor (next to “Add Media”) and insert shortcodes straight into TinyMCE or the plain textarea.
- **Core Form Display:** Build standard Gravity Forms embed shortcodes with title, description, AJAX, tab index, and field value options.
- **Conditional Shortcodes:** Generate conditional logic shortcodes for dynamic content, supporting AND/OR groups (with [GF Advanced Conditional Shortcodes by Gravity Wiz](https://gravitywiz.com/gravity-forms-advanced-conditional-shortcode/)).
- **Plain-Language Conditional Placeholders:** The generated conditional shortcode now describes each condition in human-readable text, using actual field labels, operators, and values.
- **User Information:** Output user details or user meta by key, with selectable output formatting.
- **Login Form:** Create login forms with options for custom text, registration links, redirect, and more.
- **Split Test:** Select multiple forms for built-in A/B testing; the shortcode will rotate which form is shown to each visitor.
- **Entry Count:** Display the total, unread, starred, spam, or trashed entries for any form, with customizable number formatting.
- **Entries Left:** Show the remaining entries before a form reaches its submission limit, great for limited offers and urgency.
- **Progress Meter:** (Optional, requires [GF Progress Meter by Gravity Wiz](https://gravitywiz.com/gravity-forms-progress-meter/)) Visual meter showing progress toward a goal, supporting advanced tracking (payments, field values, etc.).

## Project Structure
```
gravity-forms-shortcode-builder/
├── gf-shortcode-builder.php        <-- Main plugin file, autoloader, and bootstrap
├── assets/                         <-- CSS and JS assets
│ ├── css/
│ │ ├── admin-builder.css           <-- Styles for the builder interface
│ │ └── admin-modal.css             <-- Styles for the notification modal
│ └── js/
│   ├── admin-builder.js            <-- Logic for drag-and-drop and UI interactions
│   └── admin-modal.js              <-- Logic for the modal and shortcode insertion
├── includes/                       <-- PHP classes (logic)
│ ├── Plugin.php                    <-- Main plugin class (Singleton)
│ └── Tabs/                         <-- Individual tab classes
│   ├── CoreFormDisplay.php         <-- Logic for Core Form Display tab
│   ├── Conditional.php             <-- Logic for Conditional tab
│   ├── UserInfo.php                <-- Logic for User Info tab
│   ├── Login.php                   <-- Logic for Login tab
│   ├── SplitTest.php               <-- Logic for Split Test tab
│   ├── EntryCount.php              <-- Logic for Entry Count tab
│   ├── EntriesLeft.php             <-- Logic for Entries Left tab
│   └── ProgressMeter.php           <-- Logic for Progress Meter tab
├── views/                          <-- HTML templates (presentation)
│ └── tabs/                         <-- View files for each tab
│   ├── core-form-display.php       <-- Template for Core Form Display
│   ├── conditional.php             <-- Template for Conditional
│   ├── user-info.php               <-- Template for User Info
│   ├── login.php                   <-- Template for Login
│   ├── split-test.php              <-- Template for Split Test
│   ├── entry-count.php             <-- Template for Entry Count
│   ├── entries-left.php            <-- Template for Entries Left
│   └── progress-meter.php          <-- Template for Progress Meter
├── languages/                      <-- Translation files
└── README.md                       <-- Plugin documentation
```

## **Requirements**

- WordPress 5.8+ recommended
- [Gravity Forms](https://www.gravityforms.com/) (any recent version)
- _Optional Free Plugin:_ [GF Advanced Conditional Shortcodes (by Gravity Wiz)](https://gravitywiz.com/gravity-forms-advanced-conditional-shortcode/) for AND/OR/regex in Conditions tab
- _Optional Free Plugin:_ [GF Progress Meter (by Gravity Wiz)](https://gravitywiz.com/gravity-forms-progress-meter/) for Progress Meter functionality

---

## **Installation**

1. Download and unzip this repository.
2. Place the entire folder in your site’s `/wp-content/plugins/` directory.
3. Activate **Gravity Forms Shortcode Builder** in the WordPress Plugins menu.
4. Go to any Gravity Form’s Settings > **Shortcode Builder**.

---

## **Usage**

- Click any tab and fill out the options for your desired shortcode type.
- From the notification editor, use the **Shortcode Builder** button next to “Add Media” to open the modal, generate your shortcode, and click **Insert Shortcode** to paste it automatically.
- Copy the auto-generated shortcode and insert it where needed in confirmations, posts, pages, block patterns, etc.

---
## **Changelog**
### **Version 1.5.0 (03/05/2026)**
- **New:** Composite field support in the Conditional Shortcodes tab — Address, Name, Checkbox, Time, and Date subfields now listed individually inside grouped optgroups, with hidden subfields excluded
- **Fixed:** PHP 7.4 fatal error — `str_ends_with()` (PHP 8.0+) replaced with a PHP 7.4-safe check in the GitHub updater
- **Fixed:** Duplicate plugin folder collision (`-main` suffix) causing fatal `require_once` failure — constants guarded and updater loaded via `__DIR__`
- **Fixed:** Missing `wp_unslash()` in AJAX handlers and unsanitized POST inputs
- **Fixed:** Sparse array stored in user meta after `array_filter()` without `array_values()`
- **Fixed:** Uncaught render exception could leave open output buffer
- **Fixed:** Direct `$_GET` access replaced with `\rgget()` in notification modal
- **Fixed:** Missing `esc_attr()` on data attribute output
- **Fixed:** `__()` called with constant instead of string literal text domain
- **Improved:** Autoloader, unused imports, debug logs, and GitHub token handling cleaned up

### **Version 1.3.0 (12/04/2025)**
- **Refactoring:** Major codebase restructuring for better maintainability and security.
- **Namespaces:** Adopted `GFSB` namespace for all classes.
- **Separation of Concerns:** Separated PHP logic (`includes/`) from HTML views (`views/`).
- **Security:** Enhanced AJAX security with specific capability checks and input validation.
- **Autoloader:** Implemented a custom autoloader for better class loading performance.

### **Version 1.2.0 (12/04/2025)**

- Added a logo
- Refactored all inline CSS and JavaScript to external asset files for improved maintainability, browser caching, and code organization.

### **Version 1.1.1 (12/04/2025)**

- Added per-tab visibility toggles at the top of the Shortcode Builder page. Disable any tab to hide it from the builder and the notification modal dropdown.
- Tab visibility preferences are saved per user and persist across sessions.

### **Version 1.1 (12/04/2025)**

- Added a modal experience inside the Gravity Forms notification editor with a dedicated toolbar button and direct shortcode insertion.
- Restricted notification modal tabs to the most relevant shortcodes (Conditional, User Information, Entry Count, Entries Left) for a streamlined workflow.
- Improved conditional placeholders to spell out conditions in plain language (localized) and reflect AND/OR relations.
- Enhanced overall localization support, covering the modal button labels, state messages, and newly added placeholder text.

### **Version 1.0 (11/25/2025)**
- Initial release.

## **License & Feedback**

This project is licensed under the GNU AGPL.

Feedback, issues, and PRs welcome!

# **Gravity Forms Shortcode Builder**

<p align="center">
  <img src="logo.png" alt="Gravity Conditional Compass Logo" width="400">
</p>

**Easily build and manage complex Gravity Forms shortcodes via a user-friendly interface in the Form Settings panel. Each shortcode type is available as a tab—ideal for sites using conditional display, entry stats, split tests, login forms, and more. Compatible with Gravity Wiz Advanced Conditional Shortcodes and Gravity Wiz Progress Meter, where available.**

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

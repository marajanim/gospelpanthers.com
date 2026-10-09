# GOPA — The Movement

Custom WordPress theme for Gospel Panthers of Great Britain. No page-builder subscription or external font service is required.

## Editing

- **Pages → Home**: use the block editor and List View to select the named sections. Edit headings, paragraphs, links and images directly. Keep structural CSS classes on groups. Collections are shortcode blocks and update automatically from the corresponding admin menu.
- **Pages**: edit About us, STORM Academy, Get involved, Events & resources, News & stories, Contact and legal pages.
- **Mission fields / Academy courses / Events / Resources**: edit titles, excerpts (card summaries), body content, featured images and the Order field. Lower order appears first. Events have date and location fields. Registration and resource URLs appear as actions on individual items.
- **Resources → Resource categories**: categories automatically populate the resource filters. Upload a document in Media and use its file URL as the resource destination.
- **Posts**: publish news, insights and testimonies. The latest three posts automatically appear on Home. Initial posts are perspectives adapted from the supplied brief, not invented testimonies or event reports.
- **Appearance → Menus**: edit main, footer and legal navigation.
- **Appearance → Website text & links**: edit shared interface labels, card actions, pagination, form labels and choices, consent, success/error messages, social names and 404 content.
- **Panther hubs**: add real hub records, location, contact destination, content and featured image. The directory appears on Get involved.
- **Appearance → Customize → GOPA brand & contact**: edit the palette, contact email, footer statement, donation, podcast, live stream and social links. Empty external links remain hidden.
- **Appearance → Customize → Site Identity**: edit the site title, logo and browser icon.
- **Enquiries**: view private form submissions, including Academy registration interests, baptism requests, partnership enquiries and stories. An email is attempted on each successful submission. The list shows notification status; WordPress accepting a message does not guarantee inbox delivery.

## Before public launch

Confirm course and event details, add real resource files and external URLs, configure and test production email delivery, replace or complete legal drafts with approved organisational policies and contact details, and review supplied safeguarding claims. The site is currently running locally and no payment processing is included; the giving link goes to your chosen donation provider. Supplied montage photographs were cropped for the design and are low resolution; replace them through Media with original high-resolution photos when available.

## Structure

The theme contains presentation and shortcodes. `wp-content/mu-plugins/gopa-content.php` owns content types, resource categories and enquiry handling, so content remains available after switching themes. Forms include nonces, server-side validation, a honeypot, rate limiting, sanitisation and a consent checkbox. No analytics or tracking embeds are bundled. Reveal animations respect reduced-motion preferences, and content remains visible without JavaScript.

`tools/setup-gopa.php` and `tools/requirements.json` retain the initial content import. Setup is guarded against duplicate runs. `tools/finish-gopa.php` finalises resource categories and the default privacy page. Do not run these against an independently edited site without reviewing them.

`tools/verify-gopa.ps1` verifies key routes, CSRF rejection and private enquiry persistence. It suppresses email for its synthetic submission, removes its temporary mail filter and moves its test enquiry to Trash.

`tools/verify-admin-dynamic.php` exercises the actual admin save handler and checks the rendered text and image changes, restoring both theme settings and WordPress’s native site-logo option. `tools/GOPA-REQUIREMENTS-AUDIT.md` maps the supplied brief to the implementation and identifies missing real content. Home now uses valid native WordPress blocks throughout, including the circular badge and small lists. Photos can be replaced with the Image block’s Replace control; the initial montage-specific crop is applied only to the supplied city image.

## Campaign visual redesign

The homepage collage, team artwork and campaign gallery use supplied imagery as native Image blocks. Replace them through Pages > Home. Individual campaign tiles have been imported into Media. Inner-page cover images use the page Featured image. Mission and course cards use their own Featured image. Jost and Cardo fonts are served locally with their licences. The current collage uses complete prepared campaign tiles rather than the previous city-image crop.

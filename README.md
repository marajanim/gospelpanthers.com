# gospelpanthers.com

Custom WordPress theme and content functionality for Gospel Panthers of Great Britain.

Live website: https://www.gospelpanthers.com/

## Source

- `wp-content/themes/gopa/` — templates, styles, animations, local fonts and supplied design artwork.
- `wp-content/mu-plugins/gopa-content.php` — content collections and enquiry handling.

The Git repository lives in the local WordPress `public` directory. WordPress core, configuration, database exports and media uploads are excluded. Content edited in WordPress admin stays in the live database and Media Library.

## Deployment

Push reviewed changes to `main` in this repository. Intekhost checks the branch every five minutes and runs `deploy.php`, which updates the live theme and content MU plugin. No GitHub Actions secrets or additional deployment button are required.

The deployment checks PHP syntax, requires a fast-forward update, prevents overlapping runs and backs up replaced code before publishing it. It leaves the WordPress database, configuration and Media Library untouched. Admin content changes are immediate and do not need a Git commit.

The hosting Scheduled Tasks entry runs `/usr/bin/php82 /home/sites/39b/a/a8113db85a/gopa-git/deploy.php` every five minutes. Code backups are stored outside the website in `gopa-deploy-backups/`. The deployed commit can be checked at https://www.gospelpanthers.com/wp-content/themes/gopa/deployment.json.

To restore an earlier code change, revert its Git commit and push the revert to `main`. To pause automatic publishing, disable the GOPA entry in hosting Scheduled Tasks.

## Local changes

Edit the theme or MU plugin, review the changes, then commit and push. Do not commit passwords, wp-config.php, database backups, private form submissions or client credentials.

## Editing content

Use WordPress admin for pages, images, menus, mission fields, Academy courses, events, resources and enquiries. Appearance > Website text & links controls shared labels. Appearance > Customize controls branding and contact settings.

## Store and donations

Events and Resources are separate pages and navigation entries. Resources links to the WooCommerce shop, donation page and basket alongside the free resource library. The original combined page remains in WordPress as a content backup and redirects to Events.

The deployment runs an idempotent CLI migration to install WooCommerce and its official Stripe gateway from WordPress.org, preserve existing content while splitting the page, and create the shop, basket, checkout and one-time donation product. New store currency defaults to GBP. Existing store settings and existing destination pages are preserved.

Connect Stripe in WooCommerce > Settings > Payments > Stripe. No payment credentials are stored in this repository. Payments are not ready until the site's Stripe account is connected and enabled. Add real products, prices, images and downloadable files in Products. No sample sale items are seeded. Donations accept a custom amount between 1 and 100,000 in the store currency and use WooCommerce checkout. Review the shop's shipping, tax, email and payment settings for the actual items before selling.

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

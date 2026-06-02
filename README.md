# Movable Type Importer fixes

Movable Type Importer fixes is a WordPress plugin that adds WP-CLI commands for cleanup work after importing Movable Type content into WordPress. The plugin is intended to help with post-import fixes that are not handled by the default Movable Type to WordPress importer, including tag migration, content replacement, custom field migration, and resaving content for Jetpack Markdown parsing.

The plugin header in `movabletype-importer-fixers.php` identifies the plugin as:

- **Plugin Name:** Movable Type Importer fixes
- **Version:** 1.0.0
- **Author:** Automattic
- **License:** GPL-2.0+
- **Text Domain:** `movabletype-importer-fixers`

## Repository contents

- `movabletype-importer-fixers.php` is the WordPress plugin bootstrap file. It defines `MT_MIGRATION_TOOLS_VERSION`, sets `MT_MIGRATION_TOOLS_DIR`, and loads the CLI code only when `WP_CLI` is defined and truthy.
- `cli/init.php` loads the CLI command classes.
- `cli/class-mt-migration-base.php` defines the base `WP_CLI_Command`, the `test-db-connection` subcommand, logging helpers, and Movable Type database initialization.
- `cli/class-mt-import-fixes.php` registers the `mt-wp-cli` WP-CLI namespace and implements the migration/fix subcommands.
- `cli/class-mt-database.php` creates a `wpdb` connection to the Movable Type database.
- `.phpcs.xml` configures PHPCS with the `WordPress-Extra` ruleset and excludes the GitHub workflow directory.
- `.github/workflows/phpcs.yml` runs a PHPCS inspection on pull requests.

There are no tracked Composer or npm manifests in this repository, and there are no tracked tests, build artifacts, deployment files, nested READMEs, themes, block metadata, REST routes, shortcodes, custom post type registrations, or taxonomy registrations.

## Requirements

Use this plugin in a WordPress environment that can run WP-CLI. The CLI classes extend `WP_CLI_Command` and use WordPress APIs such as `get_page_by_title()`, `get_page_by_path()`, `wp_set_post_tags()`, `wp_update_post()`, `update_post_meta()`, and `get_posts()`.

The plugin also expects access to a Movable Type database. `MT_DB_NAME` is required. The database host, user, and password default to the current WordPress database constants and can be overridden with Movable Type-specific constants.

For `--field-type=ACF`, the migration code calls `update_field()`, so that mode requires an environment where that function is available.

For `sync-jetpack-markdown`, the command resaves posts so Jetpack can parse Markdown when Jetpack Markdown support is active.

## Movable Type database configuration

Define the Movable Type database constants in the WordPress environment before running the commands:

```php
define( 'MT_DB_NAME', 'movable_type_database_name' );
define( 'MT_DB_HOST', 'movable_type_database_host' );
define( 'MT_DB_USER', 'movable_type_database_user' );
define( 'MT_DB_PASSWORD', 'movable_type_database_password' );
```

`MT_DB_NAME` is required by `cli/class-mt-database.php`. If `MT_DB_HOST`, `MT_DB_USER`, or `MT_DB_PASSWORD` are not supplied, the class falls back to `DB_HOST`, `DB_USER`, and `DB_PASSWORD`.

Use the connection test before running migration commands:

```sh
wp mt-wp-cli test-db-connection
```

## WP-CLI commands

The plugin registers commands under the `mt-wp-cli` namespace. The migration commands default to dry-run behavior because the base class initializes `$dry_run` to `true`; pass `--dry-run=false` to write changes.

### Test the Movable Type database connection

```sh
wp mt-wp-cli test-db-connection
```

This initializes the Movable Type database connection and reports whether it connected successfully.

### Migrate Movable Type tags

```sh
wp mt-wp-cli migrate-tags --dry-run=true --blog-id=1
wp mt-wp-cli migrate-tags --dry-run=false --blog-id=1
```

This command reads Movable Type tag relationships from `mt_objecttag`, `mt_tag`, and `mt_entry`, finds matching WordPress posts by title or slug, and appends tags with `wp_set_post_tags()`.

Supported options:

- `--dry-run=true|false`
- `--blog-id=<id>`; defaults to `1`
- `--post-id-offset=<id>` for delta migrations; skips matching WordPress posts whose IDs are less than or equal to the offset

### Overwrite WordPress post content from Movable Type content

```sh
wp mt-wp-cli overwrite-content --dry-run=true
wp mt-wp-cli overwrite-content --dry-run=false
```

This command joins WordPress posts with Movable Type entries by post slug or title, then replaces WordPress post content with `entry_text`. When `entry_text_more` is present, it appends that extended content after a blank line.

Supported options:

- `--dry-run=true|false`
- `--post-id-offset=<id>` for delta migrations
- `--post-status=<status>` is documented in the command comments with `publish` and `draft` examples, but the current implementation initializes the status to `publish`

### Migrate Movable Type custom fields

```sh
wp mt-wp-cli migrate-meta-values --field-names=link,podcast --field-type=postmeta --dry-run=true
wp mt-wp-cli migrate-meta-values --field-names=link,podcast --field-type=postmeta --dry-run=false
wp mt-wp-cli migrate-meta-values --field-names=link,podcast --field-type=ACF --dry-run=false
```

This command reads Movable Type custom field values from `mt_fdvalue`, finds matching WordPress posts by title or slug, and writes values either to WordPress post meta or to ACF fields.

Supported options:

- `--field-names=<comma-separated-names>`; required
- `--field-type=postmeta|ACF`; defaults to `postmeta`
- `--dry-run=true|false`
- `--blog-id=<id>`; defaults to `1`
- `--post-id-offset=<id>` for delta migrations

### Resave posts for Jetpack Markdown

```sh
wp mt-wp-cli sync-jetpack-markdown --limit=100 --page=1 --post-type=post --dry-run=true
wp mt-wp-cli sync-jetpack-markdown --limit=100 --page=1 --post-type=post --dry-run=false
```

This command fetches posts with `get_posts()` and, when not in dry-run mode, calls `wp_update_post()` with the current post content. The command comments describe this as a way to let Jetpack parse Movable Type Markdown after import.

Supported options:

- `--dry-run=true|false`
- `--limit=<number>`; defaults to `100`
- `--page=<number>`
- `--post-type=<post-type>`; defaults to `post`

## Development and validation

This repository does not include dependency manifests or local install scripts. The tracked validation configuration is PHPCS:

- `.phpcs.xml` uses the `WordPress-Extra` ruleset.
- The pull request workflow runs `rtCamp/action-phpcs-code-review@master` with `args: "WordPress-Extra"`.
- The workflow skips `tests` and `.github`.

Before committing README changes, run:

```sh
git diff --check
```

Run PHPCS in an environment that already provides PHPCS and the WordPress coding standards:

```sh
phpcs --standard=.phpcs.xml .
```

## Maintenance notes

- The plugin only loads the CLI code in WP-CLI contexts; it does not register browser-facing WordPress hooks in normal page loads.
- Migration commands match Movable Type entries to WordPress posts by title or by basename/slug with underscores converted to hyphens.
- Commands that write data should be run first with `--dry-run=true`, then with `--dry-run=false` once the target set is understood.
- The CLI code defines `WP_IMPORTING` before migration operations when it is not already defined.
- Log helper code writes CSV files under `mt-migration-logs` in the WordPress uploads directory when `create_log_file()` is used.

## Contributing

Before creating a new issue, browse the existing issues for prior reports or fixes:

<https://github.com/a8cteam51/movabletype-importer-fixers/issues>

New issues can be opened at:

<https://github.com/a8cteam51/movabletype-importer-fixers/issues/new>

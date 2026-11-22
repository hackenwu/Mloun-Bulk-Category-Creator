# Mloun Bulk Category Creator for WooCommerce

Bulk create WooCommerce product categories, brands and global attribute values from simple text lists — with duplicate skipping.

**English** | [简体中文](README.zh-CN.md)

This repository is the **free edition** source (v1.2.1), the same code published on WordPress.org.

- WordPress.org: <https://wordpress.org/plugins/mloun-bulk-category-creator-for-woocommerce/>
- Author: [Mloun](https://mloun.com)
- License: GPLv2 or later

## Requirements

| | |
|---|---|
| WordPress | 6.5 or newer |
| WooCommerce | 6.0 or newer (declared via `Requires Plugins: woocommerce`) |
| PHP | 7.4 or newer |

## Features

- **Bulk create product categories** — full hierarchy support. A parent named on the line wins; the "Parent category" dropdown is the fallback for lines that do not name their own parent.
- **Bulk create brands** — works with Perfect WooCommerce Brands (`pwb-brand`), YITH WooCommerce Brands (`yith_product_brand`) and WooCommerce Brands (`product_brand`); other brand plugins can register their taxonomy through a filter.
- **Bulk create global attributes with values** — the `pa_*` taxonomy is registered before values are written, and attribute types are validated against the WooCommerce whitelist.
- **Duplicate skipping** — categories and brands are matched by **parent + name/slug**, so two children with the same name under different parents can coexist without being misreported as duplicates. Existing items are never overwritten.
- **Three-state results** — every line is reported as `created`, `skipped` (already exists) or `failed` (with the reason, e.g. a parent that does not exist).
- **Batched AJAX processing** — large lists are sent in configurable batches (default 50 items / 1000 ms), so imports do not hit `max_execution_time`.
- **Settings page** — batch size, batch delay, optional file logging to `uploads/`, all built on the Settings API.
- **Translation ready** — English UI, `languages/*.pot` included; translations are handled through translate.wordpress.org.

## Installation

1. Install from **Plugins → Add New** (search for "Mloun Bulk Category Creator") or upload the zip.
2. Activate the plugin. WooCommerce must be active — the plugin declares `Requires Plugins: woocommerce`.
3. Open **Bulk Add Categories** in the admin menu.

## Data formats

One item per line, fields separated by the pipe character `|`. Blank lines and lines starting with `#` or `//` are ignored.

**Categories** — `Name | Parent | Description | Slug`

```
Electronics
Mobile Phones | Electronics | Smartphones and tablets | mobile-phones
Laptops | Electronics | Notebook computers | laptops
Smart Watches
Headphones
```

**Brands** — `Name | Description | Slug`

```
Apple | Apple Inc. | apple
Samsung | Samsung Electronics | samsung
```

**Attributes** — `Name | Values (comma separated) | Type | Slug`

```
Color | Red,Blue,Black,White | select | color
Size | S,M,L,XL,XXL | select | size
Material | Cotton,Polyester,Wool,Silk | select | material
```

Accepted attribute types: `select`, `text`, `textarea`, `number`, `email`, `color`, `date`, `url`.

## Result states

| Status | Meaning |
|---|---|
| Created | The item (or the new attribute values) was written |
| Skipped | The item already exists — nothing is overwritten |
| Failed | A real error: empty name, parent not found, database error… |

A missing parent is always a failure. The plugin never silently creates an orphan at the top level.

## Hooks for developers

| Hook | Type | Purpose |
|---|---|---|
| `bulk_add_cat_process_types` | filter | Register additional operation types |
| `bulk_add_cat_max_items_per_request` | filter | Cap the items accepted in one request (default 2000) |
| `bulk_add_cat_brand_taxonomies` | filter | Register the taxonomy of another brand plugin |
| `bulk_add_cat_category_created` | action | Fires after a category is created |
| `bulk_add_cat_brand_created` | action | Fires after a brand is created |
| `bulk_add_cat_attribute_created` | action | Fires after a global attribute is created |

## Changelog

See [`readme.txt`](readme.txt) for the full history. Recent releases:

- **1.2.1** — slug is now `mloun-bulk-category-creator-for-woocommerce` (WordPress.org only accepts the WooCommerce term in a slug as `for-woocommerce`, `with-woocommerce`, `using-woocommerce` or `and-woocommerce`).
- **1.2.0** — renamed to *Mloun Bulk Category Creator for WooCommerce* after the plugin review; added `Requires Plugins: woocommerce`; removed `load_plugin_textdomain()` and the bundled `.po`/`.mo` files (translations go through translate.wordpress.org).
- **1.1.7** — text domain aligned with the WordPress.org slug.
- **1.1.6** — a parent named on the pasted line always wins; Plugin Check hardening (`WP_Filesystem` instead of `rmdir()`/`unlink()`/`file_put_contents()`).
- **1.1.5** — fixed duplicate detection: categories and brands are matched by parent + name/slug.
- **1.1.4** — fixed a fatal error on uninstall and the admin stylesheet being skipped on submenu pages.

## PRO edition

The PRO edition adds CSV/Excel import with column mapping, a Dry Run preview, update mode (upsert), one-click rollback with per-batch snapshots, bulk image/logo upload, and automatic updates — see <https://mloun.com>.

## License

GNU General Public License v2.0 or later — see [LICENSE](LICENSE).

# Changelog

All notable changes to the Rivian Accessory Guide plugin.

## 1.7.3 — 2026-07-04

### Fixed
- Bulk Actions dropdown now matches the Apply button height (40px), using
  the same `appearance: none` + custom chevron treatment as the filter
  dropdowns.

## 1.7.2 — 2026-07-04

### Fixed
- Admin list filter controls (search field, category/vehicle dropdowns,
  Filter/Clear buttons) now share a uniform 44px height and sit on one
  row. Previously the global `width: 100%; max-width: 400px` input rule
  made each control balloon to 400px and wrap, and native select chrome
  rendered a different height than the search field. Selects now use a
  custom chevron with `appearance: none`.

## 1.7.1 — 2026-07-04

### Fixed
- Admin accessory list thumbnails now render as uniform framed tiles.
  Mixed source images (white-background product shots, tight dark photos,
  originals smaller than the WordPress thumbnail size) previously read as
  different sizes; thumbnails now use a consistent 56px bordered box with
  `object-fit: contain` so nothing is cropped or oversized.

## 1.7.0 — 2026-07-04

### Changed
- Accessory post type and its taxonomies are no longer public. The single
  `/accessory/{slug}/` pages, `/accessory-category/` and `/accessory-vehicle/`
  archives were orphaned thin content — nothing linked to them (cards link
  directly to affiliate URLs), yet they appeared in `wp-sitemap.xml`. They are
  now removed from permalinks, sitemaps, site search, and the REST API.
- Rewrite rules now flush automatically once per plugin update, so the stale
  `/accessory/` rules clear without deactivating/reactivating.
- Removed the "View" row action from the admin accessory list (it pointed at
  the now-removed public pages).

### Fixed
- Uninstall now deletes vehicle taxonomy terms and the plugin version option.

## 1.6.1 — 2026-06-15

### Changed
- Collapse filters by default on desktop too.

## 1.6.0 — 2026-06-15

### Added
- Collapsible filters behind a toggle on mobile.

## 1.5.1 — 2026-06-15

### Changed
- Suppress the shortcode heading by default.

## 1.5.0 — 2026-06-15

### Added
- Price filter, sort control, and result count.

### Changed
- Denser desktop card grid.

## 1.4.0 — 2026-06-15

### Added
- Price-range tiers ($–$$$$) on accessories.
- Category filter bar.

## 1.3.0 — 2026-06-14

### Changed
- Restyled frontend to the ink & brass theme.

## Earlier

Initial development: custom post type and shortcode rendering, custom admin
panel (dashboard, accessory list/edit, categories, vehicles), vehicle
taxonomy with multi-select and frontend filtering, vendor/discount fields,
responsive card grid.

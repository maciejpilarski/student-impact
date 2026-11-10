=== Student Impact ===
Contributors: gomp
Tags: education, students, showcase, airtable, wordpress-org
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Showcase the graduates of the WordPress Credits program, ranked by their recent impact and contributions on WordPress.org.

== Description ==

Student Impact shows the program's graduates with the most recent impact on
WordPress.org. Each graduate is ranked by a composite of two signals, half each:

* **Recent impact** (50%): the weighted 12-month contribution score from their
  profiles.wordpress.org page (high-impact work such as commits, releases,
  approved translations and props counts 3x routine activity).
* **Contributions** (50%): contributions over the last 12 months.

Hours are not read, ranked or shown: they are hard to measure.

How the data is synced:

1. Graduates are read from the Airtable **Students** table (status = Graduate).
2. Each is matched (by email) to the **Students Reports** table for their
   WordPress profile URL and contribution team.
3. Each WordPress.org profile is read for the impact figures and the avatar.
4. The ranking is computed and the top N are stored for display.

A sync that cannot read more than a fifth of the profiles, or finds no impact
panel at all, publishes nothing and keeps the previous figures.

The plugin provides two displays, each a block and a shortcode:

**1. Student Stories**: the ranked top-N showcase (default 50).

* Shortcode: `[student_impact count="50" columns="3" title="Student impact"]`
* Visitors can filter by contribution area (Polyglots, Support, Documentation
  and so on) with the filter bar above the grid.

**2. Graduate Stats**: totals across all graduates (not just the top N):
graduates to date, contributions and recent impact score. On a site that runs
WPCredits-Tracker, the count of graduates comes from the tracker, and three of
its figures join the row: first contributions made, WordPress teams contributed
to, and graduates who plan to keep contributing. A page of both plugins then
shows one number for one thing.

* Shortcode: `[student_impact_stats title="Graduate impact"]`
* `layout="inline"` prints a compact strip.

== Configuration ==

Go to the **Student Impact** menu in the WP-Admin sidebar:

* On a site that runs the WPCredits Program Manager, no token is needed: a sync
  uses that plugin's Airtable token. It is read when a sync needs it and never
  copied into this plugin's settings. Elsewhere, paste an **Airtable Personal
  Access Token** scoped to read-only access to just this base.
* Confirm the Base ID, table ids and the status value ("Graduate").
* Set how many students to showcase (default 50).
* Click **Sync now**. A daily background sync also runs.

On activation the plugin seeds a bundled snapshot so the showcase works before
the first sync completes.

== How students are selected and ranked ==

A student is shown only if all of these are true:

1. They are in the Airtable Students table with Status = "Graduate".
2. They match (by email) a row in the Students Reports table.
3. That report has a usable WordPress.org profile (profiles.wordpress.org/user).

Graduates without a WordPress.org profile are left out of the showcase, but are
still counted as graduates.

Card labels:

* Rank (#1 to #3 sit on a tinted card).
* "Active now": at least one contribution in the last 90 days.
* "N high-impact": high-impact contributions in the last 12 months.
* Team chip: the student's main contribution area (also the filter groups).

== Changelog ==

= 1.7.0 =
* Hours are gone: not read from Airtable, not part of the ranking (now impact
  50%, contributions 50%), not stored and not shown on the cards or in the
  totals. The bundled snapshot is ranked the same way.
* Graduate Stats takes the program's figures from WPCredits-Tracker when it runs
  on the same site, so both plugins show the same count of graduates.
* A sync uses the WPCredits Program Manager's Airtable token when none is saved
  here.
* A sync that cannot read more than a fifth of the profiles, or finds no impact
  panel on any, keeps the previous figures instead of publishing zeros.
* The look of the Education Directory (wordpresseducation.org): EB Garamond
  headings and figures, Inter text, white cards on hairlines, one blue. The two
  typefaces ship with the plugin.
* Graduate Stats says when its figures were last updated.


= 1.6.1 =
* Backward compatibility: the previous shortcodes still work as aliases, so pages
  built with an earlier version keep rendering after the rename -
  [education_student_stories] and [ei_student_stories] map to the showcase, and
  [education_graduate_stats] maps to the stats block.

= 1.6.0 =
* Renamed the plugin to "Student Impact". Full re-slug: folder, text domain and
  asset handles → student-impact; prefixes → si_/SI_; CSS → si-; blocks →
  student-impact/showcase and student-impact/stats; shortcodes →
  [student_impact] and [student_impact_stats].

= 1.5.4 =
* Made the student profile photos larger on the showcase cards (88px → 116px).

= 1.5.3 =
* Documented the selection and ranking rules: a new "How students are selected
  and ranked" section here, and a collapsible help panel on the admin screen.

= 1.5.2 =
* Removed the "Current Job" line (e.g. "Student", "Studentessa", "Estudiante")
  from the showcase cards. This field is no longer scraped or displayed.

= 1.5.1 =
* Fixed: WordPress.org profile scraping read every student's impact and
  contributions as 0 (so Graduate Stats totals and the showcase ranking lost
  those signals after a live sync). The profile's impact panel packs the number
  and its label into adjacent tags with no whitespace; the flattener now inserts
  a space between tags so the parser matches. Re-run "Sync now" after updating.

= 1.5.0 =
* Reweighted the showcase ranking so **impact and contributions are the dominant
  criteria** (45% each) and hours is only a minor tiebreaker (10%) - students are
  no longer ranked primarily by hours. Refreshed the bundled snapshot to match.

= 1.4.0 =
* Graduate Stats now supports a `layout` option: the default "grid" of cards, or
  a compact "inline" horizontal strip suited to page headers and footers.
  Selectable in the block sidebar or via the shortcode `layout` attribute.

= 1.3.0 =
* Added a **Graduate Stats** block and `[student_impact_stats]` shortcode
  showing class-wide totals across all graduates: number of graduates, summed
  impact, summed contributions, and summed hours. Totals are computed over the
  full cohort during each sync (not just the top-N showcase).

= 1.2.0 =
* Renamed the plugin to "Student Impact".
* Moved the admin screen from Settings to its own top-level "Student Stories"
  item in the WP-Admin sidebar.

= 1.1.0 =
* Added a contribution-area filter bar (Polyglots, Support, Documentation, …)
  above the showcase, with per-area counts. Toggle via the block setting or the
  shortcode `filters` attribute.
* Increased the default showcase size to 50 students; refreshed the bundled
  snapshot to the top 50.

= 1.0.0 =
* Initial release: live Airtable + WordPress.org sync, ranking engine, Student
  Stories block and [student_impact] shortcode, admin settings with manual
  and daily sync.

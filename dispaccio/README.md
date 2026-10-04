# Il Dispaccio — Roundcube skin (child of Elastic)

«Il Dispaccio» (Telegrafo × Gazzetta), palette **Inchiostro**.
Everything in JetBrains Mono. The masthead, the headline of the opened message and the drop cap use Fraunces.
Selected rows are an inverted solid block, labels are small caps, and sections are separated by double rules.
Light and dark mode both work (`html.dark-mode`, Elastic's own switch).

* **Tested with:** Roundcube Webmail **1.7.4** (official Docker image `roundcube/roundcubemail:1.7.4-apache`),
  Chromium at 1280×800 and 390×844.
* **Parent skin:** `elastic` (`"extends": "elastic"` in `meta.json`). Elastic is not copied.
  Only the colour/font variables are overridden and extra rules are added.

## Install

1. Copy the `dispaccio/` folder into Roundcube's `skins/` directory, so you get `skins/dispaccio/meta.json`.
   Elastic must stay installed next to it.
2. Either let users pick it (Settings → Preferences → User interface → Skin), or set it as the default in
   `config/config.inc.php`:
   ```php
   $config['skin'] = 'dispaccio';
   // optional, if you restrict skins:
   $config['skins_allowed'] = ['elastic', 'dispaccio'];
   ```
3. Reload the page (hard refresh, or clear the browser cache after an update).

The compiled CSS (`styles/styles.css` and `styles/styles.min.css`) is included, so you don't need to build anything.

## Rebuilding the CSS (only if you change the LESS)

The LESS imports Elastic's sources from `../../elastic/styles/`, so build it inside a Roundcube tree
(`skins/elastic` next to `skins/dispaccio`). `--rewrite-urls=all` is required: it points
Elastic's icon fonts to `../../elastic/fonts/`.

```sh
cd skins/dispaccio
npx lessc@4 --rewrite-urls=all styles/styles.less > styles/styles.css
npx lessc@4 --rewrite-urls=all --clean-css="--s1 --advanced" styles/styles.less > styles/styles.min.css   # needs less-plugin-clean-css
```

## What is CSS/LESS and what is a template override

| Part | How |
|---|---|
| Palette Inchiostro (light + `html.dark-mode`), AA contrast | LESS: Elastic variables in `styles/_variables.less` + CSS custom properties in `styles/dispaccio.less` |
| JetBrains Mono everywhere (list, folders, flags, search, forms, menus) | LESS: `.font-family` mixin override + rules |
| Inverted selection block (list rows, folders, settings, contacts) | CSS |
| Numbered folders, UPPERCASE, dot leaders, «Sezioni» / «Rubriche» labels | CSS (counters, `::before/::after`, `:has()`) |
| Message list columns `#  NF+  Mittente › oggetto … Data`, row numbers, flags as `N`/`R` · `!` · `+`, one line per message (sender › subject, no address) | CSS (the status and flag glyphs stay clickable) |
| `/ cerca:` prompt, moved into the masthead on desktop, with the `from: subject: is:` hint | CSS (the search field is Elastic's own field, positioned with `position: fixed`) |
| Toolbar as bordered keycaps, Delete in accent | CSS |
| Opened message: Fraunces headline, `DA: / A: / DATA:` block between rules, drop cap, «Allegati» with double rule and `[+]` rows | CSS (drop cap only for **plain-text** mail, on purpose) |
| «Tiratura» box (quota) with double rule | CSS (restyled Elastic quota widget; only visible if the IMAP server reports a quota) |
| Background ruling, login wordmark, envelope mark in the task rail | CSS + `images/logo.svg` |
| «Nessun dispaccio aperto» empty preview | `watermark.html` (static file, same mechanism as Elastic) |
| **Masthead «Il Dispaccio»** + compose button + date line between double rules (date, edition time, unread in Inbox, messages in the folder, user) | **Template override:** `templates/includes/layout.html` (a copy of Elastic's with one `<header>` block and a small inline script added) |

Only **one template** is overridden: `templates/includes/layout.html`. After every Roundcube upgrade, diff it
against `skins/elastic/templates/includes/layout.html`. The only addition is the block marked
`Il Dispaccio: masthead` (markup + a small script: dateline counters and the on-demand row hover menu), plus the `Il Dispaccio: dates` script (full date in the reading header, year in the list
only for previous years; see «Dates» below).

## Dates («23 set» / «8 mar 2025» / «8 mar 2026, 14:32») — recommended server config

In the message list, dates older than a week are shown as day + abbreviated month (e.g. «23 set»), plus the year for
mail from a previous year («8 mar 2025»). In the opened message header (DATA) the date is always complete, with year
and time («8 mar 2026, 14:32», in the user's timezone). This uses Roundcube's own date settings, set **server-side**
in `config/config.inc.php` (not in the skin's `meta.json`, because skin config there would also lock the option for users):

```php
$config['prettydate']  = true;          // today: time; last 7 days: weekday + time
$config['date_long']   = 'j M Y, H:i';  // older: full date (month name from the Italian locale)
// REQUIRED, otherwise the first save of Settings > Preferences > User Interface replaces it (see below)
$config['dont_override'] = array_merge($config['dont_override'] ?? [], ['date_long']);
```

**Why `dont_override`:** Roundcube has no "long date" dropdown. Every time a user saves
Settings → Preferences → User Interface (even just to switch skin), `prefs_save.php` rebuilds
`date_long = "<date format> <time format>"` (e.g. `Y-m-d H:i`) and stores it in the user's preferences.
That value then beats the server's `date_long`, so older mail shows «2026-09-25 13:10».

Adding `'j M'` to `date_formats` does not help. The saved value would still get the time appended
(`j M H:i`), and `date_format` also drives the date pickers and contact dates, which need a year.

With `date_long` in `dont_override`, Roundcube never stores the rebuilt value. It also ignores values
users already have stored, so users who saved before the fix are cured on their next request, with no
database edits needed.

**Trade-off:** users can no longer change how *older* dates look in the list. The date/time dropdowns
still apply to today's times, date pickers and contacts.

The skin lowercases the Italian month and weekday abbreviations in the list and the header with CSS
(«23 set», «mer 10:42»), and also ships `localization/it_IT.inc` with lowercase month labels.

**How the skin uses it (1.0.1):** Roundcube formats the header table's DATA row with the pretty date
(«oggi 14:32», «dom 14:32», or `date_long`), but the hidden summary line («Da … il …») always with `date_long`.
The small `Il Dispaccio: dates` script in `templates/includes/layout.html`:

* reading view: copies the summary's full date into the DATA row (only if it contains a year, so with an old
  `'j M'` config nothing changes);
* message list (`insertrow` event): a date in the `date_long` form «8 mar 2025, 09:15» becomes «8 mar 2025», or
  «8 mar» if it is from the current year (current year computed in the user's Roundcube timezone). The full date
  stays in the cell's tooltip. «oggi …» and weekday dates are untouched.

The quote line of replies («Il 8 mar 2026, 14:32, … ha scritto:») and the print view also get the full date.
Up to 1.0.0 the recommendation was `date_long = 'j M'`, which dropped the year everywhere.

## Fonts / privacy

The fonts are bundled in `fonts/` as woff2 (latin + latin-ext subsets, variable weight) and loaded with local
`@font-face` URLs. Nothing is loaded from Google Fonts or any other CDN.

* JetBrains Mono, © The JetBrains Mono Project Authors, SIL OFL 1.1 (`fonts/OFL-JetBrainsMono.txt`)
* Fraunces, © The Fraunces Project Authors, SIL OFL 1.1 (`fonts/OFL-Fraunces.txt`)

The woff2 files come from the Fontsource packages `@fontsource-variable/jetbrains-mono` and `@fontsource-variable/fraunces`.

## Known limitations

* Needs a current browser (`:has()`, CSS nesting is compiled, `mask`). Tested in Chromium. Firefox ≥ 121 and Safari ≥ 15.4 support `:has()`.
* Between 1025 and 1200px wide (layout without the folder column) sender and subject are both shortened a lot.
* Today's mails show «oggi HH:MM» (Roundcube pretty date), not the bare time.
* The short task-rail labels («Impost.», «Tema», «Info») and the mobile footer labels («Prec.», «Succ.») are CSS text; the Italian ones apply only when the UI language is Italian.
* A few labels are fixed Italian text in the CSS: «Sezioni», «Rubriche», «Tiratura», «Da/A/Cc/Data», the column header, and the static kicker «Dispaccio · lettura».
* The drop cap applies only to plain-text messages. HTML mail keeps its own markup.
* HTML mail in dark mode stays light (on purpose: inverting arbitrary sender markup breaks logos and colours).
* On phones (< 480px), plain-text mail with hard line breaks (lines of ~70 characters, typical of
  `format=fixed` mail) wraps a second time, leaving short orphan lines and splitting aligned «tables».
  A monospace body can't fit 70+ characters at a readable size on a 390px screen; a 13px body was
  tried and reverted. Fix would need a different approach (e.g. a horizontally scrollable `pre`).
* «imap ● connesso» in the date line is decorative and is not a live connection check.
* The dateline counters are client-side (`rcmail.env`) and refresh after list and refresh requests.

## Licence

The skin's styles and templates are derived from Roundcube's Elastic skin (© The Roundcube Dev Team) and are
licensed under Creative Commons Attribution-ShareAlike 3.0, like Elastic (see `LICENSE` at the repository root). The fonts are under SIL OFL 1.1 (see above).

# Press — Project Briefing & Working Conventions (CANONICAL)

Supersedes the briefing seeded from the Imagin/blog bug-fix session.
This version folds in the modernization + full-coverage session
(PHPUnit 12 / Testbench 11 / Laravel 13 upgrade, coverage batches
A–D, the crossed-session incident, and the view-swap discovery).
Counts and file facts below are hints; **the repo is authoritative**.

**OPENING TASK for next session (spot-verify against the fresh zip):**
- Test count: expect **138 total / 137 run** (1 excluded via the
  `integration` group — GistDriverTest hits the live GitHub API).
  Derive by `grep -rc "#\[Test\]" tests/`.
- composer: `php ^8.3`, `illuminate/support ^13.0` only,
  `orchestra/testbench ^11.0`, `phpunit/phpunit ^12.5`, NO
  laravel/legacy-factories anywhere.
- `ImaginShortcode::ALLOWED_KEYS` = exactly: location, width,
  height, alt, class, style, loading, decoding, fetchpriority,
  sizes.
- Config defaults: driver `file`, prefix `press_`, path `/blog`,
  trending_limit `1000`, pagination `15`.
- Views: `authors/` and `series/` each render their OWN subject
  (see the view-swap incident) and every path link goes through
  `Press::path()`, never `config('press.path')` raw.
- Site-side open items (bottom of Roadmap) — ask which have closed.

## What this is

coderstape\Press: the blog/content package running the Sportsman
Boats production site's blog. Posts are authored as markdown with a
front-matter-style head; Press parses them (PressFileParser splits
head/body, per-field `Field\*` classes process each key, Parsedown
1.7 renders the body to HTML at ingest) and stores the result in a
`posts` table. Display is `{!! $post->body !!}` from the package's
views (which the site may override via publishing). Ingest drivers:
FileDriver, DatabaseDriver (posts authored in a `blogs` table via
AdminPostController), GistDriver (two-level: the configured gist is
a newline-separated INDEX of gist ids; each of those holds one
post's markdown; `array_pop` takes the LAST file of a multi-file
gist — pinned).

Sibling package to GrandeBerg Imagin (see IMAGIN_BRIEFING.md); the
same production site runs both, and blog posts embed Imagin image
slots. Package/consumer split: artisan steps and published
config/views live in the SITE, never in this repo.

Stack as of this session: PHP ^8.3 (Victor's machine runs 8.5.5),
illuminate/support ^13.0 (single-version support is deliberate —
single consumer), Testbench ^11, PHPUnit ^12.5 (12.5.31 locally),
erusev/parsedown ^1.7 (abandoned upstream; migration is a roadmap
item).

## The big decisions (do not relitigate)

**The Imagin integration (previous session — unchanged, now e2e
pinned).** `@imagin(...)` directives survive Parsedown ingest as
literal paragraph text (`=>` entity-escaped) and are expanded at
REQUEST time by `Post::getBodyAttribute()` →
`ImaginShortcode::expand()`. Nothing is ever eval()'d; only
single-quoted literal pairs parse; keys are whitelisted (mirrors
Imagin's image-location endpoint whitelist AND its rationale:
attribute VALUES are escaped by Imagin, attribute NAMES are not —
`unknown_keys_never_reach_the_renderer` is the tripwire); a `<p>`
wrapping a sole-content directive is unwrapped; Press does NOT
depend on Imagin (class_exists guard; `ImaginShortcode::$renderer`
is the injectable seam; add `grandeberg/imagin` to composer
`suggest` someday). IngestRenderTest now pins the full thread:
markdown file → press:process → stored literal → HTTP render →
expanded via injected renderer. Admin editing is unaffected:
`posts.body` is DERIVED from `Blog.data` and regenerated on update
(pinned in AdminPostControllerTest).

**Modernization (this session, Victor-approved).** Test suite moved
to PHPUnit 12 attributes: `#[Test]` with prefix-free snake_case
names (a return to this repo's original `@test`-era naming);
`@group integration` became `#[Group('integration')]` because
PHPUnit 12 no longer reads doc-comment metadata — without the
attribute the exclusion silently dies and the live-API test runs.
Legacy factories dropped entirely (they had been a PRODUCTION
require!) in favor of class-based factories in
`coderstape\Press\Database\Factories` (autoload-dev only; each
model binds via `newFactory()` because the package namespace
doesn't match Laravel's factory-guessing convention). phpunit.xml
uses the PHPUnit 12 schema (`convert*ToExceptions` attrs are fatal
since PHPUnit 10), `<source>` on src/, cache in `.phpunit.cache`;
`.phpunit.result.cache` is gitignored and no longer committed.

**Coverage program (this session, complete).** Suite grew
49 → 89 (A: models + Press core + transformers + theme helper)
→ 105 (B: fields + drivers offline) → 125 (C: public HTTP layer)
→ 138 (D: admin + command + e2e). Policy decisions made once:
bugs found in a batch's territory get FIXED and pinned in that
batch, always; the AIContent seam is an accepted coverage hole;
coverage is judged functionally ("no large gaps"), not by a
percentage number.

## Bugs fixed this session (each pinned; mechanisms recorded)

1. **Search leaked drafts** — PostController::index's
   orWhere/orWhereHas chain wasn't grouped, so the OR escaped the
   `active()` constraint: a search hit on a draft's body published
   the draft. Grouped in a closure; pinned
   (`search_does_not_leak_inactive_posts`).
2. **Case-variant tags crashed ingest** — `Tags::firstOrCreate`
   matched on slug AND name, so 'laravel' after 'Laravel' missed
   the lookup and hit the unique-slug insert. Now slug-only; first
   spelling keeps the display name (pinned in FieldsTest).
3. **Capitalized `Series:` heads lost their series** — cleanSeries
   plucked the lowercase 'series' key only; head keys keep authored
   casing, so the series got deleted as "unused" in the same run.
   `?? 'Series' ??` chain; pinned in DatabaseTest.
4. **Minimal posts crashed ingest** — savePost hard-indexed
   active/published_at/extra/tag_ids; a post authored without a
   date or tags head fataled. Now `??` defaults (publish now, no
   tags — judgment values, flagged); pinned
   (`a_minimal_post_with_only_a_title_and_body_can_be_ingested`).
5. **meta['url'] ignored the path default** — the constructor read
   `config('press.path')` with no fallback while `path()` defaults
   '/blog'; now built through `path()` so routes and meta agree
   even on unpublished configs. Related: the `meta()` transformer
   guard's historical `&&` became the intended `||` (behavior
   identical for every class implementing the Transformer
   interface); the quiet no-op for transformer-less models (author
   and admin-blog pages hit it every request) is the pinned
   contract.
6. **THE VIEW SWAP** — the `authors/` and `series/` view
   directories shipped with their contents SWAPPED (authors views
   held series markup and vice versa). All four pages were fatally
   broken and undetected for years: no tests rendered them, and the
   site presumably publishes overrides. Fixed across batch C + its
   amendments; incident narrated in comments in both restored
   series views.
7. **`url(null)` fatal in nav** — `url()` given null returns the
   UrlGenerator OBJECT, which Blade's `e()` cannot escape
   (TypeError). nav.blade.php had a bare
   `url(config('press.path'))`; with the test env leaving
   press.path unset, every rendered page 500'd. ALL view path links
   now go through `Press::path()` (8 call sites swapped); the whole
   C suite doubles as the unpublished-path rendering pin, and
   CustomPathTest covers the override side.
8. **Draft titles leaked on show pages** — authors/show and the
   restored series/show list `activePosts`, not `posts` (judgment
   call, flagged vetoable; matches the controllers' own
   active-posts constraints).
9. **Test-infra fixes with mechanisms worth keeping:**
    - Testbench 11's base TestCase no longer populates `app.key` on
      the TestCase path (its own encryption tests set the key
      explicitly); ours is set in `getEnvironmentSetUp()` — the one
      HTTP-through-web-middleware path needs it for EncryptCookies.
    - The **Press singleton is constructed at BOOT**: route
      registration calls `Press::path()`, so the constructor caches
      `config('press.blog')` before any test method runs. In-test
      `config()` changes never reach its meta (tag/series pages only
      appear to work because their transformers MERGE over the stale
      cache at request time). Tests needing blog meta set it in
      `getEnvironmentSetUp()` (see AuthorControllerTest).
    - Routes named fluently AFTER boot need
      `Route::getRoutes()->refreshNameLookups()` — the name table is
      built at add() time, before the fluent `->name()` lands; boot
      routes get a framework refresh pass, mid-test routes don't.
    - `artisan(...)->expectsOutput(...)` exact-string matching works
      as documented — all four command pins held, including the
      concatenation-seam string in the vendor-publish warning.
    - Admin views are NOT shipped by the package — the site provides
      them. Tests supply minimal stubs through the theme mechanism
      (`View::addLocation` + `press.theme`).
    - `theme()` produces `press::.posts.index` (double dot) by
      accident of the '.' join; it resolves via path normalization
      (`//posts/index.blade.php`). Pinned; do NOT "fix" the join
      without auditing custom-theme configs where the '.' is
      load-bearing.
10. **Scheduled publishing exists and is now pinned** —
    `Field\Date` sets `active = !isFuture()`, so future-dated posts
    ingest as drafts and flip active on the next press:process
    after the date passes. An unparseable date omits 'active'
    entirely and savePost's default (1) publishes immediately.

## The crossed-session incident (protocol now standing)

Mid-session, 16 files materialized in the assistant's sandbox that
it did not write — simultaneously, identical zips (batches B/C/D)
were delivered to Victor by a second Claude session running the
same briefing. Resolution that worked and is now the protocol:

- **Detection:** an unexplained existing file + a full
  `diff -rq` of the working tree against pristine-plus-shipped-
  ledger, with mtimes. The assistant maintains an explicit ledger
  of every file it has shipped.
- **Containment:** quarantine the contaminated tree; rebuild a
  clean tree from the pristine zip + the ledger only; nothing
  unattested ships as a batch.
- **Reconciliation:** fresh repo zip from Victor resets ground
  truth (byte-compared against the ledger tree); foreign work is
  adopted as a colleague's PR — every claim verified against
  pristine sources, every pin re-derived (Str::slug was reproduced
  from framework source to verify identifier pins empirically),
  defects amended before green-lighting.
- **Outcome:** the foreign work was competent (it found bugs 2–4
  above independently) but contained real defects the verification
  caught: it fixed only half the view swap, its author-meta test
  fought the boot-time singleton, and its login-redirect test hit
  the refreshNameLookups quirk.
- **Standing rule: one codebase, one session at a time.** If two
  sessions ever run again, coordinate explicitly before either
  ships.
- Corollary learned during the same incident: content read from a
  possibly-contaminated tree must not be reported as repo fact —
  the assistant briefly presented foreign comments as existing
  code. Provenance-check before attesting.

## Review rules earned this session (assistant-side scar tissue)

- **Version-bump batches get an environment-dependency pass** over
  HTTP/middleware tests, not just an API-surface pass (the app.key
  miss: `withFactories` removal was caught, the key wasn't).
- **When a batch makes views render for the first time, READ every
  view in the render path in full — layouts and includes too.
  Grep locates files; it never clears them.** (nav fatal, then the
  swap, escaped the same review the same way.)
- **The repo outranks theory.** Cat the pristine file before
  building a theory about how it got that way — the "extraction
  mishap" misdiagnosis cost a turn when the discriminating check
  cost five seconds. Sibling of "user observations outrank
  theory".
- The seam-view-after-write rule caught its biggest fish yet: a
  fix zip about to ship with the same broken file it claimed to
  fix.

## Current state

- Suite: **138 tests / 137 run / 273 assertions, all green** on
  PHP 8.5.5, PHPUnit 12.5.31, Testbench 11.
- composer.lock regenerated on Victor's machine post-upgrade.
- No CI yet (roadmap item 2).
- Known debts: README predates everything; route definitions still
  use `'Controller@method'` strings with a namespace group
  (verified still supported in Laravel 13's RouteGroup/RouteAction
  — tuple modernization is roadmap, not required); Parsedown
  abandoned upstream.
- The AIContent seam: `Post::contentable()` morphs to
  `\App\Models\AIContent` and sits in `$appends` — a hard reference
  to the consuming app inside the package. Any Post serialization
  (toArray/JSON) fatals in package context. ACCEPTED as a coverage
  hole (Victor's call); views access attributes directly so the
  suite survives. Config-driven morph class with a class_exists
  guard (the ImaginShortcode pattern) is the roadmap shape if it
  ever matters.

## Environment facts (learned the hard way)

- Same stack as Imagin: macOS + Laravel Herd dev, Vite site build,
  published views/config shadow package copies. When display
  behavior seems wrong on the site, CHECK PUBLISHED COPIES FIRST —
  the view swap survived for years partly because overrides masked
  it.
- The site consumes both packages; Press views changed this session
  (see site-side items) may be masked by published overrides.
- Assistant sandbox: `apt-get update` then `apt-get install -y
  php-cli` (a stale-package 404 hits without the update; the
  nodesource repo error is ignorable); `php-mbstring` needed for
  slug harnesses; **no Packagist** — the assistant lints (php -l on
  every shipped file), harnesses pure logic standalone, and fetches
  real library source from GitHub raw for empirical verification
  (done this session for Testbench constraints, WithFactories
  deprecation, RouteGroup namespace support, legacy-factories'
  illuminate ^13 support, EnsuresDefaultConfiguration, and
  Str::slug — transcribed and executed to verify identifier pins).
  Victor runs the suite; failures get pasted back and traced to
  mechanism.
- Sessions can cross (see incident). Fresh-zip reset + ledger
  byte-compare is the reconciliation tool.

## House style (updated this session)

- Tests: `#[Test]` attribute, prefix-free snake_case method names,
  flat `coderstape\Press\Tests` namespace even under Unit/Feature,
  inline helper classes at file bottom where needed
  (PressFileParserTest precedent).
- Factories: class-based, `protected $model`, `definition()`,
  models bind via `newFactory()`; factory data changes must
  preserve pinned-adjacent values (PostFactory's `extra` JSON feeds
  PressTest meta assertions).
- Imports roughly alphabetical with `coderstape` last (ASCII
  ordering), PHPUnit attributes between Illuminate and Symfony.
- Load-bearing rationale and archived failure modes live in
  comments at the code site; invented values are labeled "judgment
  call, veto ok" in comments too.

## Roadmap (agreed order)

1. **Site-side verification sweep** (all open, carried or new):
   a. Confirm the Sportsman app's Blade-compile-of-body call is
   DELETED (grep `compileString` / `renderString` /
   `Blade::render` on the post body) — STILL unconfirmed since
   the Imagin batch; it's a template-injection surface.
   b. Grep the site for `factory(` on Press models before its next
   composer update — the legacy `factory()` helper left with
   laravel/legacy-factories (which had been a production
   require).
   c. Check `resources/views/vendor/press/` for published copies of
   the six views changed this session (nav, posts/show,
   series/index, series/show, authors/index, authors/show) —
   published copies mask the swap fix AND retain the url(null)
   landmine; mirror `Press::path()` into them.
   d. Run `press:process` once after the release lands and confirm
   the post count is unchanged (savePost defaults changed
   ingest's failure mode, not its success path — verify).
   e. Note: site searches will stop showing drafts. That's the fix
   working, not a regression.
   f. Check MySQL `ONLY_FULL_GROUP_BY`: `Press::trending()` does a
   full select with groupBy('post_id') — fine on SQLite and
   lenient MySQL, fatal under strict group-by. Production
   evidently tolerates it today; know which.
2. CI — mirror Imagin's Actions setup: matrix, weekly cron,
   composer validate, php -l. Coverage measurement optional (pcov
   locally: `pecl install pcov`, no threshold gating).
3. Parsedown (abandoned) → league/commonmark decision. A parser
   swap MUST re-verify the ImaginShortcode stored-body pins (the
   `<p>`-wrap and `=&gt;` facts are Parsedown-shaped).
4. **Preview gating decision**: `?preview` is UNGATED — anyone with
   a draft's URL sees it (pinned as current behavior in
   PostControllerTest with the decision named in a comment). May be
   an intentional shareable-preview feature; gate behind
   `isEditor()` only as a deliberate call.
5. `Transformers\Author` — real meta for author pages (public
   output change; the transformer-less no-op is the pinned interim
   contract). Consider `Transformers\Blog` for admin edit at the
   same time.
6. Shortcode decoupling decision (generic registry vs Imagin-aware
   ImaginShortcode), jointly with Imagin's renderer-contract
   roadmap item. `ImaginShortcode::$renderer` is already the seam.
7. Housekeeping cluster, each its own decision: namespace/branding
   (BC implications for site imports and published config); composer
   `suggest` for grandeberg/imagin; README rewrite; route tuple
   syntax; AIContent seam (config-driven morph class); audit
   `$appends = ['author', 'contentable']` (serialization cost and
   the app coupling); align `pagination` default's string-'15'
   quirk if ever annoying (pinned with assertSame).

## Out of scope / already rejected (grows monotonically)

- Don't compile or Blade-render stored post bodies — that path IS
  the original bug (dead PHP at best, template injection by authors
  at worst). Render-time expansion via the accessor is the design.
- Don't bake Imagin markup into stored bodies at ingest (auth- and
  cache-dependent; must render per request).
- Don't loosen the ImaginShortcode key whitelist without carrying
  the attribute-name-injection rationale.
- Don't add Imagin to composer `require` — optional peer via
  class_exists (a `suggest` entry is fine).
- Don't make the expander evaluate anything: non-literal
  expressions stay visible literal text by design.
- Don't read `config('press.path')` raw in views — `Press::path()`
  carries the default; a bare read is null → url() returns the
  UrlGenerator object → Blade fatal (nav incident).
- Don't "fix" theme()'s '.' join without auditing custom themes —
  the double-dot default works via path normalization and the dot
  is load-bearing for custom theme prefixes (pinned).
- Don't fluent-`->name()` a mid-test route without
  `refreshNameLookups()` (framework quirk register).
- Don't silently gate or un-gate `?preview` — it's an open decision
  (roadmap 4), not a drive-by fix.
- Don't ship unattested work: anything not written by the current
  session gets the full adoption review (claims verified against
  pristine sources, pins re-derived) before it goes out under a
  batch.
- Don't multi-session the same codebase without explicit
  coordination (crossed-session incident).

## How we work (self-contained conventions layer)

The portable conventions (WORKING_CONVENTIONS.md) apply in full;
the load-bearing ones, with this package's incidents attached:

1. **Ground truth is the user's repo.** A fresh repo zip resets it;
   the assistant keeps a shipped-file ledger and byte-compares on
   reset (this is what reconciled the crossed session). Never build
   from a stale or unverified base.
2. **Batches**: one coherent change-set, complete drop-in files,
   repo-relative paths, zipped over two files, applied with
   `unzip -o` at the repo root (never macOS Archive Utility), each
   shipping tests, a grep-DERIVED expected count, split
   package-side/site-side deploy notes, and a commit message.
3. **Scripted edits use anchor-asserted replacements and the seam
   gets viewed after writing.** The assertion refused a fix for a
   misread this session, and the seam-view caught the swap before a
   broken file shipped. Lint and count are necessary, never
   sufficient.
4. **Diagnosis before code**: mechanism hypothesis, cheapest
   discriminating check first, evidence picks the branch. The repo
   outranks theory; user observations outrank theory.
5. **Bugs found in a batch's territory get fixed AND pinned in that
   batch** (Victor's standing policy: bugs always get fixed).
   Contract changes rename their tests. Access-control and
   public-output changes are surfaced as decisions, not drive-bys.
6. **Empirical verification over memory** for anything load-bearing:
   fetch the real library source and execute it (Str::slug,
   Testbench internals, Parsedown previously).
7. **Predictions stated on the record, scored honestly** — this
   session's ledger: modernization "green first run" missed
   (app.key); coverage A green as predicted with named
   uncertainties holding; C's amendment-required call right but for
   the wrong file; C's clobber theory wrong and retracted; D's risk
   ranking inverted (expectsOutput held, route naming failed).
   Misses became the review rules above.
8. **The briefing is merged at session end from the original
   document in hand, never rewritten from memory.** This document
   is that merge; correct it and the corrected version becomes
   canon.
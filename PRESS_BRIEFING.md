# Press — Project Briefing & Working Conventions (CANONICAL)

Supersedes the briefing seeded from the Imagin/blog bug-fix session.
That version folded in the modernization + full-coverage session
(PHPUnit 12 / Testbench 11 / Laravel 13 upgrade, coverage batches
A–D, the crossed-session incident, and the view-swap discovery).
That version folded in the hardening session: review-fix batch E, CI
batch F, and the authorization model in batches G–H.
THIS version folds in the parser-migration session: the diagnostic
commands (batches I–J), their bug fixes (K–N), and the CommonMark
swap itself (O–P). **Press now renders markdown with
league/commonmark by default.**
Counts and file facts below are hints; **the repo is authoritative**.

**OPENING TASK for next session (spot-verify against the fresh zip):**
- Test count: expect **165 total / 164 run** (1 excluded via the
  `integration` group — GistDriverTest hits the live GitHub API).
  Derive by `grep -rc "#\[Test\]" tests/`. Last confirmed green:
  164 tests, 343 assertions.
- composer: `php ^8.3`, `illuminate/support ^13.0` only,
  `erusev/parsedown ^1.7`, `league/commonmark ^2.8`,
  `orchestra/testbench ^11.0`, `phpunit/phpunit ^12.5`, NO
  laravel/legacy-factories anywhere. BOTH parsers are required on
  purpose — see the parser section below.
- `ImaginShortcode::ALLOWED_KEYS` = exactly: location, width,
  height, alt, class, style, loading, decoding, fetchpriority,
  sizes.
- Config defaults: driver `file`, prefix `press_`, path `/blog`,
  trending_limit `1000`, pagination `15`, `parser` `commonmark`,
  `authorized` present and EMPTY in the package copy (the site's
  published copy holds the real list).
- Three commands registered: `press:process`, `press:parser-diff`,
  `press:normalize-source`.
- `MarkdownParser::parse()` trims its output on every path, and
  `MarkdownParser::$renderer` is the injectable seam.
- `.github/workflows/tests.yml` exists: 6 jobs, PHP 8.3/8.4/8.5 ×
  highest/lowest, weekly Monday cron.
- `AdminPostController::__construct()` registers `'auth'` THEN
  `EnsureUserIsEditor::class` — in that order.
- Views: `authors/` and `series/` each render their OWN subject
  (see the view-swap incident) and every path link goes through
  `Press::path()`, never `config('press.path')` raw.
- Site-side open items (bottom of Roadmap) — ask which have closed.
  1a and roadmap 2 are CLOSED; don't re-ask those.

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

Post bodies are rendered at INGEST by league/commonmark (core +
Autolink extensions); Parsedown remains selectable via
`config('press.parser')` for blogs that have not migrated yet.

Authoring through the admin routes is gated on an authorized-user
list (`config('press.authorized')`, unioned with anything registered
at runtime via `Press::editors()`); the same list governs draft
visibility and the `?draft` filter.

Sibling package to GrandeBerg Imagin (see IMAGIN_BRIEFING.md); the
same production site runs both, and blog posts embed Imagin image
slots. Package/consumer split: artisan steps and published
config/views live in the SITE, never in this repo.

Stack as of the hardening session: PHP ^8.3 (Victor's machine runs 8.5.5),
illuminate/support ^13.0 (single-version support is deliberate —
single consumer), Testbench ^11, PHPUnit ^12.5 (12.5.31 locally),
erusev/parsedown ^1.7 (abandoned upstream; migration is a roadmap
item).

## Commands

    php artisan press:process

Ingests from the configured driver and rewrites `posts`. The only
command that regenerates stored bodies.

    php artisan press:parser-diff [--report=PATH] [--show=N]

READ-ONLY. Renders every post's source through Parsedown AND
league/commonmark and reports categorized differences. Both sides are
named EXPLICITLY — it does not use `config('press.parser')` for its
baseline, because once commonmark became the default that made it
compare CommonMark against itself and report zero differences.
`--show=0` keeps the console to the summary table; `--report` writes
full diffs (with FULL CONTEXT, not just changed lines — assertions
against that file must filter for `- `/`+ ` prefixes).
Categories, trivial → structural: `identical`,
`trailing-whitespace-only`, `whitespace-only`,
`imagin-directive-changed` (hard STOP), `atx-heading-needs-space`,
`list-marker-needs-space`, `raw-html-block`, `blockquote-needs-space`,
`uncategorized`. The trivial tiers return ALONE by design.

    php artisan press:normalize-source [--rule=R]... [--apply]
                                       [--allow-visible-change] [--log=PATH]

Fixes authored markdown that only works by Parsedown's leniency.
**Dry run unless `--apply`.** Database driver only. Proves every edit
is a no-op against the currently configured parser and HOLDS BACK
anything whose rendering changes. Rules: `headings`, `html-blocks`,
`heading-close` (safe tier) and `emphasis` (needs
`--allow-visible-change`; it drops a space inside `<strong>`).
Idempotent, so it can be run repeatedly and in stages.
**Must run BEFORE switching the parser.**

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

**Modernization (the coverage session, Victor-approved).** Test suite moved
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

**The authorization model (hardening session, deployed).** Admin
CRUD is editor-only, enforced by
`Http\Middleware\EnsureUserIsEditor` registered in
`AdminPostController::__construct()` AFTER `'auth'` — order is
load-bearing: guests must get the login redirect, not a 403 they
could never clear by signing in. A signed-in non-editor gets 403.
The list lives in `config('press.authorized')`; `Press::editors()`
survives as the runtime seam and the two are UNIONed, deliberately,
so a consuming site can move its list without a cutover deploy where
the gate is live and the list is empty. `isEditor()` reads config at
CALL time (unlike the boot-cached meta array), so in-test `config()`
changes do reach it. An empty list on both sides authorizes NOBODY —
the safe direction, but it means a site that registers no list
locks itself out of its own admin.

**The markdown parser (parser-migration session, shipped).** Default
is **league/commonmark**, selected by `config('press.parser')`, with
`'parsedown'` still valid. Both live in composer `require` and
neither is going away: Parsedown is the baseline `press:parser-diff`
compares against, and it is what an un-migrated blog still runs.
The Environment is built once and cached (`MarkdownParser::converter()`)
with `html_input => allow` and `allow_unsafe_links => true` stated
explicitly rather than inherited, plus `CommonMarkCoreExtension` and
`AutolinkExtension`.

Autolink is **not optional**. Parsedown autolinks bare URLs and
CommonMark core does not, because autolinking is a GFM extension —
without it, 16 posts in the real corpus silently lost links. It also
autolinks bare `www.` hosts (prefixing `http://`) and email addresses
as `mailto:`, which Parsedown leaves as plain text. Victor chose to
keep BOTH the URL and the email autolinker; 15 posts gain links as a
result, and that is an accepted improvement, not a regression.

`MarkdownParser::parse()` **trims on every path**, including the
injected renderer. CommonMark terminates output with a newline and
Parsedown does not; untrimmed, that single character would rewrite
the stored body of every post on the first `press:process` after a
swap and show up as a `trailing-whitespace-only` difference on a
third of the blog. It has never been meaningful in HTML output.

**MIGRATION ORDER — the constraint that bites (also in the config).**

    1. php artisan press:parser-diff        see what would change
    2. php artisan press:normalize-source   fix the sources
    3. switch 'parser' to commonmark
    4. php artisan press:process            re-render every post

Step 2 MUST precede step 3. `normalize-source` proves its edits are
no-ops against the CURRENTLY configured parser; run it after the
switch and it correctly holds back the very fixes it exists to make,
because under CommonMark a heading fix turns a paragraph back into a
heading, which is a rendering change by definition. This was written
into the config and then walked into anyway, in the same batch,
breaking three tests.

**Coverage program (the coverage session, complete).** Suite grew
49 → 89 (A: models + Press core + transformers + theme helper)
→ 105 (B: fields + drivers offline) → 125 (C: public HTTP layer)
→ 138 (D: admin + command + e2e). Policy decisions made once:
bugs found in a batch's territory get FIXED and pinned in that
batch, always; the AIContent seam is an accepted coverage hole;
coverage is judged functionally ("no large gaps"), not by a
percentage number.

## Bugs fixed in the coverage session (each pinned; mechanisms recorded)

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

## The hardening session (batches E–H)

Opened by spot-verifying the fresh zip against the previous
briefing: 138/137, composer, ALLOWED_KEYS, config defaults, and both
restored view directories all matched. The in-repo briefing was
byte-identical to the uploaded one.

**Batch E — review fixes (138 → 141 / 140 run).** Five findings from
a fresh-eyes pass plus one Victor-decided change:
1. `Press::driver()` had NO guard — a typo in `press.driver`
   surfaced as a raw PHP `Error` naming a class the user never
   typed. Now throws `UnsupportedDriverException` carrying the
   config value, the resolved class, and the custom-driver namespace
   rule. Also `\Str` → imported `Str`: the alias table belongs to
   the consuming app and a package shouldn't lean on it.
2. `trending_limit` was the ONLY config read in `Press` without an
   inline default. `limit(null)` is a no-op in the query builder
   (verified in framework source), so an unpublished config ran
   completely unbounded rather than the documented 1000.
3. **`?preview` recorded a visit** — preview traffic banked into
   trending before a post was ever published. Victor's call: preview
   hits never count, including `?preview` on an already-active post.
   This changed what a preview hit RECORDS, not who may make one.
4. `series/index.blade.php` shadowed the `$series` collection with
   its own loop variable. Worked by accident (foreach evaluates its
   subject once); fixed as a landmine, no behavioral delta, so the
   existing index test was strengthened rather than a test added.
5. `isEditor()` used non-strict `in_array` — hygiene only, PHP 8's
   comparison rules already closed the hole.

**Batch F — CI (no count change).** `.github/workflows/tests.yml`,
mirroring Imagin's (Victor supplied that file). Press-specific
deltas: PHP **8.5** added to the matrix (every local run has been on
8.5, so 8.3/8.4 are what CI actually adds); extensions are
mbstring/pdo_sqlite/sqlite3/json rather than Imagin's gd/exif; the
fail-fast assertion checks mbstring + the sqlite PDO driver; and
`--exclude-group integration` is passed ON THE CLI as well as set in
phpunit.xml. That redundancy is deliberate — this suite has already
been bitten once by the exclusion silently dying, and CI must never
reach github.com regardless of what phpunit.xml says on a given day.
All six jobs green first push, 21–27s each.

**Batch G — the editor gate (141 → 143 / 142 run).** Found by
running down roadmap 1a. Chain: the admin route group is
`'middleware' => 'web'` only; `AdminPostController` added just
`'auth'`; Parsedown runs with safe mode OFF so authored raw HTML
(including `<script>`) passes into the stored body; the site renders
with `{!! $post->body !!}`. Net effect: **any registered user could
put arbitrary markup on the public blog.** Surfaced as a decision
rather than fixed on sight (access-control rule), Victor approved,
then shipped: `EnsureUserIsEditor`, the `actingAsAdmin()` helper now
registers its email, one test renamed because the contract changed
(`..._for_authenticated_users` → `..._for_editors`), and a new test
asserting 403 on every admin verb.

**Batch H — `authorized` moves to config (143 → 145 / 144 run).**
The list had lived in the SITE's `PressServiceProvider` as a
`Press::editors([...])` call. Now a documented `authorized` block in
`config/press.php` mirroring Imagin's, with `isEditor()` unioning
config and runtime. Deployed to production and behavior confirmed.

**Roadmap 1a — CLOSED.** The Sportsman site displays the body with
`{!! $post->body !!}`: a raw echo, not a Blade compile. No
`compileString`/`renderString`/`Blade::render` on the body. The
template-injection surface carried since the Imagin batch is gone.

## The parser-migration session (batches I–P)

Started as a "side quest" to size roadmap 3 and ended up closing it.
The whole thing ran through one seam.

**The seam.** `MarkdownParser::$renderer`, a public static callable
mirroring `ImaginShortcode::$renderer` down to the `tearDown()` reset
discipline. Setting it swaps the markdown implementation without
touching the ingest path — no driver changes, no `PressFileParser`
changes. Everything below is built on it. ALWAYS restore it to null
in a `finally`: it is static, so a leaked renderer silently changes
every later ingest in the same process.

**Batch I — `press:parser-diff`** (152/151). Renders every post's
source through both parsers and reports categorized differences.
READ-ONLY by construction and pinned as such. Categories run trivial
→ structural, and the trivial tiers return ALONE so a trailing
newline doesn't read as a hundred-percent regression. A change in the
`@imagin` directive's shape is a hard `STOP`.

**Batch J — `press:normalize-source`** (158/157). Fixes authored
markdown that only works by Parsedown's leniency. Dry run by default;
`--apply` is the only thing that writes. THE SAFETY PROPERTY: for
every source it touches it renders the body before and after through
the current parser and REFUSES to write anything whose rendering
changes — a rule is proven per post, not trusted because it looked
safe on a sample. Scoped to the `database` driver; rewriting source
files or remote gists is deliberately out of scope. Rules:
`headings`, `html-blocks`, `heading-close` (safe tier, must be
provable no-ops) and `emphasis` (opt-in, requires
`--allow-visible-change` because it genuinely alters current output).

**Batches K–N — fixing those two commands.** K: the `emphasis` rule
only handled a space after the OPENING delimiter and silently skipped
`**text **`, the more common spelling in the real corpus; its first
replacement over-matched across delimiter pairs (`**a** and *b*` →
`**a**and*b*`). Both pinned. Also added `heading-close` for
`## Heading##`. L: `heading-close` missed CRLF sources — `\r` sat
between the closing hashes and end-of-line — so it skipped affected
posts WHILE REPORTING SUCCESS. M: registered `AutolinkExtension`
after discovering 16 posts appeared to lose links. N: a test
assertion that could never pass (it asserted a URL was absent from a
report that deliberately carries full context lines).

**Batches O–P — the swap.** O added the `parser` config key, moved
`league/commonmark` from suggest to require, and fixed four test
fixtures that relied on Parsedown accepting `#Heading` with no space
— the same bug class the corpus had. P fixed the six failures O
caused: the trailing-newline trim, `ParserDiffCommand`'s baseline
pass (it cleared the seam and used the CONFIGURED parser, so once
commonmark became the default it compared CommonMark against itself
and reported ZERO differences — a green light meaning nothing), and
`NormalizeSourceCommandTest` needing `parser => parsedown`.

## What the real corpus actually showed

Numbers from Victor's production blog, ~600 posts. Worth keeping
because they are the evidence roadmap 3 was decided on.

| Stage | Posts with real content breakage |
|---|---|
| First run | 73 |
| After heading + html-block normalization | 13 |
| After the corrected emphasis rule | 4 |
| After CRLF fix + manual work | 1 |
| Final | 0 |

`imagin-directive-changed` was **ZERO on every run** — six of them.
Verified directly rather than by category count: 467 directives
across 105 posts, all on unchanged context lines, with both facts the
expander's regex needs (`<p>@imagin(` wrapper, `=&gt;` escaping)
present under CommonMark.

Final independent audits on the migrated corpus: block structure
differs in **0 of 549**; visible text differs in **0 of 549** with
entities normalised and escaped tags audited separately; newly
escaped tags **0**. The ~549 posts still "differing" are entity
encoding, tag nesting order, and `<p>`-wrapped images — the cosmetic
floor, which was never going to reach zero.

**The migration fixed live bugs.** Three section headings rendering
as literal `## Florida`; a heading trapped inside an `<li>`; four
product links showing as raw `<a href=…>` markup; several paragraphs
of run-together text (`$33,000 in scholarshipsto student anglers`);
and post 174's mangled nested anchors. All broken BEFORE any of this
started. Parsedown's leniency had been hiding malformed source for
years.

**Causes worth recognizing again** (they will recur on the second
blog): heading with no space after `#`; trailing `##` with no space
before it; whitespace immediately inside an emphasis delimiter, on
EITHER side; `text**bold**text` with no surrounding spaces (renders
run-together TODAY); a void element (`<br>`, `<img>`, `<hr>`) on its
own line immediately followed by markdown, which CommonMark swallows
into the HTML block until a blank line; missing whitespace between
HTML attributes (`"target=`); and curly quotes inside HTML
attributes.

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

## Review rules earned (assistant-side scar tissue)

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
  fix. (It earned its keep again in batch E, catching a blank line
  the guard clause had absorbed — trivial, but the rule found it.)
- **Hedging is not calibration.** Batch F named three likely-red
  items (`composer validate --strict`, testbench at `--prefer-lowest`,
  PHP 8.3/8.4); all three passed. The list was padded because those
  items were UNVERIFIABLE in the sandbox, not because there was
  evidence of risk. "I couldn't check this" is the honest phrasing;
  "this will probably fail" is not, and it trains the reader to
  discount the warnings that matter.
- **Verify the framework claim before reporting the bug.** Nearly
  reported `$this->middleware()` in a controller constructor as
  removed by the Laravel 11 controller-middleware overhaul. Fetched
  `Illuminate\Routing\Controller` first: it is still there. That
  would have been a confident, wrong bug report — same shape as the
  "extraction mishap" misdiagnosis, same five-second cure.
- **Follow the chain, not the link.** Batch G's finding needed four
  facts together (route group middleware, controller middleware,
  parser safe-mode, the site's echo). Any one alone looks fine.
- **THE BIG ONE: hold analysis code to the same bar as shipped
  code.** The parser-migration session had five failures and every
  single one was in the ANALYSIS layer — throwaway triage scripts and
  test assertions — never in `press:parser-diff` or
  `press:normalize-source`, which were seam-viewed, anchor-asserted
  and harnessed and were right each time. But Victor's DECISIONS
  rested on the triage scripts. Specifically:
    - `html.unescape(a) == html.unescape(b)` makes an ESCAPED tag
      compare equal to a rendered one, so posts where CommonMark
      escaped a link were filed as "cosmetic entity decoding" for FIVE
      consecutive runs. Found only by grepping `&lt;` directly.
    - Comparing visible text after `strip_tags` cannot see structure at
      all: a heading becoming a paragraph, or a heading trapped inside
      an `<li>`, shows identical text.
    - The fix both times came from a DIFFERENT method (grep for
      escaped tags; compare block-tag sequences), never from the
      classifier getting smarter. Run independent audits; do not
      iterate on the one that already fooled you.
- **A tool reporting success is not a tool working.** The CRLF gap in
  `heading-close` and the `emphasis` rule's missing side both SKIPPED
  posts while reporting success. So did `ParserDiffCommand` comparing
  CommonMark against itself. Silent-skip is the worst failure mode in
  a safety tool; prefer designs that fail loudly.
- **Knowing a constraint is not applying it.** The migration-order
  constraint was written into the config file and walked into three
  tests later, in the same batch.
- **Don't declare victory early.** "Real breakage is zero" was said
  three times before it was true. Each time the number came from a
  classifier that had not been validated against a known-positive
  case.

## Current state

- Suite: **165 tests / 164 run / 343 assertions, all green** on PHP
  8.5.5, PHPUnit 12.5.31, Testbench 11. (Assertion counts drift every
  batch; derive rather than trust.)
- CI green on all six matrix jobs (PHP 8.3/8.4/8.5 × highest/lowest).
- The editor gate and the `authorized` config block are DEPLOYED to
  the Sportsman production site and confirmed working.
- Default markdown parser is **league/commonmark**. The Sportsman
  corpus is fully normalized: zero posts differ in block structure or
  visible text between the parsers.
- Sportsman deploy needs only steps 3–4 of the migration order
  (`parser => commonmark`, then `press:process`) — its sources are
  already normalized.
- composer.lock regenerated on Victor's machine post-upgrade.

**Victor has a SECOND blog** that has not been migrated. It gets the
full four-step order. `press:parser-diff` and `press:normalize-source`
are first-class, supported tooling because of it — not throwaway
scripts. Note `normalize-source` is scoped to the `database` driver;
if the second blog uses the file driver, that scope has to be widened
first (its `fetchRaw`-equivalent walk does not exist for files yet).
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
- The site consumes both packages; Press views changed in the
  coverage and hardening sessions (see site-side items) may be
  masked by published overrides.
- `league/commonmark` is a HARD REQUIREMENT of laravel/framework
  (`^2.8.1` in its composer `require`), so any Laravel app already
  ships it — verified from the framework's composer.json.
- CommonMark defaults, verified in its source: `html_input` is
  `allow` and `allow_unsafe_links` is `true`, i.e. the same
  raw-HTML posture Press already had. Press states them explicitly
  anyway.
- GitHub's UNAUTHENTICATED API rate-limits fast and returns shapes
  that break naive parsing. `raw.githubusercontent.com` does not —
  prefer fetching files over querying the API.
- Assistant sandbox: runs as root, so `sudo` is ABSENT — call
  `apt-get update` then `apt-get install -y php-cli` directly (a
  stale-package 404 hits without the update; the nodesource repo
  error is ignorable); `php-mbstring` needed for slug harnesses;
  **pdo_sqlite is NOT available**, so nothing DB-backed can be
  harnessed locally; **no Packagist** — the assistant lints (php -l on
  every shipped file), harnesses pure logic standalone, and fetches
  real library source from GitHub raw for empirical verification.
  Coverage session: Testbench constraints, WithFactories
  deprecation, RouteGroup namespace support, legacy-factories'
  illuminate ^13 support, EnsuresDefaultConfiguration, and
  Str::slug. Hardening session: `Str::title` (executed —
  'mongo' → 'Mongo'), `Builder::limit(null)` (a genuine no-op),
  `compileLimit` (inlines the int, so it is greppable in the SQL),
  `Arr::get` (an explicitly-null key returns null, NOT the default —
  which is why a config default can't be tested by setting the key
  to null), `Illuminate\Routing\Controller::middleware()` (still
  present), Parsedown 1.7.0 vs 1.7.4 (byte-identical on the Imagin
  fixtures), Parsedown safe-mode off (raw HTML passthrough), and
  league/commonmark's `html_input` default (ALLOW).
  NOTE: GitHub's unauthenticated API rate-limits quickly; raw.
  githubusercontent.com does not, so prefer fetching files over
  querying the API.
  Victor runs the suite; failures get pasted back and traced to
  mechanism.
- Sessions can cross (see incident). Fresh-zip reset + ledger
  byte-compare is the reconciliation tool.

## House style

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

Numbering is STABLE — code comments reference these numbers. Closed
items keep their slot rather than being renumbered out.

1. **Site-side verification sweep**:
   a. ~~Confirm the Blade-compile-of-body call is DELETED~~ —
   **CLOSED**: the site renders `{!! $post->body !!}`, a raw echo.
   b. Grep the site for `factory(` on Press models before its next
   composer update — the legacy `factory()` helper left with
   laravel/legacy-factories (which had been a production
   require).
   c. Check `resources/views/vendor/press/` for published copies of
   the SEVEN views changed across sessions (nav, posts/show,
   series/index, series/show, authors/index, authors/show; plus
   series/index again for the loop-shadow fix) —
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
2. ~~CI~~ — **CLOSED** (batch F). Coverage measurement still
   optional and still ungated (pcov locally: `pecl install pcov`).
3. ~~Parsedown → league/commonmark~~ — **CLOSED** (batches I–P),
   with the full evidence trail above. Parsedown remains selectable
   and required; do not remove it while an un-migrated blog exists
   or while `press:parser-diff` needs a baseline.
4. **Preview gating decision** — HALF RESOLVED. The visit-recording
   half is decided and shipped (preview hits never record). Gating
   itself is still open: `?preview` remains UNGATED, so anyone with
   a draft's URL sees it. Now that `isEditor()` gates authoring,
   gating preview behind the same check is the CONSISTENT call and
   a one-liner — but it removes shareable draft links, so it stays a
   deliberate decision, not a drive-by.
5. `Transformers\Author` — real meta for author pages (public
   output change; the transformer-less no-op is the pinned interim
   contract). Consider `Transformers\Blog` for admin edit at the
   same time.
6. **`@imagin` as a real CommonMark node — THE NEXT BIG ONE**, and
   the prize the parser swap was for. Victor has explicitly said he
   wants to pursue the extension architecture.

   Today the directive survives ingest BY ACCIDENT: the parser does
   not recognize it, so it becomes paragraph text with `=>`
   entity-escaped, and `ImaginShortcode` re-finds it at request time
   with a regex against rendered HTML, unwrapping the `<p>` the
   parser added and reversing the escaping. Every step is a
   workaround for the parser not knowing about the directive.

   league/commonmark lets you register a real inline parser:
   `@imagin(` recognized at parse time, emitted as a dedicated node.
   No escaping to reverse, no `<p>`-unwrapping, no regex against
   HTML, and the key whitelist becomes STRUCTURAL rather than
   regex-enforced. Its extension system also IS the generic
   shortcode registry this item used to ask for, so the old
   "speculative generality" objection is gone.

   Hazards to respect when starting:
    - Expansion must STAY at request time (auth- and cache-dependent).
      The node emits a PLACEHOLDER into the stored body; it does not
      bake Imagin markup at ingest. See the rejected-items list.
    - It CHANGES the stored body format. Existing posts hold the old
      literal-text form until re-ingested, so the request-time
      expander must handle both during transition and every
      ImaginShortcode pin needs re-deriving.
    - Its own batch, after the swap beds in. Two format changes at
      once destroys bisectability.
    - `ImaginShortcode::$renderer` and `MarkdownParser::$renderer`
      are both already seams; use them rather than adding a third.
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
- **Don't turn on Parsedown's safe mode.** Raw HTML passthrough is
  spec-correct markdown, existing posts may rely on it for embeds,
  and flipping it would silently mangle published bodies at the next
  `press:process`. The answer to untrusted markup is WHO MAY AUTHOR
  (the editor gate), not what may be authored. Any parser swap must
  make this posture an explicit config choice rather than inherit
  it — league/commonmark defaults `html_input` to ALLOW and
  `allow_unsafe_links` to true, i.e. the same posture, verified in
  its source.
- Don't gate `?preview` as a drive-by — see roadmap 4; the
  recording half is settled, the access half is not.
- **Don't remove Parsedown.** It is the baseline `press:parser-diff`
  compares against and what the un-migrated second blog runs.
  Abandoned upstream is not the same as unwanted here.
- **Don't disable either autolinker.** Victor explicitly chose to
  keep both URL and email autolinking. Dropping `AutolinkExtension`
  costs 16 posts their links outright.
- **Don't run `press:normalize-source` after switching the parser.**
  It holds back the fixes it exists to make. Step 2 before step 3.
- **Don't trust one classifier's "zero differences."** Run an
  independent audit by a different method before believing it.
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
   misread in the coverage session, and the seam-view caught the swap before a
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
7. **Predictions stated on the record, scored honestly** — the
   coverage session's ledger: modernization "green first run"
   missed (app.key); coverage A green as predicted with named
   uncertainties holding; C's amendment-required call right but for
   the wrong file; C's clobber theory wrong and retracted; D's risk
   ranking inverted (expectsOutput held, route naming failed).
   The hardening session's ledger: batches E, F, G and H all called
   green first run and all green first run — but F's three named
   uncertainties ALL passed, an over-hedge that became a review rule
   above. E's one named uncertainty (`getQueryLog()[0]`) held.
   The parser-migration session's ledger, much worse: I and J green;
   K, L, M each shipped to fix a defect in the batch before it; N
   fixed an assertion that could never have passed; O was called
   green and broke SIX tests; P fixed all six and was green. Four of
   nine batches existed only to repair the previous one. Every defect
   was in analysis or test code, never in the ingest path — which is
   exactly the review rule above.
   Misses became the review rules above.
8. **The briefing is merged at session end from the original
   document in hand, never rewritten from memory.** This document
   is that merge; correct it and the corrected version becomes
   canon.
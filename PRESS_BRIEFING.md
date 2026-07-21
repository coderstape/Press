# Press — Project Briefing & Working Conventions

Upload this alongside the current repo zip at the start of a session.
A full deep dive into this package is planned; this briefing was
seeded from the Imagin/blog bug fix, so it records what is VERIFIED
from that work versus what was merely observed in passing — the deep
dive should confirm the latter before building on it.

## What this is

coderstape\Press: the blog/content package running the Sportsman
Boats production site's blog. Posts are authored as markdown with a
front-matter-style head; Press parses them (PressFileParser splits
head/body, per-field `Field\*` classes process each key, Parsedown
1.7 renders the body to HTML at ingest) and stores the result in a
`posts` table. Display is `{!! $post->body !!}` from the package's
views (which the site may override via publishing). Multiple ingest
drivers exist: FileDriver, DatabaseDriver (posts authored in a
`blogs` table via AdminPostController), GistDriver.

It is a sibling package to GrandeBerg Imagin (see IMAGIN_BRIEFING.md);
the same production site runs both, and blog posts embed Imagin image
slots.

## The Imagin integration (DONE this batch — the reason this file exists)

**The bug it fixed.** Blog markdown contains `@imagin('location' =>
..., ...)` lines. Parsedown stores them as literal paragraph text
(`<p>@imagin('location' =&gt; ...)</p>` — the `=>` entity-escaped;
reproduced byte-for-byte against Parsedown 1.7.4). The site then ran
Blade's COMPILER over the stored body, which after Imagin's
directive fix (compile-time eval() → correct deferred PHP) produced
dead `<?php echo \GrandeBerg\Imagin\...` text in the page. It had
only ever worked because of the old directive bug: compiler output
used to double as render output.

**The design.** Imagin markup is auth-dependent (editors get
data-imagin-* attributes) and cache-invalidated — it can NEVER be
baked into a stored body at ingest. So expansion happens at request
time: `Post::getBodyAttribute()` calls `ImaginShortcode::expand()`,
which finds `@imagin(...)` literals in the stored HTML and replaces
them with `Imagin::image()` output. No Blade anywhere in the path.
Load-bearing facts (all pinned in ImaginShortcodeTest, 9 tests):

- **Nothing is ever eval()'d.** Only single-quoted `'key' => 'value'`
  string-literal pairs parse; both `=&gt;` and raw `=>` forms work
  (entity-decode first). Runtime variables, function calls, or
  anything malformed is left as visible literal text — never
  executed, never silently dropped. Directives without a `location`
  are likewise left alone.
- **Keys are whitelisted** (location, width, height, alt, class,
  style, loading, decoding, fetchpriority, sizes) — mirroring
  Imagin's image-location endpoint whitelist AND its rationale:
  attribute VALUES are escaped by Imagin, attribute NAMES are not,
  and here the names come from author-controlled blog content
  calling `Imagin::image()` directly, which has no whitelist of its
  own. `test_unknown_keys_never_reach_the_renderer` is the tripwire.
  Keep this list in sync with Imagin's endpoint when params change.
- **A `<p>` wrapping a directive as its sole content is unwrapped**
  (Parsedown always produces that wrap; Imagin's empty-location
  placeholder is a `<div>`, which may not live inside a paragraph —
  browsers force-close the `<p>` and shatter layout). Inline
  directives mid-paragraph keep their paragraph.
- Values may contain parentheses inside quotes ('alt' => 'Open (402)').
- **Press does NOT depend on Imagin.** The facade is reached through
  a `class_exists` guard; without Imagin (or an injected renderer),
  bodies pass through unchanged — Press installs, boots, and tests
  green in an Imagin-free world (pinned). `ImaginShortcode::$renderer`
  is a settable hook (tests use it; sites can too). Imagin is an
  optional peer: Press *knows about* it (class name, token, facade
  FQCN, mirrored whitelist) but does not *require* it. Add
  `grandeberg/imagin` to composer `suggest` during the deep dive.
- Admin editing is unaffected: it edits raw `Blog.data`; `posts.body`
  is derived and regenerated from it.
- Known accepted quirk: PostController search LIKEs the stored
  column, so it matches raw directive text, not rendered markup
  (pre-existing behavior, unchanged, now explicable).

**Open deploy item (site-side, not in either package):** find and
DELETE the Sportsman app's Blade-compile-of-body call (grep for
`compileString` / `renderString` / `Blade::render` on the post body)
and echo the accessor's output plainly. With the accessor doing
expansion it is at best a no-op and at worst a template-injection
surface for anyone who can author posts. Not yet confirmed removed —
verify at the start of the next session. No migrations, no re-ingest:
stored bodies already contain what the expander expects, so every
historical post heals on next render.

## For future consideration (agreed, not yet done)

**Shortcode decoupling decision.** Today's shape: ImaginShortcode
lives in Press, Imagin-aware but dependency-free. The alternative:
Press ships a generic shortcode/hook registry knowing nothing about
Imagin, and the SITE registers the @imagin handler in a service
provider — `ImaginShortcode::$renderer` is already the seam, so the
flip is cheap. Trade-offs as discussed: today's shape keeps the
token pattern, no-eval parsing, whitelist, and tests first-class and
co-located (right call while both packages serve one site and the
Imagin-SaaS roadmap treats Press as a consumer); the generic shape
zeroes Imagin traces in Press at the cost of the site owning a piece
of the pipeline with less test visibility. Decide during the deep
dive, jointly with Imagin's roadmap item about a first-party
renderer contract for consumers. If Press is ever open-sourced or
reused Imagin-free, that tips the scale toward generic.

## Current state (observed; deep dive to verify before building on it)

- Namespace `coderstape\Press` (upstream lineage — the package
  started from the coderstape Press course/skeleton; whether to
  rebrand the namespace is a deep-dive question with BC implications
  for the site's imports and published config/views).
- Testbench ^9.14, `laravel/legacy-factories` ^1.1 with
  `$factory->define()` factories and `withFactories()` in the base
  TestCase; erusev/parsedown ^1.7 (abandoned upstream — candidate
  for league/commonmark migration in the deep dive, but note the
  ImaginShortcode tests pin Parsedown's exact stored-body shape, so
  a parser swap must re-verify the `<p>`-wrap and `=&gt;` facts).
- House test style: `test_snake_case` methods, flat
  `coderstape\Press\Tests` namespace even under tests/Unit and
  tests/Feature. The new ImaginShortcodeTest follows house style —
  don't impose Imagin's conventions (PHPUnit attributes, class
  factories) piecemeal; modernize deliberately in the deep dive if
  at all.
- 9 test files pre-batch + ImaginShortcodeTest (9 tests) added.
  Actual counts derived from the repo at session start, not from
  this document.
- Structure: src/{Press, PressFileParser, MarkdownParser, Post, Blog,
  Tag, Series, Author, Trending, Model, Migration}, Field/* (Body,
  Title, Date, Tags, Series, Author, Permalink, Identifier, Extra
  catch-all → JSON 'extra' column), Drivers/{File, Database, Gist},
  Actions/Database (persistence), Http/Controllers (Post, Tag,
  Series, Author, AdminPost), Transformers, Console, resources/views
  (publishable), migrations incl. 2025-07 authors additions.
- Body pipeline (verified): PressFileParser → Field\Body →
  MarkdownParser (Parsedown singleton) → stored `posts.body` →
  view `{!! $post->body !!}` → NOW through the ImaginShortcode
  accessor.

## Environment facts

- Same stack as Imagin: macOS + Laravel Herd dev, Vite site build,
  published views/config shadow package copies (assume the same
  wholesale-replacement hazard as Imagin's config until verified).
- The site consumes both packages; changes to Press's views may be
  masked by site-published overrides — when display behavior seems
  wrong, check the published copies first.
- The assistant's sandbox has no Packagist: Testbench suites can't
  run there. It lints (php -l), and executes framework-free classes
  directly as harnesses (ImaginShortcode was verified this way: 12
  standalone checks against the exact production body strings,
  including the double-space arrow and parens-in-alt). Parsedown
  itself was fetched from GitHub raw to reproduce ingest behavior —
  do that again rather than trusting memory of parser behavior.

## How we work (self-contained — carries the lessons from Imagin)

These conventions were forged across the Imagin project and this
session; they apply to Press identically. Written out in full so this
briefing stands alone without IMAGIN_BRIEFING.md in context.

### Direction & decision-making

1. **Diagnosis before code.** When something breaks, the user wants
   the mechanism first — a "here's why, and here's the two-minute
   check to confirm which fork we're on" analysis — before anyone
   writes a fix. Ordered decision trees ("view source; (i) if X →
   sanitizer, (ii) if Y → HTML-block semantics...") beat immediate
   patches. The blog bug was solved this way: hypothesis → user
   pastes evidence → byte-for-byte reproduction → fix.
2. **Architecture calls are surfaced, not buried.** When a change has
   a real design choice in it (where a fix lives, what couples to
   what), present the options with honest trade-offs and a
   recommendation, then let the user decide or veto. Coupling in
   particular must be made explicit: when Press gained knowledge of
   Imagin, the exact nature of it (optional peer via class_exists,
   no composer require, what "traces" remain, what the decoupled
   alternative costs) was spelled out on request — do that
   proactively.
3. **Invented details are flagged as vetoable.** Anything not in a
   spec or the user's words — copy strings, thresholds, fallback
   behaviors — gets called out explicitly ("judgment call you can
   veto") rather than slipped in.
4. **Decisions get made once.** Settled decisions live in the
   briefing with their rationale and are not relitigated absent new
   evidence. Rejected approaches go in the out-of-scope list WITH the
   reason, so future sessions don't rediscover the bug the rejection
   prevents.
5. **Design handoffs are locked specs.** UI/visual work arrives from
   Claude Design as a spec file; implement it 1:1 — the spec's
   values are not suggestions. Gaps in the spec are filled minimally
   and flagged (see #3).
6. **Roadmaps are ordered and agreed.** New ideas get slotted into
   the roadmap for future consideration rather than expanding the
   current batch's scope.

### Code delivery

7. **Ground truth is the user's repo**, never a parallel copy or
   memory of it. When in doubt about a file's current content, ask.
   (A parallel-branch approach once caused a silent fork on Imagin.)
8. Deliverables are **complete drop-in files** with repo-relative
   paths — zipped when more than ~2 files, individual otherwise.
   When asked for "just the changed files," deliver exactly that.
   State clearly which repo each file belongs to when more than one
   package is in play.
9. **Respect each package's house style.** Imagin uses PHPUnit
   attributes and class factories; Press uses test_snake_case and
   legacy $factory->define(). New code matches the package it lands
   in; modernization happens deliberately as its own decision, never
   piecemeal as a side effect of a fix.
10. **Load-bearing rationale lives in comments.** Security reasoning
    (the whitelist/attribute-name-injection rationale), transition
    code that must not be deleted early (legacy cache routing), and
    "this looks wrong but isn't" decisions are documented at the
    code site, so the code defends itself in review.
11. Explanations are **prose-first with the why**: what changed, what
    it costs, what to watch for. Every batch ends with explicit
    **deploy notes** (cache:clear/view:clear/config:clear
    implications, npm rebuilds, .env changes, vendor
    symlink-vs-copy, site-side follow-ups) and a **commit message**.

### Testing & verification

12. **Every behavior change ships with tests**, and pre-existing
    bugs found along the way get fixed and pinned in the same batch.
    Deliberate constraints get **tripwire tests** whose comments
    explain what loosening them would break (Imagin's
    unknown_params test; Press's unknown-keys test).
13. **Expected test counts are stated after each batch — derived
    from the repo, never from memory or briefing documents.** (An
    earlier Imagin briefing said 74 when the repo had 69; reality
    outranks the doc.) The suite is the contract: markup shapes,
    config defaults, and identity guarantees are pinned
    byte-exactly where it matters.
14. **The assistant lints everything (php -l, ESM parse) but cannot
    execute Laravel/Testbench suites** (no Packagist in its
    sandbox). It states predictions and uncertainty honestly; the
    user runs suites locally, CI runs them on push, and failures
    get pasted back, traced to mechanism, and answered with minimal
    targeted fixes.
15. **Harness what can be harnessed instead of trusting memory.**
    jsdom smoke harnesses for JS (Imagin's editor has a 49-check
    harness including regression pins); standalone PHP harnesses
    that execute the real methods against the exact pinned strings
    (Imagin's placeholder, Press's ImaginShortcode against the
    actual production bodies); fetching real dependencies from
    GitHub to reproduce behavior (Parsedown 1.7.4, byte-for-byte)
    rather than asserting what a library "probably does". Browser
    behavior the harness can't see is verified by the user against
    an ordered DevTools checklist.
16. **Debugging is collaborative**: the assistant proposes ordered
    checks, the user runs them, and **user observations outrank
    theory** — always.

### Continuity

17. **Briefing files are maintained artifacts.** They get updated at
    the end of substantive sessions (new decisions, new environment
    facts, roadmap changes) and re-uploaded at the start of the
    next, so sessions continue rather than restart. Counts and
    file-level facts in them are treated as hints; the repo is
    authoritative (see #13).
18. Cross-package work names its open items explicitly (e.g., the
    site-side Blade-compile removal below) so the next session — in
    whichever package's chat — can close the loop.

## Deep-dive agenda (seed list — reorder/extend at session start)

1. Confirm the site-side Blade-compile removal shipped; verify the
   blog renders end-to-end (populated slots AND an empty-location
   placeholder inside a post body — the `<p>`-unwrap exists for it).
2. Test suite health check: run it, fix/pin whatever's red, derive
   real counts. Decide on CI (mirror Imagin's Actions setup: matrix,
   weekly cron, composer validate, php -l).
3. Parsedown (abandoned) → league/commonmark decision, re-verifying
   the ImaginShortcode stored-body pins if swapped.
4. Legacy factories / test-style modernization decision (deliberate,
   not piecemeal).
5. The shortcode decoupling decision (see above), jointly with
   Imagin's renderer-contract roadmap item.
6. Namespace/branding, composer `suggest` for Imagin, general
   architecture review (drivers, Field system, Extra catch-all,
   Transformers), readme.

## Out of scope / already rejected

- Don't compile or Blade-render stored post bodies — that path IS
  the bug this file was born from (dead PHP at best, template
  injection by authors at worst). Render-time expansion via the
  accessor is the design.
- Don't bake Imagin markup into stored bodies at ingest (auth- and
  cache-dependent; must render per request).
- Don't loosen the ImaginShortcode key whitelist without carrying
  the attribute-name-injection rationale (shared with Imagin's
  image-location endpoint).
- Don't add Imagin to Press's composer `require` — optional peer via
  class_exists (a `suggest` entry is fine).
- Don't make the expander evaluate anything: non-literal expressions
  stay visible literal text by design.

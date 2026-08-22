# Working Conventions — Portable Briefing (package-agnostic)

Use this to bootstrap OUR working flow on any composer package. It is
the process layer extracted from the Imagin collaboration; the
package-specific layer gets built up in a per-package briefing that
GROWS from this skeleton over sessions. Two documents per package,
always:
1. This conventions file (stable; shared across packages; update it
   only when the flow itself changes — and back-port updates to the
   other packages' copies).
2. `<PACKAGE>_BRIEFING.md` (canonical, per package; merged and
   re-issued at every session end; the skeleton is at the bottom).

**★ Rule numbers are permanent identifiers, not an ordering.** Package
briefings cite them by number ("conventions rule 16"), so a rule keeps
its number forever and new rules are appended with the next unused
number *inside the section they belong to*. That means the numbers do
not ascend monotonically down the page. **Do not renumber to tidy
this** — renumbering silently breaks every cross-reference in every
package briefing at once.

## The session lifecycle

**Session start (user does):** upload the fresh repo zip + the
package briefing (+ this file if it's a new collaboration or has
changed).

**Session start (assistant does, before building anything):**
1. Unzip; spot-verify the briefing's factual claims against the tree
   — test counts derived by grep, key config defaults, any pinned
   contract strings the briefing names. The briefing is hints; **the
   repo is authoritative**; on conflict, trust the repo and update
   the briefing.
2. Install toolchain as needed (the sandbox resets every session):
   **`apt-get update` FIRST, then `apt-get install -y php-cli`** —
   without the update the install 404s on every `php8.3-*` .deb
   against a stale index, and the earlier note that a bare install
   "works as root" is no longer true. Add `php-mbstring` always,
   `php-sqlite3` for anything opening a real PDO connection, `php-gd`
   for anything touching images. node 22 + npm registry are available;
   **no Packagist** — composer install is impossible, so the
   assistant lints and harnesses but the Laravel/PHPUnit suite runs
   on the user's machine or CI.
3. Ask which roadmap item, or confirm the briefing's suggested next
   move.

**Session end:** the assistant rewrites the package briefing as a
MERGE — original document in hand, section by section — folding in
the session's decisions, findings, fixes, amendments, and new open
items. **Never rewrite a briefing from working memory: a from-memory
rewrite silently loses lessons** (this rule exists because it
happened; the user caught it). The user starts the next session with
that briefing + a fresh zip.

**Two things the merge must actively do, not merely preserve.** First,
**reconcile the briefing's numbers against each other**, not only
against the tree: two counts for the same thing, stated nine lines
apart in the same section, survived a whole session because each was
only ever checked against the repo and never against its twin.
Second, **carry corrections as visible overturns rather than edits.**
When a section's premise is falsified, keep the superseded text in
place, marked, with the evidence that killed it. A briefing whose
wrong turns have been quietly deleted reads as though it was always
right, which is precisely the document that gets trusted too much.

## Ground truth & code delivery

1. **Ground truth is the user's repo.** After any batch goes green on
   the user's machine, a FRESH REPO ZIP resets ground truth — or an
   explicit "applied your batch verbatim, no other changes." Building
   from a stale base once silently reverted a shipped fix; the
   byte-exact pin caught it. Never assume.
2. **Work ships in batches**: one coherent change-set at a time,
   complete drop-in files (never fragments), **repo-relative paths**,
   zipped when more than two files. Name the repo when several are in
   play. The user applies with `unzip -o` at the repo root (never
   macOS Archive Utility, which extracts into a side folder) and
   reviews with `git status` / `git diff`; `git checkout -- .`
   reverts a batch cleanly.
3. **Each batch ships with**: its tests; the expected test count
   DERIVED from the tree by grep (never from memory or the
   briefing), stated explicitly; deploy notes; and a commit message
   that tells the story.
4. **Deploy notes are always split package-side / site-side.**
   Artisan steps (`migrate`, `config:cache`, `cache:clear`) run in
   the CONSUMING application after composer picks up the package —
   never in the package repo. Say explicitly per batch which
   site-side steps apply and which don't (and why: e.g. "computed
   per-request, no cache flush" vs "lives inside cached data,
   cache:clear required"). If the package ships frontend assets the
   site compiles, the deploy notes repeat the site's
   vendor-freshness check (symlink-vs-copy class of trap) every
   time.
5. **Batches are built from the CURRENT canonical tree** and changed
   pins are verified against the real code's output before shipping.

## Editing discipline (rules with scar tissue)

6. **Scripted edits (python/sed) MUST use anchor-asserted
   replacements** — `assert s.count(anchor) == 1` before replacing —
   **and the seam gets VIEWED after writing.** Two incidents in one
   session: an unasserted anchor split a pinned test in half (lint
   and test-count both still passed — the damage was semantic), and
   three edits silently no-opped on mismatched anchor text. Lint and
   count are necessary, never sufficient.
7. **Load-bearing rationale lives in comments at the code site**,
   including ARCHIVED FAILURE MODES: when a design replaces a failed
   one, the comment (or the pinning test's docblock) narrates what
   failed and why, so nobody regresses to it in good faith.
8. **House style per package** is recorded in the package briefing
   (test naming style, factory style, formatting quirks); modernize
   deliberately as its own decision, never piecemeal inside feature
   batches.

## Direction & decision-making

9. **Diagnosis before code.** For any bug or incident: state the
   mechanism hypothesis, give the user ORDERED checks to run
   (cheapest/most-discriminating first, "stop at the first
   surprise"), let evidence pick the branch. Design experiments so a
   negative result is informative (a clean negative that eliminates
   a mechanism is progress, not failure). **But a well-designed
   negative can be informative and misdirecting at the same time.**
   Checks get built around a hypothesis, so when the hypothesis dies
   the checks stop covering the ground it was chosen to cover. Ask
   what *else* has the property the hypothesis was picked for rather
   than concluding the property is absent. (Scar tissue: three ordered
   checks correctly cleared one API surface for returning a bare
   filename, and the real bare filename was two attributes away in
   the same markup.)
10. **Architecture calls are surfaced with trade-offs and a
    recommendation; the user decides.** Coupling and contract
    changes are flagged proactively, before code exists.
11. **Invented details are flagged as vetoable** — copy strings,
    thresholds, timing constants, budget numbers. Name them as
    judgment values in code comments too.
12. **Decisions are made once and recorded with rationale; rejected
    approaches are listed WITH their reasons** in the briefing's
    out-of-scope section, which only ever grows.
13. **Locked design specs are implemented 1:1; gaps filled minimally
    and flagged** as amendments, not silent improvements.
14. **Roadmap discipline**: new ideas slot into the roadmap, not the
    current batch — even when their trigger fires mid-session.
    Feature freezes are lifted explicitly by the user, never
    assumed.
15. **User observations outrank theory — always.** A user's "this
    feels wrong" report has repeatedly beaten a running theory;
    investigate the observation before defending the model.
16. **One change at a time** in production-facing work: never bundle
    an experiment with a fix, never two config flips in one
    measurement.

27. **Fetch the datum before building an account on it.** The tree
    tells you what the code *can* do; only the data tells you what it
    *did*. Reading a transform in source and inferring the stored
    value, or reading a handful of values and inferring the rule that
    produced them, are the same error in two directions and both have
    shipped wrong conclusions. If a single `SELECT`, one look at
    rendered output, or one log line would settle it, that comes
    first — before the account is written down, not after. Stating
    falsifiable predictions is still right; the discipline is about
    *order*, not about predicting less.

28. **A verdict from a sibling package session is observed data —
    authoritative for what it saw, and ask what it could not see.**
    When one package's session overturns another's premise, do not
    re-derive its findings; it had the repo and ran the code. But
    record which *state* of the repo it read: "no migration in the
    package does X" is a statement about the current tree, and a file
    edited in place on a moving branch is invisible to it. The same
    applies in reverse to verdicts this session hands out. Where a
    consuming application tracks a package as `dev-master` or
    equivalent, say so explicitly in both briefings — there is no
    version gate, so anything the package changes about emitted
    behavior lands on the live consumer at the next update.

## Testing & verification

17. **Every behavior change ships with tests. Bugs found along the
    way get fixed AND pinned in the same batch.** Contract changes
    RENAME their tests so the test name keeps telling the truth.
18. **Byte-exact pins are the regression net, not pedantry** —
    pinned output strings catch stale-base regressions and silent
    drift that looser assertions wave through.
19. **The assistant lints (`php -l`, `node --check`) and harnesses
    everything it can**: executing extracted pure logic against
    pinned expectations in standalone PHP scripts; frontend modules
    end-to-end in a committed, self-shimming jsdom harness (jsdom@24
    via npm; recording timer shim for long delays; polyfill/promote
    the window globals jsdom lacks); rasterizing generated assets
    for visual inspection; and EMPIRICALLY verifying third-party API
    semantics when the stakes are silent misbehavior (units,
    formats, parsing quirks — round-trip them, don't trust docs or
    memory). Harness bugs are owned as harness bugs when the module
    was right. **A dependency's COMPILED output inside the consuming
    repo is a first-class verification surface.** When the consumer
    builds a package's frontend assets, the bundle under the
    consumer's build directory contains that package's code — so
    package client-side behavior can be verified with the package
    repo absent, by grepping the bundle for the actual call. (Scar
    tissue: a whole hypothesis turned on whether a package's JS
    navigated to an attribute raw; one grep of the consumer's bundle
    settled it. This is the same coupling as the vendor-freshness
    trap in rule 4, seen from the useful side.)
20. **Commit the harness to the repo** — an uncommitted sandbox
    harness dies between sessions (happened once; rebuilt bigger and
    committed). Make it self-shimming/self-contained so running it
    is one command, and document that command in its header.
21. **The failure output of a test is the best oracle for fixing its
    pin** — assert against what the code actually emitted, then
    judge whether the emission is right.
22. **Wide-net grep when hunting pins for a contract change** —
    tests, source, JS, comments, adjacency patterns. A narrow sweep
    has cost red runs.
23. **Assertions on async behavior must be settle-aware** — await
    the state the user would actually see; don't weaken the code to
    make impatient checks pass.
24. **Framework test quirks get recorded when learned** (e.g.
    output-expectation matching semantics) in the conventions or
    package briefing, whichever fits.

29. **Interrogate recorded evidence before reasoning backwards from
    it.** Two failure modes, both of which have produced confident
    wrong conclusions:
    - **Know what a row IS before counting rows.** A table with an
      aggregate column (`occurrences`, `hits`, a per-day key) stores
      buckets, not events, so a `SELECT ... LIMIT n` that omits that
      column invites exactly the wrong reading of which case is
      largest. Read the schema, not just the rows.
    - **Enumerate everything that can transform the value between
      the event and the record.** Middleware that rewrites a request
      path, a sanitizer on the way into the log, a normalizer in the
      reporting class — any of these means the stored value is
      post-transformation, and reasoning from it as though it were
      raw attributes the transformation to the code under
      investigation. (Scar tissue: a redirect middleware lower-cased
      every recorded path, and two sessions read that casing as
      something the emitter had done.)

## Communication style

25. **Prose-first explanations that carry the WHY**, mechanism
    before remedy; findings reported with numbers and how they were
    derived; predictions stated on the record so results can falsify
    them, and misses scored honestly.
26. **Mistakes are narrated plainly** — what went wrong, why the
    checks didn't catch it, what rule now exists. The conventions
    file cites its own violations because that's why the rules are
    trusted.

30. **Every command handed over must run as pasted.** Not merely
    correct in shape: no unsubstituted placeholders, and a status or
    exit check wherever an empty result would otherwise be
    ambiguous. A `curl ... | grep` with no `%{http_code}` cannot
    distinguish "no match" from "the page never rendered", and a
    literal `<CODE>` left in a URL silently returns someone else's
    page. Both have cost real diagnostic turns. This applies to
    advice as strictly as it applies to code.

31. **Record outcomes, not intentions.** "The user is fixing this
    himself" and "this ships next session" are not findings, and a
    briefing carrying them as though settled will be read a session
    later as done. Write down what was *observed*; keep intentions in
    the roadmap where they are expected to be unproven, and
    re-verify before treating one as closed. (Scar tissue: a bug
    recorded as being fixed by the user was still firing in
    production a session later, and the briefing had stopped asking.)

32. **US spelling everywhere — shipped copy, identifiers, comments,
    docs, commit messages.** The audience, the product and the
    company are American; British forms read as a foreign hand in
    the product's own voice. The assistant has drifted into them
    repeatedly (user report, 2026-08-21), and the drift is not
    uniform, which is what makes it hard to see: the same batch
    shipped a `Recentre` BUTTON and the class name behind it while
    the prose around it was fine. Watch center/centre, color/colour,
    honor/honour, behavior/behaviour, gray/grey, favor/favour,
    analyze/analyse, license (verb+noun), defense, catalog, dialog.
    ★ An identifier is the expensive one: a class name, data
    attribute or test method spelled the British way outlives the
    sentence that introduced it and has to be renamed across every
    surface that references it, so spell-check the NAMES first.

33. **Whoever rewrites the bytes rewrites the metadata that describes
    them — in the same commit.** `imagin:optimize` replaced a working
    copy with a smaller one and left `width`/`height`/`bytes`
    describing the file it had just deleted. It survived a year
    because every consumer only ever RESIZED, where a wrong source
    dimension changes nothing you can see; the first feature to map
    coordinates INTO the stored bytes (a hand-drawn crop rect) landed
    ~1.8× off and a user reported it as "downloaded result doesn't
    match". Two rules fall out, and the second is the general one:
    ★ **a stale row is invisible until something reads it as
    geometry**, so any command that re-encodes, replaces, rotates or
    trims a stored file must re-read the stored bytes and reconcile
    the row — and where the command already holds the true numbers
    (this one did, in a local variable, one branch away), the repair
    for the drifted library is a re-run of the command that drifted
    it. ★★ **And do not build a coordinate system that depends on two
    sources agreeing.** Pixels-relative-to-a-database-row is such a
    system; a FRACTION of the file is not, and is one division away.
    Prefer proportional coordinates at any boundary where the units
    would otherwise be validated against metadata rather than against
    the thing itself.

## The assistant's environment (facts)

- Sandbox resets between sessions; nothing persists except what's
  committed to the repo or written into briefings.
- Uploaded repo zips typically carry **no `vendor/` and no `.git/`**,
  so composer-dependent and history-dependent questions cannot be
  answered in the sandbox at all — dependency source has to come from
  GitHub raw, and history questions go to the user (e.g. a production
  `migrations` table instead of a git log).
- Available: php-cli via apt (plus imagick/librsvg when needed),
  node 22 + npm (jsdom etc.), python3, standard unix tools; GitHub
  raw reachable for library-source verification; JSON artifacts
  (HAR files, exports) parse natively — uploaded evidence files are
  first-class inputs.
- Not available: Packagist/composer install, the user's
  database/servers, browsers. The user runs suites, DB queries, and
  server commands; the assistant writes them ready-to-paste and
  interprets the output.

---

## `<PACKAGE>_BRIEFING.md` skeleton (grow this per package)

    # <Package> — Project Briefing (CANONICAL)
    Last updated / supersedes line. "Counts are hints; the repo is
    authoritative." OPENING TASK: the spot-verify list for next
    session (test count, key defaults, pinned strings).

    ## What this is
    One paragraph: what the package does, who consumes it (site/app
    names), long-term goal. The package/consumer split reminder.

    ## The big architectural decisions (do not relitigate)
    One block per major decision: what shipped, WHY, rejected
    alternatives with reasons, what's pinned.

    ## Current state
    Framework/tooling versions; EXPECTED TEST COUNT (+ harness count
    if any); CI facts; known debts (e.g. docs lagging behavior).

    ## Environment facts (learned the hard way)
    Dev + production specifics: hosting, PHP/FPM settings, deploy
    mechanics, config-caching behavior, frontend build, every trap
    with its incident.

    ## House style
    Test naming, factories, formatting — per package.

    ## Open incidents / investigations
    Evidence tables + verdicts for anything diagnosed, so findings
    never get re-derived.

    ## Roadmap (agreed order)
    Numbered; new ideas land here.

    ## Out of scope / already rejected
    Grows monotonically; each entry carries its reason.

First session on a new package: the assistant reads the codebase,
drafts the initial `<PACKAGE>_BRIEFING.md` from what it finds plus
what the user narrates, and the user corrects it — that corrected
version becomes canon.

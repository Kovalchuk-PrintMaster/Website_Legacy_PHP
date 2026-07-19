# ForPrint search visibility and web quality strategy v0.1

**Status:** active planning policy
**Scope:** public ForPrint website
**Created:** 2026-07-19
**Purpose:** ensure that each actual Internet release remains crawlable, indexable, fast, understandable, measurable, and suitable for long-term search promotion.

## 1. Strategic goal

ForPrint must not become only a visually acceptable website.

The public website must also be:

- technically accessible to search engines;
- logically organized for users and crawlers;
- fast and stable on common devices;
- capable of producing unique page metadata;
- capable of exposing structured product, service, business, and breadcrumb data;
- measurable through search and performance tools;
- safe to evolve without losing existing URLs and search visibility.

Search visibility is treated as an architectural concern, not as a final marketing patch.

## 2. Core policy

Every public page intended for search must have:

1. one stable preferred URL;
2. a correct HTTP status;
3. server-rendered or crawlable primary content;
4. a unique page title;
5. an appropriate meta description;
6. one clear primary heading;
7. meaningful visible text;
8. crawlable internal links;
9. correct canonical and robots directives;
10. usable mobile presentation;
11. controlled image loading and dimensions;
12. measurable performance.

No page is considered search-ready solely because it looks correct in a browser.

## 3. Current known risks

The current public header has several search-readiness issues that must be corrected before or shortly after the first Internet release:

- the root document language is currently declared as `ru`, while the public content is primarily Ukrainian;
- the page title is currently hardcoded as `Index`;
- the viewport configuration disables or strongly restricts user zoom;
- global legacy CSS and several JavaScript libraries are loaded on every public route;
- multiple GSAP generations are currently registered globally;
- page-specific metadata ownership is not yet formalized;
- canonical URLs, robots directives, sitemap ownership, and structured data ownership are not yet established.

These are recorded as known technical debt, not as reasons to block current visual work.

## 4. Search architecture ownership

### 4.1. Global search metadata owner

The public base controller or a dedicated frontend metadata service should own:

- site name;
- canonical production origin;
- default title template;
- default meta description fallback;
- default robots policy;
- document language;
- Open Graph defaults;
- social image fallback;
- organization and local-business identity;
- production versus preview indexation policy.

Page controllers provide page-specific metadata but must not independently invent global rules.

### 4.2. Page controller responsibility

Each public route controller should provide a metadata object containing, as applicable:

```text
title
description
canonical_url
robots
page_type
primary_heading
open_graph
structured_data
breadcrumbs
```

The header template should render this object consistently.

### 4.3. Content authority

Product and service names, descriptions, categories, images, and semantic relations should come from the canonical ForPrint catalog authority rather than being recreated in SEO-only tables.

Search metadata may extend canonical content, but must not become a competing product catalog.

## 5. URL policy

### 5.1. Stable URLs

Public URLs must be:

- readable;
- lowercase where practical;
- separated with hyphens rather than spaces or underscores;
- stable across redesigns;
- free from internal database terminology;
- independent of template file names.

Preferred examples:

```text
/catalog/bloknoty
/product/bloknot-a5-z-tysnenniam
/services/druk-na-futbolkakh
/news/yak-pidhotuvaty-maket-do-druku
```

The final route scheme must be based on actual catalog semantics.

### 5.2. One preferred URL

Each indexable page must have one preferred canonical URL.

Potential duplicates include:

- HTTP and HTTPS;
- `www` and non-`www`;
- trailing slash variations;
- route aliases;
- tracking query parameters;
- sorting and filtering parameters;
- duplicated category routes;
- old URLs after renaming.

Preferred handling order:

1. permanent redirect when the old URL should no longer exist;
2. canonical annotation when duplicate access must remain available;
3. `noindex` for useful user pages that should not appear in search;
4. authentication for private pages.

### 5.3. Redirect policy

When a public URL changes:

- use a permanent server-side redirect;
- redirect directly to the closest semantic replacement;
- avoid redirect chains;
- preserve query data only when it has real meaning;
- update internal links and sitemap entries;
- keep redirect mappings under version control or controlled configuration.

### 5.4. Query and filter URLs

Catalog filtering, sorting, pagination, and search results need explicit policy.

Default planning assumption:

- core category pages are indexable;
- internal search-result pages are `noindex`;
- arbitrary filter combinations are not indexable by default;
- approved commercial landing filters may become dedicated stable pages;
- sorting parameters should not create independent indexed pages;
- pagination must remain crawlable through normal links.

The exact rules must be confirmed after the catalog URL model is audited.

## 6. Indexation policy

### 6.1. Public indexable content

Expected indexable surfaces:

- home page;
- category pages;
- approved product or service pages;
- contact and business information pages;
- useful informational articles;
- news items that provide lasting value;
- dedicated promotional landing pages when they contain unique content.

### 6.2. Non-indexable content

Expected non-indexable surfaces:

- admin;
- account and private user areas;
- cart and checkout;
- internal search results;
- temporary preview routes such as `/test-home/`;
- duplicate filtered or sorted views;
- diagnostic endpoints;
- development and staging environments;
- incomplete placeholder pages.

### 6.3. Robots and noindex

`robots.txt` controls crawling, not reliable removal from search.

Use:

- password or access control for private environments;
- `noindex` for publicly reachable pages that must not appear in search;
- `robots.txt` for crawl management;
- consistent canonical and sitemap signals.

Do not block a page in `robots.txt` when Google must crawl it to see a `noindex` directive.

## 7. Page metadata contract

### 7.1. Document language

The production Ukrainian site should render:

```html
<html lang="uk">
```

Additional language versions, if introduced later, require separate stable URLs and explicit localization policy.

### 7.2. Title

Every indexable page must have a unique, concise, descriptive title.

Planned title patterns:

```text
Home:
PrintMaster — друк і брендування у Києві

Category:
Друк блокнотів на замовлення у Києві | PrintMaster

Product or service:
Блокноти з тисненням на замовлення | PrintMaster

Article:
Як підготувати макет до друку | PrintMaster
```

Titles must be generated from controlled data and remain manually overridable where needed.

### 7.3. Meta description

Each important page should have a useful page-specific summary.

Rules:

- describe the actual page;
- avoid copied descriptions across many pages;
- include practical value rather than keyword lists;
- do not promise prices or deadlines that the page cannot support;
- provide an automatic fallback only when no approved description exists.

### 7.4. Headings and visible text

Each page should have:

- one clear visible primary heading;
- supporting section headings;
- useful visible text near products and images;
- content that explains what the user can order, how it is produced, and what choices exist.

Visual cards without textual context are insufficient as the long-term content strategy.

### 7.5. Social metadata

Open Graph and related sharing metadata should be generated from the same page metadata object:

```text
og:type
og:title
og:description
og:url
og:image
og:site_name
```

Social metadata supports link sharing but does not replace search metadata.

## 8. Site structure and internal linking

The target hierarchy should be understandable without relying on search forms or JavaScript interactions.

Example:

```text
Home
├── Catalog
│   ├── Business printing
│   ├── Souvenir products
│   ├── Textile branding
│   ├── Signs and outdoor advertising
│   └── Product or service page
├── Services
├── Promotions
├── Knowledge base
├── News
├── About
└── Contacts
```

Rules:

- important pages must be reachable through crawlable `<a href>` links;
- category pages should link to children;
- product pages should link back to relevant categories;
- related products should use meaningful links;
- articles should link to relevant products or services where genuinely useful;
- orphan pages are not allowed;
- navigation wording should reflect catalog semantics.

## 9. Breadcrumb policy

Breadcrumbs should show the logical user path, not merely repeat raw URL segments.

Example:

```text
Головна → Каталог → Блокноти → Блокноти з тисненням
```

Breadcrumbs should be:

- visible to users;
- rendered with normal links;
- generated from canonical catalog relations;
- accompanied by `BreadcrumbList` structured data after the hierarchy is stable.

## 10. Structured data strategy

Structured data must describe visible factual content. It must not invent reviews, availability, prices, addresses, or other claims.

### Phase A

Add site and business identity:

- `Organization`;
- `LocalBusiness` when the physical Kyiv business data is confirmed;
- `WebSite`;
- site name and canonical URL.

### Phase B

Add navigation context:

- `BreadcrumbList` on hierarchical pages.

### Phase C

Classify commercial pages correctly:

- use `Product` and `Offer` only when the page represents a concrete purchasable product with valid offer data;
- consider `Service` when the page primarily represents a printing or branding service;
- do not place product rich-result markup on generic category lists;
- do not expose stale or fabricated price and availability values.

ForPrint must first classify each catalog entity as product, service, configurable product, or category before implementing broad structured data.

### Phase D

Consider additional supported data only when the underlying business process can keep it accurate:

- shipping information;
- return policy;
- organization policies;
- real ratings and reviews;
- article or news markup.

## 11. Product and service content policy

A commercially useful page should eventually include:

- clear name;
- short value-focused introduction;
- relevant images;
- material and production options;
- available sizes or formats;
- finishing options;
- quantity or calculation guidance;
- realistic production notes;
- order or consultation action;
- related categories or products;
- unique FAQ only when the answers are useful and true.

Avoid:

- copied manufacturer descriptions;
- machine-generated keyword repetition;
- empty pages created only to target phrases;
- nearly identical pages differing by one word;
- hiding relevant text only inside images.

## 12. Image search and media policy

Images are part of search visibility and performance.

Rules:

- use descriptive file naming when practical;
- use meaningful `alt` text for informative images;
- use empty `alt=""` for purely decorative images;
- provide visible text near important imagery;
- keep image URLs crawlable;
- provide width and height or a stable aspect ratio;
- generate responsive image variants;
- use modern formats when supported;
- avoid loading full production-resolution images into small cards;
- lazy-load below-the-fold images;
- do not lazy-load the likely hero or LCP image without performance testing;
- preserve a stable image URL strategy when assets are replaced.

## 13. Performance and Core Web Vitals policy

Search promotion and user experience require measurable performance.

The primary quality metrics are:

- LCP — loading of the main visible content;
- INP — responsiveness to user interactions;
- CLS — visual stability.

Development observations are not enough. A page may feel fast after caching and slow on first load.

### 13.1. Measurement policy

Use:

- Chrome DevTools Performance panel during development;
- Lighthouse for repeatable laboratory checks;
- Search Console Core Web Vitals after public traffic exists;
- server and browser timings for route regressions;
- field measurement later when sufficient traffic exists.

Baseline checks should include:

```text
home
catalog category
product or service page
contact page
article or news page
```

### 13.2. CSS policy

During development, separate `forprint-*.css` ownership files are allowed and preferred for maintainability.

For production optimization, consider a controlled build or deployment step that:

- minifies project-owned CSS;
- fingerprints files by content;
- enables long-lived browser caching;
- optionally combines stable shared CSS into one modern bundle;
- keeps page-specific CSS separate when that avoids unnecessary downloads;
- preserves source files as the development truth.

Do not manually maintain a giant minified CSS file.

### 13.3. JavaScript policy

Audit and remove duplicate or obsolete globally loaded libraries.

Specifically verify whether all currently registered GSAP versions are required.

Rules:

- page-specific JavaScript should not load globally without need;
- use `defer` for suitable scripts;
- avoid blocking initial rendering;
- measure before and after removal;
- preserve functionality with focused checks.

### 13.4. Image policy

The home page is likely to be more sensitive to cold-cache loading because it contains hero and catalog imagery.

Performance work should prioritize:

1. correctly sized images;
2. explicit dimensions or aspect ratios;
3. hero image prioritization;
4. below-the-fold lazy loading;
5. compression and modern formats;
6. avoiding duplicated image downloads.

### 13.5. Cache policy

Development and production need different cache behavior.

Development:

- version query strings may be used for controlled cache busting;
- `Ctrl+F5` intentionally bypasses normal browser cache and should not be treated as representative repeat-view speed.

Production target:

- hashed or versioned static assets;
- long cache lifetime for immutable assets;
- compression enabled;
- HTML cached conservatively according to dynamic content needs;
- cache invalidation tied to deployment.

## 14. Sitemap policy

Generate an XML sitemap from canonical indexable routes.

The sitemap should include:

- canonical URLs only;
- pages that return `200`;
- pages allowed to be indexed;
- valid last-modified values only when meaningful.

Do not include:

- admin and account pages;
- cart;
- internal search;
- duplicate filters;
- redirected URLs;
- `noindex` pages;
- temporary preview routes;
- error pages.

The sitemap must be discoverable from `robots.txt` and submitted through Search Console after deployment.

## 15. HTTP and error handling

Expected behavior:

```text
200 — valid public page
301 or 308 — permanent URL replacement
302 or 307 — genuinely temporary redirect
404 — missing page
410 — intentionally removed content when appropriate
5xx — server error, never a normal missing-page response
```

Requirements:

- missing products and pages must not return fake `200` responses;
- error pages should remain useful to users;
- redirects must not lead to irrelevant destinations;
- production must enforce HTTPS;
- one host format must be canonical.

## 16. Security and accessibility quality

Search readiness includes basic quality safeguards:

- HTTPS;
- no production secrets in templates;
- no public indexing of private user data;
- keyboard-usable navigation;
- visible focus states;
- sufficient text contrast;
- form labels and error messages;
- user zoom must not be unnecessarily disabled;
- semantic HTML for content and navigation.

## 17. Measurement and operational ownership

After the first Internet release, establish:

- Google Search Console;
- XML sitemap submission;
- production-domain verification;
- URL inspection workflow;
- index coverage monitoring;
- Core Web Vitals monitoring;
- structured-data validation;
- query and landing-page review;
- 404 and redirect monitoring;
- periodic crawl and metadata checks.

Analytics may be introduced separately under a privacy-aware measurement policy.

Search Console is the primary source for how Google sees the site; analytics is the primary source for what users do after arriving.

## 18. Actual release roadmap

### Release 1 — public stabilization

Primary goal: publish a usable and technically safe website.

Required or strongly recommended:

- stable production domain and HTTPS;
- correct `lang="uk"`;
- dynamic page title instead of `Index`;
- basic per-page description support;
- production canonical origin;
- explicit preview and private-route `noindex`;
- valid `robots.txt`;
- basic XML sitemap;
- correct main HTTP statuses;
- usable mobile geometry;
- no critical visual or ordering bugs;
- initial performance baseline;
- Search Console connection after deployment.

Not required for Release 1:

- complete structured data coverage;
- full content expansion;
- advanced commercial landing pages;
- perfect Core Web Vitals on every route;
- removal of all legacy CSS.

### Release 2 — search foundation

Primary goal: make the site consistently understandable and discoverable.

Planned work:

- metadata service or metadata object contract;
- unique title and description templates;
- canonical handling;
- approved URL policy;
- breadcrumb navigation;
- sitemap automation;
- internal linking audit;
- image alt and dimension policy;
- category and product text improvements;
- removal of duplicate and thin pages from indexation.

### Release 3 — commercial semantics

Primary goal: represent ForPrint offerings accurately in search systems.

Planned work:

- product versus service classification;
- `Organization` and `LocalBusiness`;
- `BreadcrumbList`;
- suitable `Product`, `Offer`, or `Service` markup;
- Search Console rich-result monitoring;
- stable price and availability ownership;
- approved commercial landing pages;
- improved category descriptions and related links.

### Release 4 — performance and growth

Primary goal: improve search reach and conversion using measured evidence.

Planned work:

- production asset build and caching;
- removal of duplicate JavaScript libraries;
- image pipeline optimization;
- Core Web Vitals improvement;
- query and landing-page analysis;
- content plan based on real demand;
- systematic redirect and 404 monitoring;
- periodic technical SEO inspection.

## 19. Change workflow

Every search-related change follows:

```text
policy or requirement
→ scoped implementation
→ local validation
→ browser and crawler-facing check
→ clean Git checkpoint
→ actual deployment
→ Search Console or field verification
```

Do not combine unrelated visual, URL, metadata, and content migrations into one unreviewable change.

## 20. Definition of search-ready

A public page is search-ready when:

- it has a stable canonical URL;
- it returns the intended HTTP status;
- it is intentionally indexable or intentionally excluded;
- its primary content is crawlable;
- title, description, language, and heading are correct;
- internal links can discover it;
- images have appropriate semantics and dimensions;
- mobile content is complete;
- no critical layout instability is observed;
- structured data, if present, matches visible facts;
- the page is included or excluded from the sitemap correctly;
- a responsible owner exists for its data and metadata.

## 21. Near-term actions

1. Preserve the current visual stabilization work.
2. Inspect and resolve the unexpected working-tree change in `forprint-layout.css`.
3. Add this strategy document as a separate checkpoint.
4. Audit current metadata generation and header ownership.
5. Implement dynamic language, title, description, canonical, and robots support.
6. Create Release 1 robots and sitemap requirements.
7. Establish a simple performance baseline before further asset restructuring.
8. Continue visual geometry migration without expanding legacy CSS.

## 22. Primary references

- Google Search Central: SEO Starter Guide
  - https://developers.google.com/search/docs/fundamentals/seo-starter-guide
- Google Search Central: Developer SEO guide
  - https://developers.google.com/search/docs/fundamentals/get-started-developers
- Google Search Central: Canonicalization
  - https://developers.google.com/search/docs/crawling-indexing/canonicalization
- Google Search Central: Sitemaps
  - https://developers.google.com/search/docs/crawling-indexing/sitemaps/overview
- Google Search Central: Remove or noindex content
  - https://developers.google.com/search/docs/crawling-indexing/remove-information
- Google Search Central: Product structured data
  - https://developers.google.com/search/docs/appearance/structured-data/product
- Google Search Central: Breadcrumb structured data
  - https://developers.google.com/search/docs/appearance/structured-data/breadcrumb
- web.dev: Web Vitals measurement
  - https://web.dev/articles/vitals-measurement-getting-started
- web.dev: CSS and Web Vitals
  - https://web.dev/articles/css-web-vitals
- Schema.org
  - https://schema.org/
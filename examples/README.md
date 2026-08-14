# SEO & AEO Content Platform — Public Code Examples

This folder contains simplified, sanitized examples based on implementation patterns from a private production SEO/AEO content platform.

The system models website pages in PostgreSQL, organizes them into a geographic hierarchy, generates repeatable local SEO/AEO defaults, assembles related records into page-level JSON documents, and makes those documents available to reusable WordPress templates.

The public examples show the architecture without publishing production credentials, private market data, pricing rules, internal endpoints, or company-specific content.

These files are representative portfolio examples rather than copies of the production codebase.

---

## Architecture Represented

```text
                         Markets
                            │
                         Services
                            │
                        Promotions
                            │
                            ▼
                        SEO Pages
                     /      |       \
                    /       |        \
                   ▼        ▼         ▼
              Keywords    FAQs    Content Sections
                    \       |        /
                     \      |       /
                      ▼     ▼      ▼
                       Internal Links
                            │
                            ▼
                  Page Content View
                            │
                            ▼
                     Supabase REST
                            │
                            ▼
                 WordPress Route Client
                            │
                            ▼
                  Reusable Page Template
                       /           \
                      ▼             ▼
                HTML Content     SEO / JSON-LD
```

The database remains the structured content source while WordPress remains the presentation layer.

---

## Included Examples

### [`supabase-page-content-view.sql`](supabase-page-content-view.sql)

Shows how relational SEO records can be assembled into one page-level JSON document.

**Demonstrates:**

- PostgreSQL views
- `LEFT JOIN`
- `jsonb_build_object`
- `jsonb_agg`
- ordered JSON aggregation
- active-record filtering
- empty-array fallbacks with `COALESCE`
- hierarchical page metadata
- market, service, and promotion relationships
- keywords, FAQs, sections, and internal links

The view gives the WordPress layer one content object instead of requiring separate API requests for every related table.

---

### [`local-content-generator.sql`](local-content-generator.sql)

Shows the database-side generation pattern used for local market pages.

**Demonstrates:**

- PL/pgSQL
- hierarchy validation
- geographic-scope validation
- `seo_locked` content governance
- technology-aware SEO defaults
- technology-aware keywords
- technology-aware FAQs
- reusable content sections
- deterministic delete-and-regenerate behavior
- orchestration through a content-bundle function

The model is intentionally deterministic. A page can receive repeatable defaults while manually curated pages can be protected from automatic SEO overwrites.

---

### [`seo-content-client.php`](seo-content-client.php)

Shows the WordPress integration layer that resolves the current request path and retrieves the matching published page bundle from Supabase.

**Demonstrates:**

- WordPress request-path normalization
- REST API construction
- Supabase request headers
- published/active filtering
- response validation
- safe failure behavior
- separation between data retrieval and template rendering

---

### [`structured-data-builder.php`](structured-data-builder.php)

Shows how a retrieved page bundle can be converted into JSON-LD.

**Demonstrates:**

- `WebPage` schema
- `Service` schema
- `FAQPage` schema
- independent `include_in_schema` filtering
- canonical identifiers
- geographic service-area data
- sanitized structured output

The example keeps schema eligibility separate from whether the same FAQ is displayed visibly on the page.

---

### [`simplified-local-town-template.php`](simplified-local-town-template.php)

Shows how one reusable WordPress template can render different local pages using Supabase data while retaining WordPress fallback values.

**Demonstrates:**

- ACF with native custom-field fallback
- route-based Supabase content lookup
- fallback SEO fields
- Supabase SEO overrides
- dynamic content sections
- visible FAQ filtering
- JSON-LD generation
- robots directives
- Open Graph metadata
- canonical URLs
- WordPress sanitization and escaping

---

### [`sample-page-record.json`](sample-page-record.json)

Shows a synthetic relational page record before aggregation.

---

### [`sample-api-response.json`](sample-api-response.json)

Shows a synthetic page-content bundle after the relational data has been assembled by the view.

---

### [`sample-rendered-page.md`](sample-rendered-page.md)

Shows how the same structured record could appear after rendering through the reusable WordPress page layer.

---

## Content Hierarchy

The underlying page model supports hierarchical website structure.

```text
Homepage
   ↓
National / Service-Area Landing Page
   ↓
State Page
   ↓
Local Market Page
```

A page can reference another page through `parent_page_id`, while `hierarchy_level`, `geographic_scope`, `state_code`, `locality_name`, `locality_type`, and `technology` describe its role.

That allows the content model to understand a page as more than a URL string.

---

## Generated vs. Curated Content

A key design decision is the `seo_locked` flag.

```text
Local Page
    ↓
Eligible for Generation?
    ↓
seo_locked?
 /         \
Yes         No
 ↓           ↓
Preserve   Generate Defaults
```

This allows repeatable generation to coexist with manual editorial work.

The public generator demonstrates this pattern for:

- SEO titles
- meta descriptions
- primary keywords
- H1 content
- answer-engine summaries
- key answers
- keyword records
- FAQ records
- page content sections

---

## Relational Data to Page Document

WordPress does not need to understand the full relational database.

The view converts normalized relational data into a page-oriented document.

```text
seo_pages
   +
seo_markets
   +
seo_services
   +
seo_promotions
   +
seo_page_keywords
   +
seo_page_faqs
   +
seo_content_sections
   +
seo_internal_links
        ↓
seo_page_content_public
        ↓
One JSON Response
```

That response can contain:

```text
Page metadata
SEO metadata
AEO summary
Hierarchy
Locality
Technology
Market
Service
Promotion
Keywords[]
FAQs[]
Content Sections[]
Internal Links[]
```

---

## SEO and AEO Responsibilities

The platform separates several concerns that are often hard-coded together in traditional WordPress templates.

```text
Database
├── Route identity
├── Hierarchy
├── SEO metadata
├── Answer-engine content
├── Keywords
├── FAQs
├── Content sections
├── Internal links
├── Service context
└── Promotion context

WordPress
├── Resolve current request
├── Retrieve page bundle
├── Apply safe fallbacks
├── Render HTML
├── Render metadata
└── Render JSON-LD
```

---

## Public-Safe Scope

The examples intentionally remove or generalize:

- Company names
- Production domains
- Supabase project URLs
- Supabase keys
- Real market records
- Real promotions and pricing
- Internal page identifiers
- Private lead-routing logic
- Production WordPress styling
- Internal service rules
- Production caching configuration
- Private operational integrations

All sample records are synthetic.

The complete production implementation remains private.

---

## Why These Examples Are Included

The project is not simply a WordPress page template.

The implementation includes:

- relational content modeling
- hierarchical website modeling
- database-side content generation
- editorial locking
- technology-aware local content
- JSON document aggregation
- WordPress/Supabase integration
- dynamic fallback behavior
- SEO metadata
- AEO-oriented answer content
- FAQ schema controls
- reusable templates
- structured data generation

These examples show selected implementation patterns behind those capabilities.

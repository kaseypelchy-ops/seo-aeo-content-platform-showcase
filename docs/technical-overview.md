# SEO & AEO Content Platform — Technical Overview

## Introduction

The SEO & AEO Content Platform is a database-driven content system that connects WordPress to a structured PostgreSQL/Supabase content model.

The production architecture separates:

```text
Content Modeling
      ↓
PostgreSQL / Supabase

Content Generation
      ↓
PL/pgSQL

Application Delivery
      ↓
Supabase REST

Presentation
      ↓
WordPress / PHP
```

The platform is designed to manage localized SEO and answer-engine content without hard-coding every market-specific title, FAQ, content section, internal link, or metadata value directly into separate WordPress templates.

This document focuses on the implementation patterns represented in the public showcase. Production credentials, private market records, real pricing, private endpoints, company-specific content, and proprietary integrations are intentionally excluded.

---

# 1. Technology Stack

| Area | Technology | Responsibility |
|---|---|---|
| CMS / Presentation | WordPress | Routing, theme integration, template rendering |
| Integration | PHP | Route resolution, API retrieval, response validation, fallbacks |
| Data Store | PostgreSQL / Supabase | Structured SEO/AEO content |
| Database Logic | SQL / PL/pgSQL | Relational modeling, local content generation |
| Document Assembly | PostgreSQL JSONB | Aggregating relational content into page bundles |
| API | Supabase REST | Page-oriented content delivery |
| Structured Data | Schema.org / JSON-LD | WebPage, Service, FAQ structured output |
| WordPress Data | ACF / post meta | Existing dynamic fallback fields |
| Front End | PHP / HTML / CSS | Server-rendered presentation |
| Content Strategy | SEO / AEO | Search metadata, answer content, FAQs, internal linking |

---

# 2. Core Request Flow

The live rendering path is read-oriented.

```text
Visitor requests URL
        ↓
WordPress resolves request
        ↓
SEO client reads current path
        ↓
Path is normalized
        ↓
Supabase REST request
        ↓
PostgreSQL page-content view
        ↓
Page-level JSON bundle
        ↓
PHP validates response
        ↓
Reusable template receives page data
        ↓
HTML + metadata + JSON-LD rendered
```

The URL path is the primary application lookup key.

---

# 3. Page Identity

Each structured page has its own identity.

Important fields include:

```text
id
page_name
page_type
slug
path
```

The `path` field provides the stable route used by WordPress to retrieve the page bundle.

Conceptually:

```text
WordPress URL
      ↓
Normalized Path
      ↓
seo_pages.path
      ↓
Matching Page
```

A unique path constraint prevents multiple page records from unintentionally claiming the same route.

---

# 4. Hierarchical Page Model

Pages are organized into a hierarchy.

```text
Homepage
   ↓
National Page
   ↓
State Page
   ↓
Local Market Page
```

The model includes:

```text
parent_page_id
hierarchy_level
geographic_scope
state_code
locality_name
locality_type
technology
```

This allows the system to understand the role of a page without relying only on string parsing of the URL.

---

# 5. Relational Content Model

The page table contains page-level metadata and context, while repeatable content types live in related tables.

```text
seo_pages
    │
    ├── seo_page_keywords
    ├── seo_page_faqs
    ├── seo_content_sections
    └── seo_internal_links
```

Pages can also reference:

```text
seo_markets
seo_services
seo_promotions
```

Redirect behavior is modeled separately through:

```text
seo_redirects
```

This keeps each content type independently manageable.

---

# 6. SEO Fields

Page-level SEO fields can include:

```text
seo_title
meta_description
primary_keyword
canonical_url
robots_index
robots_follow
h1
```

These values are stored as structured fields rather than being embedded directly inside template PHP.

That allows them to be:

- Queried
- Audited
- Generated
- Updated in bulk
- Locked from regeneration
- Delivered consistently to templates

---

# 7. AEO Fields

The page model also includes fields intended for concise answer-oriented content.

Examples include:

```text
answer_engine_summary
key_answer
```

Related FAQ records provide additional structured answers.

This separates direct-answer content from the larger body copy.

---

# 8. Open Graph Fields

Page records can also contain:

```text
og_title
og_description
og_image_url
```

The template can prefer database values and fall back to standard SEO fields if a dedicated Open Graph value is unavailable.

---

# 9. Publishing State

A page includes explicit publication controls.

```text
status
published_at
active
```

The WordPress client requests only records that are appropriate for live rendering.

Conceptually:

```text
path = current path
active = true
status = published
```

This lets records exist in the database without automatically becoming public.

---

# 10. Editorial SEO Locking

The `seo_locked` flag protects manually curated SEO fields.

```text
Page
 ↓
seo_locked?
 /       \
Yes       No
 ↓         ↓
Keep      Generate
Manual    Defaults
SEO
```

This avoids the common problem where a bulk generation process overwrites a deliberately optimized page.

---

# 11. Content Generation Entry Point

Local content generation is orchestrated through a bundle-style PL/pgSQL function.

Conceptually:

```text
Generate Local Content Bundle
             ↓
 ┌───────────┼────────────┬─────────────┐
 ▼           ▼            ▼             ▼
SEO       Keywords       FAQs        Sections
Defaults
```

Each function owns one responsibility.

This keeps generation logic easier to maintain and test than one large procedure.

---

# 12. Local-Page Validation

Before local generation runs, the page is validated.

Conceptually:

```text
Page Exists?
    ↓
Correct Hierarchy Level?
    ↓
geographic_scope = local?
    ↓
Eligible
```

Invalid page types are rejected instead of silently receiving local-market content.

---

# 13. Technology-Aware SEO Defaults

SEO defaults can change according to technology.

```text
technology
   ↓
┌──────────┬──────────┬──────────────┐
Fiber      Coax       Fiber + Coax
```

Technology can influence:

- SEO title
- Meta description
- Primary keyword
- H1
- Answer-engine summary
- Key answer
- Open Graph title
- Open Graph description

This allows one generation system to support different local service conditions.

---

# 14. Structured Keyword Generation

Keywords are stored as related rows rather than one comma-separated field.

A keyword can contain:

```text
keyword
keyword_type
search_intent
priority
active
```

Example roles can include:

```text
primary
secondary
long_tail
question
```

Priority determines ordering in the page bundle.

---

# 15. Deterministic Keyword Regeneration

The public generation example uses a deterministic pattern:

```text
Delete Existing Generated Keywords
        ↓
Insert Current Keyword Set
        ↓
Add Technology-Specific Keywords
```

This makes repeated generation predictable.

The same page context produces the same structured keyword pattern.

---

# 16. Structured FAQ Model

FAQ records contain more than a question and answer.

They can include:

```text
question
short_answer
detailed_answer
answer_type
display_order
include_on_page
include_in_schema
authoritative
source_reference
verified_at
active
```

These fields support both presentation and answer-engine governance.

---

# 17. FAQ Display vs. Schema Eligibility

Visible page rendering and structured-data rendering use separate flags.

```text
include_on_page
      ↓
Visible FAQ

include_in_schema
      ↓
FAQPage JSON-LD
```

The PHP template and the structured-data builder therefore do not use exactly the same filter.

That is intentional.

---

# 18. FAQ Answer Selection

The visible renderer and schema builder can prefer:

```text
detailed_answer
```

and fall back to:

```text
short_answer
```

when necessary.

Conceptually:

```php
$answer = !empty($faq['detailed_answer'])
    ? $faq['detailed_answer']
    : $faq['short_answer'];
```

This gives the data model flexibility without forcing every record to contain the same answer length.

---

# 19. Content Sections

Body content is stored as ordered sections.

A section can include:

```text
section_key
section_type
heading
subheading
content
display_order
active
```

This lets the template loop through structured content.

```text
content_sections[]
        ↓
Sort / Return in display order
        ↓
Reusable Template
```

The page body is therefore data-driven without being one unstructured HTML blob.

---

# 20. Internal Links

Internal links are structured records.

A record can contain:

```text
source_page_id
destination_page_id
destination_url
anchor_text
relationship
priority
active
```

This makes parent, child, and other relationships queryable.

Conceptually:

```text
State Page
   ↓ child
Local Page

Local Page
   ↑ parent
State Page
```

Internal linking is part of the data model rather than only part of manually written page copy.

---

# 21. Page Content View

The application does not retrieve every related table separately.

A PostgreSQL view assembles the page into one document.

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
Page Content View
        ↓
One JSON Bundle
```

The public implementation demonstrates this pattern directly.

---

# 22. PostgreSQL JSONB Aggregation

The view uses PostgreSQL JSONB functions such as:

```sql
jsonb_build_object(...)
jsonb_agg(...)
coalesce(..., '[]'::jsonb)
```

This lets relational child rows become ordered arrays.

Conceptually:

```text
Multiple FAQ Rows
        ↓
jsonb_agg()
        ↓
faqs[]
```

The same approach is used for:

```text
keywords[]
faqs[]
content_sections[]
internal_links[]
```

---

# 23. Optional Relationships

Market, service, and promotion relationships are optional.

The public page-content view returns a real JSON `null` when a relationship does not exist rather than constructing an object where every property is null.

Conceptually:

```text
service_id exists?
 /            \
Yes            No
 ↓              ↓
Service Object  null
```

This produces a cleaner application contract.

---

# 24. Application-Oriented Document Shape

The view creates a page document that is easier for WordPress to consume.

Conceptually:

```json
{
  "path": "/national/example-state/example-town/",
  "seo_title": "...",
  "h1": "...",
  "technology": "fiber_coax",
  "keywords": [],
  "faqs": [],
  "content_sections": [],
  "internal_links": []
}
```

The database remains relational for management, while the application receives a page-oriented structure.

---

# 25. Supabase REST Retrieval

The WordPress client queries the page-content view through Supabase REST.

Conceptually:

```text
/rest/v1/seo_page_content_public
    ?path=eq.{normalized_path}
    &active=eq.true
    &status=eq.published
    &limit=1
```

Production connection values are configured outside public source.

---

# 26. WordPress Request Path Resolution

The PHP integration retrieves the current request path from the server request.

The public client uses:

```text
REQUEST_URI
   ↓
wp_unslash()
   ↓
wp_parse_url(..., PHP_URL_PATH)
   ↓
Normalized Path
```

Query strings are excluded from the lookup key.

---

# 27. Path Normalization

The public client normalizes:

- Leading slash
- Trailing slash
- Root path

Conceptually:

```text
national/example/
        ↓
/national/example/
```

and:

```text
/
```

remains:

```text
/
```

The goal is to match the path convention used by PostgreSQL.

---

# 28. Configuration

Production credentials are not stored in the public code.

The client expects configuration similar to:

```php
PORTFOLIO_SEO_SUPABASE_URL
PORTFOLIO_SEO_SUPABASE_KEY
```

The real values belong in protected application configuration.

---

# 29. HTTP Request

The integration uses the WordPress HTTP API rather than requiring direct cURL handling.

Conceptually:

```php
$response = wp_remote_get(
    $url,
    [
        'timeout' => 4,
        'headers' => [
            'apikey' => $key,
            'Authorization' => 'Bearer ' . $key,
            'Accept' => 'application/json',
        ],
    ]
);
```

This keeps HTTP behavior consistent with WordPress conventions.

---

# 30. API Failure Handling

The client validates several failure conditions.

```text
Request
  ↓
WP Error?
  ↓
HTTP 2xx?
  ↓
Valid JSON?
  ↓
At Least One Record?
  ↓
Record Structure Valid?
  ↓
Published + Active?
```

Any failed step returns `null` to the template layer.

The public example intentionally favors safe failure over throwing a front-end exception.

---

# 31. Page Accessor

The client exposes a simple application-facing function.

Conceptually:

```php
portfolio_seo_current_page()
```

Internally:

```text
Resolve Current Path
        ↓
Fetch Page by Path
        ↓
Return Array or null
```

This keeps the template from needing to understand Supabase request syntax.

---

# 32. WordPress Fallback Data

The reusable template can still read existing WordPress data.

The public example supports:

```text
ACF
  or
Native post meta
```

for fields such as:

```text
town_name
state_code
state_name
technology
```

The integration therefore augments an existing dynamic WordPress setup instead of requiring the entire page system to move into Supabase.

---

# 33. Supabase Override Pattern

The template builds safe defaults first.

Conceptually:

```php
$fallback_title = ...;
$fallback_h1 = ...;
$fallback_meta = ...;
```

Then it prefers structured database values when available.

```text
Supabase Value Present?
 /                 \
Yes                 No
 ↓                   ↓
Use Supabase       Use WordPress
Value              Fallback
```

This is used for:

- SEO title
- H1
- Meta description
- Canonical URL
- Technology
- Open Graph values
- Robots directives

---

# 34. Visible FAQ Rendering

The template filters the page bundle using:

```text
include_on_page
```

Then it sanitizes:

```text
question
detailed_answer / short_answer
```

before rendering.

This prevents schema-only records from automatically appearing in the visible FAQ section.

---

# 35. Dynamic Section Rendering

The template loops through `content_sections`.

Each section is validated before rendering.

Fields are handled according to context:

```text
heading     → plain text
subheading  → plain text
content     → controlled HTML
```

The public example uses WordPress escaping and sanitization helpers to preserve that boundary.

---

# 36. Robots Directives

The page bundle includes separate booleans:

```text
robots_index
robots_follow
```

The template converts them into a meta value such as:

```text
index, follow
```

or:

```text
noindex, follow
```

This gives the content model direct control over page-level crawler directives.

---

# 37. Canonical URL

The canonical URL can come from the page bundle.

If no structured value exists, the template can fall back to the WordPress permalink.

```text
Database canonical_url
        ↓
Preferred

WordPress permalink
        ↓
Fallback
```

---

# 38. Structured Data Builder

Structured data is handled in a separate PHP helper rather than being mixed into the API client.

The public builder can generate:

```text
WebPage
Service
FAQPage
Question
Answer
```

This keeps:

```text
Data Retrieval
      ≠
Schema Rendering
```

which makes both pieces easier to reason about.

---

# 39. WebPage Schema

The builder can use:

```text
canonical_url
seo_title
h1
meta_description
```

to create the `WebPage` entity.

Conceptually:

```json
{
  "@type": "WebPage",
  "@id": "...#webpage",
  "url": "...",
  "name": "...",
  "headline": "...",
  "description": "..."
}
```

---

# 40. Service Schema

For local pages, the builder can also emit a `Service` entity.

It uses:

```text
locality_name
state_code
technology
```

or fallback WordPress values if needed.

This lets the structured page model drive local service-area context.

---

# 41. FAQPage Schema

FAQ schema is assembled only from records eligible for structured output.

```text
faqs[]
   ↓
include_in_schema = true
   ↓
Question + Answer entities
   ↓
FAQPage
```

The visible HTML FAQ list and FAQPage schema can therefore differ intentionally.

---

# 42. Server-Rendered Output

The template writes:

- `<title>`
- Meta description
- Robots directive
- Canonical link
- Open Graph fields
- JSON-LD
- H1
- Dynamic sections
- FAQs

during the WordPress server render.

The core SEO/AEO content is therefore available without requiring a client-side JavaScript fetch after page load.

---

# 43. Data and Code Deployment Separation

Content changes and code changes follow different paths.

```text
Content Change
    ↓
PostgreSQL / Supabase
    ↓
Updated Page Bundle
```

does not necessarily require:

```text
PHP Deployment
```

Likewise:

```text
Template Improvement
    ↓
Code Deployment
```

does not require recreating every content record.

This separation is one of the major operational benefits of the platform.

---

# 44. Content Generation and Publication Are Separate

Generation does not automatically mean publication.

Conceptually:

```text
Generate / Update Content
        ↓
Database Record
        ↓
Review / Curate / Lock
        ↓
Published + Active
        ↓
Available to WordPress
```

This keeps automation separate from live rendering.

---

# 45. Security Model

The public-facing integration needs read access to public page content, not administrative database control.

The production architecture should keep:

- Database passwords
- Supabase service-role credentials
- WordPress credentials
- Private environment variables
- Administrative APIs

outside public source.

The read path should follow least-privilege access.

---

# 46. Input Handling

The route originates from an HTTP request.

The integration therefore:

```text
Reads Request URI
      ↓
Extracts URL Path
      ↓
Normalizes
      ↓
Encodes API Query
```

It does not directly concatenate raw request input into SQL.

---

# 47. Output Handling

Database content is still handled according to output context.

Examples:

```text
Plain text       → esc_html()
HTML attribute   → esc_attr()
URL              → esc_url()
Controlled HTML  → wp_kses_post()
```

A managed database is a trusted content source, but that does not eliminate the need for correct rendering boundaries.

---

# 48. Performance Characteristics

The request path performs one page-bundle lookup instead of several separate content requests.

Without aggregation:

```text
Fetch Page
Fetch Keywords
Fetch FAQs
Fetch Sections
Fetch Links
```

With the page-content view:

```text
Fetch One Page Bundle
```

This reduces application-side request coordination and simplifies template logic.

---

# 49. Database Query Characteristics

The primary live lookup is selective.

Conceptually:

```text
path = requested path
active = true
status = published
limit = 1
```

The unique route model keeps this lookup deterministic.

---

# 50. Scaling Local Pages

Adding another local market primarily requires structured data rather than a new template.

```text
New Page Record
      ↓
Generate / Curate Content
      ↓
Existing View
      ↓
Existing API Client
      ↓
Existing Template
```

This is the main scalability advantage of the system.

---

# 51. Database Auditing

Structured fields make content auditing possible with SQL.

Examples include finding:

```text
Pages without SEO titles
Pages without H1s
Inactive pages
Unpublished pages
Pages without keywords
Pages without FAQs
Pages with stale verification dates
Pages with missing technology
```

This is significantly easier than opening individual WordPress pages manually.

---

# 52. Bulk Content Operations

The relational model also enables controlled bulk operations.

Examples include:

- Regenerating local SEO defaults
- Refreshing technology-specific FAQs
- Rebuilding keyword records
- Updating repeated terminology
- Auditing hierarchy relationships
- Rebuilding internal links
- Updating structured content sections

These operations can target data instead of template files.

---

# 53. Testing Strategy

The public architecture can be tested at several levels.

## Database Generation

- Missing page
- Wrong hierarchy
- Wrong geographic scope
- SEO-locked page
- Fiber page
- Coax page
- Mixed-technology page

## View Aggregation

- Page with no market
- Page with no service
- Page with no promotion
- Empty keyword array
- Empty FAQ array
- Empty content-section array
- Empty internal-link array
- Correct ordering

## API Client

- Root route
- Local route
- Missing configuration
- Transport error
- HTTP error
- Empty response
- Invalid JSON
- Unpublished page
- Inactive page

## Rendering

- Supabase override
- WordPress fallback
- Robots output
- Canonical output
- Open Graph output
- Content sections
- Visible FAQs
- FAQ schema filtering
- HTML escaping

---

# 54. Troubleshooting Flow

When expected content is not appearing:

```text
Check WordPress Request Path
        ↓
Check Normalized Path
        ↓
Check Supabase Configuration
        ↓
Check Published Page Exists
        ↓
Check Page-Content View Output
        ↓
Check API Response
        ↓
Check Template Function
        ↓
Check Rendering / Fallback
```

This isolates whether the problem is:

- Routing
- Data
- API integration
- Publication state
- Template consumption
- Rendering

---

# 55. Public Implementation Files

The repository includes public-safe examples for the major implementation layers.

```text
examples/
├── supabase-page-content-view.sql
├── local-content-generator.sql
├── seo-content-client.php
├── structured-data-builder.php
├── simplified-local-town-template.php
├── sample-page-record.json
├── sample-api-response.json
└── sample-rendered-page.md
```

The implementation flow is:

```text
Structured Page Record
        ↓
PL/pgSQL Generation
        ↓
Relational Child Records
        ↓
PostgreSQL Page Content View
        ↓
Supabase REST
        ↓
WordPress PHP Client
        ↓
Reusable Template
        ↓
Structured Data Builder
        ↓
Rendered Page
```

---

# 56. Key Technical Challenges Solved

## Localized Content Without Template Duplication

**Problem:** Local pages need unique metadata, FAQs, content, and technology context.

**Approach:** Store local page context and search content as structured database records while keeping the WordPress template reusable.

---

## Relational Data vs. Application-Friendly Data

**Problem:** A normalized database is good for integrity, but WordPress should not make separate requests for every related table.

**Approach:** Use a PostgreSQL view with JSONB aggregation to deliver one page-oriented document.

---

## Automation Without Destroying Editorial Work

**Problem:** Generated defaults are useful, but manually optimized pages should not be overwritten.

**Approach:** Use the `seo_locked` flag to preserve curated SEO fields.

---

## Different Technologies, Same Platform

**Problem:** Fiber, coax, and mixed-technology markets need different content without separate template systems.

**Approach:** Use the structured `technology` field as input to deterministic generation logic.

---

## Structured FAQs for Both Humans and Machines

**Problem:** FAQ content can serve visible users and JSON-LD, but those use cases do not always need identical eligibility.

**Approach:** Store independent `include_on_page` and `include_in_schema` controls.

---

## External Content Source Without a Hard Failure

**Problem:** A Supabase/API problem should not break a public WordPress page.

**Approach:** Validate each response step and return to existing WordPress fallback values when necessary.

---

## Content Updates Without Code Deployments

**Problem:** Search content changes more often than application architecture.

**Approach:** Keep content in PostgreSQL and rendering logic in PHP so they can evolve independently.

---

# 57. Engineering Principles

## Keep Structured Content Separate From Presentation

WordPress renders; PostgreSQL owns structured SEO/AEO data.

## Model Relationships Explicitly

Hierarchy, FAQs, keywords, sections, and internal links are structured relationships rather than conventions hidden inside page copy.

## Generate Deterministically

Default content generation uses known page context rather than uncontrolled runtime generation.

## Preserve Editorial Control

Automation must be able to stop at manually curated content.

## Deliver Application-Friendly Data

The database can remain normalized while the consuming application receives one coherent document.

## Fail Gracefully

A remote content lookup should not become a full-page outage.

## Use Least Privilege

The public rendering path should only have the access it needs.

## Escape at Render Time

Database-managed content still requires context-aware output handling.

---

# Public Documentation Scope

This technical overview intentionally includes:

- WordPress request handling
- Route normalization
- Supabase REST retrieval
- PostgreSQL relational modeling
- Page hierarchy
- PL/pgSQL content generation
- `seo_locked` governance
- Technology-aware generation
- Structured keywords
- Structured FAQs
- Content sections
- Internal links
- PostgreSQL JSONB
- Page-content view aggregation
- API response validation
- WordPress fallbacks
- SEO metadata
- Open Graph metadata
- Robots directives
- JSON-LD generation
- Server-side rendering
- Security boundaries
- Testing
- Troubleshooting
- Scaling

It intentionally excludes:

- Production credentials
- Real Supabase endpoints
- Private database access
- Real market data
- Production pricing
- Company-specific content
- Internal operational workflows
- Production caching configuration
- Private integrations
- Proprietary business rules

---

# Summary

The SEO & AEO Content Platform turns localized search content into structured application data.

```text
Website Route
      ↓
Structured Page Record
      ↓
PL/pgSQL Generation
      ↓
Relational SEO / AEO Content
      ↓
PostgreSQL JSONB Aggregation
      ↓
Page Content View
      ↓
Supabase REST
      ↓
WordPress PHP Client
      ↓
Reusable Template
      ↓
HTML + SEO Metadata + JSON-LD
```

The result is a system where marketing content strategy, database design, content automation, APIs, and WordPress rendering work together without requiring every localized page to become a separate hard-coded implementation.

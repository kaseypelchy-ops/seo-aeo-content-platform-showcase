# SEO & AEO Content Platform — Public Code Examples

This folder contains simplified, sanitized examples based on implementation patterns from a private production SEO/AEO content platform.

The system models website pages and related search content in PostgreSQL, organizes pages into a geographic hierarchy, generates repeatable local SEO/AEO defaults, assembles normalized relational records into page-level JSON documents, and makes those documents available to reusable WordPress templates through Supabase.

The examples are intended to show the technical architecture without publishing production credentials, private market data, pricing rules, internal endpoints, company-specific content, or operational business logic.

These are representative portfolio examples rather than copies of the production codebase.

---

## Architecture Represented

```text
                         ┌─────────────┐
                         │   Markets   │
                         └──────┬──────┘
                                │
                         ┌──────▼──────┐
                         │  SEO Pages  │
                         └──────┬──────┘
                                │
         ┌──────────────┬───────┼──────────────┐
         │              │       │              │
         ▼              ▼       ▼              ▼
     Services      Promotions  Keywords        FAQs
                                │              │
                                ▼              ▼
                         Content Sections
                                │
                                ▼
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
                         /              \
                        ▼                ▼
                  HTML Content      SEO / JSON-LD
```

`Markets`, `Services`, and `Promotions` are optional relationships associated with page records.

Keywords, FAQs, content sections, and internal links are stored as related records and aggregated into the final page document.

The database remains the structured content source while WordPress remains responsible for presentation.

---

# Included Examples

## [`supabase-page-content-view.sql`](supabase-page-content-view.sql)

Shows how normalized relational SEO/AEO records can be assembled into one page-level document for application use.

Instead of requiring WordPress to make separate requests for page metadata, keywords, FAQs, content sections, links, market information, services, and promotions, PostgreSQL assembles those relationships before the data reaches the presentation layer.

### Demonstrates

- PostgreSQL views
- `LEFT JOIN`
- `jsonb_build_object`
- `jsonb_agg`
- ordered JSON aggregation
- active-record filtering
- empty-array fallbacks with `COALESCE`
- hierarchical page metadata
- geographic context
- technology context
- optional market relationships
- optional service relationships
- optional promotion relationships
- keyword aggregation
- FAQ aggregation
- content-section aggregation
- internal-link aggregation

Conceptually:

```text
Normalized Relational Data
        ↓
PostgreSQL View
        ↓
Page-Oriented JSON Document
        ↓
WordPress
```

This keeps the relational database normalized while giving the application a document shape that is easier to consume.

---

## [`local-content-generator.sql`](local-content-generator.sql)

Shows the database-side generation pattern used for local market pages.

The generator does more than concatenate a town name into a title. It validates that a page belongs to the correct level of the hierarchy, respects editorial locking, and generates different content according to structured page attributes such as network technology.

### Demonstrates

- PL/pgSQL
- page validation
- hierarchy validation
- geographic-scope validation
- `seo_locked` content governance
- technology-aware SEO defaults
- technology-aware keywords
- technology-aware FAQs
- reusable content sections
- deterministic delete-and-regenerate behavior
- content-generation timestamps
- orchestration through a content-bundle function

Conceptually:

```text
Local Page
    ↓
Valid Hierarchy?
    ↓
Local Geographic Scope?
    ↓
seo_locked?
 /         \
Yes         No
 ↓           ↓
Preserve    Generate
            ↓
     ┌──────┼───────┬─────────┐
     ▼      ▼       ▼         ▼
    SEO  Keywords  FAQs    Sections
```

The approach allows automatically generated defaults and manually curated SEO content to coexist.

---

## [`seo-content-client.php`](seo-content-client.php)

Shows the WordPress integration layer that resolves the current request path and retrieves the matching published page bundle from Supabase.

### Demonstrates

- WordPress request-path normalization
- route-based content lookup
- REST API construction
- Supabase request headers
- active-record filtering
- published-status filtering
- response-code validation
- JSON response validation
- safe failure behavior
- configuration outside source control
- separation between data retrieval and template rendering

Conceptually:

```text
WordPress Request
        ↓
Current URL Path
        ↓
Normalize Path
        ↓
Supabase REST Query
        ↓
Published + Active Record
        ↓
Page Bundle
```

If no valid page bundle is returned, the integration can safely return control to the WordPress fallback layer rather than breaking page rendering.

---

## [`structured-data-builder.php`](structured-data-builder.php)

Shows how the same page bundle used for visible content can also be transformed into structured JSON-LD.

### Demonstrates

- Schema.org
- JSON-LD
- `WebPage`
- `Service`
- `FAQPage`
- `Question`
- `Answer`
- canonical identifiers
- geographic service-area context
- technology-aware service information
- independent `include_in_schema` filtering
- WordPress sanitization

One important distinction is that visible FAQ eligibility and schema eligibility are intentionally separate.

```text
FAQ Record
   │
   ├── include_on_page
   │        ↓
   │    Visible HTML
   │
   └── include_in_schema
            ↓
         JSON-LD
```

A FAQ can therefore be managed differently for human presentation and structured-data output.

---

## [`simplified-local-town-template.php`](simplified-local-town-template.php)

Shows how one reusable WordPress template can render different local pages using structured Supabase data while retaining safe WordPress fallback values.

### Demonstrates

- ACF with native WordPress custom-field fallback
- route-based Supabase content lookup
- dynamic SEO fallbacks
- Supabase SEO overrides
- dynamic H1 content
- canonical URL handling
- robots directives
- Open Graph metadata
- technology-aware page data
- dynamic content sections
- visible FAQ filtering
- JSON-LD integration
- WordPress sanitization and escaping

The template does not need to know how all of the underlying relational tables are joined.

It receives a page-oriented object and focuses on rendering.

```text
Structured Page Bundle
        +
WordPress Fallback Fields
        ↓
Reusable Template
        ↓
Rendered Local Page
```

---

## [`sample-page-record.json`](sample-page-record.json)

Shows a synthetic page record before related content is aggregated.

The example demonstrates fields such as:

```text
Path
Page Type
Hierarchy Level
Geographic Scope
Locality
Technology
SEO Metadata
AEO Content
Publishing State
SEO Lock State
```

All values are fictional.

---

## [`sample-api-response.json`](sample-api-response.json)

Shows the page-oriented document after the relational content has been assembled.

The example includes nested or aggregated values for:

```text
Market
Service
Promotion
Keywords[]
FAQs[]
Content Sections[]
Internal Links[]
```

This is closer to the shape consumed by the WordPress integration layer.

---

## [`sample-rendered-page.md`](sample-rendered-page.md)

Shows how the same structured record could appear after it passes through the reusable WordPress rendering layer.

Together, the three examples represent:

```text
Relational Page Record
        ↓
Page Content View
        ↓
API Response
        ↓
WordPress Template
        ↓
Rendered Page
```

---

# Content Hierarchy

The underlying page model supports a hierarchical website structure.

```text
Homepage
   ↓
National / Service-Area Landing Page
   ↓
State Page
   ↓
Local Market Page
```

A page can reference another page through:

```text
parent_page_id
```

while fields such as:

```text
hierarchy_level
geographic_scope
state_code
locality_name
locality_type
technology
```

describe the role of the page.

That allows the content system to understand a page as more than a URL string.

Conceptually:

```text
SEO Page
   │
   ├── Parent Page
   │
   └── Child Pages
```

The hierarchy can then be used for content generation, internal linking, navigation, and local-page behavior.

---

# Generated vs. Curated Content

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
- Meta descriptions
- Primary keywords
- H1 content
- Answer-engine summaries
- Key answers
- Open Graph metadata
- Keyword records
- FAQ records
- Page content sections

The important principle is:

```text
Automation
     +
Editorial Control
     ↓
Managed Content System
```

Automatic generation does not have to mean automatic overwriting.

---

# Technology-Aware Generation

The local content generator can respond to structured technology information.

```text
Local Page
    ↓
Technology
 /      |       \
Fiber  Coax   Fiber + Coax
  ↓      ↓         ↓
Different SEO / AEO Content
```

Technology can influence:

- Titles
- H1 content
- Primary keywords
- Secondary keywords
- FAQs
- Page sections
- Answer-engine summaries
- Service descriptions

This lets one content model support multiple types of local service pages without requiring separate WordPress templates for every technology combination.

---

# Relational Data to Page Document

WordPress does not need to understand the complete relational database.

The view converts normalized data into a page-oriented document.

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
Page Metadata
SEO Metadata
AEO Content
Hierarchy
Geographic Context
Technology
Market
Service
Promotion
Keywords[]
FAQs[]
Content Sections[]
Internal Links[]
```

The architecture therefore maintains two useful representations of the same information:

```text
Normalized Relational Model
           ↓
     Data Management

Page-Oriented Document
           ↓
   Application Delivery
```

---

# Database Responsibilities

The database manages structured information such as:

```text
Route identity
Page hierarchy
SEO metadata
Answer-engine content
Keywords
FAQs
Content sections
Internal links
Market context
Service context
Promotion context
Publishing state
Editorial locking
Redirect records
```

This keeps content relationships and generation rules centralized.

---

# WordPress Responsibilities

WordPress remains responsible for presentation and request handling.

```text
Resolve Current Request
        ↓
Retrieve Page Bundle
        ↓
Apply Safe Fallbacks
        ↓
Render HTML
        ↓
Render SEO Metadata
        ↓
Render Open Graph Data
        ↓
Render JSON-LD
```

That creates a deliberate separation between:

```text
Content Architecture
        and
Presentation Architecture
```

---

# Redirect Model

The broader content platform also includes structured redirect records.

Redirects are kept separate from the primary page-content bundle because they serve a different request-management purpose.

Conceptually:

```text
Old Path
    ↓
seo_redirects
    ↓
Redirect Type
    ↓
Destination Path
```

This allows URL migration and route changes to be modeled independently from page content.

The production redirect handling is not included as a full public implementation example.

---

# SEO & AEO Responsibilities

The platform supports both traditional search metadata and structured answer-oriented content.

### SEO-oriented fields include

```text
SEO Title
Meta Description
Primary Keyword
Canonical URL
Robots Directives
H1
Open Graph Metadata
Internal Links
```

### AEO-oriented fields include

```text
Answer Engine Summary
Key Answer
Structured FAQs
Short Answers
Detailed Answers
Authoritative Flags
Source References
Verification Dates
JSON-LD Eligibility
```

Both types of content use the same structured page model.

---

# Safe Failure Behavior

The Supabase integration is not intended to make WordPress entirely dependent on a successful content request.

```text
Request Page Bundle
        ↓
Record Found?
   /           \
 Yes            No
  ↓              ↓
Use Data     Use WordPress
              Fallback
```

This gives the reusable template a fallback path if:

- Supabase is unavailable
- A route has not been created yet
- A record is inactive
- A record is not published
- The API response is invalid

The page presentation layer can therefore remain functional while the structured content layer is unavailable.

---

# Public-Safe Scope

The examples intentionally remove, rename, or generalize production-specific information, including:

- Company names
- Production domains
- Supabase project URLs
- Supabase keys
- Production credentials
- Real market records
- Real promotions
- Production pricing
- Internal page identifiers
- Private lead-routing logic
- Production WordPress styling
- Internal service rules
- Production caching configuration
- Private operational integrations
- Company-specific classification rules
- Internal workflow configuration

All public sample records are synthetic.

The complete production implementation and production datasets remain private.

---

# Why These Examples Are Included

The project is not simply a WordPress page template or a database holding SEO titles.

The implementation includes:

- Relational content modeling
- Hierarchical website modeling
- PostgreSQL
- PL/pgSQL
- Database-side content generation
- Editorial locking
- Technology-aware local content
- Structured keyword management
- Structured FAQ management
- JSON document aggregation
- PostgreSQL JSONB
- WordPress/Supabase integration
- Route-based content resolution
- Dynamic fallback behavior
- SEO metadata
- AEO-oriented answer content
- FAQ schema controls
- Reusable templates
- Structured-data generation
- Internal-link relationships
- Redirect modeling

Together, these examples show how structured data, database automation, APIs, and reusable WordPress components can support a large localized SEO/AEO website without requiring every market page to be maintained as an independent hard-coded implementation.

---

# Public Example Flow

The repository examples can be followed as one end-to-end workflow:

```text
Local Page Record
        ↓
Database Generation
        ↓
Related SEO / AEO Records
        ↓
Page Content View
        ↓
Supabase REST Response
        ↓
WordPress Route Client
        ↓
Reusable Local Template
        ↓
Structured Data Builder
        ↓
HTML + Metadata + JSON-LD
```

The full production application includes additional business rules, integrations, validation, content, and operational behavior that are intentionally excluded from this public repository.

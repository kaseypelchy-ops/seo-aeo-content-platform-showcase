# SEO / AEO Template Examples

These files are sanitized portfolio examples based on the reusable WordPress local-town template used by the production SEO/AEO platform.

The production template is substantially larger and includes additional service logic, page sections, lead handling, internal integrations, market exceptions, and styling. Those details are intentionally excluded from the public repository.

## Files

### `simplified-local-town-template.php`

A reduced PHP example showing the main architecture:

```text
WordPress Market Fields
        +
Current Route
        ↓
SEO / AEO Plugin
        ↓
Supabase Page Record
        ↓
Supabase value available?
      /                     \
    Yes                      No
     ↓                        ↓
Use Published Value     Use Dynamic Fallback
      \                     /
       ↓                   ↓
     Reusable WordPress Template
              ↓
   SEO Metadata + H1 + FAQs + JSON-LD
```

The example demonstrates:

- ACF with native WordPress custom-field fallback
- Supabase SEO/AEO integration
- Dynamic `{town}` and `{state}` content
- Published database values overriding calculated fallbacks
- Structured FAQ ingestion
- FAQ fallback generation
- Server-rendered SEO metadata
- JSON-LD generation
- Safe WordPress escaping

### `sample-page-record.json`

A synthetic Supabase record using placeholder values instead of a real market.

The placeholders include:

```text
{town}
{state}
{state_code}
{technology}
```

For example:

```text
High-Speed Internet in {town}, {state}
```

rather than publishing a real production locality.

### `sample-api-response.json`

Shows the same synthetic record in the array-shaped response a REST lookup can return.

## Public-Safe Scope

These examples intentionally exclude:

- Production Supabase credentials
- Private API endpoints
- Real local-market records
- Production phone/email routing
- Pricing and offer logic
- Market-specific exceptions
- Internal lead handling
- Full production CSS / JavaScript
- Private business rules

The goal is to demonstrate the architecture and implementation style without publishing the production application.

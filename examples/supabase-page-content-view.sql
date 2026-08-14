-- Public-safe example of the page bundle exposed to WordPress.
--
-- This view turns normalized relational SEO/AEO data into one page-oriented
-- document. Production names and fields have been generalized where needed.

create or replace view public.seo_page_content_public as
select
    p.id,
    p.page_name,
    p.page_type,
    p.slug,
    p.path,

    p.seo_title,
    p.meta_description,
    p.primary_keyword,
    p.canonical_url,
    p.robots_index,
    p.robots_follow,

    p.h1,
    p.eyebrow,
    p.intro_content,
    p.answer_engine_summary,
    p.key_answer,

    p.og_title,
    p.og_description,
    p.og_image_url,

    p.status,
    p.published_at,
    p.active,

    -- Hierarchy and local-page context are exposed directly so the
    -- presentation layer does not need to infer them from the URL.
    p.parent_page_id,
    p.hierarchy_level,
    p.geographic_scope,
    p.state_code,
    p.locality_name,
    p.locality_type,
    p.technology,
    p.seo_locked,
    p.content_generated_at,

    case
        when m.id is null then null
        else jsonb_build_object(
            'id', m.id,
            'market_name', m.market_name,
            'city', m.city,
            'state', m.state,
            'state_code', m.state_code,
            'county', m.county,
            'slug', m.slug
        )
    end as market,

    case
        when s.id is null then null
        else jsonb_build_object(
            'id', s.id,
            'service_name', s.service_name,
            'slug', s.slug,
            'service_type', s.service_type,
            'short_description', s.short_description
        )
    end as service,

    case
        when pr.id is null then null
        else jsonb_build_object(
            'id', pr.id,
            'promotion_name', pr.promotion_name,
            'slug', pr.slug,
            'headline', pr.headline,
            'subheadline', pr.subheadline,
            'price_display', pr.price_display,
            'offer_summary', pr.offer_summary,
            'offer_details', pr.offer_details,
            'start_date', pr.start_date,
            'end_date', pr.end_date,
            'status', pr.status
        )
    end as promotion,

    coalesce(
        (
            select jsonb_agg(
                jsonb_build_object(
                    'keyword', k.keyword,
                    'keyword_type', k.keyword_type,
                    'search_intent', k.search_intent,
                    'priority', k.priority
                )
                order by k.priority desc
            )
            from public.seo_page_keywords k
            where k.page_id = p.id
              and k.active = true
        ),
        '[]'::jsonb
    ) as keywords,

    coalesce(
        (
            select jsonb_agg(
                jsonb_build_object(
                    'id', f.id,
                    'question', f.question,
                    'short_answer', f.short_answer,
                    'detailed_answer', f.detailed_answer,
                    'answer_type', f.answer_type,
                    'include_on_page', f.include_on_page,
                    'include_in_schema', f.include_in_schema,
                    'authoritative', f.authoritative,
                    'source_reference', f.source_reference,
                    'verified_at', f.verified_at
                )
                order by f.display_order, f.created_at
            )
            from public.seo_page_faqs f
            where f.page_id = p.id
              and f.active = true
        ),
        '[]'::jsonb
    ) as faqs,

    coalesce(
        (
            select jsonb_agg(
                jsonb_build_object(
                    'id', c.id,
                    'section_key', c.section_key,
                    'section_type', c.section_type,
                    'heading', c.heading,
                    'subheading', c.subheading,
                    'content', c.content,
                    'display_order', c.display_order
                )
                order by c.display_order, c.created_at
            )
            from public.seo_content_sections c
            where c.page_id = p.id
              and c.active = true
        ),
        '[]'::jsonb
    ) as content_sections,

    coalesce(
        (
            select jsonb_agg(
                jsonb_build_object(
                    'anchor_text', l.anchor_text,
                    'destination_page_id', l.destination_page_id,
                    'destination_url', l.destination_url,
                    'relationship', l.relationship,
                    'priority', l.priority
                )
                order by l.priority desc
            )
            from public.seo_internal_links l
            where l.source_page_id = p.id
              and l.active = true
        ),
        '[]'::jsonb
    ) as internal_links

from public.seo_pages p
left join public.seo_markets m
    on m.id = p.market_id
left join public.seo_services s
    on s.id = p.service_id
left join public.seo_promotions pr
    on pr.id = p.promotion_id;

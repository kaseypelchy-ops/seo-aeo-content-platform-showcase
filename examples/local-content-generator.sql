-- Public-safe local SEO/AEO generation example.
--
-- The production system uses database functions to create repeatable local
-- defaults while allowing manually curated pages to be protected by
-- seo_locked.

create or replace function public.portfolio_generate_local_seo_defaults(
    target_page_id uuid
)
returns void
language plpgsql
as $$
declare
    p public.seo_pages%rowtype;
begin
    select *
    into p
    from public.seo_pages
    where id = target_page_id;

    if not found then
        raise exception 'SEO page not found';
    end if;

    if p.hierarchy_level <> 3
       or p.geographic_scope <> 'local' then
        raise exception 'Page is not a local market page';
    end if;

    -- Editorially curated SEO is not overwritten by automatic defaults.
    if p.seo_locked = true then
        return;
    end if;

    update public.seo_pages
    set
        seo_title =
            case p.technology
                when 'fiber' then
                    'Fiber Internet in ' ||
                    p.locality_name || ', ' ||
                    p.state_code || ' | Provider'

                when 'coax' then
                    'Cable Internet in ' ||
                    p.locality_name || ', ' ||
                    p.state_code || ' | Provider'

                else
                    'Internet Service in ' ||
                    p.locality_name || ', ' ||
                    p.state_code || ' | Provider'
            end,

        meta_description =
            case p.technology
                when 'fiber' then
                    'Explore fiber internet service in ' ||
                    p.locality_name || ', ' ||
                    p.state_code ||
                    '. Review local options and address-level availability.'

                when 'coax' then
                    'Explore cable internet service in ' ||
                    p.locality_name || ', ' ||
                    p.state_code ||
                    '. Review local options and address-level availability.'

                else
                    'Explore internet service in ' ||
                    p.locality_name || ', ' ||
                    p.state_code ||
                    '. Available technology and service options vary by address.'
            end,

        primary_keyword =
            case p.technology
                when 'fiber' then
                    'fiber internet ' ||
                    p.locality_name || ' ' ||
                    p.state_code

                else
                    'internet service ' ||
                    p.locality_name || ' ' ||
                    p.state_code
            end,

        h1 =
            case p.technology
                when 'fiber' then
                    'Fiber Internet in ' ||
                    p.locality_name || ', ' ||
                    p.state_code

                when 'coax' then
                    'Cable Internet in ' ||
                    p.locality_name || ', ' ||
                    p.state_code

                else
                    'High-Speed Internet in ' ||
                    p.locality_name || ', ' ||
                    p.state_code
            end,

        answer_engine_summary =
            case p.technology
                when 'fiber' then
                    'Fiber internet service is available in select parts of ' ||
                    p.locality_name ||
                    '. Exact availability and service options vary by address.'

                when 'coax' then
                    'Cable internet service is available in select parts of ' ||
                    p.locality_name ||
                    '. Exact availability and service options vary by address.'

                else
                    'High-speed internet service is available in select parts of ' ||
                    p.locality_name ||
                    '. Network technology and service options vary by address.'
            end,

        key_answer =
            'Service is available in select areas of ' ||
            p.locality_name ||
            '. Exact availability should be confirmed using the service address.',

        og_title =
            case p.technology
                when 'fiber' then
                    'Fiber Internet in ' ||
                    p.locality_name || ', ' ||
                    p.state_code

                when 'coax' then
                    'Cable Internet in ' ||
                    p.locality_name || ', ' ||
                    p.state_code

                else
                    'Internet Service in ' ||
                    p.locality_name || ', ' ||
                    p.state_code
            end,

        og_description =
            'Explore internet service and check availability in ' ||
            p.locality_name || ', ' ||
            p.state_code || '.',

        content_generated_at = now(),
        updated_at = now()

    where id = target_page_id;
end;
$$;


create or replace function public.portfolio_generate_local_keywords(
    target_page_id uuid
)
returns void
language plpgsql
as $$
declare
    p public.seo_pages%rowtype;
begin
    select *
    into p
    from public.seo_pages
    where id = target_page_id;

    if not found then
        raise exception 'SEO page not found';
    end if;

    if p.hierarchy_level <> 3
       or p.geographic_scope <> 'local' then
        raise exception 'Page is not a local market page';
    end if;

    delete from public.seo_page_keywords
    where page_id = target_page_id;

    insert into public.seo_page_keywords (
        page_id,
        keyword,
        keyword_type,
        search_intent,
        priority,
        active
    )
    values
    (
        target_page_id,
        case
            when p.technology = 'fiber'
                then 'fiber internet ' ||
                     p.locality_name || ' ' ||
                     p.state_code
            else 'internet service ' ||
                 p.locality_name || ' ' ||
                 p.state_code
        end,
        'primary',
        'local',
        100,
        true
    ),
    (
        target_page_id,
        'internet provider ' ||
        p.locality_name || ' ' ||
        p.state_code,
        'secondary',
        'local',
        95,
        true
    ),
    (
        target_page_id,
        'high speed internet ' ||
        p.locality_name || ' ' ||
        p.state_code,
        'secondary',
        'commercial',
        90,
        true
    ),
    (
        target_page_id,
        'internet availability ' ||
        p.locality_name || ' ' ||
        p.state_code,
        'long_tail',
        'local',
        85,
        true
    );

    if p.technology in ('fiber', 'fiber_coax') then
        insert into public.seo_page_keywords (
            page_id,
            keyword,
            keyword_type,
            search_intent,
            priority,
            active
        )
        values (
            target_page_id,
            'fiber internet ' ||
            p.locality_name || ' ' ||
            p.state_code,
            'secondary',
            'commercial',
            95,
            true
        );
    end if;

    if p.technology in ('coax', 'fiber_coax') then
        insert into public.seo_page_keywords (
            page_id,
            keyword,
            keyword_type,
            search_intent,
            priority,
            active
        )
        values (
            target_page_id,
            'cable internet ' ||
            p.locality_name || ' ' ||
            p.state_code,
            'secondary',
            'commercial',
            90,
            true
        );
    end if;
end;
$$;


create or replace function public.portfolio_generate_local_faqs(
    target_page_id uuid
)
returns void
language plpgsql
as $$
declare
    p public.seo_pages%rowtype;
begin
    select *
    into p
    from public.seo_pages
    where id = target_page_id;

    if not found then
        raise exception 'SEO page not found';
    end if;

    delete from public.seo_page_faqs
    where page_id = target_page_id;

    insert into public.seo_page_faqs (
        page_id,
        question,
        short_answer,
        detailed_answer,
        answer_type,
        display_order,
        include_on_page,
        include_in_schema,
        authoritative,
        source_reference,
        verified_at,
        active
    )
    values
    (
        target_page_id,
        'Is internet service available in ' ||
        p.locality_name || ', ' ||
        p.state_code || '?',

        'Service is available in select areas of ' ||
        p.locality_name || '.',

        'Exact technology, plans, speeds, pricing, promotions, and availability can vary by service address.',

        'availability',
        1,
        true,
        true,
        true,
        'Local service-area records',
        now(),
        true
    ),
    (
        target_page_id,
        'How do I check internet availability in ' ||
        p.locality_name || '?',

        'Use the exact service address to confirm availability.',

        'Address-level verification is the most accurate way to confirm technology, service options, and availability.',

        'how_to',
        2,
        true,
        true,
        true,
        'Address-level availability process',
        now(),
        true
    );

    if p.technology in ('fiber', 'fiber_coax') then
        insert into public.seo_page_faqs (
            page_id,
            question,
            short_answer,
            detailed_answer,
            answer_type,
            display_order,
            include_on_page,
            include_in_schema,
            authoritative,
            source_reference,
            verified_at,
            active
        )
        values (
            target_page_id,
            'Is fiber internet available in ' ||
            p.locality_name || '?',

            'Fiber is available in select parts of the market.',

            'Fiber availability is address-specific and should be confirmed for the exact service location.',

            'availability',
            3,
            true,
            true,
            true,
            'Local technology records',
            now(),
            true
        );
    end if;

    if p.technology in ('coax', 'fiber_coax') then
        insert into public.seo_page_faqs (
            page_id,
            question,
            short_answer,
            detailed_answer,
            answer_type,
            display_order,
            include_on_page,
            include_in_schema,
            authoritative,
            source_reference,
            verified_at,
            active
        )
        values (
            target_page_id,
            'Is cable internet available in ' ||
            p.locality_name || '?',

            'Cable internet is available in select parts of the market.',

            'Cable internet availability is address-specific and should be confirmed for the exact service location.',

            'availability',
            4,
            true,
            true,
            true,
            'Local technology records',
            now(),
            true
        );
    end if;
end;
$$;


create or replace function public.portfolio_generate_local_sections(
    target_page_id uuid
)
returns void
language plpgsql
as $$
declare
    p public.seo_pages%rowtype;
begin
    select *
    into p
    from public.seo_pages
    where id = target_page_id;

    if not found then
        raise exception 'SEO page not found';
    end if;

    delete from public.seo_content_sections
    where page_id = target_page_id;

    insert into public.seo_content_sections (
        page_id,
        section_key,
        heading,
        subheading,
        content,
        section_type,
        display_order,
        active
    )
    values
    (
        target_page_id,
        'local-overview',
        'Internet Service in ' || p.locality_name,
        'Local connectivity options.',
        'High-speed internet service is available to select locations in and around ' ||
        p.locality_name ||
        '. Exact technology and availability vary by address.',
        'text',
        10,
        true
    ),
    (
        target_page_id,
        'address-check',
        'Check Internet Availability in ' ||
        p.locality_name,
        'Service varies by street address.',
        'Use the exact service address to confirm technology, service options, and availability.',
        'availability',
        40,
        true
    );

    if p.technology in ('fiber', 'fiber_coax') then
        insert into public.seo_content_sections (
            page_id,
            section_key,
            heading,
            subheading,
            content,
            section_type,
            display_order,
            active
        )
        values (
            target_page_id,
            'fiber-service',
            'Fiber Internet in ' ||
            p.locality_name,
            'Fiber service where available.',
            'Fiber internet is available in select parts of the market. Exact availability depends on the service address.',
            'features',
            20,
            true
        );
    end if;

    if p.technology in ('coax', 'fiber_coax') then
        insert into public.seo_content_sections (
            page_id,
            section_key,
            heading,
            subheading,
            content,
            section_type,
            display_order,
            active
        )
        values (
            target_page_id,
            'coax-service',
            'Cable Internet in ' ||
            p.locality_name,
            'Cable internet service where available.',
            'Cable internet is available in select parts of the market. Exact availability depends on the service address.',
            'features',
            30,
            true
        );
    end if;
end;
$$;


create or replace function public.portfolio_generate_local_content_bundle(
    target_page_id uuid
)
returns void
language plpgsql
as $$
begin
    perform public.portfolio_generate_local_seo_defaults(
        target_page_id
    );

    perform public.portfolio_generate_local_keywords(
        target_page_id
    );

    perform public.portfolio_generate_local_faqs(
        target_page_id
    );

    perform public.portfolio_generate_local_sections(
        target_page_id
    );
end;
$$;

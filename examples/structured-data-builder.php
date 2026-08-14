<?php
/**
 * Public-safe JSON-LD builder for a structured SEO/AEO page bundle.
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Build FAQ schema independently from visible FAQ rendering.
 *
 * A FAQ can be visible on the page but excluded from structured data, or
 * eligible for schema while another presentation decision controls whether it
 * appears in the visible FAQ section.
 */
function portfolio_seo_schema_faqs(array $page): array
{
    $faqs = $page['faqs'] ?? [];

    if (!is_array($faqs)) {
        return [];
    }

    $entities = [];

    foreach ($faqs as $faq) {
        if (!is_array($faq)) {
            continue;
        }

        if (
            array_key_exists('include_in_schema', $faq) &&
            !$faq['include_in_schema']
        ) {
            continue;
        }

        $question = isset($faq['question'])
            ? trim(
                wp_strip_all_tags(
                    (string) $faq['question']
                )
            )
            : '';

        $answer = !empty($faq['detailed_answer'])
            ? trim(
                wp_strip_all_tags(
                    (string) $faq['detailed_answer']
                )
            )
            : trim(
                wp_strip_all_tags(
                    (string) (
                        $faq['short_answer'] ?? ''
                    )
                )
            );

        if ($question === '' || $answer === '') {
            continue;
        }

        $entities[] = [
            '@type' => 'Question',
            'name'  => $question,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => $answer,
            ],
        ];
    }

    return $entities;
}


/**
 * Build one schema graph from the page bundle.
 */
function portfolio_seo_build_schema(
    array $page,
    array $fallback = []
): array {
    $canonical_url = !empty($page['canonical_url'])
        ? esc_url_raw(
            (string) $page['canonical_url']
        )
        : esc_url_raw(
            (string) (
                $fallback['canonical_url'] ?? ''
            )
        );

    $seo_title = !empty($page['seo_title'])
        ? sanitize_text_field(
            (string) $page['seo_title']
        )
        : sanitize_text_field(
            (string) (
                $fallback['seo_title'] ?? ''
            )
        );

    $h1 = !empty($page['h1'])
        ? sanitize_text_field(
            (string) $page['h1']
        )
        : sanitize_text_field(
            (string) (
                $fallback['h1'] ?? ''
            )
        );

    $description = !empty($page['meta_description'])
        ? sanitize_text_field(
            (string) $page['meta_description']
        )
        : sanitize_text_field(
            (string) (
                $fallback['meta_description'] ?? ''
            )
        );

    $locality = !empty($page['locality_name'])
        ? sanitize_text_field(
            (string) $page['locality_name']
        )
        : sanitize_text_field(
            (string) (
                $fallback['locality_name'] ?? ''
            )
        );

    $state = !empty($page['state_code'])
        ? sanitize_text_field(
            (string) $page['state_code']
        )
        : sanitize_text_field(
            (string) (
                $fallback['state_code'] ?? ''
            )
        );

    $technology = !empty($page['technology'])
        ? sanitize_key(
            (string) $page['technology']
        )
        : sanitize_key(
            (string) (
                $fallback['technology'] ?? ''
            )
        );

    $graph = [];

    if ($canonical_url !== '') {
        $graph[] = [
            '@type'       => 'WebPage',
            '@id'         =>
                $canonical_url . '#webpage',
            'url'         =>
                $canonical_url,
            'name'        =>
                wp_strip_all_tags($seo_title),
            'headline'    =>
                wp_strip_all_tags($h1),
            'description' =>
                wp_strip_all_tags($description),
        ];
    }

    if (
        $canonical_url !== '' &&
        $locality !== ''
    ) {
        $graph[] = [
            '@type' => 'Service',
            '@id'   =>
                $canonical_url . '#service',
            'name'  =>
                'Internet Service in ' .
                $locality .
                ($state !== '' ? ', ' . $state : ''),
            'serviceType' =>
                $technology !== ''
                    ? $technology
                    : 'Internet Service',
            'areaServed' => [
                '@type' => 'City',
                'name'  => $locality,
            ],
        ];
    }

    $faq_entities =
        portfolio_seo_schema_faqs($page);

    if (
        $canonical_url !== '' &&
        !empty($faq_entities)
    ) {
        $graph[] = [
            '@type' => 'FAQPage',
            '@id'   =>
                $canonical_url . '#faq',
            'mainEntity' =>
                $faq_entities,
        ];
    }

    return [
        '@context' => 'https://schema.org',
        '@graph'   => $graph,
    ];
}

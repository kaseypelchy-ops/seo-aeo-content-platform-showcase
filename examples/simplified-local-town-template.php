<?php
/**
 * Simplified Local Town Template Example
 *
 * Public portfolio sample based on a production WordPress template.
 * Production business rules, styling, credentials, endpoints, pricing,
 * market exceptions, and internal integrations have been removed.
 *
 * Example parameters:
 *   {town}
 *   {state}
 *   {state_code}
 *   {technology}
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_id = get_queried_object_id();

/*
|--------------------------------------------------------------------------
| 1. Load reusable WordPress market fields
|--------------------------------------------------------------------------
|
| The production template supports ACF and falls back to native
| WordPress custom fields.
|
*/

$town_name = function_exists('get_field')
    ? get_field('town_name', $page_id)
    : get_post_meta($page_id, 'town_name', true);

$town_state = function_exists('get_field')
    ? get_field('town_state', $page_id)
    : get_post_meta($page_id, 'town_state', true);

$town_state_name = function_exists('get_field')
    ? get_field('town_state_name', $page_id)
    : get_post_meta($page_id, 'town_state_name', true);

$fiber_available = function_exists('get_field')
    ? get_field('fiber_available', $page_id)
    : get_post_meta($page_id, 'fiber_available', true);

$coax_available = function_exists('get_field')
    ? get_field('coax_available', $page_id)
    : get_post_meta($page_id, 'coax_available', true);


/*
|--------------------------------------------------------------------------
| 2. Retrieve structured SEO / AEO data
|--------------------------------------------------------------------------
|
| The production plugin resolves the current route and returns the
| matching published Supabase record.
|
| If Supabase or the plugin is unavailable, the template continues
| using its dynamic WordPress fallback values.
|
*/

$seo_data = [];

if (function_exists('zito_seo_current_page')) {
    $seo_result = zito_seo_current_page();

    if (is_array($seo_result)) {
        $seo_data = $seo_result;
    }
}


/*
|--------------------------------------------------------------------------
| 3. Build dynamic fallback SEO fields
|--------------------------------------------------------------------------
|
| These demonstrate the variable model used by the reusable template.
|
| Example rendered values:
|   {town}
|   {state}
|   {state_code}
|
*/

$fallback_title =
    'High-Speed Internet in ' .
    $town_name .
    ', ' .
    $town_state .
    ' | Provider';

$fallback_h1 =
    'High-Speed Internet in ' .
    $town_name .
    ', ' .
    $town_state_name;

$fallback_meta =
    'Explore high-speed internet service in ' .
    $town_name .
    ', ' .
    $town_state_name .
    '. Availability and technology can vary by service address.';


/*
|--------------------------------------------------------------------------
| 4. Prefer published Supabase values
|--------------------------------------------------------------------------
|
| Supabase can override the fallback values without requiring a new
| WordPress template or code deployment for each local market.
|
*/

$seo_title = !empty($seo_data['seo_title'])
    ? sanitize_text_field($seo_data['seo_title'])
    : $fallback_title;

$seo_h1 = !empty($seo_data['h1'])
    ? sanitize_text_field($seo_data['h1'])
    : $fallback_h1;

$seo_meta_description = !empty($seo_data['meta_description'])
    ? sanitize_text_field($seo_data['meta_description'])
    : $fallback_meta;

$canonical_url = !empty($seo_data['canonical_url'])
    ? esc_url_raw($seo_data['canonical_url'])
    : get_permalink($page_id);

$technology = !empty($seo_data['technology'])
    ? sanitize_key($seo_data['technology'])
    : '';


/*
|--------------------------------------------------------------------------
| 5. Build fallback AEO / FAQ content
|--------------------------------------------------------------------------
*/

$faq_items = [
    [
        'question' =>
            'Is internet service available in ' .
            $town_name .
            ', ' .
            $town_state .
            '?',

        'answer' =>
            'Service is available in select areas around ' .
            $town_name .
            '. Availability should be confirmed using the exact address.',
    ],
    [
        'question' =>
            'What internet options are available in ' .
            $town_name .
            '?',

        'answer' =>
            'Available plans and technologies can vary by market and service address.',
    ],
];


/*
|--------------------------------------------------------------------------
| 6. Prefer structured Supabase FAQs when available
|--------------------------------------------------------------------------
*/

if (!empty($seo_data['faqs']) && is_array($seo_data['faqs'])) {
    $supabase_faqs = [];

    foreach ($seo_data['faqs'] as $faq) {
        if (!is_array($faq)) {
            continue;
        }

        if (
            array_key_exists('include_on_page', $faq) &&
            !$faq['include_on_page']
        ) {
            continue;
        }

        $question = isset($faq['question'])
            ? trim(wp_strip_all_tags((string) $faq['question']))
            : '';

        $answer = !empty($faq['detailed_answer'])
            ? trim(wp_strip_all_tags((string) $faq['detailed_answer']))
            : (
                !empty($faq['short_answer'])
                    ? trim(wp_strip_all_tags((string) $faq['short_answer']))
                    : ''
            );

        if ($question !== '' && $answer !== '') {
            $supabase_faqs[] = [
                'question' => $question,
                'answer'   => $answer,
            ];
        }
    }

    if (!empty($supabase_faqs)) {
        $faq_items = $supabase_faqs;
    }
}


/*
|--------------------------------------------------------------------------
| 7. Build structured data
|--------------------------------------------------------------------------
|
| The production template builds a larger schema graph. This sample
| shows the route-specific WebPage, Service, and FAQPage concepts.
|
*/

$schema_graph = [
    [
        '@type'       => 'WebPage',
        '@id'         => $canonical_url . '#webpage',
        'url'         => $canonical_url,
        'name'        => wp_strip_all_tags($seo_title),
        'headline'    => wp_strip_all_tags($seo_h1),
        'description' => wp_strip_all_tags($seo_meta_description),
    ],
    [
        '@type'       => 'Service',
        '@id'         => $canonical_url . '#service',
        'name'        =>
            'Internet Service in ' .
            $town_name .
            ', ' .
            $town_state,
        'serviceType' => $technology ?: 'Internet Service',
        'areaServed'  => [
            '@type' => 'City',
            'name'  => $town_name,
            'containedInPlace' => [
                '@type' => 'State',
                'name'  => $town_state_name,
            ],
        ],
    ],
];

if (!empty($faq_items)) {
    $schema_graph[] = [
        '@type' => 'FAQPage',
        '@id'   => $canonical_url . '#faq',
        'mainEntity' => array_map(
            function ($item) {
                return [
                    '@type' => 'Question',
                    'name'  => wp_strip_all_tags($item['question']),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => wp_strip_all_tags($item['answer']),
                    ],
                ];
            },
            $faq_items
        ),
    ];
}

$schema_payload = [
    '@context' => 'https://schema.org',
    '@graph'   => $schema_graph,
];
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php echo esc_html($seo_title); ?></title>

    <meta
        name="description"
        content="<?php echo esc_attr($seo_meta_description); ?>"
    >

    <link
        rel="canonical"
        href="<?php echo esc_url($canonical_url); ?>"
    >

    <script type="application/ld+json">
        <?php
        echo wp_json_encode(
            $schema_payload,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE |
            JSON_PRETTY_PRINT
        );
        ?>
    </script>

    <?php wp_head(); ?>
</head>

<body <?php body_class('local-town-page'); ?>>
<?php wp_body_open(); ?>

<main>
    <header>
        <p>
            <?php
            echo esc_html(
                $town_name . ', ' . $town_state_name
            );
            ?>
        </p>

        <h1><?php echo esc_html($seo_h1); ?></h1>

        <?php if ($technology) : ?>
            <p>
                Technology:
                <?php echo esc_html($technology); ?>
            </p>
        <?php endif; ?>
    </header>

    <section>
        <h2>
            Internet Service in
            <?php echo esc_html($town_name); ?>
        </h2>

        <p>
            This section is rendered from the same reusable
            WordPress template for each configured market.
        </p>
    </section>

    <?php if (!empty($faq_items)) : ?>
        <section>
            <h2>Frequently Asked Questions</h2>

            <?php foreach ($faq_items as $faq) : ?>
                <article>
                    <h3>
                        <?php echo esc_html($faq['question']); ?>
                    </h3>

                    <p>
                        <?php echo esc_html($faq['answer']); ?>
                    </p>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>

<?php wp_footer(); ?>
</body>
</html>

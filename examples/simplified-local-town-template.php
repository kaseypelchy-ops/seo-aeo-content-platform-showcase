<?php
/**
 * Simplified Local Town Template
 *
 * Public portfolio sample showing how a reusable WordPress page can combine
 * native WordPress market fields with a Supabase SEO/AEO content bundle.
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_id = get_queried_object_id();


/*
|--------------------------------------------------------------------------
| WordPress fallback market values
|--------------------------------------------------------------------------
*/

$town_name = function_exists('get_field')
    ? get_field('town_name', $page_id)
    : get_post_meta(
        $page_id,
        'town_name',
        true
    );

$state_code = function_exists('get_field')
    ? get_field('town_state', $page_id)
    : get_post_meta(
        $page_id,
        'town_state',
        true
    );

$state_name = function_exists('get_field')
    ? get_field('town_state_name', $page_id)
    : get_post_meta(
        $page_id,
        'town_state_name',
        true
    );

$local_technology = function_exists('get_field')
    ? get_field('technology', $page_id)
    : get_post_meta(
        $page_id,
        'technology',
        true
    );


/*
|--------------------------------------------------------------------------
| Structured Supabase page bundle
|--------------------------------------------------------------------------
*/

$seo_data = [];

if (function_exists('portfolio_seo_current_page')) {
    $result = portfolio_seo_current_page();

    if (is_array($result)) {
        $seo_data = $result;
    }
}


/*
|--------------------------------------------------------------------------
| Fallback SEO / AEO values
|--------------------------------------------------------------------------
*/

$fallback_title =
    'High-Speed Internet in ' .
    $town_name .
    ', ' .
    $state_code .
    ' | Provider';

$fallback_h1 =
    'High-Speed Internet in ' .
    $town_name .
    ', ' .
    $state_name;

$fallback_meta =
    'Explore high-speed internet service in ' .
    $town_name .
    ', ' .
    $state_name .
    '. Availability and technology can vary by service address.';

$fallback_canonical =
    get_permalink($page_id);


/*
|--------------------------------------------------------------------------
| Prefer structured database values
|--------------------------------------------------------------------------
*/

$seo_title = !empty($seo_data['seo_title'])
    ? sanitize_text_field(
        (string) $seo_data['seo_title']
    )
    : $fallback_title;

$seo_h1 = !empty($seo_data['h1'])
    ? sanitize_text_field(
        (string) $seo_data['h1']
    )
    : $fallback_h1;

$seo_meta_description =
    !empty($seo_data['meta_description'])
        ? sanitize_text_field(
            (string) $seo_data['meta_description']
        )
        : $fallback_meta;

$canonical_url =
    !empty($seo_data['canonical_url'])
        ? esc_url_raw(
            (string) $seo_data['canonical_url']
        )
        : $fallback_canonical;

$robots_index =
    array_key_exists('robots_index', $seo_data)
        ? (bool) $seo_data['robots_index']
        : true;

$robots_follow =
    array_key_exists('robots_follow', $seo_data)
        ? (bool) $seo_data['robots_follow']
        : true;

$og_title =
    !empty($seo_data['og_title'])
        ? sanitize_text_field(
            (string) $seo_data['og_title']
        )
        : $seo_title;

$og_description =
    !empty($seo_data['og_description'])
        ? sanitize_text_field(
            (string) $seo_data['og_description']
        )
        : $seo_meta_description;

$technology =
    !empty($seo_data['technology'])
        ? sanitize_key(
            (string) $seo_data['technology']
        )
        : sanitize_key(
            (string) $local_technology
        );


/*
|--------------------------------------------------------------------------
| Visible FAQs
|--------------------------------------------------------------------------
*/

$faq_items = [];

if (
    !empty($seo_data['faqs']) &&
    is_array($seo_data['faqs'])
) {
    foreach ($seo_data['faqs'] as $faq) {
        if (!is_array($faq)) {
            continue;
        }

        if (
            array_key_exists(
                'include_on_page',
                $faq
            ) &&
            !$faq['include_on_page']
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

        $answer =
            !empty($faq['detailed_answer'])
                ? trim(
                    wp_strip_all_tags(
                        (string)
                        $faq['detailed_answer']
                    )
                )
                : trim(
                    wp_strip_all_tags(
                        (string) (
                            $faq['short_answer'] ?? ''
                        )
                    )
                );

        if (
            $question !== '' &&
            $answer !== ''
        ) {
            $faq_items[] = [
                'question' => $question,
                'answer'   => $answer,
            ];
        }
    }
}


/*
|--------------------------------------------------------------------------
| Dynamic content sections
|--------------------------------------------------------------------------
*/

$content_sections = [];

if (
    !empty($seo_data['content_sections']) &&
    is_array($seo_data['content_sections'])
) {
    $content_sections =
        $seo_data['content_sections'];
}


/*
|--------------------------------------------------------------------------
| JSON-LD
|--------------------------------------------------------------------------
*/

$schema_payload = function_exists(
    'portfolio_seo_build_schema'
)
    ? portfolio_seo_build_schema(
        $seo_data,
        [
            'canonical_url' =>
                $canonical_url,
            'seo_title' =>
                $seo_title,
            'h1' =>
                $seo_h1,
            'meta_description' =>
                $seo_meta_description,
            'locality_name' =>
                $town_name,
            'state_code' =>
                $state_code,
            'technology' =>
                $technology,
        ]
    )
    : [
        '@context' =>
            'https://schema.org',
        '@graph' =>
            [],
    ];

$robots_value =
    ($robots_index ? 'index' : 'noindex') .
    ', ' .
    ($robots_follow ? 'follow' : 'nofollow');
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        <?php echo esc_html($seo_title); ?>
    </title>

    <meta
        name="description"
        content="<?php
            echo esc_attr(
                $seo_meta_description
            );
        ?>"
    >

    <meta
        name="robots"
        content="<?php
            echo esc_attr(
                $robots_value
            );
        ?>"
    >

    <link
        rel="canonical"
        href="<?php
            echo esc_url(
                $canonical_url
            );
        ?>"
    >

    <meta
        property="og:title"
        content="<?php
            echo esc_attr($og_title);
        ?>"
    >

    <meta
        property="og:description"
        content="<?php
            echo esc_attr(
                $og_description
            );
        ?>"
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
                $town_name .
                ', ' .
                $state_name
            );
            ?>
        </p>

        <h1>
            <?php echo esc_html($seo_h1); ?>
        </h1>

        <?php if ($technology !== '') : ?>
            <p>
                Technology:
                <?php
                echo esc_html(
                    $technology
                );
                ?>
            </p>
        <?php endif; ?>
    </header>

    <?php
    foreach ($content_sections as $section) :
        if (!is_array($section)) {
            continue;
        }

        $heading = isset($section['heading'])
            ? sanitize_text_field(
                (string) $section['heading']
            )
            : '';

        $subheading =
            isset($section['subheading'])
                ? sanitize_text_field(
                    (string)
                    $section['subheading']
                )
                : '';

        $content = isset($section['content'])
            ? wp_kses_post(
                (string) $section['content']
            )
            : '';

        if (
            $heading === '' &&
            $content === ''
        ) {
            continue;
        }
        ?>
        <section>
            <?php if ($heading !== '') : ?>
                <h2>
                    <?php
                    echo esc_html($heading);
                    ?>
                </h2>
            <?php endif; ?>

            <?php if ($subheading !== '') : ?>
                <p>
                    <strong>
                        <?php
                        echo esc_html(
                            $subheading
                        );
                        ?>
                    </strong>
                </p>
            <?php endif; ?>

            <?php if ($content !== '') : ?>
                <div>
                    <?php
                    echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <?php if (!empty($faq_items)) : ?>
        <section>
            <h2>Frequently Asked Questions</h2>

            <?php foreach ($faq_items as $faq) : ?>
                <article>
                    <h3>
                        <?php
                        echo esc_html(
                            $faq['question']
                        );
                        ?>
                    </h3>

                    <p>
                        <?php
                        echo esc_html(
                            $faq['answer']
                        );
                        ?>
                    </p>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>

<?php wp_footer(); ?>
</body>
</html>

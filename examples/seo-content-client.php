<?php
/**
 * Public-safe Supabase page-content client.
 *
 * Representative example of the route-resolution layer used between
 * WordPress and a Supabase-backed SEO/AEO content model.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Convert the current WordPress request into the same path format stored
 * in the database.
 */
function portfolio_seo_current_request_path(): string
{
    $request_uri = isset($_SERVER['REQUEST_URI'])
        ? wp_unslash((string) $_SERVER['REQUEST_URI'])
        : '/';

    $path = wp_parse_url($request_uri, PHP_URL_PATH);

    if (!is_string($path) || $path === '') {
        return '/';
    }

    $path = '/' . ltrim($path, '/');

    if ($path === '/') {
        return '/';
    }

    return trailingslashit($path);
}


/**
 * Fetch one published page bundle from the public page-content view.
 *
 * Production credentials and endpoints are configured outside source.
 */
function portfolio_seo_fetch_page_by_path(string $path): ?array
{
    if (
        !defined('PORTFOLIO_SEO_SUPABASE_URL') ||
        !defined('PORTFOLIO_SEO_SUPABASE_KEY')
    ) {
        return null;
    }

    $normalized_path = $path === '/'
        ? '/'
        : trailingslashit('/' . ltrim($path, '/'));

    $query = http_build_query(
        [
            'path'   => 'eq.' . $normalized_path,
            'active' => 'eq.true',
            'status' => 'eq.published',
            'limit'  => '1',
        ],
        '',
        '&',
        PHP_QUERY_RFC3986
    );

    $url =
        rtrim(PORTFOLIO_SEO_SUPABASE_URL, '/') .
        '/rest/v1/seo_page_content_public?' .
        $query;

    $response = wp_remote_get(
        $url,
        [
            'timeout' => 4,
            'headers' => [
                'apikey' =>
                    PORTFOLIO_SEO_SUPABASE_KEY,

                'Authorization' =>
                    'Bearer ' .
                    PORTFOLIO_SEO_SUPABASE_KEY,

                'Accept' =>
                    'application/json',
            ],
        ]
    );

    if (is_wp_error($response)) {
        return null;
    }

    $status_code = wp_remote_retrieve_response_code(
        $response
    );

    if ($status_code < 200 || $status_code >= 300) {
        return null;
    }

    $body = wp_remote_retrieve_body($response);

    $decoded = json_decode(
        $body,
        true
    );

    if (
        !is_array($decoded) ||
        empty($decoded) ||
        !isset($decoded[0]) ||
        !is_array($decoded[0])
    ) {
        return null;
    }

    $page = $decoded[0];

    if (
        empty($page['path']) ||
        empty($page['active']) ||
        ($page['status'] ?? '') !== 'published'
    ) {
        return null;
    }

    return $page;
}


/**
 * Resolve the current WordPress request to its structured SEO/AEO page bundle.
 */
function portfolio_seo_current_page(): ?array
{
    return portfolio_seo_fetch_page_by_path(
        portfolio_seo_current_request_path()
    );
}

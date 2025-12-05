<?php
declare(strict_types=1);

namespace JuntaPlay\Front;

use WP_REST_Request;
use WP_REST_Response;
use JuntaPlay\Data\Pools;

defined('ABSPATH') || exit;

class Rest
{
    public function init(): void
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route('juntaplay/v1', '/pools/(?P<id>\d+)/quotas', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_quotas'],
            'permission_callback' => '__return_true',
            'args'                => [
                'status' => [
                    'type'    => 'string',
                    'default' => 'available',
                ],
            ],
        ]);
    }

    public function get_quotas(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;

        $pool_id = (int) $request['id'];
        $status  = $request->get_param('status');

        $pool = Pools::get($pool_id);

        if (!$pool) {
            return new WP_REST_Response(['message' => __('Campanha não encontrada.', 'juntaplay')], 404);
        }

        $table = "{$wpdb->prefix}jp_quotas";
        $where = $wpdb->prepare('WHERE pool_id = %d', $pool_id);

        if ($status && in_array($status, ['available', 'reserved', 'paid', 'canceled', 'expired'], true)) {
            $where .= $wpdb->prepare(' AND status = %s', $status);
        }

        $results = $wpdb->get_results("SELECT number, status FROM $table $where ORDER BY number ASC");

        return new WP_REST_Response([
            'pool'   => ['id' => $pool->id, 'title' => $pool->title],
            'quotas' => $results,
        ]);
    }
}

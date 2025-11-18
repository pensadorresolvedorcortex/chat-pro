<?php
/**
 * Helper: snapshot data for the GRUPOS dashboard card.
 */

defined('ABSPATH') or die('Silence is golden :)');

if ( ! function_exists( 'gdw_get_groups_card_snapshot' ) ) {
    /**
     * Collect the data used by the GRUPOS card so it can be reused via Ajax.
     *
     * @return array
     */
    function gdw_get_groups_card_snapshot() {
        $groups_admin_link = admin_url( 'admin.php?page=juntaplay-groups' );
        $badge_url         = 'https://www.juntaplay.com.br/wp-content/uploads/2025/11/dheniell.svg';

        $monthly_total = 0;
        $monthly_count = 0;
        $recent_orders = array();

        if ( function_exists( 'wc_get_orders' ) ) {
            $month_start = gmdate( 'Y-m-01 00:00:00' );
            // Use the comparator string format so WooCommerce doesn't receive an array in strtotime().
            $orders_this_month = wc_get_orders(
                array(
                    'status'       => array( 'wc-processing', 'wc-completed', 'wc-on-hold' ),
                    'date_created' => '>=' . $month_start,
                    'limit'        => -1,
                    'return'       => 'objects',
                )
            );

            foreach ( $orders_this_month as $order ) {
                $monthly_total += floatval( $order->get_total() );
            }
            $monthly_count = count( $orders_this_month );

            $recent_orders = wc_get_orders(
                array(
                    'status'  => array( 'wc-processing', 'wc-completed', 'wc-on-hold', 'wc-pending', 'wc-failed', 'wc-cancelled', 'wc-refunded' ),
                    'limit'   => 5,
                    'orderby' => 'date',
                    'order'   => 'DESC',
                    'return'  => 'objects',
                )
            );
        } else {
            $month_start = gmdate( 'Y-m-01 00:00:00' );

            $monthly_orders = new WP_Query(
                array(
                    'post_type'      => 'shop_order',
                    'posts_per_page' => -1,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                    'date_query'     => array(
                        array(
                            'after'     => $month_start,
                            'inclusive' => true,
                        ),
                    ),
                )
            );

            if ( $monthly_orders->have_posts() ) {
                foreach ( $monthly_orders->posts as $order_post ) {
                    $total = floatval( get_post_meta( $order_post->ID, '_order_total', true ) );
                    $monthly_total += $total;
                    $monthly_count++;
                }
                wp_reset_postdata();
            }

            $recent_query = new WP_Query(
                array(
                    'post_type'      => 'shop_order',
                    'posts_per_page' => 5,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                )
            );

            if ( $recent_query->have_posts() ) {
                $recent_orders = $recent_query->posts;
                wp_reset_postdata();
            }
        }

        $monthly_total_display = function_exists( 'wc_price' )
            ? wc_price( $monthly_total )
            : sprintf( 'R$ %s', number_format_i18n( $monthly_total, 2 ) );

        $new_users = get_users(
            array(
                'number'  => 6,
                'orderby' => 'registered',
                'order'   => 'DESC',
                'fields'  => array( 'ID', 'display_name', 'user_email' ),
            )
        );

        $new_user_cards = array();
        foreach ( $new_users as $user ) {
            $photo = get_user_meta( $user->ID, 'juntaplay_header', true );
            if ( empty( $photo ) ) {
                $photo = get_user_meta( $user->ID, 'profile_photo', true );
            }
            if ( empty( $photo ) ) {
                $photo = get_avatar_url( $user->ID, array( 'size' => 64 ) );
            }
            $new_user_cards[] = array(
                'name'  => $user->display_name,
                'photo' => esc_url_raw( $photo ),
            );
        }

        $pending_groups = array();
        if ( post_type_exists( 'juntaplay_group' ) ) {
            $groups_query = new WP_Query(
                array(
                    'post_type'      => 'juntaplay_group',
                    'post_status'    => array( 'pending', 'draft', 'future' ),
                    'posts_per_page' => 5,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                )
            );

            if ( $groups_query->have_posts() ) {
                foreach ( $groups_query->posts as $group_post ) {
                    $thumb = get_the_post_thumbnail_url( $group_post->ID, 'thumbnail' );
                    if ( empty( $thumb ) ) {
                        $thumb = $badge_url;
                    }
                    $pending_groups[] = array(
                        'title' => $group_post->post_title,
                        'thumb' => esc_url_raw( $thumb ),
                    );
                }
                wp_reset_postdata();
            }
        }

        $recent_orders_mapped = array();
        foreach ( $recent_orders as $order_item ) {
            if ( is_a( $order_item, 'WC_Order' ) ) {
                $date_label  = $order_item->get_date_created() ? $order_item->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '';
                $total_label = $order_item->get_formatted_order_total();
            } else {
                $date_label  = get_the_date( '', $order_item );
                $total_value = floatval( get_post_meta( $order_item->ID, '_order_total', true ) );
                $total_label = sprintf( 'R$ %s', number_format_i18n( $total_value, 2 ) );
            }

            $recent_orders_mapped[] = array(
                'date'  => $date_label,
                'total' => $total_label,
            );
        }

        return array(
            'links'               => array(
                'admin' => $groups_admin_link,
            ),
            'badge_url'           => $badge_url,
            'monthly_total'       => $monthly_total,
            'monthly_total_label' => $monthly_total_display,
            'monthly_count'       => $monthly_count,
            'new_users'           => $new_user_cards,
            'pending_groups'      => $pending_groups,
            'pending_count'       => count( $pending_groups ),
            'recent_orders'       => $recent_orders_mapped,
            'recent_count'        => count( $recent_orders_mapped ),
        );
    }
}


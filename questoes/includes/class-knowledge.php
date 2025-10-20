<?php
/**
 * Knowledge tracking.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles persistence of user knowledge progress.
 */
class Questoes_Knowledge {

    /**
     * User meta key.
     *
     * @var string
     */
    protected $meta_key = '_questoes_knowledge';

    /**
     * Retrieve sanitized knowledge data for a user.
     *
     * @param int $user_id Optional user ID. Defaults to current user.
     *
     * @return array
     */
    public function get_user_knowledge( $user_id = 0 ) {
        $user_id = $user_id ? absint( $user_id ) : get_current_user_id();

        if ( ! $user_id ) {
            return array(
                'questions' => array(),
                'courses'   => array(),
            );
        }

        $raw = get_user_meta( $user_id, $this->meta_key, true );

        if ( empty( $raw ) || ! is_array( $raw ) ) {
            return array(
                'questions' => array(),
                'courses'   => array(),
            );
        }

        $prepared = array();

        foreach ( array( 'questions', 'courses' ) as $type ) {
            $prepared[ $type ] = array();

            if ( empty( $raw[ $type ] ) || ! is_array( $raw[ $type ] ) ) {
                continue;
            }

            foreach ( $raw[ $type ] as $item_id => $item_data ) {
                $sanitized = $this->sanitize_item( $type, $item_data );

                if ( empty( $sanitized ) ) {
                    continue;
                }

                $prepared[ $type ][ $item_id ] = $sanitized;
            }
        }

        return $prepared;
    }

    /**
     * Persist a knowledge item.
     *
     * @param int    $user_id User ID.
     * @param string $type    Item type.
     * @param string $item_id Item identifier.
     * @param array  $data    Item payload.
     */
    public function upsert_item( $user_id, $type, $item_id, $data ) {
        $user_id = absint( $user_id );
        $type    = $this->sanitize_type( $type );
        $item_id = sanitize_key( $item_id );

        if ( ! $user_id || ! $type || ! $item_id ) {
            return;
        }

        $sanitized = $this->sanitize_item( $type, $data );

        if ( empty( $sanitized ) ) {
            return;
        }

        $current = $this->get_user_knowledge( $user_id );

        if ( empty( $current[ $type ] ) || ! is_array( $current[ $type ] ) ) {
            $current[ $type ] = array();
        }

        $sanitized['id']     = $item_id;
        $sanitized['type']   = $type;
        $sanitized['updated'] = isset( $sanitized['updated'] ) ? (int) $sanitized['updated'] : time();

        $current[ $type ][ $item_id ] = $sanitized;

        $current[ $type ] = $this->limit_items( $current[ $type ] );

        update_user_meta( $user_id, $this->meta_key, $current );
    }

    /**
     * Remove a knowledge item for a user.
     *
     * @param int    $user_id User ID.
     * @param string $type    Item type.
     * @param string $item_id Item ID.
     */
    public function remove_item( $user_id, $type, $item_id ) {
        $user_id = absint( $user_id );
        $type    = $this->sanitize_type( $type );
        $item_id = sanitize_key( $item_id );

        if ( ! $user_id || ! $type || ! $item_id ) {
            return;
        }

        $current = $this->get_user_knowledge( $user_id );

        if ( empty( $current[ $type ][ $item_id ] ) ) {
            return;
        }

        unset( $current[ $type ][ $item_id ] );

        update_user_meta( $user_id, $this->meta_key, $current );
    }

    /**
     * Sanitize a knowledge item payload.
     *
     * @param string $type Item type.
     * @param array  $data Raw data.
     *
     * @return array
     */
    protected function sanitize_item( $type, $data ) {
        if ( empty( $data ) || ! is_array( $data ) ) {
            return array();
        }

        $type = $this->sanitize_type( $type );

        if ( ! $type ) {
            return array();
        }

        $allowed = array(
            'title'        => 'text',
            'subtitle'     => 'text',
            'status'       => 'text',
            'progress'     => 'array',
            'answered'     => 'int',
            'total'        => 'int',
            'answered_ids' => 'array',
            'filters'      => 'array',
            'source'       => 'url',
            'context'      => 'text',
            'page'         => 'int',
            'link'         => 'url',
            'cta'          => 'text',
            'updated'      => 'int',
            'meta'         => 'array',
        );

        $sanitized = array();

        foreach ( $allowed as $key => $kind ) {
            if ( ! array_key_exists( $key, $data ) ) {
                continue;
            }

            $value = $data[ $key ];

            switch ( $kind ) {
                case 'int':
                    $sanitized[ $key ] = (int) $value;
                    break;
                case 'url':
                    $sanitized[ $key ] = esc_url_raw( $value );
                    break;
                case 'array':
                    $sanitized[ $key ] = is_array( $value ) ? $this->deep_sanitize_array( $value ) : array();
                    break;
                default:
                    $sanitized[ $key ] = sanitize_text_field( $value );
                    break;
            }
        }

        if ( ! isset( $sanitized['status'] ) ) {
            $sanitized['status'] = 'in-progress';
        }

        if ( ! isset( $sanitized['updated'] ) ) {
            $sanitized['updated'] = time();
        }

        return $sanitized;
    }

    /**
     * Sanitize an array deeply.
     *
     * @param array $value Raw value.
     *
     * @return array
     */
    protected function deep_sanitize_array( $value ) {
        $result = array();

        foreach ( $value as $key => $item ) {
            if ( is_array( $item ) ) {
                $result[ sanitize_key( $key ) ] = $this->deep_sanitize_array( $item );
            } else {
                $result[ sanitize_key( $key ) ] = is_scalar( $item ) ? sanitize_text_field( (string) $item ) : '';
            }
        }

        return $result;
    }

    /**
     * Keep only the most recent entries.
     *
     * @param array $items Knowledge items keyed by ID.
     *
     * @return array
     */
    protected function limit_items( $items ) {
        if ( empty( $items ) || ! is_array( $items ) ) {
            return array();
        }

        uasort(
            $items,
            function( $a, $b ) {
                $a_time = isset( $a['updated'] ) ? (int) $a['updated'] : 0;
                $b_time = isset( $b['updated'] ) ? (int) $b['updated'] : 0;

                if ( $a_time === $b_time ) {
                    return 0;
                }

                return ( $a_time > $b_time ) ? -1 : 1;
            }
        );

        return array_slice( $items, 0, 50, true );
    }

    /**
     * Normalize a type string.
     *
     * @param string $type Type string.
     *
     * @return string
     */
    protected function sanitize_type( $type ) {
        $type = sanitize_key( $type );

        if ( in_array( $type, array( 'questions', 'courses' ), true ) ) {
            return $type;
        }

        return '';
    }
}

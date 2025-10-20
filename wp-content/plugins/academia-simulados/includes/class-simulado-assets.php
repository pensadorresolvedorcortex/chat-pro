<?php
/**
 * Assets for the plugin.
 *
 * @package Academia_Simulados
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Academia_Simulados_Assets {
    /**
     * Initialise hooks.
     */
    public static function init() {
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_assets' ) );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Screen hook.
     */
    public static function admin_assets( $hook ) {
        $screen = get_current_screen();

        if ( ! $screen || 'simulado' !== $screen->post_type ) {
            return;
        }

        wp_enqueue_style(
            'academia-simulados-admin',
            ACADEMIA_SIMULADOS_URL . 'assets/css/admin.css',
            array(),
            ACADEMIA_SIMULADOS_VERSION
        );

        wp_enqueue_script(
            'academia-simulados-admin',
            ACADEMIA_SIMULADOS_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            ACADEMIA_SIMULADOS_VERSION,
            true
        );

        wp_localize_script(
            'academia-simulados-admin',
            'academiaSimuladosAdmin',
            array(
                'correctLabel'          => __( 'Resposta correta', 'academia-simulados' ),
                'removeAnswerLabel'     => __( 'Remover alternativa', 'academia-simulados' ),
                'minimumQuestionMessage'=> __( 'É necessário manter pelo menos uma questão no simulado.', 'academia-simulados' ),
            )
        );
    }

    /**
     * Enqueue front-end assets.
     */
    public static function frontend_assets() {
        wp_register_style(
            'academia-simulados-frontend',
            ACADEMIA_SIMULADOS_URL . 'assets/css/frontend.css',
            array(),
            ACADEMIA_SIMULADOS_VERSION
        );

        wp_register_script(
            'academia-simulados-frontend',
            ACADEMIA_SIMULADOS_URL . 'assets/js/frontend.js',
            array(),
            ACADEMIA_SIMULADOS_VERSION,
            true
        );

        wp_localize_script(
            'academia-simulados-frontend',
            'academiaSimulados',
            array(
                'progressLabel'      => __( 'Questões respondidas: %1$d de %2$d', 'academia-simulados' ),
                'scoreLabel'         => __( 'Acertos: %1$d de %2$d', 'academia-simulados' ),
                'completeLabel'      => __( 'Você concluiu o simulado! Pontuação final: %1$d de %2$d.', 'academia-simulados' ),
                'resetLabel'         => __( 'Refazer simulado', 'academia-simulados' ),
                'resetFeedback'      => __( 'Progresso reiniciado. Você pode tentar novamente.', 'academia-simulados' ),
                'defaultCorrectText' => __( 'Resposta correta! Parabéns.', 'academia-simulados' ),
                'defaultErrorText'   => __( 'Resposta incorreta. Tente novamente!', 'academia-simulados' ),
            )
        );
    }
}

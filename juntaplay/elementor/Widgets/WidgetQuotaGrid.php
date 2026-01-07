<?php
declare(strict_types=1);

namespace JuntaPlayElementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

class WidgetQuotaGrid extends Widget_Base
{
    public function get_name(): string
    {
        return 'juntaplay_quota_grid';
    }

    public function get_title(): string
    {
        return __('JuntaPlay — Seletor de Cotas', 'juntaplay');
    }

    public function get_icon(): string
    {
        return 'eicon-number-field';
    }

    public function get_categories(): array
    {
        return ['general'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section('content', [
            'label' => __('Conteúdo', 'juntaplay'),
        ]);

        $this->add_control('pool_id', [
            'label'       => __('ID da Campanha', 'juntaplay'),
            'type'        => Controls_Manager::NUMBER,
            'default'     => 0,
        ]);

        $this->add_control('per_page', [
            'label'   => __('Itens por página', 'juntaplay'),
            'type'    => Controls_Manager::NUMBER,
            'default' => 100,
        ]);

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $id       = isset($settings['pool_id']) ? (int) $settings['pool_id'] : 0;
        $per_page = isset($settings['per_page']) ? (int) $settings['per_page'] : 100;

        echo do_shortcode('[juntaplay_quota_selector id="' . $id . '" per_page="' . $per_page . '"]');
    }
}

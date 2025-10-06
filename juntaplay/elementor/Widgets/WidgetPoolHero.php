<?php
declare(strict_types=1);

namespace JuntaPlayElementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

class WidgetPoolHero extends Widget_Base
{
    public function get_name(): string
    {
        return 'juntaplay_pool_hero';
    }

    public function get_title(): string
    {
        return __('JuntaPlay — Hero da Campanha', 'juntaplay');
    }

    public function get_icon(): string
    {
        return 'eicon-info-box';
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
            'description' => __('Informe o ID da campanha ou deixe 0 para detectar automaticamente em páginas de produto.', 'juntaplay'),
        ]);

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $id       = isset($settings['pool_id']) ? (int) $settings['pool_id'] : 0;

        echo do_shortcode('[juntaplay_pool id="' . $id . '"]');
    }
}

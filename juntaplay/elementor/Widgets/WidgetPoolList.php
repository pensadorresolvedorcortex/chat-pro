<?php
declare(strict_types=1);

namespace JuntaPlayElementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

class WidgetPoolList extends Widget_Base
{
    public function get_name(): string
    {
        return 'juntaplay_pool_list';
    }

    public function get_title(): string
    {
        return __('JuntaPlay — Lista de Campanhas', 'juntaplay');
    }

    public function get_icon(): string
    {
        return 'eicon-post-list';
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

        $this->add_control('show_search', [
            'label'        => __('Exibir busca', 'juntaplay'),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
        ]);

        $this->end_controls_section();
    }

    protected function render(): void
    {
        echo do_shortcode('[juntaplay_pools]');
    }
}

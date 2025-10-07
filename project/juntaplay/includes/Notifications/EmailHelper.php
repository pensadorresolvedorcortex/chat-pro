<?php
/**
 * HTML email helper for JuntaPlay notifications.
 */

declare(strict_types=1);

namespace JuntaPlay\Notifications;

use function add_filter;
use function apply_filters;
use function date_i18n;
use function esc_attr;
use function esc_html;
use function esc_url;
use function file_exists;
use function plugins_url;
use function sprintf;
use function wp_mail;
use function wp_kses_post;
use function __;

class EmailHelper
{
    private const BRAND_PRIMARY   = '#FF4858';
    private const BRAND_SECONDARY = '#00CCC0';

    public static function init(): void
    {
        add_filter('wp_mail_from_name', [__CLASS__, 'force_from_name']);
    }

    public static function force_from_name(string $name): string
    {
        return 'JuntaPlay';
    }

    /**
     * @param array<int, mixed> $blocks
     * @param array<string, mixed> $args
     */
    public static function send(string $to, string $subject, array $blocks, array $args = []): bool
    {
        $html    = self::render($blocks, $args + ['title' => $subject]);
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        return wp_mail($to, $subject, $html, $headers);
    }

    /**
     * @param array<int, mixed> $blocks
     * @param array<string, mixed> $args
     */
    public static function render(array $blocks, array $args = []): string
    {
        $brand     = self::get_brand_name();
        $title     = isset($args['title']) ? (string) $args['title'] : $brand;
        $headline  = isset($args['headline']) ? (string) $args['headline'] : '';
        $preheader = isset($args['preheader']) ? (string) $args['preheader'] : '';
        $logo      = isset($args['logo']) ? (string) $args['logo'] : self::get_logo_url();

        $footer_lines = $args['footer'] ?? [
            __('Essa mensagem foi enviada automaticamente pelo JuntaPlay.', 'juntaplay'),
            __('Se tiver dúvidas, basta responder este e-mail ou falar com o nosso suporte.', 'juntaplay'),
            sprintf(__('© %s JuntaPlay. Todos os direitos reservados.', 'juntaplay'), date_i18n('Y')),
        ];

        $preheader_html = $preheader !== ''
            ? '<div style="display:none!important;visibility:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#fff;max-height:0;max-width:0;opacity:0;overflow:hidden;">' . esc_html($preheader) . '</div>'
            : '';

        $headline_html = $headline !== ''
            ? '<h1 style="margin:0 0 18px;font-family:\'Fredoka\', \'Figtree\', \'Segoe UI\', sans-serif;font-size:26px;line-height:1.2;font-weight:600;color:#1F2937;text-align:left;">' . esc_html($headline) . '</h1>'
            : '';

        $body_html   = self::render_blocks($blocks);
        $footer_html = self::render_footer($footer_lines);

        $logo_html = '<img src="' . esc_url($logo) . '" alt="' . esc_attr($brand) . '" style="max-width:160px;height:auto;display:inline-block;" />';

        return '<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>' . esc_html($title) . '</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f7fb;font-family:\'Figtree\', \'Segoe UI\', sans-serif;color:#1f2937;">
' . $preheader_html . '
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f5f7fb;margin:0;padding:0;width:100%;">
    <tr>
        <td align="center" style="padding:32px 16px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 24px 60px rgba(31,41,55,0.08);">
                <tr>
                    <td style="padding:32px 32px 16px;text-align:center;background:linear-gradient(135deg,' . self::BRAND_PRIMARY . ',' . self::BRAND_SECONDARY . ');">
                        ' . $logo_html . '
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px 32px 16px;">' . $headline_html . $body_html . '</td>
                </tr>
                <tr>
                    <td style="padding:24px 32px;background-color:#f8fafc;text-align:center;">' . $footer_html . '</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>';
    }

    /**
     * @param array<int, mixed> $blocks
     */
    private static function render_blocks(array $blocks): string
    {
        $html = '';

        foreach ($blocks as $block) {
            if (is_string($block)) {
                $html .= self::paragraph($block);
                continue;
            }

            if (!is_array($block)) {
                continue;
            }

            $type = isset($block['type']) ? (string) $block['type'] : 'paragraph';

            switch ($type) {
                case 'heading':
                    $content = isset($block['content']) ? (string) $block['content'] : '';
                    if ($content !== '') {
                        $html .= '<h2 style="margin:0 0 16px;font-family:\'Fredoka\', \'Figtree\', \'Segoe UI\', sans-serif;font-size:22px;line-height:1.3;font-weight:600;color:#1F2937;">' . esc_html($content) . '</h2>';
                    }
                    break;
                case 'paragraph':
                    $content = isset($block['content']) ? (string) $block['content'] : '';
                    $html   .= self::paragraph($content);
                    break;
                case 'list':
                    $items = isset($block['items']) && is_array($block['items']) ? $block['items'] : [];
                    if ($items) {
                        $html .= '<ul style="margin:0 0 16px;padding-left:20px;color:#1F2937;font-size:15px;line-height:1.6;">';
                        foreach ($items as $item) {
                            if (is_string($item)) {
                                $html .= '<li>' . esc_html($item) . '</li>';
                            }
                        }
                        $html .= '</ul>';
                    }
                    break;
                case 'button':
                    $label = isset($block['label']) ? (string) $block['label'] : '';
                    $url   = isset($block['url']) ? (string) $block['url'] : '';
                    if ($label !== '' && $url !== '') {
                        $html .= '<p style="margin:24px 0 32px;text-align:center;">'
                            . '<a href="' . esc_url($url) . '" style="display:inline-block;padding:14px 28px;border-radius:999px;font-family:\'Fredoka\', \'Figtree\', sans-serif;font-weight:600;font-size:15px;color:#ffffff;background:' . self::BRAND_PRIMARY . ';text-decoration:none;">'
                            . esc_html($label)
                            . '</a></p>';
                    }
                    break;
                case 'code':
                    $content = isset($block['content']) ? (string) $block['content'] : '';
                    if ($content !== '') {
                        $html .= '<p style="margin:0 0 16px;">'
                            . '<span style="display:inline-block;padding:12px 18px;border-radius:16px;background-color:rgba(255,72,88,0.08);font-family:\'Fira Mono\', monospace;font-size:18px;letter-spacing:3px;color:' . self::BRAND_PRIMARY . ';">'
                            . esc_html($content)
                            . '</span></p>';
                    }
                    break;
                case 'html':
                    $content = isset($block['content']) ? (string) $block['content'] : '';
                    if ($content !== '') {
                        $html .= wp_kses_post($content);
                    }
                    break;
                case 'divider':
                    $html .= '<hr style="border:0;border-top:1px solid #E5E7EB;margin:28px 0;" />';
                    break;
                default:
                    $content = isset($block['content']) ? (string) $block['content'] : '';
                    if ($content !== '') {
                        $html .= self::paragraph($content);
                    }
                    break;
            }
        }

        return $html;
    }

    /**
     * @param array<int, string> $lines
     */
    private static function render_footer(array $lines): string
    {
        $html = '';
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            $html .= '<p style="margin:6px 0;font-size:13px;line-height:1.6;color:#4B5563;">' . esc_html((string) $line) . '</p>';
        }

        return $html;
    }

    private static function paragraph(string $content): string
    {
        if ($content === '') {
            return '';
        }

        return '<p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#1F2937;">' . esc_html($content) . '</p>';
    }

    private static function get_logo_url(): string
    {
        $asset = JP_DIR . 'assets/images/Juntaplay.svg';

        if (file_exists($asset)) {
            return plugins_url('assets/images/Juntaplay.svg', JP_FILE);
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="240" height="72" viewBox="0 0 240 72">'
            . '<rect width="240" height="72" rx="18" fill="' . self::BRAND_PRIMARY . '" />'
            . '<text x="120" y="44" text-anchor="middle" font-family="Fredoka, Figtree, Arial" font-size="32" fill="#ffffff" font-weight="600">JuntaPlay</text>'
            . '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private static function get_brand_name(): string
    {
        $brand = 'JuntaPlay';

        return (string) apply_filters('juntaplay/email/brand_name', $brand);
    }
}

<?php

declare(strict_types=1);

namespace JuntaPlay\Notifications;

use function add_filter;
use function apply_filters;
use function array_filter;
use function array_map;
use function array_shift;
use function array_values;
use function esc_attr;
use function esc_html;
use function esc_url;
use function get_bloginfo;
use function gmdate;
use function implode;
use function in_array;
use function is_array;
use function is_email;
use function ob_get_clean;
use function ob_start;
use function remove_filter;
use function sanitize_text_field;
use function trailingslashit;
use function trim;
use function wp_kses_post;
use function wp_mail;
use function wp_specialchars_decode;

class EmailHelper
{
    /**
     * Flag to avoid attaching filters repeatedly.
     */
    private static bool $initialized = false;

    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        // The actual filter attachment happens per send to limit scope, but this flag
        // allows other bootstrap logic to hook in the future without duplicating work.
        self::$initialized = true;
    }

    /**
     * @param array<int, array<string, mixed>> $blocks
     * @param array<string, mixed>             $args
     */
    public static function send(string $to, string $subject, array $blocks, array $args = []): bool
    {
        self::init();

        $to = trim($to);
        if ($to === '' || !is_email($to)) {
            return false;
        }

        $subject = trim($subject);
        if ($subject === '') {
            return false;
        }

        $body = self::render($blocks, $args);
        if ($body === '') {
            return false;
        }

        $headers = $args['headers'] ?? [];
        if (!is_array($headers)) {
            $headers = [$headers];
        }

        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers   = array_map(
            static fn($header) => sanitize_text_field((string) $header),
            $headers
        );
        $headers   = array_values(array_filter($headers));

        add_filter('wp_mail_content_type', [self::class, 'force_html_content_type']);

        try {
            return wp_mail($to, wp_specialchars_decode($subject, \ENT_QUOTES), $body, $headers);
        } finally {
            remove_filter('wp_mail_content_type', [self::class, 'force_html_content_type']);
        }
    }

    public static function force_html_content_type(string $content_type): string
    {
        return 'text/html; charset=UTF-8';
    }

    /**
     * @param array<int, array<string, mixed>> $blocks
     * @param array<string, mixed>             $args
     */
    private static function render(array $blocks, array $args): string
    {
        if (!$blocks) {
            return '';
        }

        $headline  = isset($args['headline']) ? (string) $args['headline'] : '';
        $preheader = isset($args['preheader']) ? (string) $args['preheader'] : '';
        $footer    = [];
        if (isset($args['footer']) && is_array($args['footer'])) {
            foreach ($args['footer'] as $line) {
                $line = trim((string) $line);
                if ($line !== '') {
                    $footer[] = $line;
                }
            }
        }

        $logo_url        = self::get_logo_url();
        $footer_logo_url = self::get_footer_logo_url();
        $site_name       = wp_specialchars_decode(get_bloginfo('name'), \ENT_QUOTES);
        $footer_lines    = [];
        $footer_heading  = '';

        $default_footer       = self::default_footer_lines();
        $default_footer_first = '';

        if ($default_footer) {
            $default_footer_first = (string) array_shift($default_footer);
        }

        if ($footer) {
            $footer_lines   = $footer;
            $footer_heading = trim((string) array_shift($footer_lines));

            if ($footer_heading === '') {
                /* translators: %s: site name */
                $footer_heading = $default_footer_first !== ''
                    ? $default_footer_first
                    : sprintf(__('Um recado do time %s', 'juntaplay'), $site_name);
            }

            $footer_lines = array_values(array_filter($footer_lines));

            foreach ($default_footer as $default_line) {
                if (!in_array($default_line, $footer_lines, true)) {
                    $footer_lines[] = $default_line;
                }
            }
        } elseif ($default_footer_first !== '') {
            $footer_heading = $default_footer_first;
            $footer_lines   = $default_footer;
        }

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <title><?php echo esc_html($headline !== '' ? $headline : $site_name); ?></title>
    </head>
    <body style="margin:0;padding:0;background:#ffffff;">
        <?php if ($preheader !== '') : ?>
            <div style="display:none;max-height:0;overflow:hidden;font-size:1px;line-height:1px;color:#ffffff;">
                <?php echo esc_html($preheader); ?>
            </div>
        <?php endif; ?>
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f4f6fb;padding:40px 16px;">
            <tr>
                <td align="center">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background:#ffffff;border-radius:36px;overflow:hidden;box-shadow:0 28px 64px rgba(15, 23, 42, 0.18);">
                        <tr>
                            <td style="padding:48px 40px 24px;text-align:center;background:#ffffff;">
                                <?php if ($logo_url !== '') : ?>
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" style="display:block;margin:0 auto 24px;width:100%;height:auto;">
                                <?php endif; ?>
                                <div style="margin:0 auto 32px;width:120px;height:8px;background:#00CCC0;border-radius:25px;"></div>
                                <?php if ($headline !== '') : ?>
                                    <h1 style="margin:0;font-size:30px;line-height:1.25;color:#0f172a;font-weight:700;font-family:'Segoe UI',Roboto,'Helvetica Neue',Arial,'Noto Sans',sans-serif;">
                                        <?php echo esc_html($headline); ?>
                                    </h1>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:0 40px 48px;background:#ffffff;font-family:'Segoe UI',Roboto,'Helvetica Neue',Arial,'Noto Sans',sans-serif;">
                                <?php echo self::render_blocks($blocks); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </td>
                        </tr>
                        <?php if ($footer_heading !== '' || $footer_lines) : ?>
                            <?php $footer_pills = self::footer_pills(); ?>
                            <tr>
                                <td style="padding:0;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:linear-gradient(135deg,#0f172a,#1d3a72);">
                                        <tr>
                                            <td style="padding:48px 32px 52px;text-align:center;color:#ffffff;font-family:'Segoe UI',Roboto,'Helvetica Neue',Arial,'Noto Sans',sans-serif;">
                                                <?php if ($footer_logo_url !== '') : ?>
                                                    <div style="margin:0 0 18px;">
                                                        <img src="<?php echo esc_url($footer_logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" style="display:block;margin:0 auto;max-width:60px;width:100%;height:auto;">
                                                    </div>
                                                <?php elseif ($site_name !== '') : ?>
                                                    <p style="margin:0 0 12px;font-size:13px;letter-spacing:0.32em;text-transform:uppercase;color:rgba(255,255,255,0.7);">
                                                        <?php echo esc_html($site_name); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <p style="margin:0 0 22px;font-size:22px;line-height:1.55;font-weight:600;">
                                                    <?php echo esc_html($footer_heading); ?>
                                                </p>
                                                <?php foreach ($footer_lines as $line) : ?>
                                                    <p style="margin:0 0 12px;font-size:15px;line-height:1.75;color:rgba(255,255,255,0.85);">
                                                        <?php echo esc_html($line); ?>
                                                    </p>
                                                <?php endforeach; ?>
                                                <div style="margin-top:34px;display:inline-flex;gap:18px;flex-wrap:wrap;justify-content:center;">
                                                    <?php foreach ($footer_pills as $pill) : ?>
                                                        <span style="display:inline-block;padding:12px 26px;border-radius:999px;background:rgba(255,255,255,0.12);color:#e0fdfa;font-size:12px;font-weight:600;letter-spacing:0.14em;text-transform:uppercase;">
                                                            <?php echo esc_html($pill); ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
        <?php

        $html = ob_get_clean();

        return is_string($html) ? trim($html) : '';
    }

    /**
     * @param array<int, array<string, mixed>> $blocks
     */
    private static function render_blocks(array $blocks): string
    {
        $html = '';

        foreach ($blocks as $block) {
            if (!is_array($block) || empty($block['type'])) {
                continue;
            }

            $type = (string) $block['type'];
            switch ($type) {
                case 'paragraph':
                    $content = trim((string) ($block['content'] ?? ''));
                    if ($content === '') {
                        continue 2;
                    }
                    $html .= sprintf(
                        '<p style="margin:0 0 18px;color:#1f2937;font-size:15px;line-height:1.75;font-family:\'Segoe UI\',Roboto,\'Helvetica Neue\',Arial,\'Noto Sans\',sans-serif;">%s</p>',
                        esc_html($content)
                    );
                    break;

                case 'list':
                    $items = isset($block['items']) && is_array($block['items']) ? array_filter($block['items']) : [];
                    if (!$items) {
                        continue 2;
                    }
                    $html .= '<ul style="margin:0 0 18px;padding-left:22px;color:#1f2937;font-size:15px;line-height:1.75;font-family:\'Segoe UI\',Roboto,\'Helvetica Neue\',Arial,\'Noto Sans\',sans-serif;">';
                    foreach ($items as $item) {
                        $html .= sprintf('<li style="margin:0 0 8px;">%s</li>', esc_html((string) $item));
                    }
                    $html .= '</ul>';
                    break;

                case 'button':
                    $label = trim((string) ($block['label'] ?? ''));
                    $url   = trim((string) ($block['url'] ?? ''));
                    if ($label === '' || $url === '') {
                        continue 2;
                    }
                    $html .= sprintf(
                        '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:28px auto 36px;">'
                        . '<tr><td style="background:linear-gradient(135deg,#0f172a,#1d3a72);border-radius:999px;"><a href="%s" style="display:inline-block;padding:15px 36px;color:#ffffff;font-weight:600;text-decoration:none;font-size:15px;font-family:\'Segoe UI\',Roboto,\'Helvetica Neue\',Arial,\'Noto Sans\',sans-serif;">%s</a></td></tr></table>',
                        esc_url($url),
                        esc_html($label)
                    );
                    break;

                case 'code':
                    $content = trim((string) ($block['content'] ?? ''));
                    if ($content === '') {
                        continue 2;
                    }
                    $html .= sprintf(
                        '<p style="margin:0 0 22px;text-align:center;"><span style="display:inline-block;padding:14px 28px;background:#0f172a;color:#ffffff;font-size:22px;letter-spacing:0.32em;border-radius:18px;font-weight:700;font-family:\'Segoe UI\',Roboto,\'Helvetica Neue\',Arial,\'Noto Sans\',sans-serif;">%s</span></p>',
                        esc_html($content)
                    );
                    break;

                case 'html':
                    $content = trim((string) ($block['content'] ?? ''));
                    if ($content === '') {
                        continue 2;
                    }
                    $html .= wp_kses_post($content);
                    break;

                default:
                    $content = trim((string) ($block['content'] ?? ''));
                    if ($content === '') {
                        continue 2;
                    }
                    $html .= sprintf(
                        '<p style="margin:0 0 18px;color:#1f2937;font-size:15px;line-height:1.75;font-family:\'Segoe UI\',Roboto,\'Helvetica Neue\',Arial,\'Noto Sans\',sans-serif;">%s</p>',
                        esc_html($content)
                    );
                    break;
            }
        }

        return $html;
    }

    private static function get_logo_url(): string
    {
        if (!defined('JP_URL')) {
            return '';
        }

        $url = trailingslashit(JP_URL) . 'assets/images/juntaplay-email.png';

        /** @var string $filtered */
        $filtered = apply_filters('juntaplay/email/logo_url', $url);

        return trim($filtered);
    }

    private static function get_footer_logo_url(): string
    {
        if (!defined('JP_URL')) {
            return '';
        }

        $url = trailingslashit(JP_URL) . 'assets/images/juntaplay-icone.png';

        /** @var string $filtered */
        $filtered = apply_filters('juntaplay/email/footer_logo_url', $url);

        return trim($filtered);
    }

    /**
     * @return array<int, string>
     */
    /**
     * @return array<int, string>
     */
    public static function default_footer_lines(): array
    {
        $year = gmdate('Y');

        $lines = [
            __('Gestão inteligente para um futuro compartilhado.', 'juntaplay'),
            __('Construímos pontes entre conhecimento, propósito e prosperidade.', 'juntaplay'),
            __('Aqui, cada ideia conecta pessoas, e cada comunidade impulsiona resultados.', 'juntaplay'),
            __('Se precisar de suporte, fale com nossa equipe pelo e-mail suporte@juntaplay.com.br', 'juntaplay'),
            sprintf(__('© %s JuntaPlay. Todos os Direitos Reservados.', 'juntaplay'), $year),
        ];

        /** @var array<int, string> $filtered */
        $filtered = apply_filters('juntaplay/email/footer_lines', $lines);

        $normalized = [];

        foreach ($filtered as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $normalized[] = $line;
            }
        }

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    public static function footer_pills(): array
    {
        $pills = [
            __('Compartilhar', 'juntaplay'),
            __('Construir', 'juntaplay'),
            __('Crescer', 'juntaplay'),
        ];

        /** @var array<int, string> $filtered */
        $filtered = apply_filters('juntaplay/email/footer_pills', $pills);

        $normalized = [];

        foreach ($filtered as $pill) {
            $pill = trim((string) $pill);
            if ($pill !== '') {
                $normalized[] = $pill;
            }
        }

        return $normalized;
    }
}

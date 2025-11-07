<?php
/**
 * HTML email helper for JuntaPlay notifications.
 */

declare(strict_types=1);

namespace JuntaPlay\Notifications;

use function add_action;
use function add_filter;
use function base64_decode;
use function apply_filters;
use function date_i18n;
use function esc_attr;
use function esc_html;
use function esc_url;
use function file_put_contents;
use function file_exists;
use function get_transient;
use function is_dir;
use function is_writable;
use function is_wp_error;
use function plugins_url;
use function set_transient;
use function sprintf;
use function wp_mkdir_p;
use function wp_http_validate_url;
use function wp_mail;
use function wp_remote_get;
use function wp_remote_retrieve_body;
use function wp_remote_retrieve_header;
use function wp_remote_retrieve_response_code;
use function wp_kses_post;
use function __;

class EmailHelper
{
    private const BRAND_PRIMARY   = '#042940';
    private const BRAND_SECONDARY = '#00CCC0';
    private const LOCAL_LOGO_FILE = 'assets/images/juntaplay.png';
    private const LOCAL_LOGO_BASE64 =
        'iVBORw0KGgoAAAANSUhEUgAAAZAAAABQCAYAAAA3ICPMAAAVMElEQVR4nO3dZ0BUxxYH8P+yC7t0' .
        'BARUFKzYNYJdCBYURYwFNGosscVIxDwTjTEaNXkvzxI1CphiolFRY0HsXbFg7BhLAhZABBQQUaTD' .
        'Lvs++FBEBO7cu/38vqk7M0eY3bMzc2dGBC0haeGt1HQMhBCiK+Sxp0SajkFjAVDCIIQQ4Wgioai1' .
        'QUoahBCieupKJipvhJIGIYRojiqTicoqpsRBCCHaQxWJRPAKKXEQQoj2EjKRGAlVEUDJgxBCtJ2Q' .
        'n9OCZCJKHIQQonv4jkZ4j0AoeRBCiG7i+/nNK4FQ8iCEEN3G53OcafhCiYMQQvQP1yktziMQSh6E' .
        'EKKfuH6+c0oglDwIIUS/cfmcr/FwhZIHIURVMi/uh7WlOedyJy/EoN+Ez1QQEanJdJag+0AIIYQY' .
        'jholEBp9EEKIYanJ575EiEoIIcLp16MT9v+yRC1tyRUKFBWXoLCoGFnPniP9SRYeZjzB7YQHiI2/' .
        'j0s345CUmqaWWIj2kbTwVlY1lVVlAqHkQYh+k4jFkJiKYW4qg52NFZq6Or/xmuS0DJz48yp2HI7C' .
        'yfMxkCsUGoiUaEpVSaTaEQgxPGsWzsTk4f5MZet7DUNaZpbAERFNqu/kgPFD+2P80P5Iy8zCms2R' .
        '+PmPPcjKztF0aETD3roGQqMPQkhFTva2+GbGRNw+ugXBYwMgEYs1HRJRg7flg0oTCCUPQkhVbCwt' .
        'sHxOEM5uDUXD+nU0HQ5Rg8ryAj3GSwhh5tG6OS7vXAuvju00HQrRgDcSCI0+CCFcWFua48AvS+HT' .
        '3UPToRAVq5gfaARCCOFNJjXBth8WoXWzRpoOhajRawmERh+EEFaW5mbY/sMiyKQmmg6FqFD5PEEj' .
        'EEKIYJq6OuProPGaDoOoCSUQQoigpo8Zhjq17TQdBlGDlxsJafqKEP3wbdgGfBP2e5WvMZZIYGtt' .
        'CUd7W3Ru1xJ9e3SEf8/uEIv5f6eUSU3wr/HDMXvZj7zrItqpbHc6jUAIMUAlcjnSnzzFjdvxWLt9' .
        'HwKDv4ab72gcib4kSP2jBvkIkoyIdqPfMCEEAJCUmgb/j+Zg5frtvOtytKsFTw/aG6LvJABNX2lS' .
        '4wb14OfdFR6tm6N104ZwrG0LawtzGBmJkF9QhMdZT5GYkoZr/9zB2Ss3cPLCVRSXyDUdtlar7+SA' .
        'lk1cUb+uA1zqOqFBXUe41HWErbUVzGRSmJnKYGYqg6lUCrlCgeKSF6fRPs3OQebTbKRlZiH+QSru' .
        'JqXgetw93IiLR4ncMH7mSqUSX3z/E5q4OsO/ZzdedXl5tMOpi9cEikwzqC+9naSFt5LTYYrBYwOw' .
        'fE4QU2Pvf7oQEUdPM5Utw3pr2ZnL19F73KdqbbOqm9KMJRKMGNALwWMD8E7Lpm+tw9pSAmtLczRx' .
        'cYZPdw/MnjwKz3JysTHyMFau346U9Mec4ypzP2oH6jnaM5d/m+QzEcxlvT8IxrmYm5zK2FpbwtOj' .
        'Hdxbu6FDq2bo0LIZatva1Li8iZEEJsYSWJiZwr6WdaWn0RYUFuHC9X+w53g09hw/y+vnrguUSiU+' .
        'XxyG/l6deZ111aV9KwGjUj1d60snN66Cp0dbzuVS0h+jSZ/3oVCUMrddhk7jVTOf7h5Y9dWMSjtX' .
        'TdhYWiB4bAAmDffHwpB1+OH3HVAqDWcAKRGL0aV9K/h094BP945wb+UGI6Ma38zMxFQmRc/O76Bn' .
        '53ewfE4Q9kWdw4r123D+2t8qbVeTEpIf4sjZS/Dz7spcR1OXegJGJDxd70uh4RFMCcTZsTb69eiE' .
        'g6cvcC5bEa2BqInUxBhrFs7EwbXLmJNHeWYyKZbO+hg7Q76FmUwqQIS6YdQgH0RtWoW5U8egY5vm' .
        'Kn/DVyQWG2FwH0+c2RyKLSsW6PXjqifOX+VV3rmOg1YvpOt6X9pzIhoPHmUwtT2J8bqGioxo/UP1' .
        'HO1q4XR4CPMdG1UZ1Ks7doX9B1ITY8HrJlUL9PXGlchf0buru6ZDUYm4hCRe5SViMSzMTAWKRr+x' .
        '9CWFohQ/bd3N1N4Ary6o68D/y4/avh4oYZh5qr6TA6I2rYZ7azeVtdG7qztWfPmJyuonb+dga4O9' .
        'Py3mveCsjR5nZfOuw9xUJkAkhoGlL/22Yz8KCos4tyUWG2HckP6cy1WkveNLPVDb1gbHN6wUZMqq' .
        'OlNGDEK/Hp1U3g55k4mxBH/8sEilXxI0QYgpHQNanhME176UlZ2DrfuPM7U1McAPIhG/37H6RiAG' .
        '1pPMZFLs+fG/aFS/rtraXDl3ulbPOeszE2MJtq1cqFdTNva1rHnXkVdQIEAkhoVrXwoJ38XUjks9' .
        'J/Tpxm/6VY0JRF0taYcu7VuhY5vmam2zqaszhvp4qbVN8opLPSd8MWW0psMQTIvGrrzKyxUK5BUU' .
        'ChOMgeHSl27dScDpS38xtTMxcCBTuTI0AtEzH73/nqZDMGifjgsU5Ju7NuD7cEDywwxB9hoYKi59' .
        'KZRxFDKoV3c4cNjrUpHa9oFQAlEPT492qFPbDo8eP9F0KBqRnZOHv+8m4uadeNy8k4jbiQ/w7Hku' .
        'nufmIScvH9k5uVAqAVOZCUylUtRxsIdLXUe0btYIPdzbwKtje5gYs78tZFITTAjww9K1WwT8X6lf' .
        'U1dn9O3ekVcdd5NSBIpGM3SpL+09GY2k1DS41HPi1IaxRIKxQ3zx/W9/MMVICUTNktMysO3ASeyL' .
        'OofkhxnIyHoK+1rWaN7IBaMH9cXIgb157f41MhKhd1d3hO89KmDU2u1eUgp2H49G5LEzuHwzrkZ9' .
        'rSRXjue5+Uh/8hR/xd7FnhPRAF48+DBt1BDMnjyK+c3/waC+Op1AjIxEWD7nE97raReu695GS13t' .
        'S6WlSqzZshtLZk3l3MbEAD8sX7eN6TNabQmk1MATiEJRihXrt2FR6HoUFZe89m+p6ZlITc/EifNX' .
        '8fMfe7B15QLUd3JgbsvTo221CcS1Z+Bb/23NwpnMe1bqew1DWmYWU1kuSuRybN57DKs27sStOwmC' .
        '1fs46xkWha7HwdPncez3lUyPobZo7AJX5zq4n/JIsLjURSQS4fsvgtDfqzPvus5cvi5ARKqnL31p' .
        '3c4DWDD9Q84bi5u4OOPdTu2Zzi1T2xpIaanhzoWWlioxZta3mLvilzeSR0UXr/+D9z7+EvkMz3aX' .
        'adu8MXNZbVdYWIQft+xG836jMXneUkHf8OVdvhmHzxaHMZd/t2N74YJRE1fnOtj/8xJMHzOMd11p' .
        'mVmIvnpDgKhUR9/60rOcXGxmnHmYGODHVE6NCcRwRyCfLwnDjsOnavz6m7cT8P1vW5nbc2vYgLms' .
        'ttt+KArB/17FfIQDF5v3HsXz3Hymsh2qOCRTG0jEYtS2tUEbt0aYGDgQO1Z/g7hD4ejbg9+6R5kt' .
        '+45p/QK6PvalkE1sh5kO8fGCnY0VpzIyqYk6p7C0uzOpypVbcUxPSPxx4ATz3dKW5mawNDdDTh5b' .
        'hyUvFBYV415SCjq0asa5bKumDVUQUc3MDxqH+UHjNNZ+YVExVm3YobH2tZG6+lJsfBJOXohBry4d' .
        'OLUhNTHGB+/14/R78/XsTPtAVO3zxWuYFqfu3k/B0+c5zO062NViLkteyXzKdpyHs1NtgSPRHSGb' .
        'IvAwwzCfAqyKuvpSKOMoZFIgt2msAF9vSOSxp0TqOFDRENdA7iQmc77forz4B6nwaM22GVGfdkRz' .
        'ZWdjhbZujdHGrTEaOtdBg7qOcLK3ha21JWysLCGTmsDE2BjGEjHvoxzepq6D8Het6IJ7SSnV3seu' .
        'S3SxLx04fR6JyY/QsH4dTuWaN3JBtw6t8WfMrWpfayqTws+7Kz3Gq0pbD7CdUVMm48kz5rIyqQmv' .
        'tnWJSCSCp0dbDPHxQq8uHdCyiaumQ4KpTAqx2Ejr1wGElJOXj8AZC1BYVKzpUJjpQ18qLVVizdZI' .
        'LJs9jXNbkwL9a5RA+nt1hoWZqRoTiLoa0iLRV9lHHwDwPDePuSyfvSS6wtLcDJOH++OTMUN5Pfas' .
        'KqZSKXLzDeMsqMKiYrz/r4Uqe5JJ1fStL62POIiF0ydwfnQ4oN+7mPldCJ7l5Fb9Ot+eAOgoE5W6' .
        'HnuPV/k8A/nwYfHBoL6IPRyOJbOmauUbHoDB3NGSnZMHvymzcTT6sqZDYaKPfSk7Jw/he45wbsdU' .
        'JsVI/z5VvsZMJsWAd7sA+H8CkceeUu9VXAYgLTOL1yI4ABSXyAWKRn+YyqTYsPQrrF/8JRy1/EEB' .
        'Vc2Ja5Ort26jY8Bkndk0WJ6+96WQ8F1MX9wnVXPAop9315cjGzr7W0We8UwewIsdsuQVmdQEkWH/' .
        'waiBVX9DIqqXnZOHWUvXwHPUJ0hM1r0d94bQl24nPMDxP7lfS9zWrXGVJ4mXTV8BakwghvBtrDzW' .
        'TUPlGdICbE2ELZipt9fH6or0J0+xYPU6NOs7Ej/8vkNnv+QYSl8KDWd7pPdtRxmZm8rgW+6Ym5cJ' .
        'RNXTWEJUbm6mO9djCrF4aqibLyszuI8nxg7up+kwDFJK+mNsiDwE/4/moGHP4fjup03IyuY/wtYU' .
        'Q+pLh85cRPyDVM7lhg/oBUtzszf+fmDPbq+dtaW2p7AkEn5PBZnJpAbxZFF5hnz8S3lisRH++9kU' .
        'TYehlxSKUhSVlKCwqBhPs58jLTMLqemZuJOYjNj4+7h8K04np6jextD6klKpRNjmSKz48hNO5cxN' .
        'ZRjh1wu/bt//2t8H+Hq/9me1JRCpCb99CTZWFgJFQnSNr2dnNHHhd6+8QlGKqIsxOHj6Am7cjkd8' .
        'Uiqe5+UhN7+gykR94Jelgp0PpS7fhm3Qq818QjLEvrQh8hAWBU+odERRlcmB/q8lEAszU/h6vn5K' .
        '82sJRJW70mU8Ewjf6zWJ7hrJc6Ez6uI1BH+7CnEJSZzLGtranb4zxL70PDcfm3YfwbTRQziV69Cq' .
        'Gdq3aIq/Yu8CAPx7dXtjg7LaFtHNeR6t0a55E4EiIbqmJ8eD4crbcyIaAybNYnrDA0Ataxr56hND' .
        '7Uuhm1kf6X11Plb5p6/KvJFAqlpM5/NUEN8pqG4dWvMqT3RTfScH5jubc/LyMWXeUsgVCub2a9tq' .
        '9/4AUnOG3Jfu3k/B0XPcN3qOHOgDM5kUluZmlV5xzGkNpKiE/YybOrVtmcvaWFq8MfdGVEebTg1o' .
        '7FKPuez+qPO8nhZytKuFBnW0c2cy4c7Q+1Lopl3o16MTpzJWFmYI7N8Lcrm80vP1Kp3CetsohM/R' .
        'Gs14XHI00r+PwRwLoQ3kcvZvWaam3K7TrI69jTVz2dj4+7za7tujI62B6BFD70tHoi/hTmIy53KT' .
        'Av0Q2P/N6SugijWQypIIn9NhO7dryfQDtLG0wPxpmrscxxBVd+1uVeo5CHsPhhmPvT98/h8AEDw2' .
        'gFd5ol0MvS8plUqEbYnkXK5L+1bw8+5a6Y5RTovoKemPOTdexsHWhmnn57IvpqE247wlYZPN4xRg' .
        '787thQsEQCmPdTfXek7MZd/36432LbT7WlrCDfUlYGPkYdZTMipd7qgygVQchSQ8eMjr6IKQ+TNQ' .
        'y8qyxq9fFDwB44f2Z26PsEl/ksVc9uNRQ+DsKNwohM+R9gPe7QKxmPuDhm6NGmDNwpnM7RLtRH3p' .
        'xQkZGyIPCVZftT+R8kmkRC5HXPwD5saauDhj38+Lqz0y2b6WNTYunYe5U8cwt0XY3U9JYy7rYGuD' .
        'K5G/4uug8ejh3hZO9ra8LrdKepjOXNalnhPnqYNWTRvi4NplnDddEe1HfemF0PBdgp1yUaOnsMpv' .
        'MDx75TrauDVibrBzu5aI2fMb1u08gJ2HTyEx5RFy8wvgaG+LZq7OGOLjhQBfb04jFSIsvguGdjZW' .
        'mB80DvODarZ2lVdQCBv3ykeatxMfoLhEDhNjtkMTFn82FSUlcoRtjqzy6TKx2AgfDhuAJZ9/DCsL' .
        '7XrDE2FQX3ohIfkhDp+9+PJODz44/ySPRF/ivKOxIhtLC8z8cARmfjiCVz1ENVLTM5GclqEVl+sU' .
        'FhXj0o1/0MO9LVN5IyMRVs6djnFDfLEu4iDOXb2JpIdpyMsvhJ2NFRrUdUQ/z04Y6dcHzRrWFzh6' .
        'ok2oL70SGh6h3gRSNgo5du4KsrJzYGtNIwR9FnU+BmOH+Go6DADAzsOnmd/0Zdq3aIrV82YIFBHR' .
        'VdSXXjh27griEpLQvJELr3o4rQrJY0+JSuRyrI84wKtRov22H4rSdAgvbd57FDl5/O9XIYT60ith' .
        'm7k/0lsR58cK5LGnRKs37kR+YRHvxrnYEHkI2TnsT1EQbo79eZlp05EqPMvJxaoNO9Xe7u2EB/gz' .
        '5pba2yWqQ33plU27j+BZTi6vOpgOU3xwOkJkJpN+xatlDo6du4KpXy9XV3MEL+4imb/qV02H8dKS' .
        'tZvVmtBy8wsQEPy1IBeDEe1CfemFvIJC/B7B75FePqfxLgUQzav1Gjh27goCps/jdYgZYbPr6Bls' .
        '3X9C02EAeLEAOvzTBWqZfigsKsbQoK+YT10l2o360iv/35nOvMOSOYGIRCI5gEAAd1nrqM6Ow6cw' .
        'eNqXap8uI69Mmb8Uh85c1HQYAIC/7yZi8LS5yCsoVFkbeQWFGDZ9HqIuXlNZG0TzqC+9cO/YVhGA' .
        '/dW+8C143QciEonSAPQEIOhPSK5QYPayHzFq5iIUl7DvfCf8FRYVY0jQXCwKXa8Vv4szl6+j19gZ' .
        'SE3PFLzu+ymP4DkqCEejuR97TXSPIfcleewpUblN4qtZ6+F9oZRIJEoF0B3AKgC855nOxdxEp4Ap' .
        'WLl+O9+qiEAUilL8e81GNO83Gqs37kRG1jONxhPz9x10GDwBm/cdE+ToeYWiFCGbIuA+dBJu3k4Q' .
        'IEKiKwyxL1U8okokEp0A8DdLXYLciS4SiQoAfKpUKtcCmA1gOIAaH32pVCoRdfEaVm3YgUNnLmrV' .
        'fRTkleS0DHy2OAyzl/2Ijm1aoId7G7Ru1ghNXZzhaG8LOxsrSE2MYSwRpFtVKSs7B+O/+A4hmyIw' .
        '88MRGOLjybndnLx8hO85ipDwCNy9n6KiSIm2M5S+VNVlgQBCAPzEtU6VHFCvVCotAPgC8Dx96a9g' .
        '13pOsKtlDVOpFCVyOZ7n5SPlUQZu3U3E+b9u4eCp83iY8UQVoRADYWNpAV+vzujeoQ3auDWCSz0n' .
        '2FpbQWZigqLiYuQVFOJx1jPcuZ+Mf+7dR9SFGJyLuakV03JEu+hbX6omcQAAlEqlGYAUAJyuTVTr' .
        'DSdl52kRQghRnZokjYqUSuUvACZzKaOxK7IomRBCiHBYkkZ5SqXyKoAOXMr8D/EFwaEiu8+TAAAA' .
        'AElFTkSuQmCC';

    public static function init(): void
    {
        add_action('init', [__CLASS__, 'maybe_generate_local_logo'], 5);
        add_filter('wp_mail_from_name', [__CLASS__, 'force_from_name']);
    }

    public static function maybe_generate_local_logo(): void
    {
        $logo_dir  = JP_DIR . 'assets/images/';
        $logo_path = JP_DIR . self::LOCAL_LOGO_FILE;

        if (!is_dir($logo_dir)) {
            wp_mkdir_p($logo_dir);
        }

        if (file_exists($logo_path)) {
            return;
        }

        if (!is_dir($logo_dir) || !is_writable($logo_dir)) {
            return;
        }

        $binary = base64_decode(self::LOCAL_LOGO_BASE64, true);

        if ($binary === false) {
            return;
        }

        file_put_contents($logo_path, $binary);
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
            ? '<h1 style="margin:0 0 18px;font-family:\'Fredoka\', \'Figtree\', \'Segoe UI\', sans-serif;font-size:26px;line-height:1.2;font-weight:600;color:#0F172A;text-align:left;">' . esc_html($headline) . '</h1>'
            : '';

        $body_html   = self::render_blocks($blocks);
        $footer_html = self::render_footer($footer_lines);

        $logo_html = '<img src="' . esc_url($logo) . '" alt="' . esc_attr($brand) . '" style="max-width:180px;height:auto;display:inline-block;" />';

        return '<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>' . esc_html($title) . '</title>
</head>
<body style="margin:0;padding:0;background-color:#ffffff;font-family:\'Figtree\', \'Segoe UI\', sans-serif;color:#0f172a;">
' . $preheader_html . '
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#ffffff;margin:0;padding:0;width:100%;background-image:radial-gradient(circle at 20% 20%,rgba(0,204,192,0.08),transparent 55%),radial-gradient(circle at 80% 0%,rgba(4,41,64,0.08),transparent 45%);">
    <tr>
        <td align="center" style="padding:40px 16px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:680px;background:rgba(255,255,255,0.82);border-radius:32px;overflow:hidden;border:1px solid rgba(148,163,184,0.18);box-shadow:0 30px 60px rgba(15,23,42,0.12);backdrop-filter:blur(18px);">
                <tr>
                    <td style="padding:40px 40px 16px;text-align:center;background:rgba(255,255,255,0.75);">
                        <div style="display:inline-block;padding:18px 32px;border-radius:24px;background:rgba(4,41,64,0.08);box-shadow:inset 0 1px 0 rgba(255,255,255,0.45);">
                            ' . $logo_html . '
                        </div>
                        <div style="margin:24px auto 0;width:160px;height:8px;border-radius:25px;background-color:' . self::BRAND_SECONDARY . ';box-shadow:0 8px 20px rgba(0,204,192,0.35);"></div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:36px 40px 12px;background-color:rgba(255,255,255,0.92);">' . $headline_html . $body_html . '</td>
                </tr>
                <tr>
                    <td style="padding:28px 40px;background-color:rgba(15,23,42,0.04);text-align:center;">' . $footer_html . '</td>
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
                        $html .= '<h2 style="margin:0 0 16px;font-family:\'Fredoka\', \'Figtree\', \'Segoe UI\', sans-serif;font-size:22px;line-height:1.3;font-weight:600;color:#0F172A;">' . esc_html($content) . '</h2>';
                    }
                    break;
                case 'paragraph':
                    $content = isset($block['content']) ? (string) $block['content'] : '';
                    $html   .= self::paragraph($content);
                    break;
                case 'list':
                    $items = isset($block['items']) && is_array($block['items']) ? $block['items'] : [];
                    if ($items) {
                        $html .= '<ul style="margin:0 0 16px;padding-left:20px;color:#0F172A;font-size:15px;line-height:1.6;">';
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
                            . '<a href="' . esc_url($url) . '" style="display:inline-block;padding:14px 32px;border-radius:999px;font-family:\'Fredoka\', \'Figtree\', sans-serif;font-weight:600;font-size:15px;color:#ffffff;background:linear-gradient(135deg,' . self::BRAND_PRIMARY . ',' . self::BRAND_SECONDARY . ');box-shadow:0 12px 30px rgba(4,41,64,0.25);text-decoration:none;">'
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

            $html .= '<p style="margin:6px 0;font-size:13px;line-height:1.6;color:rgba(15,23,42,0.72);">' . esc_html((string) $line) . '</p>';
        }

        return $html;
    }

    private static function paragraph(string $content): string
    {
        if ($content === '') {
            return '';
        }

        return '<p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#0F172A;">' . esc_html($content) . '</p>';
    }

    private static function get_logo_url(): string
    {
        self::maybe_generate_local_logo();

        $default_url = 'https://www.agenciadigitalsaopaulo.com.br/juntaplay/wp-content/uploads/2025/10/logo.svg';
        $custom_url  = apply_filters('juntaplay/email/logo_url', $default_url);

        if (is_string($custom_url) && $custom_url !== '') {
            return self::maybe_inline_logo($custom_url);
        }

        $asset_png = JP_DIR . self::LOCAL_LOGO_FILE;
        if (file_exists($asset_png)) {
            return plugins_url(self::LOCAL_LOGO_FILE, JP_FILE);
        }

        $asset_svg = JP_DIR . 'assets/images/Juntaplay.svg';

        if (file_exists($asset_svg)) {
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

    private static function maybe_inline_logo(string $url): string
    {
        $validated = wp_http_validate_url($url);

        if (!$validated) {
            return $url;
        }

        $cache_key = 'juntaplay_email_logo_' . md5($validated);
        $cached    = get_transient($cache_key);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = wp_remote_get($validated, [
            'timeout' => 5,
            'headers' => [
                'Accept' => 'image/svg+xml,image/png,image/*;q=0.8,*/*;q=0.5',
            ],
        ]);

        if (is_wp_error($response)) {
            set_transient($cache_key, $validated, HOUR_IN_SECONDS);

            return $validated;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = (string) wp_remote_retrieve_body($response);

        if ($code >= 200 && $code < 300 && $body !== '') {
            $content_type = wp_remote_retrieve_header($response, 'content-type');
            $content_type = is_string($content_type) && $content_type !== ''
                ? strtolower($content_type)
                : (stripos($validated, '.svg') !== false ? 'image/svg+xml' : 'image/png');

            $data_uri = 'data:' . $content_type . ';base64,' . base64_encode($body);
            set_transient($cache_key, $data_uri, DAY_IN_SECONDS);

            return $data_uri;
        }

        set_transient($cache_key, $validated, HOUR_IN_SECONDS);

        return $validated;
    }
}

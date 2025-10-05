<?php
declare(strict_types=1);

namespace JuntaPlay\Setup;

use JuntaPlay\Data\GroupMembers;
use JuntaPlay\Data\Groups;
use WP_Error;
use WP_User;

use function __;
use function current_time;
use function file_exists;
use function file_get_contents;
use function esc_url_raw;
use function get_option;
use function get_current_user_id;
use function get_user_by;
use function get_post;
use function is_wp_error;
use function update_option;
use function update_user_meta;
use function wp_insert_user;
use function wp_check_filetype;
use function wp_insert_attachment;
use function wp_generate_attachment_metadata;
use function wp_update_attachment_metadata;
use function wp_upload_bits;

defined('ABSPATH') || exit;

class DemoSeeder
{
    private const DEMO_PASSWORD = 'JuntaPlay#2024';

    /**
     * Populates demo users and groups for exploratory testing.
     *
     * @return array<string, mixed>|WP_Error
     */
    public function seed(): array|WP_Error
    {
        if (!function_exists('wp_insert_user')) {
            return new WP_Error('missing_wp', __('Funções de usuário do WordPress indisponíveis.', 'juntaplay'));
        }

        $summary = [
            'users'         => [],
            'groups'        => [],
            'created_at'    => current_time('mysql'),
            'demo_password' => self::DEMO_PASSWORD,
        ];

        $users    = $this->get_users();
        $user_ids = [];

        foreach ($users as $user) {
            $login    = $user['login'];
            $existing = get_user_by('login', $login);

            if ($existing instanceof WP_User) {
                $user_ids[$login] = (int) $existing->ID;
                $summary['users'][] = [
                    'login'  => $login,
                    'status' => 'existing',
                    'id'     => (int) $existing->ID,
                ];
                continue;
            }

            $payload = [
                'user_login'   => $login,
                'user_email'   => $user['email'],
                'user_pass'    => self::DEMO_PASSWORD,
                'display_name' => $user['display_name'],
                'first_name'   => $user['first_name'],
                'last_name'    => $user['last_name'],
                'role'         => $user['role'],
                'description'  => $user['bio'],
            ];

            $user_id = wp_insert_user($payload);
            if (is_wp_error($user_id)) {
                $summary['users'][] = [
                    'login'  => $login,
                    'status' => 'error',
                    'error'  => $user_id->get_error_message(),
                ];
                continue;
            }

            $user_id = (int) $user_id;
            $user_ids[$login] = $user_id;
            $summary['users'][] = [
                'login'  => $login,
                'status' => 'created',
                'id'     => $user_id,
            ];

            if ($user['avatar'] !== '') {
                update_user_meta($user_id, 'juntaplay_avatar_url', esc_url_raw($user['avatar']));
            }
        }

        $super_admin_id = $this->resolve_super_admin_id($user_ids);
        if ($super_admin_id <= 0) {
            return new WP_Error('no_admin', __('Nenhum usuário administrador disponível para vincular aos grupos de exemplo.', 'juntaplay'));
        }

        $groups = $this->get_groups();
        $default_cover_id = $this->ensure_demo_cover_attachment();

        foreach ($groups as $group) {
            $slug = $group['slug'];
            if ($slug !== '' && Groups::slug_exists($slug)) {
                $summary['groups'][] = [
                    'title'  => $group['title'],
                    'status' => 'skipped',
                    'reason' => 'exists',
                ];
                continue;
            }

            $owner_id = $group['owner_login'] === 'super_admin'
                ? $super_admin_id
                : ($user_ids[$group['owner_login']] ?? 0);

            if ($owner_id <= 0) {
                $summary['groups'][] = [
                    'title'  => $group['title'],
                    'status' => 'skipped',
                    'reason' => 'owner_missing',
                ];
                continue;
            }

            $group_id = Groups::create([
                'title'             => $group['title'],
                'owner_id'          => $owner_id,
                'service_name'      => $group['service_name'],
                'service_url'       => $group['service_url'],
                'description'       => $group['description'],
                'rules'             => $group['rules'],
                'price_regular'     => $group['price_regular'],
                'price_promotional' => $group['price_promotional'],
                'member_price'      => $group['member_price'],
                'slots_total'       => $group['slots_total'],
                'slots_reserved'    => $group['slots_reserved'],
                'support_channel'   => $group['support_channel'],
                'delivery_time'     => $group['delivery_time'],
                'access_method'     => $group['access_method'],
                'category'          => $group['category'],
                'instant_access'    => $group['instant_access'],
                'slug'              => $slug,
                'cover_id'          => isset($group['cover_id']) && (int) $group['cover_id'] > 0
                    ? (int) $group['cover_id']
                    : $default_cover_id,
            ]);

            if (!$group_id) {
                $summary['groups'][] = [
                    'title'  => $group['title'],
                    'status' => 'error',
                    'reason' => 'create_failed',
                ];
                continue;
            }

            foreach ($group['members'] as $member) {
                $member_id = $member['login'] === 'super_admin'
                    ? $super_admin_id
                    : ($user_ids[$member['login']] ?? 0);

                if ($member_id <= 0) {
                    continue;
                }

                GroupMembers::add($group_id, $member_id, $member['role'], 'active');
            }

            if ($group['status'] !== Groups::STATUS_PENDING) {
                Groups::update_status($group_id, $group['status'], [
                    'reviewed_by' => $super_admin_id,
                ]);
            }

            $summary['groups'][] = [
                'title'  => $group['title'],
                'status' => 'created',
                'id'     => $group_id,
            ];
        }

        return $summary;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function get_users(): array
    {
        return [
            [
                'login'        => 'demo.superadmin',
                'email'        => 'demo.superadmin@example.com',
                'display_name' => 'Equipe JuntaPlay',
                'first_name'   => 'Equipe',
                'last_name'    => 'JuntaPlay',
                'role'         => 'administrator',
                'bio'          => __('Conta administrativa de demonstração para aprovação de grupos.', 'juntaplay'),
                'avatar'       => 'https://i.pravatar.cc/300?img=12',
            ],
            [
                'login'        => 'ana.streaming',
                'email'        => 'ana.streaming@example.com',
                'display_name' => 'Ana Streaming',
                'first_name'   => 'Ana',
                'last_name'    => 'Streaming',
                'role'         => 'subscriber',
                'bio'          => __('Especialista em planos de streaming e vídeos on-line.', 'juntaplay'),
                'avatar'       => 'https://i.pravatar.cc/300?img=47',
            ],
            [
                'login'        => 'bruno.sound',
                'email'        => 'bruno.sound@example.com',
                'display_name' => 'Bruno Sound',
                'first_name'   => 'Bruno',
                'last_name'    => 'Sound',
                'role'         => 'subscriber',
                'bio'          => __('Curador de playlists e experiências de áudio em alta fidelidade.', 'juntaplay'),
                'avatar'       => 'https://i.pravatar.cc/300?img=15',
            ],
            [
                'login'        => 'carla.series',
                'email'        => 'carla.series@example.com',
                'display_name' => 'Carla Séries',
                'first_name'   => 'Carla',
                'last_name'    => 'Séries',
                'role'         => 'subscriber',
                'bio'          => __('Apaixonada por cinema independente e estreias semanais.', 'juntaplay'),
                'avatar'       => 'https://i.pravatar.cc/300?img=32',
            ],
            [
                'login'        => 'davi.cursos',
                'email'        => 'davi.cursos@example.com',
                'display_name' => 'Davi Cursos',
                'first_name'   => 'Davi',
                'last_name'    => 'Cursos',
                'role'         => 'subscriber',
                'bio'          => __('Professor que compartilha planos de estudo e ferramentas acadêmicas.', 'juntaplay'),
                'avatar'       => 'https://i.pravatar.cc/300?img=38',
            ],
            [
                'login'        => 'edu.livros',
                'email'        => 'edu.livros@example.com',
                'display_name' => 'Edu Livros',
                'first_name'   => 'Edu',
                'last_name'    => 'Livros',
                'role'         => 'subscriber',
                'bio'          => __('Colecionador de revistas e audiobooks digitais.', 'juntaplay'),
                'avatar'       => 'https://i.pravatar.cc/300?img=54',
            ],
            [
                'login'        => 'fernanda.office',
                'email'        => 'fernanda.office@example.com',
                'display_name' => 'Fernanda Office',
                'first_name'   => 'Fernanda',
                'last_name'    => 'Office',
                'role'         => 'subscriber',
                'bio'          => __('Designer que administra ferramentas de produtividade para equipes remotas.', 'juntaplay'),
                'avatar'       => 'https://i.pravatar.cc/300?img=68',
            ],
            [
                'login'        => 'gustavo.games',
                'email'        => 'gustavo.games@example.com',
                'display_name' => 'Gustavo Games',
                'first_name'   => 'Gustavo',
                'last_name'    => 'Games',
                'role'         => 'subscriber',
                'bio'          => __('Streamer esportivo que coordena ligas e campeonatos.', 'juntaplay'),
                'avatar'       => 'https://i.pravatar.cc/300?img=23',
            ],
            [
                'login'        => 'helena.segura',
                'email'        => 'helena.segura@example.com',
                'display_name' => 'Helena Segura',
                'first_name'   => 'Helena',
                'last_name'    => 'Segura',
                'role'         => 'subscriber',
                'bio'          => __('Analista de cibersegurança focada em privacidade digital.', 'juntaplay'),
                'avatar'       => 'https://i.pravatar.cc/300?img=9',
            ],
            [
                'login'        => 'igor.ai',
                'email'        => 'igor.ai@example.com',
                'display_name' => 'Igor AI',
                'first_name'   => 'Igor',
                'last_name'    => 'AI',
                'role'         => 'subscriber',
                'bio'          => __('Pesquisador de inteligência artificial e automações.', 'juntaplay'),
                'avatar'       => 'https://i.pravatar.cc/300?img=5',
            ],
            [
                'login'        => 'juliana.boloes',
                'email'        => 'juliana.boloes@example.com',
                'display_name' => 'Juliana Bolões',
                'first_name'   => 'Juliana',
                'last_name'    => 'Bolões',
                'role'         => 'subscriber',
                'bio'          => __('Organizadora de bolões semanais e rifas solidárias.', 'juntaplay'),
                'avatar'       => 'https://i.pravatar.cc/300?img=61',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function get_groups(): array
    {
        return [
            [
                'title'            => 'YouTube Premium Família',
                'slug'             => 'youtube-premium-familia',
                'service_name'     => 'YouTube Premium',
                'service_url'      => 'https://www.youtube.com/premium',
                'description'      => __('Divida música e vídeos sem anúncios com até 6 perfis no mesmo plano.', 'juntaplay'),
                'rules'            => __('Não alterar senha nem idioma. Perfis identificados pelo primeiro nome.', 'juntaplay'),
                'price_regular'    => 34.9,
                'price_promotional'=> 22.9,
                'member_price'     => 12.9,
                'slots_total'      => 6,
                'slots_reserved'   => 4,
                'support_channel'  => 'WhatsApp +55 11 90000-1000',
                'delivery_time'    => __('Envio imediato após confirmação do pagamento.', 'juntaplay'),
                'access_method'    => __('Convite família via e-mail cadastrado.', 'juntaplay'),
                'category'         => 'video',
                'instant_access'   => true,
                'status'           => Groups::STATUS_APPROVED,
                'owner_login'      => 'ana.streaming',
                'members'          => [
                    ['login' => 'ana.streaming', 'role' => 'owner'],
                    ['login' => 'bruno.sound', 'role' => 'manager'],
                    ['login' => 'carla.series', 'role' => 'member'],
                    ['login' => 'davi.cursos', 'role' => 'member'],
                ],
            ],
            [
                'title'            => 'MUBI Cinemateca',
                'slug'             => 'mubi-cinemateca',
                'service_name'     => 'Mubi',
                'service_url'      => 'https://mubi.com',
                'description'      => __('Seleção rotativa de filmes independentes para cinéfilos exigentes.', 'juntaplay'),
                'rules'            => __('Não compartilhar fora do grupo. Acesso individual com e-mail.', 'juntaplay'),
                'price_regular'    => 27.9,
                'price_promotional'=> 19.9,
                'member_price'     => 11.9,
                'slots_total'      => 5,
                'slots_reserved'   => 3,
                'support_channel'  => 'Telegram @cineclubemubi',
                'delivery_time'    => __('Confirmação em até 12 horas úteis.', 'juntaplay'),
                'access_method'    => __('Convite por e-mail e senha compartilhada.', 'juntaplay'),
                'category'         => 'video',
                'instant_access'   => false,
                'status'           => Groups::STATUS_APPROVED,
                'owner_login'      => 'carla.series',
                'members'          => [
                    ['login' => 'carla.series', 'role' => 'owner'],
                    ['login' => 'ana.streaming', 'role' => 'member'],
                    ['login' => 'gustavo.games', 'role' => 'member'],
                ],
            ],
            [
                'title'            => 'NBA League Pass Squad',
                'slug'             => 'nba-league-pass-squad',
                'service_name'     => 'NBA League Pass',
                'service_url'      => 'https://www.nba.com/watch/league-pass',
                'description'      => __('Acompanhe todos os jogos da temporada regular e playoffs.', 'juntaplay'),
                'rules'            => __('Não compartilhar streams públicos. Cada membro usa perfil dedicado.', 'juntaplay'),
                'price_regular'    => 149.9,
                'price_promotional'=> 119.9,
                'member_price'     => 39.9,
                'slots_total'      => 4,
                'slots_reserved'   => 2,
                'support_channel'  => 'Discord.gg/nba-coop',
                'delivery_time'    => __('Liberação em até 6 horas.', 'juntaplay'),
                'access_method'    => __('Perfis individuais no aplicativo oficial.', 'juntaplay'),
                'category'         => 'games',
                'instant_access'   => true,
                'status'           => Groups::STATUS_APPROVED,
                'owner_login'      => 'gustavo.games',
                'members'          => [
                    ['login' => 'gustavo.games', 'role' => 'owner'],
                    ['login' => 'ana.streaming', 'role' => 'member'],
                    ['login' => 'juliana.boloes', 'role' => 'member'],
                ],
            ],
            [
                'title'            => 'PlayPlus Família',
                'slug'             => 'playplus-familia',
                'service_name'     => 'PlayPlus',
                'service_url'      => 'https://www.playplus.com',
                'description'      => __('Séries nacionais, jornalismo e esportes ao vivo da Record TV.', 'juntaplay'),
                'rules'            => __('Troca de senha apenas pela administração. Sem downloads simultâneos.', 'juntaplay'),
                'price_regular'    => 32.9,
                'price_promotional'=> 21.9,
                'member_price'     => 10.9,
                'slots_total'      => 4,
                'slots_reserved'   => 4,
                'support_channel'  => 'E-mail suporte@juntaplay.example',
                'delivery_time'    => __('Acesso liberado automaticamente após a aprovação.', 'juntaplay'),
                'access_method'    => __('Perfis compartilhados e login único.', 'juntaplay'),
                'category'         => 'video',
                'instant_access'   => true,
                'status'           => Groups::STATUS_APPROVED,
                'owner_login'      => 'super_admin',
                'members'          => [
                    ['login' => 'super_admin', 'role' => 'owner'],
                    ['login' => 'ana.streaming', 'role' => 'manager'],
                    ['login' => 'carla.series', 'role' => 'member'],
                ],
            ],
            [
                'title'            => 'Spotify Premium Família',
                'slug'             => 'spotify-premium-familia',
                'service_name'     => 'Spotify Premium',
                'service_url'      => 'https://www.spotify.com/br/family',
                'description'      => __('Plano família com mix semanal exclusivo e podcasts originais.', 'juntaplay'),
                'rules'            => __('Endereço cadastrado único. Perfis com nome e avatar personalizado.', 'juntaplay'),
                'price_regular'    => 34.9,
                'price_promotional'=> 24.9,
                'member_price'     => 9.9,
                'slots_total'      => 6,
                'slots_reserved'   => 5,
                'support_channel'  => 'WhatsApp +55 21 95555-0000',
                'delivery_time'    => __('Convite enviado em até 2 horas.', 'juntaplay'),
                'access_method'    => __('Convite família com endereço compartilhado.', 'juntaplay'),
                'category'         => 'music',
                'instant_access'   => true,
                'status'           => Groups::STATUS_APPROVED,
                'owner_login'      => 'bruno.sound',
                'members'          => [
                    ['login' => 'bruno.sound', 'role' => 'owner'],
                    ['login' => 'ana.streaming', 'role' => 'member'],
                    ['login' => 'davi.cursos', 'role' => 'member'],
                    ['login' => 'helena.segura', 'role' => 'member'],
                ],
            ],
            [
                'title'            => 'Tidal HiFi Max Collective',
                'slug'             => 'tidal-hifi-max-collective',
                'service_name'     => 'Tidal',
                'service_url'      => 'https://tidal.com',
                'description'      => __('Áudio HiFi sem perdas e catálogo Dolby Atmos compartilhado.', 'juntaplay'),
                'rules'            => __('Sem mudanças de senha. Perfis identificados com iniciais.', 'juntaplay'),
                'price_regular'    => 39.9,
                'price_promotional'=> 29.9,
                'member_price'     => 14.9,
                'slots_total'      => 5,
                'slots_reserved'   => 3,
                'support_channel'  => 'Grupo Telegram @hifimax',
                'delivery_time'    => __('Liberação em até 8 horas.', 'juntaplay'),
                'access_method'    => __('Perfis compartilhados no app.', 'juntaplay'),
                'category'         => 'music',
                'instant_access'   => false,
                'status'           => Groups::STATUS_PENDING,
                'owner_login'      => 'bruno.sound',
                'members'          => [
                    ['login' => 'bruno.sound', 'role' => 'owner'],
                    ['login' => 'ana.streaming', 'role' => 'member'],
                    ['login' => 'igor.ai', 'role' => 'member'],
                ],
            ],
            [
                'title'            => 'Brainly Premium Squad',
                'slug'             => 'brainly-premium-squad',
                'service_name'     => 'Brainly Premium',
                'service_url'      => 'https://brainly.com.br',
                'description'      => __('Respostas verificadas e monitorias em tempo real para vestibulares.', 'juntaplay'),
                'rules'            => __('Acesso individual, não compartilhar prints públicos.', 'juntaplay'),
                'price_regular'    => 29.9,
                'price_promotional'=> 21.9,
                'member_price'     => 12.9,
                'slots_total'      => 5,
                'slots_reserved'   => 4,
                'support_channel'  => 'Discord.gg/brainlysquad',
                'delivery_time'    => __('Ativação em até 4 horas.', 'juntaplay'),
                'access_method'    => __('Convite por e-mail com usuário dedicado.', 'juntaplay'),
                'category'         => 'education',
                'instant_access'   => true,
                'status'           => Groups::STATUS_APPROVED,
                'owner_login'      => 'davi.cursos',
                'members'          => [
                    ['login' => 'davi.cursos', 'role' => 'owner'],
                    ['login' => 'ana.streaming', 'role' => 'member'],
                    ['login' => 'igor.ai', 'role' => 'member'],
                    ['login' => 'juliana.boloes', 'role' => 'member'],
                ],
            ],
            [
                'title'            => 'Ubook Audiobooks Club',
                'slug'             => 'ubook-audiobooks-club',
                'service_name'     => 'Ubook',
                'service_url'      => 'https://www.ubook.com',
                'description'      => __('Audiobooks e podcasts originais para ouvir offline.', 'juntaplay'),
                'rules'            => __('Download liberado para uso pessoal. Não compartilhar arquivos ripados.', 'juntaplay'),
                'price_regular'    => 19.9,
                'price_promotional'=> 14.9,
                'member_price'     => 8.9,
                'slots_total'      => 4,
                'slots_reserved'   => 2,
                'support_channel'  => 'E-mail clube@ubookfans.example',
                'delivery_time'    => __('Ativação em até 24 horas.', 'juntaplay'),
                'access_method'    => __('Usuário individual com senha única.', 'juntaplay'),
                'category'         => 'reading',
                'instant_access'   => false,
                'status'           => Groups::STATUS_APPROVED,
                'owner_login'      => 'edu.livros',
                'members'          => [
                    ['login' => 'edu.livros', 'role' => 'owner'],
                    ['login' => 'davi.cursos', 'role' => 'member'],
                    ['login' => 'helena.segura', 'role' => 'member'],
                ],
            ],
            [
                'title'            => 'Super Interessante Digital',
                'slug'             => 'super-interessante-digital',
                'service_name'     => 'Super Interessante',
                'service_url'      => 'https://super.abril.com.br',
                'description'      => __('Revista mensal com acervo completo e reportagens especiais.', 'juntaplay'),
                'rules'            => __('Uso individual. Não compartilhar PDFs fora do grupo.', 'juntaplay'),
                'price_regular'    => 16.9,
                'price_promotional'=> 12.9,
                'member_price'     => 6.9,
                'slots_total'      => 5,
                'slots_reserved'   => 5,
                'support_channel'  => 'WhatsApp +55 31 94444-0020',
                'delivery_time'    => __('Envio em até 3 horas.', 'juntaplay'),
                'access_method'    => __('Login individual com senha rotativa.', 'juntaplay'),
                'category'         => 'reading',
                'instant_access'   => true,
                'status'           => Groups::STATUS_APPROVED,
                'owner_login'      => 'edu.livros',
                'members'          => [
                    ['login' => 'edu.livros', 'role' => 'owner'],
                    ['login' => 'ana.streaming', 'role' => 'member'],
                    ['login' => 'juliana.boloes', 'role' => 'member'],
                ],
            ],
            [
                'title'            => 'Veja Saúde Coletivo',
                'slug'             => 'veja-saude-coletivo',
                'service_name'     => 'Veja Saúde',
                'service_url'      => 'https://saude.abril.com.br',
                'description'      => __('Conteúdos exclusivos de saúde, bem-estar e alimentação balanceada.', 'juntaplay'),
                'rules'            => __('Acesso somente pessoal. Não divulgar login.', 'juntaplay'),
                'price_regular'    => 14.9,
                'price_promotional'=> 9.9,
                'member_price'     => 6.5,
                'slots_total'      => 5,
                'slots_reserved'   => 3,
                'support_channel'  => 'Telegram @revistasaude',
                'delivery_time'    => __('Liberação automática após pagamento.', 'juntaplay'),
                'access_method'    => __('Login compartilhado com autenticação em duas etapas.', 'juntaplay'),
                'category'         => 'reading',
                'instant_access'   => true,
                'status'           => Groups::STATUS_PENDING,
                'owner_login'      => 'edu.livros',
                'members'          => [
                    ['login' => 'edu.livros', 'role' => 'owner'],
                    ['login' => 'helena.segura', 'role' => 'member'],
                    ['login' => 'juliana.boloes', 'role' => 'member'],
                ],
            ],
            [
                'title'            => 'Perplexity Pro Research Hub',
                'slug'             => 'perplexity-pro-research-hub',
                'service_name'     => 'Perplexity Pro',
                'service_url'      => 'https://www.perplexity.ai',
                'description'      => __('Pesquisa avançada com IA generativa e histórico compartilhado.', 'juntaplay'),
                'rules'            => __('Não compartilhar histórico sensível. Limite de 5 prompts por hora por membro.', 'juntaplay'),
                'price_regular'    => 99.9,
                'price_promotional'=> 79.9,
                'member_price'     => 39.9,
                'slots_total'      => 4,
                'slots_reserved'   => 2,
                'support_channel'  => 'Slack #ia-coop',
                'delivery_time'    => __('Ativação manual em até 12 horas.', 'juntaplay'),
                'access_method'    => __('Convite por e-mail corporativo.', 'juntaplay'),
                'category'         => 'ai',
                'instant_access'   => false,
                'status'           => Groups::STATUS_APPROVED,
                'owner_login'      => 'igor.ai',
                'members'          => [
                    ['login' => 'igor.ai', 'role' => 'owner'],
                    ['login' => 'davi.cursos', 'role' => 'member'],
                    ['login' => 'helena.segura', 'role' => 'member'],
                ],
            ],
            [
                'title'            => 'Canva Pro Studios',
                'slug'             => 'canva-pro-studios',
                'service_name'     => 'Canva Pro',
                'service_url'      => 'https://www.canva.com',
                'description'      => __('Ferramentas premium de design com bibliotecas compartilhadas.', 'juntaplay'),
                'rules'            => __('Não remover marcas do time. Organizar pastas por projeto.', 'juntaplay'),
                'price_regular'    => 42.9,
                'price_promotional'=> 31.9,
                'member_price'     => 15.9,
                'slots_total'      => 5,
                'slots_reserved'   => 4,
                'support_channel'  => 'Slack #design-freelas',
                'delivery_time'    => __('Convite enviado automaticamente.', 'juntaplay'),
                'access_method'    => __('Convite por e-mail com domínio verificado.', 'juntaplay'),
                'category'         => 'office',
                'instant_access'   => true,
                'status'           => Groups::STATUS_APPROVED,
                'owner_login'      => 'fernanda.office',
                'members'          => [
                    ['login' => 'fernanda.office', 'role' => 'owner'],
                    ['login' => 'ana.streaming', 'role' => 'member'],
                    ['login' => 'igor.ai', 'role' => 'member'],
                    ['login' => 'helena.segura', 'role' => 'member'],
                ],
            ],
            [
                'title'            => 'Google One 2TB Compartilhado',
                'slug'             => 'google-one-2tb-compartilhado',
                'service_name'     => 'Google One',
                'service_url'      => 'https://one.google.com',
                'description'      => __('2 TB de armazenamento e VPN Google para a família.', 'juntaplay'),
                'rules'            => __('Não excluir arquivos de outros membros. Gerenciar pastas pelo Drive.', 'juntaplay'),
                'price_regular'    => 34.9,
                'price_promotional'=> 24.9,
                'member_price'     => 11.9,
                'slots_total'      => 5,
                'slots_reserved'   => 5,
                'support_channel'  => 'WhatsApp +55 27 93333-0990',
                'delivery_time'    => __('Convite em até 1 hora.', 'juntaplay'),
                'access_method'    => __('Família Google com compartilhamento imediato.', 'juntaplay'),
                'category'         => 'office',
                'instant_access'   => true,
                'status'           => Groups::STATUS_APPROVED,
                'owner_login'      => 'fernanda.office',
                'members'          => [
                    ['login' => 'fernanda.office', 'role' => 'owner'],
                    ['login' => 'bruno.sound', 'role' => 'member'],
                    ['login' => 'helena.segura', 'role' => 'member'],
                    ['login' => 'juliana.boloes', 'role' => 'member'],
                ],
            ],
            [
                'title'            => 'ExpressVPN Global Access',
                'slug'             => 'expressvpn-global-access',
                'service_name'     => 'ExpressVPN',
                'service_url'      => 'https://www.expressvpn.com',
                'description'      => __('Rede privada virtual com servidores em 94 países.', 'juntaplay'),
                'rules'            => __('Não compartilhar credenciais fora do grupo. Respeitar limites de dispositivos.', 'juntaplay'),
                'price_regular'    => 55.9,
                'price_promotional'=> 42.9,
                'member_price'     => 19.9,
                'slots_total'      => 5,
                'slots_reserved'   => 3,
                'support_channel'  => 'Signal +55 41 98888-1010',
                'delivery_time'    => __('Entrega manual em até 6 horas.', 'juntaplay'),
                'access_method'    => __('Credenciais compartilhadas com OTP rotativo.', 'juntaplay'),
                'category'         => 'security',
                'instant_access'   => false,
                'status'           => Groups::STATUS_APPROVED,
                'owner_login'      => 'helena.segura',
                'members'          => [
                    ['login' => 'helena.segura', 'role' => 'owner'],
                    ['login' => 'igor.ai', 'role' => 'member'],
                    ['login' => 'bruno.sound', 'role' => 'member'],
                ],
            ],
            [
                'title'            => 'Bolão Mega da Virada 2024',
                'slug'             => 'bolao-mega-da-virada-2024',
                'service_name'     => 'Bolão Mega-Sena',
                'service_url'      => 'https://loterias.caixa.gov.br',
                'description'      => __('Cotas digitais para o maior concurso do ano com conferência automática.', 'juntaplay'),
                'rules'            => __('Pagamento antecipado. Resultado divulgado em live exclusiva.', 'juntaplay'),
                'price_regular'    => 25.0,
                'price_promotional'=> 20.0,
                'member_price'     => 20.0,
                'slots_total'      => 50,
                'slots_reserved'   => 28,
                'support_channel'  => 'Grupo WhatsApp +55 62 97777-5050',
                'delivery_time'    => __('Confirmação instantânea com recibo PDF.', 'juntaplay'),
                'access_method'    => __('Cotas digitais com comprovante individual.', 'juntaplay'),
                'category'         => 'boloes',
                'instant_access'   => true,
                'status'           => Groups::STATUS_APPROVED,
                'owner_login'      => 'juliana.boloes',
                'members'          => [
                    ['login' => 'juliana.boloes', 'role' => 'owner'],
                    ['login' => 'ana.streaming', 'role' => 'member'],
                    ['login' => 'gustavo.games', 'role' => 'member'],
                    ['login' => 'helena.segura', 'role' => 'member'],
                ],
            ],
        ];
    }

    private function ensure_demo_cover_attachment(): int
    {
        $cached = (int) get_option('juntaplay_demo_cover_id', 0);
        if ($cached > 0 && get_post($cached)) {
            return $cached;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $contents = $this->generate_placeholder_png();
        if ($contents === '') {
            return 0;
        }

        $upload = wp_upload_bits('juntaplay-group-cover-demo.png', null, $contents);
        if (!empty($upload['error'])) {
            return 0;
        }

        $filetype = wp_check_filetype($upload['file'], null);
        $attachment = [
            'post_mime_type' => $filetype['type'] ?? 'image/png',
            'post_title'     => __('Capa demo JuntaPlay', 'juntaplay'),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];

        $attachment_id = wp_insert_attachment($attachment, $upload['file']);
        if (is_wp_error($attachment_id)) {
            return 0;
        }

        $attachment_id = (int) $attachment_id;
        $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        if (!is_wp_error($metadata) && !empty($metadata)) {
            wp_update_attachment_metadata($attachment_id, $metadata);
        }

        update_option('juntaplay_demo_cover_id', $attachment_id);

        return $attachment_id;
    }

    private function generate_placeholder_png(): string
    {
        if (!function_exists('imagecreatetruecolor')) {
            return '';
        }

        $width  = 495;
        $height = 370;

        $image = imagecreatetruecolor($width, $height);
        if (!$image) {
            return '';
        }

        $start = [0x5b, 0x6c, 0xff];
        $end   = [0x8e, 0x54, 0xe9];

        for ($y = 0; $y < $height; $y++) {
            $ratio = $height > 1 ? $y / ($height - 1) : 0;
            $r     = (int) round($start[0] + ($end[0] - $start[0]) * $ratio);
            $g     = (int) round($start[1] + ($end[1] - $start[1]) * $ratio);
            $b     = (int) round($start[2] + ($end[2] - $start[2]) * $ratio);
            $color = imagecolorallocate($image, $r, $g, $b);
            imagefilledrectangle($image, 0, $y, $width, $y, $color);
        }

        $overlay = imagecolorallocatealpha($image, 255, 255, 255, 80);
        imagefilledrectangle($image, 28, 28, $width - 28, 120, $overlay);

        $text_color = imagecolorallocate($image, 255, 255, 255);
        imagestring($image, 5, (int) (($width / 2) - 70), (int) ($height / 2) - 10, 'JuntaPlay', $text_color);
        imagestring($image, 3, (int) (($width / 2) - 60), (int) ($height / 2) + 20, 'Demo Cover', $text_color);

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    /**
     * @param array<string, int> $user_ids
     */
    private function resolve_super_admin_id(array $user_ids): int
    {
        $current = get_current_user_id();
        if ($current > 0) {
            return $current;
        }

        if (isset($user_ids['demo.superadmin'])) {
            return $user_ids['demo.superadmin'];
        }

        $admin = get_user_by('login', 'admin');
        if ($admin instanceof WP_User) {
            return (int) $admin->ID;
        }

        return 0;
    }
}

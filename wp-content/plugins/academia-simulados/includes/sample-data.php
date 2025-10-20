<?php
/**
 * Sample data for the plugin.
 *
 * @package Academia_Simulados
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Academia_Simulados_Sample_Data {
    /**
     * Seed sample simulados on activation.
     */
    public static function seed() {
        $existing = get_posts(
            array(
                'post_type'      => 'simulado',
                'post_status'    => 'any',
                'posts_per_page' => 1,
                'fields'         => 'ids',
            )
        );

        if ( ! empty( $existing ) ) {
            return;
        }

        $simulados = array(
            array(
                'post_title'   => __( 'Simulado de Português: Interpretação de Texto', 'academia-simulados' ),
                'post_content' => __( 'Avalie sua habilidade de interpretar textos identificando ideias principais e recursos linguísticos.', 'academia-simulados' ),
                'questions'    => array(
                    array(
                        'text'    => __( 'No trecho "A leitura abre portas para mundos desconhecidos", a expressão "abre portas" significa:', 'academia-simulados' ),
                        'answers' => array(
                            __( 'A leitura força as pessoas a viajar.', 'academia-simulados' ),
                            __( 'A leitura cria oportunidades e novas possibilidades.', 'academia-simulados' ),
                            __( 'A leitura limita o conhecimento a um único tema.', 'academia-simulados' ),
                            __( 'A leitura impede que conheçamos novas culturas.', 'academia-simulados' ),
                        ),
                        'correct' => 1,
                        'hint'    => __( 'A expressão "abrir portas" é frequentemente utilizada de forma figurada para indicar a criação de oportunidades.', 'academia-simulados' ),
                    ),
                    array(
                        'text'    => __( 'Em um texto dissertativo, a tese está associada:', 'academia-simulados' ),
                        'answers' => array(
                            __( 'Ao conjunto de citações de outros autores.', 'academia-simulados' ),
                            __( 'À opinião principal defendida ao longo do texto.', 'academia-simulados' ),
                            __( 'À enumeração de argumentos contrários.', 'academia-simulados' ),
                            __( 'Aos dados estatísticos apresentados.', 'academia-simulados' ),
                        ),
                        'correct' => 1,
                        'hint'    => __( 'A tese apresenta a ideia central que será defendida pelo autor.', 'academia-simulados' ),
                    ),
                    array(
                        'text'    => __( 'No período "Quando o livro chegou, todos queriam lê-lo", a oração subordinada adverbial introduzida por "quando" indica:', 'academia-simulados' ),
                        'answers' => array(
                            __( 'Condição.', 'academia-simulados' ),
                            __( 'Causa.', 'academia-simulados' ),
                            __( 'Tempo.', 'academia-simulados' ),
                            __( 'Concessão.', 'academia-simulados' ),
                        ),
                        'correct' => 2,
                        'hint'    => __( 'O conectivo "quando" introduz uma circunstância temporal.', 'academia-simulados' ),
                    ),
                ),
            ),
            array(
                'post_title'   => __( 'Simulado de Português: Gramática e Uso', 'academia-simulados' ),
                'post_content' => __( 'Questões para revisar gramática normativa, classes de palavras e concordância.', 'academia-simulados' ),
                'questions'    => array(
                    array(
                        'text'    => __( 'Assinale a alternativa em que há erro de concordância nominal.', 'academia-simulados' ),
                        'answers' => array(
                            __( 'Ela estava meio preocupada com a prova.', 'academia-simulados' ),
                            __( 'Eles ficaram alertas durante toda a noite.', 'academia-simulados' ),
                            __( 'Os relatório financeiro foi entregue ontem.', 'academia-simulados' ),
                            __( 'As crianças permaneceram quietas.', 'academia-simulados' ),
                        ),
                        'correct' => 2,
                        'hint'    => __( 'O correto seria "O relatório financeiro" para concordar com o núcleo no singular.', 'academia-simulados' ),
                    ),
                    array(
                        'text'    => __( 'Assinale a frase em que o uso da crase está correto.', 'academia-simulados' ),
                        'answers' => array(
                            __( 'Fui a pé à escola.', 'academia-simulados' ),
                            __( 'Voltarei à casa dos meus pais.', 'academia-simulados' ),
                            __( 'Ofereci o presente à ele.', 'academia-simulados' ),
                            __( 'Assisti à o espetáculo todo.', 'academia-simulados' ),
                        ),
                        'correct' => 1,
                        'hint'    => __( 'O verbo voltar exige preposição "a" e a palavra "casa" admite o artigo feminino, permitindo a crase.', 'academia-simulados' ),
                    ),
                    array(
                        'text'    => __( 'A forma verbal que completa corretamente a frase "Se ele ___, o projeto estará salvo" é:', 'academia-simulados' ),
                        'answers' => array(
                            __( 'intervêm', 'academia-simulados' ),
                            __( 'intervém', 'academia-simulados' ),
                            __( 'intervem', 'academia-simulados' ),
                            __( 'intervimos', 'academia-simulados' ),
                        ),
                        'correct' => 1,
                        'hint'    => __( 'O sujeito é "ele", portanto a forma correta é "intervém".', 'academia-simulados' ),
                    ),
                ),
            ),
            array(
                'post_title'   => __( 'Simulado de Português: Figuras de Linguagem', 'academia-simulados' ),
                'post_content' => __( 'Teste seus conhecimentos sobre metáforas, metonímias e outras figuras de linguagem.', 'academia-simulados' ),
                'questions'    => array(
                    array(
                        'text'    => __( 'Na frase "A cidade acordou em festa", identifica-se a figura de linguagem:', 'academia-simulados' ),
                        'answers' => array(
                            __( 'Metáfora.', 'academia-simulados' ),
                            __( 'Antítese.', 'academia-simulados' ),
                            __( 'Ironia.', 'academia-simulados' ),
                            __( 'Hipérbole.', 'academia-simulados' ),
                        ),
                        'correct' => 0,
                        'hint'    => __( 'Atribui-se à cidade uma ação humana, sugerindo comparação implícita.', 'academia-simulados' ),
                    ),
                    array(
                        'text'    => __( 'Quando dizemos "vou ler Machado de Assis", ocorre:', 'academia-simulados' ),
                        'answers' => array(
                            __( 'Metonímia, pois substituímos a obra pelo autor.', 'academia-simulados' ),
                            __( 'Prosopopeia, por atribuir vida a seres inanimados.', 'academia-simulados' ),
                            __( 'Pleonasmo, por repetirmos a mesma ideia.', 'academia-simulados' ),
                            __( 'Eufemismo, por suavizar uma expressão.', 'academia-simulados' ),
                        ),
                        'correct' => 0,
                        'hint'    => __( 'É comum usar o nome do autor para referir-se à sua obra, recurso típico da metonímia.', 'academia-simulados' ),
                    ),
                    array(
                        'text'    => __( 'O verso "vou-me embora pra Pasárgada" é marcado por qual figura de linguagem?', 'academia-simulados' ),
                        'answers' => array(
                            __( 'Eufemismo.', 'academia-simulados' ),
                            __( 'Hipérbole.', 'academia-simulados' ),
                            __( 'Aliteração.', 'academia-simulados' ),
                            __( 'Personificação.', 'academia-simulados' ),
                        ),
                        'correct' => 1,
                        'hint'    => __( 'A frase expressa exagero ao idealizar um local utópico como solução para os problemas.', 'academia-simulados' ),
                    ),
                ),
            ),
        );

        foreach ( $simulados as $simulado ) {
            $post_id = wp_insert_post(
                array(
                    'post_type'    => 'simulado',
                    'post_title'   => $simulado['post_title'],
                    'post_content' => $simulado['post_content'],
                    'post_status'  => 'publish',
                )
            );

            if ( ! is_wp_error( $post_id ) ) {
                update_post_meta( $post_id, Academia_Simulados_Meta_Box::META_KEY, $simulado['questions'] );
            }
        }
    }
}

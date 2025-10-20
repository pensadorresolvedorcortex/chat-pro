( function( wp ) {
    const { registerBlockType } = wp.blocks;
    const { TextControl, SelectControl, PanelBody } = wp.components;
    const { InspectorControls, useBlockProps } = wp.blockEditor;

    registerBlockType( 'questoes/mapa-organograma', {
        title: 'Questões – Mapa/Organograma',
        description: 'Exibe o mapa mental e o organograma configurados.',
        icon: 'analytics',
        category: 'widgets',
        supports: {
            html: false,
        },
        attributes: {
            mode: {
                type: 'string',
                default: 'ambos',
            },
            title: {
                type: 'string',
                default: '',
            },
            data: {
                type: 'object',
            },
        },
        edit: ( props ) => {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps( { className: 'questoes-block-preview' } );

            return (
                wp.element.createElement(
                    'div',
                    blockProps,
                    wp.element.createElement(
                        InspectorControls,
                        null,
                        wp.element.createElement(
                            PanelBody,
                            { title: 'Configurações' },
                            wp.element.createElement( SelectControl, {
                                label: 'Modo',
                                value: attributes.mode,
                                options: [
                                    { label: 'Ambos', value: 'ambos' },
                                    { label: 'Mapa Mental', value: 'mapa' },
                                    { label: 'Organograma', value: 'organograma' },
                                ],
                                onChange: ( value ) => setAttributes( { mode: value } ),
                            } ),
                            wp.element.createElement( TextControl, {
                                label: 'Título',
                                value: attributes.title,
                                onChange: ( value ) => setAttributes( { title: value } ),
                            } ),
                            wp.element.createElement( TextControl, {
                                label: 'Dados JSON (opcional)',
                                help: 'Cole JSON válido para pré-visualizar.',
                                value: attributes.data ? JSON.stringify( attributes.data ) : '',
                                onChange: ( value ) => {
                                    try {
                                        const parsed = value ? JSON.parse( value ) : null;
                                        setAttributes( { data: parsed } );
                                    } catch ( error ) {
                                        // Ignore parse errors for live typing.
                                    }
                                },
                            } )
                        )
                    ),
                    wp.element.createElement( 'div', { className: 'questoes-block-card' },
                        wp.element.createElement( 'h3', null, attributes.title || 'Questões — Academia da Comunicação' ),
                        wp.element.createElement( 'p', null, 'Pré-visualização leve. A renderização completa aparece no front-end.' )
                    )
                )
            );
        },
        save: () => null,
    } );
} )( window.wp );

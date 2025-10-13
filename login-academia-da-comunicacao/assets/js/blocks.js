( function( blocks, element, i18n ) {
    const { registerBlockType } = blocks;
    const { createElement: el } = element;
    const { __ } = i18n;

    const definitions = ( window.ADCLoginBlocks && window.ADCLoginBlocks.blocks ) || {};

    const createPreview = ( settings ) => {
        const text = settings.previewText || settings.title || '';
        return el(
            'div',
            {
                className: 'adc-block-preview',
                role: 'presentation',
            },
            text
        );
    };

    Object.keys( definitions ).forEach( ( slug ) => {
        const settings = definitions[ slug ];
        registerBlockType( `adc/${ slug }`, {
            title: settings.title || slug,
            description: settings.description || '',
            icon: settings.icon || 'admin-users',
            category: settings.category || 'widgets',
            keywords: settings.keywords || [ __( 'academia', 'login-academia-da-comunicacao' ) ],
            supports: {
                html: false,
            },
            edit: () => createPreview( settings ),
            save: () => null,
            example: {
                attributes: {},
                innerBlocks: [],
            },
        } );
    } );
} )( window.wp.blocks, window.wp.element, window.wp.i18n );

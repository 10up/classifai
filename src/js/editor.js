/*global wp */
const { dispatch } = wp.data;
dispatch( 'core/notices' ).createInfoNotice( 'Test' );

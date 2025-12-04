(function($){
'use strict';

if ( typeof WCPixLiveStatus === 'undefined' ) {
return;
}

var orderId   = WCPixLiveStatus.order_id;
var orderKey  = WCPixLiveStatus.order_key;
var endpoint  = WCPixLiveStatus.endpoint;
var interval  = parseInt( WCPixLiveStatus.polling_interval, 10 ) * 1000;
var redirect  = WCPixLiveStatus.redirect_url;
var timer     = null;

function checkStatus() {
if ( ! orderId || ! endpoint ) {
return;
}

$.get( endpoint, { order_id: orderId, order_key: orderKey } )
.done( function( response ) {
if ( ! response || ! response.status ) {
return;
}

if ( response.status === 'processing' || response.status === 'completed' ) {
clearInterval( timer );
window.location.href = response.redirect || redirect;
}
} )
.fail( function() {
// silently fail to avoid disturbing checkout UX.
} );
}

$( document ).ready( function() {
checkStatus();
timer = setInterval( checkStatus, interval );

$( window ).on( 'beforeunload', function() {
if ( timer ) {
clearInterval( timer );
}
} );
} );
})( jQuery );

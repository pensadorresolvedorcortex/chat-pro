(function () {
	var iframe = document.getElementById('udb-admin-page-iframe');
	if (!iframe) return;

	// Listen postMessage event from the page inside iframe.
	window.addEventListener('message', function (event) {
		iframe.style.height = (event.data.height) + 'px';

		if (event.data.height >= 500) {
			iframe.style.minHeight = 'auto';
		}
	}, false);
}());
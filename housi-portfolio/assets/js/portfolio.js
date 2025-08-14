document.addEventListener('DOMContentLoaded', function() {
    var filters = document.querySelectorAll('.housi-portfolio-filters button');
    var items = document.querySelectorAll('.housi-portfolio-item');

    filters.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var term = this.getAttribute('data-term');
            filters.forEach(function(b){ b.classList.remove('active'); });
            this.classList.add('active');

            items.forEach(function(item) {
                if (term === 'all' || item.classList.contains('term-' + term)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});

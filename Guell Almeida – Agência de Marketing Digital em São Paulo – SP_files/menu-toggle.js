;(function ($) {
    "use strict";

    jQuery(document).ready(function($) {
      const $menuToggle = $(".menu-toggle");
      $menuToggle.on("click", function() {
        // từ nút menu tìm ra phần tử cha .nav-wrapper
        const $wrapper = $(this).closest(".pxl-header-elementor-main");
        const $wrapper_sticky = $(this).closest(".pxl-header-elementor-sticky");
        // tìm navLinks trong wrapper đó
        const $navLinks = $wrapper.find(".menu-primary-menu-container");
        const $navLinks_sticky = $wrapper_sticky.find(".menu-primary-menu-container");

        // toggle class
        $navLinks.toggleClass("navigation-open");
        $navLinks_sticky.toggleClass("navigation-open");
        $(this).toggleClass("active");
      });
    });
})(jQuery);
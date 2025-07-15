(function ($) {
	var adminMenuWrap = document.querySelector("#adminmenuwrap");
	var heatboxOverlays = document.querySelectorAll(".heatbox-overlay");
	var instantPreviewStyleTags = document.querySelectorAll(
		".udb-instant-preview",
	);

	var brandingCheckbox = document.querySelector(".udb-enable-branding");
	var layoutSelector = document.querySelector('[name="udb_branding[layout]"]');

	var wpAdminDarkmodeCheckbox = document.querySelector(
		"#udb_branding--wp_admin_darkmode",
	);
	var wpAdminDarkModeStyleTag = document.querySelector(
		"style.udb-wp-admin-darkmode-preview",
	);

	var removeWpLogoCheckbox = document.querySelector(".udb-remove-wp-logo");
	var adminBarLogoImageFieldRow = document.querySelector(
		".admin-bar-logo-image-field",
	);
	var adminBarLogoImageSrcField = document.querySelector(
		".udb-branding-upload-image",
	);
	var adminBarLogoLinkUrlField = document.querySelector(
		".udb-admin-bar-logo-url",
	);
	var wpLogo = document.querySelector(".udb-wp-logo");
	var wpLogoLink = document.querySelector(".udb-wp-logo a");

	var modernLogoWrappers = document.querySelectorAll(".udb-admin-logo-wrapper");
	var inheritedModernLogoWrapper = document.querySelector(
		".udb-admin-logo-wrapper.udb-inherited-from-blueprint",
	);
	var modernLogoLinks = document.querySelectorAll(".udb-admin-logo-wrapper a");
	var modernLogos = document.querySelectorAll(
		".udb-admin-logo-wrapper .udb-admin-logo",
	);

	var removeWpIconStyleTag = document.querySelector(
		".udb-style-remove-wp-icon",
	);
	var adminBarLogoImageUrlStyleTag = document.querySelector(
		".udb-style-admin-bar-logo-image-url",
	);
	var removeWpIconSubmenuWrapperStyleTag = document.querySelector(
		".udb-style-remove-wp-icon-submenu-wrapper",
	);

	var inheritedOutputStyleTag = document.querySelector(
		".udb-admin-colors-output.udb-inherited-from-blueprint",
	);
	var defaultOutputStyleTag = document.querySelector(
		".udb-admin-colors-preview.udb-default-admin-colors-output",
	);
	var modernOutputStyleTag = document.querySelector(
		".udb-admin-colors-preview.udb-modern-admin-colors-output",
	);

	function init() {
		$(".udb-branding-admin-bar-logo-upload").click(function (e) {
			e.preventDefault();
			var button = this;
			var field = button.parentNode.querySelector(".udb-branding-upload-image");
			var mediaLibraryTitle = button.dataset.mediaLibraryTitle;

			var custom_uploader = wp
				.media({
					title: mediaLibraryTitle,
					button: {
						text: "Upload Image",
					},
					// Allow only single file selection.
					multiple: false,
				})
				.on("select", function () {
					var attachment = custom_uploader
						.state()
						.get("selection")
						.first()
						.toJSON();
					field.value = attachment.url;
					field.dispatchEvent(new Event("change"));
				})
				.open();
		});

		$(".udb-branding-clear-upload").click(function (e) {
			e.preventDefault();
			$(this).prev().prev().val("");
		});

		if (wpLogoLink) wpLogoLink.dataset.udbDefaultHref = wpLogoLink.href;

		modernLogoLinks.forEach(function (modernLogoLink) {
			modernLogoLink.dataset.udbDefaultHref = modernLogoLink.href;
		});

		modernLogos.forEach(function (modernLogo) {
			modernLogo.dataset.udbDefaultSrc = modernLogo.src;
		});

		checkBranding();
		toggleDarkModeClass();

		brandingCheckbox.addEventListener("change", checkBranding);
		layoutSelector.addEventListener("change", checkLayout);

		if (wpAdminDarkmodeCheckbox) {
			wpAdminDarkmodeCheckbox.addEventListener("change", toggleWpAdminDarkmode);
		}

		removeWpLogoCheckbox.addEventListener("change", checkWpLogo);
		adminBarLogoImageSrcField.addEventListener(
			"change",
			checkCustomAdminBarLogoImageSrc,
		);
		adminBarLogoLinkUrlField.addEventListener(
			"change",
			checkCustomAdminBarLogoLinkUrl,
		);
	}

	function brandingCheckboxChecked() {
		if (!brandingCheckbox || !brandingCheckbox.checked) return false;
		return true;
	}

	function checkBranding() {
		if (brandingCheckbox.checked) {
			enableBranding();
			checkLayout();
			checkWpLogo();
			checkCustomAdminBarLogoImageSrc();
			checkCustomAdminBarLogoLinkUrl();
		} else {
			disableBranding();
			disableLayout();
			showWpLogo();
			disableCustomAdminBarLogoImageSrc();
			disableCustomAdminBarLogoLinkUrl();
		}
	}

	function enableBranding() {
		if (layoutSelector) layoutSelector.disabled = false;

		instantPreviewStyleTags.forEach(function (tag) {
			tag.type = "text/css";
		});

		heatboxOverlays.forEach(function (overlay) {
			overlay.classList.add("is-hidden");
		});
	}

	function disableBranding() {
		if (layoutSelector) layoutSelector.disabled = true;

		instantPreviewStyleTags.forEach(function (tag) {
			tag.type = "text/udb";
		});

		heatboxOverlays.forEach(function (overlay) {
			overlay.classList.remove("is-hidden");
		});
	}

	function checkLayout() {
		if (inheritedOutputStyleTag) inheritedOutputStyleTag.type = "text/udb";

		if ("modern" === layoutSelector.value) {
			defaultOutputStyleTag.type = "text/udb";
			modernOutputStyleTag.type = "text/css";

			if (removeWpLogoCheckbox.checked) {
				hideModernLogoWrapper();
			} else {
				showModernLogoWrapper();
			}

			if (wpLogo) wpLogo.classList.add("udb-is-hidden");

			if (adminMenuWrap) {
				if (removeWpLogoCheckbox.checked) {
					adminMenuWrap.classList.add("udb-remove-padding");
					adminMenuWrap.classList.remove("udb-use-padding");
				} else {
					adminMenuWrap.classList.remove("udb-remove-padding");
					adminMenuWrap.classList.add("udb-use-padding");
				}
			}
		} else {
			defaultOutputStyleTag.type = "text/css";
			modernOutputStyleTag.type = "text/udb";

			hideModernLogoWrapper();

			if (wpLogo) {
				if (removeWpLogoCheckbox.checked) {
					wpLogo.classList.add("udb-is-hidden");
				} else {
					wpLogo.classList.remove("udb-is-hidden");
				}
			}

			if (adminMenuWrap) {
				adminMenuWrap.classList.remove("udb-remove-padding");
				adminMenuWrap.classList.add("udb-use-padding");
			}
		}

		if (inheritedModernLogoWrapper) {
			inheritedModernLogoWrapper.classList.add("udb-is-hidden");
		}
	}

	function hideModernLogoWrapper() {
		if (!modernLogoWrappers.length) return;

		modernLogoWrappers.forEach(function (modernLogoWrapper) {
			modernLogoWrapper.classList.add("udb-is-hidden");
		});
	}

	function showModernLogoWrapper() {
		if (!modernLogoWrappers.length) return;

		modernLogoWrappers.forEach(function (modernLogoWrapper) {
			modernLogoWrapper.classList.remove("udb-is-hidden");
		});
	}

	function disableLayout() {
		if (inheritedOutputStyleTag) inheritedOutputStyleTag.type = "text/css";
		defaultOutputStyleTag.type = "text/udb";
		modernOutputStyleTag.type = "text/udb";

		hideModernLogoWrapper();

		if (inheritedModernLogoWrapper) {
			inheritedModernLogoWrapper.classList.remove("udb-is-hidden");
		}

		if (wpLogo) wpLogo.classList.remove("udb-is-hidden");
	}

	function toggleDarkModeClass() {
		if (!wpAdminDarkmodeCheckbox || !wpAdminDarkModeStyleTag) return;

		if (wpAdminDarkmodeCheckbox.checked) {
			document.body.classList.add("dark-mode");
		} else {
			document.body.classList.remove("dark-mode");
		}
	}

	function toggleWpAdminDarkmode() {
		if (!wpAdminDarkmodeCheckbox || !wpAdminDarkModeStyleTag) return;

		if (wpAdminDarkmodeCheckbox.checked) {
			wpAdminDarkModeStyleTag.type = "text/css";
		} else {
			wpAdminDarkModeStyleTag.type = "text/udb";
		}

		toggleDarkModeClass();
	}

	function checkWpLogo() {
		if (removeWpLogoCheckbox.checked) {
			hideAdminBarLogoFieldsRow();
			hideWpLogo();
		} else {
			showAdminBarLogoFieldsRow();
			showWpLogo();
		}
	}

	function hideAdminBarLogoFieldsRow() {
		if (adminBarLogoImageFieldRow) {
			adminBarLogoImageFieldRow.classList.add("is-hidden");
		}

		if (adminBarLogoLinkUrlField) {
			adminBarLogoLinkUrlField.parentNode.parentNode.classList.add("is-hidden");
		}
	}

	function showAdminBarLogoFieldsRow() {
		if (adminBarLogoImageFieldRow) {
			adminBarLogoImageFieldRow.classList.remove("is-hidden");
		}

		if (adminBarLogoLinkUrlField) {
			adminBarLogoLinkUrlField.parentNode.parentNode.classList.remove(
				"is-hidden",
			);
		}
	}

	function hideWpLogo() {
		if (wpLogo) wpLogo.classList.add("udb-is-hidden");

		hideModernLogoWrapper();

		if ("modern" === layoutSelector.value) {
			adminMenuWrap.classList.add("udb-remove-padding");
			adminMenuWrap.classList.remove("udb-use-padding");
		} else {
			adminMenuWrap.classList.remove("udb-remove-padding");
			adminMenuWrap.classList.add("udb-use-padding");
		}
	}

	function showWpLogo() {
		if (brandingCheckboxChecked() && "modern" === layoutSelector.value) {
			showModernLogoWrapper();
		} else {
			if (wpLogo) wpLogo.classList.remove("udb-is-hidden");
		}

		adminMenuWrap.classList.remove("udb-remove-padding");
		adminMenuWrap.classList.add("udb-use-padding");
	}

	function checkCustomAdminBarLogoImageSrc() {
		if (
			!adminBarLogoImageSrcField.value ||
			"" === adminBarLogoImageSrcField.value
		) {
			removeWpIconStyleTag.innerHTML = buildCssContent(
				removeWpIconStyleTag.innerHTML,
				"",
			);
			removeWpIconSubmenuWrapperStyleTag.innerHTML = buildCssContent(
				removeWpIconSubmenuWrapperStyleTag.innerHTML,
				"",
			);
			adminBarLogoImageUrlStyleTag.innerHTML = buildCssContent(
				adminBarLogoImageUrlStyleTag.innerHTML,
				"",
			);

			modernLogos.forEach(function (modernLogo) {
				modernLogo.src = modernLogo.dataset.udbDefaultSrc;
			});
		} else {
			removeWpIconStyleTag.innerHTML = buildCssContent(
				removeWpIconStyleTag.innerHTML,
				"display: none;",
			);
			removeWpIconSubmenuWrapperStyleTag.innerHTML = buildCssContent(
				removeWpIconSubmenuWrapperStyleTag.innerHTML,
				"display: none;",
			);

			adminBarLogoImageUrlStyleTag.innerHTML = buildCssContent(
				adminBarLogoImageUrlStyleTag.innerHTML,
				"background-image: url(" + adminBarLogoImageSrcField.value + ");",
			);

			modernLogos.forEach(function (modernLogo) {
				modernLogo.src = adminBarLogoImageSrcField.value;
			});
		}
	}

	function disableCustomAdminBarLogoImageSrc() {
		removeWpIconStyleTag.innerHTML = buildCssContent(
			removeWpIconStyleTag.innerHTML,
			"display: inline;",
		);
		removeWpIconSubmenuWrapperStyleTag.innerHTML = buildCssContent(
			removeWpIconSubmenuWrapperStyleTag.innerHTML,
			"",
		);
		adminBarLogoImageUrlStyleTag.innerHTML = buildCssContent(
			adminBarLogoImageUrlStyleTag.innerHTML,
			"",
		);

		modernLogos.forEach(function (modernLogo) {
			modernLogo.src = modernLogo.dataset.udbDefaultSrc;
		});
	}

	function checkCustomAdminBarLogoLinkUrl() {
		if (
			!adminBarLogoLinkUrlField.value ||
			"" === adminBarLogoLinkUrlField.value
		) {
			disableCustomAdminBarLogoLinkUrl();
		} else {
			if (wpLogoLink) wpLogoLink.href = adminBarLogoLinkUrlField.value;

			modernLogoLinks.forEach(function (modernLogoLink) {
				modernLogoLink.href = adminBarLogoLinkUrlField.value;
			});
		}
	}

	function disableCustomAdminBarLogoLinkUrl() {
		if (wpLogoLink) wpLogoLink.href = wpLogoLink.dataset.udbDefaultHref;

		modernLogoLinks.forEach(function (modernLogoLink) {
			modernLogoLink.href = modernLogoLink.dataset.udbDefaultHref;
		});
	}

	function buildCssContent(content, cssRule) {
		var str = content.split("{");

		return str[0] + "{" + cssRule + "}";
	}

	init();
})(jQuery);

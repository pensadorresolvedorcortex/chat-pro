declare var wp: any;

const listenTemplateFieldChange = () => {
	wp.customize("udb_login[template]", function (setting: any) {
		setting.bind(function (val: string) {
			const selected = document.querySelector(
				'[data-control-name="udb_login[template]"] .is-selected img'
			) as HTMLImageElement;

			const bgImage = selected ? selected.dataset.bgImage : "";

			if (bgImage) wp.customize("udb_login[bg_image]").set(bgImage);

			switch (val) {
				case "left":
					wp.customize("udb_login[form_position]").set("left");
					break;

				case "right":
					wp.customize("udb_login[form_position]").set("right");
					break;

				default:
					wp.customize("udb_login[form_position]").set("default");
			}
		});
	});
};

export default listenTemplateFieldChange;

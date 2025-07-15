declare var wp: any;

const listenFormWidthFieldChange = () => {
	wp.customize("udb_login[form_width]", function (setting: any) {
		setting.bind(function (val: string) {
			let formPosition = wp.customize("udb_login[form_position]").get();
			let content = "";

			formPosition = formPosition ? formPosition : "default";

			if (formPosition === "default") {
				content = "#login {width: " + val + ";}";
			} else {
				content = "#loginform {max-width: " + val + ";}";
			}

			document.querySelector(
				'[data-listen-value="udb_login[form_width]"]'
			).innerHTML = content;
		});
	});
};

export default listenFormWidthFieldChange;

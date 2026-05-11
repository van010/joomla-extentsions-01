(() => {
	"use strict";

	const cfg = window.Joomla && window.Joomla.getOptions
		? window.Joomla.getOptions("plgEventbookingGenresOverride", null)
		: null;

	if (!cfg) {
		return;
	}

	const updateLabel = (forId, text) => {
		if (!forId || !text) {
			return;
		}

		const selectionField = document.querySelector(`#${forId}`);
		if (!selectionField) {
			return ;
		}
		const selectionContainer = selectionField.parentElement.parentElement;
		const label = selectionContainer.querySelector('.form-control-label');
		console.log(label);
		if (!label) {
			return;
		}

		label.textContent = text;
	};

	const replaceOptions = (selectId, options, placeholder, isMultiple) => {
		const select = document.getElementById(selectId);
		if (!select || !Array.isArray(options)) {
			return;
		}

		const currentValues = Array.from(select.selectedOptions).map((opt) => String(opt.value));
		select.innerHTML = "";

		if (!isMultiple) {
			const defaultOpt = document.createElement("option");
			defaultOpt.value = "";
			defaultOpt.textContent = placeholder || "";
			select.appendChild(defaultOpt);
		}

		options.forEach((item) => {
			const value = String(item.value ?? "");
			const text = String(item.text ?? "");

			if (!value || !text) {
				return;
			}

			const opt = document.createElement("option");
			opt.value = value;
			opt.textContent = text;
			if (currentValues.includes(value)) {
				opt.selected = true;
			}
			select.appendChild(opt);
		});

		if (isMultiple && currentValues.length && !select.selectedOptions.length) {
			currentValues.forEach((value) => {
				const opt = Array.from(select.options).find((o) => o.value === value);
				if (opt) {
					opt.selected = true;
				}
			});
		}
	};

	const apply = () => {
		if (cfg.mainCategory) {
			updateLabel(cfg.mainCategory.labelFor, cfg.mainCategory.labelText);
			replaceOptions(
				cfg.mainCategory.selectId,
				cfg.mainCategory.options,
				cfg.mainCategory.placeholder,
				false
			);
		}

		if (cfg.additionalCategory) {
			updateLabel(cfg.additionalCategory.labelFor, cfg.additionalCategory.labelText);
			replaceOptions(
				cfg.additionalCategory.selectId,
				cfg.additionalCategory.options,
				cfg.additionalCategory.placeholder,
				true
			);
		}
	};

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", apply);
	} else {
		apply();
	}
})();

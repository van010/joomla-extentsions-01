const $ = jQuery;

class VgSearchEb {

	constructor(moduleId) {
		this.form = $(`.mod-vg-search-eb-${moduleId}`);
		this.resultSection = $(`.vg-search-eb-result-${moduleId}`);
		this.baseUrl = Joomla.getOptions('system.paths').root;
	}

	async init() {
		await this.fetchEvents();
	}

	async fetchEvents() {
		const ajaxUrl = this.parseAjaxUrl();
		await $.ajax({
			types: 'POST',
			url: ajaxUrl,
			data: this.getFormData(),
			dataType: 'json',
			success: (res) => {
				this.resultSection.html(res.html);
				// $('.eb-events-timeline').hide();
			},
			error: function ()
			{
				// todo
			}
		});
	}

	getFormData() {
		const dataArray = this.form.serializeArray();
		// Convert to a clean Object if preferred
		const dataObj = {};
		$.each(dataArray, function () {
			if (dataObj[this.name]) {
				if (!Array.isArray(dataObj[this.name])) {
					dataObj[this.name] = [dataObj[this.name]];
				}
				dataObj[this.name].push(this.value);
			} else {
				dataObj[this.name] = this.value;
			}
		});

		return dataObj;
	}

	parseAjaxUrl() {
		const url_params = {
			option: 'com_ajax',
			module: 'vg_search_eb',
			method: 'loadEvents',
			format: 'json',
		};
		const paramsString = new URLSearchParams(url_params).toString();
		return this.baseUrl + '/index.php?' + paramsString.replace(/%2C/g, ','); // Replace %2C with a comma
	}
}

$(function () {
	// todo
	console.log('Ready to cook!');
});

function searchEvents() {
	const s = new VgSearchEb(modVgSearchEbId);
	s.init().then(r => 1);
}

function resetSearch()
{
	window.location.reload();
}
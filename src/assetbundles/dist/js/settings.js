(function($) {
	Craft.KlaviyoConnect = Craft.KlaviyoConnect || {};

	Craft.KlaviyoConnect.Settings = Garnish.Base.extend({
		init(ids) {
			this.initTestConnection(ids);
			this.initCustomProperties(ids);
			this.initImageEngine(ids);
			this.initReveal();
		},

		initTestConnection(ids) {
			const $button = $('#' + ids.testConnectionButton);
			const $results = $('#' + ids.testResults);

			$button.on('click', () => {
				$button.addClass('loading');
				$results.empty();

				Craft.sendActionRequest('POST', 'klaviyoconnect/settings/test-connection', {
					data: $button.closest('form').find('[name^="settings["]').serialize(),
				})
					.then(({ data }) => {
						if (data.lastErrorCleared) {
							$button.closest('.field').find('.warning').remove();
						}

						const $siteLists = $('#' + ids.siteLists);
						$siteLists.html(data.siteListsHtml);
						Craft.initUiElements($siteLists);

						data.results.forEach((result) => {
							$('<li/>')
								.append($('<span/>', { class: 'status ' + (result.connected ? 'green' : 'red') }))
								.append($('<strong/>').text(result.label + ': '))
								.append(document.createTextNode(result.message))
								.appendTo($results);
						});
					})
					.catch(({ response }) => Craft.cp.displayError(response?.data?.message))
					.finally(() => $button.removeClass('loading'));
			});
		},

		initCustomProperties(ids) {
			const table = document.getElementById(ids.customPropertiesTable);
			const rowTemplate = document.getElementById(ids.customPropertyRowTemplate);

			document.getElementById(ids.addCustomPropertyButton).addEventListener('click', () => {
				table.tBodies[0].insertAdjacentHTML('beforeend', rowTemplate.innerHTML.replaceAll('__ROW__', 'new' + Date.now()));
			});

			table.addEventListener('click', (event) => {
				if (event.target.matches('.delete')) {
					event.preventDefault();
					event.target.closest('tr').remove();
				}
			});
		},

		// Show the named transform for Craft, and the size fields whenever no named transform applies
		initImageEngine(ids) {
			const engine = document.getElementById(ids.imageEngine);
			const namedTransform = document.getElementById(ids.namedTransform);
			const update = () => {
				document.getElementById(ids.imageCraft).classList.toggle('hidden', engine.value !== 'craft');
				document.getElementById(ids.imageSize).classList.toggle('hidden', engine.value === 'none' || (engine.value === 'craft' && namedTransform.value !== ''));
			};
			engine.addEventListener('change', update);
			namedTransform.addEventListener('change', update);
		},

		// Show a cell's extra input when its select picks the option named in data-reveal
		initReveal() {
			document.querySelectorAll('.klaviyoconnect-reveal-table').forEach((table) => {
				table.addEventListener('change', (event) => {
					const revealValue = event.target.dataset.reveal;
					if (revealValue !== undefined) {
						event.target.closest('td').querySelectorAll('.klaviyoconnect-reveal').forEach((reveal) => {
							reveal.classList.toggle('hidden', event.target.value !== (reveal.dataset.revealValue ?? revealValue));
						});
					}
				});
			});
		},
	});
})(jQuery);

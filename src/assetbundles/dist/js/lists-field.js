document.addEventListener('click', (event) => {
	const link = event.target.closest('.klaviyoconnect-refresh-lists');
	if (! link) {
		return;
	}

	event.preventDefault();
	if (link.classList.contains('disabled')) {
		return;
	}

	const wrapper = link.closest('.klaviyoconnect-lists-input');
	const control = wrapper.querySelector('.klaviyoconnect-lists-control');
	const values = [...control.querySelectorAll('select, input[type="checkbox"]:checked')].map((input) => input.value).filter(Boolean);
	link.classList.add('disabled');

	Craft.sendActionRequest('POST', 'klaviyoconnect/lists/refresh', {
		data: {
			fieldId: wrapper.dataset.fieldId,
			siteId: wrapper.dataset.siteId,
			namespace: wrapper.dataset.namespace,
			values,
		},
	})
		.then(({ data }) => {
			control.innerHTML = data.inputHtml;
			Craft.initUiElements(control);
		})
		.catch(({ response }) => Craft.cp.displayError(response?.data?.message))
		.finally(() => link.classList.remove('disabled'));
});

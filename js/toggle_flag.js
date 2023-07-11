const flagBoxes = document.querySelectorAll('input.flag');


flagBoxes.forEach(checkbox => {
    checkbox.addEventListener('click', event => {
        checkbox.disabled = true;

        const id = checkbox.closest('tr').dataset.id;
        const checked = checkbox.checked;
        const formData = new FormData();
        formData.append('id', id);
        formData.append('enabled', checked);

        fetch('./js/set_flag.php', {
            method: 'POST',
            redirect: 'error',
            body: formData
        })
        .then(response => {
            return response.json();
        })
        .then(content => {
            if (content.success !== true) {
                checkbox.checked = !checkbox.checked;
            }
            checkbox.disabled = false;
        })
        .catch(error => {
            console.log(error);
            checkbox.checked = !checkbox.checked;
            checkbox.disabled = false;
        });
    });

    checkbox.disabled = false;
});

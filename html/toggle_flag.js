const flagBoxes = document.querySelectorAll('input.flag');


flagBoxes.forEach(checkbox => {
    checkbox.addEventListener('click', event => {
        checkbox.disabled = true;
        
        const id = checkbox.dataset.id;
        const checked = checkbox.checked;
        const formData = new FormData();
        formData.append('id', id);
        formData.append('enabled', checked);

        fetch('set_flag.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            checkbox.disabled = false;
        })
        .catch(error => {
            checkbox.checked = !checkbox.checked;
            checkbox.disabled = false;
        });
        
    });
});

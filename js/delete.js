const deleteLinks = document.querySelectorAll('a.delete');


deleteLinks.forEach(link => {
    link.addEventListener('click', event => {
        event.preventDefault();

        const confirmed = confirm('Are you sure you want to delete this entry?');

        if (confirmed) {
            const id = link.dataset.id;
            const formData = new FormData();
            formData.append('id', id);

            fetch('./js/delete.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                document.getElementById(`br${id}`).remove();
            })
            .catch(error => {
                link.innerHTML = 'Error';
            });
        }
    });
});

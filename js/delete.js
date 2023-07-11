const deleteLinks = document.querySelectorAll('a.delete');


deleteLinks.forEach(link => {
    link.addEventListener('click', event => {
        event.preventDefault();

        const confirmed = confirm('Are you sure you want to delete this entry?');

        if (confirmed) {
            const id = link.closest('tr').dataset.id;
            const formData = new FormData();
            formData.append('id', id);

            fetch('./js/delete.php', {
                method: 'POST',
                redirect: 'error',
                body: formData
            })
            .then(response => {
                return response.json();
            })
            .then(content => {
                    console.log(content);
                if (content.success) {
                    document.getElementById(`br${id}`).remove();
                } else {
                    link.innerHTML = 'Error';
                }
            })
            .catch(error => {
                link.parentElement.innerHTML = 'Error';
            });
        }
    });
});

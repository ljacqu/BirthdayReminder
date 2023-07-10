const deleteLinks = document.querySelectorAll('a.unlock');


deleteLinks.forEach(link => {
    link.addEventListener('click', event => {
        event.preventDefault();

        const confirmed = confirm('Unlock the user? This will set the number of failed login attempts back to zero.');

        if (confirmed) {
            const id = link.dataset.id;
            const formData = new FormData();
            formData.append('unlock', id);

            fetch('system.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                    link.parentElement.innerText = 'Unlocked';
            })
            .catch(error => {
                    link.parentElement.innerText = 'Error';
            });
        }
    });
});

const nameCells = document.querySelectorAll('.dbleditname');

nameCells.forEach(nameCell => {
    nameCell.addEventListener('dblclick', event => {
        const oldName = nameCell.textContent;
        const input = newInputElem('text', oldName);

        input.addEventListener('focusout', event => {
            input.disabled = true;
            
            const newName = input.value;
            if (newName === oldName || !newName.trim()) {
                replaceElemWithTextNode(input, oldName);
            } else {
                const formData = new FormData();
                formData.append('name', newName);
                formData.append('id', nameCell.closest('tr').dataset.id);

                fetch('./js/edit_name.php', {
                    method: 'POST',
                    redirect: 'error',
                    body: formData
                })
                .then(response => {
                    return response.json();
                }).then(response => {
                    const text = response.success ? newName : oldName;
                    replaceElemWithTextNode(input, text);
                })
                .catch(error => {
                    replaceElemWithTextNode(input, oldName);
                });
            }
        });

        nameCell.replaceChild(input, nameCell.childNodes[0]);
    });
});

const dateCells = document.querySelectorAll('.dbleditdate');

dateCells.forEach(dateCell => {
    dateCell.addEventListener('dblclick', event => {
        const oldDateText = dateCell.textContent;
        const oldDate = dateCell.dataset.date;
        const input = newInputElem('date', oldDate);

        input.addEventListener('focusout', event => {
            input.disabled = true;

            const newDate = input.value;
            if (newDate === oldDate || !newDate.trim()) {
                replaceElemWithTextNode(input, oldDateText);
            } else {
                const formData = new FormData();
                formData.append('date', newDate);
                formData.append('id', dateCell.closest('tr').dataset.id);

                fetch('./js/edit_date.php', {
                    method: 'POST',
                    redirect: 'error',
                    body: formData
                })
                .then(response => {
                    return response.json();
                }).then(response => {
                    const text = response.success ? response.newText : oldDateText;
                    dateCell.dataset.date = newDate;
                    replaceElemWithTextNode(input, text);
                })
                .catch(error => {
                    replaceElemWithTextNode(input, oldDateText);
                });
            }
        });

        dateCell.replaceChild(input, dateCell.childNodes[0]);
    });
});


function newInputElem(type, value) {
    const input = document.createElement('input');
    input.type = type;
    input.value = value;
    return input;
}

function replaceElemWithTextNode(elemToReplace, newText) {
    const parent = elemToReplace.parentElement;
    const textNode = document.createTextNode(newText);
    parent.replaceChild(textNode, elemToReplace);
}
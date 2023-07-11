const nameCells = document.querySelectorAll('.dbleditname');

nameCells.forEach(nameCell => {
    let isInEditMode = false;

    nameCell.addEventListener('dblclick', event => {
        if (isInEditMode) {
            return; // Already displaying an input, so don't do it again
        }

        isInEditMode = true;
        const oldName = nameCell.textContent;
        const input = newInputElem('text', oldName);

        let submitChangeAndSetText = (useOld) => {
            input.disabled = true;

            const newName = input.value;
            if (useOld || newName === oldName || !newName.trim()) {
                replaceElemWithTextNode(input, oldName);
                isInEditMode = false;
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
                })
                .then(response => {
                    const text = response.success ? newName : oldName;
                    replaceElemWithTextNode(input, text);
                    isInEditMode = false;
                })
                .catch(error => {
                    replaceElemWithTextNode(input, oldName);
                    isInEditMode = false;
                });
            }
        };

        input.addEventListener('focusout', event => submitChangeAndSetText(false));
        input.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                submitChangeAndSetText(false);
            } else if (event.key === 'Escape') {
                submitChangeAndSetText(true);
            }
        });

        nameCell.replaceChild(input, nameCell.childNodes[0]);
    });
});

const dateCells = document.querySelectorAll('.dbleditdate');

dateCells.forEach(dateCell => {
    let isInEditMode = false;

    dateCell.addEventListener('dblclick', event => {
        if (isInEditMode) {
            return; // Already displaying an input, so don't do it again
        }

        isInEditMode = true;

        const oldDateText = dateCell.textContent;
        const oldDate = dateCell.dataset.date;
        const input = newInputElem('date', oldDate);

        let submitChangeAndSetText = (useOld) => {
            input.disabled = true;

            const newDate = input.value;
            if (useOld || newDate === oldDate || !newDate.trim()) {
                replaceElemWithTextNode(input, oldDateText);
                isInEditMode = false;
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
                })
                .then(response => {
                    const text = response.success ? response.newText : oldDateText;
                    dateCell.dataset.date = newDate;
                    replaceElemWithTextNode(input, text);
                    isInEditMode = false;
                })
                .catch(error => {
                    replaceElemWithTextNode(input, oldDateText);
                    isInEditMode = false;
                });
            }
        };

        input.addEventListener('focusout', event => submitChangeAndSetText(false));
        input.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                submitChangeAndSetText(false);
            } else if (event.key === 'Escape') {
                submitChangeAndSetText(true);
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
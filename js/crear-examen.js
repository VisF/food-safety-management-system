(function () {
    const displayInput = document.getElementById('fecha_display');
    const hiddenInput = document.getElementById('fecha');
    const form = displayInput ? displayInput.form : null;

    const parseDisplayDate = (value) => {
        const match = /^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/.exec(
            (value || '').trim()
        );

        if (!match) {
            return '';
        }

        return `${match[3]}-${match[2]}-${match[1]}`;
    };

    const syncHiddenDate = () => {
        if (!displayInput || !hiddenInput) {
            return;
        }

        hiddenInput.value = parseDisplayDate(
            displayInput.value
        );
    };

    if (!displayInput || !hiddenInput) {
        return;
    }

    syncHiddenDate();

    displayInput.addEventListener(
        'input',
        syncHiddenDate
    );

    displayInput.addEventListener(
        'blur',
        syncHiddenDate
    );

    if (form) {
        form.addEventListener(
            'submit',
            function (event) {
                syncHiddenDate();

                if (!hiddenInput.value) {
                    event.preventDefault();

                    displayInput.setCustomValidity(
                        'Usá el formato dd/mm/aaaa.'
                    );

                    displayInput.reportValidity();

                    return;
                }

                displayInput.setCustomValidity('');
            }
        );
    }
})();
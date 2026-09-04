(function () {
    const inputs =
        document.querySelectorAll(
            '.documento-card__archivo-input'
        );

    inputs.forEach(function (input) {

        input.addEventListener(
            'change',
            function () {

                if (
                    this.files &&
                    this.files.length > 0
                ) {
                    this.form.submit();
                }

            }
        );

    });
})();
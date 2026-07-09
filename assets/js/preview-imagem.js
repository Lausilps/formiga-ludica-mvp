function inicializarPreviewImagem(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    input.addEventListener('change', function (event) {
        const arquivo = event.target.files[0];

        if (arquivo) {
            preview.src = URL.createObjectURL(arquivo);
            preview.style.display = 'block';
        } else {
            preview.src = '';
            preview.style.display = 'none';
        }
    });
}

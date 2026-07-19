function alternarSenha(idCampo, botao) {
    const campo = document.getElementById(idCampo);
    const mostrando = campo.type === 'text';

    campo.type = mostrando ? 'password' : 'text';
    botao.textContent = mostrando ? '👁' : '🙈';
    botao.setAttribute('aria-label', mostrando ? 'Mostrar senha' : 'Ocultar senha');
}

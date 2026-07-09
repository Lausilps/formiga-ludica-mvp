const CARRINHO_STORAGE_KEY = 'formigaludica_carrinho';

const Carrinho = {
    obter() {
        try {
            const dados = localStorage.getItem(CARRINHO_STORAGE_KEY);
            return dados ? JSON.parse(dados) : [];
        } catch (e) {
            return [];
        }
    },
    salvar(lista) {
        localStorage.setItem(CARRINHO_STORAGE_KEY, JSON.stringify(lista));
    }
};

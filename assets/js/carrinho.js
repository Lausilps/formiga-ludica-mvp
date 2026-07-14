const CARRINHO_STORAGE_KEY = 'formigaludica_carrinho';
const CARRINHO_WHATSAPP_NUMERO = '5537991121992';

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
    },
    formatarPreco(valor) {
        return Number(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    },

    // Liga a barra/modal de pedido e a seleção de jogos a um conjunto de elementos
    // presentes tanto no catálogo (index.php) quanto na tela de recomendação.
    iniciarUI(opcoes = {}) {
        const selecionados = Carrinho.obter();
        const botaoEscolherSeletor = opcoes.botaoEscolherSeletor || '.btn-escolher';
        const aoAlternarSelecao = opcoes.aoAlternarSelecao || function () {};

        const modalPedido = document.getElementById('modal-pedido');
        const badgeCarrinho = document.getElementById('carrinho-badge');
        let modalPedidoAberto = false;

        function abrirModalPedido() {
            montarListaPedido();
            modalPedido.classList.add('ativo');
            modalPedidoAberto = true;
            history.pushState({ modal: 'pedido' }, '');
        }

        function fecharModalPedido() {
            modalPedido.classList.remove('ativo');
            if (modalPedidoAberto) {
                modalPedidoAberto = false;
                history.back();
            }
        }

        // Voltar do navegador/celular fecha o modal em vez de sair do site
        window.addEventListener('popstate', () => {
            modalPedidoAberto = false;
            modalPedido.classList.remove('ativo');
        });

        function atualizarBarraPedido() {
            if (!badgeCarrinho) return;
            if (selecionados.length > 0) {
                badgeCarrinho.textContent = selecionados.length;
                badgeCarrinho.style.display = 'flex';
            } else {
                badgeCarrinho.style.display = 'none';
            }
        }

        function atualizarBotoesSelecionados() {
            document.querySelectorAll(botaoEscolherSeletor).forEach(botao => {
                const nome = botao.dataset.nome;
                if (selecionados.some(j => j.nome === nome)) {
                    botao.textContent = 'Selecionado';
                    botao.classList.add('selecionado');
                } else {
                    botao.textContent = 'Escolher';
                    botao.classList.remove('selecionado');
                }
            });
        }

        function alternarJogoSelecionado(jogo) {
            const index = selecionados.findIndex(i => i.nome === jogo.nome);
            if (index === -1) {
                selecionados.push(jogo);
            } else {
                selecionados.splice(index, 1);
            }
            Carrinho.salvar(selecionados);
            atualizarBarraPedido();
            atualizarBotoesSelecionados();
            aoAlternarSelecao(jogo);
        }

        function montarListaPedido() {
            const listaPedido = document.getElementById('lista-pedido');
            const totalPedido = document.getElementById('total-pedido');
            listaPedido.innerHTML = '';
            let total = 0;

            selecionados.forEach((jogo, index) => {
                total += Number(jogo.preco);
                const item = document.createElement('div');
                item.className = 'item-pedido';
                item.innerHTML = `
                    <span>${jogo.nome} - ${Carrinho.formatarPreco(jogo.preco)}</span>
                    <button type="button" data-index="${index}">Remover</button>
                `;
                listaPedido.appendChild(item);
            });

            totalPedido.textContent = `Total estimado: ${Carrinho.formatarPreco(total)}`;

            document.querySelectorAll('#lista-pedido button').forEach(botao => {
                botao.addEventListener('click', function () {
                    selecionados.splice(Number(this.dataset.index), 1);
                    Carrinho.salvar(selecionados);
                    montarListaPedido();
                    atualizarBarraPedido();
                    atualizarBotoesSelecionados();
                    if (selecionados.length === 0) fecharModalPedido();
                });
            });
        }

        document.getElementById('abrir-modal-pedido').addEventListener('click', abrirModalPedido);

        document.getElementById('fechar-modal-pedido').addEventListener('click', fecharModalPedido);

        document.getElementById('continuar-escolhendo').addEventListener('click', fecharModalPedido);

        modalPedido.addEventListener('click', e => {
            if (e.target === modalPedido) fecharModalPedido();
        });

        document.getElementById('confirmar-whatsapp').addEventListener('click', () => {
            const total = selecionados.reduce((soma, j) => soma + Number(j.preco), 0);
            const mensagem = `Olá! Tenho interesse em alugar os jogos:\n\n- ${selecionados.map(j => j.nome).join('\n- ')}\n\nTotal estimado: ${Carrinho.formatarPreco(total)}\n\nPode me passar disponibilidade?`;
            window.open(`https://wa.me/${CARRINHO_WHATSAPP_NUMERO}?text=${encodeURIComponent(mensagem)}`, '_blank');
        });

        atualizarBarraPedido();
        atualizarBotoesSelecionados();

        return {
            selecionados,
            alternarJogoSelecionado,
            atualizarBarraPedido,
            atualizarBotoesSelecionados,
            montarListaPedido
        };
    }
};

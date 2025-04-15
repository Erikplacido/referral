// ✅ Função para carregar a posição do usuário no ranking
async function carregarPosicaoUsuario() {
    try {
        const response = await fetch("fetch_posicao.php");
        const data = await response.json();

        if (data.position) {
            document.querySelector('[data-referral-ranking]').textContent = data.position;
        } else {
            document.querySelector('[data-referral-ranking]').textContent = "N/A";
        }
    } catch (error) {
        console.error("Erro ao buscar posição do ranking:", error);
        document.querySelector('[data-referral-ranking]').textContent = "Erro";
    }
}

// ✅ Executar ao carregar a página
document.addEventListener("DOMContentLoaded", () => {
    carregarPosicaoUsuario();
});
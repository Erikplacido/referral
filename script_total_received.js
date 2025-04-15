document.addEventListener("DOMContentLoaded", async function () {
    try {
        const response = await fetch("fetch_total_received.php");
        const data = await response.json();

        // Se houver erro, exibe mensagem no console
        if (data.error) {
            console.error("Erro ao buscar total recebido:", data.error);
            return;
        }

        // Atualiza o elemento na página com o valor retornado
        const totalReceivedElement = document.querySelector("[data-total-received]");
        if (totalReceivedElement) {
            totalReceivedElement.textContent = `$ ${data.total_received}`;
        }
    } catch (error) {
        console.error("Erro ao carregar os dados de total recebido:", error);
    }
});
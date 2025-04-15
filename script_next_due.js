document.addEventListener("DOMContentLoaded", async () => {
    try {
        const response = await fetch("fetch_next_due.php");
        const data = await response.json();

        if (data.error) {
            console.error("Erro ao carregar próximo pagamento:", data.error);
            return;
        }

        // Delay para garantir que o DOM esteja pronto
        setTimeout(() => {
            const el = document.querySelector("[data-next-due]");
            if (el) {
                el.textContent = `$ ${data.next_due}`;
                console.log("Próximo pagamento inserido com sucesso.");
            } else {
                console.warn("Elemento [data-next-due] não encontrado!");
            }
        }, 100); // 100ms geralmente já resolve

    } catch (error) {
        console.error("Erro ao buscar o próximo pagamento devido:", error);
    }
});
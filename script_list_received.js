document.addEventListener("DOMContentLoaded", async function () {
    try {
        const response = await fetch("fetch_list_received.php");
        const data = await response.json();

        if (data.error) {
            console.error("Erro:", data.error);
            return;
        }

        const tbody = document.getElementById("payments-body");
        if (!tbody) {
            console.error("Elemento da tabela de pagamentos não encontrado.");
            return;
        }

        tbody.innerHTML = ""; // Limpa a tabela antes de preencher os novos dados

        if (!data.payments || data.payments.length === 0) {
            tbody.innerHTML = "<tr><td colspan='3'>Nenhum pagamento encontrado no período.</td></tr>";
            return;
        }

        // Preenche a tabela com os pagamentos do período correto
        data.payments.forEach(payment => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>$ ${parseFloat(payment.payment_value).toFixed(2)}</td>
                <td>${payment.referral_name}</td>
                <td>${new Date(payment.payment_date).toLocaleDateString('pt-BR')}</td>
            `;
            tbody.appendChild(row);
        });

    } catch (error) {
        console.error("❌ Erro ao carregar pagamentos:", error);
    }
});
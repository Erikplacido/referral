// ✅ Modal de Configurações
const modal = document.getElementById('settingsModal');
const settingsBtn = document.querySelector('button[title="Settings"]');
const closeBtn = document.querySelector('.modal-content .close');

if (settingsBtn && modal) {
    settingsBtn.addEventListener('click', () => {
        modal.style.display = 'block';
        setTimeout(() => modal.classList.add('show'), 10);
    });

    const closeModal = () => {
        modal.classList.remove('show');
        setTimeout(() => modal.style.display = 'none', 300);
    };

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    window.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
}

// ✅ Alternância entre abas do modal
document.querySelectorAll('.tab-btn').forEach(button => {
    button.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

        button.classList.add('active');
        document.getElementById(button.getAttribute('data-tab')).classList.add('active');
    });
});

// ✅ Buscar dados do usuário e exibir na interface
async function fetchUserData() {
    try {
        const response = await fetch("fetch_data.php");
        const data = await response.json();

        console.log("Dados recebidos:", data); // Debug

        if (!data.user) throw new Error("Usuário não encontrado");

        // Atualizar informações do usuário
        document.querySelector('.user-name').textContent = data.user.name || 'Usuário';
        document.querySelector('[data-referral-code]').textContent = data.user.referral_code || 'N/A';
        document.querySelector('[data-club-category]').textContent = data.user.club_category || 'N/A';
        document.querySelector('[data-referral-ranking]').textContent = data.user.referral_ranking || 'N/A';

        // Atualizar Overview
        if (data.overview) {
            document.querySelector('[data-total-referrals]').textContent = data.overview.total_referrals || '0';
            document.querySelector('[data-successful]').textContent = data.overview.successful || '0';
            document.querySelector('[data-unsuccessful]').textContent = data.overview.unsuccessful || '0';
            document.querySelector('[data-pending]').textContent = data.overview.pending || '0';
            document.querySelector('[data-in-negotiation]').textContent = data.overview.in_negotiation || '0';
        } else {
            console.error("Erro: Dados do Overview não encontrados.");
        }

    } catch (error) {
        console.error("Erro ao carregar os dados do usuário:", error);
    }
}

// ✅ Buscar lista de pagamentos
async function fetchPayments() {
    try {
        const response = await fetch("fetch_data.php");
        const data = await response.json();

        console.log("🔍 Dados de pagamentos recebidos:", data); // Debug

        const tbody = document.getElementById("payments-body");
        if (!tbody) throw new Error("Elemento da tabela de pagamentos não encontrado");

        tbody.innerHTML = "";

        // Verifica se há pagamentos antes de iterar
        if (!data.payments || !Array.isArray(data.payments) || data.payments.length === 0) {
            console.warn("⚠️ Nenhum pagamento encontrado.");
        } else {
            // Preencher a tabela com os dados de pagamentos
            data.payments.forEach(payment => {
                const row = document.createElement("tr");
                row.innerHTML = `
                    <td>R$ ${parseFloat(payment.payment_value).toFixed(2)}</td>
                    <td>${payment.referral_name}</td>
                    <td>${payment.payment_date}</td>
                `;
                tbody.appendChild(row);
            });
        }

        // Atualizar os valores de pagamentos na interface
        document.querySelector("[data-total-received]").textContent = 
            data.total_received ? `R$ ${parseFloat(data.total_received).toFixed(2)}` : "R$ 0.00";

        document.querySelector("[data-next-due]").textContent = 
            data.next_due ? `R$ ${parseFloat(data.next_due).toFixed(2)}` : "R$ 0.00";

    } catch (error) {
        console.error("❌ Erro ao carregar pagamentos:", error);
    }
}

// ✅ Buscar e preencher os dados bancários do usuário
async function fetchBankDetails() {
    try {
        const response = await fetch("fetch_data.php");
        const data = await response.json();

        if (!data.bankDetails) {
            console.error("Dados bancários não encontrados.");
            return;
        }

        console.log("Dados bancários recebidos:", data); // Debug

        document.getElementById("bankName").value = data.bankDetails.bankName || "";
        document.getElementById("agency").value = data.bankDetails.agency || "";
        document.getElementById("bsb").value = data.bankDetails.bsb || "";
        document.getElementById("accountNumber").value = data.bankDetails.accountNumber || "";
        document.getElementById("abnNumber").value = data.bankDetails.abnNumber || "";

        // Bloqueia os campos para evitar edição direta até clicar no botão "Editar"
        document.querySelectorAll("#paymentHistoryForm input").forEach(input => {
            input.setAttribute("readonly", true);
        });

    } catch (error) {
        console.error("Erro ao carregar dados bancários:", error);
    }
}

// ✅ Habilitar edição dos campos bancários
document.getElementById("editBankDetails").addEventListener("click", () => {
    document.querySelectorAll("#paymentHistoryForm input").forEach(input => {
        input.removeAttribute("readonly");
    });
});

// ✅ Atualizar os dados bancários do usuário
document.getElementById("paymentHistoryForm").addEventListener("submit", async (event) => {
    event.preventDefault();

    const formData = new FormData(event.target);

    try {
        const response = await fetch("update_payments_info.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (result.message) {
            alert(result.message);
            fetchBankDetails(); // Recarrega os dados após a atualização
        } else if (result.error) {
            alert("Erro: " + result.error);
        }
    } catch (error) {
        console.error("Erro ao atualizar os dados bancários:", error);
    }
});

// ✅ Evento principal que executa todas as funções ao carregar a página
document.addEventListener("DOMContentLoaded", () => {
    fetchUserData();
    fetchPayments();
    fetchBankDetails();

    // Evento para alternar aba de "Payment History"
    const paymentTabButton = document.querySelector('button[data-tab="paymentHistory"]');
    if (paymentTabButton) {
        paymentTabButton.addEventListener("click", fetchBankDetails);
    }

    // ✅ Evento de Logout
    const logoutBtn = document.querySelector('.logout-icon');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            window.location.href = "logout.php";
        });
    } else {
        console.error("Erro: Botão de logout NÃO encontrado!");
    }
});

// ✅ Modal de Configurações
const modal = document.getElementById('settingsModal');
const settingsBtn = document.querySelector('button[title="Settings"]');
const closeBtn = document.querySelector('.modal-content .close');

if (settingsBtn && modal) {
    settingsBtn.addEventListener('click', () => {
        modal.style.display = 'block';
        setTimeout(() => modal.classList.add('show'), 10);
    });

    const closeModal = () => {
        modal.classList.remove('show');
        setTimeout(() => modal.style.display = 'none', 300);
    };

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    window.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
}

// ✅ Alternância entre abas do modal
document.querySelectorAll('.tab-btn').forEach(button => {
    button.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

        button.classList.add('active');
        document.getElementById(button.getAttribute('data-tab')).classList.add('active');
    });
});

// ✅ Buscar dados do usuário e exibir na interface
async function fetchUserData() {
    try {
        const response = await fetch("fetch_data.php");
        const data = await response.json();

        console.log("Dados recebidos:", data); // Debug

        if (!data.user) throw new Error("Usuário não encontrado");

        // Atualizar informações do usuário
        document.querySelector('.user-name').textContent = data.user.name || 'Usuário';
        document.querySelector('[data-referral-code]').textContent = data.user.referral_code || 'N/A';
        document.querySelector('[data-club-category]').textContent = data.user.club_category || 'N/A';
        document.querySelector('[data-referral-ranking]').textContent = data.user.referral_ranking || 'N/A';

        // Atualizar Overview
        if (data.overview) {
            document.querySelector('[data-total-referrals]').textContent = data.overview.total_referrals || '0';
            document.querySelector('[data-successful]').textContent = data.overview.successful || '0';
            document.querySelector('[data-unsuccessful]').textContent = data.overview.unsuccessful || '0';
            document.querySelector('[data-pending]').textContent = data.overview.pending || '0';
            document.querySelector('[data-in-negotiation]').textContent = data.overview.in_negotiation || '0';
        } else {
            console.error("Erro: Dados do Overview não encontrados.");
        }

    } catch (error) {
        console.error("Erro ao carregar os dados do usuário:", error);
    }
}

// ✅ Buscar lista de pagamentos
async function fetchPayments() {
    try {
        const response = await fetch("fetch_data.php");
        const data = await response.json();

        console.log("🔍 Dados de pagamentos recebidos:", data); // Debug

        const tbody = document.getElementById("payments-body");
        if (!tbody) throw new Error("Elemento da tabela de pagamentos não encontrado");

        tbody.innerHTML = "";

        // Verifica se há pagamentos antes de iterar
        if (!data.payments || !Array.isArray(data.payments) || data.payments.length === 0) {
            console.warn("⚠️ Nenhum pagamento encontrado.");
        } else {
            // Preencher a tabela com os dados de pagamentos
            data.payments.forEach(payment => {
                const row = document.createElement("tr");
                row.innerHTML = `
                    <td>R$ ${parseFloat(payment.payment_value).toFixed(2)}</td>
                    <td>${payment.referral_name}</td>
                    <td>${payment.payment_date}</td>
                `;
                tbody.appendChild(row);
            });
        }

        // Atualizar os valores de pagamentos na interface
        document.querySelector("[data-total-received]").textContent = 
            data.total_received ? `R$ ${parseFloat(data.total_received).toFixed(2)}` : "R$ 0.00";

        document.querySelector("[data-next-due]").textContent = 
            data.next_due ? `R$ ${parseFloat(data.next_due).toFixed(2)}` : "R$ 0.00";

    } catch (error) {
        console.error("❌ Erro ao carregar pagamentos:", error);
    }
}

// ✅ Buscar e preencher os dados bancários do usuário
async function fetchBankDetails() {
    try {
        const response = await fetch("fetch_data.php");
        const data = await response.json();

        if (!data.bankDetails) {
            console.error("Dados bancários não encontrados.");
            return;
        }

        console.log("Dados bancários recebidos:", data); // Debug

        document.getElementById("bankName").value = data.bankDetails.bankName || "";
        document.getElementById("agency").value = data.bankDetails.agency || "";
        document.getElementById("bsb").value = data.bankDetails.bsb || "";
        document.getElementById("accountNumber").value = data.bankDetails.accountNumber || "";
        document.getElementById("abnNumber").value = data.bankDetails.abnNumber || "";

        // Bloqueia os campos para evitar edição direta até clicar no botão "Editar"
        document.querySelectorAll("#paymentHistoryForm input").forEach(input => {
            input.setAttribute("readonly", true);
        });

    } catch (error) {
        console.error("Erro ao carregar dados bancários:", error);
    }
}

// ✅ Habilitar edição dos campos bancários
document.getElementById("editBankDetails").addEventListener("click", () => {
    document.querySelectorAll("#paymentHistoryForm input").forEach(input => {
        input.removeAttribute("readonly");
    });
});

// ✅ Atualizar os dados bancários do usuário
document.getElementById("paymentHistoryForm").addEventListener("submit", async (event) => {
    event.preventDefault();

    const formData = new FormData(event.target);

    try {
        const response = await fetch("update_payments_info.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (result.message) {
            alert(result.message);
            fetchBankDetails(); // Recarrega os dados após a atualização
        } else if (result.error) {
            alert("Erro: " + result.error);
        }
    } catch (error) {
        console.error("Erro ao atualizar os dados bancários:", error);
    }
});

// ✅ Evento principal que executa todas as funções ao carregar a página
document.addEventListener("DOMContentLoaded", () => {
    fetchUserData();
    fetchPayments();
    fetchBankDetails();

    // Evento para alternar aba de "Payment History"
    const paymentTabButton = document.querySelector('button[data-tab="paymentHistory"]');
    if (paymentTabButton) {
        paymentTabButton.addEventListener("click", fetchBankDetails);
    }

    // ✅ Evento de Logout
    const logoutBtn = document.querySelector('.logout-icon');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            window.location.href = "logout.php";
        });
    } else {
        console.error("Erro: Botão de logout NÃO encontrado!");
    }
});
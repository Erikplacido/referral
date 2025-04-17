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

        // Atualizar informações do usuário (nome completo)
        const nome = data.user.name || '';
        const sobrenome = data.user.sobrenome || '';
        document.querySelector('.user-name').textContent = `${nome} ${sobrenome}`.trim();

        document.querySelector('[data-referral-code]').textContent = data.user.referral_code || 'N/A';
        document.querySelector('[data-club-category]').textContent = data.user.club_category || 'N/A';

        // Atualizar Overview
        if (data.overview) {
            document.querySelector('[data-total-referrals]').textContent = data.overview.total_referrals || '0';
            document.querySelector('[data-successful]').textContent = data.overview.successful || '0';
            document.querySelector('[data-unsuccessful]').textContent = data.overview.unsuccessful || '0';
            document.querySelector('[data-pending]').textContent = data.overview.pending || '0';
            document.querySelector('[data-in-negotiation]').textContent = data.overview.in_negotiation || '0';

            // ✅ Proteção contra sobrescrita indevida do próximo pagamento
            const nextDueEl = document.querySelector('[data-next-due]');
            if (nextDueEl && nextDueEl.textContent === 'Carregando...') {
                nextDueEl.textContent = `R$ ${data.overview.next_due || '0.00'}`;
            }
        } else {
            console.error("Erro: Dados do Overview não encontrados.");
        }

    } catch (error) {
        console.error("Erro ao carregar os dados do usuário:", error);
    }
}

// ✅ Buscar e preencher os dados bancários do usuário
async function fetchBankDetails() {
    try {
        const response = await fetch("fetch_data.php");
        const data = await response.json();

        if (!data.bankDetails) {
            console.error("Dados bancários não encontrados.");
            const editBtn = document.getElementById("editBankDetails");
            if (editBtn) editBtn.style.display = 'none';
            return;
        }

        console.log("Dados bancários recebidos:", data); // Debug

        const { bankName, agency, bsb, accountNumber, abnNumber } = data.bankDetails;

        document.getElementById("bankName").value = bankName || "";
        document.getElementById("agency").value = agency || "";
        document.getElementById("bsb").value = bsb || "";
        document.getElementById("accountNumber").value = accountNumber || "";
        document.getElementById("abnNumber").value = abnNumber || "";

        // Determina se há dados existentes
        const hasData = bankName || agency || bsb || accountNumber || abnNumber;

        const editBtn = document.getElementById("editBankDetails");
        if (editBtn) {
            if (!hasData) {
                // sem dados: oculta Edit e libera inputs para inserir
                editBtn.style.display = 'none';
                document.querySelectorAll("#paymentHistoryForm input").forEach(input => {
                    input.removeAttribute("readonly");
                });
            } else {
                // com dados: mostra Edit e bloqueia inputs até editar
                editBtn.style.display = '';
                document.querySelectorAll("#paymentHistoryForm input").forEach(input => {
                    input.setAttribute("readonly", true);
                });
            }
        }

    } catch (error) {
        console.error("Erro ao carregar dados bancários:", error);
    }
}

// ✅ Habilitar edição dos campos bancários
const editBtn = document.getElementById("editBankDetails");
if (editBtn) {
    editBtn.addEventListener("click", () => {
        document.querySelectorAll("#paymentHistoryForm input").forEach(input => {
            input.removeAttribute("readonly");
        });
        editBtn.style.display = 'none';
    });
}

// ✅ Atualizar os dados bancários do usuário
const paymentForm = document.getElementById("paymentHistoryForm");
if (paymentForm) {
    paymentForm.addEventListener("submit", async (event) => {
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
}

// ✅ Evento principal que executa todas as funções ao carregar a página
document.addEventListener("DOMContentLoaded", () => {
    fetchUserData();
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

// Forgot Password toggle
document.getElementById("forgotPasswordLink").addEventListener("click", function (e) {
    e.preventDefault();
    document.getElementById("forgotPasswordForm").style.display = "block";
    document.getElementById("changePasswordForm").style.display = "none";
});

document.getElementById("forgotPasswordForm").addEventListener("submit", async function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    const response = await fetch("send_reset_email.php", {
        method: "POST",
        body: formData
    });

    const data = await response.json();

    if (data.success) {
        alert(data.success);
    } else {
        alert(data.error || "Erro ao processar solicitação.");
    }
});

// Share Referral
document.addEventListener('DOMContentLoaded', () => {
    const shareBtn = document.getElementById("shareReferralBtn");
    const referralElement = document.querySelector('[data-referral-code]');
    const feedback = document.getElementById("referralFeedback");

    if (shareBtn && referralElement) {
        shareBtn.addEventListener('click', () => {
            const code = referralElement.textContent.trim();
            if (!code) {
                alert("Referral code not loaded yet.");
                return;
            }

            const shareLink = `https://bluefacilityservices.com.au/${code}`;

            // Copia para área de transferência
            navigator.clipboard.writeText(shareLink).then(() => {
                feedback.style.display = "block";
                setTimeout(() => (feedback.style.display = "none"), 3000);
            }).catch(err => {
                console.error("Failed to copy referral link:", err);
            });
        });
    }
});
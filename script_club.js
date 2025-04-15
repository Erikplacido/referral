async function fetchClubData() {
    try {
        const response = await fetch("fetch_club.php");
        const data = await response.json();

        console.log("Dados do clube recebidos:", data); // Debug

        if (!data.user) throw new Error("Usu�rio n�o encontrado");

        // Atualizar nome e categoria do usu�rio
        document.querySelector('.user-name').textContent = data.user.name || 'Usu�rio';
        document.querySelector('[data-club-category]').textContent = data.user.club_category || 'N/A';

        // Atualizar a imagem do usu�rio dinamicamente
        const userIcon = document.querySelector('.user-icon');
        if (userIcon) {
            userIcon.src = data.user.icon;
        }

    } catch (error) {
        console.error("Erro ao carregar os dados do clube:", error);
    }
}

// Executa a fun��o ao carregar a p�gina
document.addEventListener("DOMContentLoaded", () => {
    fetchClubData();


});
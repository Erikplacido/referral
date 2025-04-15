async function fetchRanking() {
    try {
        const response = await fetch("fetch_ranking.php");
        const data = await response.json();

        if (data.error) {
            console.error("Erro ao buscar ranking:", data.error);
            return;
        }

document.querySelector("[data-ranking-info]").textContent =
    `Top 1: ${data[0]?.name || "N/A"} - $${parseFloat(data[0]?.total_received || 0).toFixed(2)} | ` +
    `Top 2: ${data[1]?.name || "N/A"} - $${parseFloat(data[1]?.total_received || 0).toFixed(2)} | ` +
    `Top 3: ${data[2]?.name || "N/A"} - $${parseFloat(data[2]?.total_received || 0).toFixed(2)}`;
            
    } catch (error) {
        console.error("Erro ao carregar ranking:", error);
    }
}

document.addEventListener("DOMContentLoaded", fetchRanking);
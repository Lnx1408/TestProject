<?php headerGame($data); ?>

<main class="main container" id="main">
    <div class="my-games-container">
        <!-- Header Section -->
        <div class="games-header">
            <h1 class="page-title" data-i18n="my_games.title">Mis Partidas</h1>
            <div class="games-stats">
                <div class="stat-card">
                    <i class='bx ri-file-list-3-line'></i>
                    <div class="stat-info">
                        <p class="stat-label" data-i18n="my_games.stats.classification">Partidas de Clasificación</p>
                        <h3 class="stat-value" id="classificationTotal">0</h3>
                    </div>
                </div>
                <div class="stat-card">
                    <i class='bx ri-building-4-line'></i>
                    <div class="stat-info">
                        <p class="stat-label" data-i18n="my_games.stats.construction">Partidas de Construcción</p>
                        <h3 class="stat-value" id="constructionTotal">0</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <div class="search-container">
                <i class='bx bx-search search-icon'></i>
                <input 
                    type="text" 
                    id="searchInput" 
                    class="search-input" 
                    data-i18n-placeholder="my_games.search.placeholder"
                    placeholder="Buscar partidas por código...">
            </div>
        </div>

        <!-- Games Sections -->
        <div class="games-sections">
            <!-- Classification Games -->
            <section class="games-section" id="classificationGames">
                <div class="section-header">
                    <h2 data-i18n="my_games.sections.classification">Partidas de Clasificación</h2>
                    <button class="toggle-view-btn" data-section="classification">
                        <i class='bx bx-chevron-down'></i>
                    </button>
                </div>
                <div class="games-grid" id="classificationGrid">
                    <!-- Cards will be added dynamically -->
                </div>
                <div class="load-more-container">
                    <button class="load-more-btn" data-section="classification" id="loadMoreClassification">
                        <i class='bx bx-plus'></i>
                        <span data-i18n="my_games.buttons.load_more">Ver más</span>
                    </button>
                    <button class="load-less-btn hidden" data-section="classification" id="loadLessClassification">
                        <i class='bx bx-minus'></i>
                        <span data-i18n="my_games.buttons.load_less">Ver menos</span>
                    </button>
                </div>
            </section>

            <!-- Construction Games -->
            <section class="games-section" id="constructionGames">
                <div class="section-header">
                    <h2 data-i18n="my_games.sections.construction">Partidas de Construcción</h2>
                    <button class="toggle-view-btn" data-section="construction">
                        <i class='bx bx-chevron-down'></i>
                    </button>
                </div>
                <div class="games-grid" id="constructionGrid">
                    <!-- Cards will be added dynamically -->
                </div>
                <div class="load-more-container">
                    <button class="load-more-btn" data-section="construction" id="loadMoreConstruction">
                        <i class='bx bx-plus'></i>
                        <span data-i18n="my_games.buttons.load_more">Ver más</span>
                    </button>
                </div>
            </section>
        </div>
    </div>
</main>

<script>
    const base_url = "<?= base_url(); ?>";
</script>
<?php
if (!empty($data['page_functions_js'])) {
    foreach ($data['page_functions_js'] as $js) {
        echo '<script src="' . media() . '/js/' . $js . '"></script>';
    }
}
?>
<?php footerGame($data); ?>
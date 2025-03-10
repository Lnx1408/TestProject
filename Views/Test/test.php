<?php headerGame($data); ?>

<main class="main container" id="main">
    <!--- INICIO CONTENIDO --->

    <div class="game-container">
        <!-- Analyses -->
        <div class="analyse">
            <div class="card-item color-success">
                <div class="status">
                    <div class="info">
                        <h3>Promedio Primer Intento</h3>
                        <h1>Aciertos</h1>
                    </div>
                    <div class="progresss">
                        <svg>
                            <circle cx="38" cy="38" r="36"></circle>
                        </svg>
                        <div class="percentage">
                            <p>+81%</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-item li">
                <i class='bx light-success ri-timer-flash-line'></i>
                <span class="info">
                    <p class="analyse-title">Tiempo Promedio</p>
                    <p class="analyse-info">
                        174 min
                    </p>
                </span>
            </div>
            <div class="card-item li">
                <i class='bx light-warning ri-user-star-fill'></i>
                <span class="info">
                    <p class="analyse-title">Total Jugadores</p>
                    <p class="analyse-info">
                        1
                    </p>
                </span>
            </div>
            <div class="card-item li">
                <i class='bx light-primary ri-reset-left-line'></i>
                <span class="info">
                    <p class="analyse-title">Promedio Intentos</p>
                    <p class="analyse-info">
                        6
                    </p>
                </span>
            </div>
            <div class="card-item hidden color-primary">
                <div class="status">
                    <div class="info">
                        <h3>Usaron 1 Intento</h3>
                        <h1>14,147</h1>
                    </div>
                    <div class="progresss">
                        <svg>
                            <circle cx="38" cy="38" r="36"></circle>
                        </svg>
                        <div class="percentage">
                            <p>+21%</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-item hidden color-warning">
                <div class="status">
                    <div class="info">
                        <h3>Usaron 2 a 3 Intentos</h3>
                        <h1>24,981</h1>
                    </div>
                    <div class="progresss">
                        <svg>
                            <circle cx="38" cy="38" r="36"></circle>
                        </svg>
                        <div class="percentage">
                            <p>-48%</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-item hidden color-danger">
                <div class="status">
                    <div class="info">
                        <h3>Usaron +3 Intentos</h3>
                        <h1>24,981</h1>
                    </div>
                    <div class="progresss">
                        <svg>
                            <circle cx="38" cy="38" r="36"></circle>
                        </svg>
                        <div class="percentage">
                            <p>-48%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Analyses -->

        <!-- Botón Ver más -->
        <div class="show-more-container">
            <button id="showMoreBtn" class="show-more-btn">
                <i class='bx bx-plus'></i>
                Ver más
            </button>
        </div>

        <div class="bottom-data">
            <div class="orders">
                <div class="header-table">
                    <i class='bx ri-user-community-fill'></i>
                    <h3>Jugadores</h3>
                </div>
                <table id="tableJugadores" class="table-players">
                    <thead>
                        <tr>
                            <th>Jugador</th>
                            <th>Tiempo Empleado</th>
                            <th>Estado</th>
                            <th>Ver</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <img src="images/profile-1.jpg">
                                <p>John Doe</p>
                            </td>
                            <td>14-08-2023</td>
                            <td><span class="status completed">Completed</span></td>
                            <td>5H</td>
                        </tr>
                        <tr>
                            <td>
                                <img src="images/profile-1.jpg">
                                <p>Alexander Castro</p>
                            </td>
                            <td>14-08-2023</td>
                            <td><span class="status pending">Pending</span></td>
                            <td>5H</td>
                        </tr>
                        <tr>
                            <td>
                                <img src="images/profile-1.jpg">
                                <p>Stefan Salvatore</p>
                            </td>
                            <td>14-08-2023</td>
                            <td><span class="status process">Processing</span></td>
                            <td>5H</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!--- FIN CONTENIDO --->
</main>

<script>
    const base_url = "<?= base_url(); ?>";
</script>
<script src="<?= media(); ?>/js/jquery-3.7.1.min.js"></script>
<script src="<?= media(); ?>/js/plugins/datatables/dataTables.min.js"></script>
<script src="<?= media(); ?>/js/<?= $data['page_functions_js']; ?>"></script>
<?php footerGame($data); ?>
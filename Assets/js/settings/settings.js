/**
 * Función para alternar la visibilidad de una sección del menú
 * @param {string} sectionId - ID de la sección a alternar
 */
function toggleSection(sectionId) {
    const sectionContent = document.getElementById(sectionId);
    // Accede al div contenedor del botón
    const headerWrapper = sectionContent.previousElementSibling;
    // Encuentra el botón dentro del contenedor
    const sectionHeader = headerWrapper.querySelector('.section-header');
    
    // Comprobar si la sección está activa
    const isActive = sectionContent.classList.contains('active');
    
    // Cerrar todas las secciones primero
    document.querySelectorAll('.section-content').forEach(section => {
        section.classList.remove('active');
    });
    
    document.querySelectorAll('.section-header').forEach(header => {
        header.classList.remove('active');
    });
    
    // Si la sección no estaba activa, abrirla
    if (!isActive) {
        sectionContent.classList.add('active');
        sectionHeader.classList.add('active');
    }
}

/**
 * Módulo para gestionar la funcionalidad de la página de ajustes
 */
class SettingsManager {
    constructor() {
        this.init();
    }
    
    /**
     * Inicializa el módulo
     */
    init() {
        this.handleUrlParameters();
    }
    
    /**
     * Gestiona los parámetros de URL para abrir automáticamente secciones
     */
    handleUrlParameters() {
        // Obtener parámetros de URL
        const urlParams = new URLSearchParams(window.location.search);
        const section = urlParams.get('section');
        
        if (section) {
            // Buscar la sección correspondiente y abrirla
            const sectionId = this.getSectionId(section);
            if (sectionId) {
                toggleSection(sectionId);
            }
        }
    }
    
    /**
     * Obtiene el ID de la sección basado en el parámetro de URL
     * @param {string} section - Nombre de la sección
     * @returns {string|null} - ID de la sección o null si no se encuentra
     */
    getSectionId(section) {
        switch (section) {
            case 'personal':
                return 'personalInfo';
            case 'security':
                return 'securityInfo';
            case 'account':
                return 'accountInfo';
            default:
                return null;
        }
    }
}

// Inicializar el módulo cuando el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', () => {
    window.settingsManager = new SettingsManager();
});
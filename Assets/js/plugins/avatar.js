/**
 * Módulo para la gestión de avatares con iniciales
 */
const AvatarModule = {
    /**
     * Obtiene las iniciales a partir de un nombre completo
     * @param {string} name - Nombre completo
     * @returns {string} Iniciales (2 caracteres)
     */
    getInitials(name) {
        if (!name || typeof name !== 'string') return '--';

        const words = name.split(' ').filter(word => word.length > 0);
        return words.length === 1
            ? words[0].substring(0, 2).toUpperCase()
            : (words[0][0] + words[words.length - 1][0]).toUpperCase();
    },

    getInitials(firstName, lastName) {
        if ((!firstName && !lastName) || (typeof firstName !== 'string' && typeof lastName !== 'string')) {
            return '--';
        }

        const clean = text => text?.trim().split(' ').filter(w => w.length > 0);

        const first = clean(firstName);
        const last = clean(lastName);

        const firstInitial = first?.[0]?.[0] || '';
        const lastInitial = last?.[0]?.[0] || '';

        return (firstInitial + lastInitial).toUpperCase() || '--';
    },

    /**
     * Genera un color basado en el nombre
     * @param {string} name - Nombre para generar el color
     * @returns {string} Color en formato CSS (variable CSS o hex)
     */
    getAvatarColor(name) {
        if (!name || typeof name !== 'string') return 'var(--primary)';

        const colors = [
            'var(--primary)',
            'var(--success)',
            'var(--warning)',
            'var(--danger)',
            '#7E57C2',
            '#26A69A',
            '#FF7043'
        ];

        let hash = 0;
        for (let i = 0; i < name.length; i++) {
            hash = name.charCodeAt(i) + ((hash << 5) - hash);
        }

        return colors[Math.abs(hash) % colors.length];
    },

    /**
     * Crea un elemento de avatar
     * @param {string} name - Nombre para el avatar
     * @returns {HTMLElement} Elemento DOM del avatar
     */
    createAvatarElement(name) {
        const initials = this.getInitials(name);
        const color = this.getAvatarColor(name);

        const avatar = document.createElement('div');
        avatar.className = 'avatar-circle';
        avatar.style.backgroundColor = color;
        avatar.innerHTML = `<span class="initials">${initials}</span>`;

        return avatar;
    },

    /**
     * Actualiza un avatar existente
     * @param {HTMLElement} avatarElement - Elemento del avatar
     * @param {HTMLElement} initialsElement - Elemento con las iniciales
     * @param {string} name - Nombre actualizado
     */
    updateAvatar(avatarElement, initialsElement, firstName, lastName) {
        if (!avatarElement || !initialsElement) return;

        avatarElement.style.backgroundColor = this.getAvatarColor(`${firstName || ''} ${lastName || ''}`.trim());
        initialsElement.textContent = this.getInitials(firstName, lastName);
    },

    /**
     * Capitaliza un nombre completo
     * @param {string} name - Nombre a capitalizar
     * @returns {string} Nombre capitalizado
     */
    capitalizeFullName(name) {
        if (!name) return '';

        return name.split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join(' ');
    }
};

// Exportar el módulo para uso global
window.AvatarModule = AvatarModule;
const LanguageManager = {
    currentLang: localStorage.getItem('preferredLanguage') || 'es',
    translations: {},

    async init() {
        await this.loadTranslations();
        this.applyTranslations();
        this.setupLanguageToggle();
        document.dispatchEvent(new Event('translationsLoaded'));
    },

    async loadTranslations() {
        try {
            const response = await fetch(`${base_url}/Assets/js/i18n/${this.currentLang}.json`);
            this.translations = await response.json();
        } catch (error) {
            console.error('Error loading translations:', error);
        }
    },

    applyTranslations() {
        document.querySelectorAll('[data-i18n]').forEach(element => {
            const key = element.getAttribute('data-i18n');
            const keys = key.split('.');
            let translation = this.translations;

            // Navegar a través del objeto de traducciones
            for (const k of keys) {
                translation = translation[k];
                if (!translation) break;
            }

            element.textContent = translation || key;
        });
    },

    getTranslation(key) {
        const keys = key.split('.');
        let translation = this.translations;

        for (const k of keys) {
            translation = translation?.[k];
            if (!translation) break;
        }

        return translation;
    },

    async toggleLanguage() {
        this.currentLang = this.currentLang === 'es' ? 'en' : 'es';
        localStorage.setItem('preferredLanguage', this.currentLang);

        // Actualizar el idioma en el servidor
        try {
            const response = await fetch(`${base_url}/Language/setLanguage`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    encryptedData: CryptoModule.encrypt({
                        language: this.currentLang
                    })
                })
            });

            const data = await response.json();
            const decryptedData = CryptoModule.decrypt(data.data);

            if (!decryptedData.success) {
                console.error('Error updating language in server:', decryptedData.message);
            }
        } catch (error) {
            console.error('Error updating language:', error);
        }

        await this.loadTranslations();
        this.applyTranslations();

        // Actualizar el texto del botón de idioma
        const langButton = document.getElementById('language-text');
        if (langButton) {
            langButton.textContent = this.currentLang.toUpperCase();
        }

        const event = new Event('languageChanged');
        document.dispatchEvent(event);
    },

    setupLanguageToggle() {
        const languageButton = document.getElementById('language-button');
        if (languageButton) {
            languageButton.addEventListener('click', () => this.toggleLanguage());
        }

        // Actualizar el texto inicial del botón
        const langButton = document.getElementById('language-text');
        if (langButton) {
            langButton.textContent = this.currentLang.toUpperCase();
        }
    }
};
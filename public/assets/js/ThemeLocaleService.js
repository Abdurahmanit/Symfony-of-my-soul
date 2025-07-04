class ThemeLocaleService {
    constructor() {
        this.themeKey = 'userTheme';
        this.localeKey = 'userLocale';
        this.defaultTheme = 'light';
        this.defaultLocale = 'en';
    }

    getSavedTheme() {
        return localStorage.getItem(this.themeKey) || this.defaultTheme;
    }

    setTheme(theme) {
        document.body.className = '';
        document.body.classList.add(theme + '-theme');
        localStorage.setItem(this.themeKey, theme);

        fetch('/set-theme', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ theme: newTheme })
        }).catch(error => console.error('Error setting theme on server:', error));
    }

    toggleTheme() {
        const currentTheme = this.getSavedTheme();
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
    }

    getSavedLocale() {
        return localStorage.getItem(this.localeKey) || this.defaultLocale;
    }

    setLocale(locale) {
        localStorage.setItem(this.localeKey, locale);
        window.location.href = `/${locale}${window.location.pathname}`;
    }

    applySavedSettings() {
        this.setTheme(this.getSavedTheme());
    }
}

export { ThemeLocaleService };
import 'bootstrap';
import { ThemeLocaleService } from './ThemeLocaleService';
import React from 'react';
import { createRoot } from 'react-dom/client';

import TemplateFormApp from './react/index';
import AutocompleteUserSelect from './react/components/AutocompleteUserSelect';
import DraggableQuestion from './react/components/DraggableQuestion';

document.addEventListener('DOMContentLoaded', () => {
    const themeLocaleService = new ThemeLocaleService();
    themeLocaleService.applySavedSettings();

    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            themeLocaleService.toggleTheme();
        });
    }

    const langSelect = document.getElementById('language-select');
    if (langSelect) {
        langSelect.addEventListener('change', (event) => {
            themeLocaleService.setLocale(event.target.value);
        });
    }

    const templateFormContainer = document.getElementById('template-form-root');
    if (templateFormContainer) {
        const root = createRoot(templateFormContainer);
        root.render(React.createElement(TemplateFormApp));
    }

    document.querySelectorAll('.js-autocomplete-user-select').forEach(element => {
        const root = createRoot(element);
        const { currentUsers, inputName } = element.dataset;
        root.render(React.createElement(AutocompleteUserSelect, { initialUsers: currentUsers ? JSON.parse(currentUsers) : [], inputName: inputName || 'users[]' }));
    });

    document.querySelectorAll('.js-draggable-question-list').forEach(element => {
        const root = createRoot(element);
        const initialQuestions = JSON.parse(element.dataset.questions || '[]');
        root.render(React.createElement(DraggableQuestion, { initialQuestions: initialQuestions }));
    });
});
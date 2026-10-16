import * as monaco from 'monaco-editor';
import EditorWorker from 'monaco-editor/esm/vs/editor/editor.worker?worker';
import JsonWorker from 'monaco-editor/esm/vs/language/json/json.worker?worker';
import CssWorker from 'monaco-editor/esm/vs/language/css/css.worker?worker';
import HtmlWorker from 'monaco-editor/esm/vs/language/html/html.worker?worker';
import TsWorker from 'monaco-editor/esm/vs/language/typescript/ts.worker?worker';

globalThis.MonacoEnvironment = {
    getWorker(_moduleId, label) {
        if (label === 'json') return new JsonWorker();
        if (['css', 'scss', 'less'].includes(label)) return new CssWorker();
        if (['html', 'handlebars', 'razor'].includes(label)) return new HtmlWorker();
        if (['typescript', 'javascript'].includes(label)) return new TsWorker();
        return new EditorWorker();
    },
};

export function initStudentCodeEditor() {
const form = document.querySelector('[data-code-form]');
const container = document.querySelector('[data-code-editor]');
const textarea = document.querySelector('#source_code');

if (form && container && textarea && !container.dataset.editorInitialized) {
    container.dataset.editorInitialized = '1';
    const languageInput = form.querySelector('[data-code-language]');
    const error = form.querySelector('[data-code-error]');
    const aliases = {
        'c#': 'csharp', 'c++': 'cpp', 'js': 'javascript', 'node.js': 'javascript',
        'ts': 'typescript', 'py': 'python', 'golang': 'go', 'shell': 'shell',
        'bash': 'shell', 'objective-c': 'objective-c',
    };
    const languages = monaco.languages.getLanguages();

    const resolveLanguage = (value) => {
        const requested = value.trim().toLowerCase();
        const key = aliases[requested] || requested;
        const match = languages.find((language) =>
            language.id.toLowerCase() === key ||
            language.aliases?.some((alias) => alias.toLowerCase() === key)
        );
        return match?.id || 'plaintext';
    };

    if (languageInput) {
        const list = document.querySelector('#source-language-options');
        const existing = new Set([...list.options].map((option) => option.value.toLowerCase()));
        languages.forEach((language) => {
            if (language.id === 'plaintext' || existing.has(language.id.toLowerCase())) return;
            const option = document.createElement('option');
            option.value = language.id;
            list.append(option);
        });
    }

    const theme = () => document.documentElement.dataset.theme === 'dark' ? 'vs-dark' : 'vs';
    const editor = monaco.editor.create(container, {
        value: textarea.value,
        language: resolveLanguage(container.dataset.language || languageInput?.value || ''),
        theme: theme(),
        automaticLayout: true,
        minimap: { enabled: false },
        fontSize: 14,
        lineNumbers: 'on',
        scrollBeyondLastLine: false,
        tabSize: 4,
        wordWrap: 'on',
        accessibilitySupport: 'auto',
        contextmenu: false,
    });

    container.classList.add('is-ready');
    textarea.hidden = true;
    textarea.required = false;
    editor.onDidChangeModelContent(() => {
        textarea.value = editor.getValue();
        error.hidden = true;
    });

    languageInput?.addEventListener('input', () => {
        monaco.editor.setModelLanguage(editor.getModel(), resolveLanguage(languageInput.value));
    });

    new MutationObserver(() => monaco.editor.setTheme(theme()))
        .observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

    document.addEventListener('fullscreenchange', () => {
        requestAnimationFrame(() => editor.layout());
    });
    document.addEventListener('student-activity-shown', () => {
        requestAnimationFrame(() => editor.layout());
    });

    form.addEventListener('submit', (event) => {
        textarea.value = editor.getValue();
        if (!textarea.value.trim()) {
            event.preventDefault();
            error.hidden = false;
            editor.focus();
        }
    });
}
}

if (typeof document !== 'undefined') initStudentCodeEditor();

const installedPages = new WeakSet();

export function installActivityControls(doc) {
    if (!doc.querySelector('[data-student-activity-locked]') || installedPages.has(doc)) return;
    installedPages.add(doc);
    const fullscreenActivity = doc.querySelector('[data-fullscreen-activity]');

    const block = (event) => {
        event.preventDefault();
        event.stopImmediatePropagation();
    };

    // Capture before the editor and form controls process clipboard actions.
    ['copy', 'cut', 'paste', 'contextmenu', 'dragstart', 'drop'].forEach((type) => {
        doc.addEventListener(type, block, true);
    });

    doc.addEventListener('beforeinput', (event) => {
        if (['insertFromPaste', 'insertFromDrop', 'deleteByCut'].includes(event.inputType)) block(event);
    }, true);

    doc.addEventListener('keydown', (event) => {
        const key = event.key.toLowerCase();
        const modifier = event.ctrlKey || event.metaKey;
        if ((modifier && ['c', 'v', 'x', 'f', 'insert'].includes(key)) ||
            (event.shiftKey && key === 'insert') || key === 'f3' ||
            (key === 'escape' && fullscreenActivity && doc.fullscreenElement === fullscreenActivity)) {
            block(event);
        }
    }, true);

    doc.addEventListener('keyup', (event) => {
        if (event.key.toLowerCase() === 'escape' && fullscreenActivity && doc.fullscreenElement === fullscreenActivity) block(event);
    }, true);
}

if (typeof document !== 'undefined') {
    installActivityControls(document);
    document.addEventListener('student-activity-ready', () => installActivityControls(document));
}

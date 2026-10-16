import assert from 'node:assert/strict';
import test from 'node:test';
import { installActivityControls } from '../../resources/js/student-activity-controls.js';

function page(active) {
    const doc = new EventTarget();
    const activity = {};
    doc.querySelector = (selector) => active ? (selector === '[data-fullscreen-activity]' ? activity : {}) : null;
    doc.fullscreenElement = null;
    installActivityControls(doc);
    return doc;
}

function keyboard(key, options = {}) {
    const event = new Event('keydown', { cancelable: true });
    Object.assign(event, { key, ctrlKey: false, metaKey: false, shiftKey: false, ...options });
    return event;
}

test('activity page blocks clipboard, Find shortcuts, and drag and drop', () => {
    const doc = page(true);
    for (const type of ['copy', 'cut', 'paste', 'contextmenu', 'dragstart', 'drop']) {
        const event = new Event(type, { cancelable: true });
        doc.dispatchEvent(event);
        assert.equal(event.defaultPrevented, true, type);
    }
    for (const event of [
        keyboard('c', { ctrlKey: true }), keyboard('v', { metaKey: true }),
        keyboard('f', { ctrlKey: true }), keyboard('F3'),
        keyboard('Insert', { shiftKey: true }),
    ]) {
        doc.dispatchEvent(event);
        assert.equal(event.defaultPrevented, true, event.key);
    }
    const pasteInput = new Event('beforeinput', { cancelable: true });
    pasteInput.inputType = 'insertFromPaste';
    doc.dispatchEvent(pasteInput);
    assert.equal(pasteInput.defaultPrevented, true);
});

test('normal typing and pages outside an active activity remain available', () => {
    const active = page(true);
    const typing = keyboard('a');
    active.dispatchEvent(typing);
    assert.equal(typing.defaultPrevented, false);

    const ordinary = page(false);
    const copy = new Event('copy', { cancelable: true });
    ordinary.dispatchEvent(copy);
    assert.equal(copy.defaultPrevented, false);
});

test('Escape is blocked while the activity is in fullscreen', () => {
    const doc = page(true);
    const activity = doc.querySelector('[data-fullscreen-activity]');
    const beforeStart = keyboard('Escape');
    doc.dispatchEvent(beforeStart);
    assert.equal(beforeStart.defaultPrevented, false);

    doc.fullscreenElement = activity;
    const keyDown = keyboard('Escape');
    doc.dispatchEvent(keyDown);
    assert.equal(keyDown.defaultPrevented, true);
    const keyUp = new Event('keyup', { cancelable: true });
    keyUp.key = 'Escape';
    doc.dispatchEvent(keyUp);
    assert.equal(keyUp.defaultPrevented, true);

    doc.fullscreenElement = null;
    const afterExit = keyboard('Escape');
    doc.dispatchEvent(afterExit);
    assert.equal(afterExit.defaultPrevented, false);
});

import assert from 'node:assert/strict';
import test from 'node:test';
import { installFullscreenActivity } from '../../resources/js/student-fullscreen.js';

function page(keyboard, options = {}) {
    const doc = new EventTarget();
    const gate = { hidden: false };
    const content = { hidden: true, focus() {}, dataset: { workLoaded: options.timed ? '0' : '1' }, innerHTML: '' };
    const error = { hidden: true, textContent: '' };
    const display = { textContent: '' };
    const gateTime = { hidden: true };
    const start = new EventTarget();
    start.textContent = 'Start coding';
    start.focus = () => {};
    const elements = {
        '[data-activity-gate]': gate,
        '[data-activity-content]': content,
        '[data-fullscreen-start]': start,
        '[data-fullscreen-error]': error,
        '[data-gate-time]': gateTime,
    };
    const activity = {
        dataset: {
            activityName: 'coding exercise',
            timerStartUrl: options.timed ? '/timer' : undefined,
            workUrl: '/work', csrfToken: 'test-token',
        },
        querySelector: (selector) => selector === '[data-time-remaining]' ? display : elements[selector],
        querySelectorAll: (selector) => selector === '[data-time-remaining]' ? [display] : [],
        requestFullscreen: async () => {
            doc.fullscreenElement = activity;
            doc.dispatchEvent(new Event('fullscreenchange'));
        },
    };
    doc.querySelector = () => activity;
    doc.fullscreenEnabled = true;
    doc.fullscreenElement = null;
    const dispose = installFullscreenActivity(doc, keyboard, options.fetcher);
    return { doc, activity, gate, content, start, error, display, dispose };
}

test('activity opens in fullscreen and pauses when fullscreen exits', async () => {
    const { doc, gate, content, start } = page();
    assert.equal(content.hidden, true);
    assert.equal(gate.hidden, false);

    start.dispatchEvent(new Event('click'));
    await new Promise((resolve) => setImmediate(resolve));
    assert.equal(gate.hidden, true);
    assert.equal(content.hidden, false);

    doc.fullscreenElement = null;
    doc.dispatchEvent(new Event('fullscreenchange'));
    assert.equal(content.hidden, true);
    assert.equal(gate.hidden, false);
    assert.equal(start.textContent, 'Resume coding exercise');
});

test('fullscreen denial leaves the activity paused with a useful message', async () => {
    const { activity, content, error, start } = page();
    activity.requestFullscreen = async () => { throw new Error('Denied'); };
    start.dispatchEvent(new Event('click'));
    await new Promise((resolve) => setImmediate(resolve));
    assert.equal(content.hidden, true);
    assert.equal(error.hidden, false);
    assert.match(error.textContent, /Denied|blocked/i);
});

test('Escape keyboard lock is requested in fullscreen and released on exit', async () => {
    const calls = [];
    const keyboard = {
        lock: async (keys) => { calls.push(['lock', keys]); },
        unlock: () => { calls.push(['unlock']); },
    };
    const { doc, start } = page(keyboard);
    start.dispatchEvent(new Event('click'));
    await new Promise((resolve) => setImmediate(resolve));
    assert.deepEqual(calls, [['lock', ['Escape']]]);

    doc.fullscreenElement = null;
    doc.dispatchEvent(new Event('fullscreenchange'));
    assert.deepEqual(calls, [['lock', ['Escape']], ['unlock']]);
});

test('timed activity starts on click, loads work, and shows the server countdown', async () => {
    const urls = [];
    const fetcher = async (url) => {
        urls.push(url);
        if (url === '/timer') return {
            ok: true, json: async () => ({ remaining_milliseconds: 90000 }),
        };
        return { ok: true, text: async () => '<form>Timed work</form>' };
    };
    const { content, display, start, dispose } = page(undefined, { timed: true, fetcher });
    start.dispatchEvent(new Event('click'));
    await new Promise((resolve) => setImmediate(resolve));
    assert.deepEqual(urls, ['/timer', '/work']);
    assert.equal(content.dataset.workLoaded, '1');
    assert.equal(content.hidden, false);
    assert.equal(display.textContent, '01:30');
    dispose();
});

test('expired timer keeps work hidden and offers a return link', async () => {
    const fetcher = async () => ({
        ok: false, status: 409, json: async () => ({ message: 'Time is up.' }),
    });
    const { content, error, start } = page(undefined, { timed: true, fetcher });
    start.dispatchEvent(new Event('click'));
    await new Promise((resolve) => setImmediate(resolve));
    assert.equal(content.hidden, true);
    assert.equal(start.hidden, true);
    assert.equal(error.textContent, 'Time is up.');
});

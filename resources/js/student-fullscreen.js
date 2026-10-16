export function installFullscreenActivity(doc, keyboard = globalThis.navigator?.keyboard, fetcher = globalThis.fetch) {
    const activity = doc.querySelector('[data-fullscreen-activity]');
    if (!activity) return;

    const gate = activity.querySelector('[data-activity-gate]');
    const content = activity.querySelector('[data-activity-content]');
    const start = activity.querySelector('[data-fullscreen-start]');
    const error = activity.querySelector('[data-fullscreen-error]');
    const returnLink = activity.querySelector('[data-timer-return]');
    const timerStartUrl = activity.dataset.timerStartUrl;
    let sessionReady = !timerStartUrl;
    let started = false;
    let starting = false;
    let expired = false;
    let keyboardLockRequested = false;
    let deadline = null;
    let timerInterval = null;

    const lockEscape = async () => {
        if (keyboardLockRequested || typeof keyboard?.lock !== 'function') return;
        keyboardLockRequested = true;
        try {
            await keyboard.lock(['Escape']);
            if (doc.fullscreenElement !== activity) keyboard.unlock?.();
        } catch {
            // The keydown handler remains the fallback when keyboard lock is denied.
        }
    };

    const unlockEscape = () => {
        if (!keyboardLockRequested) return;
        keyboardLockRequested = false;
        keyboard.unlock?.();
    };

    const sync = () => {
        const fullscreen = doc.fullscreenElement === activity;
        const active = fullscreen && sessionReady && !expired;
        gate.hidden = active;
        content.hidden = !active;
        if (fullscreen) {
            started = true;
            void lockEscape();
            if (active) {
                error.hidden = true;
                content.focus({ preventScroll: true });
                doc.dispatchEvent(new Event('student-activity-shown'));
            }
        } else {
            unlockEscape();
            if (started && !expired) {
                start.textContent = `Resume ${activity.dataset.activityName}`;
                start.focus({ preventScroll: true });
            }
        }
    };

    const expire = (message = 'Time is up. Ask your instructor or administrator to reset your timer.') => {
        expired = true;
        sessionReady = false;
        clearInterval(timerInterval);
        start.hidden = true;
        if (returnLink) returnLink.hidden = false;
        error.textContent = message;
        error.hidden = false;
        sync();
    };

    const updateCountdown = () => {
        if (deadline === null || expired) return;
        const seconds = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
        const displays = activity.querySelectorAll('[data-time-remaining]');
        if (displays.length) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const remainder = seconds % 60;
            const time = hours > 0
                ? `${hours}:${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`
                : `${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`;
            displays.forEach((display) => { display.textContent = time; });
        }
        if (seconds === 0) expire();
    };

    const prepareTimedSession = async () => {
        const response = await fetcher(timerStartUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': activity.dataset.csrfToken, 'Accept': 'application/json' },
            credentials: 'same-origin',
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            if (response.status === 409) {
                expire(result.message);
                return;
            }
            throw new Error(result.message || 'Could not start the timer. Please try again.');
        }

        deadline = Date.now() + result.remaining_milliseconds;
        const gateTime = activity.querySelector('[data-gate-time]');
        if (gateTime) gateTime.hidden = false;
        if (result.remaining_milliseconds <= 0) {
            expire();
            return;
        }
        if (content.dataset.workLoaded !== '1') {
            const workResponse = await fetcher(activity.dataset.workUrl, {
                headers: { 'Accept': 'text/html' }, credentials: 'same-origin',
            });
            if (!workResponse.ok) throw new Error('Could not load the activity. Please try again.');
            content.innerHTML = await workResponse.text();
            content.dataset.workLoaded = '1';
            doc.dispatchEvent(new Event('student-activity-ready'));
        }

        sessionReady = true;
        clearInterval(timerInterval);
        updateCountdown();
        if (!expired) timerInterval = setInterval(updateCountdown, 1000);
    };

    start.addEventListener('click', async () => {
        if (starting || expired) return;
        if (typeof activity.requestFullscreen !== 'function' || doc.fullscreenEnabled === false) {
            error.textContent = 'Fullscreen is unavailable in this browser. Please use a browser that supports fullscreen.';
            error.hidden = false;
            return;
        }

        starting = true;
        start.disabled = true;
        try {
            await activity.requestFullscreen();
            if (timerStartUrl && !sessionReady) await prepareTimedSession();
            sync();
        } catch (failure) {
            error.textContent = failure?.message || 'Fullscreen was blocked. Allow fullscreen for this site, then try again.';
            error.hidden = false;
        } finally {
            starting = false;
            start.disabled = false;
        }
    });

    doc.addEventListener('fullscreenchange', sync);
    sync();

    return () => {
        clearInterval(timerInterval);
        unlockEscape();
        doc.removeEventListener('fullscreenchange', sync);
    };
}

if (typeof document !== 'undefined') installFullscreenActivity(document);

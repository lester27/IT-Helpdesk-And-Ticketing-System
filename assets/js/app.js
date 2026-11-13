/*
 * assets/js/app.js — small client-side helpers (vanilla JS, no frameworks)
 *
 * Ideas / TODO:
 *   - Confirm dialogs: <button data-confirm="Resolve this ticket?"> -> confirm() before submit
 *   - Bootstrap client-side form validation (.needs-validation)
 *   - Auto-dismiss success alerts after a few seconds
 *
 * Remember: JS checks are for UX only. All rules MUST also be enforced in PHP.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Confirm before submitting dangerous actions
    document.querySelectorAll('[data-confirm]').forEach((el) => {
        el.addEventListener('click', (ev) => {
            if (!confirm(el.dataset.confirm)) ev.preventDefault();
        });
    });
});


(() => {
    "use strict";

    const root = document.querySelector("[data-fp-login-password]");
    if (!root) return;

    const input = root.querySelector("[data-fp-login-password-input]");
    const toggle = root.querySelector("[data-fp-login-password-toggle]");
    if (!input || !toggle) return;

    const setVisible = (visible) => {
        input.type = visible ? "text" : "password";
        toggle.setAttribute("aria-pressed", visible ? "true" : "false");

        const label = visible ? "Приховати пароль" : "Показати пароль";
        toggle.setAttribute("aria-label", label);
        toggle.setAttribute("title", label);
    };

    toggle.addEventListener("click", () => {
        const visible = input.type === "password";
        setVisible(visible);
        input.focus({preventScroll: true});
    });
})();

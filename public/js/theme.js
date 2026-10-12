(() => {
    "use strict";
    const key = "threads-city-theme";
    const root = document.documentElement;
    const system = window.matchMedia("(prefers-color-scheme: dark)");
    let preference;
    try { preference = localStorage.getItem(key); } catch {}
    if (preference !== "light" && preference !== "dark") preference = null;
    const apply = (theme) => {
        root.dataset.theme = theme;
        const dark = theme === "dark";
        document.querySelectorAll("[data-theme-toggle]").forEach((button) => {
            const label = dark ? "Chế độ sáng" : "Chế độ tối";
            button.hidden = false;
            button.setAttribute("aria-pressed", String(dark));
            button.setAttribute("aria-label", dark ? "Chuyển sang giao diện sáng" : "Chuyển sang giao diện tối");
            button.title = button.getAttribute("aria-label");
            button.querySelector("[data-theme-label]").textContent = label;
        });
    };
    const current = () => preference || (system.matches ? "dark" : "light");
    apply(current());
    document.addEventListener("DOMContentLoaded", () => {
        apply(current());
        document.querySelectorAll("[data-theme-toggle]").forEach((button) => {
            button.addEventListener("click", () => {
                preference = root.dataset.theme === "dark" ? "light" : "dark";
                try { localStorage.setItem(key, preference); } catch {}
                apply(preference);
            });
        });
    });
    system.addEventListener("change", () => { if (!preference) apply(current()); });
    window.addEventListener("storage", (event) => {
        if (event.key !== key && event.key !== null) return;
        preference = event.newValue === "light" || event.newValue === "dark" ? event.newValue : null;
        apply(current());
    });
})();

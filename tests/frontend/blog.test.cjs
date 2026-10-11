// HTTP + DOM integration tests, not a visual browser/screenshot test.
// A seeded local Laravel/MySQL server must be running; see README.
const { test } = require("node:test");
const assert = require("node:assert/strict");
const { readFileSync } = require("node:fs");
const { join } = require("node:path");
const { JSDOM } = require("jsdom");
const script = readFileSync(join(__dirname, "../../public/js/blog.js"), "utf8");
const base = process.env.BLOG_TEST_URL || "http://127.0.0.1:8000";
assert.match(
    base,
    /^http:\/\/(127\.0\.0\.1|localhost)(:\d+)?$/,
    "Only a local demo server may be tested.",
);

class Client {
    constructor() {
        this.cookies = new Map();
    }
    async request(path, data, method = "GET") {
        const headers = {
            Cookie: [...this.cookies].map(([k, v]) => k + "=" + v).join("; "),
        };
        let body;
        if (data) {
            headers["Content-Type"] = "application/x-www-form-urlencoded";
            body = new URLSearchParams(data);
        }
        const response = await fetch(base + path, {
            method,
            headers,
            body,
            redirect: "manual",
        });
        for (const cookie of response.headers.getSetCookie()) {
            const pair = cookie.split(";")[0];
            const at = pair.indexOf("=");
            this.cookies.set(pair.slice(0, at), pair.slice(at + 1));
        }
        return {
            status: response.status,
            location: response.headers.get("location"),
            html: await response.text(),
        };
    }
    async page(path) {
        const page = await this.request(path);
        assert.equal(page.status, 200, path + " should render");
        const token = page.html.match(/name="csrf-token" content="([^"]+)"/);
        assert.ok(token, "CSRF meta token exists");
        this.token = token[1];
        return page.html;
    }
    async login(email, password) {
        await this.page("/dang-nhap");
        const result = await this.request(
            "/dang-nhap",
            { _token: this.token, email, password },
            "POST",
        );
        assert.equal(result.status, 302);
        await this.page("/");
    }
}

let cachedEditor;
async function editorDom(setup = () => {}) {
    cachedEditor ||= (async () => {
        const client = new Client();
        await client.login("admin@blog.local", "Admin@12345");
        return client.page("/admin/bai-viet/soan");
    })();
    const dom = new JSDOM(await cachedEditor, {
        url: base + "/admin/bai-viet/soan",
        runScripts: "outside-only",
    });
    dom.window.HTMLDialogElement.prototype.showModal = function () {
        this.setAttribute("open", "");
    };
    dom.window.HTMLDialogElement.prototype.close = function () {
        this.removeAttribute("open");
    };
    setup(dom.window);
    dom.window.eval(script);
    return dom;
}

test("real HTTP/MySQL lifecycle: publish, moderate, trash, restore draft, permanently delete", async () => {
    const guest = new Client();
    assert.equal((await guest.request("/admin")).status, 302);
    assert.equal(
        (
            await guest.request(
                "/dang-nhap",
                { email: "admin@blog.local", password: "Admin@12345" },
                "POST",
            )
        ).status,
        419,
        "Missing CSRF rejected",
    );
    const owner = new Client();
    const reader = new Client();
    await owner.login("admin@blog.local", "Admin@12345");
    await reader.login("reader@blog.local", "Reader@12345");
    assert.equal((await reader.request("/admin")).status, 403);
    for (const path of [
        "/admin",
        "/admin/bai-viet",
        "/admin/bai-viet/thung-rac",
        "/admin/chuyen-muc",
        "/admin/the",
        "/admin/binh-luan",
        "/admin/doc-gia",
        "/admin/cai-dat",
    ])
        await owner.page(path);
    const editor = await owner.page("/admin/bai-viet/soan");
    const doc = new JSDOM(editor);
    const category = doc.window.document.querySelector(
        'select[name="category_id"] option[value]:not([value=""])',
    ).value;
    doc.window.close();
    const slug = "http-smoke-" + Date.now();
    const data = {
        title: "HTTP smoke test",
        slug,
        content: "## Test section\n\nContent for temporary end-to-end test.",
        excerpt: "Temporary test post.",
        category_id: category,
        status: "draft",
    };
    let id;
    try {
        const created = await owner.request(
            "/admin/bai-viet",
            { ...data, _token: owner.token },
            "POST",
        );
        assert.equal(created.status, 302);
        id = created.location.match(/\/bai-viet\/(\d+)\/sua/)[1];
        assert.equal((await guest.request("/bai-viet/" + slug)).status, 404);
        await owner.page("/admin/bai-viet/" + id + "/xem-truoc");
        const published = await owner.request(
            "/admin/bai-viet/" + id,
            {
                ...data,
                status: "published",
                _method: "PATCH",
                _token: owner.token,
            },
            "POST",
        );
        assert.equal(published.status, 302);
        assert.equal((await guest.request("/bai-viet/" + slug)).status, 200);
        const saved = await reader.request(
            "/bai-viet/" + id + "/luu",
            { _token: reader.token },
            "POST",
        );
        assert.equal(saved.status, 302);
        assert.match(await reader.page("/da-luu"), /HTTP smoke test/);
        const message = "Smoke comment " + slug;
        const comment = await reader.request(
            "/bai-viet/" + id + "/binh-luan",
            { body: message, _token: reader.token },
            "POST",
        );
        assert.equal(comment.status, 302);
        assert.ok(
            !(await guest.request("/bai-viet/" + slug)).html.includes(message),
            "Pending comment stays private",
        );
        const html = await owner.page("/admin/binh-luan?status=pending");
        const comments = new JSDOM(html);
        const box = [
            ...comments.window.document.querySelectorAll(".management-box"),
        ].find((el) => el.textContent.includes(message));
        assert.ok(box);
        const action = box
            .querySelector('form[action] select[name="status"]')
            .closest("form")
            .getAttribute("action");
        comments.window.close();
        const approved = await owner.request(
            new URL(action).pathname,
            { _token: owner.token, _method: "PATCH", status: "approved" },
            "POST",
        );
        assert.equal(approved.status, 302);
        assert.ok(
            (await guest.request("/bai-viet/" + slug)).html.includes(message),
        );
        assert.equal(
            (
                await owner.request(
                    "/admin/bai-viet/" + id,
                    { _method: "DELETE", _token: owner.token },
                    "POST",
                )
            ).status,
            302,
        );
        assert.equal((await guest.request("/bai-viet/" + slug)).status, 404);
        assert.ok(!(await reader.page("/da-luu")).includes("HTTP smoke test"));
        const trash = new JSDOM(await owner.page("/admin/bai-viet/thung-rac"));
        const deletion = trash.window.document.querySelector(
            'form[action$="/' + id + '/xoa-vinh-vien"]',
        );
        assert.ok(
            deletion.querySelector('input[name="confirm_delete"][required]'),
        );
        assert.ok(deletion.querySelector('input[name="_token"]').value);
        trash.window.close();
        assert.equal(
            (
                await owner.request(
                    "/admin/bai-viet/" + id + "/khoi-phuc",
                    { _method: "PATCH", _token: owner.token },
                    "POST",
                )
            ).status,
            302,
        );
        assert.equal(
            (await guest.request("/bai-viet/" + slug)).status,
            404,
            "Restoration stays draft",
        );
        assert.match(
            await owner.page("/admin/bai-viet/" + id + "/sua"),
            /Temporary test post/,
        );
        assert.equal(
            (
                await owner.request(
                    "/admin/bai-viet/" + id,
                    {
                        ...data,
                        status: "published",
                        _method: "PATCH",
                        _token: owner.token,
                    },
                    "POST",
                )
            ).status,
            302,
        );
        assert.match(await reader.page("/da-luu"), /HTTP smoke test/);
        assert.ok(
            (await guest.request("/bai-viet/" + slug)).html.includes(message),
            "Approved comment retained",
        );
    } finally {
        if (id) {
            await owner.page("/admin/bai-viet");
            const removed = await owner.request(
                "/admin/bai-viet/" + id,
                { _method: "DELETE", _token: owner.token },
                "POST",
            );
            assert.equal(removed.status, 302, "Temporary post is cleaned up");
            await owner.page("/admin/bai-viet/thung-rac");
            const permanent = await owner.request(
                "/admin/bai-viet/" + id + "/xoa-vinh-vien",
                { _method: "DELETE", _token: owner.token, confirm_delete: "1" },
                "POST",
            );
            assert.equal(
                permanent.status,
                302,
                "Temporary trash and related data cleaned up",
            );
        }
    }
    assert.equal((await guest.request("/bai-viet/" + slug)).status, 404);
    assert.ok(
        !(await reader.page("/da-luu")).includes("HTTP smoke test"),
        "Cascaded bookmark is cleaned up",
    );
});

test("analytics chart and accessible daily table agree for 7/30/90 days", async () => {
    const owner = new Client();
    await owner.login("admin@blog.local", "Admin@12345");
    for (const days of [7, 30, 90]) {
        const dom = new JSDOM(await owner.page("/admin?range=" + days));
        const chart = dom.window.document.querySelector("[data-views-chart]");
        assert.ok(chart);
        assert.equal(chart.getAttribute("role"), "img");
        assert.equal(chart.querySelectorAll("circle").length, days);
        assert.doesNotMatch(chart.innerHTML, /NaN|Infinity|undefined/);
        assert.equal(
            dom.window.document.querySelectorAll(".daily-data tbody tr").length,
            days,
        );
        assert.equal(
            dom.window.document.querySelector('select[name="range"]').value,
            String(days),
        );
        dom.window.close();
    }
});

test("editor preview renders headings and escapes markup", async () => {
    const dom = await editorDom();
    const doc = dom.window.document,
        form = doc.querySelector("[data-editor]");
    form.elements.namedItem("title").value = "<img src=x onerror=alert(1)>";
    form.elements.namedItem("excerpt").value = "Opening";
    form.elements.namedItem("content").value =
        "## Heading\n\n<script>window.attacked=true</script>\n\nA paragraph.";
    doc.querySelector("[data-preview]").click();
    assert.equal(doc.querySelector("[data-preview-dialog]").open, true);
    assert.equal(doc.querySelector("[data-preview-title] img"), null);
    assert.equal(doc.querySelector("[data-preview-body] script"), null);
    assert.equal(
        doc.querySelector("[data-preview-body] h2").textContent,
        "Heading",
    );
    assert.equal(doc.querySelectorAll("[data-preview-body] p").length, 2);
    doc.querySelector("[data-close-preview]").click();
    assert.equal(doc.querySelector("[data-preview-dialog]").open, false);
    dom.window.close();
});

test("editor counts words and does not intercept real database form submit", async () => {
    const dom = await editorDom();
    const form = dom.window.document.querySelector("[data-editor]");
    form.elements.namedItem("content").value = "one two three";
    form.elements
        .namedItem("content")
        .dispatchEvent(new dom.window.Event("input"));
    assert.equal(
        dom.window.document.querySelector("[data-word-count]").textContent,
        "3 từ",
    );
    const event = new dom.window.Event("submit", {
        bubbles: true,
        cancelable: true,
    });
    form.dispatchEvent(event);
    assert.equal(event.defaultPrevented, false);
    assert.equal(form.method, "post");
    assert.equal(form.enctype, "multipart/form-data");
    assert.ok(form.elements.namedItem("_token").value);
    assert.equal(dom.window.localStorage.length, 0);
    dom.window.close();
});

test("confirmation cancels only when user declines", () => {
    const dom = new JSDOM(
        '<form data-confirm="Delete?"><button>Delete</button></form><div id="toast"></div>',
        { url: base, runScripts: "outside-only" },
    );
    dom.window.confirm = () => false;
    dom.window.eval(script);
    const form = dom.window.document.querySelector("form");
    const denied = new dom.window.Event("submit", { cancelable: true });
    form.dispatchEvent(denied);
    assert.equal(denied.defaultPrevented, true);
    dom.window.confirm = () => true;
    const accepted = new dom.window.Event("submit", { cancelable: true });
    form.dispatchEvent(accepted);
    assert.equal(accepted.defaultPrevented, false);
    dom.window.close();
});

test("unsupported dialog gives a message without breaking save", async () => {
    const dom = await editorDom((window) => {
        window.HTMLDialogElement.prototype.showModal = undefined;
    });
    dom.window.document.querySelector("[data-preview]").click();
    assert.match(
        dom.window.document.querySelector("#toast").textContent,
        /chưa hỗ trợ/,
    );
    dom.window.close();
});

test("clipboard success and failure are reported honestly", async () => {
    const dom = new JSDOM(
        '<button data-copy-link>Copy</button><div id="toast"></div>',
        { url: base + "/bai-viet/test", runScripts: "outside-only" },
    );
    let copied;
    Object.defineProperty(dom.window.navigator, "clipboard", {
        value: {
            writeText: async (value) => {
                copied = value;
            },
        },
    });
    dom.window.eval(script);
    dom.window.document.querySelector("button").click();
    await new Promise((resolve) => setImmediate(resolve));
    assert.equal(copied, base + "/bai-viet/test");
    assert.match(
        dom.window.document.querySelector("#toast").textContent,
        /Đã sao chép/,
    );
    dom.window.navigator.clipboard.writeText = async () => {
        throw new Error("blocked");
    };
    dom.window.document.querySelector("button").click();
    await new Promise((resolve) => setImmediate(resolve));
    assert.match(
        dom.window.document.querySelector("#toast").textContent,
        /Không thể sao chép/,
    );
    dom.window.close();
});

test("cover preview validates size/type and releases old object URL", async () => {
    const released = [];
    const dom = await editorDom((window) => {
        window.URL.createObjectURL = () => "blob:preview";
        window.URL.revokeObjectURL = (url) => released.push(url);
    });
    const doc = dom.window.document,
        input = doc.querySelector("[data-cover-input]");
    Object.defineProperty(input, "files", {
        configurable: true,
        value: [{ type: "image/png", size: 100 }],
    });
    input.dispatchEvent(new dom.window.Event("change"));
    assert.equal(doc.querySelector("[data-cover-preview]").hidden, false);
    Object.defineProperty(input, "files", {
        configurable: true,
        value: [{ type: "image/svg+xml", size: 100 }],
    });
    input.dispatchEvent(new dom.window.Event("change"));
    assert.equal(doc.querySelector("[data-cover-preview]").hidden, true);
    assert.deepEqual(released, ["blob:preview"]);
    assert.match(doc.querySelector("#toast").textContent, /không quá 5 MB/);
    Object.defineProperty(input, "files", {
        configurable: true,
        value: [{ type: "image/png", size: 6 * 1024 * 1024 }],
    });
    input.dispatchEvent(new dom.window.Event("change"));
    assert.equal(doc.querySelector("[data-cover-preview]").hidden, true);
    dom.window.close();
});

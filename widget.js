/*!
 * PrimeWebKit chat widget
 * Embed: <script src="https://api.primewebkit.com/widget.js" data-bot-id="BOT-UUID" async></script>
 *
 * Self-contained, dependency-free. Renders inside a Shadow DOM so host
 * page CSS can't break it (and vice versa). Talks to the PrimeWebKit
 * public widget API with streamed (SSE) responses.
 */
(function () {
  "use strict";

  if (window.__pwkWidgetLoaded) return; // one widget per page
  window.__pwkWidgetLoaded = true;

  // ------------------------------------------------------------------
  // Locate our own <script> tag → bot id + API origin
  // ------------------------------------------------------------------
  var script =
    document.currentScript ||
    document.querySelector('script[data-bot-id][src*="widget.js"]');
  if (!script) return;

  var BOT_ID = script.getAttribute("data-bot-id");
  if (!BOT_ID) {
    console.warn("[PrimeWebKit] Missing data-bot-id attribute on the widget script tag.");
    return;
  }

  var API_ORIGIN;
  try {
    API_ORIGIN = new URL(script.src).origin;
  } catch (e) {
    API_ORIGIN = "https://api.primewebkit.com";
  }
  var API = API_ORIGIN + "/api/v1/widget/" + encodeURIComponent(BOT_ID);

  // ------------------------------------------------------------------
  // Visitor identity — stable fingerprint + per-bot session id.
  // Falls back to in-memory values when storage is blocked.
  // ------------------------------------------------------------------
  function randomId(len) {
    var out = "";
    try {
      var bytes = new Uint8Array(len / 2);
      crypto.getRandomValues(bytes);
      for (var i = 0; i < bytes.length; i++) out += ("0" + bytes[i].toString(16)).slice(-2);
    } catch (e) {
      while (out.length < len) out += Math.random().toString(16).slice(2);
      out = out.slice(0, len);
    }
    return out;
  }

  function persistent(key, generate) {
    try {
      var v = localStorage.getItem(key);
      if (!v) {
        v = generate();
        localStorage.setItem(key, v);
      }
      return v;
    } catch (e) {
      return generate();
    }
  }

  var FINGERPRINT = persistent("pwk_w_fp", function () { return randomId(32); });
  var SESSION_ID = persistent("pwk_w_sess_" + BOT_ID, function () { return randomId(32); });

  var transcriptKey = "pwk_w_msgs_" + BOT_ID;
  function loadTranscript() {
    try {
      return JSON.parse(sessionStorage.getItem(transcriptKey) || "[]");
    } catch (e) { return []; }
  }
  function saveTranscript(msgs) {
    try {
      sessionStorage.setItem(transcriptKey, JSON.stringify(msgs.slice(-40)));
    } catch (e) { /* storage unavailable — transcript just won't survive reloads */ }
  }

  // ------------------------------------------------------------------
  // Fetch config, then boot
  // ------------------------------------------------------------------
  fetch(API + "/config", { headers: { Accept: "application/json" } })
    .then(function (r) { return r.json().then(function (j) { return { status: r.status, j: j }; }); })
    .then(function (res) {
      var json = res.j;
      if (!json || !json.success || !json.data) {
        console.warn(
          "[PrimeWebKit] Widget config request failed (HTTP " + res.status + "): " +
          ((json && json.message) || "unknown error") +
          ". Common causes: the bot is not published (draft), or this domain isn't in the widget's allowed domains."
        );
        return;
      }
      var widget = json.data.widget || {};
      if (widget.is_active === false) {
        console.info("[PrimeWebKit] Widget is turned off in the dashboard (is_active = false).");
        return;
      }
      whenBodyReady(function () { boot(json.data.bot || {}, widget); });
    })
    .catch(function (e) {
      console.warn("[PrimeWebKit] Could not reach the widget API:", e && e.message ? e.message : e);
    });

  // The embed snippet may be placed in <head>; wait for <body> to exist
  // before mounting.
  function whenBodyReady(fn) {
    if (document.body) { fn(); return; }
    document.addEventListener("DOMContentLoaded", fn, { once: true });
  }

  // ------------------------------------------------------------------
  // UI
  // ------------------------------------------------------------------
  function boot(bot, widget) {
    var COLOR = widget.primary_color || bot.primary_color || "#0d9488";
    var DARK = widget.theme === "dark";
    var LEFT = widget.position === "bottom-left";
    var GREETING = widget.greeting_message || bot.welcome_message || "Hi! How can I help you today?";
    var PLACEHOLDER = widget.placeholder_text || "Type a message\u2026";
    var NAME = bot.name || "Chat";

    var host = document.createElement("div");
    host.id = "pwk-widget";
    var root = host.attachShadow ? host.attachShadow({ mode: "open" }) : host;

    var t = DARK
      ? { panel: "#171a21", header: COLOR, botBubble: "#262a33", botText: "#f1f2f6",
          userText: "#ffffff", input: "#1f232c", inputText: "#f1f2f6", border: "rgba(255,255,255,0.08)",
          muted: "#8a93a5", scroll: "rgba(255,255,255,0.15)" }
      : { panel: "#ffffff", header: COLOR, botBubble: "#f1f2f6", botText: "#1c1f26",
          userText: "#ffffff", input: "#ffffff", inputText: "#1c1f26", border: "rgba(0,0,0,0.08)",
          muted: "#8a93a5", scroll: "rgba(0,0,0,0.15)" };

    var css =
      ":host{all:initial}" +
      "*{box-sizing:border-box;margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif}" +
      ".pwk-btn{position:fixed;bottom:20px;" + (LEFT ? "left" : "right") + ":20px;z-index:2147483000;" +
        "width:56px;height:56px;border-radius:50%;border:none;cursor:pointer;background:" + COLOR + ";" +
        "box-shadow:0 6px 24px rgba(0,0,0,.28);display:flex;align-items:center;justify-content:center;" +
        "transition:transform .15s ease}" +
      ".pwk-btn:hover{transform:scale(1.06)}" +
      ".pwk-btn svg{width:26px;height:26px;fill:#fff}" +
      ".pwk-panel{position:fixed;bottom:90px;" + (LEFT ? "left" : "right") + ":20px;z-index:2147483000;" +
        "width:372px;max-width:calc(100vw - 32px);height:560px;max-height:calc(100vh - 120px);" +
        "background:" + t.panel + ";border-radius:16px;box-shadow:0 12px 48px rgba(0,0,0,.32);" +
        "display:none;flex-direction:column;overflow:hidden;border:1px solid " + t.border + "}" +
      ".pwk-panel.open{display:flex;animation:pwk-in .18s ease}" +
      "@keyframes pwk-in{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}" +
      "@media(max-width:480px){.pwk-panel{bottom:0;" + (LEFT ? "left" : "right") + ":0;width:100vw;max-width:100vw;" +
        "height:100dvh;max-height:100dvh;border-radius:0;border:none}}" +
      ".pwk-head{background:" + t.header + ";color:#fff;padding:14px 16px;display:flex;align-items:center;gap:10px;flex-shrink:0}" +
      ".pwk-head-name{font-size:15px;font-weight:600;flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}" +
      ".pwk-head-status{font-size:11px;opacity:.85}" +
      ".pwk-avatar{width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.22);" +
        "display:flex;align-items:center;justify-content:center;flex-shrink:0}" +
      ".pwk-avatar svg{width:18px;height:18px;fill:#fff}" +
      ".pwk-x{background:none;border:none;cursor:pointer;padding:6px;border-radius:8px;line-height:0}" +
      ".pwk-x:hover{background:rgba(255,255,255,.15)}" +
      ".pwk-x svg{width:18px;height:18px;stroke:#fff}" +
      ".pwk-msgs{flex:1;overflow-y:auto;padding:16px 14px;display:flex;flex-direction:column;gap:10px;" +
        "scrollbar-width:thin;scrollbar-color:" + t.scroll + " transparent}" +
      ".pwk-msgs::-webkit-scrollbar{width:6px}.pwk-msgs::-webkit-scrollbar-thumb{background:" + t.scroll + ";border-radius:3px}" +
      ".pwk-m{max-width:86%;padding:9px 13px;border-radius:14px;font-size:14px;line-height:1.5;" +
        "word-wrap:break-word;overflow-wrap:break-word;white-space:pre-wrap}" +
      ".pwk-m.bot{align-self:flex-start;background:" + t.botBubble + ";color:" + t.botText + ";border-bottom-left-radius:4px}" +
      ".pwk-m.user{align-self:flex-end;background:" + COLOR + ";color:" + t.userText + ";border-bottom-right-radius:4px}" +
      ".pwk-m.err{align-self:center;background:rgba(248,113,113,.12);color:#e05c5c;font-size:13px;text-align:center}" +
      ".pwk-m code{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12.5px;background:rgba(127,127,127,.18);" +
        "padding:1px 5px;border-radius:4px}" +
      ".pwk-m a{color:inherit;text-decoration:underline}" +
      ".pwk-typing{align-self:flex-start;display:flex;gap:4px;padding:12px 14px;background:" + t.botBubble + ";" +
        "border-radius:14px;border-bottom-left-radius:4px}" +
      ".pwk-typing span{width:7px;height:7px;border-radius:50%;background:" + t.muted + ";" +
        "animation:pwk-b 1.2s infinite ease-in-out}" +
      ".pwk-typing span:nth-child(2){animation-delay:.15s}.pwk-typing span:nth-child(3){animation-delay:.3s}" +
      "@keyframes pwk-b{0%,60%,100%{transform:translateY(0);opacity:.5}30%{transform:translateY(-5px);opacity:1}}" +
      ".pwk-foot{flex-shrink:0;border-top:1px solid " + t.border + ";padding:10px 12px;background:" + t.panel + "}" +
      ".pwk-row{display:flex;gap:8px;align-items:flex-end}" +
      ".pwk-in{flex:1;resize:none;border:1px solid " + t.border + ";background:" + t.input + ";color:" + t.inputText + ";" +
        "border-radius:12px;padding:10px 12px;font-size:14px;line-height:1.45;max-height:110px;outline:none}" +
      ".pwk-in:focus{border-color:" + COLOR + "}" +
      ".pwk-in::placeholder{color:" + t.muted + "}" +
      ".pwk-send{width:38px;height:38px;flex-shrink:0;border-radius:50%;border:none;cursor:pointer;background:" + COLOR + ";" +
        "display:flex;align-items:center;justify-content:center;transition:opacity .15s}" +
      ".pwk-send:disabled{opacity:.45;cursor:default}" +
      ".pwk-send svg{width:17px;height:17px;fill:#fff}" +
      ".pwk-brand{text-align:center;font-size:10.5px;color:" + t.muted + ";padding:6px 0 2px}" +
      ".pwk-brand a{color:" + t.muted + ";text-decoration:none}" +
      "@media(prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}" +
      (widget.custom_css || "");

    var chatIcon = '<svg viewBox="0 0 24 24"><path d="M12 3C6.5 3 2 6.9 2 11.7c0 2.7 1.4 5.1 3.6 6.7-.1.9-.5 2.3-1.5 3.6 0 0 2.6-.3 4.6-1.7 1 .3 2.1.5 3.3.5 5.5 0 10-3.9 10-8.7S17.5 3 12 3z"/></svg>';
    var closeIcon = '<svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>';
    var sendIcon = '<svg viewBox="0 0 24 24"><path d="M3.4 20.4l17.4-7.5c.8-.4.8-1.5 0-1.8L3.4 3.6c-.7-.3-1.4.3-1.4 1l0 4.6c0 .5.4.9.8 1l11.2 1.8L2.8 13.8c-.5.1-.8.5-.8 1l0 4.6c0 .7.7 1.3 1.4 1z"/></svg>';

    root.innerHTML =
      "<style>" + css + "</style>" +
      '<button class="pwk-btn" type="button" aria-label="Open chat">' + chatIcon + "</button>" +
      '<div class="pwk-panel" role="dialog" aria-label="' + escapeAttr(NAME) + '">' +
        '<div class="pwk-head">' +
          '<div class="pwk-avatar">' + chatIcon + "</div>" +
          '<div style="flex:1;min-width:0">' +
            '<div class="pwk-head-name">' + escapeHtml(NAME) + "</div>" +
            '<div class="pwk-head-status">Online</div>' +
          "</div>" +
          '<button class="pwk-x" type="button" aria-label="Close chat">' + closeIcon + "</button>" +
        "</div>" +
        '<div class="pwk-msgs"></div>' +
        '<div class="pwk-foot">' +
          '<div class="pwk-row">' +
            '<textarea class="pwk-in" rows="1" placeholder="' + escapeAttr(PLACEHOLDER) + '" aria-label="Message"></textarea>' +
            '<button class="pwk-send" type="button" aria-label="Send">' + sendIcon + "</button>" +
          "</div>" +
          (widget.show_branding !== false
            ? '<div class="pwk-brand"><a href="https://api.primewebkit.com/portal/" target="_blank" rel="noopener">Powered by PrimeWebKit</a></div>'
            : "") +
        "</div>" +
      "</div>";

    document.body.appendChild(host);

    var btn = root.querySelector(".pwk-btn");
    var panel = root.querySelector(".pwk-panel");
    var closeBtn = root.querySelector(".pwk-x");
    var msgsEl = root.querySelector(".pwk-msgs");
    var input = root.querySelector(".pwk-in");
    var sendBtn = root.querySelector(".pwk-send");

    var messages = loadTranscript();
    var streaming = false;

    if (messages.length === 0) {
      messages.push({ role: "assistant", content: GREETING });
    }
    renderAll();

    btn.addEventListener("click", function () {
      var open = panel.classList.toggle("open");
      btn.setAttribute("aria-label", open ? "Close chat" : "Open chat");
      if (open) {
        scrollDown();
        input.focus();
      }
    });
    closeBtn.addEventListener("click", function () {
      panel.classList.remove("open");
      btn.setAttribute("aria-label", "Open chat");
    });

    input.addEventListener("input", autoGrow);
    input.addEventListener("keydown", function (e) {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        send();
      }
    });
    sendBtn.addEventListener("click", send);

    function autoGrow() {
      input.style.height = "auto";
      input.style.height = Math.min(input.scrollHeight, 110) + "px";
    }

    // ----------------------------------------------------------------
    // Rendering
    // ----------------------------------------------------------------
    function escapeHtml(s) {
      return String(s).replace(/[&<>"']/g, function (c) {
        return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
      });
    }
    function escapeAttr(s) { return escapeHtml(s); }

    // Tiny, safe formatter: escape first, then bold / italics / inline
    // code / bare links. Enough for typical model output without
    // pulling in a markdown library.
    function formatMessage(s) {
      var h = escapeHtml(s);
      h = h.replace(/`([^`\n]+)`/g, "<code>$1</code>");
      h = h.replace(/\*\*([^*\n]+)\*\*/g, "<b>$1</b>");
      h = h.replace(/(^|[^*])\*([^*\n]+)\*(?!\*)/g, "$1<i>$2</i>");
      h = h.replace(/(https?:\/\/[^\s<]+[^\s<.,)\]])/g, '<a href="$1" target="_blank" rel="noopener nofollow">$1</a>');
      return h;
    }

    function bubble(role, html) {
      var div = document.createElement("div");
      div.className = "pwk-m " + (role === "user" ? "user" : role === "error" ? "err" : "bot");
      div.innerHTML = html;
      msgsEl.appendChild(div);
      return div;
    }

    function renderAll() {
      msgsEl.innerHTML = "";
      for (var i = 0; i < messages.length; i++) {
        bubble(messages[i].role, formatMessage(messages[i].content));
      }
      scrollDown();
    }

    function scrollDown() {
      msgsEl.scrollTop = msgsEl.scrollHeight;
    }

    function typingEl() {
      var el = document.createElement("div");
      el.className = "pwk-typing";
      el.innerHTML = "<span></span><span></span><span></span>";
      msgsEl.appendChild(el);
      scrollDown();
      return el;
    }

    // ----------------------------------------------------------------
    // Send + stream
    // ----------------------------------------------------------------
    function send() {
      var text = input.value.trim();
      if (!text || streaming) return;

      input.value = "";
      autoGrow();
      streaming = true;
      sendBtn.disabled = true;

      messages.push({ role: "user", content: text });
      saveTranscript(messages);
      bubble("user", formatMessage(text));
      scrollDown();

      var typing = typingEl();
      var replyEl = null;
      var replyText = "";

      // Content-Type text/plain keeps this a CORS "simple request" —
      // no OPTIONS preflight, so it works even behind strict
      // WAFs/proxies. The backend parses the JSON body regardless of
      // the content type.
      fetch(API + "/messages/stream", {
        method: "POST",
        headers: { "Content-Type": "text/plain", Accept: "text/event-stream" },
        body: JSON.stringify({ session_id: SESSION_ID, fingerprint: FINGERPRINT, message: text }),
      })
        .then(function (res) {
          if (!res.ok || !res.body) {
            // Non-stream failure (rate limit, plan limit, blocked domain…)
            return res.json().then(
              function (j) { throw new Error((j && j.message) || "The assistant is unavailable right now."); },
              function () { throw new Error("The assistant is unavailable right now."); }
            );
          }
          var reader = res.body.getReader();
          var decoder = new TextDecoder();
          var buffer = "";

          function pump() {
            return reader.read().then(function (chunk) {
              if (chunk.done) return finish(null);
              // Normalize CRLF so framing works regardless of the
              // server's line-ending convention.
              buffer = (buffer + decoder.decode(chunk.value, { stream: true })).replace(/\r\n/g, "\n");

              // SSE frames are separated by a blank line
              var frames = buffer.split("\n\n");
              buffer = frames.pop();

              for (var i = 0; i < frames.length; i++) {
                var dataLines = frames[i]
                  .split("\n")
                  .filter(function (l) { return l.indexOf("data:") === 0; })
                  .map(function (l) { return l.slice(5).replace(/^ /, ""); });
                if (!dataLines.length) continue;

                var payload;
                try { payload = JSON.parse(dataLines.join("\n")); } catch (e) { continue; }

                if (payload.error) return finish(payload.message || "Something went wrong generating a reply.");
                if (payload.delta) {
                  if (!replyEl) {
                    typing.remove();
                    replyEl = bubble("assistant", "");
                  }
                  replyText += payload.delta;
                  replyEl.innerHTML = formatMessage(replyText);
                  scrollDown();
                }
                if (payload.done) return finish(null);
              }
              return pump();
            });
          }
          return pump();
        })
        .catch(function (err) {
          finish(err && err.message ? err.message : "Couldn't reach the assistant. Please try again.");
        });

      function finish(errorMessage) {
        if (typing.parentNode) typing.remove();
        if (errorMessage) {
          bubble("error", escapeHtml(errorMessage));
        } else if (replyText) {
          messages.push({ role: "assistant", content: replyText });
          saveTranscript(messages);
        } else if (!replyEl) {
          bubble("error", "No reply was generated. Please try again.");
        }
        streaming = false;
        sendBtn.disabled = false;
        scrollDown();
        input.focus();
      }
    }
  }
})();

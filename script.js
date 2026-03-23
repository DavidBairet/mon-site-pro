// ==============================
// FORMULAIRE DEVIS
// ==============================

const form = document.getElementById("devis-form");

if (form) {
  const message = document.getElementById("form-message");
  const button = form.querySelector("button");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    button.textContent = "Envoi en cours...";
    button.disabled = true;

    const formData = new FormData(form);

    try {
      const response = await fetch("send-devis.php", {
        method: "POST",
        body: formData,
      });

      if (response.redirected) {
        window.location.href = response.url;
        return;
      }

      const text = await response.text();

      if (response.ok) {
        form.reset();

        if (message) {
          message.style.display = "block";
          message.textContent = "Message envoyé avec succès ✅";
        }

        button.textContent = "Envoyé ✅";
      } else {
        if (message) {
          message.style.display = "block";
          message.textContent = text || "Erreur lors de l’envoi ❌";
        }

        button.textContent = "Erreur ❌";
        button.disabled = false;
      }
    } catch (error) {
      if (message) {
        message.style.display = "block";
        message.textContent = "Erreur réseau ❌";
      }

      button.textContent = "Erreur ❌";
      button.disabled = false;
    }
  });
}

// ==============================
// BANNIÈRE COOKIES
// ==============================

const cookieBanner = document.getElementById("cookie-banner");
const acceptBtn = document.getElementById("cookie-accept");
const refuseBtn = document.getElementById("cookie-refuse");

const COOKIE_KEY = "lsd-cookie-consent";

// 👉 si tu ajoutes Google Analytics plus tard
const GA_ID = "G-XXXXXXXXXX";

function loadAnalytics() {
  if (!GA_ID || GA_ID === "G-XXXXXXXXXX") return;

  if (window.__gaLoaded) return;
  window.__gaLoaded = true;

  const script = document.createElement("script");
  script.async = true;
  script.src = `https://www.googletagmanager.com/gtag/js?id=${GA_ID}`;
  document.head.appendChild(script);

  window.dataLayer = window.dataLayer || [];
  function gtag() {
    dataLayer.push(arguments);
  }
  window.gtag = gtag;

  gtag("js", new Date());
  gtag("config", GA_ID);
}

function setConsent(value) {
  localStorage.setItem(COOKIE_KEY, value);
  cookieBanner.hidden = true;

  if (value === "accepted") {
    loadAnalytics();
  }
}

function initCookies() {
  if (!cookieBanner) return;

  const consent = localStorage.getItem(COOKIE_KEY);

  if (consent === "accepted") {
    cookieBanner.hidden = true;
    loadAnalytics();
    return;
  }

  if (consent === "refused") {
    cookieBanner.hidden = true;
    return;
  }

  cookieBanner.hidden = false;

  acceptBtn?.addEventListener("click", () => setConsent("accepted"));
  refuseBtn?.addEventListener("click", () => setConsent("refused"));
}

document.addEventListener("DOMContentLoaded", initCookies);
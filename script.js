const form = document.getElementById("devis-form");
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

    if (response.ok) {
      form.reset();
      message.style.display = "block";
      button.textContent = "Envoyé ✅";
    } else {
      button.textContent = "Erreur ❌";
    }
  } catch (error) {
    button.textContent = "Erreur ❌";
  }
});
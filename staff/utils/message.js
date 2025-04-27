function handleSuccessOrErrorModal() {
  const urlParams = new URLSearchParams(window.location.search);
  const success = urlParams.get("success");
  const error = urlParams.get("error");

  if (success || error) {
    const modal = document.createElement("div");
    modal.className = "modal fade";
    modal.id = "feedbackModal";
    modal.tabIndex = -1;
    modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header ${
                      success ? "bg-success" : "bg-danger"
                    } text-white">
                        <h5 class="modal-title">${
                          success ? "Success" : "Error"
                        }</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>${
                          success
                            ? "Action completed successfully!"
                            : "An error occurred. Please try again."
                        }</p>
                    </div>
                </div>
            </div>
        `;
    document.body.appendChild(modal);

    const bootstrapModal = new bootstrap.Modal(
      document.getElementById("feedbackModal")
    );
    bootstrapModal.show();

    setTimeout(() => {
      bootstrapModal.hide();
      const url = new URL(window.location);
      url.searchParams.delete("success");
      url.searchParams.delete("error");
      window.history.replaceState(
        {},
        document.title,
        url.pathname + url.search
      );

      setTimeout(() => {
        document.getElementById("feedbackModal")?.remove();
      }, 500);
    }, 3000);
  }
}

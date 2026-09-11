/**
 * Interactive Media Overlay Script Engine
 * Vanilla DOM JavaScript Component
 */
document.addEventListener("DOMContentLoaded", () => {
    const triggers = document.querySelectorAll(".gallery-trigger");
    const modal = document.getElementById("lightbox-modal");
    const modalImg = document.getElementById("modal-expanded-img");
    const closeBtn = document.getElementById("modal-close-btn");

    if (triggers.length > 0 && modal && modalImg && closeBtn) {
        
        // Loop triggers execution tracking
        triggers.forEach(trigger => {
            trigger.addEventListener("click", () => {
                const targetThumb = trigger.querySelector(".gallery-thumb");
                if (targetThumb) {
                    // Extract source and map to expanded canvas element
                    const sourceUrl = targetThumb.getAttribute("src");
                    const descriptiveAlt = targetThumb.getAttribute("alt");
                    
                    modalImg.setAttribute("src", sourceUrl);
                    modalImg.setAttribute("alt", descriptiveAlt);
                    
                    // Modify ARIA access parameters smoothly
                    modal.style.display = "flex";
                    modal.setAttribute("aria-hidden", "false");
                    closeBtn.focus();
                }
            });
        });

        // Event listener tracking close window click execution routine
        const dismissModal = () => {
            modal.style.display = "none";
            modal.setAttribute("aria-hidden", "true");
        };

        closeBtn.addEventListener("click", dismissModal);

        // Closes window safely if visitor clicks transparent backdrop
        modal.addEventListener("click", (e) => {
            if (e.target === modal) {
                dismissModal();
            }
        });

        // Accessibility Escape keyboard intercept engine
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && modal.getAttribute("aria-hidden") === "false") {
                dismissModal();
            }
        });
    }
});
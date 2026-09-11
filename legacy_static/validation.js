/**
 * Client-Side Input Validation Architecture
 * Pure JavaScript Framework - Zero PHP dependencies
 */
document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("inquiry-form");
    
    if (form) {
        form.addEventListener("submit", (event) => {
            // Suppress standard destructive action page resets
            event.preventDefault();
            
            // Extract input tracking nodes
            const nameInput = document.getElementById("client-name");
            const emailInput = document.getElementById("client-email");
            const messageInput = document.getElementById("client-message");
            
            // Extract UI feedback target spans
            const nameError = document.getElementById("name-error");
            const emailError = document.getElementById("email-error");
            const messageError = document.getElementById("message-error");
            const successPane = document.getElementById("global-success-pane");
            
            // Reset visibility flags
            nameError.textContent = "";
            emailError.textContent = "";
            messageError.textContent = "";
            successPane.style.display = "none";
            successPane.textContent = "";

            let isFormValid = true;

            // 1. Check Full Name Input Validation
            if (!nameInput.value || nameInput.value.trim() === "") {
                nameError.textContent = "Error: Full name is required and cannot be blank.";
                isFormValid = false;
            }

            // 2. Check Email Input Validation via Standard Regex Checks
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailInput.value || emailInput.value.trim() === "") {
                emailError.textContent = "Error: Email address is required.";
                isFormValid = false;
            } else if (!emailRegex.test(emailInput.value.trim())) {
                emailError.textContent = "Error: Please enter a valid, operational email structure.";
                isFormValid = false;
            }

            // 3. Check Message Body Text Area Processing
            if (!messageInput.value || messageInput.value.trim() === "") {
                messageError.textContent = "Error: Inquiry details text cannot be left empty.";
                isFormValid = false;
            }

            // Execute final submission feedback loop if tracking variables pass checks
            if (isFormValid) {
                successPane.textContent = `Thank you, ${nameInput.value.trim()}! Your inquiry was validated locally and processed successfully.`;
                successPane.style.display = "block";
                
                // Clear fields safely
                form.reset();
            }
        });
    }
});
document.getElementById("showPass").addEventListener("click", function() {
            const pw = document.getElementById("password");
            pw.type = this.checked ? "text" : "password";
        });

document.getElementById('signupForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Stop page reload

    const formData = new FormData(this);

    fetch('signup.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        const feedback = document.getElementById('feedback');
        feedback.textContent = data;
        feedback.style.color = data.includes("successfully") ? "green" : "red";
    })
    .catch(error => {
        const feedback = document.getElementById('feedback');
        feedback.textContent = "Error: " + error;
        feedback.style.color = "red";
    });
});
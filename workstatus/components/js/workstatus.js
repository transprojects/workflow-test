document.addEventListener("submit", function(e) {
    const btn = document.getElementById("submit-btn");
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
});

document.addEventListener("DOMContentLoaded", () => {
    const button = document.querySelector(".test__btn");

    button.addEventListener("mouseover", () => {
        button.style.boxShadow = "0 0 15px rgba(251, 216, 70, 0.8)";
    });

    button.addEventListener("mouseleave", () => {
        button.style.boxShadow = "none";
    });
});

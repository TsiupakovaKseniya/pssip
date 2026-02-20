document.querySelector(".first-screen__btn").addEventListener("click", function () {
    this.classList.add("bounce");

    setTimeout(() => {
        this.classList.remove("bounce");
    }, 400); // Удаляем эффект через 400 мс
});

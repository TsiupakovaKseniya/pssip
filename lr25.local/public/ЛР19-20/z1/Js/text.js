document.addEventListener("DOMContentLoaded", () => {
    const title = document.querySelector(".choice__title");

    title.addEventListener("mouseover", () => {
        title.style.transform = "rotate(360deg)";
        title.style.color = "rgb(153, 203, 56)";
    });

    title.addEventListener("mouseleave", () => {
        title.style.transform = "rotate(0deg)"; // Возвращаем в обычное состояние
        title.style.color = ""; // Возвращаем цвет по умолчанию из CSS
    });
});

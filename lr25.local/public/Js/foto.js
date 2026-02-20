document.querySelectorAll(".tabs-content__card img").forEach((img) => {
    img.addEventListener("mouseover", () => {
        img.style.transform = "scale(1.2)";
    });

    img.addEventListener("mouseleave", () => {
        img.style.transform = "scale(1)";
    });
});

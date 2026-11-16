document.addEventListener("DOMContentLoaded", function () {
    // Counter animation effect for stats preview
    const statElements = document.querySelectorAll(".stat-number");
    
    statElements.forEach(el => {
        let target = parseInt(el.getAttribute("data-target"));
        let count = 0;
        let speed = target / 50;

        function updateCount() {
            count += speed;
            if (count < target) {
                el.innerText = Math.ceil(count) + (target === 85 ? "%" : "");
                setTimeout(updateCount, 30);
            } else {
               
                if (target === 85) {
                    el.innerText = target + "%+";
                } else {
                    el.innerText = target;
                }
            }
        }
        updateCount();
    });
});
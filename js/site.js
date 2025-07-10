document.addEventListener("DOMContentLoaded", function () {
    const loginBtn = document.querySelector(".login");
    const signupBtn = document.querySelector(".signup");

    if (loginBtn) {
        loginBtn.addEventListener("mouseover", () => {
            loginBtn.style.backgroundColor = "#065f46";
            loginBtn.style.color = "#fff";
        });

        loginBtn.addEventListener("mouseout", () => {
            loginBtn.style.backgroundColor = "#004d3f";
            loginBtn.style.color = "#fff";
        });
    }

    if (signupBtn) {
        signupBtn.addEventListener("mouseover", () => {
            signupBtn.style.opacity = 0.8;
        });

        signupBtn.addEventListener("mouseout", () => {
            signupBtn.style.opacity = 1;
        });}
});
document.addEventListener('DOMContentLoaded', () => {
  const um = document.querySelector('.user-menu');
  um.addEventListener('click', e => {
    e.stopPropagation();
    um.classList.toggle('open');
  });
  document.addEventListener('click', () => um.classList.remove('open'));
});

const form = document.getElementById("loginForm");
const email = document.getElementById("email");
const password = document.getElementById("password");
const togglePassword = document.getElementById("togglePassword");
const status = document.getElementById("loginStatus");

togglePassword.addEventListener("click", () => {
  const visible = password.type === "password";
  password.type = visible ? "text" : "password";
  togglePassword.textContent = visible ? "Ẩn" : "Hiện";
  togglePassword.setAttribute("aria-label", visible ? "Ẩn mật khẩu" : "Hiện mật khẩu");
  togglePassword.setAttribute("aria-pressed", String(visible));
});

for (const input of [email, password]) {
  input.addEventListener("input", () => {
    input.removeAttribute("aria-invalid");
    document.getElementById(`${input.id}Error`).textContent = "";
    status.textContent = "";
  });
}

form.addEventListener("submit", async (event) => {
  event.preventDefault();
  email.value = email.value.trim();
  const errors = [
    [email, email.validity.valueMissing ? "Vui lòng nhập địa chỉ email." : email.validity.typeMismatch ? "Vui lòng nhập địa chỉ email hợp lệ." : ""],
    [password, password.validity.valueMissing ? "Vui lòng nhập mật khẩu." : ""]
  ];

  for (const [input, message] of errors) {
    document.getElementById(`${input.id}Error`).textContent = message;
    input.setAttribute("aria-invalid", String(Boolean(message)));
  }

  status.textContent = "";
  const firstError = errors.find(([, message]) => message);
  if (firstError) {
    firstError[0].focus();
    return;
  }

  const button = form.querySelector('[type="submit"]');
  button.disabled = true;
  status.textContent = "Đang đăng nhập…";
  try {
    const sessionResponse = await fetch('../../backend/auth.php');
    const session = await sessionResponse.json();
    if (!sessionResponse.ok) throw new Error(session.error);
    const response = await fetch('../../backend/auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': session.csrf },
      body: JSON.stringify({ email: email.value, password: password.value })
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error);
    location.href = result.role === 'quan_tri' ? 'admin.php' : 'index.php';
  } catch (error) {
    status.textContent = error.message || 'Không thể đăng nhập. Hãy mở website qua máy chủ PHP.';
  } finally { button.disabled = false; }
});

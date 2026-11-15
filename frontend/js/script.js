const defaultProducts = [
  {id:1, name:"Fresh Broccoli", category:"Vegetables", price:4.90, oldPrice:6.00, emoji:"🥦", discount:"18% OFF"},
  {id:2, name:"Organic Carrot", category:"Vegetables", price:3.40, oldPrice:4.20, emoji:"🥕", discount:"15% OFF"},
  {id:3, name:"Sweet Strawberry", category:"Fruits", price:5.80, oldPrice:7.00, emoji:"🍓", discount:"17% OFF"},
  {id:4, name:"Green Avocado", category:"Fruits", price:6.20, oldPrice:7.50, emoji:"🥑", discount:"12% OFF"},
  {id:5, name:"Organic Milk", category:"Dairy", price:3.90, oldPrice:4.50, emoji:"🥛", discount:"10% OFF"},
  {id:6, name:"Fresh Croissant", category:"Bakery", price:2.80, oldPrice:3.40, emoji:"🥐", discount:"8% OFF"},
  {id:7, name:"Orange Juice", category:"Drinks", price:4.60, oldPrice:5.50, emoji:"🧃", discount:"14% OFF"},
  {id:8, name:"Red Apple", category:"Fruits", price:4.20, oldPrice:5.00, emoji:"🍎", discount:"10% OFF"}
];

let products = [];
let cart = [];
let currentFilter = "All";
let searchTerm = "";

const productGrid = document.getElementById("productGrid");
const cartCount = document.getElementById("cartCount");
const cartTotal = document.getElementById("cartTotal");
const panelCartTotal = document.getElementById("panelCartTotal");
const cartItems = document.getElementById("cartItems");
const cartPanel = document.getElementById("cartPanel");
const overlay = document.getElementById("overlay");

function money(value){
  return `$${value.toFixed(2)}`;
}

function parsePrice(text, fallback = 0){
  const num = parseFloat(String(text || "").replace(/[^0-9.]/g, ""));
  return Number.isFinite(num) ? num : fallback;
}

function initProductsFromDom(){
  if(!productGrid) return;
  const cards = Array.from(productGrid.querySelectorAll(".product-card"));
  if(cards.length === 0){
    productGrid.innerHTML = defaultProducts.map(p => `
      <article class="product-card">
        <div class="product-image">
          <span class="discount">${p.discount}</span>
          <span>${p.emoji}</span>
        </div>
        <div class="product-info">
          <span class="product-category">${p.category}</span>
          <h3 class="product-title">${p.name}</h3>
          <div class="rating">★★★★★ <span style="color:#9a9a9a">(4.8)</span></div>
          <div class="product-bottom">
            <div>
              <span class="price">${money(p.price)}</span>
              <span class="old-price">${money(p.oldPrice)}</span>
            </div>
            <button class="add-btn" type="button">+</button>
          </div>
        </div>
      </article>
    `).join("");
  }

  products = Array.from(productGrid.querySelectorAll(".product-card")).map((card, index) => {
    const id = index + 1;
    const titleEl = card.querySelector(".product-title, h3");
    const categoryEl = card.querySelector(".product-category");
    const priceEl = card.querySelector(".price");
    const oldPriceEl = card.querySelector(".old-price");
    const discountEl = card.querySelector(".discount");
    const imageSpans = card.querySelectorAll(".product-image span");
    const emojiEl = imageSpans.length > 1 ? imageSpans[imageSpans.length - 1] : imageSpans[0];
    const addBtn = card.querySelector(".add-btn, button");
    if(addBtn){
      addBtn.removeAttribute("onclick");
      addBtn.addEventListener("click", () => addToCart(id));
    }
    return {
      id,
      element: card,
      name: (titleEl?.textContent || `Product ${id}`).trim(),
      category: (categoryEl?.textContent || "All").trim(),
      price: parsePrice(priceEl?.textContent, 0),
      oldPrice: parsePrice(oldPriceEl?.textContent, 0),
      emoji: (emojiEl?.textContent || "🥗").trim(),
      discount: (discountEl?.textContent || "").trim()
    };
  });
}

function renderProducts(){
  if(!productGrid) return;
  let visibleCount = 0;
  products.forEach(p => {
    const matchCategory = currentFilter === "All" || p.category.toLowerCase() === currentFilter.toLowerCase();
    const matchSearch = p.name.toLowerCase().includes(searchTerm.toLowerCase());
    const show = matchCategory && matchSearch;
    p.element.style.display = show ? "" : "none";
    if(show) visibleCount++;
  });

  let emptyMsg = document.getElementById("emptyProductMessage");
  if(visibleCount === 0){
    if(!emptyMsg){
      emptyMsg = document.createElement("div");
      emptyMsg.id = "emptyProductMessage";
      emptyMsg.style.cssText = "grid-column:1/-1;text-align:center;padding:40px;color:#7d838c";
      emptyMsg.textContent = "Không tìm thấy sản phẩm phù hợp.";
      productGrid.appendChild(emptyMsg);
    }
    emptyMsg.style.display = "";
  } else if(emptyMsg){
    emptyMsg.style.display = "none";
  }
}

function addToCart(id){
  const product = products.find(p => p.id === id);
  if(!product) return;
  const existing = cart.find(item => item.id === id);

  if(existing){
    existing.qty += 1;
  }else{
    cart.push({...product, qty:1});
  }

  updateCart();
  openCart();
}

function removeFromCart(id){
  cart = cart.filter(item => item.id !== id);
  updateCart();
}

function updateCart(){
  const count = cart.reduce((sum,item) => sum + item.qty,0);
  const total = cart.reduce((sum,item) => sum + item.price * item.qty,0);

  if(cartCount) cartCount.textContent = count;
  if(cartTotal) cartTotal.textContent = money(total);
  if(panelCartTotal) panelCartTotal.textContent = money(total);

  if(!cartItems) return;
  if(cart.length === 0){
    cartItems.innerHTML = `<p class="empty-cart">Your cart is empty.</p>`;
    return;
  }

  cartItems.innerHTML = cart.map(item => `
    <div class="cart-row">
      <div class="emoji">${item.emoji}</div>
      <div>
        <strong>${item.name}</strong><br>
        <small>${item.qty} × ${money(item.price)}</small>
      </div>
      <button class="remove-btn" onclick="removeFromCart(${item.id})">✕</button>
    </div>
  `).join("");
}

function setFilter(filter){
  currentFilter = filter;

  document.querySelectorAll(".tab").forEach(btn => {
    btn.classList.toggle("active", btn.dataset.filter === filter);
  });

  renderProducts();
}

document.querySelectorAll(".tab").forEach(btn => {
  btn.addEventListener("click", () => setFilter(btn.dataset.filter || "All"));
});

document.querySelectorAll(".category-card").forEach(btn => {
  btn.addEventListener("click", () => {
    setFilter(btn.dataset.filter || "All");
    document.getElementById("products")?.scrollIntoView({behavior:"smooth"});
  });
});

document.getElementById("searchInput")?.addEventListener("input", e => {
  searchTerm = e.target.value.trim();
  renderProducts();
});

document.getElementById("searchBtn")?.addEventListener("click", () => {
  searchTerm = (document.getElementById("searchInput")?.value || "").trim();
  renderProducts();
  document.getElementById("products")?.scrollIntoView({behavior:"smooth"});
});

document.getElementById("categoryBtn")?.addEventListener("click", () => {
  document.getElementById("categoryDropdown")?.classList.toggle("open");
});

document.getElementById("menuToggle")?.addEventListener("click", () => {
  document.getElementById("navLinks")?.classList.toggle("open");
});

document.querySelectorAll(".nav-links a").forEach(link => {
  link.addEventListener("click", () => {
    document.querySelectorAll(".nav-links a").forEach(a => a.classList.remove("active"));
    link.classList.add("active");
    document.getElementById("navLinks")?.classList.remove("open");
  });
});

function openCart(){
  cartPanel?.classList.add("open");
  overlay?.classList.add("show");
}

function closeCart(){
  cartPanel?.classList.remove("open");
  overlay?.classList.remove("show");
}

document.getElementById("cartButton")?.addEventListener("click", openCart);
document.getElementById("closeCart")?.addEventListener("click", closeCart);
overlay?.addEventListener("click", closeCart);

initProductsFromDom();
renderProducts();
updateCart();

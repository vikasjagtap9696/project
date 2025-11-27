
function showMessage(msg, type = 'success') {
  const msgEl = document.getElementById('ajax-message');
  msgEl.innerHTML = msg;
  msgEl.style.display = 'block'; 
  msgEl.classList.remove('success', 'error'); 
  msgEl.classList.add(type); 
  msgEl.classList.add('show'); 

  if (type === 'success') {
    msgEl.style.backgroundColor = '#e6f7d9';
    msgEl.style.color = '#4b8b4c';
    msgEl.style.borderLeftColor = '#4b8b4c';
  } else if (type === 'error') {
    msgEl.style.backgroundColor = '#f7e6e6';
    msgEl.style.color = '#8d4b4b';
    msgEl.style.borderLeftColor = '#8d4b4b';
  }

  setTimeout(() => {
    msgEl.classList.remove('show');
    setTimeout(() => { msgEl.style.display = 'none'; }, 500);
  }, 3000);
}

['metal', 'style', 'occasion', 'collection'].forEach(id => {
  const element = document.getElementById(id);

  if (element) {
    element.addEventListener('change', () => {
      filterProducts();
    });
  }
});

const searchInput = document.getElementById('search');
if (searchInput) {
  searchInput.addEventListener('keyup', debounce(() => {
    filterProducts();
    
  }, 2000));
}

function debounce(func, delay) {
  let timeout;
  return function () {
    const context = this;
    const args = arguments;
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(context, args), delay);
  };
}

function filterProducts() {
  let params = new URLSearchParams();
  const fields = [
    { id: 'search', key: 'q' },
    { id: 'metal', key: 'metal_type' },
    { id: 'style', key: 'design_style' },
    { id: 'occasion', key: 'occasion' },
    { id: 'collection', key: 'collection_key' }
  ];

  fields.forEach(field => {
    const element = document.getElementById(field.id);
    const value = element ? element.value.trim() : '';
    if (value) { params.set(field.key, value); }
  });

  window.location.href = 'products.php?' + params.toString();
}

document.querySelectorAll('.fav-icon').forEach(el => {
  el.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();

    let card = el.closest('.product-card');
    let pid = card.dataset.id;

    el.style.pointerEvents = 'none'; 

    fetch('index1.php?fav_id=' + pid)
      .then(res => res.json())
      .then(data => {
        if (data.status === 'added') {
          el.style.color = 'red';
          el.classList.add('is-favorite');
          showMessage(data.message);
        } else if (data.status === 'removed') {
          el.style.color = 'white';
          el.classList.remove('is-favorite');
          showMessage(data.message);
        } else {
          showMessage(data.message, 'error');
        }
      })
      .catch(() => showMessage("❌ Network error. Please try again.", 'error'))
      .finally(() => {
        el.style.pointerEvents = 'auto'; 
      });
  });
});

// --- AJAX Add to Cart Handler ---
document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
  btn.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();

    const productId = btn.dataset.id;
    btn.disabled = true; 

    fetch(`index1.php?add_to_cart_ajax=${productId}`)
      .then(res => res.json())
      .then(data => {
        btn.disabled = false;

        if (data.status === 'success') {
          showMessage(data.message);

          const count = data.cart_count;
          const navLink = document.getElementById('cart-nav-link');

          if (navLink) {
            let navBadge = navLink.querySelector('.cart-badge');

            if (!navBadge) {
              navBadge = document.createElement('span');
              navBadge.className = 'cart-badge';
              navBadge.style.cssText =
                'position: static; margin-left: 5px; background: none; color: var(--primary-color); font-weight: 700;';
              navLink.appendChild(navBadge);
            }
            navBadge.textContent = count;
          }
        } else {
          showMessage(data.message, 'error');
        }
      })
      .catch(() => {
        btn.disabled = false;
        showMessage("❌ Network error: Could not add to cart.", 'error');
      });
  });
});

document.querySelectorAll('.product-card').forEach(card => {
  let allImages = JSON.parse(card.getAttribute('data-gallery') || '[]') || [];

  allImages = allImages.filter(url => url && url.trim() !== '');

  if (allImages.length <= 1) return;

  let index = 0;
  let mainImg = card.querySelector('.main-img');
  let prev = card.querySelector('.prev');
  let next = card.querySelector('.next');
  let slideInterval;

  const updateImage = (newIndex) => {
    index = (newIndex + allImages.length) % allImages.length;
    mainImg.src = allImages[index];
  };

  const startSlide = () => {
    clearInterval(slideInterval);
    slideInterval = setInterval(() => { updateImage(index + 1); }, 2000);
  };

  const stopSlide = () => {
    clearInterval(slideInterval);
  };

  card.addEventListener('mouseenter', startSlide);
  card.addEventListener('mouseleave', stopSlide);

  if (prev) {
    prev.addEventListener('click', (e) => {
      e.stopPropagation();
      stopSlide();
      updateImage(index - 1);
    });
  }
  if (next) {
    next.addEventListener('click', (e) => {
      e.stopPropagation();
      stopSlide();
      updateImage(index + 1);
    });
  }
});


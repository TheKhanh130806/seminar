/* GrapesJS project JSON preserves editor metadata; HTML/CSS is used for publishing the full page. */
const message = document.getElementById('editor-status');
const saveButton = document.getElementById('save');
const publishButton = document.getElementById('publish');
const resetButton = document.getElementById('reset');
const assetsButton = document.getElementById('assets');
let editor, token, savedPage = null, template, busy = false, needsSave = false;
const requiredIds = ['productGrid', 'cartPanel', 'cartItems', 'cartCount', 'cartTotal', 'panelCartTotal', 'closeCart', 'overlay'];

async function request(url, options = {}) {
  const response = await fetch(url, options);
  const data = await response.json();
  if (!response.ok) throw new Error(data.error || 'Không thể xử lý yêu cầu.');
  return data;
}

function lockCoreHooks() {
  const wrapper = editor.getWrapper();
  ['productGrid', 'cartPanel', 'cartItems', 'cartCount', 'cartTotal', 'panelCartTotal', 'closeCart', 'overlay', 'searchInput', 'searchBtn', 'cartButton', 'categoryBtn', 'categoryDropdown', 'menuToggle', 'navLinks'].forEach(id => {
    const found = wrapper.find(`#${id}`);
    if (found[0]) found[0].set({ removable: false, copyable: false });
  });
}

function loadPage(useDefault = false) {
  const saved = useDefault ? null : savedPage;
  editor.loadProjectData({ pages: [{ component: { type: 'wrapper', components: [] } }], styles: [], assets: [] });
  if (saved && saved.project) {
    editor.loadProjectData(saved.project);
  } else {
    editor.setComponents(template.body.innerHTML);
  }
  lockCoreHooks();
  editor.UndoManager.clear();
  editor.clearDirtyCount();
  needsSave = useDefault;
}

async function store(action) {
  if (busy) return;
  busy = true;
  saveButton.disabled = publishButton.disabled = resetButton.disabled = true;
  message.textContent = 'Đang lưu…';
  try {
    const doc = new DOMParser().parseFromString(editor.getHtml(), 'text/html');
    for (const id of requiredIds) {
      if (!doc.getElementById(id)) {
        throw new Error(`Thiếu thành phần bắt buộc (#${id}). Hãy hoàn tác hoặc khôi phục mặc định.`);
      }
    }
    const html = doc.body.innerHTML;
    const payload = { action, section: 'page', project: editor.getProjectData(), html, css: editor.getCss({ avoidProtected: true }) };
    await request('../../backend/editor.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
      body: JSON.stringify(payload)
    });
    savedPage = payload;
    editor.clearDirtyCount();
    needsSave = false;
    message.textContent = action === 'publish' ? 'Đã xuất bản toàn bộ trang chủ. Tải lại trang chủ để xem.' : 'Đã lưu bản nháp toàn trang trên máy chủ.';
  } catch (error) {
    message.textContent = error.message;
  } finally {
    busy = false;
    saveButton.disabled = publishButton.disabled = resetButton.disabled = false;
  }
}

async function init() {
  try {
    if (!window.grapesjs) throw new Error('Không tải được GrapesJS. Kiểm tra kết nối Internet và tải lại trang.');
    const [data, assetData] = await Promise.all([
      request('../../backend/editor.php'),
      request('../../backend/assets.php')
    ]);
    token = data.csrf;
    data.assets = assetData.assets || [];
    savedPage = data.page || data.sections?.page || null;
    const response = await fetch('index.html', { cache: 'no-store' });
    if (!response.ok) throw new Error('Không tải được mẫu trang chủ.');
    template = new DOMParser().parseFromString(await response.text(), 'text/html');
    template.body.querySelectorAll('script').forEach(el => el.remove());

    editor = grapesjs.init({
      container: '#editor',
      height: '100%',
      storageManager: false,
      assetManager: {
        assets: data.assets || [],
        upload: '../../backend/assets.php',
        uploadName: 'files',
        multiUpload: true,
        autoAdd: true,
        headers: { 'X-CSRF-Token': token },
        params: { _csrf: token },
        uploadText: 'Kéo ảnh vào đây hoặc bấm để tải lên (JPG, PNG, GIF, WebP · tối đa 10 MB/ảnh)',
        style: { width: '100%', height: '100%' }
      },
      parser: { optionsHtml: { allowScripts: false, allowUnsafeAttr: false } },
      canvas: { styles: [new URL('../css/style.css', location.href).href, 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap'] },
      deviceManager: {
        devices: [
          { name: 'Máy tính', width: '' },
          { name: 'Máy tính bảng', width: '768px', widthMedia: '820px' },
          { name: 'Điện thoại', width: '375px', widthMedia: '560px' }
        ]
      },
      blockManager: {
        blocks: [
          { id: 'text', label: 'Văn bản', content: '<p>Nội dung mới của bạn</p>' },
          { id: 'heading', label: 'Tiêu đề', content: '<h2>Tiêu đề mới</h2>' },
          { id: 'image', label: 'Hình ảnh', content: { type: 'image' } },
          { id: 'link', label: 'Nút liên kết', content: '<a href="#products" class="shop-btn">Mua ngay →</a>' },
          { id: 'section', label: 'Khối nội dung', content: '<section class="section container"><h2>Khám phá LeaFood</h2><p>Nội dung của bạn</p></section>' },
          {
            id: 'banner',
            label: 'Thẻ Banner',
            content: '<article class="banner banner-green"><div class="banner-content"><span class="sale">NEW DEAL</span><h2>Ưu đãi mới</h2><p>Mô tả ngắn gọn tại đây.</p><a href="#products" class="shop-btn">Xem ngay →</a></div><div class="banner-art"><span class="big-emoji">🥗</span></div></article>'
          },
          {
            id: 'product-card',
            label: 'Thẻ sản phẩm',
            content: '<article class="product-card"><div class="product-image"><span class="discount">10% OFF</span><span>🍏</span></div><div class="product-info"><span class="product-category">Fruits</span><h3 class="product-title">Sản phẩm mới</h3><div class="rating">★★★★★ <span style="color:#9a9a9a">(5.0)</span></div><div class="product-bottom"><div><span class="price">$5.00</span><span class="old-price">$6.00</span></div><button class="add-btn" type="button">+</button></div></div></article>'
          }
        ]
      }
    });

    editor.on('load', () => {
      try {
        loadPage();
        editor.refresh();
        saveButton.disabled = publishButton.disabled = resetButton.disabled = false;
        assetsButton.disabled = false;
        message.textContent = 'Chế độ chỉnh sửa toàn trang: Nhấp đúp để sửa chữ, kéo thả khối từ góc phải, hoặc sửa trực tiếp sản phẩm/banner.';
      } catch (error) {
        message.textContent = `Không thể mở trình chỉnh sửa: ${error.message}`;
      }
    });

    const editorResize = new ResizeObserver(() => editor.refresh());
    editorResize.observe(document.getElementById('editor'));

    saveButton.addEventListener('click', () => store('save'));
    assetsButton.addEventListener('click', () => editor.AssetManager.open());
    publishButton.addEventListener('click', () => {
      if (confirm('Xuất bản toàn bộ trang này lên trang chủ?')) store('publish');
    });
    resetButton.addEventListener('click', () => {
      if (busy || !confirm('Khôi phục toàn bộ trang về mẫu gốc? Thay đổi chưa lưu sẽ bị bỏ. Trang công khai chỉ thay đổi sau khi xuất bản.')) return;
      loadPage(true);
      editor.refresh();
      message.textContent = 'Đã khôi phục toàn bộ trang về mẫu gốc. Chọn Lưu nháp hoặc Xuất bản trang để lưu thay đổi.';
    });
    window.addEventListener('beforeunload', event => {
      if (needsSave || editor?.getDirtyCount()) {
        event.preventDefault();
        event.returnValue = '';
      }
    });
  } catch (error) {
    message.textContent = error.message;
  }
}

document.getElementById('logout').addEventListener('click', async () => {
  if ((needsSave || editor?.getDirtyCount()) && !confirm('Đăng xuất và bỏ thay đổi chưa lưu?')) return;
  try {
    const auth = await request('../../backend/auth.php');
    await request('../../backend/auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': auth.csrf },
      body: JSON.stringify({ action: 'logout' })
    });
    editor?.clearDirtyCount();
    needsSave = false;
    location.href = 'login.html';
  } catch (error) {
    message.textContent = error.message;
  }
});

init();

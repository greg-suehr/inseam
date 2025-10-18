/**
 * Ishere Page Editor
 * Drag-and-drop block-based page editor
 */

const PageEditor = {

  blocktree: [],
  selectedBlock: null,
  draggedBlock: null,
  assetPickerCallback: null,

  init() {
    this.blocktree = window.PAGE_DATA.blocktree || [];
    this.setupEventListeners();
    this.renderBlocks();
  },

  setupEventListeners() {
    // Palette drag events
    document.querySelectorAll('.block-palette-item').forEach(item => {
      item.addEventListener('dragstart', (e) => this.handlePaletteDragStart(e));
    });
    
    // Canvas drop events
    const grid = document.getElementById('blockGrid');
    grid.addEventListener('dragover', (e) => this.handleDragOver(e));
    grid.addEventListener('drop', (e) => this.handleDrop(e));

    // Save button
    document.getElementById('savePage').addEventListener('click', () => this.savePage());

    // Preview button
    document.getElementById('previewPage').addEventListener('click', () => this.previewPage());
    
    // Modal save button
    document.getElementById('modalSave').addEventListener('click', () => this.saveBlockConfig());
    
    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
          this.closeModal();
          this.closeAssetPicker();
        }
      });
    });
  },
  
  handlePaletteDragStart(e) {
    const blockType = e.target.dataset.blockType;
    e.dataTransfer.effectAllowed = 'copy';
    e.dataTransfer.setData('blockType', blockType);
    e.dataTransfer.setData('isNew', 'true');
  },
  
  handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'copy';
  },
  
  handleDrop(e) {
    e.preventDefault();
    
    const isNew = e.dataTransfer.getData('isNew') === 'true';
    const blockType = e.dataTransfer.getData('blockType');
    
    if (isNew && blockType) {
      this.addBlock(blockType);
    }
  },

  addBlock(type, data = {}) {
    const newBlock = this.createBlockData(type, data);
    this.blocktree.push(newBlock);
    this.renderBlocks();
    this.markUnsaved();
  },

  createBlockData(type, data = {}) {
    const defaults = {
      paragraph: { type: 'paragraph', text: 'New paragraph text...', data: {} },
      heading: { type: 'heading', data: { level: 2, text: 'New Heading' } },
      image: { type: 'image', data: { src: '', alt: '', assetId: null } },
      link: { type: 'link', data: { text: 'Link text', href: '#', external: false } },
      list: { type: 'list', data: { order: 'ul', items: ['Item 1', 'Item 2'] } },
      section: { type: 'section', data: { key: '', className: '' }, children: [] }
    };

    return { ...defaults[type], ...data };
  },

  renderBlocks() {
    const grid = document.getElementById('blockGrid');
    
    if (this.blocktree.length === 0) {
      grid.innerHTML = `
        <div class="empty-state" style="text-align: center; padding: 60px 20px; color: var(--editor-text-muted);">
          <p style="font-size: 18px; margin-bottom: 8px;">No blocks yet</p>
          <p style="font-size: 14px;">Drag blocks from the left sidebar to get started</p>
        </div>
      `;
      return;
    }

    grid.innerHTML = this.blocktree.map((block, index) => 
      this.renderBlock(block, index)
    ).join('');

    // Re-attach event listeners
    this.attachBlockEventListeners();
  },

  renderBlock(block, index) {
    const content = this.renderBlockContent(block);
    
    return `
      <div class="block-item" data-index="${index}" draggable="true">
        <div class="block-toolbar">
          <button class="block-toolbar-btn" onclick="PageEditor.moveBlock(${index}, 'up')" title="Move up">
            ↑
          </button>
          <button class="block-toolbar-btn" onclick="PageEditor.moveBlock(${index}, 'down')" title="Move down">
            ↓
          </button>
          <button class="block-toolbar-btn" onclick="PageEditor.configureBlock(${index})" title="Configure">
            ⚙️
          </button>
          <button class="block-toolbar-btn danger" onclick="PageEditor.deleteBlock(${index})" title="Delete">
            🗑
          </button>
        </div>
        <span class="block-type-label">${block.type}</span>
        <div class="block-content">
          ${content}
        </div>
      </div>
    `;
  },
  
  renderBlockContent(block) {
    switch (block.type) {
    case 'paragraph':
      return `<p>${this.escapeHtml(block.text || '')}</p>`;
      
    case 'heading':
      const level = block.data?.level || 2;
      const text = block.data?.text || '';
      return `<h${level}>${this.escapeHtml(text)}</h${level}>`;
      
    case 'image':
      const src = block.data?.src || 'https://via.placeholder.com/300x200?text=No+Image';
      const alt = block.data?.alt || '';
      return `<img src="${src}" alt="${this.escapeHtml(alt)}" style="max-width: 100%; height: auto; border-radius: 4px;">`;
      
    case 'link':
      const href = block.data?.href || '#';
      const linkText = block.data?.text || 'Link';
      return `<a href="${href}" style="color: var(--editor-primary); text-decoration: underline;">${this.escapeHtml(linkText)}</a>`;
      
    case 'list':
      const order = block.data?.order || 'ul';
      const items = block.data?.items || [];
      const listItems = items.map(item => `<li>${this.escapeHtml(item)}</li>`).join('');
      return `<${order}>${listItems}</${order}>`;
      
    case 'section':
      const children = block.children || [];
      const inner = children.map((child, i) => `
          <div class="block-item" data-nested="1" data-parent-type="section" data-index="-1">
            <span class="block-type-label">${this.escapeHtml(child.type)}</span>
            <div class="block-content">
              ${this.renderBlockContent(child)}
            </div>
          </div>
        `).join('');
      const key = block.data?.key || 'section';
      const cls = block.data?.className || '';
      return `
          <div style="border: 1px dashed #ccc; padding: 12px; border-radius: 6px;">
            <div style="font-size:12px;color:#888;margin-bottom:8px;">key: ${this.escapeHtml(key)} ${cls ? `(.${this.escapeHtml(cls)})` : ''}</div>
            ${inner || '<em style="color:#999">Empty section</em>'}
          </div>
        `;
    default:
      return `<pre>${this.escapeHtml(JSON.stringify(block, null, 2))}</pre>`;
    }
  },

  attachBlockEventListeners() {
    const grid = document.getElementById('blockGrid');
    if (!grid) return;

    grid.addEventListener('click', (e) => {
      const btn = e.target.closest('.block-toolbar-btn');
      if (!btn) return;
      const action = btn.dataset.action;
      const index = parseInt(btn.dataset.index, 10);
      if (Number.isNaN(index)) return;
      
      if (action === 'move-up')   this.moveBlock(index, 'up');
      if (action === 'move-down') this.moveBlock(index, 'down');
      if (action === 'configure') this.configureBlock(index);
      if (action === 'delete')    this.deleteBlock(index);
    });

    grid.querySelectorAll('.block-item').forEach(el => {
      el.addEventListener('click', (e) => {
        // Avoid clicks on toolbar re-triggering
        if (e.target.closest('.block-toolbar')) return;
        const idx = parseInt(el.dataset.index, 10);
        if (!Number.isNaN(idx)) this.selectBlock(idx);
      });
    });
  },

  moveBlock(index, dir) {
    if (dir === 'up' && index > 0) {
      [this.blocktree[index - 1], this.blocktree[index]] = [this.blocktree[index], this.blocktree[index - 1]];
    } else if (dir === 'down' && index < this.blocktree.length - 1) {
      [this.blocktree[index + 1], this.blocktree[index]] = [this.blocktree[index], this.blocktree[index + 1]];
    }
    this.renderBlocks();
    this.markUnsaved();
  },
  
  deleteBlock(index) {
    this.blocktree.splice(index, 1);
    this.renderBlocks();
    this.clearInspector();
    this.markUnsaved();
  },
  
  selectBlock(index) {
    this.selectedBlock = index;
    this.renderInspector();
  },

  renderInspector() {
    const panel = document.getElementById('inspectorPanel');
    if (!panel) return;
    
    if (this.selectedBlock == null || !this.blocktree[this.selectedBlock]) {
      this.clearInspector();
      return;
    }

    const block = this.blocktree[this.selectedBlock];
    panel.classList.remove('empty');
    panel.innerHTML = `
      <h3>Inspector</h3>
      <p style="margin-bottom:8px;"><strong>Type:</strong> ${this.escapeHtml(block.type)}</p>
      <div style="display:flex; gap:8px; margin-bottom:8px;">
        <button class="btn btn-secondary" id="inspectorConfigureBtn">Configure…</button>
        <button class="btn btn-secondary" id="inspectorDeleteBtn">Delete</button>
      </div>
      <div style="font-size:12px;color:#777">
        Index: ${this.selectedBlock}
      </div>
    `;
    
    document.getElementById('inspectorConfigureBtn').addEventListener('click', () => this.configureBlock(this.selectedBlock));
    document.getElementById('inspectorDeleteBtn').addEventListener('click', () => this.deleteBlock(this.selectedBlock));
  },

  clearInspector() {
    const panel = document.getElementById('inspectorPanel');
    if (!panel) return;
    panel.classList.add('empty');
    panel.innerHTML = `<p>Select a block to configure</p>`;
  },

  configureBlock(index) {
    const block = this.blocktree[index];
    if (!block) return;

    this.selectedBlock = index;

    const modal = document.getElementById('blockConfigModal');
    const body  = document.getElementById('modalBody');
    const title = document.getElementById('modalTitle');

    title.textContent = `Configure: ${block.type}`;

    // Forms per block type
    let form = '';
    if (block.type === 'paragraph') {
      form = `
        <div class="form-group">
          <label>Text</label>
          <textarea id="cfg_text">${this.escapeHtml(block.text || '')}</textarea>
        </div>
      `;
    } else if (block.type === 'heading') {
      form = `
        <div class="form-group">
          <label>Level (1-6)</label>
          <input type="number" id="cfg_level" min="1" max="6" value="${block.data?.level ?? 2}" />
        </div>
        <div class="form-group">
          <label>Text</label>
          <input type="text" id="cfg_text" value="${this.escapeAttr(block.data?.text || '')}" />
        </div>
      `;
    } else if (block.type === 'image') {
      form = `
        <div class="form-group">
          <label>Source URL</label>
          <input type="text" id="cfg_src" value="${this.escapeAttr(block.data?.src || '')}" />
        </div>
        <div class="form-group">
          <label>Alt text</label>
          <input type="text" id="cfg_alt" value="${this.escapeAttr(block.data?.alt || '')}" />
        </div>
        <div>
          <button class="btn btn-secondary" id="pickAssetBtn">Choose from assets…</button>
        </div>
      `;
    } else if (block.type === 'link') {
      form = `
        <div class="form-group">
          <label>Text</label>
          <input type="text" id="cfg_text" value="${this.escapeAttr(block.data?.text || '')}" />
        </div>
        <div class="form-group">
          <label>Href</label>
          <input type="text" id="cfg_href" value="${this.escapeAttr(block.data?.href || '')}" />
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" id="cfg_external" ${block.data?.external ? 'checked' : ''} />
            Open in new tab (external)
          </label>
        </div>
      `;
    } else if (block.type === 'list') {
      const items = Array.isArray(block.data?.items) ? block.data.items.join('\n') : '';
      form = `
        <div class="form-group">
          <label>Order</label>
          <select id="cfg_order">
            <option value="ul" ${block.data?.order !== 'ol' ? 'selected' : ''}>Unordered (•)</option>
            <option value="ol" ${block.data?.order === 'ol' ? 'selected' : ''}>Ordered (1.)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Items (one per line)</label>
          <textarea id="cfg_items">${this.escapeHtml(items)}</textarea>
        </div>
      `;
    } else if (block.type === 'section') {
      form = `
        <div class="form-group">
          <label>Key</label>
          <input type="text" id="cfg_key" value="${this.escapeAttr(block.data?.key || '')}" />
        </div>
        <div class="form-group">
          <label>Class Name</label>
          <input type="text" id="cfg_class" value="${this.escapeAttr(block.data?.className || '')}" />
        </div>
        <p style="font-size:12px;color:#777">Children can be managed in a future iteration.</p>
      `;
    } else {
      form = `<pre>${this.escapeHtml(JSON.stringify(block, null, 2))}</pre>`;
    }

    body.innerHTML = form;
    if (block.type === 'image') {
      const pickBtn = document.getElementById('pickAssetBtn');
      pickBtn?.addEventListener('click', () => {
        this.openAssetPicker(({ id, url }) => {
          const srcInput = document.getElementById('cfg_src');
          if (srcInput) srcInput.value = url || '';
          // Store asset id for save
          block.data = block.data || {};
          block.data.assetId = id || null;
        });
      });
    }

    modal.classList.add('active');
  },

  saveBlockConfig() {
    if (this.selectedBlock == null) return;
    const block = this.blocktree[this.selectedBlock];
    if (!block) return;

    if (block.type === 'paragraph') {
      block.text = document.getElementById('cfg_text')?.value || '';
    } else if (block.type === 'heading') {
      block.data = block.data || {};
      block.data.level = parseInt(document.getElementById('cfg_level')?.value || '2', 10);
      block.data.text  = document.getElementById('cfg_text')?.value || '';
    } else if (block.type === 'image') {
      block.data = block.data || {};
      block.data.src = document.getElementById('cfg_src')?.value || '';
      block.data.alt = document.getElementById('cfg_alt')?.value || '';
    } else if (block.type === 'link') {
      block.data = block.data || {};
      block.data.text     = document.getElementById('cfg_text')?.value || '';
      block.data.href     = document.getElementById('cfg_href')?.value || '';
      block.data.external = !!document.getElementById('cfg_external')?.checked;
    } else if (block.type === 'list') {
      block.data = block.data || {};
      block.data.order = document.getElementById('cfg_order')?.value === 'ol' ? 'ol' : 'ul';
      const raw = document.getElementById('cfg_items')?.value || '';
      block.data.items = raw.split('\n').map(s => s.trim()).filter(Boolean);
    } else if (block.type === 'section') {
      block.data = block.data || {};
      block.data.key       = document.getElementById('cfg_key')?.value || '';
      block.data.className = document.getElementById('cfg_class')?.value || '';
    }

    this.closeModal();
    this.renderBlocks();
    this.renderInspector();
    this.markUnsaved();
  },
  
  closeModal() {
    const modal = document.getElementById('blockConfigModal');
    modal?.classList.remove('active');
  },
  
  async openAssetPicker(callback) {
    const overlay = document.getElementById('assetPickerModal');
    const body    = document.getElementById('assetPickerBody');
    body.innerHTML = '<p>Loading…</p>';
    overlay.classList.add('active');
    this.assetPickerCallback = callback;
    
    try {
      const url = (window.PAGE_DATA && window.PAGE_DATA.assetBrowseUrl) || '';
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const html = await res.text();
      body.innerHTML = html;

      body.addEventListener('click', (e) => {
        const tile = e.target.closest('[data-asset-id]');
        if (!tile) return;
        const id  = tile.getAttribute('data-asset-id');
        const url = tile.getAttribute('data-url') || tile.querySelector('img')?.getAttribute('src') || '';
        const fn  = this.assetPickerCallback;
        this.assetPickerCallback = null;
        this.closeAssetPicker();
        if (fn) fn({ id, url });
      });
    } catch (e) {
      body.innerHTML = '<p style="color:#c00">Failed to load assets.</p>';
    }
  },

  closeAssetPicker() {
    const overlay = document.getElementById('assetPickerModal');
    overlay?.classList.remove('active');
  },

  async savePage() {
    const url = (window.PAGE_DATA && window.PAGE_DATA.saveUrl) || '';
    const payload = { blocktree: this.blocktree };
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(payload),
      });
      if (!res.ok) throw new Error('Save failed');
      this.showSaved();
    } catch (e) {
      alert('Failed to save page.');
    }
  },

  previewPage() {
    const w = window.open('', '_blank');
    if (!w) return;
    const html = `
      <html><head><title>Preview</title>
      <meta charset="utf-8" />
      <meta name="viewport" content="width=device-width,initial-scale=1" />
      </head><body style="font-family:sans-serif;padding:24px;max-width:800px;margin:0 auto;">
        ${this.blocktree.map(b => this.renderBlockContent(b)).join('')}
      </body></html>`;
    w.document.open();
    w.document.write(html);
    w.document.close();
  },

  markUnsaved() {
    // TODO: toggle dirty flag and show on UI
  },
  
  showSaved() {
    const el = document.getElementById('saveIndicator');
    if (!el) return;
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 1200);
  },
  
  escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  },

  escapeAttr(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;');
  },
};

window.PageEditor = PageEditor;
document.addEventListener('DOMContentLoaded', () => PageEditor.init());
export {};

/*
    *For AUTH (login and register func)
*/

document.addEventListener('DOMContentLoaded', () => {
    // Login state button
    (function setupLoginForm() {
      const email = document.getElementById('email');
      const password = document.getElementById('password');
      const loginBtn = document.getElementById('loginBtn');
      if (!email || !password || !loginBtn) return;
  
      const update = () => {
        const enabled = email.value.trim().length > 0 && password.value.trim().length > 0;
        loginBtn.disabled = !enabled;
        loginBtn.classList.toggle('disabled', !enabled);
      };
      email.addEventListener('input', update);
      password.addEventListener('input', update);
      update();
    })();
  
    // Password toggle visibility (supports multiple toggles)
    (function setupPasswordToggles() {
      const toggles = document.querySelectorAll('.password-toggle');
      if (!toggles.length) return;

      const eyeSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
      const eyeOffSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 11 8 11 8a20.16 20.16 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line><path d="M8.5 8.5A10.43 10.43 0 0 0 1 12s4 8 11 8a10.43 10.43 0 0 0 4.5-1.08"></path></svg>';

      toggles.forEach(btn => {
        const targetId = btn.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (!input) return;

        btn.addEventListener('click', () => {
          const hidden = input.type === 'password';
          input.type = hidden ? 'text' : 'password';
          btn.innerHTML = hidden ? eyeOffSvg : eyeSvg;
        });

        btn.innerHTML = eyeSvg;
      });
    })();
  
    // Register needs 
    (function setupRegisterEnhancements() {
      const roleOptions = document.querySelectorAll('.role-option');
      const sellerFields = document.getElementById('sellerFields');
      const editorContainer = document.getElementById('storeDescriptionEditor');
      const hiddenDesc = document.getElementById('store_description');
      const storeName = document.getElementById('store_name');
      const form = document.querySelector('form[method="POST"]');
      if (!roleOptions.length || !sellerFields || !form) return;
  
      let quill = window.__registerQuillInstance || null;
  
      const ensureQuill = (cb) => {
        if (!document.querySelector('link[href*="quill.snow.css"]')) {
          const link = document.createElement('link');
          link.rel = 'stylesheet';
          link.href = 'https://cdn.quilljs.com/1.3.6/quill.snow.css';
          document.head.appendChild(link);
        }
        if (typeof Quill === 'undefined') {
          const s = document.createElement('script');
          s.src = 'https://cdn.quilljs.com/1.3.6/quill.min.js';
          s.onload = cb;
          document.head.appendChild(s);
        } else cb();
      };
  
      const initQuill = () => {
        if (!editorContainer || quill) return;
        ensureQuill(() => {
          if (window.__registerQuillInstance) return;
          quill = new Quill('#storeDescriptionEditor', {
            theme: 'snow',
            modules: {
              toolbar: [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ color: [] }, { background: [] }],
                [{ list: 'ordered' }, { list: 'bullet' }],
                [{ align: [] }],
                ['link', 'image'],
                ['clean']
              ]
            },
            placeholder: 'Tulis deskripsi toko Anda di sini...'
          });
          window.__registerQuillInstance = quill;
        });
      };
  
      const selectRole = (role, source) => {
        const radio = document.querySelector(`input[name="role"][value="${role}"]`);
        if (radio) radio.checked = true;
        roleOptions.forEach(o => o.classList.remove('selected'));
        if (source) source.classList.add('selected');
        sellerFields.classList.toggle('show', role === 'seller');
        
        // Toggle required attribute based on role
        const storeName = document.getElementById('store_name');
        const storeDesc = document.getElementById('store_description');
        if (storeName) {
          storeName.required = role === 'seller';
        }
        if (storeDesc) {
          storeDesc.required = role === 'seller';
        }
        
        if (role === 'seller') initQuill();
      };
  
      roleOptions.forEach(option => {
        option.addEventListener('click', (e) => {
          const role = e.currentTarget.querySelector('input[name="role"]')?.value;
          if (role) selectRole(role, e.currentTarget);
        });
      });
  
      const current = document.querySelector('input[name="role"]:checked')?.value;
      if (current === 'seller') {
        sellerFields.classList.add('show');
        roleOptions[1]?.classList.add('selected');
        initQuill();
      } else {
        roleOptions[0]?.classList.add('selected');
      }
  
      form.addEventListener('submit', (e) => {
        const role = document.querySelector('input[name="role"]:checked')?.value;
        if (role === 'seller') {
          if (!storeName || !storeName.value.trim()) {
            e.preventDefault();
            alert('Store name must be filled!');
            return;
          }
          if (quill && hiddenDesc) {
            const txt = quill.getText().trim();
            if (!txt) {
              e.preventDefault();
              alert('Store description must be filled!');
              return;
            }
            hiddenDesc.value = quill.root.innerHTML;
          }
        } else {
          // Clear seller fields when buyer is selected
          if (storeName) storeName.value = '';
          if (hiddenDesc) hiddenDesc.value = '';
        }
      });
    })();
  });
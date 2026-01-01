// Client-side validation for ad image uploads
(function(){
  function humanSize(bytes){
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024*1024) return Math.round(bytes/1024) + ' KiB';
    return Math.round(bytes/(1024*1024)) + ' MiB';
  }

  var input = document.querySelector('input[name="photos[]"]') || document.getElementById('photos-input');
  var form = input && input.form ? input.form : null;
  if (!input || !form) return;

  // create or reuse error box
  var errorBox = document.getElementById('upload-errors');
  if (!errorBox) {
    errorBox = document.createElement('div');
    errorBox.id = 'upload-errors';
    errorBox.style.color = '#b00020';
    errorBox.style.marginTop = '8px';
    input.parentNode.insertBefore(errorBox, input.nextSibling);
  }

  // preview container
  var preview = document.getElementById('photo-previews');
  if (!preview) {
    preview = document.createElement('div');
    preview.id = 'photo-previews';
    preview.style.display = 'flex';
    preview.style.gap = '8px';
    preview.style.marginTop = '8px';
    input.parentNode.insertBefore(preview, errorBox.nextSibling);
  }

  function validateFiles(files){
    var errors = [];
    if (files.length > 5) {
      errors.push('Maximum 5 fichiers autorisés (sélectionnés: ' + files.length + ').');
    }
    for (var i=0;i<files.length;i++){
      var f = files[i];
      if (f.type !== 'image/jpeg' && f.type !== 'image/pjpeg'){
        errors.push('Le fichier "' + f.name + '" n\'est pas un JPEG.');
      }
      if (f.size > 200*1024){
        errors.push('Le fichier "' + f.name + '" est trop volumineux (' + humanSize(f.size) + '). Max 200 KiB.');
      }
    }
    return errors;
  }

  function renderPreviews(fileList){
    preview.innerHTML = '';
    Array.prototype.forEach.call(fileList, function(file, idx){
      var box = document.createElement('div');
      box.style.display = 'flex';
      box.style.flexDirection = 'column';
      box.style.alignItems = 'center';
      box.style.fontSize = '12px';

      var img = document.createElement('img');
      img.style.width = '120px';
      img.style.height = '90px';
      img.style.objectFit = 'cover';
      img.style.borderRadius = '6px';
      img.style.boxShadow = '0 3px 8px rgba(0,0,0,0.08)';

      var reader = new FileReader();
      reader.onload = function(e){ img.src = e.target.result; };
      reader.readAsDataURL(file);

      var name = document.createElement('div');
      name.textContent = file.name + ' (' + humanSize(file.size) + ')';
      name.style.marginTop = '6px';

      var remove = document.createElement('button');
      remove.type = 'button';
      remove.textContent = 'Retirer';
      remove.style.marginTop = '6px';
      remove.style.padding = '4px 8px';
      remove.style.border = 'none';
      remove.style.background = '#e53e3e';
      remove.style.color = '#fff';
      remove.style.borderRadius = '4px';
      // use a stable identifier (name + size) to remove files reliably
      var fid = file.name + '||' + file.size;
      remove.setAttribute('data-fileid', fid);
      remove.addEventListener('click', function(){
        removeFileById(fid);
      });

      box.appendChild(img);
      box.appendChild(name);
      box.appendChild(remove);
      preview.appendChild(box);
    });
  }

  function removeFileById(fileId){
    var newDt = new DataTransfer();
    var files = Array.prototype.slice.call(input.files || []);
    files.forEach(function(f){
      var id = f.name + '||' + f.size;
      if (id !== fileId) {
        newDt.items.add(f);
      }
    });
    // update both the input and internal dt
    input.files = newDt.files;
    dt = newDt;
    // re-render
    renderPreviews(dt.files);
    // re-validate
    var errs = validateFiles(dt.files);
    if (errs.length) {
      errorBox.innerHTML = errs.map(function(x){ return '<div>'+x+'</div>'; }).join('');
    } else {
      errorBox.innerHTML = '<div style="color:green">Fichiers valides — prêts à être envoyés.</div>';
    }
  }

  // Maintain an internal DataTransfer to allow adding files across multiple selects
  var dt = new DataTransfer();

  input.addEventListener('change', function(e){
    var newFiles = Array.prototype.slice.call(e.target.files || []);
    // If already at limit, show explicit alert and don't add
    if ((dt.files && dt.files.length >= 5) && newFiles.length > 0) {
      alert('Impossible d\'ajouter une photo — limite de 5 photos atteinte.');
      // clear the input selection so the user can re-open file dialog
      try { input.value = ''; } catch (ex) {}
      // restore input.files from internal dt so files are preserved for submit
      try { input.files = dt.files; } catch (ex) {}
      return;
    }

    var combined = Array.prototype.slice.call(dt.files || []);

    // Append new files while respecting max 5 and avoiding duplicates by name+size
    newFiles.forEach(function(f){
      if (combined.length >= 5) return;
      var duplicate = combined.some(function(existing){ return existing.name === f.name && existing.size === f.size; });
      if (!duplicate) combined.push(f);
    });

    // Validate combined selection
    var errors = validateFiles(combined);
    if (errors.length) {
      errorBox.innerHTML = errors.map(function(x){ return '<div>'+x+'</div>'; }).join('');
      // still accept up to first 5 valid files
    } else {
      errorBox.innerHTML = '<div style="color:green">Fichiers valides — prêts à être envoyés.</div>';
    }

    // rebuild DataTransfer from combined (limit 5)
    dt = new DataTransfer();
    combined.slice(0,5).forEach(function(f){ dt.items.add(f); });
    input.files = dt.files;
    renderPreviews(dt.files);
  });

  // prevent submit if invalid
  form.addEventListener('submit', function(e){
    var files = Array.prototype.slice.call(input.files || []);
    var errors = validateFiles(files);
    if (errors.length){
      e.preventDefault();
      errorBox.innerHTML = errors.map(function(x){ return '<div>'+x+'</div>'; }).join('');
      window.scrollTo({ top: errorBox.getBoundingClientRect().top + window.pageYOffset - 80, behavior: 'smooth' });
      return;
    }

    // Count existing photos not marked for removal (only in edit mode)
    var existingPhotos = form.querySelectorAll('input[name="remove_images[]"]');
    var existingCount = 0;
    if (existingPhotos.length > 0) {
      // In edit mode: count photos not checked for removal
      existingPhotos.forEach(function(cb){
        if (!cb.checked) existingCount++;
      });
    }

    var newCount = files.length;
    var totalCount = existingCount + newCount;

    // Validation passed - form will be submitted with confirmation modal
  });
})();

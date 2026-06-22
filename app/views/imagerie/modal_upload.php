<!-- ══════════════════════════════════════════════════════════════
     MODALE UPLOAD / AJOUT / REMPLACEMENT — MULTI-FICHIERS DICOM
══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden">

            <!-- En-tête dynamique -->
            <div class="modal-header border-0 p-4" id="uploadModalHeader"
                 style="background:linear-gradient(135deg,#1d4ed8,#2563eb)">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0" id="uploadModalTitle">
                        <i class="bi bi-cloud-upload me-2"></i>Compléter l'examen
                    </h5>
                    <div class="text-white opacity-75 small mt-1" id="uploadModalSubtitle"></div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Alerte mode remplacement -->
            <div id="uploadReplaceAlert" class="alert alert-warning rounded-0 mb-0 d-none"
                 style="font-size:.84rem;border-radius:0">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Remplacement :</strong> tous les fichiers DICOM existants seront supprimés.
                Le statut repassera à <strong>« À interpréter »</strong>.
            </div>

            <!-- Alerte mode ajout -->
            <div id="uploadAddAlert" class="alert alert-info rounded-0 mb-0 d-none"
                 style="font-size:.84rem;border-radius:0">
                <i class="bi bi-plus-circle-fill me-2"></i>
                <strong>Ajout :</strong> les nouveaux fichiers seront ajoutés aux fichiers existants.
            </div>

            <!-- Alerte minimum fichiers requis -->
            <div id="minFilesAlert" class="alert alert-primary rounded-0 mb-0 d-none"
                 style="font-size:.84rem;border-radius:0">
                <i class="bi bi-paperclip me-2"></i>
                Cet examen comprend <strong><span id="minFilesExamCount">1</span> examen(s)</strong>
                — joindre au minimum <strong><span id="minFilesReqCount">1</span> fichier(s)</strong>.
            </div>

            <form id="uploadForm" action="<?= BASE_URL ?>imagerie/upload" method="POST"
                  enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="imagerie_id"         id="imagerie_selector">
                    <input type="hidden" name="mode"               id="upload_mode" value="new">
                    <input type="hidden" name="imagerie_ids_groupe" id="imagerie_ids_groupe" value="">
                    <input type="hidden" name="nb_examens_requis"   id="nb_examens_requis"   value="1">
                    <input type="hidden" name="marquer_termine"     id="marquer_termine_hidden" value="1">

                    <!-- Zone de dépôt / sélection de fichiers -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-primary d-flex align-items-center gap-2">
                            <i class="bi bi-files"></i>
                            FICHIERS DICOM / IMAGES
                            <span class="badge bg-primary rounded-pill" id="fileCountBadge"
                                  style="font-size:.65rem;display:none">0</span>
                        </label>

                        <!-- Zone drop -->
                        <div id="dropZone"
                             onclick="document.getElementById('dicom_file_input').click()"
                             style="border:2px dashed #93c5fd;border-radius:12px;padding:28px 20px;
                                    text-align:center;cursor:pointer;transition:all .2s;background:#eff6ff">
                            <i class="bi bi-cloud-arrow-up" style="font-size:2rem;color:#3b82f6"></i>
                            <div class="fw-semibold text-primary mt-2" style="font-size:.88rem">
                                Cliquer ou déposer les fichiers ici
                            </div>
                            <div class="text-muted" style="font-size:.75rem;margin-top:4px">
                                Formats : .dcm, .dicom, .jpg, .png &nbsp;·&nbsp;
                                <strong>Sélection multiple possible</strong>
                            </div>
                        </div>

                        <input type="file" name="dicom_file[]" id="dicom_file_input"
                               class="d-none"
                               accept=".dcm,.dicom,.jpg,.jpeg,.png"
                               multiple>

                        <!-- Liste des fichiers sélectionnés -->
                        <div id="fileList" class="mt-3" style="display:none">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-semibold text-primary small">
                                    Fichiers sélectionnés :
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill"
                                        onclick="clearFiles()" style="font-size:.72rem">
                                    <i class="bi bi-x-circle me-1"></i>Vider
                                </button>
                            </div>
                            <div id="fileItems" class="d-flex flex-column gap-1"></div>
                        </div>
                    </div>

                    <!-- Compte-rendu (masqué en mode "add") -->
                    <div id="uploadReportSection">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-primary">
                                <i class="bi bi-file-text me-1"></i>COMPTE-RENDU / INTERPRÉTATION
                            </label>
                            <textarea name="interpretation" id="upload_interpretation"
                                      class="form-control border-2" rows="4"
                                      placeholder="Saisir vos observations ici…"></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold text-primary">
                                <i class="bi bi-check-circle me-1"></i>CONCLUSION DIAGNOSTIQUE
                            </label>
                            <input type="text" name="conclusion" id="upload_conclusion"
                                   class="form-control border-2"
                                   placeholder="Conclusion en une phrase…">
                        </div>
                    </div>
                </div>

                <!-- ── Toggle : Marquer comme terminé ── -->
                <div class="mx-1 mb-3 p-3 rounded-3" id="termineToggleWrap"
                     style="background:#f0fdf4;border:1.5px solid #bbf7d0">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-bold text-success" style="font-size:.88rem">
                                <i class="bi bi-check-circle-fill me-2"></i>Marquer l'examen comme terminé
                            </div>
                            <div class="text-muted" style="font-size:.76rem;margin-top:2px">
                                Confirme que tous les examens du groupe ont été réalisés et transmis.
                            </div>
                        </div>
                        <div class="form-check form-switch ms-3 mb-0">
                            <input class="form-check-input" type="checkbox"
                                   id="marquer_termine_check"
                                   style="width:2.5rem;height:1.3rem;cursor:pointer"
                                   checked>
                        </div>
                    </div>
                    <!-- Avertissement si non coché -->
                    <div id="termineOffWarning" class="mt-2 d-none"
                         style="font-size:.76rem;color:#92400e;background:#fef3c7;
                                border-radius:6px;padding:5px 10px">
                        <i class="bi bi-info-circle me-1"></i>
                        Les fichiers seront sauvegardés mais l'examen restera <strong>En attente</strong>.
                    </div>
                </div>

                <div class="modal-footer border-0 p-4 pt-0 d-flex justify-content-between align-items-center">
                    <div class="text-muted small" id="uploadProgress" style="display:none">
                        <span class="spinner-border spinner-border-sm me-1"></span>
                        Envoi en cours…
                    </div>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                                data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" id="uploadSubmitBtn"
                                class="btn btn-primary btn-lg rounded-pill fw-bold shadow px-5">
                            <i class="bi bi-send-check me-1"></i>ENREGISTRER
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
#dropZone:hover, #dropZone.dragover {
    background: #dbeafe !important;
    border-color: #2563eb !important;
}
.file-item {
    display:flex; align-items:center; gap:8px;
    background:#f8fafc; border:1px solid #e2e8f0;
    border-radius:8px; padding:7px 12px; font-size:.78rem;
}
.file-item i { color:#3b82f6; font-size:1rem; }
.file-item .fname { flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.file-item .fsize { color:#94a3b8; white-space:nowrap; }
</style>

<script>
(function () {
    const input    = document.getElementById('dicom_file_input');
    const dropZone = document.getElementById('dropZone');
    const fileList = document.getElementById('fileList');
    const fileItems= document.getElementById('fileItems');
    const badge    = document.getElementById('fileCountBadge');

    // Drag & drop
    dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault(); dropZone.classList.remove('dragover');
        // Simuler une sélection par drag
        const dt = e.dataTransfer;
        if (dt && dt.files.length) {
            const list = new DataTransfer();
            // Merge avec les fichiers déjà dans l'input
            if (input.files) [...input.files].forEach(f => list.items.add(f));
            [...dt.files].forEach(f => list.items.add(f));
            input.files = list.files;
            renderFileList();
        }
    });

    input.addEventListener('change', renderFileList);

    function renderFileList() {
        const files = input.files;
        fileItems.innerHTML = '';
        if (!files || files.length === 0) {
            fileList.style.display = 'none';
            badge.style.display = 'none';
            return;
        }
        fileList.style.display = '';
        badge.style.display = '';
        badge.textContent = files.length;

        const exts = { dcm:'bi-file-earmark-medical', dicom:'bi-file-earmark-medical',
                       jpg:'bi-file-earmark-image', jpeg:'bi-file-earmark-image',
                       png:'bi-file-earmark-image' };

        [...files].forEach((f, i) => {
            const ext  = f.name.split('.').pop().toLowerCase();
            const icon = exts[ext] || 'bi-file-earmark';
            const size = f.size > 1048576
                ? (f.size / 1048576).toFixed(1) + ' Mo'
                : Math.round(f.size / 1024) + ' Ko';
            const div = document.createElement('div');
            div.className = 'file-item';
            div.innerHTML = `<i class="bi ${icon}"></i>
                <span class="fname">${i+1}. ${f.name}</span>
                <span class="fsize">${size}</span>`;
            fileItems.appendChild(div);
        });
    }

    window.clearFiles = function () {
        input.value = '';
        fileItems.innerHTML = '';
        fileList.style.display = 'none';
        badge.style.display = 'none';
        badge.textContent = '0';
    };

    // ── Toggle "Marquer comme terminé" ──────────────────────────────
    const termineCheck   = document.getElementById('marquer_termine_check');
    const termineHidden  = document.getElementById('marquer_termine_hidden');
    const termineWarn    = document.getElementById('termineOffWarning');
    const termineWrap    = document.getElementById('termineToggleWrap');

    termineCheck.addEventListener('change', function () {
        termineHidden.value = this.checked ? '1' : '0';
        if (this.checked) {
            termineWrap.style.background    = '#f0fdf4';
            termineWrap.style.borderColor   = '#bbf7d0';
            termineWarn.classList.add('d-none');
        } else {
            termineWrap.style.background    = '#fffbeb';
            termineWrap.style.borderColor   = '#fde68a';
            termineWarn.classList.remove('d-none');
        }
    });

    // ── Validation avant soumission ──────────────────────────────────
    document.getElementById('uploadForm').addEventListener('submit', function (ev) {
        const nbReq     = parseInt(document.getElementById('nb_examens_requis').value) || 1;
        const nbFiles   = input.files ? input.files.length : 0;
        const mustDone  = termineCheck.checked;

        if (mustDone && nbFiles < nbReq) {
            ev.preventDefault();
            ev.stopImmediatePropagation();
            // Met en évidence la zone de dépôt
            dropZone.style.borderColor  = '#dc2626';
            dropZone.style.background   = '#fef2f2';
            const errMsg = document.getElementById('minFilesErrMsg');
            if (!errMsg) {
                const div = document.createElement('div');
                div.id        = 'minFilesErrMsg';
                div.className = 'alert alert-danger mt-2 mb-0 py-2';
                div.style.fontSize = '.82rem';
                div.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Minimum <strong>${nbReq} fichier(s)</strong> requis pour cet examen.
                    Vous en avez sélectionné <strong>${nbFiles}</strong>.`;
                fileList.parentNode.insertBefore(div, fileList.nextSibling);
            }
            return false;
        }
        // Retirer l'erreur si ok
        const oldErr = document.getElementById('minFilesErrMsg');
        if (oldErr) oldErr.remove();
        dropZone.style.borderColor = '#93c5fd';
        dropZone.style.background  = '#eff6ff';

        if (!input.files || input.files.length === 0) return;
        document.getElementById('uploadProgress').style.display = '';
        document.getElementById('uploadSubmitBtn').disabled = true;
    });
})();
</script>
